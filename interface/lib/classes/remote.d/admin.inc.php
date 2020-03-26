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

class remoting_admin extends remoting {
	
	/**
	 * set record permissions in any table
	 * @param string session_id
	 * @param string index_field
	 * @param string index_value
	 * @param array permissions
	 * @author "ispcomm", improved by M. Cramer <m.cramer@pixcept.de>
	 */
	public function update_record_permissions($session_id, $tablename, $index_field, $index_value, $permissions) {
		global $app;
		
		if(!$this->checkPerm($session_id, 'admin_record_permissions')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		foreach($permissions as $key => $value) {  // make sure only sys_ fields are updated
			switch($key) {
				case 'sys_userid':
					// check if userid is valid
					$check = $app->db->queryOneRecord('SELECT userid FROM sys_user WHERE userid = ?', $app->functions->intval($value));
					if(!$check || !$check['userid']) {
						throw new SoapFault('invalid parameters', $value . ' is no valid sys_userid.');
						return false;
					}
					$permissions[$key] = $app->functions->intval($value);
					break;
				case 'sys_groupid':
					// check if groupid is valid
					$check = $app->db->queryOneRecord('SELECT groupid FROM sys_group WHERE groupid = ?', $app->functions->intval($value));
					if(!$check || !$check['groupid']) {
						throw new SoapFault('invalid parameters', $value . ' is no valid sys_groupid.');
						return false;
					}
					$permissions[$key] = $app->functions->intval($value);
					break;
				case 'sys_perm_user':
				case 'sys_perm_group':
					// check if permissions are valid
					$value = strtolower($value);
					if(!preg_match('/^[riud]+$/', $value)) {
						throw new SoapFault('invalid parameters', $value . ' is no valid permission string.');
						return false;
					}
					
					$newvalue = '';
					if(strpos($value, 'r') !== false) $newvalue .= 'r';
					if(strpos($value, 'i') !== false) $newvalue .= 'i';
					if(strpos($value, 'u') !== false) $newvalue .= 'u';
					if(strpos($value, 'd') !== false) $newvalue .= 'd';
					$permissions[$key] = $newvalue;
					unset($newvalue);
					
					break;
				default:
					throw new SoapFault('invalid parameters', 'Only sys_userid, sys_groupid, sys_perm_user and sys_perm_group parameters can be changed with this function.');
					break;
			}
		}
		
		return $app->db->datalogUpdate( $tablename, $permissions, $index_field, $index_value ) ;
	}
	
	/**
	 Set a value in the system configuration
	 @param int session id
	 @param string  section of the config field in the table. Could be 'web', 'dns', 'mail', 'dns', 'cron', etc
	 @param string key of the option that you want to set
	 @param string option value that you want to set
	 */
	public function system_config_set($session_id, $section, $key, $value) {
		global $app;
		if(!$this->checkPerm($session_id, 'system_config_set')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if ($section != '' && $key != '') {
			$app->uses('remoting_lib,getconf,ini_parser');
			$system_config_array = $app->getconf->get_global_config();
			$system_config_array[$section][$key] = $value;
			$system_config_str = $app->ini_parser->get_ini_string($system_config_array);
			return $app->db->datalogUpdate('sys_ini', array("config" => $system_config_str), 'sysini_id', 1);
		} else {
			throw new SoapFault('invalid_function_parameter', 'Invalid function parameter.');
			return false;
		}
	}
	
	/**
	 Get the values of the system configuration
	 @param int session id
	 @param string section of the config field in the table. Could be 'web', 'dns', 'mail', 'dns', 'cron', etc
	 @param string|null key of the option that you want to get 
	 */
	public function system_config_get($session_id, $section, $key = null) {
		global $app;
		if(!$this->checkPerm($session_id, 'system_config_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if ($section != '') {
			$app->uses('remoting_lib,getconf,ini_parser');
			$system_config_array = $app->getconf->get_global_config();
			if($key != '') {
				if(isset($system_config_array[$section][$key])) return $system_config_array[$section][$key];
				else return false;
			} else {
				if(isset($system_config_array[$section])) return $system_config_array[$section];
				else return false;
			}
		} else {
			throw new SoapFault('invalid_function_parameter', 'Invalid function parameter.');
			return false;
		}
	}
	
	// config_value_* functions ---------------------------------------------------------------------------------------

	//* Get config_value details
	public function config_value_get($session_id, $group, $name)
	{
		global $app;
		
		if(!$this->checkPerm($session_id, 'config_value_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		// validate fields
		if($group == '' || $name == '') {
			throw new SoapFault('field_empty_error', 'Group and name parameter may not be empty.');
			return false;
		}
		
		return $app->db->queryOneRecord('SELECT * FROM sys_config WHERE `group` = ? AND `name` = ?', $group, $name);
	}

	//* Add a config_value record
	public function config_value_add($session_id, $group, $name, $value)
	{
		global $app;
		
		if(!$this->checkPerm($session_id, 'config_value_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		// validate fields
		if($group == '' || $name == '' || $value == '') {
			throw new SoapFault('field_empty_error', 'Group, name, and value parameter may not be empty.');
			return false;
		}
		
		if(is_array($app->db->queryOneRecord('SELECT * FROM sys_config WHERE `group` = ? AND `name` = ?', $group, $name))) {
			throw new SoapFault('record_unique_error', 'Group plus name field combination is not unique.');
			return false;
		}
		
		return $app->db->query('INSERT INTO sys_config (`group`,`name`,`value`) VALUES (?,?,?)',$group,$name,$value);
	}

	//* Update config_value record
	public function config_value_update($session_id, $group, $name, $value)
	{
		global $app;
		
		if(!$this->checkPerm($session_id, 'config_value_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		// validate fields
		if($group == '' || $name == '' || $value == '') {
			throw new SoapFault('field_empty_error', 'Group, name, and value parameter may not be empty.');
			return false;
		}
		
		if(!is_array($app->db->queryOneRecord('SELECT * FROM sys_config WHERE `group` = ? AND `name` = ?', $group, $name))) {
			throw new SoapFault('record_nonexist_error', 'There is no record with this group plus name field combination.');
			return false;
		}
		
		return $app->db->query('UPDATE sys_config SET `value` = ? WHERE `group` = ? AND `name` = ?',$value,$group,$name);
	}
	
	//* Replace config_value record
	public function config_value_replace($session_id, $group, $name, $value)
	{
		global $app;
		
		if(!$this->checkPerm($session_id, 'config_value_replace')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		// validate fields
		if($group == '' || $name == '' || $value == '') {
			throw new SoapFault('field_empty_error', 'Group, name, and value parameter may not be empty.');
			return false;
		}
		
		if(is_array($app->db->queryOneRecord('SELECT * FROM sys_config WHERE `group` = ? AND `name` = ?', $group, $name))) {
			return $app->db->query('UPDATE sys_config SET `value` = ? WHERE `group` = ? AND `name` = ?',$value,$group,$name);
		} else {
			return $app->db->query('INSERT INTO sys_config (`group`,`name`,`value`) VALUES (?,?,?)',$group,$name,$value);
		}
	}

	//* Delete config_value record
	public function config_value_delete($session_id, $group, $name)
	{
		global $app;
		
		if(!$this->checkPerm($session_id, 'config_value_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		// validate fields
		if($group == '' || $name == '') {
			throw new SoapFault('field_empty_error', 'Group and name parameter may not be empty.');
			return false;
		}
		
		if(!is_array($app->db->queryOneRecord('SELECT * FROM sys_config WHERE `group` = ? AND `name` = ?', $group, $name))) {
			throw new SoapFault('record_nonexist_error', 'There is no record with this group plus name field combination.');
			return false;
		}
		
		return $app->db->query('DELETE FROM sys_config WHERE `group` = ? AND `name` = ?',$group,$name);
	}
	
	// Get datalog information with tstamp >=
	public function sys_datalog_get_by_tstamp($session_id, $tstamp)	
	{
		global $app;

		if(!$this->checkPerm($session_id, 'server_get')) {
	        	throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
        		return false;
		}
		
		$tstamp = $app->functions->intval($tstamp);

		if($tstamp > 0)	{
			$rec = $app->db->queryAllRecords("SELECT datalog_id, server_id, dbtable, dbidx, action, tstamp, user, data, status, error FROM sys_datalog WHERE tstamp >= ? ORDER BY datalog_id DESC", $tstamp);
			return $rec;
		}
	}
	
	// Get datalog information by datalog_id
	public function sys_datalog_get($session_id, $datalog_id, $newer = false)	
	{
		global $app;

		if(!$this->checkPerm($session_id, 'server_get')) {
	        	throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
        		return false;
		}
		
		$tstamp = $app->functions->intval($tstamp);

		if($datalog_id > 0 && $newer === true)	{
			$rec = $app->db->queryAllRecords("SELECT datalog_id, server_id, dbtable, dbidx, action, tstamp, user, data, status, error FROM sys_datalog WHERE datalog_id >= ? ORDER BY datalog_id DESC", $datalog_id);
			return $rec;
		} elseif ($datalog_id > 0) {
			$rec = $app->db->queryAllRecords("SELECT datalog_id, server_id, dbtable, dbidx, action, tstamp, user, data, status, error FROM sys_datalog WHERE datalog_id = ? ORDER BY datalog_id DESC", $datalog_id);
			return $rec;
		} else {
			throw new SoapFault('invalid_datalog_id', 'The ID passed to the function must be > 0');
        	return false;
		}
	}


}

?>
