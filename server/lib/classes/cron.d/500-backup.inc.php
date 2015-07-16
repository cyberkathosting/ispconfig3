<?php

/*
Copyright (c) 2013, Marius Cramer, pixcept KG
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

class cronjob_backup extends cronjob {

	// job schedule
	protected $_schedule = '0 0 * * *';

	/* this function is optional if it contains no custom code */
	public function onPrepare() {
		global $app;

		parent::onPrepare();
	}

	/* this function is optional if it contains no custom code */
	public function onBeforeRun() {
		global $app;

		return parent::onBeforeRun();
	}

	public function onRunJob() {
		global $app, $conf;

		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
		$global_config = $app->getconf->get_global_config('sites');
		$backup_dir = $server_config['backup_dir'];
		$backup_mode = $server_config['backup_mode'];
		if($backup_mode == '') $backup_mode = 'userzip';

		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		$http_server_user = $web_config['user'];

		if($backup_dir != '') {

			if(isset($server_config['backup_dir_ftpread']) && $server_config['backup_dir_ftpread'] == 'y') {
				$backup_dir_permissions = 0755;
			} else {
				$backup_dir_permissions = 0750;
			}

			if(!is_dir($backup_dir)) {
				mkdir(escapeshellcmd($backup_dir), $backup_dir_permissions, true);
			} else {
				chmod(escapeshellcmd($backup_dir), $backup_dir_permissions);
			}
            $run_backups = true;
            //* mount backup directory, if necessary
            if( $server_config['backup_dir_is_mount'] == 'y' && !$app->system->mount_backup_dir($backup_dir) ) $run_backups = false;
			if($run_backups){
				$web_array = array();
				
				//* backup only active domains
				$sql = "SELECT * FROM web_domain WHERE server_id = ? AND (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias') AND active = 'y'";
				$records = $app->db->queryAllRecords($sql, $conf['server_id']);
				if(is_array($records)) {
					foreach($records as $rec) {

						//* Do the website backup
						if($rec['backup_interval'] == 'daily' or ($rec['backup_interval'] == 'weekly' && date('w') == 0) or ($rec['backup_interval'] == 'monthly' && date('d') == '01')) {

							$web_path = $rec['document_root'];
							$web_user = $rec['system_user'];
							$web_group = $rec['system_group'];
							$web_id = $rec['domain_id'];
							if(!in_array($web_id, $web_array)) $web_array[] = $web_id;
							$web_backup_dir = $backup_dir.'/web'.$web_id;
							if(!is_dir($web_backup_dir)) mkdir($web_backup_dir, 0750);
							chmod($web_backup_dir, 0750);
							//if(isset($server_config['backup_dir_ftpread']) && $server_config['backup_dir_ftpread'] == 'y') {
							chown($web_backup_dir, $rec['system_user']);
							chgrp($web_backup_dir, $rec['system_group']);
							/*} else {
								chown($web_backup_dir, 'root');
								chgrp($web_backup_dir, 'root');
							}*/
						
							$backup_excludes = '';
							$b_excludes = explode(',', trim($rec['backup_excludes']));
							if(is_array($b_excludes) && !empty($b_excludes)){
								foreach($b_excludes as $b_exclude){
									$b_exclude = trim($b_exclude);
									if($b_exclude != ''){
										$backup_excludes .= ' --exclude='.escapeshellarg($b_exclude);
									}
								}
							}
						
							if($backup_mode == 'userzip') {
								//* Create a .zip backup as web user and include also files owned by apache / nginx user
								$web_backup_file = 'web'.$web_id.'_'.date('Y-m-d_H-i').'.zip';
								exec('cd '.escapeshellarg($web_path).' && sudo -u '.escapeshellarg($web_user).' find . -group '.escapeshellarg($web_group).' -print 2> /dev/null | zip -b /tmp --exclude=backup\*'.$backup_excludes.' --symlinks '.escapeshellarg($web_backup_dir.'/'.$web_backup_file).' -@', $tmp_output, $retval);
								if($retval == 0 || $retval == 12) exec('cd '.escapeshellarg($web_path).' && sudo -u '.escapeshellarg($web_user).' find . -user '.escapeshellarg($http_server_user).' -print 2> /dev/null | zip -b /tmp --exclude=backup\*'.$backup_excludes.' --update --symlinks '.escapeshellarg($web_backup_dir.'/'.$web_backup_file).' -@', $tmp_output, $retval);
							} else {
								//* Create a tar.gz backup as root user
								$web_backup_file = 'web'.$web_id.'_'.date('Y-m-d_H-i').'.tar.gz';
								exec('tar pczf '.escapeshellarg($web_backup_dir.'/'.$web_backup_file).' --exclude=backup\*'.$backup_excludes.' --directory '.escapeshellarg($web_path).' .', $tmp_output, $retval);
							}
							if($retval == 0 || ($backup_mode != 'userzip' && $retval == 1) || ($backup_mode == 'userzip' && $retval == 12)) { // tar can return 1, zip can return 12(due to harmless warings) and still create valid backups  
								if(is_file($web_backup_dir.'/'.$web_backup_file)){
									$backupusername = ($global_config['backups_include_into_web_quota'] == 'y') ? $web_user : 'root';
									$backupgroup = ($global_config['backups_include_into_web_quota'] == 'y') ? $web_group : 'root';
									chown($web_backup_dir.'/'.$web_backup_file, $backupusername);
									chgrp($web_backup_dir.'/'.$web_backup_file, $backupgroup);
									chmod($web_backup_dir.'/'.$web_backup_file, 0750);

									//* Insert web backup record in database
									$filesize = filesize($web_backup_dir.'/'.$web_backup_file);
									$sql = "INSERT INTO web_backup (server_id, parent_domain_id, backup_type, backup_mode, tstamp, filename, filesize) VALUES (?, ?, ?, ?, ?, ?, ?)";
									$app->db->query($sql, $conf['server_id'], $web_id, 'web', $backup_mode, time(), $web_backup_file, $filesize);
									if($app->db->dbHost != $app->dbmaster->dbHost) 
										$app->dbmaster->query($sql, $conf['server_id'], $web_id, 'web', $backup_mode, time(), $web_backup_file, $filesize);
									unset($filesize);
								}
							} else {
								if(is_file($web_backup_dir.'/'.$web_backup_file)) unlink($web_backup_dir.'/'.$web_backup_file);
							}

							//* Remove old backups
							$backup_copies = intval($rec['backup_copies']);

							$dir_handle = dir($web_backup_dir);
							$files = array();
							while (false !== ($entry = $dir_handle->read())) {
								if($entry != '.' && $entry != '..' && substr($entry, 0, 3) == 'web' && is_file($web_backup_dir.'/'.$entry)) {
									$files[] = $entry;
								}
							}
							$dir_handle->close();

							rsort($files);

							for ($n = $backup_copies; $n <= 10; $n++) {
								if(isset($files[$n]) && is_file($web_backup_dir.'/'.$files[$n])) {
									$sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ? AND filename = ?";
									$app->db->query($sql, $conf['server_id'], $web_id, $files[$n]);
									if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'],  $web_id, $files[$n]);
									@unlink($web_backup_dir.'/'.$files[$n]);
								}
							}

							unset($files);
							unset($dir_handle);

							//* Remove backupdir symlink and create as directory instead
							$app->system->web_folder_protection($web_path, false);

							if(is_link($web_path.'/backup')) {
								unlink($web_path.'/backup');
							}
							if(!is_dir($web_path.'/backup')) {
								mkdir($web_path.'/backup');
								chown($web_path.'/backup', $rec['system_user']);
								chgrp($web_path.'/backup', $rec['system_group']);
							}

							$app->system->web_folder_protection($web_path, true);
						}

						/* If backup_interval is set to none and we have a
						backup directory for the website, then remove the backups */
						if($rec['backup_interval'] == 'none' || $rec['backup_interval'] == '') {
							$web_id = $rec['domain_id'];
							$web_user = $rec['system_user'];
							$web_backup_dir = realpath($backup_dir.'/web'.$web_id);
							if(is_dir($web_backup_dir)) {
								$dir_handle = opendir($web_backup_dir.'/');
								while ($file = readdir($dir_handle)) {
									if(!is_dir($file)) {
										unlink ("$web_backup_dir/"."$file");
									}
								}
							}
							$sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ?";
							$app->db->query($sql, $conf['server_id'], $web_id);
							if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'], $web_id);
						}
					}
				}

				$records = $app->db->queryAllRecords("SELECT * FROM web_database WHERE server_id = ? AND backup_interval != 'none' AND backup_interval != ''", $conf['server_id']);
				if(is_array($records)) {

					include 'lib/mysql_clientdb.conf';

					foreach($records as $rec) {

						//* Do the database backup
						if($rec['backup_interval'] == 'daily' or ($rec['backup_interval'] == 'weekly' && date('w') == 0) or ($rec['backup_interval'] == 'monthly' && date('d') == '01')) {

							$web_id = $rec['parent_domain_id'];
							if(!in_array($web_id, $web_array)) $web_array[] = $web_id;
							$db_backup_dir = $backup_dir.'/web'.$web_id;
							if(!is_dir($db_backup_dir)) mkdir($db_backup_dir, 0750);
							chmod($db_backup_dir, 0750);
							$backupusername = 'root';
							$backupgroup = 'root';
							if ($global_config['backups_include_into_web_quota'] == 'y') {
								$sql = "SELECT * FROM web_domain WHERE domain_id = ".$rec['parent_domain_id'];
								$webdomain = $app->db->queryOneRecord($sql);
								$backupusername = $webdomain['system_user'];
								$backupgroup = $webdomain['system_group'];
							}
							chown($db_backup_dir, $backupusername);
							chgrp($db_backup_dir, $backupgroup);

							//* Do the mysql database backup with mysqldump
							$db_id = $rec['database_id'];
							$db_name = $rec['database_name'];
							$db_backup_file = 'db_'.$db_name.'_'.date('Y-m-d_H-i').'.sql';
							//$command = "mysqldump -h '".escapeshellcmd($clientdb_host)."' -u '".escapeshellcmd($clientdb_user)."' -p'".escapeshellcmd($clientdb_password)."' -c --add-drop-table --create-options --quick --result-file='".$db_backup_dir.'/'.$db_backup_file."' '".$db_name."'";
							$command = "mysqldump -h ".escapeshellarg($clientdb_host)." -u ".escapeshellarg($clientdb_user)." -p".escapeshellarg($clientdb_password)." -c --add-drop-table --create-options --quick --max_allowed_packet=512M --result-file='".$db_backup_dir.'/'.$db_backup_file."' '".$db_name."'";
							exec($command, $tmp_output, $retval);

							//* Compress the backup with gzip
							if($retval == 0) exec("gzip -c '".escapeshellcmd($db_backup_dir.'/'.$db_backup_file)."' > '".escapeshellcmd($db_backup_dir.'/'.$db_backup_file).".gz'", $tmp_output, $retval);

							if($retval == 0){
								if(is_file($db_backup_dir.'/'.$db_backup_file.'.gz')){
									chmod($db_backup_dir.'/'.$db_backup_file.'.gz', 0750);
									chown($db_backup_dir.'/'.$db_backup_file.'.gz', fileowner($db_backup_dir));
									chgrp($db_backup_dir.'/'.$db_backup_file.'.gz', filegroup($db_backup_dir));

									//* Insert web backup record in database
									$filesize = filesize($db_backup_dir.'/'.$db_backup_file.'.gz');
									$sql = "INSERT INTO web_backup (server_id, parent_domain_id, backup_type, backup_mode, tstamp, filename, filesize) VALUES (?, ?, ?, ?, ?, ?, ?)";
									$app->db->query($sql, $conf['server_id'], $web_id, 'mysql', 'sqlgz', time(), $db_backup_file.'.gz', $filesize);
									if($app->db->dbHost != $app->dbmaster->dbHost) 
										$app->dbmaster->query($sql, $conf['server_id'], $web_id, 'mysql', 'sqlgz', time(), $db_backup_file.'.gz', $filesize);
									unset($filesize);
								}
							} else {
								if(is_file($db_backup_dir.'/'.$db_backup_file.'.gz')) unlink($db_backup_dir.'/'.$db_backup_file.'.gz');
							}
							//* Remove the uncompressed file
							if(is_file($db_backup_dir.'/'.$db_backup_file)) unlink($db_backup_dir.'/'.$db_backup_file);

							//* Remove old backups
							$backup_copies = intval($rec['backup_copies']);

							$dir_handle = dir($db_backup_dir);
							$files = array();
							while (false !== ($entry = $dir_handle->read())) {
								if($entry != '.' && $entry != '..' && preg_match('/^db_('.$db_name.')_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.sql.gz$/', $entry, $matches) && is_file($db_backup_dir.'/'.$entry)) {
									if(array_key_exists($matches[1], $files) == false) $files[$matches[1]] = array();
									$files[$matches[1]][] = $entry;
								}
							}
							$dir_handle->close();

							reset($files);
							foreach($files as $db_name => $filelist) {
								rsort($filelist);
								for ($n = $backup_copies; $n <= 10; $n++) {
									if(isset($filelist[$n]) && is_file($db_backup_dir.'/'.$filelist[$n])) {
										$sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ? AND filename = ?";
										$app->db->query($sql, $conf['server_id'], $web_id, $filelist[$n]);
										if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'], $web_id, $filelist[$n]);
										@unlink($db_backup_dir.'/'.$filelist[$n]);
									}
								}
							}

							unset($files);
							unset($dir_handle);
						}
					}

					unset($clientdb_host);
					unset($clientdb_user);
					unset($clientdb_password);

				}

				// remove non-existing backups from database
				$backups = $app->db->queryAllRecords("SELECT * FROM web_backup WHERE server_id = ?", $conf['server_id']);
				if(is_array($backups) && !empty($backups)){
					foreach($backups as $backup){
						$backup_file = $backup_dir.'/web'.$backup['parent_domain_id'].'/'.$backup['filename'];
						if(!is_file($backup_file)){
							$sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ? AND filename = ?";
							$app->db->query($sql, $conf['server_id'], $backup['parent_domain_id'], $backup['filename']);
						}
					}
				}
				if($app->db->dbHost != $app->dbmaster->dbHost){
					$backups = $app->dbmaster->queryAllRecords("SELECT * FROM web_backup WHERE server_id = ?", $conf['server_id']);
					if(is_array($backups) && !empty($backups)){
						foreach($backups as $backup){
							$backup_file = $backup_dir.'/web'.$backup['parent_domain_id'].'/'.$backup['filename'];
							if(!is_file($backup_file)){
								$sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ? AND filename = ?";
								$app->dbmaster->query($sql, $conf['server_id'], $backup['parent_domain_id'], $backup['filename']);
							}
						}
					}
				}
				
				// garbage collection (non-existing databases)
				if(is_array($web_array) && !empty($web_array)){
					foreach($web_array as $tmp_web_id){
						$tmp_backup_dir = $backup_dir.'/web'.$tmp_web_id;
						if(is_dir($tmp_backup_dir)){
							$dir_handle = dir($tmp_backup_dir);
							$files = array();
							while (false !== ($entry = $dir_handle->read())) {
								if($entry != '.' && $entry != '..' && preg_match('/^db_(.*?)_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}\.sql.gz$/', $entry, $matches) && is_file($tmp_backup_dir.'/'.$entry)) {

									$tmp_db_name = $matches[1];
									$tmp_database = $app->db->queryOneRecord("SELECT * FROM web_database WHERE server_id = ? AND parent_domain_id = ? AND database_name = ?", $conf['server_id'], $tmp_web_id, $tmp_db_name);

									if(is_array($tmp_database) && !empty($tmp_database)){
										if($tmp_database['backup_interval'] == 'none' || intval($tmp_database['backup_copies']) == 0){
											@unlink($tmp_backup_dir.'/'.$entry);
											$sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ? AND filename = ?";
											$app->db->query($sql, $conf['server_id'], $tmp_web_id, $entry);
											if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'], $tmp_web_id, $entry);
										}
									} else {
										@unlink($tmp_backup_dir.'/'.$entry);
										$sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ? AND filename = ?";
										$app->db->query($sql, $conf['server_id'], $tmp_web_id, $entry);
										if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'], $tmp_web_id, $entry);
									}
								}
							}
							$dir_handle->close();
						}
					}
				}
				//* end run_backups
				if( $server_config['backup_dir_is_mount'] == 'y' ) $app->system->umount_backup_dir($backup_dir);
			} 
		}
		
		// delete files from backup download dir (/var/www/example.com/backup)
		unset($records, $entry, $files);
		$sql = "SELECT * FROM web_domain WHERE server_id = ? AND (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias') AND active = 'y'";
		$records = $app->db->queryAllRecords($sql, $conf['server_id']);
		if(is_array($records)) {
			foreach($records as $rec) {
				$backup_download_dir = $rec['document_root'].'/backup';
				if(is_dir($backup_download_dir)){
					$dir_handle = dir($backup_download_dir);
					$files = array();
					while (false !== ($entry = $dir_handle->read())) {
						if($entry != '.' && $entry != '..' && is_file($backup_download_dir.'/'.$entry)) {
							// delete files older than 3 days
							if(time() - filemtime($backup_download_dir.'/'.$entry) >= 60*60*24*3) @unlink($backup_download_dir.'/'.$entry);
						}
					}
					$dir_handle->close();
				}
			}
		}

		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
