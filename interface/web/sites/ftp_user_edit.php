<?php
/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/ftp_user.tform.php";

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
			if(!$app->tform->checkClientLimit('limit_ftp_user')) {
				$app->error($app->tform->wordbook["limit_ftp_user_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_ftp_user')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_ftp_user_txt"]);
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
		$ftpuser_prefix = $app->tools_sites->replacePrefix($global_config['ftpuser_prefix'], $this->dataRecord);
		
		if ($this->dataRecord['username'] != ""){
			/* REMOVE the restriction */
			$app->tpl->setVar("username", preg_replace('/'.$ftpuser_prefix.'/' , '', $this->dataRecord['username'], 1));
		}
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$app->tpl->setVar("username_prefix", $global_config['ftpuser_prefix']);
		}
		else {
			$app->tpl->setVar("username_prefix", $ftpuser_prefix);
		}

		parent::onShowEnd();
	}
	
	function onSubmit() {
		global $app, $conf;
		
		// Get the record of the parent domain
		$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ".$app->functions->intval(@$this->dataRecord["parent_domain_id"]));
		
		// Set a few fixed values
		$this->dataRecord["server_id"] = $parent_domain["server_id"];
		
		//die(print_r($this->dataRecord));
		
		if(isset($this->dataRecord['username']) && trim($this->dataRecord['username']) == '') $app->tform->errorMessage .= $app->tform->lng('username_error_empty').'<br />';
		if(isset($this->dataRecord['username']) && empty($this->dataRecord['parent_domain_id'])) $app->tform->errorMessage .= $app->tform->lng('parent_domain_id_error_empty').'<br />';
		if(isset($this->dataRecord['dir']) && stristr($this->dataRecord['dir'],'..')) $app->tform->errorMessage .= $app->tform->lng('dir_dot_error').'<br />';
		if(isset($this->dataRecord['dir']) && stristr($this->dataRecord['dir'],'./')) $app->tform->errorMessage .= $app->tform->lng('dir_slashdot_error').'<br />';
		
		parent::onSubmit();
	}
	
	function onBeforeInsert() {
		global $app, $conf, $interfaceConf;
		
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$ftpuser_prefix = $app->tools_sites->replacePrefix($global_config['ftpuser_prefix'], $this->dataRecord);
		
		if ($app->tform->errorMessage == '') {
			$this->dataRecord['username'] = $ftpuser_prefix . $this->dataRecord['username'];
		}
		
		parent::onBeforeInsert();
	}

		function onAfterInsert() {
		global $app, $conf;
		
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".$app->functions->intval($this->dataRecord["parent_domain_id"]));
		$server_id = $web["server_id"];
		$dir = $web["document_root"];
		$uid = $web["system_user"];
		$gid = $web["system_group"];
		
		// The FTP user shall be owned by the same group then the website
		$sys_groupid = $web['sys_groupid'];
		
		$sql = "UPDATE ftp_user SET server_id = $server_id, dir = '$dir', uid = '$uid', gid = '$gid', sys_groupid = '$sys_groupid' WHERE ftp_user_id = ".$this->id;
		$app->db->query($sql);
		}

	function onBeforeUpdate() {
		global $app, $conf, $interfaceConf;

		/*
		 * If the names should be restricted -> do it!
		 */
		
		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$ftpuser_prefix = $app->tools_sites->replacePrefix($global_config['ftpuser_prefix'], $this->dataRecord);
		
		/* restrict the names */
		if ($app->tform->errorMessage == '') {
			$this->dataRecord['username'] = $ftpuser_prefix . $this->dataRecord['username'];
		}
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
		//* When the site of the FTP user has been changed
		if(isset($this->dataRecord['parent_domain_id']) && $this->oldDataRecord['parent_domain_id'] != $this->dataRecord['parent_domain_id']) {
			$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".$app->functions->intval($this->dataRecord["parent_domain_id"]));
			$server_id = $web["server_id"];
			$dir = $web["document_root"];
			$uid = $web["system_user"];
			$gid = $web["system_group"];
		
			// The FTP user shall be owned by the same group then the website
			$sys_groupid = $web['sys_groupid'];
		
			$sql = "UPDATE ftp_user SET server_id = $server_id, dir = '$dir', uid = '$uid', gid = '$gid', sys_groupid = '$sys_groupid' WHERE ftp_user_id = ".$this->id;
			$app->db->query($sql);
		}
		
	}
}

$page = new page_action;
$page->onLoad();

?>
