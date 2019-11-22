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
		$sql = "SELECT server_id FROM server_ip WHERE ip_address  = ?";
		$all = $app->db->queryOneRecord($sql, $ipaddress);
		return $all;
	}

	//* Get server ips
	public function server_ip_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'server_ip_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
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


	public function server_get($session_id, $server_id = null, $section ='') {
			global $app;
			if(!$this->checkPerm($session_id, 'server_get')) {
					throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
					return false;
			}
			if (!empty($session_id)) {
					$app->uses('remoting_lib , getconf');
					if(!empty($server_id)) {
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
	 Set a value in the server configuration
	 @param int session id
	 @param int server id
	 @param string  section of the config field in the server table. Could be 'web', 'dns', 'mail', 'dns', 'cron', etc
	 @param string key of the option that you want to set
	 @param string option value that you want to set
	 */


	public function server_config_set($session_id, $server_id, $section, $key, $value) {
			global $app;
			if(!$this->checkPerm($session_id, 'server_config_set')) {
				throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
				return false;
			}
			if (!empty($server_id) && $server_id > 0 && $section != '' && $key != '') {
				$app->uses('remoting_lib,getconf,ini_parser');
				$server_config_array = $app->getconf->get_server_config($server_id);
				$server_config_array[$section][$key] = $value;
				$server_config_str = $app->ini_parser->get_ini_string($server_config_array);
				return $app->db->datalogUpdate('server', array("config" => $server_config_str), 'server_id', $server_id);
			} else {
				throw new SoapFault('invalid_function_parameter', 'Invalid function parameter.');
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
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
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
	    Gets the server_id by server_name
	    @param int session_id
	    @param int server_name
	    @author Sascha Bay <info@space2place.de> TheCry 2013
    */
	public function server_get_serverid_by_name($session_id, $server_name)
    {
        global $app;
		if(!$this->checkPerm($session_id, 'server_get')) {
        	throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
		}
		if (!empty($session_id) && !empty($server_name)) {
			$sql = "SELECT server_id FROM server WHERE server_name  = ?";
			$all = $app->db->queryOneRecord($sql, $server_name);
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
        	throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
		}
		if (!empty($session_id) && !empty($server_id)) { 
			$sql = "SELECT mail_server, web_server, dns_server, file_server, db_server, vserver_server, proxy_server, firewall_server, mirror_server_id FROM server WHERE server_id  = ?";
			$all = $app->db->queryOneRecord($sql, $server_id);
			return $all;
		} else {
			return false;
		}
	}

	public function server_get_app_version($session_id, $server_id = 0)
    {
		global $app;
		if(!$this->checkPerm($session_id, 'server_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if (!empty($session_id)) {
			if($server_id == 0) $ispc_app_version = array('ispc_app_version' => ISPC_APP_VERSION);
			else {
				$rec = $app->db->queryOneRecord("SELECT data FROM monitor_data WHERE type = 'ispc_info' AND server_id = ?", $server_id);
				$rec = unserialize($rec['data']);
				$ispc_app_version = array('ispc_app_version' => $rec['version']);
				unset($rec);
			}
			return $ispc_app_version;
		} else {
			return false;
		}
	}

	public function server_get_php_versions($session_id, $server_id, $php)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'server_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
		}
		if (!empty($session_id) && !empty($server_id) && !empty($php)) {
			$php_versions = array();

			$web_config[$server_id] = $app->getconf->get_server_config($server_id, 'web');
			$server_type = !empty($web_config[$server_id]['server_type']) ? $web_config[$server_id]['server_type'] : 'apache';

			if ($php === 'php-fpm' || ($php === 'hhvm' && $server_type === 'nginx')) {
				$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ? AND (client_id = 0)", $server_id);
				foreach ($php_records as $php_record) {
					$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					$php_versions[] = $php_version;
				}
			}
			if ($php === 'fast-cgi') {
				$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ? AND (client_id = 0)", $server_id);
				foreach ($php_records as $php_record) {
					$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					$php_versions[] = $php_version;
				}
			}
			return $php_versions;
		} else {
			return false;
		}
	}
}