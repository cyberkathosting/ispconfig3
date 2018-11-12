<?php
/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/mail_domain_catchall.tform.php";

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
			if(!$app->tform->checkClientLimit('limit_mailcatchall', "type = 'catchall'")) {
				$app->error($app->tform->wordbook["limit_mailcatchall_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_mailcatchall', "type = 'catchall'")) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_mailcatchall_txt"]);
			}
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;

		$email = $this->dataRecord["source"];
		$email_parts = explode("@", $email);
		$app->tpl->setVar("email_local_part", $email_parts[0]);
		$email_parts[1] = $app->functions->idn_decode($email_parts[1]);

		// Getting Domains of the user
		$sql = "SELECT domain FROM mail_domain WHERE ".$app->tform->getAuthSQL('r');
		$domains = $app->db->queryAllRecords($sql);
		$domain_select = '';
		if(is_array($domains)) {
			foreach( $domains as $domain) {
				$domain['domain'] = $app->functions->idn_decode($domain['domain']);
				$selected = (isset($email_parts[1]) && $domain["domain"] == $email_parts[1])?'SELECTED':'';
				$domain_select .= "<option value='" . $app->functions->htmlentities($domain['domain']) . "' $selected>" . $app->functions->htmlentities($domain['domain']) . "</option>\r\n";
			}
		}
		$app->tpl->setVar("email_domain", $domain_select);

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		// Check if Domain belongs to user
		$domain = $app->db->queryOneRecord("SELECT server_id, domain FROM mail_domain WHERE domain = ? AND ".$app->tform->getAuthSQL('r'), $app->functions->idn_encode($_POST["email_domain"]));
		if($domain["domain"] != $app->functions->idn_encode($_POST["email_domain"])) $app->tform->errorMessage .= $app->tform->wordbook["no_domain_perm"];

		// Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_mailcatchall FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another catchall
			if($this->id == 0 && $client["limit_mailcatchall"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(forwarding_id) as number FROM mail_forwarding WHERE sys_groupid = ? AND type = 'catchall'", $client_group_id);
				if($tmp["number"] >= $client["limit_mailcatchall"]) {
					$app->tform->errorMessage .= $app->tform->wordbook["limit_mailcatchall_txt"]."<br>";
				}
				unset($tmp);
			}
		} // end if user is not admin

		// compose the email field
		$this->dataRecord["source"] = "@".$app->functions->idn_encode($_POST["email_domain"]);
		// Set the server id of the mailbox = server ID of mail domain.
		$this->dataRecord["server_id"] = $domain["server_id"];

		//unset($this->dataRecord["email_local_part"]);
		unset($this->dataRecord["email_domain"]);

		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app;

		$domain = $app->db->queryOneRecord("SELECT sys_groupid FROM mail_domain WHERE domain = ? AND ".$app->tform->getAuthSQL('r'), $app->functions->idn_encode($_POST["email_domain"]));
		$app->db->query("update mail_forwarding SET sys_groupid = ? WHERE forwarding_id = ?", $domain['sys_groupid'], $this->id);

	}

}

$page = new page_action;
$page->onLoad();

?>
