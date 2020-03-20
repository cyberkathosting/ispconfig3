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
		$app->plugins->registerEvent('database_insert', $this->plugin_name, 'db_insert');
		$app->plugins->registerEvent('database_update', $this->plugin_name, 'db_update');
		$app->plugins->registerEvent('database_delete', $this->plugin_name, 'db_delete');

		//* Database users
		$app->plugins->registerEvent('database_user_insert', $this->plugin_name, 'db_user_insert');
		$app->plugins->registerEvent('database_user_update', $this->plugin_name, 'db_user_update');
		$app->plugins->registerEvent('database_user_delete', $this->plugin_name, 'db_user_delete');


	}

	function process_host_list($action, $database_name, $database_user, $database_password, $host_list, $link, $database_rename_user = '', $user_access_mode = 'rw') {
		global $app;

		// check mysql-plugins
		$unwanted_sql_plugins = array('validate_password'); // strict-password-validation
		$temp = "'".implode("','", $unwanted_sql_plugins)."'";
		$result = $link->query("SELECT plugin_name FROM information_schema.plugins WHERE plugin_status='ACTIVE' AND plugin_name IN ($temp)");
		if($result && $result->num_rows > 0) {
			$sql_plugins = array();
			while ($row = $result->fetch_assoc()) {
				$sql_plugins[] = $row['plugin_name'];
			}
			$result->free();
			if(count($sql_plugins) > 0) {
				foreach ($sql_plugins as $plugin) $app->log("MySQL-Plugin $plugin enabled - can not execute function process_host_list", LOGLEVEL_ERROR);
				return false;
			}
		}

		if(!$user_access_mode) $user_access_mode = 'rw';
		$action = strtoupper($action);

		// set to all hosts if none given
		if(trim($host_list) == '') $host_list = '%';

		// process arrays and comma separated strings
		if(!is_array($host_list)) $host_list = explode(',', $host_list);

		$success = true;
		if(!preg_match('/\*[A-F0-9]{40}$/', $database_password)) {
				$result = $link->query("SELECT PASSWORD('" . $link->escape_string($database_password) . "') as `crypted`");
				if($result) {
						$row = $result->fetch_assoc();
						$database_password = $row['crypted'];
						$result->free();
				}
		}

		$app->log("Calling $action for $database_name with access $user_access_mode and hosts " . implode(', ', $host_list), LOGLEVEL_DEBUG);

		// loop through hostlist
		foreach($host_list as $db_host) {
			$db_host = trim($db_host);

			$app->log($action . ' for user ' . $database_user . ' at host ' . $db_host, LOGLEVEL_DEBUG);

			// check if entry is valid ip address
			$valid = true;
			if($db_host == '%' || $db_host == 'localhost') {
				$valid = true;
			} elseif(function_exists('filter_var')) {
				if(!filter_var($db_host, FILTER_VALIDATE_IP)) $valid=false;
			} elseif(preg_match("/^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", $db_host)) {
				$groups = explode('.', $db_host);
				foreach($groups as $group){
					if($group<0 or $group>255)
						$valid=false;
				}
			} else {
				$valid = false;
			}

			if($valid == false) {
				$app->log("Invalid host " . $db_host . " for GRANT to " . $database_name, LOGLEVEL_DEBUG);
				continue;
			}

			$grants = 'ALL PRIVILEGES';
			if($user_access_mode == 'r') $grants = 'SELECT';
			elseif($user_access_mode == 'rd') $grants = 'SELECT, DELETE, ALTER, DROP';

			$database_name = str_replace('_', '\\_', $link->escape_string($database_name));

			if($action == 'GRANT') {
				if($user_access_mode == 'r' || $user_access_mode == 'rd') {
					if(!$link->query("REVOKE ALL PRIVILEGES ON `".$database_name."`.* FROM '".$link->escape_string($database_user)."'@'$db_host'")) $success = false;
					$app->log("REVOKE ALL PRIVILEGES ON `".$database_name."`.* FROM '".$link->escape_string($database_user)."'@'$db_host' success? " . ($success ? 'yes' : 'no'), LOGLEVEL_DEBUG);
					$success = true;
				}

				if(!$link->query("GRANT " . $grants . " ON `".$database_name."`.* TO '".$link->escape_string($database_user)."'@'$db_host' IDENTIFIED BY PASSWORD '".$link->escape_string($database_password)."'")) $success = false;
				$app->log("GRANT " . $grants . " ON `".$database_name."`.* TO '".$link->escape_string($database_user)."'@'$db_host' IDENTIFIED BY PASSWORD '".$link->escape_string($database_password)."' success? " . ($success ? 'yes' : 'no'), LOGLEVEL_DEBUG);
			} elseif($action == 'REVOKE') {
				if(!$link->query("REVOKE ALL PRIVILEGES ON `".$database_name."`.* FROM '".$link->escape_string($database_user)."'@'$db_host'")) $success = false;
			} elseif($action == 'DROP') {
				if(!$link->query("DROP USER '".$link->escape_string($database_user)."'@'$db_host'")) $success = false;
			} elseif($action == 'RENAME') {
				if(!$link->query("RENAME USER '".$link->escape_string($database_user)."'@'$db_host' TO '".$link->escape_string($database_rename_user)."'@'$db_host'")) $success = false;
			} elseif($action == 'PASSWORD') {
				//if(!$link->query("SET PASSWORD FOR '".$link->escape_string($database_user)."'@'$db_host' = '".$link->escape_string($database_password)."'")) $success = false;
				// SET PASSWORD for already hashed passwords is not supported by latest MySQL 5.7 anymore, so we have to set the hashed password directly
				if(trim($database_password) != '') {
					// MySQL < 5.7 and MariadB 10
					if(!$link->query("UPDATE mysql.user SET `Password` = '".$link->escape_string($database_password)."' WHERE `Host` = '".$db_host."' AND `User` = '".$link->escape_string($database_user)."'")) {
						// MySQL 5.7, the Password field has been renamed to authentication_string
						if(!$link->query("UPDATE mysql.user SET `authentication_string` = '".$link->escape_string($database_password)."' WHERE `Host` = '".$db_host."' AND `User` = '".$link->escape_string($database_user)."'")) $success = false;
					}
					if($success == true) $link->query("FLUSH PRIVILEGES");
				}
			}
		}

		return $success;
	}

	function drop_or_revoke_user($database_id, $user_id, $host_list){
		global $app;

		// set to all hosts if none given
		if(trim($host_list) == '') $host_list = '%';

		$db_user_databases = $app->db->queryAllRecords("SELECT * FROM web_database WHERE (database_user_id = ? OR database_ro_user_id = ?) AND active = 'y' AND database_id != ?", $user_id, $user_id, $database_id);
		$db_user_host_list = array();
		if(is_array($db_user_databases) && !empty($db_user_databases)){
			foreach($db_user_databases as $db_user_database){
				if($db_user_database['remote_access'] == 'y'){
					if($db_user_database['remote_ips'] == ''){
						$db_user_host_list[] = '%';
					} else {
						$tmp_remote_ips = explode(',', $db_user_database['remote_ips']);
						if(is_array($tmp_remote_ips) && !empty($tmp_remote_ips)){
							foreach($tmp_remote_ips as $tmp_remote_ip){
								$tmp_remote_ip = trim($tmp_remote_ip);
								if($tmp_remote_ip != '') $db_user_host_list[] = $tmp_remote_ip;
							}
						}
						unset($tmp_remote_ips);
					}
				}
				$db_user_host_list[] = 'localhost';
			}
		}
		$host_list_arr = explode(',', $host_list);
		//print_r($host_list_arr);
		$drop_hosts = array_diff($host_list_arr, $db_user_host_list);
		//print_r($drop_hosts);
		$revoke_hosts = array_diff($host_list_arr, $drop_hosts);
		//print_r($revoke_hosts);

		$drop_host_list = implode(',', $drop_hosts);
		$revoke_host_list = implode(',', $revoke_hosts);
		//echo $drop_host_list."\n";
		//echo $revoke_host_list."\n";
		return array('revoke_hosts' => $revoke_host_list, 'drop_hosts' => $drop_host_list);
	}

	function db_insert($event_name, $data) {
		global $app, $conf;

		if($data['new']['type'] == 'mysql') {
			if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
				return;
			}

			//* Connect to the database
			$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
			if ($link->connect_error) {
				$app->log('Unable to connect to mysql'.$link->connect_error, LOGLEVEL_ERROR);
				return;
			}

			// Charset for the new table
			if($data['new']['database_charset'] != '') {
				$query_charset_table = ' DEFAULT CHARACTER SET '.$data['new']['database_charset'];
			} else {
				$query_charset_table = '';
			}

			//* Create the new database
			if ($link->query('CREATE DATABASE `'.$link->escape_string($data['new']['database_name']).'`'.$query_charset_table)) {
				$app->log('Created MySQL database: '.$data['new']['database_name'], LOGLEVEL_DEBUG);
			} else {
				$app->log('Unable to create the database: '.$link->error, LOGLEVEL_WARNING);
			}

			// Create the database user if database is active
			if($data['new']['active'] == 'y') {

				// get the users for this database
				$db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = ?", $data['new']['database_user_id']);
				$db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = ?", $data['new']['database_ro_user_id']);

				$host_list = '';
				if($data['new']['remote_access'] == 'y') {
					$host_list = $data['new']['remote_ips'];
					if($host_list == '') $host_list = '%';
				}
				if($host_list != '') $host_list .= ',';
				$host_list .= 'localhost';

				if($db_user) {
					if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link, '', ($data['new']['quota_exceeded'] == 'y' ? 'rd' : 'rw'));
				}
				if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link, '', 'r');
				}

			}

			$link->close();
		}
	}

	function db_update($event_name, $data) {
		global $app, $conf;

		// skip processing if database was and is inactive
		if($data['new']['active'] == 'n' && $data['old']['active'] == 'n') return;

		if($data['new']['type'] == 'mysql') {
			if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
				return;
			}

			//* Connect to the database
			$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
			if ($link->connect_error) {
				$app->log('Unable to connect to the database: '.$link->connect_error, LOGLEVEL_ERROR);
				return;
			}

			// check if the database exists
			if($data['new']['database_name'] == $data['old']['database_name']) {
				$result = $link->query("SHOW DATABASES LIKE '".$link->escape_string($data['new']['database_name'])."'");
				if($result->num_rows === 0) $this->db_insert($event_name, $data);
			}

			// get the users for this database
			$db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = ?", $data['new']['database_user_id']);
			$old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = ?", $data['old']['database_user_id']);

			$db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = ?", $data['new']['database_ro_user_id']);
			$old_db_ro_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = ?", $data['old']['database_ro_user_id']);

			$host_list = '';
			if($data['new']['remote_access'] == 'y') {
				$host_list = $data['new']['remote_ips'];
				if($host_list == '') $host_list = '%';
			}
			if($host_list != '') $host_list .= ',';
			$host_list .= 'localhost';

			// REVOKES and DROPS have to be done on old host list, not new host list
			$old_host_list = '';
			if($data['old']['remote_access'] == 'y') {
				$old_host_list = $data['old']['remote_ips'];
				if($old_host_list == '') $old_host_list = '%';
			}
			if($old_host_list != '') $old_host_list .= ',';
			$old_host_list .= 'localhost';

			//* rename database
			if ( $data['new']['database_name'] !=  $data['old']['database_name'] ) {
				$old_name = $link->escape_string($data['old']['database_name']);
				$new_name = $link->escape_string($data['new']['database_name']);
				$timestamp = time();

				$tables = $link->query("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema='".$old_name."' AND TABLE_TYPE='BASE TABLE'");
				$tables_all = $link->query("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema='".$old_name."'");
				if ($tables->num_rows > 0) {
					while ($row = $tables->fetch_assoc()) {
						$tables_array[] = $row['TABLE_NAME'];
					}

					//* save triggers, routines and events
					$triggers = $link->query("SHOW TRIGGERS FROM ".$old_name);
					if ($triggers->num_rows > 0) {
						while ($row = $triggers->fetch_assoc()) {
							$triggers_array[] = $row;
						}
						$app->log('Dumping triggers from '.$old_name, LOGLEVEL_DEBUG);
						$command = "mysqldump -h ? -u ? -p? ? -d -t -R -E > ?";
						$app->system->exec_safe($command, $clientdb_host, $clientdb_user, $clientdb_password, $old_name, $timestamp.$old_name.'.triggers');
						$ret = $app->system->last_exec_retcode();
						$app->system->chmod($timestamp.$old_name.'.triggers', 0600);
						if ($ret != 0) {
							unset($triggers_array);
							$app->system->unlink($timestamp.$old_name.'.triggers');
							$app->log('Unable to dump triggers from '.$old_name, LOGLEVEL_ERROR);
						}
					}

					//* save views
					$views = $link->query("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema='".$old_name."' and TABLE_TYPE='VIEW'");
					if ($views->num_rows > 0) {
						while ($row = $views->fetch_assoc()) {
							$views_array[] = $row;
						}
						foreach ($views_array as $_views) {
							$temp[] = $_views['TABLE_NAME'];
						}
						$app->log('Dumping views from '.$old_name, LOGLEVEL_DEBUG);
						$temp_views = implode(' ', $temp);
						$command = "mysqldump -h ? -u ? -p? ? ? > ?";
						$app->system->exec_safe($command, $clientdb_host, $clientdb_user, $clientdb_password, $old_name, $temp_views, $timestamp.$old_name.'.views');
						$ret = $app->system->last_exec_retcode();
						$app->system->chmod($timestamp.$old_name.'.views', 0600);
						if ($ret != 0) {
							unset($views_array);
							$app->system->unlink($timestamp.$old_name.'.views');
							$app->log('Unable to dump views from '.$old_name, LOGLEVEL_ERROR);
						}
						unset($temp);
						unset($temp_views);
					}

					//* create new database
					$this->db_insert($event_name, $data);

					$link->query("show databases like '".$new_name."'");
					if ($link) {
						//* rename tables
						foreach ($tables_array as $table) {
							$table = $link->escape_string($table);
							$sql = "RENAME TABLE ".$old_name.".".$table." TO ".$new_name.".".$table;
							$link->query($sql);
							$app->log($sql, LOGLEVEL_DEBUG);
							if(!$link) {
								$app->log($sql." failed", LOGLEVEL_ERROR);
							}
						}

						//* drop old triggers
						if (@is_array($triggers_array)) {
							foreach($triggers_array as $trigger) {
								$_trigger = $link->escape_string($trigger['Trigger']);
								$sql = "DROP TRIGGER ".$old_name.".".$_trigger;
								$link->query($sql);
								$app->log($sql, LOGLEVEL_DEBUG);
								unset($_trigger);
							}
							//* update triggers, routines and events
							$command = "mysql -h ? -u ? -p? ? < ?";
							$app->system->exec_safe($command, $clientdb_host, $clientdb_user, $clientdb_password, $new_name, $timestamp.$old_name.'.triggers');
							$ret = $app->system->last_exec_retcode();
							if ($ret != 0) {
								$app->log('Unable to import triggers for '.$new_name, LOGLEVEL_ERROR);
							} else {
								$app->system->unlink($timestamp.$old_name.'.triggers');
							}
						}

						//* loading views
						if (@is_array($views_array)) {
							$command = "mysql -h ? -u ? -p? ? < ?";
							$app->system->exec_safe($command, $clientdb_host, $clientdb_user, $clientdb_password, $new_name, $timestamp.$old_name.'.views');
							$ret = $app->system->last_exec_retcode();
							if ($ret != 0) {
								$app->log('Unable to import views for '.$new_name, LOGLEVEL_ERROR);
							} else {
								$app->system->unlink($timestamp.$old_name.'.views');
							}
						}

						//* drop old database
						$this->db_delete($event_name, $data);
					} else {
						$app->log('Connection to new databse '.$new_name.' failed', LOGLEVEL_ERROR);
						if (@is_array($triggers_array)) {
							$app->system->unlink($timestamp.$old_name.'.triggers');
						}
						if (@is_array($views_array)) {
							$app->system->unlink($timestamp.$old_name.'.views');
						}
					}

				} elseif ($tables->num_rows == 0 && $tables_all->num_rows == 0) {
					//* Rename empty database by creating a new one and dropping the old database
					$this->db_insert($event_name, $data);
					$this->db_delete($event_name, $data);

				} else { //* SELECT TABLE_NAME error
					$app->log('Unable to rename database '.$old_name.' to '.$new_name, LOGLEVEL_ERROR);
				}
			}

			// Create the database user if database was disabled before
			if($data['new']['active'] == 'y') {
				if($db_user) {
					if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link, '', ($data['new']['quota_exceeded'] == 'y' ? 'rd' : 'rw'));
				}
				if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link, '', 'r');
				}
			} elseif($data['new']['active'] == 'n' && $data['old']['active'] == 'y') { // revoke database user, if inactive
				if($old_db_user) {
					if($old_db_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						// Find out users to drop and users to revoke
						$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $old_host_list);
						if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
						if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
					}

				}
				if($old_db_ro_user && $data['old']['database_user_id'] != $data['old']['database_ro_user_id']) {
					if($old_db_ro_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						// Find out users to drop and users to revoke
						$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_ro_user_id'], $old_host_list);
						if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
						if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
					}
				}
				// Database is not active, so stop processing here
				$link->close();
				return;
			}

			//* selected Users have changed
			if($data['new']['database_user_id'] != $data['old']['database_user_id']) {
				if($data['old']['database_user_id'] && $data['old']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($old_db_user) {
						if($old_db_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $old_host_list);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
						}
					}
				}
				if($db_user) {
					if($db_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $host_list, $link, '', ($data['new']['quota_exceeded'] == 'y' ? 'rd' : 'rw'));
				}
			}
			if($data['new']['database_ro_user_id'] != $data['old']['database_ro_user_id']) {
				if($data['old']['database_ro_user_id'] && $data['old']['database_ro_user_id'] != $data['new']['database_user_id']) {
					if($old_db_ro_user) {
						if($old_db_ro_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_ro_user_id'], $old_host_list);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
						}
					}
				}
				if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $host_list, $link, '', 'r');
				}
			}

			//* Remote access option has changed.
			if($data['new']['remote_access'] != $data['old']['remote_access']) {

				//* set new priveliges
				if($data['new']['remote_access'] == 'y') {
					if($db_user) {
						if($db_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							$this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['new']['remote_ips'], $link, '', ($data['new']['quota_exceeded'] == 'y' ? 'rd' : 'rw'));
						}
					}
					if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
						if($db_ro_user['database_user'] == 'root') $app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						else $this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['new']['remote_ips'], $link, '', 'r');
					}
				} else {
					if($old_db_user) {
						if($old_db_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $data['old']['remote_ips']);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
						}
					}
					if($old_db_ro_user && $data['old']['database_user_id'] != $data['old']['database_ro_user_id']) {
						if($old_db_ro_user['database_user'] == 'root'){
							$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
						} else {
							// Find out users to drop and users to revoke
							$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_ro_user_id'], $data['old']['remote_ips']);
							if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
							if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
						}
					}
				}
				$app->log('Changing MySQL remote access privileges for database: '.$data['new']['database_name'], LOGLEVEL_DEBUG);
			} elseif($data['new']['remote_access'] == 'y' && $data['new']['remote_ips'] != $data['old']['remote_ips']) {
				//* Change remote access list
				if($old_db_user) {
					if($old_db_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						// Find out users to drop and users to revoke
						$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $data['old']['remote_ips']);
						if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
						if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
					}
				}
				if($db_user) {
					if($db_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						$this->process_host_list('GRANT', $data['new']['database_name'], $db_user['database_user'], $db_user['database_password'], $data['new']['remote_ips'], $link, '', ($data['new']['quota_exceeded'] == 'y' ? 'rd' : 'rw'));
					}
				}

				if($old_db_ro_user && $data['old']['database_user_id'] != $data['old']['database_ro_user_id']) {
					if($old_db_ro_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						// Find out users to drop and users to revoke
						$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_ro_user_id'], $data['old']['remote_ips']);
						if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
						if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_ro_user['database_user'], $old_db_ro_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
					}
				}

				if($db_ro_user && $data['new']['database_user_id'] != $data['new']['database_ro_user_id']) {
					if($db_ro_user['database_user'] == 'root'){
						$app->log('User root not allowed for Client databases', LOGLEVEL_WARNING);
					} else {
						$this->process_host_list('GRANT', $data['new']['database_name'], $db_ro_user['database_user'], $db_ro_user['database_password'], $data['new']['remote_ips'], $link, '', 'r');
					}
				}
			}

			$link->close();
		}

	}

	function db_delete($event_name, $data) {
		global $app, $conf;

		if($data['old']['type'] == 'mysql') {
			if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
				return;
			}

			//* Connect to the database
			$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
			if ($link->connect_error) {
				$app->log('Unable to connect to mysql: '.$link->connect_error, LOGLEVEL_ERROR);
				return;
			}

			$old_host_list = '';
			if($data['old']['remote_access'] == 'y') {
				$old_host_list = $data['old']['remote_ips'];
				if($old_host_list == '') $old_host_list = '%';
			}
			if($old_host_list != '') $old_host_list .= ',';
			$old_host_list .= 'localhost';

			if($data['old']['database_user_id']) {
				$old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = ?", $data['old']['database_user_id']);
				$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_user_id'], $old_host_list);
				if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
				if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
			}
			if($data['old']['database_ro_user_id']) {
				$old_db_user = $app->db->queryOneRecord("SELECT `database_user`, `database_password` FROM `web_database_user` WHERE `database_user_id` = ?", $data['old']['database_ro_user_id']);
				$drop_or_revoke_user = $this->drop_or_revoke_user($data['old']['database_id'], $data['old']['database_ro_user_id'], $old_host_list);
				if($drop_or_revoke_user['drop_hosts'] != '') $this->process_host_list('DROP', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['drop_hosts'], $link);
				if($drop_or_revoke_user['revoke_hosts'] != '') $this->process_host_list('REVOKE', $data['old']['database_name'], $old_db_user['database_user'], $old_db_user['database_password'], $drop_or_revoke_user['revoke_hosts'], $link);
			}


			if($link->query('DROP DATABASE `'.$link->escape_string($data['old']['database_name'].'`'))) {
				$app->log('Dropping MySQL database: '.$data['old']['database_name'], LOGLEVEL_DEBUG);
			} else {
				$app->log('Error while dropping MySQL database: '.$data['old']['database_name'].' '.$link->error, LOGLEVEL_WARNING);
			}

			$link->close();
		}


	}


	function db_user_insert($event_name, $data) {
		global $app, $conf;
		// we have nothing to do here, stale user accounts are useless ;)
	}

	function db_user_update($event_name, $data) {
		global $app, $conf;

		if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
			$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
			return;
		}

		//* Connect to the database
		$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
		if ($link->connect_error) {
			$app->log('Unable to connect to mysql'.$link->connect_error, LOGLEVEL_ERROR);
			return;
		}


		if($data['old']['database_user'] == $data['new']['database_user'] && ($data['old']['database_password'] == $data['new']['database_password'] || $data['new']['database_password'] == '')) {
			return;
		}


		$host_list = array('localhost');
		// get all databases this user was active for
		$user_id = intval($data['old']['database_user_id']);
		$db_list = $app->db->queryAllRecords("SELECT `remote_access`, `remote_ips` FROM `web_database` WHERE `database_user_id` = ? OR database_ro_user_id = ?", $user_id, $user_id);;
		if(count($db_list) < 1) return; // nothing to do on this server for this db user

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
				$app->log('Renaming MySQL user: '.$data['old']['database_user'].' to '.$data['new']['database_user'], LOGLEVEL_DEBUG);
			}

			if($data['new']['database_password'] != $data['old']['database_password'] && $data['new']['database_password'] != '') {
				$result = $app->db->queryOneRecord("SELECT VERSION() as version");
				$dbversion = $result['version'];

				// mariadb or mysql < 5.7
				if(stripos($dbversion, 'mariadb') !== false || version_compare($dbversion, '5.7', '<')) {
					$query = sprintf("SET PASSWORD FOR '%s'@'%s' = '%s'",
						$link->escape_string($data['new']['database_user']),
						$db_host,
						$link->escape_string($data['new']['database_password']));
					$link->query($query);
				}
				// mysql >= 5.7
				else {
					$query = sprintf("ALTER USER IF EXISTS '%s'@'%s' IDENTIFIED WITH mysql_native_password AS '%s'",
						$link->escape_string($data['new']['database_user']),
						$db_host,
						$link->escape_string($data['new']['database_password']));
					$link->query($query);
				}
				$app->log('Changing MySQL user password for: ' . $data['new']['database_user'] . '@' . $db_host, LOGLEVEL_DEBUG);
			}
		}

		$link->close();

	}

	function db_user_delete($event_name, $data) {
		global $app, $conf;

		if(!include ISPC_LIB_PATH.'/mysql_clientdb.conf') {
			$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf', LOGLEVEL_ERROR);
			return;
		}

		//* Connect to the database
		$link = new mysqli($clientdb_host, $clientdb_user, $clientdb_password);
		if ($link->connect_error) {
			$app->log('Unable to connect to mysql'.$link->connect_error, LOGLEVEL_ERROR);
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
				$app->log('Dropping MySQL user: '.$data['old']['database_user'], LOGLEVEL_DEBUG);
			}
		}

		$link->close();
	}

} // end class

?>
