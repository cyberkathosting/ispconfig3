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

class remoting_server extends remoting {
	/**
	 Gets the server configuration
	 @param int session id
	 @param int server id
	 @param string  section of the config field in the server table. Could be 'web', 'dns', 'mail', 'dns', 'cron', etc
	 @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */


	public function server_get_serverid_by_ip($session_id, $ipaddress)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'server_get_serverid_by_ip')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$sql = "SELECT server_id FROM server_ip WHERE ip_address  = '$ipaddress' LIMIT 1 ";
		$all = $app->db->queryAllRecords($sql);
		return $all;
	}

	//* Get server ips
	public function server_ip_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'server_ip_get')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../admin/form/server_ip.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a IP address record
	public function server_ip_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'server_ip_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../admin/form/server_ip.tform.php', $client_id, $params);
	}

	//* Update IP address record
	public function server_ip_update($session_id, $client_id, $ip_id, $params)
	{
		if(!$this->checkPerm($session_id, 'server_ip_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../admin/form/server_ip.tform.php', $client_id, $ip_id, $params);
		return $affected_rows;
	}

	//* Delete IP address record
	public function server_ip_delete($session_id, $ip_id)
	{
		if(!$this->checkPerm($session_id, 'server_ip_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../admin/form/server_ip.tform.php', $ip_id);
		return $affected_rows;
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
			$section_config =  $app->getconf->get_server_config($server_id, $section);
			return $section_config;
		} else {
			return false;
		}
	}
	
	/**
	    Gets the server_id by server_name
	    @param int session_id
	    @param int server_name
	    @author Sascha Bay <info@space2place.de> TheCry 2013
    */
	public function server_get_serverid_by_name($session_id, $server_name)
    {
        global $app;
		if(!$this->checkPerm($session_id, 'server_get')) {
        	$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
		}
		if (!empty($session_id) && !empty($server_name)) {
			$sql = "SELECT server_id FROM server WHERE server_name  = '$server_name' LIMIT 1 ";
			$all = $app->db->queryAllRecords($sql);
			return $all;
		} else {
			return false;
		}
	}
	
	/**
	    Gets the functions of a server by server_id
	    @param int session_id
	    @param int server_id
	    @author Sascha Bay <info@space2place.de> TheCry 2013
    */
	public function server_get_functions($session_id, $server_id)
    {
        global $app;
		if(!$this->checkPerm($session_id, 'server_get')) {
        	$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
		}
		if (!empty($session_id) && !empty($server_id)) { 
			$sql = "SELECT mail_server, web_server, dns_server, file_server, db_server, vserver_server, proxy_server, firewall_server FROM server WHERE server_id  = '$server_id' LIMIT 1 ";
			$all = $app->db->queryAllRecords($sql);
			return $all;
		} else {
			return false;
		}
	}

}

?>
