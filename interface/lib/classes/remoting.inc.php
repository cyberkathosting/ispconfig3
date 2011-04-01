<?php

/*
Copyright (c) 2007 - 2009, Till Brehm, projektfarm Gmbh
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
	public $oldDataRecord;
	public $dataRecord;
	public $id;
	
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
		
		//* Delete old remoting sessions
		$sql = "DELETE FROM remote_session WHERE tstamp < ".time();
		$app->db->query($sql);
		
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
	

    /**
	    Gets the server configuration
	    @param int session id
	    @param int server id
	    @param string  section of the config field in the server table. Could be 'web', 'dns', 'mail', 'dns', 'cron', etc
	    @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
    */
    public function server_get($session_id, $server_id, $section ='') {
        global $app;        
        if(!$this->checkPerm($session_id, 'server_get')) {
            $this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
        }
        if (!empty($session_id) && !empty($server_id)) {    
            $app->uses('remoting_lib , getconf');        
            $section_config =  $app->getconf->get_server_config($server_id,$section);        
            return $section_config;
        } else {
            return false;
        }
    }
	
	//* Get mail domain details
	public function mail_domain_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_domain_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_domain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a mail domain
	public function mail_domain_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'mail_domain_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$primary_id = $this->insertQuery('../mail/form/mail_domain.tform.php',$client_id,$params);
		return $primary_id;
	}
	
	//* Update a mail domain
	public function mail_domain_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'mail_domain_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../mail/form/mail_domain.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}
	
	//* Delete a mail domain
	public function mail_domain_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'mail_domain_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../mail/form/mail_domain.tform.php', $primary_id);
		return $affected_rows;
	}
	
	//* Get mail mailinglist details
	public function mail_mailinglist_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_mailinglist_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_mailinglist.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a mail mailinglist
	public function mail_mailinglist_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'mail_mailinglist_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$primary_id = $this->insertQuery('../mail/form/mail_mailinglist.tform.php',$client_id,$params);
		return $primary_id;
	}
	
	//* Update a mail mailinglist
	public function mail_mailinglist_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'mail_mailinglist_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../mail/form/mail_mailinglist.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}
	
	//* Delete a mail mailinglist
	public function mail_mailinglist_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'mail_mailinglist_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../mail/form/mail_mailinglist.tform.php', $primary_id);
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
	public function mail_user_add($session_id, $client_id, $params){
		if (!$this->checkPerm($session_id, 'mail_user_add')){
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->insertQuery('../mail/form/mail_user.tform.php', $client_id, $params);
		return $affected_rows;
	}

	//* edycja uzytkownika email	
	public function mail_user_update($session_id, $client_id, $primary_id, $params)
	{
		if (!$this->checkPerm($session_id, 'mail_user_update'))
		{
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../mail/form/mail_user.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	
	//*usuniecie uzytkownika emial
	public function mail_user_delete($session_id, $primary_id)
	{
		if (!$this->checkPerm($session_id, 'mail_user_delete'))
		{
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../mail/form/mail_user.tform.php', $primary_id);
		return $affected_rows;
	}
	
	//* Get mail user filter details
	public function mail_user_filter_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'mail_user_filter_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../mail/form/mail_user_filter.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	public function mail_user_filter_add($session_id, $client_id, $params)
	{
		global $app;
		if (!$this->checkPerm($session_id, 'mail_user_filter_add')){
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->insertQuery('../mail/form/mail_user_filter.tform.php', $client_id, $params,'mail:mail_user_filter:on_after_insert');
		// $app->plugin->raiseEvent('mail:mail_user_filter:on_after_insert',$this);
		return $affected_rows;
	}

	public function mail_user_filter_update($session_id, $client_id, $primary_id, $params)
	{
		global $app;
		if (!$this->checkPerm($session_id, 'mail_user_filter_update'))
		{
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../mail/form/mail_user_filter.tform.php', $client_id, $primary_id, $params,'mail:mail_user_filter:on_after_update');
		// $app->plugin->raiseEvent('mail:mail_user_filter:on_after_update',$this);
		return $affected_rows;
	}

	public function mail_user_filter_delete($session_id, $primary_id)
	{
		global $app;
		if (!$this->checkPerm($session_id, 'mail_user_filter_delete'))
		{
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../mail/form/mail_user_filter.tform.php', $primary_id);
		$app->plugin->raiseEvent('mail:mail_user_filter:on_after_delete',$this);
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
	public function mail_alias_add($session_id, $client_id, $params)
	{
		if (!$this->checkPerm($session_id, 'mail_alias_add'))
		{
			$this->server->fault('permission_denied','You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->insertQuery('../mail/form/mail_alias.tform.php', $client_id, $params);
		return $affected_rows;
	}


	public function mail_alias_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_alias_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_alias.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}

	public function mail_alias_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_alias_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_alias.tform.php', $primary_id);
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
	public function mail_forward_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_forward_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_forward.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_forward_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_forward_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_forward.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_forward_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_forward_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_forward.tform.php', $primary_id);
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
 	public function mail_catchall_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_catchall_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_domain_catchall.tform.php', $client_id, $params);
			return $affected_rows;
	}

	public function mail_catchall_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_catchall_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_domain_catchall.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}

	public function mail_catchall_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_catchall_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_domain_catchall.tform.php', $primary_id);
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
	public function mail_transport_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_transport_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_transport.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_transport_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_transport_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_transport.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_transport_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_transport_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_transport.tform.php', $primary_id);
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
	public function mail_spamfilter_whitelist_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_whitelist_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/spamfilter_whitelist.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_whitelist_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_whitelist_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/spamfilter_whitelist.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_whitelist_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_whitelist_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/spamfilter_whitelist.tform.php', $primary_id);
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
	public function mail_spamfilter_blacklist_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_blacklist_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/spamfilter_blacklist.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_blacklist_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_blacklist_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/spamfilter_blacklist.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_blacklist_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_blacklist_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/spamfilter_blacklist.tform.php', $primary_id);
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
	public function mail_spamfilter_user_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_user_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/spamfilter_users.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_user_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_user_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/spamfilter_users.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_spamfilter_user_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_spamfilter_user_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/spamfilter_users.tform.php', $primary_id);
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
	public function mail_policy_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_policy_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/spamfilter_policy.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_policy_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_policy_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/spamfilter_policy.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_policy_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_policy_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/spamfilter_policy.tform.php', $primary_id);
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
	public function mail_fetchmail_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_fetchmail_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_get.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_fetchmail_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_fetchmail_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_get.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_fetchmail_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_fetchmail_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_get.tform.php', $primary_id);
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
	public function mail_whitelist_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_whitelist_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_whitelist.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_whitelist_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_whitelist_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_whitelist.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_whitelist_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_whitelist_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_whitelist.tform.php', $primary_id);
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
	public function mail_blacklist_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_blacklist_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_blacklist.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_blacklist_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_blacklist_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_blacklist.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_blacklist_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_blacklist_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_blacklist.tform.php', $primary_id);
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
	public function mail_filter_add($session_id, $client_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_filter_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->insertQuery('../mail/form/mail_content_filter.tform.php', $client_id, $params);
			return $affected_rows;
	}


	public function mail_filter_update($session_id, $client_id, $primary_id, $params)
	{
			if (!$this->checkPerm($session_id, 'mail_filter_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../mail/form/mail_content_filter.tform.php', $client_id, $primary_id, $params);
			return $affected_rows;
	}


	public function mail_filter_delete($session_id, $primary_id)
	{
			if (!$this->checkPerm($session_id, 'mail_filter_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../mail/form/mail_content_filter.tform.php', $primary_id);
			return $affected_rows;
	}




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
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../client/form/client.tform.php');
		return $app->remoting_lib->getDataRecord($client_id);
	}
	
	public function client_get_id($session_id, $sys_userid)
    {
		global $app;
		if(!$this->checkPerm($session_id, 'client_get_id')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		$sys_userid = intval($sys_userid);
		
		$rec = $app->db->queryOneRecord("SELECT client_id FROM sys_user WHERE userid = ".$sys_userid);
		if(isset($rec['client_id'])) {
			return intval($rec['client_id']);
		} else {
			$this->server->fault('no_client_found', 'There is no sysuser account for this client ID.');
			return false;
		}
		
	}
	
	
	public function client_add($session_id, $reseller_id, $params)
	{
		if (!$this->checkPerm($session_id, 'client_add'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
		$affected_rows = $this->klientadd('../client/form/client.tform.php',$reseller_id, $params);
		return $affected_rows;  
				  
	}
	
	public function client_update($session_id, $client_id, $reseller_id, $params)
	{
			global $app;
			
			if (!$this->checkPerm($session_id, 'client_update'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->updateQuery('../client/form/client.tform.php', $reseller_id, $client_id, $params);
			
			$app->remoting_lib->ispconfig_sysuser_update($params,$client_id);
			
			return $affected_rows;
	}


	public function client_delete($session_id,$client_id)
	{
			global $app;
			
			if (!$this->checkPerm($session_id, 'client_delete'))
			{
					$this->server->fault('permission_denied','You do not have the permissions to access this function.');
					return false;
			}
			$affected_rows = $this->deleteQuery('../client/form/client.tform.php',$client_id);
			
			$app->remoting_lib->ispconfig_sysuser_delete($client_id);
			
			return $affected_rows;
	}
	
	// Website functions ---------------------------------------------------------------------------------------
	
	//* Get cron details
	public function sites_cron_get($session_id, $cron_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'sites_cron_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/cron.tform.php');
		return $app->remoting_lib->getDataRecord($cron_id);
	}
	
	//* Add a cron record
	public function sites_cron_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_cron_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/cron.tform.php',$client_id,$params);
	}
	
	//* Update cron record
	public function sites_cron_update($session_id, $client_id, $cron_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_cron_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/cron.tform.php',$client_id,$cron_id,$params);
		return $affected_rows;
	}
	
	//* Delete cron record
	public function sites_cron_delete($session_id, $cron_id)
    {
		if(!$this->checkPerm($session_id, 'sites_cron_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/cron.tform.php',$cron_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function sites_database_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'sites_database_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/database.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function sites_database_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_database_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/database.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function sites_database_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_database_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/database.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function sites_database_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'sites_database_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/database.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function sites_ftp_user_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'sites_ftp_user_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/ftp_user.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function sites_ftp_user_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_ftp_user_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/ftp_user.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function sites_ftp_user_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_ftp_user_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/ftp_user.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function sites_ftp_user_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'sites_ftp_user_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/ftp_user.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function sites_shell_user_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'sites_shell_user_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/shell_user.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function sites_shell_user_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_shell_user_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/shell_user.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function sites_shell_user_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_shell_user_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/shell_user.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function sites_shell_user_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'sites_shell_user_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/shell_user.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function sites_web_domain_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'sites_web_domain_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_domain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function sites_web_domain_add($session_id, $client_id, $params, $readonly = false)
    {
		global $app;
		if(!$this->checkPerm($session_id, 'sites_web_domain_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows =  $this->insertQuery('../sites/form/web_domain.tform.php',$client_id,$params, 'sites:web_domain:on_after_insert');
		if ($readonly === true)
			$app->db->query("UPDATE web_domain SET `sys_userid` = '1' WHERE domain_id = ".$affected_rows);
		return $affected_rows;		
	}
	
	//* Update a record
	public function sites_web_domain_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_web_domain_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/web_domain.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function sites_web_domain_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'sites_web_domain_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/web_domain.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// -----------------------------------------------------------------------------------------------
	
	//* Get record details
	public function sites_web_aliasdomain_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_aliasdomain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function sites_web_aliasdomain_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/web_aliasdomain.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function sites_web_aliasdomain_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/web_aliasdomain.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function sites_web_aliasdomain_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/web_aliasdomain.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function sites_web_subdomain_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_subdomain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function sites_web_subdomain_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/web_subdomain.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function sites_web_subdomain_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/web_subdomain.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function sites_web_subdomain_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/web_subdomain.tform.php',$primary_id);
		return $affected_rows;
	}
	
	
	// DNS Function --------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_zone_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_zone_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_soa.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_zone_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_zone_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_soa.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_zone_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_zone_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_soa.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_zone_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_zone_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_soa.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_aaaa_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_aaaa_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_aaaa.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_aaaa_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_aaaa_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_aaaa.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_aaaa_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_aaaa_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_aaaa.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_aaaa_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_aaaa_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_aaaa.tform.php',$primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_a_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_a_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_a.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_a_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_a_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_a.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_a_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_a_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_a.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_a_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_a_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_a.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_alias_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_alias_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_alias.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_alias_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_alias_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_alias.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_alias_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_alias_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_alias.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_alias_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_alias_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_alias.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_cname_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_cname_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_cname.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_cname_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_cname_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_cname.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_cname_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_cname_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_cname.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_cname_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_cname_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_cname.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_hinfo_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_hinfo_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_hinfo.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_hinfo_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_hinfo_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_hinfo.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_hinfo_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_hinfo_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_hinfo.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_hinfo_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_hinfo_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_hinfo.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_mx_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_mx_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_mx.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_mx_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_mx_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_mx.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_mx_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_mx_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_mx.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_mx_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_mx_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_mx.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_ns_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_ns_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_ns.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_ns_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_ns_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_ns.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_ns_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_ns_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_ns.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_ns_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_ns_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_ns.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_ptr_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_ptr_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_ptr.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_ptr_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_ptr_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_ptr.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_ptr_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_ptr_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_ptr.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_ptr_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_ptr_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_ptr.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_rp_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_rp_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_rp.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_rp_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_rp_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_rp.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_rp_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_rp_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_rp.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_rp_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_rp_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_rp.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_srv_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_srv_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_srv.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_srv_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_srv_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_srv.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_srv_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_srv_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_srv.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_srv_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_srv_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_srv.tform.php',$primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_txt_get($session_id, $primary_id)
    {
		global $app;
		
		if(!$this->checkPerm($session_id, 'dns_txt_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_txt.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	public function dns_txt_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_txt_add')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_txt.tform.php',$client_id,$params);
	}
	
	//* Update a record
	public function dns_txt_update($session_id, $client_id, $primary_id, $params)
    {
		if(!$this->checkPerm($session_id, 'dns_txt_update')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_txt.tform.php',$client_id,$primary_id,$params);
		return $affected_rows;
	}
	
	//* Delete a record
	public function dns_txt_delete($session_id, $primary_id)
    {
		if(!$this->checkPerm($session_id, 'dns_txt_delete')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_txt.tform.php',$primary_id);
		return $affected_rows;
	}
	
	
	
	
	
	
	
	
	
	
        


	//** private functions -----------------------------------------------------------------------------------
	
	


	private function klientadd($formdef_file, $reseller_id, $params)
    {
		global $app, $tform, $remoting_lib;
		$app->uses('remoting_lib');
			
		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);
		
		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile($reseller_id);
		
		//* load the client template
		if(isset($params['template_master']) and $params['template_master'] > 0)
		{
			$template=$app->db->queryOneRecord("SELECT * FROM client_template WHERE template_id=".intval($params['template_master']));
			$params=array_merge($params,$template);
		}
		
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
			$app->remoting_lib->ispconfig_sysuser_add($params,$insert_id);

            if($reseller_id) {
                $client_group = $app->db->queryOneRecord("SELECT * FROM sys_group WHERE client_id = ".$insert_id);
                $reseller_user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE client_id = ".$reseller_id);
                $app->auth->add_group_to_user($reseller_user['userid'], $client_group['groupid']);
                $app->db->query("UPDATE client SET parent_client_id = ".$reseller_id." WHERE client_id = ".$insert_id);
            }   

		}
		return $insert_id;
	}

	private function insertQuery($formdef_file, $client_id, $params,$event_identifier = '')
    {
		global $app, $tform, $remoting_lib;
		
		$app->uses('remoting_lib');
		
		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile($client_id);
		
		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);
		
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
		
		// set a few values for compatibility with tform actions, mostly used by plugins
		$this->id = $insert_id;
		$this->dataRecord = $params;
		
		if($event_identifier != '') $app->plugin->raiseEvent($event_identifier,$this);
	
		//$app->uses('tform');
		//* Save changes to Datalog
		if($app->remoting_lib->formDef["db_history"] == 'yes') {
			$new_rec = $app->remoting_lib->getDataRecord($insert_id);
			$app->remoting_lib->datalogSave('INSERT',$primary_id,array(),$new_rec);			
		}		
		return $insert_id;
	}
	
	
	private function updateQuery($formdef_file, $client_id, $primary_id, $params, $event_identifier = '')
    {
		global $app;
		
		$app->uses('remoting_lib');
		
		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile($client_id);
		
		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);
		
		//* Get the SQL query
		$sql = $app->remoting_lib->getSQL($params,'UPDATE',$primary_id);
		if($app->remoting_lib->errorMessage != '') {
			$this->server->fault('data_processing_error', $app->remoting_lib->errorMessage);
			return false;
		}
		
		$old_rec = $app->remoting_lib->getDataRecord($primary_id);
		
		// set a few values for compatibility with tform actions, mostly used by plugins
		$this->oldDataRecord = $old_rec;
		$this->id = $primary_id;
		$this->dataRecord = $params;
		
		$app->db->query($sql);
		
		if($app->db->errorMessage != '') {
			$this->server->fault('database_error', $app->db->errorMessage . ' '.$sql);
			return false;
		}
		
		$affected_rows = $app->db->affectedRows();
		
		if($event_identifier != '') $app->plugin->raiseEvent($event_identifier,$this);
		
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
		
		//* load the user profile of the client
		$app->remoting_lib->loadUserProfile(0);
		
		//* Load the form definition
		$app->remoting_lib->loadFormDef($formdef_file);
		
		$old_rec = $app->remoting_lib->getDataRecord($primary_id);
		
		// set a few values for compatibility with tform actions, mostly used by plugins
		$this->oldDataRecord = $old_rec;
		$this->id = $primary_id;
		$this->dataRecord = $params;
		
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
			$app->remoting_lib->datalogSave('DELETE',$primary_id,$old_rec,array());
		}
		
		
		return $affected_rows;
	}
	
	
	private function checkPerm($session_id, $function_name)
    {
	$dobre=array();
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
	
	//---
	
	
	/**
	 * Gets sites by $sys_userid & $sys_groupid
	 * @param	int		session id
	 * @param	int		user id
	 * @param	array	list of groups
	 * @return	mixed	array with sites by user
	 * @author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	public function client_get_sites_by_user($session_id, $sys_userid, $sys_groupid) {
        global $app;
        if(!$this->checkPerm($session_id, 'client_get_sites_by_user')) {
              $this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
              return false;
        }
        $sys_userid  = intval($sys_userid);        
        $sys_groupid = explode(',', $sys_groupid);
        $new_group = array();
        foreach($sys_groupid as $group_id) {
			$new_group[] = intval( $group_id);
        }
        $group_list = implode(',', $new_group);
		$sql ="SELECT domain, domain_id, document_root, active FROM web_domain WHERE ( (sys_userid = $sys_userid  AND sys_perm_user LIKE '%r%') OR (sys_groupid IN ($group_list) AND sys_perm_group LIKE '%r%') OR  sys_perm_other LIKE '%r%') AND type = 'vhost'";
        $result = $app->db->queryAllRecords($sql);
        if(isset($result)) {
			return $result;
        } else {
			$this->server->fault('no_client_found', 'There is no site for this user');
			return false;
        }
    }
    
    /**
     * Change domains status
	 * @param	int		session id
	 * @param	int		site id
	 * @param	string	active or inactive string 
	 * @return	mixed	false if error
	 * @author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	 
    public function sites_web_domain_set_status($session_id, $primary_id, $status) {
        global $app;
        if(!$this->checkPerm($session_id, 'sites_web_domain_set_status')) {
            $this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
        }        
        if(in_array($status, array('active', 'inactive'))) {        	    	
        	if ($status == 'active') {
        		$status = 'y';
        	} else {
        		$status = 'n';
        	}
	        $sql = "UPDATE web_domain SET active = '$status' WHERE domain_id = ".intval($primary_id);	        
	        $app->db->query($sql);
	        $result = $app->db->affectedRows();	
	         return $result;
        } else {
			$this->server->fault('status_undefined', 'The status is not available');
			return false;
        }      
	}
	
	/**
	 * Get sys_user information by username
	 * @param	int		session id
	 * @param	string	user's name  
	 * @return	mixed	false if error
	 * @author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	public function client_get_by_username($session_id, $username) {
        global $app;
        if(!$this->checkPerm($session_id, 'client_get_by_username')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
        }
        $username = $app->db->quote($username);
        $rec = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE username = '".$username."'");
        if (isset($rec)) {
			return $rec;
        } else {
			$this->server->fault('no_client_found', 'There is no user account for this user name.');
			return false;
        }
    }

    /**
     * Changes client password
     * 
  	 * @param	int		session id
  	 * @param	int		client	id
  	 * @param	string	new password
  	 * @return	bool	true if success 
	 * @author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
     * 
     */
    public function client_change_password($session_id, $client_id, $new_password) {
        global $app;

        if(!$this->checkPerm($session_id, 'client_change_password')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
        }
        $client_id = intval($client_id);
        $client = $app->db->queryOneRecord("SELECT client_id FROM client WHERE client_id = ".$client_id);
        if($client['client_id'] > 0) {
            $new_password = $app->db->quote($new_password);
            $sql = "UPDATE client SET password = md5('".($new_password)."') 	WHERE client_id = ".$client_id;
            $app->db->query($sql);            
            $sql = "UPDATE sys_user SET passwort = md5('".($new_password)."') 	WHERE client_id = ".$client_id;
            $app->db->query($sql);            
            return true;
        } else {
			$this->server->fault('no_client_found', 'There is no user account for this client_id');
			return false;
        }
    }
    
	public function mail_domain_get_by_domain($session_id, $domain) {
        global $app;
        if(!$this->checkPerm($session_id, 'mail_domain_get_by_domain')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
        }        
        if (!empty($domain_id)) {
        	$domain      	= $app->db->quote($domain);        	
    	    $sql            = "SELECT * FROM mail_domain WHERE domain = $domain";
        	$result         = $app->db->queryAllRecords($sql);
        	return          $result;
        }
        return false;
    }
    
    
    
	/**
   	* Get a list of functions
   	* @param 	int		session id
   	* @return	mixed	array of the available functions
    * @author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
    */
    public function get_function_list($session_id) 
    {
        if(!$this->checkPerm($session_id, 'get_function_list')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
        }
        return get_class_methods($this);
    }
    
    /**
     * Get all databases by user
     * @author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
     */
	public function sites_database_get_all_by_user($session_id, $client_id)
    {
        global $app;
		if(!$this->checkPerm($session_id, 'sites_database_get_all_by_user')) {
        	$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
		}
        $client_id = intval($client_id);
        $sql = "SELECT database_id, database_name, database_user, database_password FROM web_database WHERE sys_userid  = $client_id ";
        $all = $app->db->queryAllRecords($sql);
        return $all;
	}
	
	/**
	 * 	Get all client templates
	 *	@param 	int		session id
	 *	@author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	public function client_templates_get_all($session_id) {
		global $app;
		if(!$this->checkPerm($session_id, 'client_templates_get_all')) {
			 $this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
		}
        $sql    = "SELECT * FROM client_template";
		$result = $app->db->queryAllRecords($sql);
        return $result;
   }
	
	/**
	 * Get all DNS zone by user 
	 *@author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */	 
    public function dns_zone_get_by_user($session_id, $client_id, $server_id) {
        global $app;
        if(!$this->checkPerm($session_id, 'dns_zone_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
        }        
        if (!empty($client_id) && !empty($server_id)) {
        	$server_id      = intval($server_id);
        	$client_id      = intval($client_id);
    	    $sql            = "SELECT id, origin FROM dns_soa d INNER JOIN sys_user s on(d.sys_groupid = s.default_group) WHERE client_id = $client_id AND server_id = $server_id";
        	$result         = $app->db->queryAllRecords($sql);
        	return          $result;
        }
        return false;
    }
    
    
	/**
	 * Changes DNS zone status 
	 *	@param 	int		session id
	 *	@param	int		dns soa id
	 *	@param	string	status active or inactive string
	 *	@author	Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	 
    public function dns_zone_set_status($session_id, $primary_id, $status) {
        global $app;
        if(!$this->checkPerm($session_id, 'dns_zone_set_status')) {
              $this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
               return false;
        }        
        if(in_array($status, array('active', 'inactive'))) {	    	        	
	    	if ($status == 'active') {
	    		$status = 'Y';
	    	} else {
	    		$status = 'N';
	    	}
	        $sql = "UPDATE dns_soa SET active = '$status' WHERE id = ".intval($primary_id);
	        $app->db->query($sql);
	        $result = $app->db->affectedRows();
	        return $result;
        } else {
			$this->server->fault('status_undefined', 'The status is not available');
			return false;
        }  
    }
    
    public function mail_domain_set_status($session_id, $primary_id, $status) {
        global $app;
        if(!$this->checkPerm($session_id, 'mail_domain_set_status')) {
              $this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
               return false;
        }        
        if(in_array($status, array('active', 'inactive'))) {	    	        	
	    	if ($status == 'active') {
	    		$status = 'y';
	    	} else {
	    		$status = 'n';
	    	}
	        $sql = "UPDATE mail_domain SET active = '$status' WHERE domain_id = ".intval($primary_id);
	        $app->db->query($sql);
	        $result = $app->db->affectedRows();
	        return $result;
        } else {
			$this->server->fault('status_undefined', 'The status is not available');
			return false;
        }  
    }
}
?>