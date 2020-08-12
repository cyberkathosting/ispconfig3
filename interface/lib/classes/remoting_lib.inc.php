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

--UPDATED 08.2009--
Full SOAP support for ISPConfig 3.1.4 b
Updated by Arkadiusz Roch & Artur Edelman
Copyright (c) Tri-Plex technology

*/

/**
 * Formularbehandlung
 *
 * Functions to validate, display and save form values
 *
 *        Database table field definitions
 *
 *        Datatypes:
 *        - INTEGER (Converts data to int automatically)
 *        - DOUBLE
 *        - CURRENCY (Formats digits in currency notation)
 *        - VARCHAR (No format check)
 *        - DATE (Date format, converts from and to UNIX timestamps automatically)
 *
 *        Formtype:
 *        - TEXT (Normal text field)
 *        - PASSWORD (password field, the content will not be displayed again to the user)
 *        - SELECT (Option fiield)
 *        - MULTIPLE (Allows selection of multiple values)
 *
 *        VALUE:
 *        - Value or array
 *
 *        SEPARATOR
 *        - separator char used for fileds with multiple values
 *
 *        Hint: The auto increment (ID) filed of the table has not be be definied separately.
 *
 */


global $app;
$app->load('tform_base');
class remoting_lib extends tform_base {


	// additional class variables
	var $sys_username;
	var $sys_userid;
	var $sys_default_group;
	var $sys_groups;
	var $client_id;
	var $dataRecord;


	//* Load the form definition from file. - special version for remoting
	// module parameter is only for compatibility with base class
	function loadFormDef($file, $module = '') {
		global $app, $conf;

		include $file;

		$this->formDef = $form;
		unset($this->formDef['tabs']);

		//* Copy all fields from all tabs into one form definition
		foreach($form['tabs'] as $tab) {
			foreach($tab['fields'] as $key => $value) {
				$this->formDef['fields'][$key] = $value;
			}
		}
		unset($form);

		$this->dateformat = 'Y-m-d'; //$app->lng('conf_format_dateshort');
		$this->datetimeformat = 'Y-m-d H:i:s'; //$app->lng('conf_format_datetime');

		return true;
	}

	//* Load the user profile
	function loadUserProfile($client_id_param = 0) {
		global $app, $conf;

		$client_login = false;
		if(isset($_SESSION['client_login']) && isset($_SESSION['client_sys_userid']) && $_SESSION['client_login'] == 1) {
			$client_sys_userid = $app->functions->intval($_SESSION['client_sys_userid']);

			$client = $app->db->queryOneRecord("SELECT client.client_id FROM sys_user, client WHERE sys_user.client_id = client.client_id and sys_user.userid = ?", $client_sys_userid);

			$this->client_id = $client['client_id'];
			$client_login = true;
		} else {
			$this->client_id = $app->functions->intval($client_id_param);
		}

		if($this->client_id == 0) {
			$this->sys_username         = 'admin';
			$this->sys_userid            = 1;
			$this->sys_default_group     = 1;
			$this->sys_groups            = 1;
			$_SESSION["s"]["user"]["typ"] = 'admin';
		} else {
			$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE client_id = ?", $this->client_id);
			$this->sys_username         = $user['username'];
			$this->sys_userid            = $user['userid'];
			$this->sys_default_group     = $user['default_group'];
			$this->sys_groups             = $user['groups'];
			// we have to force admin priveliges for the remoting API as some function calls might fail otherwise.
			if($client_login == false) $_SESSION["s"]["user"]["typ"] = 'admin';
		}

		$_SESSION["s"]["user"]["username"] = $this->sys_username;
		$_SESSION["s"]["user"]["userid"] = $this->sys_userid;
		$_SESSION["s"]["user"]["default_group"] = $this->sys_default_group;
		$_SESSION["s"]["user"]["groups"] = $this->sys_groups;
		$_SESSION["s"]["user"]["client_id"] = $this->client_id;

		return true;
	}


	/**
	 * Converts the data in the array to human readable format
	 * Datatype conversion e.g. to show the data in lists
	 * tab parameter is only there for compatibility with params of base class
	 *
	 * @param record
	 * @return record
	 */
	function decode($record, $tab = '') {
		return $this->_decode($record, '', true);
	}


	/**
	 * Get the key => value array of a form filled from a datasource definitiom
	 * dummy parameter is only there for compatibility with params of base class
	 *
	 * @param field = array with field definition
	 * @param record = Dataset as array
	 * @return key => value array for the value field of a form
	 */
	function getDatasourceData($field, $record, $dummy = '') {
		return $this->_getDatasourceData($field, $record, true);
	}


	/**
	 /**
	 * Rewrite the record data to be stored in the database
	 * and check values with regular expressions.
	 *
	 * @param record = Datensatz als Array
	 * @return record
	 */
	function encode($record, $tab = '', $dbencode = true) {
		$new_record = $this->_encode($record, '', $dbencode, true);
		if(isset($record['_ispconfig_pw_crypted'])) $new_record['_ispconfig_pw_crypted'] = $record['_ispconfig_pw_crypted']; // this one is not in form definitions!

		return $new_record;
	}


	/**
	 * Create SQL statement
	 * dummy parameter is only there for compatibility with params of base class
	 *
	 * @param record = Datensatz als Array
	 * @param action = INSERT oder UPDATE
	 * @param primary_id
	 * @return record
	 */
	function getSQL($record, $action = 'INSERT', $primary_id = 0, $sql_ext_where = '', $dummy = '') {

		global $app;
		
		// early usage. make sure _primary_id is sanitized if present.
		if ( isset($record['_primary_id']) && is_numeric($record['_primary_id'])) {
			$_primary_id = intval($record['_primary_id']);
			if ($_primary_id > 0)
				$this->primary_id_override = $_primary_id;
		}
		
		if(!is_array($this->formDef)) $app->error("Form definition not found.");
		$this->dataRecord = $record;

		return $this->_getSQL($record, '', $action, $primary_id, $sql_ext_where, true);
	}

