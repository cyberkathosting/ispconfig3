<?php

class remoting {
	
	//* remote session timeout in seconds
	private $session_timeout = 600;
	
    private $app;
    private $conf;
    private $server;

    public function __construct()
    {
        global $app, $conf, $server;
        $this->server = $server;
        $this->app = $app;
        $this->conf = $conf;
    }

	//* remote login function
	public function login($username, $password)
    {
		if(empty($username)) {
			$this->server->fault('login_username_empty', 'The login username is empty');
			return false;
		}
		
		if(empty($password)) {
			$this->server->fault('login_password_empty', 'The login password is empty');
			return false;
		}
		
		$username = $this->app->db->quote($username);
		$password = $this->app->db->quote($password);
		
		$sql = "SELECT * FROM remote_user WHERE remote_username = '$username' and remote_password = md5('$password')";
		$remote_user = $this->app->db->queryOneRecord($sql);
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
			$this->app->db->query($sql);
			return $remote_session;
		} else {
			$this->server->fault('login_failed', 'The login failed. Username or password wrong.');
			return false;
		}
		
	}
	
	
	//* remote logout function
	public function logout($session_id)
    {		
		if(empty($session_id)) {
			$this->server->fault('session_id_empty', 'The SessionID is empty.');
			return false;
		}
		
		$session_id = $this->app->db->quote($session_id);
		
		$sql = "DELETE FROM remote_session WHERE remote_session = '$session_id'";
		$this->app->db->query($sql);
        return ($this->app->db->affectedRows() == 1);
	}
	
	public function mail_domain_add($session_id, $params)
    {
		if(!$this->checkPerm($session_id, 'mail_domain_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		//* Form definition file, that is used for this table in the interafce
		$formdef = '../mail/form/mail_domain.tform.php';
		
		//* check the variables against the form definition and build the sql query automatically.
		// I will use a modified version of the tform class for this.
		
	}
	
	
	
	//** private functions -----------------------------------------------------------------------------------
	
	private function updateQuery($formdef, $params)
    {
	
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
		if(empty($session_id)) {
			$this->server->fault('session_id_empty','The SessionID is empty.');
			return false;
		}
		
		$session_id = $this->app->db->quote($session_id);
		
		$now = time();
		$sql = "SELECT * FROM remote_session WHERE remote_session = '$session_id' AND tstamp >= $now";
		$session = $this->app->db->queryOneRecord($sql);
		if($session['remote_userid'] > 0) {
			return $session;
		} else {
			$this->server->fault('session_does_not_exist','The Session is expired or does not exist.');
			return false;
		}
	}
}

?>