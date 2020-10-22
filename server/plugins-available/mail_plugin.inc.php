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

class mail_plugin {

	var $plugin_name = 'mail_plugin';
	var $class_name  = 'mail_plugin';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['mail'] == true) {
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

		//* Mailboxes
		$app->plugins->registerEvent('mail_user_insert', $this->plugin_name, 'user_insert');
		$app->plugins->registerEvent('mail_user_update', $this->plugin_name, 'user_update');
		$app->plugins->registerEvent('mail_user_delete', $this->plugin_name, 'user_delete');

		//* Mail Domains
		//$app->plugins->registerEvent('mail_domain_insert',$this->plugin_name,'domain_insert');
		//$app->plugins->registerEvent('mail_domain_update',$this->plugin_name,'domain_update');
		$app->plugins->registerEvent('mail_domain_delete', $this->plugin_name, 'domain_delete');

		//* Mail transports
		$app->plugins->registerEvent('mail_transport_insert', $this->plugin_name, 'transport_update');
		$app->plugins->registerEvent('mail_transport_update', $this->plugin_name, 'transport_update');
		$app->plugins->registerEvent('mail_transport_delete', $this->plugin_name, 'transport_update');

	}


	function user_insert($event_name, $data) {
		global $app, $conf;

		//* get the config
		$app->uses('getconf,system');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		// convert to lower case - it could cause problems if some directory above has upper case name
		//  $data['new']['maildir'] = strtolower($data['new']['maildir']);

		$maildomain_path = $data['new']['maildir'];
		$tmp_basepath = $data['new']['maildir'];
		$tmp_basepath_parts = explode('/', $tmp_basepath);
		unset($tmp_basepath_parts[count($tmp_basepath_parts)-1]);
		$base_path = implode('/', $tmp_basepath_parts);

		//* Set the email-uid and gid if not given
		if (($data['new']['uid'] == -1) || ($data['new']['gid'] == -1)) {
			$app->log('Setting uid and gid automatically',LOGLEVEL_DEBUG);
			if ($mail_config["mailbox_virtual_uidgid_maps"] == 'y') {
				$app->log('Map uid to linux-user',LOGLEVEL_DEBUG);
				$email_parts = explode('@',$data['new']['email']);
				$webdomain = $app->db->queryOneRecord("SELECT domain_id, server_id, system_user, parent_domain_id FROM web_domain WHERE domain = ?", $email_parts[1]);
				if ($webdomain) {
					while (($webdomain['system_user'] == null) && ($webdomain['parent_domain_id'] != 0)) {
						$webdomain = $app->db->queryOneRecord("SELECT domain_id, server_id, system_user, parent_domain_id FROM web_domain WHERE domain_id = ?", $webdomain['parent_domain_id']);
					}
					$app->log($data['new']['server_id'].' == '.$webdomain['server_id'],LOGLEVEL_DEBUG);

					// only if web and mailserver are identical
					if ($data['new']['server_id'] == $webdomain['server_id']) {
						$data['new']['uid'] = $app->system->getuid($webdomain['system_user']);
					}
				}
			}
		}
		// if nothing set before -> use standard mailuser uid and gid vmail
		if ($data['new']['uid'] == -1) $data['new']['uid'] = $mail_config["mailuser_uid"];
		if ($data['new']['gid'] == -1) $data['new']['gid'] = $mail_config["mailuser_gid"];
		$app->log('Mailuser uid: '.$data['new']['uid'].', gid: '.$data['new']['gid'],LOGLEVEL_DEBUG);

		// update DB if values changed
		$app->db->query("UPDATE mail_user SET uid = ?, gid = ? WHERE mailuser_id = ?", $data['new']['uid'], $data['new']['gid'], $data['new']['mailuser_id']);

		// now get names of uid and gid
		$user = $app->system->getuser($data['new']['uid']);
		$group = $app->system->getgroup($data['new']['gid']);
		//* Create the mail domain directory, if it does not exist
		if(!empty($base_path) && !is_dir($base_path)) {
			$app->system->mkdirpath($base_path, 0770, $mail_config['mailuser_name'], $mail_config['mailuser_group']); // needs group-access because users of subfolders may differ from vmail
			$app->log('Created Directory: '.$base_path, LOGLEVEL_DEBUG);
		}

		if ($data['new']['maildir_format'] == 'mdbox') {
			$app->system->exec_safe("su -c 'doveadm mailbox create -u ? INBOX'", $data["new"]["email"]);
			$app->system->exec_safe("su -c 'doveadm mailbox create -u ? Sent'", $data["new"]["email"]);
			$app->system->exec_safe("su -c 'doveadm mailbox create -u ? Trash'", $data["new"]["email"]);
			$app->system->exec_safe("su -c 'doveadm mailbox create -u ? Junk'", $data["new"]["email"]);
			$app->system->exec_safe("su -c 'doveadm mailbox create -u ? Drafts'", $data["new"]["email"]);
			
			$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? INBOX'", $data["new"]["email"]);
			$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? Sent'", $data["new"]["email"]);
			$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? Trash'", $data["new"]["email"]);
			$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? Junk'", $data["new"]["email"]);
			$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? Drafts'", $data["new"]["email"]);
		}
		else {
			// Dovecot uses a different mail layout with a separate 'Maildir' subdirectory.
			if($mail_config['pop3_imap_daemon'] == 'dovecot') {
				$app->system->mkdirpath($maildomain_path, 0700, $user, $group);
				$app->log('Created Directory: '.$maildomain_path, LOGLEVEL_DEBUG);
				$maildomain_path .= '/Maildir';
			}
					
			//* When the mail user dir exists but it is not a valid maildir, move it to corrupted maildir folder
			if(!empty($maildomain_path) && is_dir($maildomain_path) && !is_dir($maildomain_path.'/new') && !is_dir($maildomain_path.'/cur')) {
				if(!is_dir($mail_config['homedir_path'].'/corrupted/'.$data['new']['mailuser_id'])) $app->system->mkdirpath($mail_config['homedir_path'].'/corrupted/'.$data['new']['mailuser_id'], 0700, $mail_config['mailuser_name'], $mail_config['mailuser_group']);
				$app->system->exec_safe("su -c ? vmail", "mv -f " . $data['new']['maildir']." ".$mail_config['homedir_path'].'/corrupted/'.$data['new']['mailuser_id']);
				$app->log('Moved invalid maildir to corrupted Maildirs folder: '.$data['new']['maildir'], LOGLEVEL_WARN);
			}
	
			//* Create the maildir, if it doesn not exist, set permissions, set quota.
			if(!empty($maildomain_path) && !is_dir($maildomain_path)) {
	
				$app->system->maildirmake($maildomain_path, $user, '', $group);
	
				//* This is to fix the maildrop quota not being rebuilt after the quota is changed.
				if($mail_config['pop3_imap_daemon'] != 'dovecot') {
					if(is_dir($maildomain_path)) $app->system->exec_safe("su -c ? ?", "maildirmake -q ".$data['new']['quota']."S ".$maildomain_path, $user); // Avoid maildirmake quota bug, see debian bug #214911
					$app->log('Created Maildir: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".$maildomain_path."' ".$user, LOGLEVEL_DEBUG);
				}
			}
	
			if(!is_dir($data['new']['maildir'].'/.Sent')) {
				$app->system->maildirmake($maildomain_path, $user, 'Sent', $group);
			}
			if(!is_dir($data['new']['maildir'].'/.Drafts')) {
				$app->system->maildirmake($maildomain_path, $user, 'Drafts', $group);
			}
			if(!is_dir($data['new']['maildir'].'/.Trash')) {
				$app->system->maildirmake($maildomain_path, $user, 'Trash', $group);
			}
			if(!is_dir($data['new']['maildir'].'/.Junk')) {
				$app->system->maildirmake($maildomain_path, $user, 'Junk', $group);
			}
	
			// Set permissions now recursive
			$app->system->exec_safe('chown -R ?:? ?', $user, $group, $data['new']['maildir']);
			$app->log('Set ownership on '.$data['new']['maildir'], LOGLEVEL_DEBUG);
	
			//* Set the maildir quota
			if(is_dir($data['new']['maildir'].'/new') && $mail_config['pop3_imap_daemon'] != 'dovecot') {
				if($data['new']['quota'] > 0) {
					if(is_dir($data['new']['maildir'])) $app->system->exec_safe("su -c ? ? ", "maildirmake -q ".$data['new']['quota']."S ". $data['new']['maildir'], $user);
					$app->log('Set Maildir quota: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".$data['new']['maildir']."' ".$user, LOGLEVEL_DEBUG);
				}
			}
		}

		//* Send the welcome email message
		$tmp = explode('@', $data["new"]["email"]);
		$domain = $tmp[1];
		unset($tmp);
		$html = false;
		if(file_exists($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$domain.'.html')) {
			$lines = file($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$domain.'.html');
			$html = true;
		} elseif(file_exists($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$conf['language'].'.html')) {
			$lines = file($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$conf['language'].'.html');
			$html = true;
		} elseif(file_exists($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$domain.'.txt')) {
			$lines = file($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$domain.'.txt');
		} elseif(file_exists($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$conf['language'].'.txt')) {
			$lines = file($conf['rootpath'].'/conf-custom/mail/welcome_email_'.$conf['language'].'.txt');
		} elseif(file_exists($conf['rootpath'].'/conf-custom/mail/welcome_email_en.txt')) {
			$lines = file($conf['rootpath'].'/conf-custom/mail/welcome_email_en.txt');
		} elseif(file_exists($conf['rootpath'].'/conf/mail/welcome_email_'.$conf['language'].'.txt')) {
			$lines = file($conf['rootpath'].'/conf/mail/welcome_email_'.$conf['language'].'.txt');
		} else {
			$lines = file($conf['rootpath'].'/conf/mail/welcome_email_en.txt');
		}

		//* Get from address
		$parts = explode(':', trim($lines[0]));
		unset($parts[0]);
		$welcome_mail_from  = implode(':', $parts);
		unset($lines[0]);

		//* Get subject
		$parts = explode(':', trim($lines[1]));
		unset($parts[0]);
		$welcome_mail_subject  = implode(':', $parts);
		unset($lines[1]);

		//* Get message
		$welcome_mail_message = trim(implode($lines));
		unset($tmp);

		$mailHeaders      = "MIME-Version: 1.0" . "\n";
		if($html) {
			$mailHeaders     .= "Content-Type: text/html; charset=utf-8" . "\n";
			$mailHeaders     .= "Content-Transfer-Encoding: quoted-printable" . "\n";
		} else {
			$mailHeaders     .= "Content-Type: text/plain; charset=utf-8" . "\n";
			$mailHeaders     .= "Content-Transfer-Encoding: 8bit" . "\n";
		}
		$mailHeaders     .= "From: $welcome_mail_from" . "\n";
		$mailHeaders     .= "Reply-To: $welcome_mail_from" . "\n";
		$mailTarget       = $data["new"]["email"];
		$mailSubject      = "=?utf-8?B?".base64_encode($welcome_mail_subject)."?=";

		//* Send the welcome email only on the "master" mail server to avoid duplicate emails
		if($conf['mirror_server_id'] == 0) mail($mailTarget, $mailSubject, $welcome_mail_message, $mailHeaders);

	}

	function user_update($event_name, $data) {
		global $app, $conf;

		// get the config
		$app->uses('getconf,system');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		// Maildir-Format must not be changed on this way !!
		$data['new']['maildir_format'] = $data['old']['maildir_format'];
		
		$maildomain_path = $data['new']['maildir'];
		$tmp_basepath = $data['new']['maildir'];
		$tmp_basepath_parts = explode('/', $tmp_basepath);
		unset($tmp_basepath_parts[count($tmp_basepath_parts)-1]);
		$base_path = implode('/', $tmp_basepath_parts);

		//* Set the email-uid and gid if not given -> in case of changed settings again setting here
		if (($data['new']['uid'] == -1) || ($data['new']['gid'] == -1)) {
			$app->log('Setting uid and gid automatically',LOGLEVEL_DEBUG);
			if ($mail_config["mailbox_virtual_uidgid_maps"] == 'y') {
				$app->log('Map uid to linux-user',LOGLEVEL_DEBUG);
				$email_parts = explode('@',$data['new']['email']);
				$webdomain = $app->db->queryOneRecord("SELECT domain_id, server_id, system_user, parent_domain_id FROM web_domain WHERE domain = ?", $email_parts[1]);
				if ($webdomain) {
					while ($webdomain['parent_domain_id'] != 0) {
						$webdomain = $app->db->queryOneRecord("SELECT domain_id, server_id, system_user, parent_domain_id FROM web_domain WHERE domain_id = ?", $webdomain['parent_domain_id']);
					}
					$app->log($data['new']['server_id'].' == '.$webdomain['server_id'],LOGLEVEL_DEBUG);

					// only if web and mailserver are identical
					if ($data['new']['server_id'] == $webdomain['server_id']) {
						$data['new']['uid'] = $app->system->getuid($webdomain['system_user']);
					}
				}
			}
		}
		// if nothing set before -> use standard mailuser uid and gid vmail
		if ($data['new']['uid'] == -1) $data['new']['uid'] = $mail_config["mailuser_uid"];
		if ($data['new']['gid'] == -1) $data['new']['gid'] = $mail_config["mailuser_gid"];
		$app->log('Mailuser uid: '.$data['new']['uid'].', gid: '.$data['new']['gid'],LOGLEVEL_DEBUG);

		// update DB if values changed
		$app->db->query("UPDATE mail_user SET uid = ?, gid = ? WHERE mailuser_id = ?", $data['new']['uid'], $data['new']['gid'], $data['new']['mailuser_id']);

		$user = $app->system->getuser($data['new']['uid']);
		$group = $app->system->getgroup($data['new']['gid']);

		//* Create the mail domain directory, if it does not exist
		if(!empty($base_path) && !is_dir($base_path)) {
			$app->system->mkdirpath($base_path, 0770, $mail_config['mailuser_name'], $mail_config['mailuser_group']); // needs group-access because users of subfolders may differ from vmail
			$app->log('Created Directory: '.$base_path, LOGLEVEL_DEBUG);
		}

		if ($data['new']['maildir_format'] == 'mdbox') {
			// Move mailbox, if domain has changed and delete old mailbox
			if($data['new']['maildir'] != $data['old']['maildir'] && is_dir($data['old']['maildir'])) {
				if(is_dir($data['new']['maildir'])) {
					$app->system->exec_safe("rm -fr ?", $data['new']['maildir']);
					//rmdir($data['new']['maildir']);
				}
				$app->system->exec_safe('mv -f ? ?'. $data['old']['maildir'], $data['new']['maildir']);
				$app->log('Moved Maildir from: '.$data['old']['maildir'].' to '.$data['new']['maildir'], LOGLEVEL_DEBUG);
			}
				
			//* Create the maildir, if it doesn not exist, set permissions, set quota.
			if(!is_dir($data['new']['maildir'].'/mdbox')) {
				$app->system->exec_safe("su -c 'doveadm mailbox create -u ? INBOX'", $data["new"]["email"]);
				$app->system->exec_safe("su -c 'doveadm mailbox create -u ? Sent'", $data["new"]["email"]);
				$app->system->exec_safe("su -c 'doveadm mailbox create -u ? Trash'", $data["new"]["email"]);
				$app->system->exec_safe("su -c 'doveadm mailbox create -u ? Junk'", $data["new"]["email"]);
				$app->system->exec_safe("su -c 'doveadm mailbox create -u ? Drafts'", $data["new"]["email"]);
					
				$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? INBOX'", $data["new"]["email"]);
				$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? Sent'", $data["new"]["email"]);
				$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? Trash'", $data["new"]["email"]);
				$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? Junk'", $data["new"]["email"]);
				$app->system->exec_safe("su -c 'doveadm mailbox subscribe -u ? Drafts'", $data["new"]["email"]);
			}
		}
		else {
			// Dovecot uses a different mail layout with a separate 'Maildir' subdirectory.
			if($mail_config['pop3_imap_daemon'] == 'dovecot') {
				$app->system->mkdirpath($maildomain_path, 0700, $user, $group);
				$app->log('Created Directory: '.$base_path, LOGLEVEL_DEBUG);
				$maildomain_path .= '/Maildir';
			}
	
			//* When the mail user dir exists but it is not a valid maildir, move it to corrupted maildir folder
			if(!empty($maildomain_path) && is_dir($maildomain_path) && !is_dir($maildomain_path.'/new') && !is_dir($maildomain_path.'/cur')) {
				if(!is_dir($mail_config['homedir_path'].'/corrupted/'.$data['new']['mailuser_id'])) $app->system->mkdirpath($mail_config['homedir_path'].'/corrupted/'.$data['new']['mailuser_id'], 0700, $mail_config['mailuser_name'], $mail_config['mailuser_group']);
				$app->system->exec_safe("su -c ? ?", "mv -f ".$data['new']['maildir']." ".$mail_config['homedir_path'].'/corrupted/'.$data['new']['mailuser_id'], 'vmail');
				$app->log('Moved invalid maildir to corrupted Maildirs folder: '.$data['new']['maildir'], LOGLEVEL_WARN);
			}
	
			//* Create the maildir, if it doesn not exist, set permissions, set quota.
			if(!empty($maildomain_path) && !is_dir($maildomain_path.'/new')) {
				$app->system->maildirmake($maildomain_path, $user, '', $group);
	
				//* This is to fix the maildrop quota not being rebuilt after the quota is changed.
				if($mail_config['pop3_imap_daemon'] != 'dovecot') {
					if($data['new']['quota'] > 0) {
						if(is_dir($maildomain_path)) $app->system->exec_safe("su -c ? ?", "maildirmake -q ".$data['new']['quota']."S ".$maildomain_path, $user); // Avoid maildirmake quota bug, see debian bug #214911
						$app->log('Updated Maildir quota: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".$maildomain_path."' ".$user, LOGLEVEL_DEBUG);
					} else {
						if(file_exists($data['new']['maildir'].'/maildirsize')) unlink($data['new']['maildir'].'/maildirsize');
						$app->log('Set Maildir quota to unlimited.', LOGLEVEL_DEBUG);
					}
				}
			}
	
			if(!is_dir($data['new']['maildir'].'/.Sent')) {
				$app->system->maildirmake($maildomain_path, $user, 'Sent', $group);
			}
			if(!is_dir($data['new']['maildir'].'/.Drafts')) {
				$app->system->maildirmake($maildomain_path, $user, 'Drafts', $group);
			}
			if(!is_dir($data['new']['maildir'].'/.Trash')) {
				$app->system->maildirmake($maildomain_path, $user, 'Trash', $group);
			}
			if(!is_dir($data['new']['maildir'].'/.Junk')) {
				$app->system->maildirmake($maildomain_path, $user, 'Junk', $group);
			}
	
			// Set permissions now recursive
			$app->system->exec_safe('chown -R ?:? ?', $user, $group, $data['new']['maildir']);
			$app->log('Set ownership on '.$data['new']['maildir'], LOGLEVEL_DEBUG);
	
			// Move mailbox, if domain has changed and delete old mailbox
			if($data['new']['maildir'] != $data['old']['maildir'] && is_dir($data['old']['maildir'])) {
				if(is_dir($data['new']['maildir'])) {
					$app->system->exec_safe("rm -fr ?", $data['new']['maildir']);
				}
				$app->system->exec_safe('mv -f ? ?', $data['old']['maildir'], $data['new']['maildir']);
				$app->log('Moved Maildir from: '.$data['old']['maildir'].' to '.$data['new']['maildir'], LOGLEVEL_DEBUG);
			}
			//This is to fix the maildrop quota not being rebuilt after the quota is changed.
			// Courier Layout
			if(is_dir($data['new']['maildir'].'/new') && $mail_config['pop3_imap_daemon'] != 'dovecot') {
				if($data['new']['quota'] > 0) {
					if(is_dir($data['new']['maildir'])) $app->system->exec_safe("su -c ? ?", "maildirmake -q ".$data['new']['quota']."S ".$data['new']['maildir'], $user);
					$app->log('Updated Maildir quota: '."su -c 'maildirmake -q ".$data['new']['quota']."S ".$data['new']['maildir']."' ".$user, LOGLEVEL_DEBUG);
				} else {
					if(file_exists($data['new']['maildir'].'/maildirsize')) unlink($data['new']['maildir'].'/maildirsize');
					$app->log('Set Maildir quota to unlimited.', LOGLEVEL_DEBUG);
				}
			}
		}
	}

	function user_delete($event_name, $data) {
		global $app, $conf;

		// get the config
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		$maildir_path_deleted = false;
		$old_maildir_path = $data['old']['maildir'];
		if($old_maildir_path != $mail_config['homedir_path'] && strlen($old_maildir_path) > strlen($mail_config['homedir_path']) && !stristr($old_maildir_path, '//') && !stristr($old_maildir_path, '..') && !stristr($old_maildir_path, '*') && strlen($old_maildir_path) >= 10) {
			$app->system->exec_safe('rm -rf ?', $old_maildir_path);
			$app->log('Deleted the Maildir: '.$data['old']['maildir'], LOGLEVEL_DEBUG);
			$maildir_path_deleted = true;
		} else {
			$app->log('Possible security violation when deleting the maildir: '.$data['old']['maildir'], LOGLEVEL_ERROR);
		}

		//* Delete the mail-backups
		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
		$backup_dir = $server_config['backup_dir'];
		$mount_backup = true;
		if($server_config['backup_dir'] != '' && $maildir_path_deleted && $server_config['backup_delete'] == 'y') {
			//* mount backup directory, if necessary
			if( $server_config['backup_dir_is_mount'] == 'y' && !$app->system->mount_backup_dir($backup_dir) ) $mount_backup = false;
			if($mount_backup){
				$sql = "SELECT * FROM mail_domain WHERE domain = ?";
				$tmp = explode("@",$data['old']['email']);
				$domain_rec = $app->db->queryOneRecord($sql,$tmp[1]);
				unset($tmp);
				if (is_array($domain_rec)) {
					$mail_backup_dir = $backup_dir.'/mail'.$domain_rec['domain_id'];
					$mail_backup_files = 'mail'.$data['old']['mailuser_id'];
					$app->system->exec_safe('rm -f ?*', $mail_backup_dir.'/'.$mail_backup_files);
					//* cleanup database
					$sql = "DELETE FROM mail_backup WHERE server_id = ? AND parent_domain_id = ? AND mailuser_id = ?";
					$app->db->query($sql, $conf['server_id'], $domain_rec['domain_id'], $data['old']['mailuser_id']);
					if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'], $domain_rec['domain_id'], $data['old']['mailuser_id']);

					$app->log('Deleted the mail backups for: '.$data['old']['email'], LOGLEVEL_DEBUG);
				}
			}
		}
	}

	function domain_delete($event_name, $data) {
		global $app, $conf;

		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		$maildomain_path_deleted = false;
		//* Delete maildomain path
		$old_maildomain_path = $mail_config['homedir_path'].'/'.$data['old']['domain'];
		if($old_maildomain_path != $mail_config['homedir_path'] && !stristr($old_maildomain_path, '//') && !stristr($old_maildomain_path, '..') && !stristr($old_maildomain_path, '*') && !stristr($old_maildomain_path, '&') && strlen($old_maildomain_path) >= 10  && !empty($data['old']['domain'])) {
			$app->system->exec_safe('rm -rf ?', $old_maildomain_path);
			$app->log('Deleted the mail domain directory: '.$old_maildomain_path, LOGLEVEL_DEBUG);
			$maildomain_path_deleted = true;
		} else {
			$app->log('Possible security violation when deleting the mail domain directory: '.$old_maildomain_path, LOGLEVEL_ERROR);
		}

		//* Delete mailfilter path
		$old_maildomain_path = $mail_config['homedir_path'].'/mailfilters/'.$data['old']['domain'];
		if($old_maildomain_path != $mail_config['homedir_path'].'/mailfilters/' && !stristr($old_maildomain_path, '//') && !stristr($old_maildomain_path, '..') && !stristr($old_maildomain_path, '*') && !stristr($old_maildomain_path, '&') && strlen($old_maildomain_path) >= 10 && !empty($data['old']['domain'])) {
			$app->system->exec_safe('rm -rf ?', $old_maildomain_path);
			$app->log('Deleted the mail domain mailfilter directory: '.$old_maildomain_path, LOGLEVEL_DEBUG);
		} else {
			$app->log('Possible security violation when deleting the mail domain mailfilter directory: '.$old_maildomain_path, LOGLEVEL_ERROR);
		}
		
		//* Delete the mail-backups
		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
		$backup_dir = $server_config['backup_dir'];
		$mount_backup = true;
		if($server_config['backup_dir'] != '' && $maildomain_path_deleted && $server_config['backup_delete'] == 'y'){
			//* mount backup directory, if necessary
			if( $server_config['backup_dir_is_mount'] == 'y' && !$app->system->mount_backup_dir($backup_dir) ) $mount_backup = false;
			if($mount_backup){
				$mail_backup_dir = $backup_dir.'/mail'.$data['old']['domain_id'];
				$app->system->exec_safe('rm -rf ?', $mail_backup_dir);
				//* cleanup database
				$sql = "DELETE FROM mail_backup WHERE server_id = ? AND parent_domain_id = ?";
				$app->db->query($sql, $conf['server_id'], $data['old']['domain_id']);
				if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'], $domain_rec['domain_id']);

				$app->log('Deleted the mail backup directory: '.$mail_backup_dir, LOGLEVEL_DEBUG);
			}
		}

	}

	function transport_update($event_name, $data) {
		global $app, $conf;

		exec($conf['init_scripts'] . '/' . 'postfix reload &> /dev/null');
		$app->log('Postfix config reloaded ', LOGLEVEL_DEBUG);

	}




} // end class

?>
