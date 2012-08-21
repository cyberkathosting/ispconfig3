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
*/

class mysql_clientdb_plugin {
	
	var $plugin_name = 'mysql_clientdb_plugin';
	var $class_name  = 'mysql_clientdb_plugin';
	
	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;
		
		if($conf['services']['db'] == true) {
			return true;
		} else {
			return false;
		}
		
	}
	
		
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		//* Databases
		$app->plugins->registerEvent('database_insert',$this->plugin_name,'db_insert');
		$app->plugins->registerEvent('database_update',$this->plugin_name,'db_update');
		$app->plugins->registerEvent('database_delete',$this->plugin_name,'db_delete');
		
		//* Database users
		$app->plugins->registerEvent('database_user_insert',$this->plugin_name,'db_user_insert');
		$app->plugins->registerEvent('database_user_update',$this->plugin_name,'db_user_update');
		$app->plugins->registerEvent('database_user_delete',$this->plugin_name,'db_user_delete');
		
		
	}
	
  function process_host_list($action, $database_name, $database_user, $database_password, $host_list, $link, $database_rename_user = '', $user_read_only = false) {
      global $app;
      
      $action = strtoupper($action);
      
      // set to all hosts if none given
      if(trim($host_list) == '') $host_list = '%';
      
      // process arrays and comma separated strings
      if(!is_array($host_list)) $host_list = explode(',', $host_list);
      
      $success = true;
      
      // loop through hostlist
      foreach($host_list as $db_host) {
          $db_host = trim($db_host);
          
          // check if entry is valid ip address
          $valid = true;
		  if($db_host == '%') {
		  	$valid = true;
		  } elseif(preg_match("/^[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}$/", $db_host)) {
              $groups = explode('.', $db_host);
              foreach($groups as $group){
                if($group<0 OR $group>255)
                $valid=false;
              }
          } else {
              $valid = false;
          }
          
          if($valid == false) continue;
          
          if($action == 'GRANT') {
              if(!$link->query("GRANT " . ($user_read_only ? "SELECT" : "ALL") . " ON ".$link->escape_string($database_name).".* TO '".$link->escape_string($database_user)."'@'$db_host' IDENTIFIED BY PASSWORD '".$link->escape_string($database_password)."';")) $success = false;
          } elseif($action == 'REVOKE') {
              if(!$link->query("REVOKE ALL PRIVILEGES ON ".$link->escape_string($database_name).".* FROM '".$link->escape_string($database_user)."'@'$db_host' IDENTIFIED BY PASSWORD '".$link->escape_string($database_password)."';")) $success = false;
          } elseif($action == 'DROP') {
              if(!$link->query("DROP USER '".$link->escape_string($database_user)."'@'$db_host';")) $success = false;
          } elseif($action == 'RENAME') {
              if(!$link->query("RENAME USER '".$link->escape_string($database_user)."'@'$db_host' TO '".$link->escape_string($database_rename_user)."'@'$db_host'")) $success = false;
          } elseif($action == 'PASSWORD') {
              if(!$link->query("SET PASSWORD FOR '".$link->escape_string($database_user)."'@'$db_host' = '".$link->escape_string($database_password)."';")) $success = false;
          }
      }
      
      return $success;
  }
	
	function db_insert($event_name,$data) {
		global $app, $conf;
		
		if($data['new']['type'] == 'mysql') {
			if(!include(ISPC_LIB_PATH.'/mysql_clientdb.conf')) {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf',LOGLEVEL_ERROR);
				return;
			}
			
			//* Connect to the database
			$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
			if ($link->connect_error) {
				$app->log('Unable to connect to mysql'.$link->connect_error,LOGLEVEL_ERROR);
				return;
			}

			// Charset for the new table
			if($data['new']['database_charset'] != '') {
        $query_charset_table = ' DEFAULT CHARACTER SET '.$data['new']['database_charset'];
			} else {
        $query_charset_table = '';
			}

			//* Create the new database
			if ($link->query('CREATE DATABASE '.$link->escape_string($data['new']['database_name']).$query_charset_table)) {
				$app->log('Created MySQL database: '.$data['new']['database_name'],LOGLEVEL_DEBUG);
			} else {
				$app->log('Unable to create the database: '.$link->error,LOGLEVEL_WARNING);
			}
			
			// Create the database user if database is active
			if($data['new']['active'] == 'y') {
				
                // get the users for this database
                $db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['new']['database_user_id']) . "'");
                
                $db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['new']['database_ro_user_id']) . "'");
                
                $host_list = '';
                if($data['new']['remote_access'] == 'y') {
                    $host_list = $data['new']['remote_ips'];
                }
                if($host_list != '') $host_list .= ',';
                $host_list .= 'localhost';
                
                if($db_user) {
                    if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link);
                }
                if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
                    if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link, '', true);
                }
				
			}
			
			$link->query('FLUSH PRIVILEGES;');
			$link->close();
		}
	}
	
	function db_update($event_name,$data) {
		global $app, $conf;
		
		if($data['new']['type'] == 'mysql') {
			if(!include(ISPC_LIB_PATH.'/mysql_clientdb.conf')) {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf',LOGLEVEL_ERROR);
				return;
			}
			
			//* Connect to the database
			$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
			if ($link->connect_error) {
				$app->log('Unable to connect to the database: '.$link->connect_error,LOGLEVEL_ERROR);
				return;
			}
			
            // get the users for this database
            $db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['new']['database_user_id']) . "'");
            
            $db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['new']['database_ro_user_id']) . "'");
            
            $host_list = '';
            if($data['new']['remote_access'] == 'y') {
                $host_list = $data['new']['remote_ips'];
            }
            if($host_list != '') $host_list .= ',';
            $host_list .= 'localhost';
            
            // Create the database user if database was disabled before
			if($data['new']['active'] == 'y' && $data['old']['active'] == 'n') {
                if($db_user) {
                    if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link);
                }
                if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
                    if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link, '', true);
                }
			} else if($data['new']['active'] == 'n' && $data['old']['active'] == 'y') { // revoke database user, if inactive
                if($db_user) {
                    if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else $this->process_host_list('REVOKE', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link);
                }
                if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
                    if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else $this->process_host_list('REVOKE', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link);
                }
			}
            
            //* selected Users have changed
            if($data['new']['database_user_id'] != $data['old']['database_user_id']) {
                if($data['old']['database_user_id'] && $data['old']['database_user_id'] != $data['new']['database_ro_user_id']) {
                    $old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['old']['database_user_id']) . "'");
                    if($old_db_user) {
                        if($old_db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                        else $this->process_host_list('REVOKE', $data['new']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $host_list, $link);
                    }
                }
                if($db_user) {
                    if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link);
                }
            }
            if($data['new']['database_ro_user_id'] != $data['old']['database_ro_user_id']) {
                if($data['old']['database_ro_user_id'] && $data['old']['database_ro_user_id'] != $data['new']['database_user_id']) {
                    $old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = '" . intval($data['old']['database_ro_user_id']) . "'");
                    if($old_db_user) {
                        if($old_db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                        else $this->process_host_list('REVOKE', $data['new']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $host_list, $link);
                    }
                }
                if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
                    if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link, '', true);
                }
            }
			
			//* Remote access option has changed.
			if($data['new']['remote_access'] != $data['old']['remote_access']) {
				
				//* revoke old priveliges
				//mysql_query("REVOKE ALL PRIVILEGES ON ".mysql_real_escape_string($data["new"]["database_name"],$link).".* FROM '".mysql_real_escape_string($data["new"]["database_user"],$link)."';",$link);
				
				//* set new priveliges
				if($data['new']['remote_access'] == 'y') { 		
                    if($db_user) {
                        if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                        else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['new']['remote_ips'], $link);
                    }
                    if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
                        if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                        else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['new']['remote_ips'], $link, '', true);
                    }
				} else {
                    if($db_user) {
                        if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                        else $this->process_host_list('REVOKE', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['new']['remote_ips'], $link);
                    }
                    if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
                        if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                        else $this->process_host_list('REVOKE', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['new']['remote_ips'], $link);
                    }
				}
				$app->log('Changing MySQL remote access privileges for database: '.$data['new']['database_name'],LOGLEVEL_DEBUG);
			} elseif($data['new']['remote_access'] == 'y' && $data['new']['remote_ips'] != $data['old']['remote_ips']) {
                //* Change remote access list
                if($db_user) {
                    if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else {
                        $this->process_host_list('REVOKE', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['old']['remote_ips'], $link);
                        $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['new']['remote_ips'], $link);
                    }
                }
                if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
                    if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases',LOGLEVEL_WARNING);
                    else {
                        $this->process_host_list('REVOKE', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['old']['remote_ips'], $link);
                        $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['new']['remote_ips'], $link, '', true);
                    }
                }
          }
      
			
			$link->query('FLUSH PRIVILEGES;');
			$link->close();
		}
		
	}
	
	function db_delete($event_name,$data) {
		global $app, $conf;
		
		if($data['old']['type'] == 'mysql') {
			if(!include(ISPC_LIB_PATH.'/mysql_clientdb.conf')) {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf',LOGLEVEL_ERROR);
				return;
			}
		
			//* Connect to the database
			$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
			if ($link->connect_error) {
				$app->log('Unable to connect to mysql: '.$link->connect_error,LOGLEVEL_ERROR);
				return;
			}
			
			if($link->query('DROP DATABASE '.$link->escape_string($data['old']['database_name']))) {
				$app->log('Dropping MySQL database: '.$data['old']['database_name'],LOGLEVEL_DEBUG);
			} else {
				$app->log('Error while dropping MySQL database: '.$data['old']['database_name'].' '.$link->error,LOGLEVEL_WARNING);
			}
			
			$link->query('FLUSH PRIVILEGES;');
			$link->close();
		}
		
		
	}
	
	 
	function db_user_insert($event_name,$data) {
		global $app, $conf;
		// we have nothing to do here, stale user accounts are useless ;)
	}
	
	function db_user_update($event_name,$data) {
		global $app, $conf;
		
        if(!include(ISPC_LIB_PATH.'/mysql_clientdb.conf')) {
            $app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf',LOGLEVEL_ERROR);
            return;
        }
        
        //* Connect to the database
        $link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
        if ($link->connect_error) {
            $app->log('Unable to connect to mysql'.$link->connect_error,LOGLEVEL_ERROR);
            return;
        }
        
        
        if($data['old']['database_user'] == $data['new']['database_user'] && $data['old']['database_password'] == $data['new']['database_password']) {
            return;
        }
        
        
        $host_list = array('localhost');
        // get all databases this user was active for
        $db_list = $app->db->queryAllRecords("SELECT `remote_access`, `remote_ips` FROM `web_database` WHERE `database_user_id` = '" . intval($data['old']['database_user_id']) . "'");
        foreach($db_list as $database) {
            if($database['remote_access'] != 'y') continue;
            
            if($database['remote_ips'] != '') $ips = explode(',', $database['remote_ips']);
            else $ips = array('%');
            
            foreach($ips as $ip) {
                $ip = trim($ip);
                if(!in_array($ip, $host_list)) $host_list[] = $ip;
            }
        }
        
        foreach($host_list as $db_host) {
            if($data['new']['database_user'] != $data['old']['database_user']) {
				$link->query("RENAME USER '".$link->escape_string($data['old']['database_user'])."'@'$db_host' TO '".$link->escape_string($data['new']['database_user'])."'@'$db_host'");
				$app->log('Renaming MySQL user: '.$data['old']['database_user'].' to '.$data['new']['database_user'],LOGLEVEL_DEBUG);
			}

			if($data['new']['database_password'] != $data['old']['database_password']) {
				$db_host = 'localhost';
				$link->query("SET PASSWORD FOR '".$link->escape_string($data['new']['database_user'])."'@'$db_host' = '".$link->escape_string($data['new']['database_password'])."';");
				$app->log('Changing MySQL user password for: '.$data['new']['database_user'],LOGLEVEL_DEBUG);
			}
        }
        
        $link->query('FLUSH PRIVILEGES;');
        $link->close();
        
	}
	
	function db_user_delete($event_name,$data) {
		global $app, $conf;
		
        if(!include(ISPC_LIB_PATH.'/mysql_clientdb.conf')) {
            $app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf',LOGLEVEL_ERROR);
            return;
        }
        
        //* Connect to the database
        $link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
        if ($link->connect_error) {
            $app->log('Unable to connect to mysql'.$link->connect_error,LOGLEVEL_ERROR);
            return;
        }
        
        $host_list = array();
        // read all mysql users with this username
        $result = $link->query("SELECT `User`, `Host` FROM `mysql`.`user` WHERE `User` = '" . $link->escape_string($data['old']['database_user']) . "' AND `Create_user_priv` = 'N'"); // basic protection against accidently deleting system users like debian-sys-maint
        if($result) {
            while($row = $result->fetch_assoc()) {
                $host_list[] = $row['Host'];
            }
            $result->free();
        }
        
        foreach($host_list as $db_host) {
            if($link->query("DROP USER '".$link->escape_string($data['old']['database_user'])."'@'$db_host';")) {
				$app->log('Dropping MySQL user: '.$data['old']['database_user'],LOGLEVEL_DEBUG);
            }
        }
        
        $link->query('FLUSH PRIVILEGES;');
        $link->close();
	}
} // end class

?>
