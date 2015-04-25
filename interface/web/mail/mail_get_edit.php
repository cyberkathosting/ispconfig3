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

$tform_def_file = "form/mail_get.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('mail');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_fetchmail')) {
				$app->error($app->tform->wordbook["limit_fetchmail_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_fetchmail')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_fetchmail_txt"]);
			}
		}

		parent::onShowNew();
	}

	function onSubmit() {
		global $app, $conf;

		//* Check if destination email belongs to user
		if(isset($_POST["destination"])) {
			$email = $app->db->queryOneRecord("SELECT email FROM mail_user WHERE email = ? AND ".$app->tform->getAuthSQL('r'), $app->functions->idn_encode($_POST["destination"]));
			if($email["email"] != $app->functions->idn_encode($_POST["destination"])) $app->tform->errorMessage .= $app->tform->lng("no_destination_perm");
		}

		// Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_fetchmail FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another transport.
			if($this->id == 0 && $client["limit_fetchmail"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(mailget_id) as number FROM mail_get WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_fetchmail"]) {
					$app->tform->errorMessage .= $app->tform->wordbook["limit_fetchmail_txt"]."<br>";
				}
				unset($tmp);
			}
		} // end if user is not admin


		// Set the server ID according to the selected destination
		$tmp = $app->db->queryOneRecord("SELECT server_id FROM mail_user WHERE email = ?", $this->dataRecord["destination"]);
		$this->dataRecord["server_id"] = $tmp["server_id"];
		unset($tmp);

		//* Check that no illegal combination of options is set
		if((!isset($this->dataRecord['source_delete']) || @$this->dataRecord['source_delete'] == 'n') && $this->dataRecord['source_read_all'] == 'y') {
			$app->tform->errorMessage .= $app->tform->lng('error_delete_read_all_combination')."<br>";
		}

		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app;

		$tmp = $app->db->queryOneRecord("SELECT sys_groupid FROM mail_user WHERE email = ?", $this->dataRecord["destination"]);
		$app->db->query("update mail_get SET sys_groupid = ? WHERE mailget_id = ?", $tmp['sys_groupid'], $this->id);

	}

}

$page = new page_action;
$page->onLoad();

?>
