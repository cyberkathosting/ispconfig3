<?php

/*
Copyright (c) 2007 - 2011, Till Brehm, projektfarm Gmbh
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

class remoting {

	//* remote session timeout in seconds
	private $session_timeout = 600;

	public $oldDataRecord;
	public $dataRecord;
	public $id;

	private $_methods = array();

	/*
	These variables shall stay global.
	Please do not make them private variables.

	private $app;
    private $conf;
    */

	public function __construct($methods = array())
	{
		global $app;
		$app->uses('remoting_lib');

		$this->_methods = $methods;

		/*
        $this->app = $app;
        $this->conf = $conf;
		*/
	}

	//* remote login function
	public function login($username, $password, $client_login = false)
	{
		global $app, $conf;

		// Maintenance mode
		$app->uses('ini_parser,getconf');
		$server_config_array = $app->getconf->get_global_config('misc');
		if($server_config_array['maintenance_mode'] == 'y'){
			throw new SoapFault('maintenance_mode', 'This ISPConfig installation is currently under maintenance. We should be back shortly. Thank you for your patience.');
			return false;
		}

		if(empty($username)) {
			throw new SoapFault('login_username_empty', 'The login username is empty.');
			return false;
		}

		if(empty($password)) {
			throw new SoapFault('login_password_empty', 'The login password is empty.');
			return false;
		}

		//* Delete old remoting sessions
		$sql = "DELETE FROM remote_session WHERE tstamp < ".time();
		$app->db->query($sql);

		$username = $app->db->quote($username);
		$password = $app->db->quote($password);

		if($client_login == true) {
			$sql = "SELECT * FROM sys_user WHERE USERNAME = '$username'";
			$user = $app->db->queryOneRecord($sql);
			if($user) {
				$saved_password = stripslashes($user['passwort']);

				if(substr($saved_password, 0, 3) == '$1$') {
					//* The password is crypt-md5 encrypted
					$salt = '$1$'.substr($saved_password, 3, 8).'$';

					if(crypt(stripslashes($password), $salt) != $saved_password) {
						throw new SoapFault('client_login_failed', 'The login failed. Username or password wrong.');
						return false;
					}
				} else {
					//* The password is md5 encrypted
					if(md5($password) != $saved_password) {
						throw new SoapFault('client_login_failed', 'The login failed. Username or password wrong.');
						return false;
					}
				}
			} else {
				throw new SoapFault('client_login_failed', 'The login failed. Username or password wrong.');
				return false;
			}
			if($user['active'] != 1) {
				throw new SoapFault('client_login_failed', 'The login failed. User is blocked.');
				return false;
			}

			// now we need the client data
			$client = $app->db->queryOneRecord("SELECT client.can_use_api FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = " . $app->functions->intval($user['default_group']));
			if(!$client || $client['can_use_api'] != 'y') {
				throw new SoapFault('client_login_failed', 'The login failed. Client may not use api.');
				return false;
			}

			//* Create a remote user session
			//srand ((double)microtime()*1000000);
			$remote_session = md5(mt_rand().uniqid('ispco'));
			$remote_userid = $user['userid'];
			$remote_functions = '';
			$tstamp = time() + $this->session_timeout;
			$sql = 'INSERT INTO remote_session (remote_session,remote_userid,remote_functions,client_login,tstamp'
				.') VALUES ('
				." '$remote_session',$remote_userid,'$remote_functions',1,$tstamp)";
			$app->db->query($sql);
			return $remote_session;
		} else {
			$sql = "SELECT * FROM remote_user WHERE remote_username = '$username' and remote_password = md5('$password')";
			$remote_user = $app->db->queryOneRecord($sql);
			if($remote_user['remote_userid'] > 0) {
				//* Create a remote user session
				//srand ((double)microtime()*1000000);
				$remote_session = md5(mt_rand().uniqid('ispco'));
				$remote_userid = $remote_user['remote_userid'];
				$remote_functions = $remote_user['remote_functions'];
				$tstamp = time() + $this->session_timeout;
				$sql = 'INSERT INTO remote_session (remote_session,remote_userid,remote_functions,tstamp'
					.') VALUES ('
					." '$remote_session',$remote_userid,'$remote_functions',$tstamp)";
				$app->db->query($sql);
				return $remote_session;
			} else {
				throw new SoapFault('login_failed', 'The login failed. Username or password wrong.');
				return false;
			}
		}

	}

	//* remote logout function
	public function logout($session_id)
	{
		global $app;

		if(empty($session_id)) {
			throw new SoapFault('session_id_empty', 'The SessionID is empty.');
			return false;
		}

		$session_id = $app->db->quote($session_id);

		$sql = "DELETE FROM remote_session WHERE remote_session = '$session_id'";
		if($app->db->query($sql) != false) {
			return true;
		} else {
			return false;
		}
	}

	//** protected functions -----------------------------------------------------------------------------------

	protected function klientadd($formdef_file, $reseller_id, $params)
	{
		global $app;

		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);

		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile($reseller_id);

		//* Get the SQL query
		$sql = $app->remoting_lib->getSQL($params, 'INSERT', 0);

		//* Check if no system user with that username exists
		$username = $app->db->quote($params["username"]);
		$tmp = $app->db->queryOneRecord("SELECT count(userid) as number FROM sys_user WHERE username = '$username'");
		if($tmp['number'] > 0) $app->remoting_lib->errorMessage .= "Duplicate username<br />";

		//* Stop on error while preparing the sql query
		if($app->remoting_lib->errorMessage != '') {
			throw new SoapFault('data_processing_error', $app->remoting_lib->errorMessage);
			return false;
		}

		//* Execute the SQL query
		$app->db->query($sql);
		$insert_id = $app->db->insertID();


		//* Stop on error while executing the sql query
		if($app->remoting_lib->errorMessage != '') {
			throw new SoapFault('data_processing_error', $app->remoting_lib->errorMessage);
			return false;
		}

		$this->id = $insert_id;
		$this->dataRecord = $params;

		$app->plugin->raiseEvent('client:' . (isset($params['limit_client']) && $params['limit_client'] > 0 ? 'reseller' : 'client') . ':on_after_insert', $this);

		/*
		if($app->db->errorMessage != '') {
			throw new SoapFault('database_error', $app->db->errorMessage . ' '.$sql);
			return false;
		}
		*/

		/* copied from the client_edit php */
		exec('ssh-keygen -t rsa -C '.$username.'-rsa-key-'.time().' -f /tmp/id_rsa -N ""');
		$app->db->query("UPDATE client SET created_at = ".time().", id_rsa = '".$app->db->quote(@file_get_contents('/tmp/id_rsa'))."', ssh_rsa = '".$app->db->quote(@file_get_contents('/tmp/id_rsa.pub'))."' WHERE client_id = ".$this->id);
		exec('rm -f /tmp/id_rsa /tmp/id_rsa.pub');



		//$app->uses('tform');
		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$new_rec = $app->remoting_lib->getDataRecord($insert_id);
			$app->remoting_lib->datalogSave('INSERT', $primary_id, array(), $new_rec);
			$app->remoting_lib->ispconfig_sysuser_add($params, $insert_id);

			if($reseller_id) {
				$client_group = $app->db->queryOneRecord("SELECT * FROM sys_group WHERE client_id = ".$insert_id);
				$reseller_user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE client_id = ".$reseller_id);
				$app->auth->add_group_to_user($reseller_user['userid'], $client_group['groupid']);
				$app->db->query("UPDATE client SET parent_client_id = ".$reseller_id." WHERE client_id = ".$insert_id);
			}

		}
		return $insert_id;
	}

	protected function insertQuery($formdef_file, $client_id, $params, $event_identifier = '')
	{
		$sql = $this->insertQueryPrepare($formdef_file, $client_id, $params);
		if($sql !== false) return $this->insertQueryExecute($sql, $params, $event_identifier);
		else return false;
	}

	protected function insertQueryPrepare($formdef_file, $client_id, $params)
	{
		global $app;

		$app->uses('remoting_lib');

		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile($client_id);

		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);

		//* Get the SQL query
		$sql = $app->remoting_lib->getSQL($params, 'INSERT', 0);
		if($app->remoting_lib->errorMessage != '') {
			throw new SoapFault('data_processing_error', $app->remoting_lib->errorMessage);
			return false;
		}
		$app->log('Executed insertQueryPrepare', LOGLEVEL_DEBUG);
		return $sql;
	}

	protected function insertQueryExecute($sql, $params, $event_identifier = '')
	{
		global $app;

		$app->uses('remoting_lib');

		$app->db->query($sql);

		if($app->db->errorMessage != '') {
			throw new SoapFault('database_error', $app->db->errorMessage . ' '.$sql);
			return false;
		}

		$insert_id = $app->db->insertID();

		// set a few values for compatibility with tform actions, mostly used by plugins
		$this->id = $insert_id;
		$this->dataRecord = $params;
		$app->log('Executed insertQueryExecute, raising events now if any: ' . $event_identifier, LOGLEVEL_DEBUG);
		if($event_identifier != '') $app->plugin->raiseEvent($event_identifier, $this);

		//$app->uses('tform');
		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$new_rec = $app->remoting_lib->getDataRecord($insert_id);
			$app->remoting_lib->datalogSave('INSERT', $primary_id, array(), $new_rec);
		}
		return $insert_id;
	}

	protected function updateQuery($formdef_file, $client_id, $primary_id, $params, $event_identifier = '')
	{
		global $app;

		$sql = $this->updateQueryPrepare($formdef_file, $client_id, $primary_id, $params);
		if($sql !== false) return $this->updateQueryExecute($sql, $primary_id, $params, $event_identifier);
		else return false;
	}

	protected function updateQueryPrepare($formdef_file, $client_id, $primary_id, $params)
	{
		global $app;

		$app->uses('remoting_lib');

		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile($client_id);

		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);
		
		//* get old record and merge with params, so only new values have to be set in $params
		$old_rec = $app->remoting_lib->getDataRecord($primary_id);
		$params = $app->functions->array_merge($old_rec,$params);

		//* Get the SQL query
		$sql = $app->remoting_lib->getSQL($params, 'UPDATE', $primary_id);
		// throw new SoapFault('debug', $sql);
		if($app->remoting_lib->errorMessage != '') {
			throw new SoapFault('data_processing_error', $app->remoting_lib->errorMessage);
			return false;
		}

		return $sql;
	}

	protected function updateQueryExecute($sql, $primary_id, $params, $event_identifier = '')
	{
		global $app;

		$app->uses('remoting_lib');

		$old_rec = $app->remoting_lib->getDataRecord($primary_id);

		// set a few values for compatibility with tform actions, mostly used by plugins
		$this->oldDataRecord = $old_rec;
		$this->id = $primary_id;
		$this->dataRecord = $params;

		$app->db->query($sql);

		if($app->db->errorMessage != '') {
			throw new SoapFault('database_error', $app->db->errorMessage . ' '.$sql);
			return false;
		}

		$affected_rows = $app->db->affectedRows();
		$app->log('Executed updateQueryExecute, raising events now if any: ' . $event_identifier, LOGLEVEL_DEBUG);

		if($event_identifier != '') $app->plugin->raiseEvent($event_identifier, $this);

		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$new_rec = $app->remoting_lib->getDataRecord($primary_id);
			$app->remoting_lib->datalogSave('UPDATE', $primary_id, $old_rec, $new_rec);
		}

		return $affected_rows;
	}

	protected function deleteQuery($formdef_file, $primary_id, $event_identifier = '')
	{
		global $app;

		$app->uses('remoting_lib');

		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile(0);

		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);

		$old_rec = $app->remoting_lib->getDataRecord($primary_id);

		// set a few values for compatibility with tform actions, mostly used by plugins
		$this->oldDataRecord = $old_rec;
		$this->id = $primary_id;
		$this->dataRecord = $old_rec;
		$app->log('Executed deleteQuery, raising events now if any: ' . $event_identifier, LOGLEVEL_DEBUG);
		//$this->dataRecord = $params;

		//* Get the SQL query
		$sql = $app->remoting_lib->getDeleteSQL($primary_id);
		$app->db->errorMessage = '';
		$app->db->query($sql);
		$affected_rows = $app->db->affectedRows();

		if($app->db->errorMessage != '') {
			throw new SoapFault('database_error', $app->db->errorMessage . ' '.$sql);
			return false;
		}

		if($event_identifier != '') {
			$app->plugin->raiseEvent($event_identifier, $this);
		}

		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$app->remoting_lib->datalogSave('DELETE', $primary_id, $old_rec, array());
		}


		return $affected_rows;
	}


	protected function checkPerm($session_id, $function_name)
	{
		global $app;
		$dobre=array();
		$session = $this->getSession($session_id);
		if(!$session){
			return false;
		}

		$_SESSION['client_login'] = $session['client_login'];
		if($session['client_login'] == 1) {
			// permissions are checked at an other place
			$_SESSION['client_sys_userid'] = $session['remote_userid'];
			$app->remoting_lib->loadUserProfile(); // load the profile - we ALWAYS need this on client logins!
			return true;
		} else {
			$_SESSION['client_sys_userid'] = 0;
		}

		$dobre= str_replace(';', ',', $session['remote_functions']);
		$check = in_array($function_name, explode(',', $dobre) );
		if(!$check) {
			$app->log("REMOTE-LIB DENY: ".$session_id ." /". $function_name, LOGLEVEL_WARN);
		}
		return $check;
	}


	protected function getSession($session_id)
	{
		global $app;

		if(empty($session_id)) {
			throw new SoapFault('session_id_empty', 'The SessionID is empty.');
			return false;
		}

		$session_id = $app->db->quote($session_id);

		$now = time();
		$sql = "SELECT * FROM remote_session WHERE remote_session = '$session_id' AND tstamp >= $now";
		$session = $app->db->queryOneRecord($sql);
		if($session['remote_userid'] > 0) {
			return $session;
		} else {
			throw new SoapFault('session_does_not_exist', 'The Session is expired or does not exist.');
			return false;
		}
	}

	public function server_get($session_id, $server_id = null, $section ='') {
		global $app;
		if(!$this->checkPerm($session_id, 'server_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if (!empty($session_id)) {
			if(!empty($server_id)) {
				$app->uses('remoting_lib , getconf');
				$section_config =  $app->getconf->get_server_config($server_id, $section);
				return $section_config;
			} else {
				$servers = array();
				$sql = "SELECT server_id FROM server WHERE 1";
				$all = $app->db->queryAllRecords($sql);
				foreach($all as $s) {
					$servers[$s['server_id']] = $app->getconf->get_server_config($s['server_id'], $section);
				}
				unset($all);
				unset($s);
				return $servers;
			}
		} else {
			return false;
		}
	}
	
	/**
	    Gets a list of all servers
	    @param int session_id
	    @param int server_name
	    @author Marius Cramer <m.cramer@pixcept.de> 2014
    */
	public function server_get_all($session_id)
    {
        global $app;
		if(!$this->checkPerm($session_id, 'server_get')) {
        	$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
		}
		if (!empty($session_id)) {
			$sql = "SELECT server_id, server_name FROM server WHERE 1";
			$servers = $app->db->queryAllRecords($sql);
			return $servers;
		} else {
			return false;
		}
	}

	/**
	 * Get a list of functions
	 * @param  int  session id
	 * @return mixed array of the available functions
	 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */


	public function get_function_list($session_id)
	{
		if(!$this->checkPerm($session_id, 'get_function_list')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->_methods;
	}

}

?>
