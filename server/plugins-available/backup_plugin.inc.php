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
		//$app->plugins->registerAction('backup_download_mail', $this->plugin_name, 'backup_action_mail');
		$app->plugins->registerAction('backup_restore_mail', $this->plugin_name, 'backup_action_mail');
		
	}

	//* Do a backup action
	public function backup_action($action_name, $data) {
		global $app, $conf;

		$backup_id = intval($data);
		$backup = $app->dbmaster->queryOneRecord("SELECT * FROM web_backup WHERE backup_id = ?", $backup_id);

		if(is_array($backup)) {

			$app->uses('ini_parser,file,getconf,system');

			$web = $app->dbmaster->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $backup['parent_domain_id']);
			$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
			$backup_dir = $server_config['backup_dir'].'/web'.$web['domain_id'];
			
			$backup_dir_is_ready = true;
            //* mount backup directory, if necessary
            if( $server_config['backup_dir_is_mount'] == 'y' && !$app->system->mount_backup_dir($backup_dir) ) $backup_dir_is_ready = false;

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

				//* Restore a MongoDB backup
				if($action_name == 'backup_restore' && $backup['backup_type'] == 'mongodb') {
					if(file_exists($backup_dir.'/'.$backup['filename'])) {
						//$parts = explode('_',$backup['filename']);
						//$db_name = $parts[1];
						preg_match('@^db_(.+)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.tar\.gz$@', $backup['filename'], $matches);
						$db_name = $matches[1];

						// extract tar.gz archive
						$dump_directory = str_replace(".tar.gz", "", $backup['filename']);
						$extracted = "/usr/local/ispconfig/server/temp";
						exec("tar -xzvf ".escapeshellarg($backup_dir.'/'.$backup['filename'])." --directory=".escapeshellarg($extracted));
						$restore_directory = $extracted."/".$dump_directory."/".$db_name;

						// mongorestore -h 127.0.0.1 -u root -p 123456 --authenticationDatabase admin -d c1debug --drop ./toRestore
						$command = "mongorestore -h 127.0.0.1 --port 27017 -u root -p 123456 --authenticationDatabase admin -d ".$db_name." --drop ".escapeshellarg($restore_directory);
						exec($command);
						exec("rm -rf ".escapeshellarg($extracted."/".$dump_directory));
					}

					unset($clientdb_host);
					unset($clientdb_user);
					unset($clientdb_password);
					$app->log('Restored MongoDB backup '.$backup_dir.'/'.$backup['filename'], LOGLEVEL_DEBUG);
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
				if( $server_config['backup_dir_is_mount'] == 'y' ) $app->system->umount_backup_dir($backup_dir);
			} else {
				$app->log('Backup directory not ready.', LOGLEVEL_DEBUG);
			}
		} else {
			$app->log('No backup with ID '.$backup_id.' found.', LOGLEVEL_DEBUG);
		}

		return 'ok';
	}

	//* Restore a mail backup - florian@schaal-24.de
	public function backup_action_mail($action_name, $data) {
		global $app, $conf;
	
		$backup_id = intval($data);
		$mail_backup = $app->dbmaster->queryOneRecord("SELECT * FROM mail_backup WHERE backup_id = ?", $backup_id);
	
		if (is_array($mail_backup) && $action_name == 'backup_restore_mail') {
			$app->uses('ini_parser,file,getconf');
	
			$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
			$backup_dir = $server_config['backup_dir'];
			$backup_dir_is_ready = true;
	
			//* mount backup directory, if necessary
			if( $server_config['backup_dir_is_mount'] == 'y' && !$app->system->mount_backup_dir($backup_dir) ) $backup_dir_is_ready = false;
	
			if($backup_dir_is_ready){
				$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
				$domain_rec = $app->db->queryOneRecord("SELECT * FROM mail_domain WHERE domain_id = ?", $mail_backup['parent_domain_id']);
			
				$backup_dir = $server_config['backup_dir'].'/mail'.$domain_rec['domain_id'];
				$mail_backup_file = $backup_dir.'/'.$mail_backup['filename'];
			
				$sql = "SELECT * FROM mail_user WHERE server_id = ? AND mailuser_id = ?";
				$record = $app->db->queryOneRecord($sql, $conf['server_id'], $mail_backup['mailuser_id']);
			
				//* strip mailbox from maildir
				$domain_dir=explode('/',$record['maildir']);
				$_temp=array_pop($domain_dir);unset($_temp);
				$domain_dir=implode('/',$domain_dir);
			
				if(!is_dir($domain_dir)) {
					mkdir($domain_dir, 0700); //* never create the full path
					chown($domain_dir, $mail_config['mailuser_name']);
					chgrp($domain_dir, $mail_config['mailuser_group']);
				}
			
				if(file_exists($mail_backup_file) && $record['homedir'] != '' && $record['homedir'] != '/' && !stristr($mail_backup_file,'..') && !stristr($mail_backup_file,'etc') && $mail_config['homedir_path'] == $record['homedir'] && is_dir($domain_dir)) {
					if($mail_backup['backup_mode'] == 'userzip') {
						copy($mail_backup_file, $domain_dir.'/'.$mail_backup['filename']);
						chgrp($domain_dir.'/'.$mail_backup['filename'], $mail_config['mailuser_group']);
						$command = 'sudo -u '.$mail_config['mailuser_name'].' unzip -qq -o  '.escapeshellarg($domain_dir.'/'.$mail_backup['filename']).' -d '.escapeshellarg($domain_dir).' 2> /dev/null';
						exec($command,$tmp_output, $retval);
						unlink($domain_dir.'/'.$mail_backup['filename']);
						if($retval == 0){
							$app->log('Restored Mail backup '.$mail_backup_file,LOGLEVEL_DEBUG);
						} else {
							$app->log('Unable to restore Mail backup '.$mail_backup_file.' '.$tmp_output,LOGLEVEL_ERROR);
						}
					}
					if($mail_backup['backup_mode'] == 'rootgz') {
						$command='tar xfz '.escapeshellarg($mail_backup_file).' --directory '.escapeshellarg($domain_dir);
						exec($command,$tmp_output, $retval);
						if($retval == 0){
							$app->log('Restored Mail backup '.$mail_backup_file,LOGLEVEL_DEBUG);
						} else {
							$app->log('Unable to restore Mail backup '.$mail_backup_file.' '.$tmp_output,LOGLEVEL_ERROR);
						}
					}
				}
				if( $server_config['backup_dir_is_mount'] == 'y' ) $app->system->umount_backup_dir($backup_dir);
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

