<?php

/*
Copyright (c) 2011, Till Brehm, projektfarm Gmbh
Modified 2009, Marius Cramer, pixcept KG
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

$tform_def_file = "form/web_folder_user.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions,validate_cron');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onSubmit() {
		global $app, $conf;

		// Get the record of the parent domain
		$folder = $app->db->queryOneRecord("select * FROM web_folder WHERE web_folder_id = ? AND ".$app->tform->getAuthSQL('r'), @$this->dataRecord["web_folder_id"]);
		if(!$folder || $folder['web_folder_id'] != @$this->dataRecord['web_folder_id']) $app->tform->errorMessage .= $app->tform->lng("no_folder_perm");

		// Set a few fixed values
		$this->dataRecord["server_id"] = $folder["server_id"];
		
		// make sure this folder/user combination does not exist already
		if($this->id > 0){
			$user = $app->db->queryOneRecord("SELECT * FROM web_folder_user WHERE web_folder_id = ? AND username = ? AND web_folder_user_id != ?", $this->dataRecord['web_folder_id'], $this->dataRecord['username'], $this->id);
		} else {
			$user = $app->db->queryOneRecord("SELECT * FROM web_folder_user WHERE web_folder_id = ? AND username = ?", $this->dataRecord['web_folder_id'], $this->dataRecord['username']);
		}
		if(is_array($user) && !empty($user)) $app->tform->errorMessage .= $app->tform->lng('error_user_exists_already_txt');

		parent::onSubmit();
	}
	
	function onAfterInsert() {
		global $app, $conf;

		$folder = $app->db->queryOneRecord("SELECT * FROM web_folder WHERE web_folder_id = ?", $this->dataRecord["web_folder_id"]);

		// The web folder user entry shall be owned by the same group as the web folder
		$sys_groupid = $app->functions->intval($folder['sys_groupid']);

		$sql = "UPDATE web_folder_user SET sys_groupid = ? WHERE web_folder_user_id = ?";
		$app->db->query($sql, $sys_groupid, $this->id);
	}
	
	function onAfterUpdate() {
		global $app, $conf;

		//* When the web folder has been changed
		if(isset($this->dataRecord['web_folder_id']) && $this->oldDataRecord['web_folder_id'] != $this->dataRecord['web_folder_id']) {
			$folder = $app->db->queryOneRecord("SELECT * FROM web_folder WHERE web_folder_id = ?", $this->dataRecord["web_folder_id"]);

			// The web folder user entry shall be owned by the same group as the web folder
			$sys_groupid = $app->functions->intval($folder['sys_groupid']);

			$sql = "UPDATE web_folder_user SET sys_groupid = ? WHERE web_folder_user_id = ?";
			$app->db->query($sql, $sys_groupid, $this->id);
		}

	}

}

$page = new page_action;
$page->onLoad();

?>
