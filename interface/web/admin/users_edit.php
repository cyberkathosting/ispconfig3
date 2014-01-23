<?php
/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/users.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onBeforeInsert() {
		global $app, $conf;

		if(!in_array($this->dataRecord['startmodule'], $this->dataRecord['modules'])) {
			$app->tform->errorMessage .= $app->tform->wordbook['startmodule_err'];
		}
	}

	function onBeforeUpdate() {
		global $app, $conf;

		if($conf['demo_mode'] == true && $_REQUEST['id'] <= 3) $app->error('This function is disabled in demo mode.');

		if(@is_array($this->dataRecord['modules']) && !in_array($this->dataRecord['startmodule'], $this->dataRecord['modules'])) {
			$app->tform->errorMessage .= $app->tform->wordbook['startmodule_err'];
		}
		
		$this->oldDataRecord = $app->tform->getDataRecord($this->id);
		
		//* A user that belongs to a client record (client or reseller) may not have typ admin
		if(isset($this->dataRecord['typ']) && $this->dataRecord['typ'][0] == 'admin'  && $this->oldDataRecord['client_id'] > 0) {
			$app->tform->errorMessage .= $app->tform->wordbook['client_not_admin_err'];
		}
		
	}

	/*
	 This function is called automatically right after
	 the data was successful updated in the database.
	*/
	function onAfterUpdate() {
		global $app, $conf;

		$client = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE userid = ".$this->id);
		$client_id = $app->functions->intval($client['client_id']);
		$username = $app->db->quote($this->dataRecord["username"]);
		$old_username = $app->db->quote($this->oldDataRecord['username']);

		// username changed
		if(isset($conf['demo_mode']) && $conf['demo_mode'] != true && isset($this->dataRecord['username']) && $this->dataRecord['username'] != '' && $this->oldDataRecord['username'] != $this->dataRecord['username']) {
			$sql = "UPDATE client SET username = '$username' WHERE client_id = $client_id AND username = '$old_username'";
			$app->db->query($sql);
			$tmp = $app->db->queryOneRecord("SELECT * FROM sys_group WHERE client_id = $client_id");
			$app->db->datalogUpdate("sys_group", "name = '$username'", 'groupid', $tmp['groupid']);
			unset($tmp);
		}

		// password changed
		if(isset($conf['demo_mode']) && $conf['demo_mode'] != true && isset($this->dataRecord["passwort"]) && $this->dataRecord["passwort"] != '') {
			$password = $app->db->quote($this->dataRecord["passwort"]);
			$salt="$1$";
			$base64_alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
			for ($n=0;$n<8;$n++) {
				$salt.=$base64_alphabet[mt_rand(0, 63)];
			}
			$salt.="$";
			$password = crypt(stripslashes($password), $salt);
			$sql = "UPDATE client SET password = '$password' WHERE client_id = $client_id AND username = '$username'";
			$app->db->query($sql);
		}

		// language changed
		if(isset($conf['demo_mode']) && $conf['demo_mode'] != true && isset($this->dataRecord['language']) && $this->dataRecord['language'] != '' && $this->oldDataRecord['language'] != $this->dataRecord['language']) {
			$language = $app->db->quote($this->dataRecord["language"]);
			$sql = "UPDATE client SET language = '$language' WHERE client_id = $client_id AND username = '$username'";
			$app->db->query($sql);
		}

		// reseller status changed
		/*
		if(isset($this->dataRecord["limit_client"]) && $this->dataRecord["limit_client"] != $this->oldDataRecord["limit_client"]) {
			$modules = $conf['interface_modules_enabled'];
			if($this->dataRecord["limit_client"] > 0) $modules .= ',client';
			$modules = $app->db->quote($modules);
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET modules = '$modules' WHERE client_id = $client_id";
			$app->db->query($sql);
		}
		*/
		parent::onAfterUpdate();
	}

}

$page = new page_action;
$page->onLoad();

?>
