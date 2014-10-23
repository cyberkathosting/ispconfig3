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
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, client.default_mailserver, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id order by client.contact_name");

			// Set the mailserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_mailserver]");
			$app->tpl->setVar("server_id", "<option value='$client[default_mailserver]'>$tmp[server_name]</option>");
			unset($tmp);

			if ($settings['use_domain_module'] != 'y') {
				// Fill the client select field
				$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$app->functions->intval($client['client_id'])." ORDER BY client.company_name, client.contact_name, sys_group.name";
				$clients = $app->db->queryAllRecords($sql);
				$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$app->functions->intval($client['client_id']));
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
			$client_mail = $app->db->queryOneRecord("SELECT mail_servers FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			$client_mail['mail_servers_ids'] = explode(',', $client_mail['mail_servers']);

			$only_one_server = count($client_mail['mail_servers_ids']) === 1;
			$app->tpl->setVar('only_one_server', $only_one_server);

			if ($only_one_server) {
				$app->tpl->setVar('server_id_value', $client_mail['mail_servers_ids'][0]);
			}

			$sql = "SELECT server_id, server_name FROM server WHERE server_id IN (" . $client_mail['mail_servers'] . ");";
			$mail_servers = $app->db->queryAllRecords($sql);

			$options_mail_servers = "";

			foreach ($mail_servers as $mail_server) {
				$options_mail_servers .= "<option value='$mail_server[server_id]'>$mail_server[server_name]</option>";
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
					$domain_select .= ">" . $app->functions->idn_decode($domain['domain']) . "</option>\r\n";
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
		}


		// Get the spamfilter policys for the user
		$tmp_user = $app->db->queryOneRecord("SELECT policy_id FROM spamfilter_users WHERE email = '@".$app->db->quote($this->dataRecord["domain"])."'");
		$sql = "SELECT id, policy_name FROM spamfilter_policy WHERE ".$app->tform->getAuthSQL('r')." ORDER BY policy_name";
		$policys = $app->db->queryAllRecords($sql);
		$policy_select = "<option value='0'>".$app->tform->wordbook["no_policy"]."</option>";
		if(is_array($policys)) {
			foreach( $policys as $p) {
				$selected = ($p["id"] == $tmp_user["policy_id"])?'SELECTED':'';
				$policy_select .= "<option value='$p[id]' $selected>$p[policy_name]</option>\r\n";
			}
		}
		$app->tpl->setVar("policy", $policy_select);
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
			$client = $app->db->queryOneRecord("SELECT limit_maildomain, default_mailserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			// When the record is updated
			if($this->id > 0) {
				// restore the server ID if the user is not admin and record is edited
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM mail_domain WHERE domain_id = ".$app->functions->intval($this->id));
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

		// Spamfilter policy
		$policy_id = $app->functions->intval($this->dataRecord["policy"]);
		if($policy_id > 0) {
			$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = '@".$app->db->quote($this->dataRecord["domain"])."'");
			if($tmp_user["id"] > 0) {
				// There is already a record that we will update
				$app->db->datalogUpdate('spamfilter_users', "policy_id = $policy_id", 'id', $tmp_user["id"]);
			} else {
				$tmp_domain = $app->db->queryOneRecord("SELECT sys_groupid FROM mail_domain WHERE domain_id = ".$this->id);
				// We create a new record
				$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `priority`, `policy_id`, `email`, `fullname`, `local`)
				        VALUES (".$_SESSION["s"]["user"]["userid"].", ".$app->functions->intval($tmp_domain["sys_groupid"]).", 'riud', 'riud', '', ".$app->functions->intval($this->dataRecord["server_id"]).", 5, ".$app->functions->intval($policy_id).", '@".$app->db->quote($this->dataRecord["domain"])."', '@".$app->db->quote($this->dataRecord["domain"])."', 'Y')";
				$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
				unset($tmp_domain);
			}
		} // endif spamfilter policy

		//* create dns-record with dkim-values if the zone exists
		if ( (isset($this->dataRecord['dkim']) && $this->dataRecord['dkim'] == 'y') && (isset($this->dataRecord['active']) && $this->dataRecord['active'] == 'y') ) {
			$soa_rec = $app->db->queryOneRecord("SELECT id AS zone, sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, server_id, ttl, serial FROM dns_soa WHERE active = 'Y' AND origin = ?", $this->dataRecord['domain'].'.');
			if ( isset($soa_rec) && !empty($soa_rec) ) {
				//* check for a dkim-record in the dns
				$dns_data = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE name = ? AND sys_groupid = ?", $this->dataRecord['dkim_selector'].'._domainkey.'.$this->dataRecord['domain'].'.', $_SESSION["s"]["user"]['sys_groupid']);
				if ( isset($dns_data) && !empty($dns_data) ) {
					$dns_data['data'] = 'v=DKIM1; t=s; p='.str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"), '', $this->dataRecord['dkim_public']);
					$dns_data['active'] = 'Y';
					$dns_data['stamp'] = date('Y-m-d H:i:s');
					$dns_data['serial'] = $app->validate_dns->increase_serial($dns_data['serial']);
					$app->db->datalogUpdate('dns_rr', $dns_data, 'id', $dns_data['id']);
					$zone = $app->db->queryOneRecord("SELECT id, serial FROM dns_soa WHERE active = 'Y' AND id = ?", $dns_data['zone']);
					$new_serial = $app->validate_dns->increase_serial($zone['serial']);
					$app->db->datalogUpdate('dns_soa', "serial = '".$new_serial."'", 'id', $zone['id']);
				} else { //* no dkim-record found - create new record
					$dns_data = $app->db->queryOneRecord("SELECT id AS zone, sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, server_id, ttl, serial FROM dns_soa WHERE active = 'Y' AND origin = ?", $this->dataRecord['domain'].'.');
					$dns_data['name'] = $this->dataRecord['dkim_selector'].'._domainkey.'.$this->dataRecord['domain'].'.';
					$dns_data['type'] = 'TXT';
					$dns_data['data'] = 'v=DKIM1; t=s; p='.str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"), '', $this->dataRecord['dkim_public']);
					$dns_data['aux'] = 0;
					$dns_data['active'] = 'Y';
					$dns_data['stamp'] = date('Y-m-d H:i:s');
					$dns_data['serial'] = $app->validate_dns->increase_serial($dns_data['serial']);
					$app->db->datalogInsert('dns_rr', $dns_data, 'id', $dns_data['zone']);
					$new_serial = $app->validate_dns->increase_serial($soa_rec['serial']);
					$app->db->datalogUpdate('dns_soa', "serial = '".$new_serial."'", 'id', $soa_rec['zone']);
				}
			}
		} //* endif add dns-record
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
			if($rec['domain'] != $this->dataRecord["domain"] && $app->tform->checkPerm($this->id, 'u')) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Domain can not be changed. Please ask your Administrator if you want to change the domain name.');
				$this->dataRecord["domain"] = $rec['domain'];
			}
			unset($rec);
		}

		//* update dns-record when the dkim record was changed
		// NOTE: only if the domain-name was not changed

		//* get domain-data from the db
		$mail_data = $app->db->queryOneRecord("SELECT * FROM mail_domain WHERE domain = ?", $this->dataRecord['domain']);
		
		if ( isset($mail_data) && !empty($mail_data) ) {
			$post_data = $mail_data;
			$post_data['dkim_selector'] = $this->dataRecord['dkim_selector'];
			$post_data['dkim_public'] = $this->dataRecord['dkim_public'];
			$post_data['dkim_private'] = $this->dataRecord['dkim_private'];
			if ( isset($this->dataRecord['dkim']) ) $post_data['dkim'] = 'y'; else $post_data['dkim'] = 'n';
			if ( isset($this->dataRecord['active']) ) $post_data['active'] = 'y'; else      $post_data['active'] = 'n';
		}

		//* dkim-value changed
		if ( $mail_data != $post_data ) {
			//* get the dns-record for the public from the db
			$dns_data = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE name = ? AND sys_groupid = ?", $mail_data['dkim_selector'].'._domainkey.'.$mail_data['domain'].'.', $mail_data['sys_groupid']);

			//* we modify dkim dns-values for active mail-domains only
			if ( $post_data['active'] == 'y' ) {
				if ( $post_data['dkim'] == 'n' ) {
					$new_dns_data['active'] = 'N';
				} else {
					if ( $post_data['dkim_selector'] != $mail_data['dkim_selector'] )
						$new_dns_data['name'] = $post_data['dkim_selector'].'._domainkey.'.$post_data['domain'].'.';
					if ( $post_data['dkim'] != $mail_data['dkim'] )
						$new_dns_data['active'] = 'Y';
					if ( $post_data['active'] != $mail_data['active'] && $post_data['active'] == 'y' )
						$new_dns_data['active'] = 'Y';
					if ( $post_data['dkim_public'] != $mail_data['dkim_public'] )
						$new_dns_data['data'] = 'v=DKIM1; t=s; p='.str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"), '', $post_data['dkim_public']);
				}
			} else $new_dns_data['active'] = 'N';

			if ( isset($dns_data) && !empty($dns_data) && isset($new_dns_data) ) {
				//* update dns-record
				$new_dns_data['serial'] = $app->validate_dns->increase_serial($dns_data['serial']);
				$app->db->datalogUpdate('dns_rr', $new_dns_data, 'id', $dns_data['id']);
				$zone = $app->db->queryOneRecord("SELECT id, serial FROM dns_soa WHERE active = 'Y' AND id = ?", $dns_data['zone']);
				$new_serial = $app->validate_dns->increase_serial($zone['serial']);
				$app->db->datalogUpdate('dns_soa', "serial = '".$new_serial."'", 'id', $zone['id']);
			} else {
				//* create a new dns-record
				$new_dns_data = $app->db->queryOneRecord("SELECT id AS zone, sys_userid, sys_groupid, sys_perm_user, sys_perm_group, sys_perm_other, server_id, ttl, serial FROM dns_soa WHERE active = 'Y' AND origin = ?", $mail_data['domain'].'.');
				//* create a new record only if the dns-zone exists
				if ( isset($new_dns_data) && !empty($new_dns_data) && $post_data['dkim'] == 'y' ) {
					$new_dns_data['name'] = $post_data['dkim_selector'].'._domainkey.'.$post_data['domain'].'.';
					$new_dns_data['type'] = 'TXT';
					$new_dns_data['data'] = 'v=DKIM1; t=s; p='.str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"), '', $post_data['dkim_public']);
					$new_dns_data['aux'] = 0;
					$new_dns_data['active'] = 'Y';
					$new_dns_data['stamp'] = date('Y-m-d H:i:s');
					$new_dns_data['serial'] = $app->validate_dns->increase_serial($new_dns_data['serial']);
					$app->db->datalogInsert('dns_rr', $new_dns_data, 'id', $new_dns_data['zone']);
					$zone = $app->db->queryOneRecord("SELECT id, serial FROM dns_soa WHERE active = 'Y' AND id = ?", $new_dns_data['zone']);
					$new_serial = $app->validate_dns->increase_serial($zone['serial']);
					$app->db->datalogUpdate('dns_soa', "serial = '".$new_serial."'", 'id', $zone['id']);
				}
			}
		} //* endif $mail_data != $post_data
	}

	function onAfterUpdate() {
		global $app, $conf;

		// Spamfilter policy
		$policy_id = $app->functions->intval($this->dataRecord["policy"]);
		$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = '@".$app->db->quote($this->dataRecord["domain"])."'");
		if($policy_id > 0) {
			if($tmp_user["id"] > 0) {
				// There is already a record that we will update
				$app->db->datalogUpdate('spamfilter_users', "policy_id = $policy_id", 'id', $tmp_user["id"]);
			} else {
				$tmp_domain = $app->db->queryOneRecord("SELECT sys_groupid FROM mail_domain WHERE domain_id = ".$this->id);
				// We create a new record
				$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `priority`, `policy_id`, `email`, `fullname`, `local`)
				        VALUES (".$_SESSION["s"]["user"]["userid"].", ".$app->functions->intval($tmp_domain["sys_groupid"]).", 'riud', 'riud', '', ".$app->functions->intval($this->dataRecord["server_id"]).", 5, ".$app->functions->intval($policy_id).", '@".$app->db->quote($this->dataRecord["domain"])."', '@".$app->db->quote($this->dataRecord["domain"])."', 'Y')";
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
			$mail_config = $app->getconf->get_server_config($this->dataRecord["server_id"], 'mail');

			//* Update the mailboxes
			$mailusers = $app->db->queryAllRecords("SELECT * FROM mail_user WHERE email like '%@".$app->db->quote($this->oldDataRecord['domain'])."'");
			$sys_groupid = $app->functions->intval((isset($this->dataRecord['client_group_id']))?$this->dataRecord['client_group_id']:$this->oldDataRecord['sys_groupid']);
			$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = $client_group_id");
			$client_user_id = $app->functions->intval(($tmp['userid'] > 0)?$tmp['userid']:1);
			if(is_array($mailusers)) {
				foreach($mailusers as $rec) {
					// setting Maildir, Homedir, UID and GID
					$mail_parts = explode("@", $rec['email']);
					$maildir = str_replace("[domain]", $this->dataRecord['domain'], $mail_config["maildir_path"]);
					$maildir = str_replace("[localpart]", $mail_parts[0], $maildir);
					$maildir = $app->db->quote($maildir);
					$email = $app->db->quote($mail_parts[0].'@'.$this->dataRecord['domain']);
					$app->db->datalogUpdate('mail_user', "maildir = '$maildir', email = '$email', sys_userid = $client_user_id, sys_groupid = '$sys_groupid'", 'mailuser_id', $rec['mailuser_id']);
				}
			}

			//* Update the aliases
			$forwardings = $app->db->queryAllRecords("SELECT * FROM mail_forwarding WHERE source like '%@".$app->db->quote($this->oldDataRecord['domain'])."' OR destination like '%@".$app->db->quote($this->oldDataRecord['domain'])."'");
			if(is_array($forwardings)) {
				foreach($forwardings as $rec) {
					$destination = $app->db->quote(str_replace($this->oldDataRecord['domain'], $this->dataRecord['domain'], $rec['destination']));
					$source = $app->db->quote(str_replace($this->oldDataRecord['domain'], $this->dataRecord['domain'], $rec['source']));
					$app->db->datalogUpdate('mail_forwarding', "source = '$source', destination = '$destination', sys_userid = $client_user_id, sys_groupid = '$sys_groupid'", 'forwarding_id', $rec['forwarding_id']);
				}
			}

			//* Update the mailinglist
			$app->db->query("UPDATE mail_mailinglist SET sys_userid = $client_user_id, sys_groupid = $sys_groupid WHERE domain = '".$app->db->quote($this->oldDataRecord['domain'])."'");
			
			//* Update fetchmail accounts
			$fetchmail = $app->db->queryAllRecords("SELECT * FROM mail_get WHERE destination like '%@".$app->db->quote($this->oldDataRecord['domain'])."'");
			if(is_array($fetchmail)) {
				foreach($fetchmail as $rec) {
					$destination = $app->db->quote(str_replace($this->oldDataRecord['domain'], $this->dataRecord['domain'], $rec['destination']));
					$app->db->datalogUpdate('mail_get', "destination = '$destination', sys_userid = $client_user_id, sys_groupid = '$sys_groupid'", 'mailget_id', $rec['mailget_id']);
				}
			}
			
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
