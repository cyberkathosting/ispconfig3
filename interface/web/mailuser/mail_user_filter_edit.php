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

$tform_def_file = "form/mail_user_filter.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('mailuser');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf;

		// we will check the limits only when the email address belongs to a client and not the admin
		if($_SESSION["s"]["user"]["default_group"] > 0) {
			if(!$app->tform->checkClientLimit('limit_mailfilter', "")) {
				$app->error($app->tform->lng("limit_mailfilter_txt"));
			}
			if(!$app->tform->checkResellerLimit('limit_mailfilter', "")) {
				$app->error('Reseller: '.$app->tform->lng("limit_mailfilter_txt"));
			}
		}

		parent::onShowNew();
	}


	function onSubmit() {
		global $app, $conf;

		// Get the parent mail_user record
		$mailuser = $app->db->queryOneRecord("SELECT * FROM mail_user WHERE mailuser_id = ?", $_SESSION['s']['user']['mailuser_id']);

		// Set the mailuser_id
		$this->dataRecord["mailuser_id"] = $mailuser["mailuser_id"];

		// Remove leading dots
		if(substr($this->dataRecord['target'], 0, 1) == '.') $this->dataRecord['target'] = substr($this->dataRecord['target'], 1);


		// Check the client limits if the email address is assigned to a client
		if($_SESSION["s"]["user"]["default_group"] > 0) { // if user is not admin
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_mailfilter FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another filter
			if($this->id == 0 && $client["limit_mailfilter"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(filter_id) as number FROM mail_user_filter WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_mailfilter"]) {
					$app->tform->errorMessage .= $app->tform->lng("limit_mailfilter_txt")."<br>";
				}
				unset($tmp);
			}
		} // end if user is not admin

		parent::onSubmit();
	}

}

$page = new page_action;
$page->onLoad();

?>
