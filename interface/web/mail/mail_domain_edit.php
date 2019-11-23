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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('mail');

// Loading classes
$app->uses('tpl,tform,tform_actions,tools_sites');
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
		} else {
			$settings = $app->getconf->get_global_config('mail');
			$app->tform->formDef['tabs']['domain']['fields']['server_id']['default'] = intval($settings['default_mailserver']);
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;

		$app->uses('ini_parser,getconf');
		$settings = $app->getconf->get_global_config('domains');

		if($_SESSION["s"]["user"]["typ"] == 'admin' && $settings['use_domain_module'] != 'y') {
			// Getting Clients of the user
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY client.company_name, client.contact_name, sys_group.name";

			$clients = $app->db->queryAllRecords($sql);
			$clients = $app->functions->htmlentities($clients);
			$client_select = '';
			if($_SESSION["s"]["user"]["typ"] == 'admin') $client_select .= "<option value='0'></option>";
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id", $client_select);

		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, client.default_mailserver, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ? order by client.contact_name", $client_group_id);
			$client = $app->functions->htmlentities($client);

			// Set the mailserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ?", $client['default_mailserver']);
			$app->tpl->setVar("server_id", "<option value='$client[default_mailserver]'>" . $app->functions->htmlentities($tmp['server_name']) . "</option>");
			unset($tmp);

			if ($settings['use_domain_module'] != 'y') {
				// Fill the client select field
				$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ? ORDER BY client.company_name, client.contact_name, sys_group.name";
				$clients = $app->db->queryAllRecords($sql, $client['client_id']);
				$clients = $app->functions->htmlentities($clients);
				$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client['client_id']);
				$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contactname'].'</option>';
				//$tmp_data_record = $app->tform->getDataRecord($this->id);
				if(is_array($clients)) {
					foreach( $clients as $client) {
						$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
						$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
					}
				}
				$app->tpl->setVar("client_group_id", $client_select);
			}
		}

		if($_SESSION["s"]["user"]["typ"] != 'admin')
		{
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client_mail = $app->db->queryOneRecord("SELECT mail_servers FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			$client_mail['mail_servers_ids'] = explode(',', $client_mail['mail_servers']);

			$only_one_server = count($client_mail['mail_servers_ids']) === 1;
			$app->tpl->setVar('only_one_server', $only_one_server);

			if ($only_one_server) {
				$app->tpl->setVar('server_id_value', $client_mail['mail_servers_ids'][0]);
			}

			$sql = "SELECT server_id, server_name FROM server WHERE server_id IN ?";
			$mail_servers = $app->db->queryAllRecords($sql, $client_mail['mail_servers_ids']);

			$options_mail_servers = "";

			foreach ($mail_servers as $mail_server) {
				$options_mail_servers .= '<option value="'.$mail_server['server_id'].'"'.($this->id > 0 && $this->dataRecord["server_id"] == $mail_server['server_id'] ? ' selected="selected"' : '').'>'.$app->functions->htmlentities($mail_server['server_name']).'</option>';
			}

			$app->tpl->setVar("client_server_id", $options_mail_servers);
			unset($options_mail_servers);

		}

		/*
		 * Now we have to check, if we should use the domain-module to select the domain
		 * or not
		 */
		if ($settings['use_domain_module'] == 'y') {
			/*
			 * The domain-module is in use.
			*/
			$domains = $app->tools_sites->getDomainModuleDomains("mail_domain", $this->dataRecord["domain"]);
			$domain_select = '';
			if(is_array($domains) && sizeof($domains) > 0) {
				/* We have domains in the list, so create the drop-down-list */
				foreach( $domains as $domain) {
					$domain_select .= "<option value=" . $domain['domain_id'] ;
					if ($domain['domain'] == $this->dataRecord["domain"]) {
						$domain_select .= " selected";
					}
					$domain_select .= ">" . $app->functions->htmlentities($app->functions->idn_decode($domain['domain'])) . "</option>\r\n";
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
			$app->tpl->setVar("domain_option", $domain_select);
			$app->tpl->setVar("domain_module", 1);
		} else {
			$app->tpl->setVar("domain_module", 0);
		}


		// Get the spamfilter policys for the user
		$tmp_user = $app->db->queryOneRecord("SELECT policy_id FROM spamfilter_users WHERE email = ?", '@' . $this->dataRecord["domain"]);
		$sql = "SELECT id, policy_name FROM spamfilter_policy WHERE ".$app->tform->getAuthSQL('r')." ORDER BY policy_name";
		$policys = $app->db->queryAllRecords($sql);
		$policy_select = "<option value='0'>".$app->tform->wordbook["no_policy"]."</option>";
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

		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"], true);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		// load dkim-values
		$sql = "SELECT domain, dkim_private, dkim_public, dkim_selector FROM mail_domain WHERE domain_id = ?";
		$rec = $app->db->queryOneRecord($sql, $app->functions->intval($_GET['id']));
		$dns_key = str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"),'',$rec['dkim_public']);
		$dns_record = $rec['dkim_selector'] . '._domainkey.' . $rec['domain'] . '. 3600   TXT   v=DKIM1; t=s; p=' . $dns_key;
		$app->tpl->setVar('dkim_selector', $rec['dkim_selector'], true);
		$app->tpl->setVar('dkim_private', $rec['dkim_private'], true);
		$app->tpl->setVar('dkim_public', $rec['dkim_public'], true);
		if (!empty($rec['dkim_public'])) $app->tpl->setVar('dns_record', $dns_record, true);

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		/* check if the domain module is used - and check if the selected domain can be used! */
		$app->uses('ini_parser,getconf');
		$settings = $app->getconf->get_global_config('domains');
		if ($settings['use_domain_module'] == 'y') {
			if ($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
				$this->dataRecord['client_group_id'] = $app->tools_sites->getClientIdForDomain($this->dataRecord['domain']);
			}
			$domain_check = $app->tools_sites->checkDomainModuleDomain($this->dataRecord['domain']);
			if(!$domain_check) {
				// invalid domain selected
				$app->tform->errorMessage .= $app->tform->lng("domain_error_empty")."<br />";
			} else {
				$this->dataRecord['domain'] = $domain_check;
			}
		}

		if($_SESSION["s"]["user"]["typ"] != 'admin') {
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT client.mail_servers, limit_maildomain, default_mailserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			// When the record is updated
			if($this->id > 0) {
				// restore the server ID if the user is not admin and record is edited
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM mail_domain WHERE domain_id = ?", $this->id);
				$this->dataRecord["server_id"] = $tmp["server_id"];
				unset($tmp);
				// When the record is inserted
			} else {
				$client['mail_servers_ids'] = explode(',', $client['mail_servers']);

				// Check if chosen server is in authorized servers for this client
				if (!(is_array($client['mail_servers_ids']) && in_array($this->dataRecord["server_id"], $client['mail_servers_ids']))) {
					$app->error($app->tform->wordbook['error_not_allowed_server_id']);
				}

				if($client["limit_maildomain"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM mail_domain WHERE sys_groupid = ?", $client_group_id);
					if($tmp["number"] >= $client["limit_maildomain"]) {
						$app->error($app->tform->wordbook["limit_maildomain_txt"]);
					}
				}
			}

			// Clients may not set the client_group_id, so we unset them if user is not a admin
			if(!$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);
		}

		//* make sure that the email domain is lowercase
		if(isset($this->dataRecord["domain"])){
			$this->dataRecord["domain"] = $app->functions->idn_encode($this->dataRecord["domain"]);
			$this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);
		}


		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app, $conf;
		
		$domain = $app->functions->idn_encode($this->dataRecord["domain"]);

		// Spamfilter policy
		$policy_id = $app->functions->intval($this->dataRecord["policy"]);
		if($policy_id > 0) {
			$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", '@' . $domain);
			if($tmp_user["id"] > 0) {
				// There is already a record that we will update
				$app->db->datalogUpdate('spamfilter_users', array("policy_id" => $policy_id), 'id', $tmp_user["id"]);
			} else {
				$tmp_domain = $app->db->queryOneRecord("SELECT sys_groupid FROM mail_domain WHERE domain_id = ?", $this->id);
				// We create a new record
				$insert_data = array(
					"sys_userid" => $_SESSION["s"]["user"]["userid"], 
					"sys_groupid" => $tmp_domain["sys_groupid"],
					"sys_perm_user" => 'riud', 
					"sys_perm_group" => 'riud', 
					"sys_perm_other" => '',
					"server_id" => $this->dataRecord["server_id"],
					"priority" => 5,
					"policy_id" => $policy_id,
					"email" => '@' . $domain,
					"fullname" => '@' . $domain,
					"local" => 'Y'
				);
				$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
				unset($tmp_domain);
			}
		} // endif spamfilter policy

		//* create dns-record with dkim-values if the zone exists
		if ( $this->dataRecord['active'] == 'y' && $this->dataRecord['dkim'] == 'y' ) {
			$soaDomain = $this->dataRecord['domain'].'.';
 			while ((!isset($soa) && (substr_count($soaDomain,'.') > 1))) {
				$soa = $app->db->queryOneRecord("SELECT id AS zone, sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, server_id, ttl, serial FROM dns_soa WHERE active = 'Y' AND origin = ?", $soaDomain);
				$soaDomain = preg_replace("/^[^\.]+\./","",$soaDomain);
			}
			if ( isset($soa) && !empty($soa) ) $this->update_dns($this->dataRecord, $soa);
		}

	}

	function onBeforeUpdate() {
		global $app, $conf;
		
		$domain = $app->functions->idn_encode($this->dataRecord["domain"]);

		//* Check if the server has been changed
		// We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$rec = $app->db->queryOneRecord("SELECT server_id, domain from mail_domain WHERE domain_id = ?", $this->id);
			if($rec['server_id'] != $this->dataRecord["server_id"]) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Server can not be changed.');
				$this->dataRecord["server_id"] = $rec['server_id'];
			}
			unset($rec);
			//* If the user is neither admin nor reseller
		} else {
			//* We do not allow users to change a domain which has been created by the admin
			$rec = $app->db->queryOneRecord("SELECT domain from mail_domain WHERE domain_id = ?", $this->id);
			if($rec['domain'] != $domain && !$app->tform->checkPerm($this->id, 'u')) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Domain can not be changed. Please ask your Administrator if you want to change the domain name.');
				$this->dataRecord["domain"] = $rec['domain'];
			}
			unset($rec);
		}

	}

	function onAfterUpdate() {
		global $app, $conf;

		$domain = $app->functions->idn_encode($this->dataRecord["domain"]);
		
		// Spamfilter policy
		$policy_id = $app->functions->intval($this->dataRecord["policy"]);
		$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", '@' . $domain);
		if($policy_id > 0) {
			if($tmp_user["id"] > 0) {
				// There is already a record that we will update
				$app->db->datalogUpdate('spamfilter_users', array("policy_id" => $policy_id), 'id', $tmp_user["id"]);
			} else {
				$tmp_domain = $app->db->queryOneRecord("SELECT sys_groupid FROM mail_domain WHERE domain_id = ?", $this->id);
				// We create a new record
				$insert_data = array(
					"sys_userid" => $_SESSION["s"]["user"]["userid"], 
					"sys_groupid" => $tmp_domain["sys_groupid"],
					"sys_perm_user" => 'riud', 
					"sys_perm_group" => 'riud', 
					"sys_perm_other" => '',
					"server_id" => $this->dataRecord["server_id"],
					"priority" => 5,
					"policy_id" => $policy_id,
					"email" => '@' . $domain,
					"fullname" => '@' . $domain,
					"local" => 'Y'
				);
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
		if($this->oldDataRecord['domain'] != $domain || (isset($this->dataRecord['client_group_id']) && $this->oldDataRecord['sys_groupid'] != $this->dataRecord['client_group_id'])) {
			$app->uses('getconf');
			$mail_config = $app->getconf->get_server_config($this->dataRecord["server_id"], 'mail');

			//* Update the mailboxes
			$mailusers = $app->db->queryAllRecords("SELECT * FROM mail_user WHERE email like ?", '%@' . $this->oldDataRecord['domain']);
			$sys_groupid = $app->functions->intval((isset($this->dataRecord['client_group_id']))?$this->dataRecord['client_group_id']:$this->oldDataRecord['sys_groupid']);
			$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = ?", $client_group_id);
			$client_user_id = $app->functions->intval(($tmp['userid'] > 0)?$tmp['userid']:1);
			if(is_array($mailusers)) {
				foreach($mailusers as $rec) {
					// setting Maildir, Homedir, UID and GID
					$mail_parts = explode("@", $rec['email']);
					$maildir = str_replace("[domain]", $domain, $mail_config["maildir_path"]);
					$maildir = str_replace("[localpart]", $mail_parts[0], $maildir);
					$email = $mail_parts[0].'@'.$this->dataRecord['domain'];
					$app->db->datalogUpdate('mail_user', array("maildir" => $maildir, "email" => $email, "sys_userid" => $client_user_id, "sys_groupid" => $sys_groupid), 'mailuser_id', $rec['mailuser_id']);
				}
			}

			//* Update the aliases
			$forwardings = $app->db->queryAllRecords("SELECT * FROM mail_forwarding WHERE source like ? OR destination like ?", '%@' . $this->oldDataRecord['domain'], '%@' . $this->oldDataRecord['domain']);
			if(is_array($forwardings)) {
				foreach($forwardings as $rec) {
					$destination = str_replace($this->oldDataRecord['domain'], $domain, $rec['destination']);
					$source = str_replace($this->oldDataRecord['domain'], $domain, $rec['source']);
					$app->db->datalogUpdate('mail_forwarding', array("source" => $source, "destination" => $destination, "sys_userid" => $client_user_id, "sys_groupid" => $sys_groupid), 'forwarding_id', $rec['forwarding_id']);
				}
			}

			//* Update the mailinglist
			$app->db->query("UPDATE mail_mailinglist SET sys_userid = ?, sys_groupid = ? WHERE domain = ?", $client_user_id, $sys_groupid, $this->oldDataRecord['domain']);
			
			//* Update fetchmail accounts
			$fetchmail = $app->db->queryAllRecords("SELECT * FROM mail_get WHERE destination like ?", '%@' . $this->oldDataRecord['domain']);
			if(is_array($fetchmail)) {
				foreach($fetchmail as $rec) {
					$destination = str_replace($this->oldDataRecord['domain'], $domain, $rec['destination']);
					$app->db->datalogUpdate('mail_get', array("destination" => $destination, "sys_userid" => $client_user_id, "sys_groupid" => $sys_groupid), 'mailget_id', $rec['mailget_id']);
				}
			}
			
			//* Delete the old spamfilter record
			$tmp = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", '@' . $this->oldDataRecord["domain"]);
			$app->db->datalogDelete('spamfilter_users', 'id', $tmp["id"]);
			unset($tmp);

		} // end if domain name changed

		//* update dns-record when the dkim record was changed
		// NOTE: only if the domain-name was not changed
		if ( $this->dataRecord['active'] == 'y' && $domain ==  $this->oldDataRecord['domain'] ) {
			$dkim_active = @($this->dataRecord['dkim'] == 'y') ? true : false; 
			$selector = @($this->dataRecord['dkim_selector'] != $this->oldDataRecord['dkim_selector']) ? true : false;
			$dkim_private = @($this->dataRecord['dkim_private'] != $this->oldDataRecord['dkim_private']) ? true : false;
			
			$soaDomain = $domain.'.';
			while ((!isset($soa) && (substr_count($soaDomain,'.') > 1))) {
				$soa = $app->db->queryOneRecord("SELECT id AS zone, sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, server_id, ttl, serial FROM dns_soa WHERE active = 'Y' AND origin = ?", $soaDomain);
				$soaDomain = preg_replace("/^[^\.]+\./","",$soaDomain);
			}

			if ( ($selector || $dkim_private || $dkim_active) && $dkim_active )
				//* create a new record only if the dns-zone exists
				if ( isset($soa) && !empty($soa) ) {
					$this->update_dns($this->dataRecord, $soa);
				}
			if (! $dkim_active) {
				// updated existing dmarc-record to policy 'none'
				$sql = "SELECT * from dns_rr WHERE name = ? AND data LIKE 'v=DMARC1%' AND " . $app->tform->getAuthSQL('r');
				$rec = $app->db->queryOneRecord($sql, '_dmarc.'.$domain.'.');
				if (is_array($rec))
					if (strpos($rec['data'], 'p=none=') === false) {
						$rec['data'] = str_replace(array('quarantine', 'reject'), 'none', $rec['data']);
						$app->db->datalogUpdate('dns_rr', $rec, 'id', $rec['id']);
						$soa_id = $app->functions->intval($soa['zone']);
						$serial = $app->validate_dns->increase_serial($soa["serial"]);
						$app->db->datalogUpdate('dns_soa', array("serial" => $serial), 'id', $soa_id);
					}	
				}
		}

	}

	private function update_dns($dataRecord, $new_rr) {
		global $app, $conf;

		// purge old rr-record(s)
		$sql = "SELECT * FROM dns_rr WHERE name LIKE ? AND data LIKE 'v=DKIM1%' AND " . $app->tform->getAuthSQL('r') . " ORDER BY serial DESC";
		$rec = $app->db->queryAllRecords($sql, '%._domainkey.'.$dataRecord['domain'].'.');
		if(is_array($rec)) {
			foreach($rec as $r) {
				$app->db->datalogDelete('dns_rr', 'id', $r['id']);
			}
		}
		
		// also delete a dsn-records with same selector 
		$sql = "SELECT * from dns_rr WHERE name ? AND data LIKE 'v=DKIM1%' AND " . $app->tform->getAuthSQL('r');
		$rec = $app->db->queryAllRecords($sql, '._domainkey.'.$dataRecord['dkim_selector'].'.', $dataRecord['domain']);
		if (is_array($rec))
			foreach ($rec as $del)
				$app->db->datalogDelete('dns_rr', 'id', $del['id']);

		$new_rr['name'] = $dataRecord['dkim_selector'].'._domainkey.'.$dataRecord['domain'].'.';
		$new_rr['type'] = 'TXT';
		$new_rr['data'] = 'v=DKIM1; t=s; p='.str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"), '', $this->dataRecord['dkim_public']);
		$new_rr['aux'] = 0;
		$new_rr['active'] = 'Y';
		$new_rr['stamp'] = date('Y-m-d H:i:s');
		$new_rr['serial'] = $app->validate_dns->increase_serial($new_rr['serial']);
		$app->db->datalogInsert('dns_rr', $new_rr, 'id', $new_rr['zone']);
		$zone = $app->db->queryOneRecord("SELECT id, serial FROM dns_soa WHERE active = 'Y' AND id = ?", $new_rr['zone']);
		$new_serial = $app->validate_dns->increase_serial($zone['serial']);
		$app->db->datalogUpdate('dns_soa', array("serial" => $new_serial), 'id', $zone['id']);
	}
}

$page = new page_action;
$page->onLoad();

?>
