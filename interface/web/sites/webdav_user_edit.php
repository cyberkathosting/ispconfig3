<?php
/*
Copyright (c) 2010 Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/webdav_user.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_webdav_user')) {
				$app->error($app->tform->wordbook["limit_webdav_user_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_webdav_user')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_webdav_user_txt"]);
			}
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf, $interfaceConf;
		/*
		 * If the names are restricted -> remove the restriction, so that the
		 * data can be edited
		*/
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$webdavuser_prefix = $app->tools_sites->replacePrefix($global_config['webdavuser_prefix'], $this->dataRecord);

		if ($this->dataRecord['username'] != "") {
			/* REMOVE the restriction */
			$app->tpl->setVar("username", str_replace($webdavuser_prefix , '', $this->dataRecord['username']));
		}
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$app->tpl->setVar("username_prefix", $global_config['webdavuser_prefix']);
		} else {
			$app->tpl->setVar("username_prefix", $webdavuser_prefix);
		}

		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("parent_domain_id_value", $this->dataRecord["parent_domain_id"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		/* Get the record of the parent domain */
		$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ".intval(@$this->dataRecord["parent_domain_id"]));

		/*
		 * Set a few fixed values
		 */
		$this->dataRecord["server_id"] = $parent_domain["server_id"];

		/*
		 * Are there some errors?
		 */
		if(isset($this->dataRecord['username']) && trim($this->dataRecord['username']) == '') $app->tform->errorMessage .= $app->tform->lng('username_error_empty').'<br />';
		if(isset($this->dataRecord['username']) && empty($this->dataRecord['parent_domain_id'])) $app->tform->errorMessage .= $app->tform->lng('parent_domain_id_error_empty').'<br />';
		if(isset($this->dataRecord['dir']) && stristr($this->dataRecord['dir'],'..')) $app->tform->errorMessage .= $app->tform->lng('dir_dot_error').'<br />';
		if(isset($this->dataRecord['dir']) && stristr($this->dataRecord['dir'],'./')) $app->tform->errorMessage .= $app->tform->lng('dir_slashdot_error').'<br />';
		
		parent::onSubmit();
	}

	function onBeforeInsert() {
		global $app, $conf, $interfaceConf;

		/*
		 * If the names should be restricted -> do it!
		*/
		if ($app->tform->errorMessage == '') {

			$app->uses('getconf,tools_sites');
			$global_config = $app->getconf->get_global_config('sites');
			$webdavuser_prefix = $app->tools_sites->replacePrefix($global_config['webdavuser_prefix'], $this->dataRecord);

			/* restrict the names */
			$this->dataRecord['username'] = $webdavuser_prefix . $this->dataRecord['username'];

			/*
			 * We shall not save the pwd in plaintext, so we store it as the hash, the apache-moule needs
			 */
			$hash = md5($this->dataRecord["username"] . ':' . $this->dataRecord["dir"] . ':' . $this->dataRecord["password"]);
			$this->dataRecord["password"] = $hash;

			/*
			*  Get the data of the domain, owning the webdav user
			*/
			$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($this->dataRecord["parent_domain_id"]));
			/* The server is the server of the domain */
			$this->dataRecord["server_id"] = $web["server_id"];
			/* The Webdav user shall be owned by the same group then the website */
			$this->dataRecord["sys_groupid"] = $web['sys_groupid'];
		}

		parent::onBeforeInsert();
	}

	function onAfterInsert() {
		global $app, $conf;
	}

	function onBeforeUpdate() {
		global $app, $conf, $interfaceConf;

		/*
		 * we can not change the username and the dir, so get the "old" - data from the db
		 * and set it
		*/
		$data = $app->db->queryOneRecord("SELECT * FROM webdav_user WHERE webdav_user_id = ".intval($this->id));
		$this->dataRecord["username"] = $data['username'];
		$this->dataRecord["dir"]      = $data['dir'];
		$passwordOld = $data['password'];

		/*
		 * We shall not save the pwd in plaintext, so we store it as the hash, the apache-moule
		 * needs (only if the pwd is changed)
		 */
		if ((isset($this->dataRecord["password"])) && ($this->dataRecord["password"] != '') && ($this->dataRecord["password"] != $passwordOld)) {
			$hash = md5($this->dataRecord["username"] . ':' . $this->dataRecord["dir"] . ':' . $this->dataRecord["password"]);
			$this->dataRecord["password"] = $hash;
		}

		parent::onBeforeUpdate();
	}

	function onAfterUpdate() {
		global $app, $conf;
	}
}

$page = new page_action;
$page->onLoad();

?>