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

$tform_def_file = "form/mail_user.tform.php";

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
			if(!$app->tform->checkClientLimit('limit_mailbox')) {
				$app->error($app->tform->wordbook["limit_mailbox_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_mailbox')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_mailbox_txt"]);
			}
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;

		// Workaround for #5448, accessed via link on quota dashlet.
		$app->tpl->setVar('app_module', 'mail');

		$email = $this->dataRecord["email"];
		$email_parts = explode("@", $email);
		$app->tpl->setVar("email_local_part", $email_parts[0]);
		$email_parts[1] = $app->functions->idn_decode($email_parts[1]);

		// Getting Domains of the user
		// $sql = "SELECT domain, server_id FROM mail_domain WHERE ".$app->tform->getAuthSQL('r').' ORDER BY domain';
		$sql = "SELECT domain, server_id FROM mail_domain WHERE (".$app->tform->getAuthSQL('r').") AND domain NOT IN (SELECT SUBSTR(source,2) FROM mail_forwarding WHERE type = 'aliasdomain') ORDER BY domain";               
		$domains = $app->db->queryAllRecords($sql);
		$domain_select = '';
		if(is_array($domains)) {
			foreach( $domains as $domain) {
				$domain['domain'] = $app->functions->idn_decode($domain['domain']);
				$selected = ($domain["domain"] == @$email_parts[1])?'SELECTED':'';
				$domain_select .= "<option value='" . $app->functions->htmlentities($domain['domain']) . "' $selected>" . $app->functions->htmlentities($domain['domain']) . "</option>\r\n";
			}
		}
		$app->tpl->setVar("email_domain", $domain_select);
		unset($domains);
		unset($domain_select);

		// Get the spamfilter policys for the user
		$tmp_user = $app->db->queryOneRecord("SELECT policy_id FROM spamfilter_users WHERE email = ?", $this->dataRecord["email"]);
		if (isset($_POST['policy'])) $tmp_user['policy_id'] = intval($_POST['policy']);
		$sql = "SELECT id, policy_name FROM spamfilter_policy WHERE ".$app->tform->getAuthSQL('r') . " ORDER BY policy_name";
		$policys = $app->db->queryAllRecords($sql);
		$policy_select = "<option value='0'>".$app->tform->lng("no_policy")."</option>";
		if(is_array($policys)) {
			foreach( $policys as $p) {
				$selected = ($p["id"] == $tmp_user["policy_id"])?'SELECTED':'';
				$policy_select .= "<option value='$p[id]' $selected>" . $app->functions->htmlentities($p['policy_name']) . "</option>\r\n";
			}
		}
		$app->tpl->setVar("policy", $policy_select);
		unset($policys);
		unset($policy_select);
		unset($tmp_user);

		// Convert quota from Bytes to MB
		if($this->dataRecord["quota"] != -1) $app->tpl->setVar("quota", $this->dataRecord["quota"] / 1024 / 1024);

		// Is autoresponder set?
		if (!empty($this->dataRecord['autoresponder']) && $this->dataRecord['autoresponder'] == 'y') {
			$app->tpl->setVar("ar_active", 'checked="checked"');
		} else {
			$app->tpl->setVar("ar_active", '');
		}

		if($this->dataRecord['autoresponder_subject'] == '') {
			$app->tpl->setVar('autoresponder_subject', $app->tform->lng('autoresponder_subject'));
		} else {
			$app->tpl->setVar('autoresponder_subject', $this->dataRecord['autoresponder_subject'], true);
		}

		$app->uses('getconf');
		$mail_config = $app->getconf->get_global_config('mail');
		if($mail_config["enable_custom_login"] == "y") {
			$app->tpl->setVar("enable_custom_login", 1);
		} else {
			$app->tpl->setVar("enable_custom_login", 0);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		//* Check if Domain belongs to user
		if(isset($_POST["email_domain"])) {
			$domain = $app->db->queryOneRecord("SELECT server_id, domain FROM mail_domain WHERE domain = ? AND ".$app->tform->getAuthSQL('r'), $app->functions->idn_encode($_POST["email_domain"]));
			if($domain["domain"] != $app->functions->idn_encode($_POST["email_domain"])) $app->tform->errorMessage .= $app->tform->lng("no_domain_perm");
		}

		//* if its an insert, check that the password is not empty
		if($this->id == 0 && $_POST["password"] == '') {
			$app->tform->errorMessage .= $app->tform->lng("error_no_pwd")."<br>";
		}

		//* Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_mailbox, limit_mailquota, parent_client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);


			// Check if the user may add another mailbox.
			if($this->id == 0 && $client["limit_mailbox"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(mailuser_id) as number FROM mail_user WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_mailbox"]) {
					$app->tform->errorMessage .= $app->tform->lng("limit_mailbox_txt")."<br>";
				}
				unset($tmp);
			}

			// Check the quota and adjust
			$old_mail_values = @($this->id > 0)?$app->db->queryOneRecord("SELECT * FROM mail_user WHERE mailuser_id = ?", $this->id):array();
			if(isset($_POST["quota"]) && $client["limit_mailquota"] >= 0 && (($app->functions->intval($this->dataRecord["quota"]) * 1024 * 1024 != $old_mail_values['quota']) || ($_POST["quota"] <= 0))) {
				$tmp = $app->db->queryOneRecord("SELECT sum(quota) as mailquota FROM mail_user WHERE mailuser_id != ? AND ".$app->tform->getAuthSQL('u'), $this->id);
				$mailquota = $tmp["mailquota"] / 1024 / 1024;
				$new_mailbox_quota = $app->functions->intval($this->dataRecord["quota"]);
				if(($mailquota + $new_mailbox_quota > $client["limit_mailquota"]) || ($new_mailbox_quota == 0 && $client["limit_mailquota"] != -1)) {
					$max_free_quota = $client["limit_mailquota"] - $mailquota;
					$app->tform->errorMessage .= $app->tform->lng("limit_mailquota_txt").": ".$max_free_quota."<br>";
					// Set the quota field to the max free space
					$this->dataRecord["quota"] = $max_free_quota;
				}
				unset($tmp);
				unset($tmp_quota);
			}

			if($client['parent_client_id'] > 0) {
				// Get the limits of the reseller
				$reseller = $app->db->queryOneRecord("SELECT limit_mailquota, limit_maildomain FROM client WHERE client_id = ?", $client['parent_client_id']);

				//* Check the website quota of the client
				if(isset($_POST["quota"]) && $reseller["limit_mailquota"] >= 0 && $app->functions->intval($this->dataRecord["quota"]) * 1024 * 1024 != $old_mail_values['quota']) {
					$tmp = $app->db->queryOneRecord("SELECT sum(quota) as mailquota FROM mail_user, sys_group, client WHERE mail_user.sys_groupid=sys_group.groupid AND sys_group.client_id=client.client_id AND ? IN (client.parent_client_id, client.client_id) AND mailuser_id != ?", $client['parent_client_id'], $this->id);

					$mailquota = $tmp["mailquota"] / 1024 / 1024;
					$new_mailbox_quota = $app->functions->intval($this->dataRecord["quota"]);
					if(($mailquota + $new_mailbox_quota > $reseller["limit_mailquota"]) || ($new_mailbox_quota == 0 && $reseller["limit_mailquota"] != -1)) {
						$max_free_quota = $reseller["limit_mailquota"] - $mailquota;
						if($max_free_quota < 0) $max_free_quota = 0;
						$app->tform->errorMessage .= $app->tform->lng("limit_mailquota_txt").": ".$max_free_quota."<br>";
						// Set the quota field to the max free space
						$this->dataRecord["quota"] = $max_free_quota;
					}
					unset($tmp);
					unset($tmp_quota);
				}
			}
		} // end if user is not admin


		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config(!empty($domain["server_id"]) ? $domain["server_id"] : '', 'mail');

		// Set Maildir format
		if ($this->id == 0) {
			$this->dataRecord['maildir_format'] = $mail_config['maildir_format'];
		}
		else {
			// restore Maildir format
			$tmp = $app->db->queryOneRecord("SELECT maildir_format FROM mail_user WHERE mailuser_id = ".$app->functions->intval($this->id));
			$this->dataRecord['maildir_format'] = $tmp['maildir_format'];
		}
		
		//* compose the email field
		if(isset($_POST["email_local_part"]) && isset($_POST["email_domain"])) {
			$this->dataRecord["email"] = strtolower($_POST["email_local_part"]."@".$app->functions->idn_encode($_POST["email_domain"]));

			// Set the server id of the mailbox = server ID of mail domain.
			$this->dataRecord["server_id"] = $domain["server_id"];

			unset($this->dataRecord["email_local_part"]);
			unset($this->dataRecord["email_domain"]);

			// Convert quota from MB to Bytes
			if($this->dataRecord["quota"] != -1) $this->dataRecord["quota"] = $this->dataRecord["quota"] * 1024 * 1024;

			// setting Maildir, Homedir, UID and GID
			$maildir = str_replace("[domain]", $domain["domain"], $mail_config["maildir_path"]);
			$maildir = str_replace("[localpart]", strtolower($_POST["email_local_part"]), $maildir);
			$this->dataRecord["maildir"] = $maildir;
			$this->dataRecord["homedir"] = $mail_config["homedir_path"];
			
			// Will be overwritten by mail_plugin
			if ($mail_config["mailbox_virtual_uidgid_maps"] == 'y') {
				$this->dataRecord['uid'] = -1;
				$this->dataRecord['gid'] = -1;
			} else {
				$this->dataRecord['uid'] = intval($mail_config["mailuser_uid"]);
				$this->dataRecord['gid'] = intval($mail_config["mailuser_gid"]);
			}
				
			//* Check if there is no alias or forward with this address
			$tmp = $app->db->queryOneRecord("SELECT count(forwarding_id) as number FROM mail_forwarding WHERE active = 'y' AND source = ?", $this->dataRecord["email"]);
			if($tmp['number'] > 0) $app->tform->errorMessage .= $app->tform->lng("duplicate_alias_or_forward_txt")."<br>";
			unset($tmp);

		}

		$sys_config = $app->getconf->get_global_config('mail');
		if($sys_config["enable_custom_login"] == "y") {
			if(!isset($_POST["login"]) || $_POST["login"] == '') $this->dataRecord["login"] = $this->dataRecord["email"];
			elseif(strpos($_POST["login"], '@') !== false && $_POST["login"] != $this->dataRecord["email"]) $app->tform->errorMessage .= $app->tform->lng("error_login_email_txt")."<br>";
		} else {
			$this->dataRecord["login"] = isset($this->dataRecord["email"]) ? $this->dataRecord["email"] : '';
		}
		//* if autoresponder checkbox not selected, do not save dates
		if (!isset($_POST['autoresponder'])) {
			$this->dataRecord['autoresponder_start_date'] = '';
			$this->dataRecord['autoresponder_end_date'] = '';
		}

		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app, $conf;

		// Set the domain owner as mailbox owner
		$domain = $app->db->queryOneRecord("SELECT sys_groupid, server_id FROM mail_domain WHERE domain = ? AND ".$app->tform->getAuthSQL('r'), $app->functions->idn_encode($_POST["email_domain"]));
		$app->db->query("UPDATE mail_user SET sys_groupid = ? WHERE mailuser_id = ?", $domain["sys_groupid"], $this->id);

		// Spamfilter policy
		$policy_id = $app->functions->intval($this->dataRecord["policy"]);
		if($policy_id > 0) {
			$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", $this->dataRecord["email"]);
			if($tmp_user["id"] > 0) {
				// There is already a record that we will update
				$app->db->datalogUpdate('spamfilter_users', array("policy_id" => $policy_id), 'id', $tmp_user["id"]);
			} else {
				// We create a new record
				$insert_data = array(
					"sys_userid" => $_SESSION["s"]["user"]["userid"],
					"sys_groupid" => $domain["sys_groupid"],
					"sys_perm_user" => 'riud',
					"sys_perm_group" => 'riud',
					"sys_perm_other" => '',
					"server_id" => $domain["server_id"],
					"priority" => 10,
					"policy_id" => $policy_id,
					"email" => $this->dataRecord["email"],
					"fullname" => $app->functions->idn_decode($this->dataRecord["email"]),
					"local" => 'Y'
				);
				$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
			}
		}  // endif spamfilter policy


		// Set the fields for dovecot
		if(isset($this->dataRecord["email"])) {
			$disableimap = ($this->dataRecord["disableimap"])?'y':'n';
			$disablepop3 = ($this->dataRecord["disablepop3"])?'y':'n';
			$disabledeliver = ($this->dataRecord["postfix"] == 'y')?'n':'y';
			$disablesmtp = ($this->dataRecord["disablesmtp"])?'y':'n';

			$app->db->query($sql, $disableimap, $disableimap, $disablepop3, $disablesmtp, $disabledeliver, $disabledeliver, $this->id);
			$sql = "UPDATE mail_user SET disableimap = ?, disablesieve = ?, disablepop3 = ?, disablesmtp = ?, disabledeliver = ?, disablelda = ?, disablelmtp = ? WHERE mailuser_id = ?";
			$app->db->query($sql, $disableimap, $disableimap, $disablepop3, $disablesmtp, $disabledeliver, $disabledeliver, $disabledeliver, $this->id);
		}
	}

	function onAfterUpdate() {
		global $app, $conf;

		// Set the domain owner as mailbox owner
		if(isset($_POST["email_domain"])) {
			$domain = $app->db->queryOneRecord("SELECT sys_groupid, server_id FROM mail_domain WHERE domain = ? AND ".$app->tform->getAuthSQL('r'), $app->functions->idn_encode($_POST["email_domain"]));
			$app->db->query("UPDATE mail_user SET sys_groupid = ? WHERE mailuser_id = ?", $domain["sys_groupid"], $this->id);

			// Spamfilter policy
			$policy_id = $app->functions->intval($this->dataRecord["policy"]);
			$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", $this->dataRecord["email"]);
			if($policy_id > 0) {
				if($tmp_user["id"] > 0) {
					// There is already a record that we will update
					$app->db->datalogUpdate('spamfilter_users', array("policy_id" => $policy_id), 'id', $tmp_user["id"]);
				} else {
					// We create a new record
					$insert_data = array(
						"sys_userid" => $_SESSION["s"]["user"]["userid"],
						"sys_groupid" => $domain["sys_groupid"],
						"sys_perm_user" => 'riud',
						"sys_perm_group" => 'riud',
						"sys_perm_other" => '',
						"server_id" => $domain["server_id"],
						"priority" => 10,
						"policy_id" => $policy_id,
						"email" => $this->dataRecord["email"],
						"fullname" => $app->functions->idn_decode($this->dataRecord["email"]),
						"local" => 'Y'
					);
					$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
				}
			}else {
				if($tmp_user["id"] > 0) {
					// There is already a record but the user shall have no policy, so we delete it
					$app->db->datalogDelete('spamfilter_users', 'id', $tmp_user["id"]);
				}
			} // endif spamfilter policy
		}

		// Set the fields for dovecot
		if(isset($this->dataRecord["email"])) {
			$disableimap = (isset($this->dataRecord["disableimap"]) && $this->dataRecord["disableimap"])?'y':'n';
			$disablepop3 = (isset($this->dataRecord["disablepop3"]) && $this->dataRecord["disablepop3"])?'y':'n';
			$disabledeliver = ($this->dataRecord["postfix"] == 'y')?'n':'y';
			$disablesmtp = (isset($this->dataRecord["disablesmtp"]) && $this->dataRecord["disablesmtp"])?'y':'n';

			$sql = "UPDATE mail_user SET disableimap = ?, disablesieve = ?, `disablesieve-filter` = ?, disablepop3 = ?, disablesmtp = ?, disabledeliver = ?, disablelda = ?, disablelmtp = ? WHERE mailuser_id = ?";
			$app->db->query($sql, $disableimap, $disableimap, $disableimap, $disablepop3, $disablesmtp, $disabledeliver, $disabledeliver, $disabledeliver, $this->id);
		}

		//** If the email address has been changed, change it in all aliases too
		if(isset($this->dataRecord['email']) && $this->oldDataRecord['email'] != $this->dataRecord['email']) {
			//if($this->oldDataRecord['email'] != $this->dataRecord['email']) {

			//* Update the aliases
			$forwardings = $app->db->queryAllRecords("SELECT * FROM mail_forwarding WHERE destination = ?", $this->oldDataRecord['email']);
			if(is_array($forwardings)) {
				foreach($forwardings as $rec) {
					$destination = $this->dataRecord['email'];
					$app->db->datalogUpdate('mail_forwarding', array("destination" => $destination), 'forwarding_id', $rec['forwarding_id']);
				}
			}

		} // end if email addess changed

		//* Change backup options when user mail backup options have been changed
		if(isset($this->dataRecord['backup_interval']) && ($this->dataRecord['backup_interval'] != $this->oldDataRecord['backup_interval'] || $this->dataRecord['backup_copies'] != $this->oldDataRecord['backup_copies'])) {
			$backup_interval = $this->dataRecord['backup_interval'];
			$backup_copies = $app->functions->intval($this->dataRecord['backup_copies']);
			$app->db->datalogUpdate('mail_user', array("backup_interval" => $backup_interval, "backup_copies" => $backup_copies), 'mailuser_id', $rec['mailuser_id']);
			unset($backup_copies);
			unset($backup_interval);
		} // end if backup options changed

	}

}

$app->tform_actions = new page_action;
$app->tform_actions->onLoad();

?>
