<?php
/*
Copyright (c) 2013, Florian Schaal, info@schaal-24.de
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
		$backup_dir = $server_config['backup_dir'];
		$backup_mode = $server_config['backup_mode'];
		if($backup_mode == '') $backup_mode = 'userzip';
		$backup_dir_permissions =0750;

		if($backup_dir != '') {
/*
			//* mount backup directory, if necessary
			$run_backups = true;
			$server_config['backup_dir_mount_cmd'] = trim($server_config['backup_dir_mount_cmd']);
			if($server_config['backup_dir_is_mount'] == 'y' && $server_config['backup_dir_mount_cmd'] != ''){
				if(!$app->system->is_mounted($backup_dir)){
					exec(escapeshellcmd($server_config['backup_dir_mount_cmd']));
					sleep(1);
					if(!$app->system->is_mounted($backup_dir)) $run_backups = false;
				}
			}
*/

			$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
			
			if(!is_dir($backup_dir)) {
				mkdir(escapeshellcmd($backup_dir), $backup_dir_permissions, true);
			} else {
				chmod(escapeshellcmd($backup_dir), $backup_dir_permissions);
			}

			$sql = "SELECT * FROM mail_user WHERE server_id = '".$conf['server_id']."' AND maildir <> ''";
			$records = $app->db->queryAllRecords($sql);
			if(is_array($records)) {
				foreach($records as $rec) {
					//* Do the mailbox backup
					if($rec['backup_interval'] == 'daily' or ($rec['backup_interval'] == 'weekly' && date('w') == 0) or ($rec['backup_interval'] == 'monthly' && date('d') == '01')) {
						$sql="SELECT * FROM mail_domain WHERE domain = '".$app->db->quote(explode("@",$rec['email'])[1])."'";
						$domain_rec=$app->db->queryOneRecord($sql);
						$mail_backup_dir = $backup_dir.'/mail'.$domain_rec['domain_id'];

						if(!is_dir($mail_backup_dir)) mkdir($mail_backup_dir, 0750);
						chmod($mail_backup_dir, $backup_dir_permissions);

						$domain_dir=explode('/',$rec['maildir']); 
						$_temp=array_pop($domain_dir);unset($_temp);
						$domain_dir=implode('/',$domain_dir);
						$source_dir=array_pop(explode('/',$rec['maildir']));

						$mail_backup_file = 'mail'.$rec['mailuser_id'].'_'.date('Y-m-d_H-i');

						if($backup_mode == 'userzip') {
							$mail_backup_file.='.zip';
							exec('cd '.$rec['homedir'].' && zip -b /tmp -r '.$mail_backup_dir.'/'.$mail_backup_file.' '.$source_dir.' > /dev/nul');
							//exec('cd '.$rec['homedir'].' && zip -b /tmp -r '.$mail_backup_dir.'/'.$mail_backup_file.' '.$source_dir.' > /dev/nul');
						} else {
							/* Create a tar.gz backup */
							$mail_backup_file.='.tar.gz';
							exec(escapeshellcmd('tar pczf '.$mail_backup_dir.'/'.$mail_backup_file.' --directory '.$domain_dir.' '.$source_dir), $tmp_output, $retval);
						}
						if($retval == 0){
							chown($mail_backup_dir.'/'.$mail_backup_file, 'root');
							chgrp($mail_backup_dir.'/'.$mail_backup_file, 'root');
							chmod($mail_backup_dir.'/'.$mail_backup_file, 0640);
							/* Insert mail backup record in database */
							$sql = "INSERT INTO mail_backup (server_id,parent_domain_id,mailuser_id,backup_mode,tstamp,filename,filesize) VALUES (".$conf['server_id'].",".$domain_rec['domain_id'].",".$rec['mailuser_id'].",'".$backup_mode."',".time().",'".$app->db->quote($mail_backup_file)."','".$app->functions->formatBytes(filesize($mail_backup_dir.'/'.$mail_backup_file))."')";
							$app->db->query($sql);	
							if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql);
						} else {
							/* Backup failed - remove archive */
							if(is_file($mail_backup_dir.'/'.$mail_backup_file)) unlink($mail_backup_dir.'/'.$mail_backup_file);
							$app->log($mail_backup_file.' NOK:'.$tmp_output, LOGLEVEL_DEBUG);
						}
						/* Remove old backups */
						$backup_copies = intval($rec['backup_copies']);
						$dir_handle = dir($mail_backup_dir);
						$files = array();
						while (false !== ($entry = $dir_handle->read())) {
							if($entry != '.' && $entry != '..' && substr($entry,0,4+strlen($rec['mailuser_id'])) == 'mail'.$rec['mailuser_id'] && is_file($mail_backup_dir.'/'.$entry)) {
								$files[] = $entry;
							}
						}
						$dir_handle->close();
						rsort($files);
						for ($n = $backup_copies; $n <= 10; $n++) {
							if(isset($files[$n]) && is_file($mail_backup_dir.'/'.$files[$n])) {
								unlink($mail_backup_dir.'/'.$files[$n]);
								$sql = "DELETE FROM mail_backup WHERE server_id = ".$conf['server_id']." AND parent_domain_id = ".$domain_rec['domain_id']." AND filename = '".$app->db->quote($files[$n])."'";
								$app->db->query($sql);
								if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql);
							}
						}
						unset($files);
						unset($dir_handle);
					}
					/* Remove inactive backups */
					if($rec['backup_interval'] == 'none') {
						/* remove backups from db */
						$sql = "DELETE FROM mail_backup WHERE server_id = ".$conf['server_id']." AND parent_domain_id = ".$domain_rec['domain_id']." AND mailuser_id = ".$rec['mailuser_id'];
						$app->db->query($sql);
						if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql);
						/* remove archives */
						$mail_backup_dir = $backup_dir.'/mail'.$rec['sys_userid'];
						$mail_backup_file = 'mail'.$rec['mailuser_id'].'_*';
						if(is_dir($mail_backup_dir)) {
							foreach (glob($mail_backup_dir.'/'.$mail_backup_file) as $filename) {
								unlink($filename);
							}
						}
					}
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
