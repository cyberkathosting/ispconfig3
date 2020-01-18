<?php

/*
Copyright (c) 2007 - 2013, Till Brehm, projektfarm Gmbh
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

--UPDATED 08.2009--
Full SOAP support for ISPConfig 3.1.4 b
Updated by Arkadiusz Roch & Artur Edelman
Copyright (c) Tri-Plex technology

--UPDATED 08.2013--
Migrated into new remote classes system
by Marius Cramer <m.cramer@pixcept.de>

*/

class remoting_client extends remoting {
	/*
 *
 *
 *
 * 	 * Client functions
 *
 *
 */
	//* Get client details
	public function client_get($session_id, $client_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'client_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../client/form/client.tform.php');
		$data = $app->remoting_lib->getDataRecord($client_id);

		// we need to get the new-style templates for backwards-compatibility - maybe we remove this in a later version
		if(is_array($data) && count($data) > 0) {
			if(isset($data['client_id'])) {
				// this is a single record
				if($data['template_additional'] == '') {
					$tpls = $app->db->queryAllRecords('SELECT CONCAT(`assigned_template_id`, \':\', `client_template_id`) as `item` FROM `client_template_assigned` WHERE `client_id` = ?', $data['client_id']);
					$tpl_arr = array();
					if($tpls) {
						foreach($tpls as $tpl) $tpl_arr[] = $tpl['item'];
					}
					$data['template_additional'] = implode('/', $tpl_arr);
					unset($tpl_arr);
					unset($tpls);
				}
			} elseif(isset($data[0]['client_id'])) {
				// multiple client records
				foreach($data as $index => $client) {
					if($client['template_additional'] == '') {
						$tpls = $app->db->queryAllRecords('SELECT CONCAT(`assigned_template_id`, \':\', `client_template_id`) as `item` FROM `client_template_assigned` WHERE `client_id` = ?', $client['client_id']);
						$tpl_arr = array();
						if($tpls) {
							foreach($tpls as $tpl) $tpl_arr[] = $tpl['item'];
						}
						$data[$index]['template_additional'] = implode('/', $tpl_arr); // dont use the $client array here - changes would not be returned to soap
					}
					unset($tpl_arr);
					unset($tpls);
				}
			}
		}

