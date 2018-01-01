<?php
/*
Copyright (c) 2005 - 2009, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/mail_mailinglist.tform.php";

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
			if(!$app->tform->checkClientLimit('limit_mailmailinglist')) {
				$app->error($app->tform->wordbook["limit_mailmailinglist_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_mailmailinglist')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_mailmailinglist_txt"]);
			}
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] == 'admin') {
			// Getting Clients of the user
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY client.company_name, client.contact_name, sys_group.name";
			$clients = $app->db->queryAllRecords($sql);
			$clients = $app->functions->htmlentities($clients);
			$client_select = '';
			if($_SESSION["s"]["user"]["typ"] == 'admin') $client_select .= "<option value='0'></option>";
			$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = ($client["groupid"] == $tmp_data_record["sys_groupid"])?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id", $client_select);

		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, client.default_mailserver, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ? order by contact_name", $client_group_id);
			$client = $app->functions->htmlentities($client);

			// Fill the client select field
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ? ORDER BY client.company_name, client.contact_name, sys_group.name";
			$clients = $app->db->queryAllRecords($sql, $client['client_id']);
			$clients = $app->functions->htmlentities($clients);
			$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client['client_id']);
			$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contactname'].'</option>';
			$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @($client["groupid"] == $tmp_data_record["sys_groupid"])?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id", $client_select);
		}

		// Getting Domains of the user
		$sql = "SELECT domain FROM mail_domain WHERE ".$app->tform->getAuthSQL('r').' ORDER BY domain';
		$domains = $app->db->queryAllRecords($sql);
		$domain_select = '';
		if(is_array($domains)) {
			foreach( $domains as $domain) {
				$selected = ($domain["domain"] == $this->dataRecord["domain"])?'SELECTED':'';
				$domain_select .= "<option value='" . $app->functions->htmlentities($domain['domain']) . "' $selected>" . $app->functions->htmlentities($domain['domain']) . "</option>\r\n";
			}
		}
		$app->tpl->setVar("domain_option", $domain_select);

		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("listname_value", $this->dataRecord["listname"], true);
			$app->tpl->setVar("domain_value", $this->dataRecord["domain"], true);
			$app->tpl->setVar("email_value", $this->dataRecord["email"], true);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] != 'admin') {

			// Get the limits of the client
			$client_group_id = intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_mailmailinglist, default_mailserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			//* Check if Domain belongs to user
			if(isset($_POST["domain"])) {
				$domain = $app->db->queryOneRecord("SELECT domain FROM mail_domain WHERE domain = ? AND ".$app->tform->getAuthSQL('r'), $this->dataRecord["domain"]);
				if($domain["domain"] != $this->dataRecord["domain"]) $app->tform->errorMessage .= $app->tform->lng("no_domain_perm");
			}

			// When the record is updated
			if($this->id == 0) {
				//Check if email is in use
				$check = $app->db->queryOneRecord("SELECT count(source) as number FROM mail_forwarding WHERE source = ?", $this->dataRecord["listname"]."@".$this->dataRecord["domain"]);
				if($check['number'] != 0) {
					$app->error($app->tform->wordbook["email_in_use_txt"]);
				}

				$check = $app->db->queryOneRecord("SELECT count(email) as number FROM mail_user WHERE email = ?", $this->dataRecord["listname"]."@".$this->dataRecord["domain"]);
				if($check['number'] != 0) {
					$app->error($app->tform->wordbook["email_in_use_txt"]);
				}

				$check = $app->db->queryOneRecord("SELECT count(mailinglist_id) as number FROM mail_mailinglist WHERE listname = ? AND domain = ?", $this->dataRecord["listname"], $this->dataRecord["domain"]);
				if($check['number'] != 0) {
					$app->error($app->tform->wordbook["email_in_use_txt"]);
				}

				// Check if the user may add another mail_domain
				if($client["limit_mailmailinglist"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(mailinglist_id) as number FROM mail_mailinglist WHERE sys_groupid = ?", $client_group_id);
					if($tmp["number"] >= $client["limit_mailmailinglist"]) {
						$app->error($app->tform->wordbook["limit_mailmailinglist_txt"]);
					}
				}
			}

			// Clients may not set the client_group_id, so we unset them if user is not a admin
			if(!$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);
		}

		//* make sure that the email domain is lowercase
		if(isset($this->dataRecord["domain"])) $this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);

		parent::onSubmit();
	}

	function onBeforeInsert() {
		global $app, $conf;

		// Set the server id of the mailinglist = server ID of mail domain.
		$domain = $app->db->queryOneRecord("SELECT server_id FROM mail_domain WHERE domain = ?", $this->dataRecord["domain"]);
		$this->dataRecord["server_id"] = $domain['server_id'];
	}

	function onAfterInsert() {
		global $app, $conf;

		// make sure that the record belongs to the client group and not the admin group when a dmin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_mailinglist SET sys_groupid = ?, sys_perm_group = 'ru' WHERE mailinglist_id = ?", $client_group_id, $this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_mailinglist SET sys_groupid = ?, sys_perm_group = 'riud' WHERE mailinglist_id = ?", $client_group_id, $this->id);
		}
	}

	function onBeforeUpdate() {
		global $app, $conf;

		//* Check if the server has been changed
		// We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$rec = $app->db->queryOneRecord("SELECT server_id, domain from mail_mailinglist WHERE mailinglist_id = ?", $this->id);
			$this->dataRecord["server_id"] = $rec['server_id'];
			unset($rec);
			//* If the user is neither admin nor reseller
		} else {
			//* We do not allow users to change a domain which has been created by the admin
			$rec = $app->db->queryOneRecord("SELECT domain from mail_mailinglist WHERE mailinglist_id = ?", $this->id);
			if($rec['domain'] != $this->dataRecord["domain"] && $app->tform->checkPerm($this->id, 'u')) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Domain can not be changed. Please ask your Administrator if you want to change the domain name.');
				$this->dataRecord["domain"] = $rec['domain'];
			}
			unset($rec);
		}
	}

	function onAfterUpdate() {
		global $app, $conf;

		// make sure that the record belongs to the clinet group and not the admin group when admin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_mailinglist SET sys_groupid = ?, sys_perm_group = 'ru' WHERE mailinglist_id = ?", $client_group_id, $this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_mailinglist SET sys_groupid = ?, sys_perm_group = 'riud' WHERE mailinglist_id = ?", $client_group_id, $this->id);
		}
	}

}

$app->tform_actions = new page_action;
$app->tform_actions->onLoad();

?>
