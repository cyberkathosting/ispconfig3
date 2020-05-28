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
		$app->plugins->registerAction('backup_delete', $this->plugin_name, 'backup_action');
		$app->plugins->registerAction('backup_web_files', $this->plugin_name, 'make_backup_callback');
		$app->plugins->registerAction('backup_database', $this->plugin_name, 'make_backup_callback');
		//$app->plugins->registerAction('backup_download_mail', $this->plugin_name, 'backup_action_mail');
		$app->plugins->registerAction('backup_restore_mail', $this->plugin_name, 'backup_action_mail');
		$app->plugins->registerAction('backup_delete_mail', $this->plugin_name, 'backup_action_mail');
	}

	//* Do a backup action
	public function backup_action($action_name, $data) {
		global $app, $conf;

		$backup_id = intval($data);
		$backup = $app->dbmaster->queryOneRecord("SELECT * FROM web_backup WHERE backup_id = ?", $backup_id);

		if(is_array($backup)) {

			$app->uses('ini_parser,file,getconf,system');
			$app->load("backup");

			$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $backup['parent_domain_id']);
			$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
			$backup_dir = trim($server_config['backup_dir']);
			if($backup_dir == '') return;
			$backup_dir .= '/web'.$web['domain_id'];
			
			$backup_dir_is_ready = true;
            //* mount backup directory, if necessary
			if (!backup::mount_backup_dir($conf['server_id']))
				$backup_dir_is_ready = false;

			if($backup_dir_is_ready){
				//* Make backup available for download
				if($action_name == 'backup_download') {
					//* Copy the backup file to the backup folder of the website
					if(file_exists($backup_dir.'/'.$backup['filename']) && file_exists($web['document_root'].'/backup/') && !stristr($backup_dir.'/'.$backup['filename'], '..') && !stristr($backup_dir.'/'.$backup['filename'], 'etc')) {
						copy($backup_dir.'/'.$backup['filename'], $web['document_root'].'/backup/'.$backup['filename']);
						chgrp($web['document_root'].'/backup/'.$backup['filename'], $web['system_group']);
						chown($web['document_root'].'/backup/'.$backup['filename'], $web['system_user']);
						chmod($web['document_root'].'/backup/'.$backup['filename'],0600);
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
						$app->system->exec_safe("tar -xzvf ? --directory=?", $backup_dir.'/'.$backup['filename'], $extracted);
						$restore_directory = $extracted."/".$dump_directory."/".$db_name;

						// mongorestore -h 127.0.0.1 -u root -p 123456 --authenticationDatabase admin -d c1debug --drop ./toRestore
						$command = "mongorestore -h 127.0.0.1 --port 27017 -u root -p 123456 --authenticationDatabase admin -d ? --drop ?";
						$app->system->exec_safe($command, $db_name, $restore_directory);
						$app->system->exec_safe("rm -rf ?", $extracted."/".$dump_directory);
					}

					unset($clientdb_host);
					unset($clientdb_user);
					unset($clientdb_password);
					$app->log('Restored MongoDB backup '.$backup_dir.'/'.$backup['filename'], LOGLEVEL_DEBUG);
				}

				//* Restore a mysql backup
				if($action_name == 'backup_restore' && $backup['backup_type'] == 'mysql') {
					backup::restoreBackupDatabase($backup['backup_format'], trim($backup['backup_password']), $backup_dir, $backup['filename'], $backup['backup_mode'], $backup['backup_type']);
				}

				//* Restore a web backup
				if($action_name == 'backup_restore' && $backup['backup_type'] == 'web') {
					backup::restoreBackupWebFiles($backup['backup_format'], trim($backup['backup_password']), $backup_dir, $backup['filename'], $backup['backup_mode'], $backup['backup_type'], $web['document_root'], $web['system_user'], $web['system_group']);
				}
				
				if($action_name == 'backup_delete') {
					if(file_exists($backup_dir.'/'.$backup['filename']) && !stristr($backup_dir.'/'.$backup['filename'], '..') && !stristr($backup_dir.'/'.$backup['filename'], 'etc')) {
						unlink($backup_dir.'/'.$backup['filename']);
						
						$sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ? AND filename = ?";
						$app->db->query($sql, $conf['server_id'], $backup['parent_domain_id'], $backup['filename']);
						if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'], $backup['parent_domain_id'], $backup['filename']);
						$app->log('unlink '.$backup_dir.'/'.$backup['filename'], LOGLEVEL_DEBUG);
					}
				}

				backup::unmount_backup_dir($conf['server_id']);
			} else {
				$app->log('Backup directory not ready.', LOGLEVEL_DEBUG);
			}
		} else {
			$app->log('No backup with ID '.$backup_id.' found.', LOGLEVEL_DEBUG);
		}

		return 'ok';
	}

	/**
	 * Makes a backup of website files or database, it's triggered by action and required for the plugin to work.
	 * Please, don't call this function directly unless you know what you're doing.
	 * @param string $action_name is the backup source: web files or database
	 * @param string|int $domain_id
	 * @return string
	 * @author Ramil Valitov <ramilvalitov@gmail.com>
	 * @uses backup_plugin::run_backup() recommended to use if you need to make backups
	 */
	public function make_backup_callback($action_name, $domain_id)
	{
		global $app;

		//Load backup class
		$app->load('backup');
		$app->log('Triggered backup routine for domain id ' . $domain_id . ', action name ' . $action_name, LOGLEVEL_DEBUG);

		switch ($action_name) {
			case 'backup_web_files':
				backup::run_backup($domain_id, 'web', "manual");
				break;
			case 'backup_database':
				backup::run_backup($domain_id, 'mysql', "manual");
				break;
			default:
				$app->log('Unknown backup action name: ' . $action_name, LOGLEVEL_ERROR);
				break;
		}
		return 'ok';
	}

	//* Restore a mail backup - florian@schaal-24.de
	public function backup_action_mail($action_name, $data) {
		global $app, $conf;
	
		$backup_id = intval($data);
		$mail_backup = $app->dbmaster->queryOneRecord("SELECT * FROM mail_backup WHERE backup_id = ?", $backup_id);
	
		if (is_array($mail_backup)) {
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
				if (!is_dir($record['maildir'])) {
					mkdir($record['maildir'], 0700); //* never create the full path
					chown($record['maildir'], $mail_config['mailuser_name']);
					chgrp($record['maildir'], $mail_config['mailuser_group']);
				}
			
				if ($action_name == 'backup_restore_mail') {
					if(file_exists($mail_backup_file) && $record['homedir'] != '' && $record['homedir'] != '/' && !stristr($mail_backup_file,'..') && !stristr($mail_backup_file,'etc') && $mail_config['homedir_path'] == $record['homedir'] && is_dir($domain_dir) && is_dir($record['maildir'])) {
						if ($record['maildir_format'] == 'mdbox') {
							$retval = -1;
							// First unzip backupfile to local backup-folder
							if($mail_backup['backup_mode'] == 'userzip') {
								copy($mail_backup_file, $record['maildir'].'/'.$mail_backup['filename']);
								chgrp($record['maildir'].'/'.$mail_backup['filename'], $mail_config['mailuser_group']);
								$command = 'sudo -u ? unzip -qq -o ? -d ? 2> /dev/null';
								$app->system->exec_safe($command, $mail_config['mailuser_name'], $record['maildir'].'/'.$mail_backup['filename'], $record['maildir']);
								$retval = $app->system->last_exec_retcode();
								unlink($record['maildir'].'/'.$mail_backup['filename']);
							} elseif($mail_backup['backup_mode'] == 'rootgz') {
								$command='tar xfz ? --directory ?';
								$app->system->exec_safe($command, $mail_backup_file, $record['maildir']);
								$retval = $app->system->last_exec_retcode();
							}
							
							if($retval == 0) {
								// Now import backup-mailbox into special backup-folder
								$backupname = "backup-".date("Y-m-d", $mail_backup['tstamp']);
								$app->system->exec_safe("doveadm mailbox create -u ? ?", $record["email"], $backupname);
								$app->system->exec_safe("doveadm import -u ? mdbox:? ? all", $record["email"], $record['maildir']."/backup", $backupname);
								$app->system->exec_safe("for f in `doveadm mailbox list -u ? ?*`; do doveadm mailbox subscribe -u ? \$f; done", $record["email"], $backupname, $record["email"]);
								$retval = $app->system->last_exec_retcode();
								$app->system->exec_safe('rm -rf ?', $record['maildir'].'/backup');
							}
							
							if($retval == 0){
								$app->log('Restored Mail backup '.$mail_backup_file,LOGLEVEL_DEBUG);
							} else {
								// cleanup
								if (file_exists($record['maildir'].'/'.$mail_backup['filename'])) unlink($record['maildir'].'/'.$mail_backup['filename']);
								if (file_exists($record['maildir']."/backup")) $app->system->exec_safe('rm -rf ?', $record['maildir']."/backup");
								
								$app->log('Unable to restore Mail backup '.$mail_backup_file.' '.$tmp_output,LOGLEVEL_ERROR);
							}
						}
						else {
							if($mail_backup['backup_mode'] == 'userzip') {
								copy($mail_backup_file, $domain_dir.'/'.$mail_backup['filename']);
								chgrp($domain_dir.'/'.$mail_backup['filename'], $mail_config['mailuser_group']);
								$command = 'sudo -u ? unzip -qq -o ? -d ? 2> /dev/null';
								$app->system->exec_safe($command, $mail_config['mailuser_name'], $domain_dir.'/'.$mail_backup['filename'], $domain_dir);
								$retval = $app->system->last_exec_retcode();
								$tmp_output = $app->system->last_exec_out();
								unlink($domain_dir.'/'.$mail_backup['filename']);
								if($retval == 0){
									$app->log('Restored Mail backup '.$mail_backup_file,LOGLEVEL_DEBUG);
								} else {
									$app->log('Unable to restore Mail backup '.$mail_backup_file.' '.$tmp_output,LOGLEVEL_ERROR);
								}
							}
							if($mail_backup['backup_mode'] == 'rootgz') {
								$command='tar xfz ? --directory ?';
								$app->system->exec_safe($command, $mail_backup_file, $domain_dir);
								$retval = $app->system->last_exec_retcode();
								$tmp_output = $app->system->last_exec_out();
								if($retval == 0){
									$app->log('Restored Mail backup '.$mail_backup_file,LOGLEVEL_DEBUG);
								} else {
									$app->log('Unable to restore Mail backup '.$mail_backup_file.' '.$tmp_output,LOGLEVEL_ERROR);
								}
							}
						}
					}
				}
				
				if($action_name == 'backup_delete_mail') {
					if(file_exists($mail_backup_file) && !stristr($mail_backup_file, '..') && !stristr($mail_backup_file, 'etc')) {
						unlink($mail_backup_file);
						$sql = "DELETE FROM mail_backup WHERE server_id = ? AND parent_domain_id = ? AND filename = ?";
						$app->db->query($sql, $conf['server_id'], $mail_backup['parent_domain_id'], $mail_backup['filename']);
						if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'], $mail_backup['parent_domain_id'], $mail_backup['filename']);
						$app->log('unlink '.$backup_dir.'/'.$mail_backup['filename'], LOGLEVEL_DEBUG);
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

