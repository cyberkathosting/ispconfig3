<?php

class remoting {
	
	//* remote session timeout in seconds
	private $session_timeout = 600;
	
	private $server;
	
	/*
	These variables shall stay global. 
	Please do not make them private variables.
    
	private $app;
    private $conf;
    */

    public function __construct()
    {
        global $server;
        $this->server = $server;
		/*
        $this->app = $app;
        $this->conf = $conf;
		*/
    }

	//* remote login function
	public function login($username, $password)
    {
		global $app, $conf, $server;
		
		if(empty($username)) {
			$this->server->fault('login_username_empty', 'The login username is empty');
			return false;
		}
		
		if(empty($password)) {
			$this->server->fault('login_password_empty', 'The login password is empty');
			return false;
		}
		
		$username = $app->db->quote($username);
		$password = $app->db->quote($password);
		
		$sql = "SELECT * FROM remote_user WHERE remote_username = '$username' and remote_password = md5('$password')";
		$remote_user = $app->db->queryOneRecord($sql);
		if($remote_user['remote_userid'] > 0) {
			//* Create a remote user session
			srand ((double)microtime()*1000000);
			$remote_session = md5(rand());
			$remote_userid = $remote_user['remote_userid'];
			$remote_functions = $remote_user['remote_functions'];
			$tstamp = time() + $this->session_timeout;
			$sql = 'INSERT INTO remote_session (remote_session,remote_userid,remote_functions,tstamp'
                   .') VALUES ('
                   ." '$remote_session',$remote_userid,'$remote_functions',$tstamp)";
			$app->db->query($sql);
			return $remote_session;
		} else {
			$this->server->fault('login_failed', 'The login failed. Username or password wrong.');
			return false;
		}
		
	}
	
	//* remote logout function
	public function logout($session_id)
    {		
		global $app;
		
		if(empty($session_id)) {
			$this->server->fault('session_id_empty', 'The SessionID is empty.');
			return false;
		}
		
		$session_id = $app->db->quote($session_id);
		
		$sql = "DELETE FROM remote_session WHERE remote_session = '$session_id'";
		$app->db->query($sql);
        return ($app->db->affectedRows() == 1);
	}
	
	//* Get mail domain details
	public function mail_domain_get($session_id, $domain_id)
    {
		if(!$this->checkPerm($session_id, 'mail_domain_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_domain.tform.php');
		return $app->remoting_lib->getDataRecord($domain_id);
	}
	
	//* Add a mail domain
	public function mail_domain_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'mail_domain_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$domain_id = $this->insertQuery('../mail/form/mail_domain.tform.php',$client_id,$params);
		return $domain_id;
	}
	
	//* Update a mail domain
	public function mail_domain_update($session_id, $client_id, $domain_id, $params)
    {
		if(!$this->checkPerm($session_id, 'mail_domain_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../mail/form/mail_domain.tform.php',$client_id,$domain_id,$params);
		return $affected_rows;
	}
	
	//* Delete a mail domain
	public function mail_domain_delete($session_id, $domain_id)
    {
		if(!$this->checkPerm($session_id, 'mail_domain_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../mail/form/mail_domain.tform.php',$domain_id);
		return $affected_rows;
	}
	
	
	
	//** private functions -----------------------------------------------------------------------------------
	
	
	private function insertQuery($formdef_file, $client_id, $params)
    {
		global $app;
		
		$app->uses('remoting_lib');
		
		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);
		
		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile($client_id);
		
		//* Get the SQL query
		$sql = $app->remoting_lib->getSQL($params,'INSERT',0);
		if($app->remoting_lib->errorMessage != '') {
			$this->server->fault('data_processing_error', $app->remoting_lib->errorMessage);
			return false;
		}
		
		$app->db->query($sql);
		
		if($app->db->errorMessage != '') {
			$this->server->fault('database_error', $app->db->errorMessage . ' '.$sql);
			return false;
		}
		
		$insert_id = $app->db->insertID();
		
		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$new_rec = $app->remoting_lib->getDataRecord($insert_id);
			$app->tform->datalogSave('INSERT',$primary_id,array(),$new_rec);
		}
		
		
		
		
		return $insert_id;
	}
	
	
	private function updateQuery($formdef_file, $client_id, $primary_id, $params)
    {
		global $app;
		
		$app->uses('remoting_lib');
		
		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);
		
		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile($client_id);
		
		//* Get the SQL query
		$sql = $app->remoting_lib->getSQL($params,'UPDATE',$primary_id);
		if($app->remoting_lib->errorMessage != '') {
			$this->server->fault('data_processing_error', $app->remoting_lib->errorMessage);
			return false;
		}
		
		$old_rec = $app->remoting_lib->getDataRecord($primary_id);
		
		$app->db->query($sql);
		
		if($app->db->errorMessage != '') {
			$this->server->fault('database_error', $app->db->errorMessage . ' '.$sql);
			return false;
		}
		
		$affected_rows = $app->db->affectedRows();
		
		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$new_rec = $app->remoting_lib->getDataRecord($primary_id);
			$app->tform->datalogSave('UPDATE',$primary_id,$old_rec,$new_rec);
		}
		
		
		
		return $affected_rows;
	}
	
	private function deleteQuery($formdef_file, $primary_id)
    {
		global $app;
		
		$app->uses('remoting_lib');
		
		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);
		
		//* Get the SQL query
		$sql = $app->remoting_lib->getDeleteSQL($primary_id);
		
		$app->db->query($sql);
		
		if($app->db->errorMessage != '') {
			$this->server->fault('database_error', $app->db->errorMessage . ' '.$sql);
			return false;
		}
		
		$affected_rows = $app->db->affectedRows();
		
		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$rec = $app->remoting_lib->getDataRecord($primary_id);
			$app->tform->datalogSave('DELETE',$primary_id,$rec,array());
		}
		
		
		return $affected_rows;
	}
	
	
	private function checkPerm($session_id, $function_name)
    {
		$session = $this->getSession($session_id);
        if(!$session){
            return false;
        }
		return in_array($function_name, explode(',', $session['remote_functions']) );
	}
	
	
	private function getSession($session_id)
    {	
		global $app;
		
		if(empty($session_id)) {
			$this->server->fault('session_id_empty','The SessionID is empty.');
			return false;
		}
		
		$session_id = $app->db->quote($session_id);
		
		$now = time();
		$sql = "SELECT * FROM remote_session WHERE remote_session = '$session_id' AND tstamp >= $now";
		$session = $app->db->queryOneRecord($sql);
		if($session['remote_userid'] > 0) {
			return $session;
		} else {
			$this->server->fault('session_does_not_exist','The Session is expired or does not exist.');
			return false;
		}
	}
}

?>