		return $data;
	}

	public function client_get_id($session_id, $sys_userid)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'client_get_id')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$sys_userid = $app->functions->intval($sys_userid);

		$rec = $app->db->queryOneRecord("SELECT client_id FROM sys_user WHERE userid = ?", $sys_userid);
		if(isset($rec['client_id'])) {
			return $app->functions->intval($rec['client_id']);
		} else {
			throw new SoapFault('no_client_found', 'There is no sysuser account for this client ID.');
			return false;
		}

	}
	
	//* Get the contact details to send a email like email address, name, etc.
	public function client_get_emailcontact($session_id, $client_id) {
		global $app;
		
		if(!$this->checkPerm($session_id, 'client_get_emailcontact')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		$client_id = $app->functions->intval($client_id);

		$rec = $app->db->queryOneRecord("SELECT company_name,contact_name,gender,email,language FROM client WHERE client_id = ?", $client_id);
		
		if(is_array($rec)) {
			return $rec;
		} else {
			throw new SoapFault('no_client_found', 'There is no client with this client ID.');
			return false;
		}
	}

	public function client_get_groupid($session_id, $client_id)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'client_get_id')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$client_id = $app->functions->intval($client_id);

		$rec = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client_id);
		if(isset($rec['groupid'])) {
			return $app->functions->intval($rec['groupid']);
		} else {
			throw new SoapFault('no_group_found', 'There is no group for this client ID.');
			return false;
		}

	}


	public function client_add($session_id, $reseller_id, $params)
	{
		global $app;
		
		if (!$this->checkPerm($session_id, 'client_add'))
		{
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if(!isset($params['parent_client_id']) || $params['parent_client_id'] == 0) $params['parent_client_id'] = $reseller_id;

		if($params['parent_client_id']) {
			// check if this one is reseller
			$check = $app->db->queryOneRecord('SELECT `limit_client` FROM `client` WHERE `client_id` = ?', intval($params['parent_client_id']));
			if($check['limit_client'] == 0) {
				// Selected client is not a reseller. REMOVING PARENT_CLIENT_ID!!!
				$params['parent_client_id'] = 0;
			} elseif(isset($params['limit_client']) && $params['limit_client'] != 0) {
				throw new SoapFault('Invalid reseller', 'Reseller cannot be client of another reseller.');
				return false;
			}
		}

		$affected_rows = $this->klientadd('../client/form/' . (isset($params['limit_client']) && $params['limit_client'] != 0 ? 'reseller' : 'client') . '.tform.php', $reseller_id, $params);

		return $affected_rows;

	}

	public function client_update($session_id, $client_id, $reseller_id, $params)
	{
		global $app;

		if (!$this->checkPerm($session_id, 'client_update'))
		{
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../client/form/' . (isset($params['limit_client']) && $params['limit_client'] != 0 ? 'reseller' : 'client') . '.tform.php');
		$old_rec = $app->remoting_lib->getDataRecord($client_id);
		
		//* merge old record with params, so only new values have to be set in $params
		$params = $app->functions->array_merge($old_rec,$params);

		if(!isset($params['parent_client_id']) || $params['parent_client_id'] == 0) $params['parent_client_id'] = $reseller_id;

		if($params['parent_client_id']) {
			// check if this one is reseller
			$check = $app->db->queryOneRecord('SELECT `limit_client` FROM `client` WHERE `client_id` = ?', intval($params['parent_client_id']));
			if($check['limit_client'] == 0) {
				throw new SoapFault('Invalid reseller', 'Selected client is not a reseller.');
				return false;
			}

			if(isset($params['limit_client']) && $params['limit_client'] != 0) {
				throw new SoapFault('Invalid reseller', 'Reseller cannot be client of another reseller.');
				return false;
			}
		}

		// we need the previuos templates assigned here
		$this->oldTemplatesAssigned = $app->db->queryAllRecords('SELECT * FROM `client_template_assigned` WHERE `client_id` = ?', $client_id);
		if(!is_array($this->oldTemplatesAssigned) || count($this->oldTemplatesAssigned) < 1) {
			// check previous type of storing templates
			$tpls = explode('/', $old_rec['template_additional']);
			$this->oldTemplatesAssigned = array();
			foreach($tpls as $item) {
				$item = trim($item);
				if(!$item) continue;
				$this->oldTemplatesAssigned[] = array('assigned_template_id' => 0, 'client_template_id' => $item, 'client_id' => $client_id);
			}
			unset($tpls);
		}
		if(isset($params['template_additional'])) {
			$app->uses('client_templates');
			$templates = explode('/', $params['template_additional']);
			$params['template_additional'] = '';
			$app->client_templates->update_client_templates($client_id, $templates);
			unset($templates);
		}


		$affected_rows = $this->updateQuery('../client/form/' . (isset($params['limit_client']) && $params['limit_client'] != 0 ? 'reseller' : 'client') . '.tform.php', $reseller_id, $client_id, $params, 'client:' . ($reseller_id ? 'reseller' : 'client') . ':on_after_update');

		$app->remoting_lib->ispconfig_sysuser_update($params, $client_id);

		return $affected_rows;
	}

	public function client_template_additional_get($session_id, $client_id) {
		global $app;

		if(!$this->checkPerm($session_id, 'client_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		if(@is_numeric($client_id)) {
			$sql = "SELECT * FROM `client_template_assigned` WHERE `client_id` = ?";
			return $app->db->queryAllRecords($sql, $client_id);
		} else {
			throw new SoapFault('The ID must be an integer.');
			return array();
		}
	}

	private function _set_client_formdata($client_id) {
		global $app;

		$this->id = $client_id;
		$this->dataRecord = $app->db->queryOneRecord('SELECT * FROM `client` WHERE `client_id` = ?', $client_id);
		$this->oldDataRecord = $this->dataRecord;

		$this->oldTemplatesAssigned = $app->db->queryAllRecords('SELECT * FROM `client_template_assigned` WHERE `client_id` = ?', $client_id);
		if(!is_array($this->oldTemplatesAssigned) || count($this->oldTemplatesAssigned) < 1) {
			// check previous type of storing templates
			$tpls = explode('/', $this->oldDataRecord['template_additional']);
			$this->oldTemplatesAssigned = array();
			foreach($tpls as $item) {
				$item = trim($item);
				if(!$item) continue;
				$this->oldTemplatesAssigned[] = array('assigned_template_id' => 0, 'client_template_id' => $item, 'client_id' => $client_id);
			}
			unset($tpls);
		}
	}

	public function client_template_additional_add($session_id, $client_id, $template_id) {
		global $app;

		if(!$this->checkPerm($session_id, 'client_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		if(@is_numeric($client_id) && @is_numeric($template_id)) {
			// check if client exists
			$check = $app->db->queryOneRecord('SELECT `client_id` FROM `client` WHERE `client_id` = ?', $client_id);
			if(!$check) {
				throw new SoapFault('Invalid client');
				return false;
			}
			// check if template exists
			$check = $app->db->queryOneRecord('SELECT `template_id` FROM `client_template` WHERE `template_id` = ?', $template_id);
			if(!$check) {
				throw new SoapFault('Invalid template');
				return false;
			}

			// for the update event we have to cheat a bit
			$this->_set_client_formdata($client_id);

			$sql = "INSERT INTO `client_template_assigned` (`client_id`, `client_template_id`) VALUES (?, ?)";
			$app->db->query($sql, $client_id, $template_id);
			$insert_id = $app->db->insertID();

			$app->plugin->raiseEvent('client:client:on_after_update', $this);

			return $insert_id;
		} else {
			throw new SoapFault('The IDs must be of type integer.');
			return false;
		}
	}

	public function client_template_additional_delete($session_id, $client_id, $assigned_template_id) {
		global $app;

		if(!$this->checkPerm($session_id, 'client_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		if(@is_numeric($client_id) && @is_numeric($assigned_template_id)) {
			// check if client exists
			$check = $app->db->queryOneRecord('SELECT `client_id` FROM `client` WHERE `client_id` = ?', $client_id);
			if(!$check) {
				throw new SoapFault('Invalid client');
				return false;
			}
			// check if template exists
			$check = $app->db->queryOneRecord('SELECT `assigned_template_id` FROM `client_template_assigned` WHERE `client_id` = ? AND `client_template_id` = ?', $client_id, $assigned_template_id);
			if(!$check) {
				throw new SoapFault('Invalid template');
				return false;
			}

			// for the update event we have to cheat a bit
			$this->_set_client_formdata($client_id);

			$sql = "DELETE FROM `client_template_assigned` WHERE `assigned_template_id` = ? AND `client_id` = ?";
			$app->db->query($sql, $check['assigned_template_id'], $client_id);
			$affected_rows = $app->db->affectedRows();

			$app->plugin->raiseEvent('client:client:on_after_update', $this);

			return $affected_rows;
		} else {
			throw new SoapFault('The IDs must be of type integer.');
			return false;
		}
	}

	public function client_delete($session_id, $client_id)
	{
		global $app;

		if (!$this->checkPerm($session_id, 'client_delete'))
		{
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../client/form/client.tform.php', $client_id);

		$app->remoting_lib->ispconfig_sysuser_delete($client_id);

		return $affected_rows;
	}

	// -----------------------------------------------------------------------------------------------

	public function client_delete_everything($session_id, $client_id)
	{
		global $app, $conf;

		if(!$this->checkPerm($session_id, 'client_delete_everything')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$client_id = $app->functions->intval($client_id);

		if($client_id > 0) {
			//* remove the group of the client from the resellers group
			$parent_client_id = $app->functions->intval($this->dataRecord['parent_client_id']);
			$parent_user = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE client_id = ?", $parent_client_id);
			$client_group = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client_id);
			$app->auth->remove_group_from_user($parent_user['userid'], $client_group['groupid']);

			//* delete the group of the client
			$app->db->query("DELETE FROM sys_group WHERE client_id = ?", $client_id);

			//* delete the sys user(s) of the client
			$app->db->query("DELETE FROM sys_user WHERE client_id = ?", $client_id);

			//* Delete all records (sub-clients, mail, web, etc....)  of this client.
			$tables = 'cron,dns_rr,dns_soa,dns_slave,ftp_user,mail_access,mail_content_filter,mail_domain,mail_forwarding,mail_get,mail_user,mail_user_filter,shell_user,spamfilter_users,support_message,web_database,web_database_user,web_domain,web_traffic,domain,mail_mailinglist,client';
			$tables_array = explode(',', $tables);
			$client_group_id = $app->functions->intval($client_group['groupid']);
			if($client_group_id > 1) {
				foreach($tables_array as $table) {
					if($table != '') {
						$records = $app->db->queryAllRecords("SELECT * FROM $table WHERE sys_groupid = ?", $client_group_id);
						//* find the primary ID of the table
						$table_info = $app->db->tableInfo($table);
						$index_field = '';
						foreach($table_info as $tmp) {
							if($tmp['option'] == 'primary') $index_field = $tmp['name'];
						}
						//* Delete the records
						if($index_field != '') {
							if(is_array($records)) {
								foreach($records as $rec) {
									$app->db->datalogDelete($table, $index_field, $rec[$index_field]);
									//* Delete traffic records that dont have a sys_groupid column
									if($table == 'web_domain') {
										$app->db->query("DELETE FROM web_traffic WHERE hostname = ?", $rec['domain']);
									}
									//* Delete mail_traffic records that dont have a sys_groupid
									if($table == 'mail_user') {
										$app->db->query("DELETE FROM mail_traffic WHERE mailuser_id = ?", $rec['mailuser_id']);
									}
								}
							}
						}

					}
				}
			}

		}
		if (!$this->checkPerm($session_id, 'client_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../client/form/client.tform.php', $client_id);

		return $affected_rows;
	}

	/**
	 * Get sys_user information by username
	 * @param int  session id
	 * @param string user's name
	 * @return mixed false if error
	 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */


	public function client_get_by_username($session_id, $username) {
		global $app;
		if(!$this->checkPerm($session_id, 'client_get_by_username')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$rec = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE username = ?", $username);
		if (isset($rec)) {
			return $rec;
		} else {
			throw new SoapFault('no_client_found', 'There is no user account for this user name.');
			return false;
		}
	}
	
	public function client_get_by_customer_no($session_id, $customer_no) {
		global $app;
		if(!$this->checkPerm($session_id, 'client_get_by_customer_no')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$customer_no = trim($customer_no);
		if($customer_no == '') {
			throw new SoapFault('permission_denied', 'There was no customer number specified.');
			return false;
		}
		$customer_no = $app->db->quote($customer_no);
		$rec = $app->db->queryOneRecord("SELECT * FROM client WHERE customer_no = '".$customer_no."'");
		if (isset($rec)) {
			return $rec;
		} else {
			throw new SoapFault('no_client_found', 'There is no user account for this customer number.');
			return false;
		}
	}

	/**
	 * Get All client_id's from database
	 * @param int session_id
	 * @return Array of all client_id's
	 */
	public function client_get_all($session_id) {
		global $app;
		if(!$this->checkPerm($session_id, 'client_get_all')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$result = $app->db->queryAllRecords("SELECT client_id FROM client WHERE 1");
		if(!$result) {
			return false;
		}
		foreach( $result as $record) {
			$rarrary[] = $record['client_id'];
		}
		return $rarrary;
	}

	/**
	 * Changes client password
	 *
	 * @param int  session id
	 * @param int  client id
	 * @param string new password
	 * @return bool true if success
	 *
	 */
	public function client_change_password($session_id, $client_id, $new_password) {
		global $app;

		$app->uses('auth');

		if(!$this->checkPerm($session_id, 'client_change_password')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$client = $app->db->queryOneRecord("SELECT client_id FROM client WHERE client_id = ?", $client_id);
		if($client['client_id'] > 0) {
			$new_password = $app->auth->crypt_password($new_password);
			$sql = "UPDATE client SET password = ? 	WHERE client_id = ?";
			$app->db->query($sql, $new_password, $client_id);
			$sql = "UPDATE sys_user SET passwort = ? 	WHERE client_id = ?";
			$app->db->query($sql, $new_password, $client_id);
			return true;
		} else {
			throw new SoapFault('no_client_found', 'There is no user account for this client_id');
			return false;
		}
	}

	/**
	 *  Get all client templates
	 * @param  int  session id
	 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	public function client_templates_get_all($session_id) {
		global $app;
		if(!$this->checkPerm($session_id, 'client_templates_get_all')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$sql    = "SELECT * FROM client_template";
		$result = $app->db->queryAllRecords($sql);
		return $result;
	}
	
	public function client_login_get($session_id,$username,$password,$remote_ip = '') {
		global $app;
		
		//* Check permissions
		if(!$this->checkPerm($session_id, 'client_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		//* Check username and password
		if(!preg_match("/^[\w\.\-\_\@]{1,128}$/", $username)) {
			throw new SoapFault('user_regex_error', 'Username contains invalid characters.');
			return false;
		}
		if(!preg_match("/^.{1,64}$/i", $password)) {
			throw new SoapFault('password_length_error', 'Invalid password length or no password provided.');
			return false;
		}
		
		//* Check failed logins
		$sql = "SELECT * FROM `attempts_login` WHERE `ip`= ? AND  `login_time` > (NOW() - INTERVAL 1 MINUTE) LIMIT 1";
		$alreadyfailed = $app->db->queryOneRecord($sql, $remote_ip);
		
		//* too many failedlogins
		if($alreadyfailed['times'] > 5) {
			throw new SoapFault('error_user_too_many_logins', 'Too many failed logins.');
			return false;
		}
		
		
		//*Set variables
		$returnval == false;
		
		if(strstr($username,'@')) {
			// Check against client table
			$sql = "SELECT * FROM client WHERE email = ?";
			$user = $app->db->queryOneRecord($sql, $username);

			if($user) {
				$saved_password = stripslashes($user['password']);

				if(preg_match('/^\$[156]\$/', $saved_password)) {
					//* The password is crypt encrypted
					if(crypt(stripslashes($password), $saved_password) !== $saved_password) {
						$user = false;
					}
				} else {

					//* The password is md5 encrypted
					if(md5($password) != $saved_password) {
						$user = false;
					}
				}
			}
			
			if(is_array($user)) {
				$returnval = array(	'username' 	=> 	$user['username'],
									'type'		=>	'user',
									'client_id'	=>	$user['client_id'],
									'language'	=>	$user['language'],
									'country'	=>	$user['country']);
			}
			
		} else {
			// Check against sys_user table
			$sql = "SELECT * FROM sys_user WHERE username = ?";
			$user = $app->db->queryOneRecord($sql, $username);

			if($user) {
				$saved_password = stripslashes($user['passwort']);

				if(preg_match('/^\$[156]\$/', $saved_password)) {
					//* The password is crypt-md5 encrypted
					if(crypt(stripslashes($password), $saved_password) != $saved_password) {
						$user = false;
					}
				} else {

					//* The password is md5 encrypted
					if(md5($password) != $saved_password) {
						$user = false;
					}
				}
			}
			
			if(is_array($user)) {
				$returnval = array(	'username' 	=> 	$user['username'],
									'type'		=>	$user['typ'],
									'client_id'	=>	$user['client_id'],
									'language'	=>	$user['language'],
									'country'	=>	'de');
			} else {
				throw new SoapFault('login_failed', 'Login failed.');
			}
		}
		
		//* Log failed login attempts
		if($user === false) {
			if(!$alreadyfailed['times'] ) {
				//* user login the first time wrong
				$sql = "INSERT INTO `attempts_login` (`ip`, `times`, `login_time`) VALUES (?, 1, NOW())";
				$app->db->query($sql, $remote_ip);
			} elseif($alreadyfailed['times'] >= 1) {
				//* update times wrong
				$sql = "UPDATE `attempts_login` SET `times`=`times`+1, `login_time`=NOW() WHERE `ip` = ? AND `login_time` > (NOW() - INTERVAL 1 MINUTE) ORDER BY `login_time` DESC LIMIT 1";
				$app->db->query($sql, $remote_ip);
			}
		}
		
		return $returnval;
	}
	
	public function client_get_by_groupid($session_id, $group_id)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'client_get_id')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$group_id = $app->functions->intval($group_id);

		$rec = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE groupid = ?", $group_id);
		if(isset($rec['client_id'])) {
			$client_id = $app->functions->intval($rec['client_id']);
			return $this->client_get($session_id, $client_id);
		} else {
			throw new SoapFault('no_group_found', 'There is no client for this group ID.');
			return false;
		}
	}

}

?>
