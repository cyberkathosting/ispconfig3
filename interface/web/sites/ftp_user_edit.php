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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

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
			$app->tpl->setVar("username", $app->tools_sites->removePrefix($this->dataRecord['username'], $this->dataRecord['username_prefix'], $ftpuser_prefix), true);
		}

		if($this->dataRecord['username'] == "") {
			$app->tpl->setVar("username_prefix", $ftpuser_prefix, true);
		} else {
			$app->tpl->setVar("username_prefix", $app->tools_sites->getPrefix($this->dataRecord['username_prefix'], $ftpuser_prefix, $global_config['ftpuser_prefix']), true);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		// Get the record of the parent domain
		if(isset($this->dataRecord["parent_domain_id"])) {
			$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL('r'), @$this->dataRecord["parent_domain_id"]);
			if(!$parent_domain || $parent_domain['domain_id'] != @$this->dataRecord['parent_domain_id']) $app->tform->errorMessage .= $app->tform->lng("no_domain_perm");
		} else {
			$tmp = $app->tform->getDataRecord($this->id);
			$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL('r'), $tmp["parent_domain_id"]);
			if(!$parent_domain) $app->tform->errorMessage .= $app->tform->lng("no_domain_perm");
			unset($tmp);
		}

		// Set a few fixed values
		$this->dataRecord["server_id"] = $parent_domain["server_id"];

		//die(print_r($this->dataRecord));

		if(isset($this->dataRecord['username']) && trim($this->dataRecord['username']) == '') $app->tform->errorMessage .= $app->tform->lng('username_error_empty').'<br />';
		if(isset($this->dataRecord['username']) && empty($this->dataRecord['parent_domain_id'])) $app->tform->errorMessage .= $app->tform->lng('parent_domain_id_error_empty').'<br />';
		if(isset($this->dataRecord['dir']) && stristr($this->dataRecord['dir'], '..')) $app->tform->errorMessage .= $app->tform->lng('dir_dot_error').'<br />';
		if(isset($this->dataRecord['dir']) && stristr($this->dataRecord['dir'], './')) $app->tform->errorMessage .= $app->tform->lng('dir_slashdot_error').'<br />';

		parent::onSubmit();
	}

	function onBeforeInsert() {
		global $app, $conf, $interfaceConf;

		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$ftpuser_prefix = $app->tools_sites->replacePrefix($global_config['ftpuser_prefix'], $this->dataRecord);

		$this->dataRecord['username_prefix'] = $ftpuser_prefix;

		if ($app->tform->errorMessage == '') {
			$this->dataRecord['username'] = $ftpuser_prefix . $this->dataRecord['username'];
		}

		parent::onBeforeInsert();
	}

	function onAfterInsert() {
		global $app, $conf;

		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $this->dataRecord["parent_domain_id"]);
		$server_id = $app->functions->intval($web["server_id"]);
		$dir = $web["document_root"];
		$uid = $web["system_user"];
		$gid = $web["system_group"];
		
		// Check system user and group
		if($app->functions->is_allowed_user($uid) == false || $app->functions->is_allowed_group($gid) == false) {
			$app->error('Invalid system user or group');
		}

		// The FTP user shall be owned by the same group then the website
		$sys_groupid = $app->functions->intval($web['sys_groupid']);

		$sql = "UPDATE ftp_user SET server_id = ?, dir = ?, uid = ?, gid = ?, sys_groupid = ? WHERE ftp_user_id = ?";
		$app->db->query($sql, $server_id, $dir, $uid, $gid, $sys_groupid, $this->id);
	}

	function onBeforeUpdate() {
		global $app, $conf, $interfaceConf;
		
		/*
		 * If the names should be restricted -> do it!
		 */

		$app->uses('getconf,tools_sites');
		$global_config = $app->getconf->get_global_config('sites');
		$ftpuser_prefix = $app->tools_sites->replacePrefix($global_config['ftpuser_prefix'], $this->dataRecord);

		$old_record = $app->tform->getDataRecord($this->id);
		$ftpuser_prefix = $app->tools_sites->getPrefix($old_record['username_prefix'], $ftpuser_prefix);
		$this->dataRecord['username_prefix'] = $ftpuser_prefix;

		/* restrict the names */
		if ($app->tform->errorMessage == '') {
			$this->dataRecord['username'] = $ftpuser_prefix . $this->dataRecord['username'];
		}
	}

	function onAfterUpdate() {
		global $app, $conf;

		//* When the site of the FTP user has been changed
		if(isset($this->dataRecord['parent_domain_id']) && $this->oldDataRecord['parent_domain_id'] != $this->dataRecord['parent_domain_id']) {
			$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $this->dataRecord["parent_domain_id"]);
			$server_id = $app->functions->intval($web["server_id"]);
			$dir = $web["document_root"];
			$uid = $web["system_user"];
			$gid = $web["system_group"];

			// The FTP user shall be owned by the same group then the website
			$sys_groupid = $app->functions->intval($web['sys_groupid']);

			$sql = "UPDATE ftp_user SET server_id = ?, dir = ?, uid = ?, gid = ?, sys_groupid = ? WHERE ftp_user_id = ?";
			$app->db->query($sql, $server_id, $dir, $uid, $gid, $sys_groupid, $this->id);
		}

		//* 2. check to ensure that the FTP user path is not changed to a path outside of the docroot by a normal user
		if(isset($this->dataRecord['dir']) && $this->dataRecord['dir'] != $this->oldDataRecord['dir'] && !$app->auth->is_admin()) {
			$vd = new validate_ftpuser;
			$error_message = $vd->ftp_dir('dir', $this->dataRecord['dir'], '');
			//* This check should normally never be triggered
			//* Set the path to a safe path (web doc root).
			if($error_message != '') {
				$ftp_data = $app->db->queryOneRecord("SELECT parent_domain_id FROM ftp_user WHERE ftp_user_id = ?", $app->tform->primary_id);
				$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $ftp_data["parent_domain_id"]);
				$dir = $web["document_root"];
				$sql = "UPDATE ftp_user SET dir = ? WHERE ftp_user_id = ?";
				$app->db->query($sql, $dir, $this->id);
				$app->log("Error in FTP path settings of FTP user ".$this->dataRecord['username'], 1);
			}

		}

	}

}

$page = new page_action;
$page->onLoad();

?>
