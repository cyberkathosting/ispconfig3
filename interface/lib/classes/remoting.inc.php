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
		global $app;
		
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
		$affected_rows = $this->deleteQuery('../mail/form/mail_domain.tform.php',$domain_id);
		return $affected_rows;
	}
	
	//* Get mail user details
	public function mail_user_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_user_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_user.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	
	//* dodanie uzytkownika email
	public function mail_user_add($session_id,$domain_id, $client_id, $params){
		if (!$this->checkPerm($session_id, 'mail_user_add')){
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->insertQuery('../mail/form/mail_user.tform.php',$domain_id, $client_id, $params);
		return $affected_rows;
	}

	//* edycja uzytkownika email	
	public function mail_user_update($session_id, $client_id, $domain_id, $params)
	{
		if (!$this->checkPerm($session_id, 'mail_user_update'))
		{
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../mail/form/mail_user.tform.php', $client_id, $domain_id, $params);
		return $affected_rows;
	}

	
	//*usuniecie uzytkownika emial
	public function mail_user_delete($session_id,$domain_id)
	{
		if (!$this->checkPerm($session_id, 'mail_user_delete'))
		{
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../mail/form/mail_user.tform.php',$domain_id);
		return $affected_rows;
	}

	//* Get alias details
	public function mail_alias_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_alias_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_alias.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* aliasy email
	public function mail_alias_add($session_id,$domain_id, $client_id, $params)
	{
		if (!$this->checkPerm($session_id, 'mail_alias_add'))
		{
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->insertQuery('../mail/form/mail_alias.tform.php', $domain_id,  $client_id, $params);
		return $affected_rows;
	}


	public function mail_alias_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_alias_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_alias.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}

	public function mail_alias_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_alias_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_alias.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get mail forwarding details
	public function mail_forward_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_forward_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_forward.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
 	//* przekierowania email
	public function mail_forward_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_forward_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_forward.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_forward_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_forward_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_forward.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_forward_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_forward_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_forward.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get catchall details
	public function mail_catchall_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_catchall_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_domain_catchall.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* catchall e-mail
 	public function mail_catchall_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_catchall_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_domain_catchall.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}

	public function mail_catchall_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_catchall_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_domain_catchall.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}

	public function mail_catchall_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_catchall_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_domain_catchall.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get transport details
	public function mail_transport_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_transport_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_transport.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* przeniesienia e-mail
	public function mail_transport_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_transport_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_transport.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_transport_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_transport_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_transport.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_transport_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_transport_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_transport.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get spamfilter whitelist details
	public function mail_spamfilter_whitelist_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_spamfilter_whitelist_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/spamfilter_whitelist.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

 	//* biała lista e-mail
	public function mail_spamfilter_whitelist_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_whitelist_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/spamfilter_whitelist.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_whitelist_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_whitelist_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/spamfilter_whitelist.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_whitelist_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_whitelist_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/spamfilter_whitelist.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get spamfilter blacklist details
	public function mail_spamfilter_blacklist_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_spamfilter_blacklist_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/spamfilter_blacklist.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
 	//* czarna lista e-mail
	public function mail_spamfilter_blacklist_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_blacklist_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/spamfilter_blacklist.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_blacklist_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_blacklist_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/spamfilter_blacklist.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_blacklist_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_blacklist_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/spamfilter_blacklist.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get spamfilter user details
	public function mail_spamfilter_user_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_spamfilter_user_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/spamfilter_users.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* filtr spamu użytkowników e-mail
	public function mail_spamfilter_user_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_user_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/spamfilter_users.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_user_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_user_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/spamfilter_users.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_user_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_user_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/spamfilter_users.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get policy details
	public function mail_policy_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_policy_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/spamfilter_policy.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
 	//* polityki filtrów spamu e-mail
	public function mail_policy_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_policy_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/spamfilter_policy.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_policy_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_policy_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/spamfilter_policy.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_policy_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_policy_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/spamfilter_policy.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get fetchmail details
	public function mail_fetchmail_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_fetchmail_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_get.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	 //* fetchmail
	public function mail_fetchmail_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_fetchmail_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_get.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_fetchmail_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_fetchmail_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_get.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_fetchmail_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_fetchmail_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_get.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get whitelist details
	public function mail_whitelist_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_whitelist_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_whitelist.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* wpisy białej listy
	public function mail_whitelist_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_whitelist_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_whitelist.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_whitelist_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_whitelist_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_whitelist.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_whitelist_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_whitelist_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_whitelist.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get Blacklist details
	public function mail_blacklist_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_blacklist_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_blacklist.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* wpisy białej listy
	public function mail_blacklist_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_blacklist_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_blacklist.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_blacklist_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_blacklist_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_blacklist.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_blacklist_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_blacklist_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_blacklist.tform.php',$domain_id);
			return $affected_rows;
	}
	
	//* Get filter details
	public function mail_filter_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_filter_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_content_filter.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* wpisy filtrow e-mail
	public function mail_filter_add($session_id,$domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_filter_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_content_filter.tform.php', $domain_id,  $client_id, $params);
			return $affected_rows;
	}


	public function mail_filter_update($session_id, $domain_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_filter_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_content_filter.tform.php', $client_id, $domain_id, $params);
			return $affected_rows;
	}


	public function mail_filter_delete($session_id,$domain_id)
	{
			if (!$this->checkPerm($session_id, 'mail_filter_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_content_filter.tform.php',$domain_id);
			return $affected_rows;
	}




/* 
 * 
 * 
 * 
 * 	 * klient add :)
 * 
 * 
 */

	public function client_add($session_id,$domain_id, $client_id, $params)
	{
		if (!$this->checkPerm($session_id, 'client_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
		$affected_rows = $this->klientadd('../client/form/client.tform.php',$domain_id, $client_id, $params);
		return $affected_rows;  
				  
	}


        


	//** private functions -----------------------------------------------------------------------------------
	
	


	private function klientadd($formdef_file, $client_id, $params)
    {
		global $app, $tform, $remoting_lib;
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
		//$app->uses('tform');
		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$new_rec = $app->remoting_lib->getDataRecord($insert_id);
			$app->remoting_lib->datalogSave('INSERT',$primary_id,array(),$new_rec);
			
		$app->remoting_lib->dodaj_usera($params,$insert_id);

		}
		
		
		
		
		return $insert_id;
	}




	private function insertQuery($formdef_file, $client_id, $params)
    {
		global $app, $tform, $remoting_lib;
		
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
		
		
	
		//$app->uses('tform');
		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$new_rec = $app->remoting_lib->getDataRecord($insert_id);
			$app->remoting_lib->datalogSave('INSERT',$primary_id,array(),$new_rec);
			
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
			$app->remoting_lib->datalogSave('UPDATE',$primary_id,$old_rec,$new_rec);
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
			$app->remoting_lib->datalogSave('DELETE',$primary_id,$rec,array());
		}
		
		
		return $affected_rows;
	}
	
	
	private function checkPerm($session_id, $function_name)
    {
	$dobre=Array();
	$session = $this->getSession($session_id);
        if(!$session){
            return false;
        }
		
		$dobre= str_replace(';',',',$session['remote_functions']);
		return in_array($function_name, explode(',', $dobre) );
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
