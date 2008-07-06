<?php

/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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
	
		
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		//* Mailboxes
		$app->plugins->registerEvent('database_insert',$this->plugin_name,'db_insert');
		$app->plugins->registerEvent('database_update',$this->plugin_name,'db_update');
		$app->plugins->registerEvent('database_delete',$this->plugin_name,'db_delete');
		
		
	}
	
	
	function db_insert($event_name,$data) {
		global $app, $conf;
		
		if($data["new"]["type"] == 'mysql') {
			if(!include_once(ISPC_LIB_PATH.'/mysql_clientdb.conf')) {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf',LOGLEVEL_ERROR);
			}
		
			//* Connect to the database
			$link = mysql_connect($clientdb_host, $clientdb_user, $clientdb_password);
			if (!$link) {
				$app->log('Unable to connect to the database'.mysql_error($link),LOGLEVEL_ERROR);
				return;
			}
		
			//* Create the new database
			if (mysql_query('CREATE DATABASE '.addslashes($data["new"]["database_name"]),$link)) {
				$app->log('Created MySQL database: '.$data["new"]["database_name"],LOGLEVEL_DEBUG);
			} else {
				$app->log('Unable to connect to the database'.mysql_error($link),LOGLEVEL_ERROR);
			}
			
			// Create the database user
			if($data["new"]["remote_access"] == 'y') {
			 	$db_host = '%';
			} else {
				$db_host = 'localhost';
			}
			
			mysql_query("GRANT ALL ON ".addslashes($data["new"]["database_name"]).".* TO '".addslashes($data["new"]["database_user"])."'@'$db_host' IDENTIFIED BY '".addslashes($data["new"]["database_password"])."';",$link);
			//echo "GRANT ALL ON ".addslashes($data["new"]["database_name"]).".* TO '".addslashes($data["new"]["database_user"])."'@'$db_host' IDENTIFIED BY '".addslashes($data["new"]["database_password"])."';";
			
			mysql_query("FLUSH PRIVILEGES;",$link);
			mysql_close($link);
		}
	}
	
	function db_update($event_name,$data) {
		global $app, $conf;
		
		if($data["new"]["type"] == 'mysql') {
			if(!include_once(ISPC_LIB_PATH.'/mysql_clientdb.conf')) {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf',LOGLEVEL_ERROR);
				return;
			}
		
			//* Connect to the database
			$link = mysql_connect($clientdb_host, $clientdb_user, $clientdb_password);
			if (!$link) {
				$app->log('Unable to connect to the database'.mysql_error($link),LOGLEVEL_ERROR);
			}
			
			//* Rename User
			if($data["new"]["database_user"] != $data["old"]["database_user"]) {
				mysql_query("RENAME USER '".addslashes($data["old"]["database_user"])."' TO '".addslashes($data["new"]["database_user"])."'",$link);
				$app->log('Renaming mysql user: '.$data["old"]["database_user"].' to '.$data["new"]["database_user"],LOGLEVEL_DEBUG);
			}
			
			//* Remote access option has changed.
			if($data["new"]["remote_access"] != $data["old"]["remote_access"]) {
				if($data["new"]["remote_access"] == 'y') {
					mysql_query("UPDATE mysql.user SET Host = '%' WHERE User = '".addslashes($data["new"]["database_user"])."' and Host = 'localhost';",$link);
					mysql_query("UPDATE mysql.db SET Host = '%' WHERE User = '".addslashes($data["new"]["database_user"])."' and Host = 'localhost';",$link);
				} else {
					mysql_query("UPDATE mysql.user SET Host = 'localhost' WHERE User = '".addslashes($data["new"]["database_user"])."' and Host = '%';",$link);
					mysql_query("UPDATE mysql.db SET Host = 'localhost' WHERE User = '".addslashes($data["new"]["database_user"])."' and Host = '%';",$link);
				}
				$app->log('Changing mysql remote access priveliges for database: '.$data["new"]["database_name"],LOGLEVEL_DEBUG);
			}
			
			//* Get the db host setting for the access priveliges
			if($data["new"]["remote_access"] == 'y') {
			 	$db_host = '%';
			} else {
				$db_host = 'localhost';
			}
			
			/*
			//* Rename database
			if($data["new"]["database_name"] != $data["old"]["database_name"]) {
				mysql_query("",$link);
			}
			*/
			
			//* Change password
			if($data["new"]["database_password"] != $data["old"]["database_password"]) {
				mysql_query("SET PASSWORD FOR '".addslashes($data["new"]["database_user"])."'@'$db_host' = PASSWORD('".addslashes($data["new"]["database_password"])."');",$link);
				$app->log('Changing mysql user password for: '.$data["new"]["database_user"],LOGLEVEL_DEBUG);
			}
			
			mysql_query("FLUSH PRIVILEGES;",$link);
			mysql_close($link);
		}
		
	}
	
	function db_delete($event_name,$data) {
		global $app, $conf;
		
		if($data["old"]["type"] == 'mysql') {
			if(!include_once(ISPC_LIB_PATH.'/mysql_clientdb.conf')) {
				$app->log('Unable to open'.ISPC_LIB_PATH.'/mysql_clientdb.conf',LOGLEVEL_ERROR);
				return;
			}
		
			//* Connect to the database
			$link = mysql_connect($clientdb_host, $clientdb_user, $clientdb_password);
			if (!$link) {
				$app->log('Unable to connect to the database'.mysql_error($link),LOGLEVEL_ERROR);
			}
			
			//* Get the db host setting for the access priveliges
			if($data["old"]["remote_access"] == 'y') {
			 	$db_host = '%';
			} else {
				$db_host = 'localhost';
			}
			
			mysql_query("DROP USER '".addslashes($data["old"]["database_user"])."'@'$db_host';",$link);
			$app->log('Dropping mysql user: '.$data["old"]["database_user"],LOGLEVEL_DEBUG);
			
			mysql_query('DROP DATABASE '.addslashes($data["old"]["database_name"]),$link);
			$app->log('Dropping mysql database: '.$data["old"]["database_name"],LOGLEVEL_DEBUG);
			
			
			mysql_query("FLUSH PRIVILEGES;",$link);
			mysql_close($link);
		}
		
		
	}
	
	
	

} // end class

?>