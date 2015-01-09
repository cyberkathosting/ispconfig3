<?php

/*
Copyright (c) 2012, Till Brehm, ISPConfig UG
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

class backup_plugin {

	var $plugin_name = 'backup_plugin';
	var $class_name  = 'backup_plugin';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	public function onInstall() {
		global $conf;

		return true;

	}


	/*
	 	This function is called when the plugin is loaded
	*/

	public function onLoad() {
		global $app;

		//* Register for actions
		$app->plugins->registerAction('backup_download', $this->plugin_name, 'backup_action');
		$app->plugins->registerAction('backup_restore', $this->plugin_name, 'backup_action');

	}

	//* Do a backup action
	public function backup_action($action_name, $data) {
		global $app, $conf;

		$backup_id = intval($data);
		$backup = $app->dbmaster->queryOneRecord("SELECT * FROM web_backup WHERE backup_id = $backup_id");

		if(is_array($backup)) {

			$app->uses('ini_parser,file,getconf,system');

			$web = $app->dbmaster->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".$backup['parent_domain_id']);
			$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
			$backup_dir = $server_config['backup_dir'].'/web'.$web['domain_id'];
			
			//* mount backup directory, if necessary
			/*
			$backup_dir_is_ready = true;
			$server_config['backup_dir_mount_cmd'] = trim($server_config['backup_dir_mount_cmd']);
			if($server_config['backup_dir_is_mount'] == 'y' && $server_config['backup_dir_mount_cmd'] != ''){
				if(!$app->system->is_mounted($server_config['backup_dir'])){
					exec(escapeshellcmd($server_config['backup_dir_mount_cmd']));
					sleep(1);
					if(!$app->system->is_mounted($server_config['backup_dir'])) $backup_dir_is_ready = false;
				}
			}*/
			$backup_dir_is_ready = true;
			$backup_dir_mount_cmd = '/usr/local/ispconfig/server/scripts/backup_dir_mount.sh';
			if(	$server_config['backup_dir_is_mount'] == 'y' && 
				is_file($backup_dir_mount_cmd) && 
				is_executable($backup_dir_mount_cmd) &&
				fileowner($backup_dir_mount_cmd) === 0
			){
				if(!$app->system->is_mounted($backup_dir)){
					exec($backup_dir_mount_cmd);
					sleep(1);
					if(!$app->system->is_mounted($server_config['backup_dir'])) $backup_dir_is_ready = false;
				}
			}

			if($backup_dir_is_ready){
				//* Make backup available for download
				if($action_name == 'backup_download') {
					//* Copy the backup file to the backup folder of the website
					if(file_exists($backup_dir.'/'.$backup['filename']) && file_exists($web['document_root'].'/backup/') && !stristr($backup_dir.'/'.$backup['filename'], '..') && !stristr($backup_dir.'/'.$backup['filename'], 'etc')) {
						copy($backup_dir.'/'.$backup['filename'], $web['document_root'].'/backup/'.$backup['filename']);
						chgrp($web['document_root'].'/backup/'.$backup['filename'], $web['system_group']);
						$app->log('cp '.$backup_dir.'/'.$backup['filename'].' '.$web['document_root'].'/backup/'.$backup['filename'], LOGLEVEL_DEBUG);
					}
				}

				//* Restore a mysql backup
				if($action_name == 'backup_restore' && $backup['backup_type'] == 'mysql') {
					//* Load sql dump into db
					include 'lib/mysql_clientdb.conf';

					if(file_exists($backup_dir.'/'.$backup['filename'])) {
						//$parts = explode('_',$backup['filename']);
						//$db_name = $parts[1];
						preg_match('@^db_(.+)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.sql\.gz$@', $backup['filename'], $matches);
						$db_name = $matches[1];
						$command = "gunzip --stdout ".escapeshellarg($backup_dir.'/'.$backup['filename'])." | mysql -h '".escapeshellcmd($clientdb_host)."' -u '".escapeshellcmd($clientdb_user)."' -p'".escapeshellcmd($clientdb_password)."' '".$db_name."'";
						exec($command);
					}
					unset($clientdb_host);
					unset($clientdb_user);
					unset($clientdb_password);
					$app->log('Restored MySQL backup '.$backup_dir.'/'.$backup['filename'], LOGLEVEL_DEBUG);
				}

				//* Restore a web backup
				if($action_name == 'backup_restore' && $backup['backup_type'] == 'web') {
					if($backup['backup_mode'] == 'userzip') {
						if(file_exists($backup_dir.'/'.$backup['filename']) && $web['document_root'] != '' && $web['document_root'] != '/' && !stristr($backup_dir.'/'.$backup['filename'], '..') && !stristr($backup_dir.'/'.$backup['filename'], 'etc')) {
							if(file_exists($web['document_root'].'/backup/'.$backup['filename'])) rename($web['document_root'].'/backup/'.$backup['filename'], $web['document_root'].'/backup/'.$backup['filename'].'.bak');
							copy($backup_dir.'/'.$backup['filename'], $web['document_root'].'/backup/'.$backup['filename']);
							chgrp($web['document_root'].'/backup/'.$backup['filename'], $web['system_group']);
							//chown($web['document_root'].'/backup/'.$backup['filename'],$web['system_user']);
							$command = 'sudo -u '.escapeshellarg($web['system_user']).' unzip -qq -o  '.escapeshellarg($web['document_root'].'/backup/'.$backup['filename']).' -d '.escapeshellarg($web['document_root']).' 2> /dev/null';
							exec($command);
							unlink($web['document_root'].'/backup/'.$backup['filename']);
							if(file_exists($web['document_root'].'/backup/'.$backup['filename'].'.bak')) rename($web['document_root'].'/backup/'.$backup['filename'].'.bak', $web['document_root'].'/backup/'.$backup['filename']);
							$app->log('Restored Web backup '.$backup_dir.'/'.$backup['filename'], LOGLEVEL_DEBUG);
						}
					}
					if($backup['backup_mode'] == 'rootgz') {
						if(file_exists($backup_dir.'/'.$backup['filename']) && $web['document_root'] != '' && $web['document_root'] != '/' && !stristr($backup_dir.'/'.$backup['filename'], '..') && !stristr($backup_dir.'/'.$backup['filename'], 'etc')) {
							$command = 'tar xzf '.escapeshellarg($backup_dir.'/'.$backup['filename']).' --directory '.escapeshellarg($web['document_root']);
							exec($command);
							$app->log('Restored Web backup '.$backup_dir.'/'.$backup['filename'], LOGLEVEL_DEBUG);
						}
					}
				}
			} else {
				$app->log('Backup directory not ready.', LOGLEVEL_DEBUG);
			}

		} else {
			$app->log('No backup with ID '.$backup_id.' found.', LOGLEVEL_DEBUG);
		}

		return 'ok';
	}

} // end class

?>
