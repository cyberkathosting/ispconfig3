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

$tform_def_file = "form/mail_domain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

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
			if(!$app->tform->checkClientLimit('limit_maildomain')) {
				$app->error($app->tform->wordbook["limit_maildomain_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_maildomain')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_maildomain_txt"]);
			}
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] == 'admin') {
			// Getting Clients of the user
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(client.company_name,' :: ',client.contact_name) as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY sys_group.name";
			
			$clients = $app->db->queryAllRecords($sql);
			$client_select = '';
			if($_SESSION["s"]["user"]["typ"] == 'admin') $client_select .= "<option value='0'></option>";
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name] :: $client[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);

		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, client.default_mailserver, CONCAT(client.company_name,' :: ',client.contact_name) as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id order by client.contact_name");

			// Set the mailserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_mailserver]");
			$app->tpl->setVar("server_id","<option value='$client[default_mailserver]'>$tmp[server_name]</option>");
			unset($tmp);

			// Fill the client select field
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(client.company_name,' :: ',client.contact_name) as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$client['client_id']." ORDER BY sys_group.name";
			$clients = $app->db->queryAllRecords($sql);
			$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$client['client_id']);
			$client_select = '<option value="'.$tmp['groupid'].'">'.$client['name'].' :: '.$client['contactname'].'</option>';
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name] :: $client[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);

		}

		/*
		 * Now we have to check, if we should use the domain-module to select the domain
		 * or not
		 */
		$app->uses('ini_parser,getconf');
		$settings = $app->getconf->get_global_config('domains');
		if ($settings['use_domain_module'] == 'y') {
			/*
			 * The domain-module is in use.
			*/
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			/*
			 * The admin can select ALL domains, the user only the domains assigned to him
			 */
			$sql = "SELECT domain FROM domain ";
			if ($_SESSION["s"]["user"]["typ"] != 'admin') {
				$sql .= "WHERE sys_groupid =" . $client_group_id;
			}
			$sql .= " ORDER BY domain";
			$domains = $app->db->queryAllRecords($sql);
			$domain_select = '';
			if(is_array($domains) && sizeof($domains) > 0) {
				/* We have domains in the list, so create the drop-down-list */
				foreach( $domains as $domain) {
					$domain_select .= "<option value=" . $domain['domain'] ;
					if ($domain['domain'] == $this->dataRecord["domain"]) {
						$domain_select .= " selected";
					}
					$domain_select .= ">" . $domain['domain'] . "</option>\r\n";
				}
			}
			else {
				/*
				 * We have no domains in the domain-list. This means, we can not add ANY new domain.
				 * To avoid, that the variable "domain_option" is empty and so the user can
				 * free enter a domain, we have to create a empty option!
				*/
				$domain_select .= "<option value=''></option>\r\n";
			}
			$app->tpl->setVar("domain_option",$domain_select);
		}


		// Get the spamfilter policys for the user
		$tmp_user = $app->db->queryOneRecord("SELECT policy_id FROM spamfilter_users WHERE email = '@".$this->dataRecord["domain"]."'");
		$sql = "SELECT id, policy_name FROM spamfilter_policy WHERE ".$app->tform->getAuthSQL('r');
		$policys = $app->db->queryAllRecords($sql);
		$policy_select = "<option value='0'>".$app->tform->wordbook["no_policy"]."</option>";
		if(is_array($policys)) {
			foreach( $policys as $p) {
				$selected = ($p["id"] == $tmp_user["policy_id"])?'SELECTED':'';
				$policy_select .= "<option value='$p[id]' $selected>$p[policy_name]</option>\r\n";
			}
		}
		$app->tpl->setVar("policy",$policy_select);
		unset($policys);
		unset($policy_select);
		unset($tmp_user);

		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;
		if($_SESSION["s"]["user"]["typ"] != 'admin') {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_maildomain, default_mailserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// When the record is updated
			if($this->id > 0) {
				// restore the server ID if the user is not admin and record is edited
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM mail_domain WHERE domain_id = ".intval($this->id));
				$this->dataRecord["server_id"] = $tmp["server_id"];
				unset($tmp);
				// When the record is inserted
			} else {
				// set the server ID to the default mailserver of the client
				$this->dataRecord["server_id"] = $client["default_mailserver"];

				// Check if the user may add another mail_domain
				if($client["limit_maildomain"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM mail_domain WHERE sys_groupid = $client_group_id");
					if($tmp["number"] >= $client["limit_maildomain"]) {
						$app->error($app->tform->wordbook["limit_maildomain_txt"]);
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

	function onAfterInsert() {
		global $app, $conf;

		// make sure that the record belongs to the client group and not the admin group when a dmin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_domain SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE domain_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_domain SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE domain_id = ".$this->id);
		}

		// Spamfilter policy
		$policy_id = intval($this->dataRecord["policy"]);
		if($policy_id > 0) {
			$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = '@".$app->db->quote($this->dataRecord["domain"])."'");
			if($tmp_user["id"] > 0) {
				// There is already a record that we will update
				$app->db->datalogUpdate('spamfilter_users', "policy_id = $ploicy_id", 'id', $tmp_user["id"]);
			} else {
				$tmp_domain = $app->db->queryOneRecord("SELECT sys_groupid FROM mail_domain WHERE domain_id = ".$this->id);
				// We create a new record
				$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `priority`, `policy_id`, `email`, `fullname`, `local`)
				        VALUES (".$_SESSION["s"]["user"]["userid"].", ".$tmp_domain["sys_groupid"].", 'riud', 'riud', '', ".$this->dataRecord["server_id"].", 5, ".$policy_id.", '@".mysql_real_escape_string($this->dataRecord["domain"])."', '@".mysql_real_escape_string($this->dataRecord["domain"])."', 'Y')";
				$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
				unset($tmp_domain);
			}
		}  // endif spamfilter policy
	}

	function onBeforeUpdate() {
		global $app, $conf;

		//* Check if the server has been changed
		// We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$rec = $app->db->queryOneRecord("SELECT server_id, domain from mail_domain WHERE domain_id = ".$this->id);
			if($rec['server_id'] != $this->dataRecord["server_id"]) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Server can not be changed.');
				$this->dataRecord["server_id"] = $rec['server_id'];
			}
			unset($rec);
			//* If the user is neither admin nor reseller
		} else {
			//* We do not allow users to change a domain which has been created by the admin
			$rec = $app->db->queryOneRecord("SELECT domain from mail_domain WHERE domain_id = ".$this->id);
			if($rec['domain'] != $this->dataRecord["domain"] && $app->tform->checkPerm($this->id,'u')) {
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
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_domain SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE domain_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE mail_domain SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE domain_id = ".$this->id);
		}

		// Spamfilter policy
		$policy_id = intval($this->dataRecord["policy"]);
		$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = '@".$app->db->quote($this->dataRecord["domain"])."'");
		if($policy_id > 0) {
			if($tmp_user["id"] > 0) {
				// There is already a record that we will update
				$app->db->datalogUpdate('spamfilter_users', "policy_id = $policy_id", 'id', $tmp_user["id"]);
			} else {
				$tmp_domain = $app->db->queryOneRecord("SELECT sys_groupid FROM mail_domain WHERE domain_id = ".$this->id);
				// We create a new record
				$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `priority`, `policy_id`, `email`, `fullname`, `local`)
				        VALUES (".$_SESSION["s"]["user"]["userid"].", ".$tmp_domain["sys_groupid"].", 'riud', 'riud', '', ".$this->dataRecord["server_id"].", 5, ".$policy_id.", '@".mysql_real_escape_string($this->dataRecord["domain"])."', '@".mysql_real_escape_string($this->dataRecord["domain"])."', 'Y')";
				$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
				unset($tmp_domain);
			}
		} else {
			if($tmp_user["id"] > 0) {
				// There is already a record but the user shall have no policy, so we delete it
				$app->db->datalogDelete('spamfilter_users', 'id', $tmp_user["id"]);
			}
		} // endif spamfilter policy
		//** If the domain name or owner has been changed, change the domain and owner in all mailbox records
		if($this->oldDataRecord['domain'] != $this->dataRecord['domain'] || (isset($this->dataRecord['client_group_id']) && $this->oldDataRecord['sys_groupid'] != $this->dataRecord['client_group_id'])) {
			$app->uses('getconf');
			$mail_config = $app->getconf->get_server_config($this->dataRecord["server_id"],'mail');

			//* Update the mailboxes
			$mailusers = $app->db->queryAllRecords("SELECT * FROM mail_user WHERE email like '%@".$app->db->quote($this->oldDataRecord['domain'])."'");
			$sys_groupid = (isset($this->dataRecord['client_group_id']))?$this->dataRecord['client_group_id']:$this->oldDataRecord['sys_groupid'];
			if(is_array($mailusers)) {
				foreach($mailusers as $rec) {
					// setting Maildir, Homedir, UID and GID
					$mail_parts = explode("@",$rec['email']);
					$maildir = str_replace("[domain]",$this->dataRecord['domain'],$mail_config["maildir_path"]);
					$maildir = str_replace("[localpart]",$mail_parts[0],$maildir);
					$maildir = $app->db->quote($maildir);
					$email = $app->db->quote($mail_parts[0].'@'.$this->dataRecord['domain']);
					$app->db->datalogUpdate('mail_user', "maildir = '$maildir', email = '$email', sys_groupid = '$sys_groupid'", 'mailuser_id', $rec['mailuser_id']);
				}
			}

			//* Update the aliases
			$forwardings = $app->db->queryAllRecords("SELECT * FROM mail_forwarding WHERE source like '%@".$app->db->quote($this->oldDataRecord['domain'])."' OR destination like '%@".mysql_real_escape_string($this->oldDataRecord['domain'])."'");
			if(is_array($forwardings)) {
				foreach($forwardings as $rec) {
					$destination = $app->db->quote(str_replace($this->oldDataRecord['domain'],$this->dataRecord['domain'],$rec['destination']));
					$source = $app->db->quote(str_replace($this->oldDataRecord['domain'],$this->dataRecord['domain'],$rec['source']));
					$app->db->datalogUpdate('mail_forwarding', "source = '$source', destination = '$destination', sys_groupid = '$sys_groupid'", 'forwarding_id', $rec['forwarding_id']);
				}
			}
			
			//* Update the mailinglist
			$app->db->query("UPDATE mail_mailinglist SET sys_groupid = $sys_groupid WHERE domain = '".$app->db->quote($this->oldDataRecord['domain'])."'");

			//* Delete the old spamfilter record
			$tmp = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = '@".$app->db->quote($this->oldDataRecord["domain"])."'");
			$app->db->datalogDelete('spamfilter_users', 'id', $tmp["id"]);
			unset($tmp);

		} // end if domain name changed

	}

}

$page = new page_action;
$page->onLoad();

?>