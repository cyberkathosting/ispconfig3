<?php
/*
Copyright (c) 2005 - 2012, Till Brehm, projektfarm Gmbh, ISPConfig UG
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

$tform_def_file = "form/client.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('client');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {
	var $_template_additional = array();

	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {

			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_client FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another website.
			if($client["limit_client"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(client_id) as number FROM client WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_client"]) {
					$app->error($app->tform->wordbook["limit_client_txt"]);
				}
			}
		}

		parent::onShowNew();
	}


	function onSubmit() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user' && $this->id == 0) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_client FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another website.
			if($client["limit_client"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(client_id) as number FROM client WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_client"]) {
					$app->error($app->tform->wordbook["limit_client_txt"]);
				}
			}
		}
		
		//* Resellers shall not be able to create another reseller
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			$this->dataRecord['limit_client'] = 0;
		} else {
			if($this->dataRecord["reseller"]) {
				$this->dataRecord["limit_client"] = 1; // allow 1 client, template limits will be applied later, if we set -1 it would override template limits
			}
		}

		if($this->id != 0) {
			$this->oldTemplatesAssigned = $app->db->queryAllRecords('SELECT * FROM `client_template_assigned` WHERE `client_id` = ?', $this->id);
			if(!is_array($this->oldTemplatesAssigned) || count($this->oldTemplatesAssigned) < 1) {
				// check previous type of storing templates
				$tpls = explode('/', $this->oldDataRecord['template_additional']);
				$this->oldTemplatesAssigned = array();
				foreach($tpls as $item) {
					$item = trim($item);
					if(!$item) continue;
					$this->oldTemplatesAssigned[] = array('assigned_template_id' => 0, 'client_template_id' => $item, 'client_id' => $this->id);
				}
				unset($tpls);
			}
		} else {
			$this->oldTemplatesAssigned = array();
		}

		$this->_template_additional = explode('/', $this->dataRecord['template_additional']);
		$this->dataRecord['template_additional'] = '';

		parent::onSubmit();
	}

	function onShowEnd() {

		global $app;

		$sql = "SELECT template_id,template_name FROM client_template WHERE template_type = 'a' and ".$app->tform->getAuthSQL('r')." ORDER BY template_name ASC";
		$tpls = $app->db->queryAllRecords($sql);
		$option = '';
		$tpl = array();
		$tpls = $app->functions->htmlentities($tpls);
		foreach($tpls as $item){
			$option .= '<option value="' . $item['template_id'] . '|' .  $item['template_name'] . '">' . $item['template_name'] . '</option>';
			$tpl[$item['template_id']] = $item['template_name'];
		}
		$app->tpl->setVar('tpl_add_select', $option);

		// check for new-style records
		$result = $app->db->queryAllRecords('SELECT assigned_template_id, client_template_id FROM client_template_assigned WHERE client_id = ?', $this->id);
		if($result && count($result) > 0) {
			// new style
			$items = array();
			$text = '';
			foreach($result as $item){
				if (trim($item['client_template_id']) != ''){
					if ($text != '') $text .= '';
					$text .= '<li rel="' . $item['assigned_template_id'] . '">' . $tpl[$item['client_template_id']];
					$text .= '&nbsp;<a href="#" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></a>';
					$tmp = new stdClass();
					$tmp->id = $item['assigned_template_id'];
					$tmp->data = '';
					$app->plugin->raiseEvent('get_client_template_details', $tmp);
					if($tmp->data != '') $text .= '<br /><em>' . $app->functions->htmlentities($tmp->data) . '</em>';

					$text .= '</li>';
					$items[] = $item['assigned_template_id'] . ':' . $item['client_template_id'];
				}
			}

			$tmprec = $app->tform->getHTML(array('template_additional' => implode('/', $items)), $this->active_tab, 'EDIT');
			$app->tpl->setVar('template_additional', $tmprec['template_additional']);
			unset($tmprec);
		} else {
			// old style
			$sql = "SELECT template_additional FROM client WHERE client_id = ?";
			$result = $app->db->queryOneRecord($sql, $this->id);
			$tplAdd = explode("/", $result['template_additional']);
			$text = '';
			foreach($tplAdd as $item){
				if (trim($item) != ''){
					if ($text != '') $text .= '';
					$text .= '<li>' . $tpl[$item]. '&nbsp;<a href="#" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></a></li>';
				}
			}
		}

		$app->tpl->setVar('template_additional_list', $text);
		$app->tpl->setVar('app_module', 'client');
		

		//* Set the 'customer no' default value
		if($this->id == 0) {
			
			if($app->auth->is_admin()) {
				//* Logged in User is admin
				//* get the system config
				$app->uses('getconf');
				$system_config = $app->getconf->get_global_config();
				if($system_config['misc']['customer_no_template'] != '') {
				
					//* Set customer no default
					$customer_no = $app->functions->intval($system_config['misc']['customer_no_start']+$system_config['misc']['customer_no_counter']);
					$customer_no_string = str_replace('[CUSTOMER_NO]',$customer_no,$system_config['misc']['customer_no_template']);
					$app->tpl->setVar('customer_no',$customer_no_string);
				}
			} else {
				//* Logged in user must be a reseller
				//* get the record of the reseller
				$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
				$reseller = $app->db->queryOneRecord("SELECT client.client_id, client.customer_no_template, client.customer_no_counter, client.customer_no_start FROM sys_group,client WHERE client.client_id = sys_group.client_id and sys_group.groupid = ?", $client_group_id);
				
				if($reseller['customer_no_template'] != '') {
					if(isset($this->dataRecord['customer_no'])&& $this->dataRecord['customer_no']!='') $customer_no_string = $this->dataRecord['customer_no'];
					else {
						//* Set customer no default
						$customer_no = $app->functions->intval($reseller['customer_no_start']+$reseller['customer_no_counter']);
						$customer_no_string = str_replace(array('[CUSTOMER_NO]','[CLIENTID]'),array($customer_no, $reseller['client_id']),$reseller['customer_no_template']);
					}
					$app->tpl->setVar('customer_no',$customer_no_string);
				}
			}
		}
		
		if($app->auth->is_admin()) {
			// Fill the client select field
			$sql = "SELECT client.client_id, sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 AND client.limit_client != 0 ORDER BY client.company_name, client.contact_name, sys_group.name";
			$clients = $app->db->queryAllRecords($sql);
			$clients = $app->functions->htmlentities($clients);
			$client_select = "<option value='0'>- ".$app->tform->lng('none_txt')." -</option>";
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				$selected_client_id = 0; // needed to get list of PHP versions
				foreach($clients as $client) {
					if(is_array($this->dataRecord) && ($client["client_id"] == $this->dataRecord['parent_client_id']) && !$selected_client_id) $selected_client_id = $client["client_id"];
					$selected = @(is_array($this->dataRecord) && ($client["client_id"] == $this->dataRecord['parent_client_id']))?'SELECTED':'';
					if($selected == 'SELECTED') $selected_client_id = $client["client_id"];
					$client_select .= "<option value='$client[client_id]' $selected>$client[contactname]</option>\r\n";
				}
			}
			$app->tpl->setVar("parent_client_id", $client_select);
		}
		
		parent::onShowEnd();

	}

	/*
	 This function is called automatically right after
	 the data was successful inserted in the database.
	*/
	function onAfterInsert() {
		global $app, $conf;
		// Create the group for the client
		$groupid = $app->db->datalogInsert('sys_group', array("name" => $this->dataRecord["username"], "description" => '', "client_id" => $this->id), 'groupid');
		$groups = $groupid;

		$username = $this->dataRecord["username"];
		$password = $this->dataRecord["password"];
		$modules = $conf['interface_modules_enabled'];
		if(isset($this->dataRecord["limit_client"]) && $this->dataRecord["limit_client"] > 0) $modules .= ',client';
		$startmodule = (stristr($modules, 'dashboard'))?'dashboard':'client';
		$usertheme = (isset($this->dataRecord["usertheme"]) && $this->dataRecord["usertheme"] != ''? $this->dataRecord["usertheme"] : 'default');
		$type = 'user';
		$active = 1;
		$language = $this->dataRecord["language"];
		$password = $app->auth->crypt_password($password);

		// Create the controlpaneluser for the client
		//Generate ssh-rsa-keys
		$app->uses('functions');
		$app->functions->generate_ssh_key($this->id, $username);

		// Create the controlpaneluser for the client
		$sql = "INSERT INTO sys_user (`username`,`passwort`,`modules`,`startmodule`,`app_theme`,`typ`,`active`,`language`,`groups`,`default_group`,`client_id`)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$app->db->query($sql, $username, $password, $modules, $startmodule, $usertheme, $type, $active, $language, $groups, $groupid, $this->id);

		//* If the user who inserted the client is a reseller (not admin), we will have to add this new client group
		//* to his groups, so he can administrate the records of this client.
		if($_SESSION['s']['user']['typ'] == 'user') {
			$app->auth->add_group_to_user($_SESSION['s']['user']['userid'], $groupid);
			$app->db->query("UPDATE client SET parent_client_id = ? WHERE client_id = ?", $_SESSION['s']['user']['client_id'], $this->id);
		} else {
			if($this->dataRecord['parent_client_id'] > 0) {
				//* get userid of the reseller and add it to the group of the client
				$tmp = $app->db->queryOneRecord("SELECT sys_user.userid FROM sys_user,sys_group WHERE sys_user.default_group = sys_group.groupid AND sys_group.client_id = ?", $this->dataRecord['parent_client_id']);
				$app->auth->add_group_to_user($tmp['userid'], $groupid);
				$app->db->query("UPDATE client SET parent_client_id = ? WHERE client_id = ?", $this->dataRecord['parent_client_id'], $this->id);
				unset($tmp);
			}
		}

		//* Set the default servers
		$tmp = $app->getconf->get_global_config('mail');
		$default_mailserver = $app->functions->intval($tmp['default_mailserver']);
		if (!$default_mailserver) {
			$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE mail_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
			$default_mailserver = $app->functions->intval($tmp['server_id']);
		}
		$tmp = $app->getconf->get_global_config('sites');
		$default_webserver = $app->functions->intval($tmp['default_webserver']);
		$default_dbserver = $app->functions->intval($tmp['default_dbserver']);
		if (!$default_webserver) {
			$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE web_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
			$default_webserver = $app->functions->intval($tmp['server_id']);
		}
		if (!$default_dbserver) {
			$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE db_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
			$default_dbserver = $app->functions->intval($tmp['server_id']);
		}
		$tmp = $app->getconf->get_global_config('dns');
		$default_dnsserver = $app->functions->intval($tmp['default_dnsserver']);
		if (!$default_dnsserver) {
			$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE dns_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
			$default_dnsserver = $app->functions->intval($tmp['server_id']);
		}

		$sql = "UPDATE client SET mail_servers = ?, web_servers = ?, dns_servers = ?, default_slave_dnsserver = ?, db_servers = ? WHERE client_id = ?";
		$app->db->query($sql, $default_mailserver, $default_webserver, $default_dnsserver, $default_dnsserver, $default_dbserver, $this->id);

		if(isset($this->dataRecord['template_master'])) {
			$app->uses('client_templates');
			$app->client_templates->update_client_templates($this->id, $this->_template_additional);
		}
		
		if($this->dataRecord['customer_no'] == $this->dataRecord['customer_no_org']) {
			if($app->auth->is_admin()) {
				//* Logged in User is admin
				//* get the system config
				$app->uses('getconf');
				$system_config = $app->getconf->get_global_config();
				if($system_config['misc']['customer_no_template'] != '') {
				
					//* save new counter value
					$system_config['misc']['customer_no_counter']++;
					$system_config_str = $app->ini_parser->get_ini_string($system_config);
					$app->db->datalogUpdate('sys_ini', array("config" => $system_config_str), 'sysini_id', 1);
				}
			} else {
				//* Logged in user must be a reseller
				//* get the record of the reseller
				$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
				$reseller = $app->db->queryOneRecord("SELECT client.client_id, client.customer_no_template, client.customer_no_counter, client.customer_no_start FROM sys_group,client WHERE client.client_id = sys_group.client_id and sys_group.groupid = ?", $client_group_id);
				
				if($reseller['customer_no_template'] != '') {
					//* save new counter value
					$customer_no_counter = $app->functions->intval($reseller['customer_no_counter']+1);
					$app->db->query("UPDATE client SET customer_no_counter = ? WHERE client_id = ?", $customer_no_counter, $reseller['client_id']);
				}
			}
		}
		
		//* Send welcome email
		$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
		$sql = "SELECT * FROM client_message_template WHERE template_type = 'welcome' AND sys_groupid = ?";
		$email_template = $app->db->queryOneRecord($sql, $client_group_id);
		$client = $app->tform->getDataRecord($this->id);
		if(is_array($email_template) && $email_template['subject'] != '' && $email_template['message'] != '' && $client['email'] != '') {
			//* Parse client details into message
			$message = $email_template['message'];
			$subject = $email_template['subject'];
			foreach($client as $key => $val) {
				switch ($key) {
				case 'password':
					$message = str_replace('{password}', $this->dataRecord['password'], $message);
					$subject = str_replace('{password}', $this->dataRecord['password'], $subject);
					break;
				case 'gender':
					$message = str_replace('{salutation}', $app->tform->lng('gender_'.$val.'_txt'), $message);
					$subject = str_replace('{salutation}', $app->tform->lng('gender_'.$val.'_txt'), $subject);
					break;
				default:
					$message = str_replace('{'.$key.'}', $val, $message);
					$subject = str_replace('{'.$key.'}', $val, $subject);
				}
			}
			
			//* Get sender address
			if($app->auth->is_admin()) {
				$app->uses('getconf');
				$system_config = $app->getconf->get_global_config('mail');
				$from = $system_config['admin_mail'];
			} else {
				$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
				$reseller = $app->db->queryOneRecord("SELECT client.email FROM sys_group,client WHERE client.client_id = sys_group.client_id and sys_group.groupid = ?", $client_group_id);
				$from = $reseller["email"];
			}

			//* Send the email
			$app->functions->mail($client['email'], $subject, $message, $from);
		}
		

		parent::onAfterInsert();
	}


	/*
	 This function is called automatically right after
	 the data was successful updated in the database.
	*/
	function onAfterUpdate() {
		global $app, $conf;
		// username changed
		if(isset($conf['demo_mode']) && $conf['demo_mode'] != true && isset($this->dataRecord['username']) && $this->dataRecord['username'] != '' && $this->oldDataRecord['username'] != $this->dataRecord['username']) {
			$username = $this->dataRecord["username"];
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET username = ? WHERE client_id = ?";
			$app->db->query($sql, $username, $client_id);

			$tmp = $app->db->queryOneRecord("SELECT * FROM sys_group WHERE client_id = ?", $client_id);
			$app->db->datalogUpdate("sys_group", array("name" => $username), 'groupid', $tmp['groupid']);
			unset($tmp);
		}

		// password changed
		if(isset($conf['demo_mode']) && $conf['demo_mode'] != true && isset($this->dataRecord["password"]) && $this->dataRecord["password"] != '') {
			$password = $this->dataRecord["password"];
			$password = $app->auth->crypt_password($password);
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET passwort = ? WHERE client_id = ?";
			$app->db->query($sql, $password, $client_id);
		}

		if(!isset($this->dataRecord['locked'])) $this->dataRecord['locked'] = 'n';
		if(isset($conf['demo_mode']) && $conf['demo_mode'] != true && $this->dataRecord["locked"] != $this->oldDataRecord['locked']) {
			/** lock all the things like web, mail etc. - easy to extend */


			// get tmp_data of client
			$client_data = $app->db->queryOneRecord('SELECT `tmp_data` FROM `client` WHERE `client_id` = ?', $this->id);

			if($client_data['tmp_data'] == '') $tmp_data = array();
			else $tmp_data = unserialize($client_data['tmp_data']);

			if(!is_array($tmp_data)) $tmp_data = array();

			// database tables with their primary key columns
			$to_disable = array('cron' => 'id',
				'ftp_user' => 'ftp_user_id',
				'mail_domain' => 'domain_id',
				'mail_user' => 'mailuser_id',
				'mail_user_smtp' => 'mailuser_id',
				'mail_forwarding' => 'forwarding_id',
				'mail_get' => 'mailget_id',
				'openvz_vm' => 'vm_id',
				'shell_user' => 'shell_user_id',
				'webdav_user' => 'webdav_user_id',
				'web_database' => 'database_id',
				'web_domain' => 'domain_id',
				'web_folder' => 'web_folder_id',
				'web_folder_user' => 'web_folder_user_id'
			);

			$udata = $app->db->queryOneRecord('SELECT `userid` FROM `sys_user` WHERE `client_id` = ?', $this->id);
			$gdata = $app->db->queryOneRecord('SELECT `groupid` FROM `sys_group` WHERE `client_id` = ?', $this->id);
			$sys_groupid = $gdata['groupid'];
			$sys_userid = $udata['userid'];

			$entries = array();
			if($this->dataRecord['locked'] == 'y') {
				$prev_active = array();
				$prev_sysuser = array();
				foreach($to_disable as $current => $keycolumn) {
					$active_col = 'active';
					$reverse = false;
					if($current == 'mail_user') {
						$active_col = 'postfix';
					} elseif($current == 'mail_user_smtp') {
						$current = 'mail_user';
						$active_col = 'disablesmtp';
						$reverse = true;
					}
					
					if(!isset($prev_active[$current])) $prev_active[$current] = array();
					if(!isset($prev_sysuser[$current])) $prev_sysuser[$current] = array();

					$entries = $app->db->queryAllRecords('SELECT ?? as `id`, `sys_userid`, ?? FROM ?? WHERE `sys_groupid` = ?', $keycolumn, $active_col, $current, $sys_groupid);
					foreach($entries as $item) {

						if($item[$active_col] != 'y' && $reverse == false) $prev_active[$current][$item['id']][$active_col] = 'n';
						elseif($item[$active_col] == 'y' && $reverse == true) $prev_active[$current][$item['id']][$active_col] = 'y';
						if($item['sys_userid'] != $sys_userid) $prev_sysuser[$current][$item['id']] = $item['sys_userid'];
						// we don't have to store these if y, as everything without previous state gets enabled later

						$app->db->datalogUpdate($current, array($active_col => ($reverse == true ? 'y' : 'n'), 'sys_userid' => $_SESSION["s"]["user"]["userid"]), $keycolumn, $item['id']);
					}
				}

				$tmp_data['prev_active'] = $prev_active;
				$tmp_data['prev_sys_userid'] = $prev_sysuser;
				$app->db->query("UPDATE `client` SET `tmp_data` = ? WHERE `client_id` = ?", serialize($tmp_data), $this->id);
				unset($prev_active);
				unset($prev_sysuser);
			} elseif($this->dataRecord['locked'] == 'n') {
				foreach($to_disable as $current => $keycolumn) {
					$active_col = 'active';
					$reverse = false;
					if($current == 'mail_user') {
						$active_col = 'postfix';
					} elseif($current == 'mail_user_smtp') {
						$current = 'mail_user';
						$active_col = 'disablesmtp';
						$reverse = true;
					}
					
					$entries = $app->db->queryAllRecords('SELECT ?? as `id` FROM ?? WHERE `sys_groupid` = ?', $keycolumn, $current, $sys_groupid);
					foreach($entries as $item) {
						$set_active = ($reverse == true ? 'n' : 'y');
						$set_inactive = ($reverse == true ? 'y' : 'n');
						$set_sysuser = $sys_userid;
						if(array_key_exists('prev_active', $tmp_data) == true
							&& array_key_exists($current, $tmp_data['prev_active']) == true
							&& array_key_exists($item['id'], $tmp_data['prev_active'][$current]) == true
							&& $tmp_data['prev_active'][$current][$item['id']][$active_col] == $set_inactive) $set_active = $set_inactive;
						if(array_key_exists('prev_sysuser', $tmp_data) == true
							&& array_key_exists($current, $tmp_data['prev_sysuser']) == true
							&& array_key_exists($item['id'], $tmp_data['prev_sysuser'][$current]) == true
							&& $tmp_data['prev_sysuser'][$current][$item['id']] != $sys_userid) $set_sysuser = $tmp_data['prev_sysuser'][$current][$item['id']];

						$app->db->datalogUpdate($current, array($active_col => $set_active, 'sys_userid' => $set_sysuser), $keycolumn, $item['id']);
					}
				}
				if(array_key_exists('prev_active', $tmp_data)) unset($tmp_data['prev_active']);
				$app->db->query("UPDATE `client` SET `tmp_data` = ? WHERE `client_id` = ?", serialize($tmp_data), $this->id);
			}
			unset($tmp_data);
			unset($entries);
			unset($to_disable);
		}

		if(!isset($this->dataRecord['canceled'])) $this->dataRecord['canceled'] = 'n';
		if(isset($conf['demo_mode']) && $conf['demo_mode'] != true && $this->dataRecord["canceled"] != $this->oldDataRecord['canceled']) {
			if($this->dataRecord['canceled'] == 'y') {
				$sql = "UPDATE sys_user SET active = '0' WHERE client_id = ?";
				$app->db->query($sql, $this->id);
			} elseif($this->dataRecord['canceled'] == 'n') {
				$sql = "UPDATE sys_user SET active = '1' WHERE client_id = ?";
				$app->db->query($sql, $this->id);
			}
		}

		// language changed
		if(isset($conf['demo_mode']) && $conf['demo_mode'] != true && isset($this->dataRecord['language']) && $this->dataRecord['language'] != '' && $this->oldDataRecord['language'] != $this->dataRecord['language']) {
			$language = $this->dataRecord["language"];
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET language = ? WHERE client_id = ?";
			$app->db->query($sql, $language, $client_id);
		}

		//* reseller status changed
		if(isset($this->dataRecord["limit_client"]) && $this->dataRecord["limit_client"] != $this->oldDataRecord["limit_client"]) {
			$modules = $conf['interface_modules_enabled'];
			if($this->dataRecord["limit_client"] > 0) $modules .= ',client';
			$client_id = $this->id;
			$sql = "UPDATE sys_user SET modules = ? WHERE client_id = ?";
			$app->db->query($sql, $modules, $client_id);
		}
		
		//* Client has been moved to another reseller
		if($_SESSION['s']['user']['typ'] == 'admin' && isset($this->dataRecord['parent_client_id']) && $this->dataRecord['parent_client_id'] != $this->oldDataRecord['parent_client_id']) {
			//* Get groupid of the client
			$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $this->id);
			$groupid = $tmp['groupid'];
			unset($tmp);
			
			//* Remove sys_user of old reseller from client group
			if($this->oldDataRecord['parent_client_id'] > 0) {
				//* get userid of the old reseller remove it from the group of the client
				$tmp = $app->db->queryOneRecord("SELECT sys_user.userid FROM sys_user,sys_group WHERE sys_user.default_group = sys_group.groupid AND sys_group.client_id = ?", $this->oldDataRecord['parent_client_id']);
				$app->auth->remove_group_from_user($tmp['userid'], $groupid);
				unset($tmp);
			}
			
			//* Add sys_user of new reseller to client group
			if($this->dataRecord['parent_client_id'] > 0) {
				//* get userid of the reseller and add it to the group of the client
				$tmp = $app->db->queryOneRecord("SELECT sys_user.userid, sys_user.default_group FROM sys_user,sys_group WHERE sys_user.default_group = sys_group.groupid AND sys_group.client_id = ?", $this->dataRecord['parent_client_id']);
				$app->auth->add_group_to_user($tmp['userid'], $groupid);
				$app->db->query("UPDATE client SET sys_userid = ?, sys_groupid = ?, parent_client_id = ? WHERE client_id = ?", $tmp['userid'], $tmp['default_group'], $this->dataRecord['parent_client_id'], $this->id);
				unset($tmp);
			} else {
				//* Client is not assigned to a reseller anymore, so we assign it to the admin
				$app->db->query("UPDATE client SET sys_userid = 1, sys_groupid = 1, parent_client_id = 0 WHERE client_id = ?", $this->id);
			}
		}

		if(isset($this->dataRecord['template_master'])) {
			$app->uses('client_templates');
			$app->client_templates->update_client_templates($this->id, $this->_template_additional);
		}

		parent::onAfterUpdate();
	}

}

$page = new page_action;
$page->onLoad();

?>