	function getDeleteSQL($primary_id) {

		if(stristr($this->formDef['db_table'], '.')) {
			$escape = '';
		} else {
			$escape = '`';
		}

		$sql = "DELETE FROM ".$escape.$this->formDef['db_table'].$escape." WHERE ".$this->formDef['db_table_idx']." = ".$primary_id. " AND " . $this->getAuthSQL('d', $this->formDef['db_table']);
		return $sql;
	}

       function getDataRecord($primary_id, $client_id = 0) {
		global $app;
		$escape = '`';
               $this->loadUserProfile($client_id);
		if(@is_numeric($primary_id)) {
			if($primary_id > 0) {
				// Return a single record
				return parent::getDataRecord($primary_id);
			} elseif($primary_id == -1) {
				// Return a array with all records
				$sql = "SELECT * FROM ??";
				return $app->db->queryAllRecords($sql, $this->formDef['db_table']);
			} else {
				throw new SoapFault('invalid_id', 'The ID has to be > 0 or -1.');
				return array();
			}
		} elseif (@is_array($primary_id) || @is_object($primary_id)) {
			if(@is_object($primary_id)) $primary_id = get_object_vars($primary_id); // do not use cast (array)xxx because it returns private and protected properties!
			$sql_offset = 0;
			$sql_limit = 0;
			$sql_where = '';
			$params = array($this->formDef['db_table']);
			foreach($primary_id as $key => $val) {
				if($key == '#OFFSET#') $sql_offset = $app->functions->intval($val);
				elseif($key == '#LIMIT#') $sql_limit = $app->functions->intval($val);
				elseif(stristr($val, '%')) {
					$sql_where .= "?? like ? AND ";
				} else {
					$sql_where .= "?? = ? AND ";
				}
				$params[] = $key;
				$params[] = $val;
			}
			$sql_where = substr($sql_where, 0, -5);
			if($sql_where == '') $sql_where = '1';
			$sql = "SELECT * FROM ?? WHERE ".$sql_where. " AND " . $this->getAuthSQL('r', $this->formDef['db_table']);
			if($sql_offset >= 0 && $sql_limit > 0) $sql .= ' LIMIT ' . $sql_offset . ',' . $sql_limit;
			return $app->db->queryAllRecords($sql, true, $params);
		} else {
			$this->errorMessage = 'The ID must be either an integer or an array.';
			return array();
		}
	}

	function ispconfig_sysuser_add($params, $insert_id){
		global $conf, $app, $sql1;
		$username = $params["username"];
		$password = $params["password"];
		if(!isset($params['modules'])) {
			$modules = $conf['interface_modules_enabled'];
		} else {
			$modules = $params['modules'];
		}
		if(isset($params['limit_client']) && $params['limit_client'] > 0) {
			$modules .= ',client';
		}

		if(!isset($params['startmodule'])) {
			$startmodule = 'dashboard';
		} else {
			$startmodule = $params["startmodule"];
			if(!preg_match('/'.$startmodule.'/', $modules)) {
				$_modules = explode(',', $modules);
				$startmodule=$_modules[0];
			}
		}
		$usertheme = (isset($params["usertheme"]) && $params["usertheme"] != '')?$params["usertheme"]:'default';
		$type = 'user';
		$active = 1;
		$insert_id = $app->functions->intval($insert_id);
		$language = $params["language"];
		$groupid = $app->db->datalogInsert('sys_group', array("name" => $username, "description" => "", "client_id" => $insert_id), 'groupid');
		$groups = $groupid;
		if(!isset($params['_ispconfig_pw_crypted']) || $params['_ispconfig_pw_crypted'] != 1) $password = $app->auth->crypt_password(stripslashes($password));
		$sql1 = "INSERT INTO sys_user (username,passwort,modules,startmodule,app_theme,typ,active,language,groups,default_group,client_id)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$app->db->query($sql1, $username,$password,$modules,$startmodule,$usertheme,$type,$active,$language,$groups,$groupid,$insert_id);
	}

	function ispconfig_sysuser_update($params, $client_id){
		global $app;
		$username = $params["username"];
		$clear_password = $params["password"];
		$language = $params['language'];
		$modules = $params['modules'];
		$client_id = $app->functions->intval($client_id);

		if(!isset($params['_ispconfig_pw_crypted']) || $params['_ispconfig_pw_crypted'] != 1) $password = $app->auth->crypt_password(stripslashes($clear_password));
		else $password = $clear_password;
		$params = array($username);
		if ($clear_password) {
			$pwstring = ", passwort = ?";
			$params[] = $password;
		} else {
			$pwstring ="" ;
		}

		$langstring = '';
		if (!empty($language)) {
			$langstring = ', language = ?';
			$params[] = $language;
		}

		$modulesstring = '';
		if (!empty($modules)) {
			$modulesstring = ', modules = ?';
			$params[] = $modules;
		}

		$params[] = $client_id;
		$sql = "UPDATE sys_user set username = ? $pwstring $langstring $modulesstring WHERE client_id = ?";
		$app->db->query($sql, true, $params);
	}

	function ispconfig_sysuser_delete($client_id){
		global $app;
		$client_id = $app->functions->intval($client_id);
		$sql = "DELETE FROM sys_user WHERE client_id = ?";
		$app->db->query($sql, $client_id);
		$sql = "DELETE FROM sys_group WHERE client_id = ?";
		$app->db->query($sql, $client_id);
	}

}

?>
