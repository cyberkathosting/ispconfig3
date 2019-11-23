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

class shelluser_base_plugin {

	var $plugin_name = 'shelluser_base_plugin';
	var $class_name = 'shelluser_base_plugin';
	var $min_uid = 499;

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['web'] == true) {
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
		
		$app->plugins->registerEvent('shell_user_insert', $this->plugin_name, 'insert');
		$app->plugins->registerEvent('shell_user_update', $this->plugin_name, 'update');
		$app->plugins->registerEvent('shell_user_delete', $this->plugin_name, 'delete');
		

	}


	function insert($event_name, $data) {
		global $app, $conf;
		
		$app->uses('system,getconf');
		
		$security_config = $app->getconf->get_security_config('permissions');
		if($security_config['allow_shell_user'] != 'yes') {
			$app->log('Shell user plugin disabled by security settings.',LOGLEVEL_WARN);
			return false;
		}

		//* Check if the resulting path is inside the docroot
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $data['new']['parent_domain_id']);
		if(substr($data['new']['dir'],0,strlen($web['document_root'])) != $web['document_root']) {
			$app->log('Directory of the shell user is outside of website docroot.',LOGLEVEL_WARN);
			return false;
		}
		if(strpos($data['new']['dir'], '/../') !== false || substr($data['new']['dir'],-3) == '/..') {
			$app->log('Directory of the shell user is not valid.',LOGLEVEL_WARN);
			return false;
		}
		
		if(!$app->system->is_allowed_user($data['new']['username'], false, false)
			|| !$app->system->is_allowed_user($data['new']['puser'], true, true)
			|| !$app->system->is_allowed_group($data['new']['pgroup'], true, true)) {
			$app->log('Shell user must not be root or in group root.',LOGLEVEL_WARN);
			return false;
		}

		if($data['new']['active'] != 'y') $data['new']['shell'] = '/bin/false';
		
		if($app->system->is_user($data['new']['puser'])) {

			// Get the UID of the parent user
			$uid = intval($app->system->getuid($data['new']['puser']));
			if($uid > $this->min_uid) {
				//* Remove webfolder protection
				$app->system->web_folder_protection($web['document_root'], false);
				
				//* Home directory of the new shell user
				if($data['new']['chroot'] == 'jailkit') {
					$homedir = $data['new']['dir'];
				} else {
					$homedir = $data['new']['dir'].'/home/'.$data['new']['username'];
				}
				
				// Create home base directory if it does not exist
				if(!is_dir($data['new']['dir'].'/home')){
					$app->file->mkdirs($data['new']['dir'].'/home', '0755');
				}
				
				// Change ownership of home base dir to root user
				$app->system->chown($data['new']['dir'].'/home','root');
				$app->system->chgrp($data['new']['dir'].'/home','root');
				$app->system->chmod($data['new']['dir'].'/home',0755);
				
				if(!is_dir($homedir)){
					$app->file->mkdirs($homedir, '0750');
					$app->system->chown($homedir,$data['new']['puser'],false);
					$app->system->chgrp($homedir,$data['new']['pgroup'],false);
				}
				$command = 'useradd -d ? -g ? -o'; // non unique
				$command .= ' -s ? -u ? ?';
				$app->system->exec_safe($command, $homedir, $data['new']['pgroup'], $data['new']['shell'], $uid, $data['new']['username']);
				$app->log("Executed command: ".$command, LOGLEVEL_DEBUG);
				$app->log("Added shelluser: ".$data['new']['username'], LOGLEVEL_DEBUG);

				if($data['new']['password'] != '') {
					$retval = null;
					$stderr = '';
					$app->system->pipe_exec('chpasswd -e ' . escapeshellarg($data['new']['username']), $data['new']['username'] . ':' . $data['new']['password'], $retval, $stderr);
					if($retval != 0) {
						$app->log("Command chpasswd failed for user ".$data['new']['username'] . ' with code ' . $retval . ': ' . $stderr, LOGLEVEL_WARN);
					}
				}
			
				$app->system->chown($data['new']['dir'],$data['new']['username'],false);
				$app->system->chgrp($data['new']['dir'],$data['new']['pgroup'],false);
				

				// call the ssh-rsa update function
				$app->uses("getconf");
				$this->data = $data;
				$this->app = $app;
				$this->_setup_ssh_rsa();

				//* Create .bash_history file
				$app->system->touch($homedir.'/.bash_history');
				$app->system->chmod($homedir.'/.bash_history', 0750);
				$app->system->chown($homedir.'/.bash_history', $data['new']['username']);
				$app->system->chgrp($homedir.'/.bash_history', $data['new']['pgroup']);

				//* Create .profile file
				$app->system->touch($homedir.'/.profile');
				$app->system->chmod($homedir.'/.profile', 0644);
				$app->system->chown($homedir.'/.profile', $data['new']['username']);
				$app->system->chgrp($homedir.'/.profile', $data['new']['pgroup']);

				//* Disable shell user temporarily if we use jailkit
				if($data['new']['chroot'] == 'jailkit') {
					$command = 'usermod -s /bin/false -L ? 2>/dev/null';
					$app->system->exec_safe($command, $data['new']['username']);
					$app->log("Disabling shelluser temporarily: ".$command, LOGLEVEL_DEBUG);
				}

				//* Add webfolder protection again
				$app->system->web_folder_protection($web['document_root'], true);
			} else {
				$app->log("UID = $uid for shelluser:".$data['new']['username']." not allowed.", LOGLEVEL_ERROR);
			}
		} else {
			$app->log("Skipping insertion of user:".$data['new']['username'].", parent user ".$data['new']['puser']." does not exist.", LOGLEVEL_WARN);
		}
	}

	function update($event_name, $data) {
		global $app, $conf;

		$app->uses('system,getconf');
		
		$security_config = $app->getconf->get_security_config('permissions');
		if($security_config['allow_shell_user'] != 'yes') {
			$app->log('Shell user plugin disabled by security settings.',LOGLEVEL_WARN);
			return false;
		}

		//* Check if the resulting path is inside the docroot
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $data['new']['parent_domain_id']);
		if(substr($data['new']['dir'],0,strlen($web['document_root'])) != $web['document_root']) {
			$app->log('Directory of the shell user is outside of website docroot.',LOGLEVEL_WARN);
			return false;
		}
		
		if(strpos($data['new']['dir'], '/../') !== false || substr($data['new']['dir'],-3) == '/..') {
			$app->log('Directory of the shell user is not valid.',LOGLEVEL_WARN);
			return false;
		}

		if(!$app->system->is_allowed_user($data['new']['username'], false, false)
			|| !$app->system->is_allowed_user($data['new']['puser'], true, true)
			|| !$app->system->is_allowed_group($data['new']['pgroup'], true, true)) {
			$app->log('Shell user must not be root or in group root.',LOGLEVEL_WARN);
			return false;
		}
		
		if($data['new']['active'] != 'y') $data['new']['shell'] = '/bin/false';
		
		if($app->system->is_user($data['new']['puser'])) {
			// Get the UID of the parent user
			$uid = intval($app->system->getuid($data['new']['puser']));
			if($uid > $this->min_uid) {
				
				//* Home directory of the shell user
				if($data['new']['chroot'] == 'jailkit') {
					$homedir = $data['new']['dir'];
					$homedir_old = $data['old']['dir'];
				} else {
					$homedir = $data['new']['dir'].'/home/'.$data['new']['username'];
					$homedir_old = $data['old']['dir'].'/home/'.$data['old']['username'];
				}
				
				$app->log("Homedir New: ".$homedir, LOGLEVEL_DEBUG);
				$app->log("Homedir Old: ".$homedir_old, LOGLEVEL_DEBUG);
				
				// Check if the user that we want to update exists, if not, we insert it
				if($app->system->is_user($data['old']['username'])) {
					//* Remove webfolder protection
					$app->system->web_folder_protection($web['document_root'], false);

					if($homedir != $homedir_old){
						$app->system->web_folder_protection($web['document_root'], false);
						// Rename dir, in case the new directory exists already.
						if(is_dir($homedir)) {
							$app->log("New Homedir exists, renaming it to ".$homedir.'_bak', LOGLEVEL_DEBUG);
							$app->system->rename($homedir,$homedir.'_bak');
						}
						
						// Move old directory to new path
						$app->system->rename($homedir_old,$homedir);
						$app->file->mkdirs($homedir, '0750');
						$app->system->chown($homedir,$data['new']['puser']);
						$app->system->chgrp($homedir,$data['new']['pgroup']);
						$app->system->web_folder_protection($web['document_root'], true);
					} else {
						if(!is_dir($homedir)){
							$app->system->web_folder_protection($web['document_root'], false);
							if(!is_dir($data['new']['dir'].'/home')){
								$app->file->mkdirs($data['new']['dir'].'/home', '0755');
								$app->system->chown($data['new']['dir'].'/home','root');
								$app->system->chgrp($data['new']['dir'].'/home','root');
							}
							$app->file->mkdirs($homedir, '0750');
							$app->system->chown($homedir,$data['new']['puser']);
							$app->system->chgrp($homedir,$data['new']['pgroup']);
							$app->system->web_folder_protection($web['document_root'], true);
						}
					}
					$app->system->usermod($data['old']['username'], 0, $app->system->getgid($data['new']['pgroup']), $homedir, $data['new']['shell'], $data['new']['password'], $data['new']['username']);
					$app->log("Updated shelluser: ".$data['old']['username'], LOGLEVEL_DEBUG);

					// call the ssh-rsa update function
					$app->uses("getconf");
					$this->data = $data;
					$this->app = $app;
					$this->_setup_ssh_rsa();

					//* Create .bash_history file
					if(!is_file($data['new']['dir']).'/.bash_history') {
						$app->system->touch($homedir.'/.bash_history');
						$app->system->chmod($homedir.'/.bash_history', 0750);
						$app->system->chown($homedir.'/.bash_history', $data['new']['username']);
						$app->system->chgrp($homedir.'/.bash_history', $data['new']['pgroup']);
					}
					
					//* Create .profile file
					if(!is_file($data['new']['dir']).'/.profile') {
						$app->system->touch($homedir.'/.profile');
						$app->system->chmod($homedir.'/.profile', 0644);
						$app->system->chown($homedir.'/.profile', $data['new']['username']);
						$app->system->chgrp($homedir.'/.profile', $data['new']['pgroup']);
					}

					//* Add webfolder protection again
					$app->system->web_folder_protection($web['document_root'], true);
				} else {
					// The user does not exist, so we insert it now
					$this->insert($event_name, $data);
				}
			} else {
				$app->log("UID = $uid for shelluser:".$data['new']['username']." not allowed.", LOGLEVEL_ERROR);
			}
		} else {
			$app->log("Skipping update for user:".$data['new']['username'].", parent user ".$data['new']['puser']." does not exist.", LOGLEVEL_WARN);
		}
	}

	function delete($event_name, $data) {
		global $app, $conf;

		$app->uses('system,getconf,services');
		
		$security_config = $app->getconf->get_security_config('permissions');
		if($security_config['allow_shell_user'] != 'yes') {
			$app->log('Shell user plugin disabled by security settings.',LOGLEVEL_WARN);
			return false;
		}

		if($app->system->is_user($data['old']['username'])) {
			// Get the UID of the user
			$userid = intval($app->system->getuid($data['old']['username']));
			if($userid > $this->min_uid) {
				$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($data['old']['parent_domain_id']));
					
				// check if we have to delete the dir
				$check = $app->db->queryOneRecord('SELECT shell_user_id FROM `shell_user` WHERE `dir` = ?', $data['old']['dir']);
				if(!$check && is_dir($data['old']['dir'])) {
					
					$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $data['old']['parent_domain_id']);
					$app->system->web_folder_protection($web['document_root'], false);
					
					// delete dir
					if($data['new']['chroot'] == 'jailkit') {
						$homedir = $data['old']['dir'];
					} else {
						$homedir = $data['old']['dir'].'/home/'.$data['old']['username'];
					}
				
					if(substr($homedir, -1) !== '/') $homedir .= '/';
					$files = array('.bash_logout', '.bash_history', '.bashrc', '.profile');
					$dirs = array('.ssh', '.cache');
					foreach($files as $delfile) {
						if(is_file($homedir . $delfile) && fileowner($homedir . $delfile) == $userid) unlink($homedir . $delfile);
					}
					foreach($dirs as $deldir) {
						if(is_dir($homedir . $deldir) && fileowner($homedir . $deldir) == $userid) $app->system->exec_safe('rm -rf ?', $homedir . $deldir);
					}
					$empty = true;
					$dirres = opendir($homedir);
					if($dirres) {
						while(($entry = readdir($dirres)) !== false) {
							if($entry != '.' && $entry != '..') {
								$empty = false;
								break;
							}
						}
						closedir($dirres);
					}
					if($empty == true) {
						rmdir($homedir);
					}
					unset($files);
					unset($dirs);
					
					$app->system->web_folder_protection($web['document_root'], true);
				}
				
				// We delete only non jailkit users, jailkit users will be deleted by the jailkit plugin.
				if ($data['old']['chroot'] != "jailkit") {
					// if this web uses PHP-FPM, that PPH-FPM service must be stopped before we can delete this user
					if($web['php'] == 'php-fpm'){
						if(trim($web['fastcgi_php_version']) != ''){
							$default_php_fpm = false;
							list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($web['fastcgi_php_version']));
						} else {
							$default_php_fpm = true;
						}
						$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');
						if(!$default_php_fpm){
							$app->services->restartService('php-fpm', 'stop:'.$custom_php_fpm_init_script);
						} else {
							$app->services->restartService('php-fpm', 'stop:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
						}
					}
					$command = 'killall -u ? ; userdel -f ? &> /dev/null';
					$app->system->exec_safe($command, $data['old']['username'], $data['old']['username']);
					$app->log("Deleted shelluser: ".$data['old']['username'], LOGLEVEL_DEBUG);
					// start PHP-FPM again
					if($web['php'] == 'php-fpm'){
						if(!$default_php_fpm){
							$app->services->restartService('php-fpm', 'start:'.$custom_php_fpm_init_script);
						} else {
							$app->services->restartService('php-fpm', 'start:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
						}
					}
				}

			} else {
				$app->log("UID = $userid for shelluser:".$data['old']['username']." not allowed.", LOGLEVEL_ERROR);
			}
		} else {
			$app->log("User:".$data['new']['username']." does not exist in in /etc/passwd, skipping delete.", LOGLEVEL_WARN);
		}

	}

	private function _setup_ssh_rsa() {
		global $app;
		$this->app->log("ssh-rsa setup shelluser_base", LOGLEVEL_DEBUG);
		// Get the client ID, username, and the key
		$domain_data = $this->app->db->queryOneRecord('SELECT sys_groupid FROM web_domain WHERE web_domain.domain_id = ?', $this->data['new']['parent_domain_id']);
		$sys_group_data = $this->app->db->queryOneRecord('SELECT * FROM sys_group WHERE sys_group.groupid = ?', $domain_data['sys_groupid']);
		$id = intval($sys_group_data['client_id']);
		$username= $sys_group_data['name'];
		$client_data = $this->app->db->queryOneRecord('SELECT * FROM client WHERE client.client_id = ?', $id);
		$userkey = $client_data['ssh_rsa'];
		unset($domain_data);
		unset($client_data);

		// ssh-rsa authentication variables
		//$sshrsa = $this->data['new']['ssh_rsa'];
		$sshrsa = '';
		$ssh_users = $app->db->queryAllRecords("SELECT ssh_rsa FROM shell_user WHERE parent_domain_id = ?", $this->data['new']['parent_domain_id']);
		if(is_array($ssh_users)) {
			foreach($ssh_users as $sshu) {
				if($sshu['ssh_rsa'] != '') $sshrsa .= "\n".$sshu['ssh_rsa'];
			}
		}
		$sshrsa = trim($sshrsa);
		$usrdir = $this->data['new']['dir'];
		//* Home directory of the new shell user
		if($this->data['new']['chroot'] != 'jailkit') {
			$usrdir = $this->data['new']['dir'].'/home/'.$this->data['new']['username'];
		}
		$sshdir = $usrdir.'/.ssh';
		$sshkeys= $usrdir.'/.ssh/authorized_keys';

		$app->uses('file');
		$sshrsa = $app->file->unix_nl($sshrsa);
		$sshrsa = $app->file->remove_blank_lines($sshrsa, 0);

		// If this user has no key yet, generate a pair
		if ($userkey == '' && $id > 0){
			//Generate ssh-rsa-keys
			$app->uses('functions');
			$app->functions->generate_ssh_key($id, $username);
			$this->app->log("ssh-rsa keypair generated for ".$username, LOGLEVEL_DEBUG);
		};

		if (!file_exists($sshkeys)){
			// add root's key
			$app->file->mkdirs($sshdir, '0700');
			if(is_file('/root/.ssh/authorized_keys')) $app->system->file_put_contents($sshkeys, $app->system->file_get_contents('/root/.ssh/authorized_keys'));

			// Remove duplicate keys
			$existing_keys = @file($sshkeys, FILE_IGNORE_NEW_LINES);
			$new_keys = explode("\n", $userkey);
			$final_keys_arr = @array_merge($existing_keys, $new_keys);
			$new_final_keys_arr = array();
			if(is_array($final_keys_arr) && !empty($final_keys_arr)){
				foreach($final_keys_arr as $key => $val){
					$new_final_keys_arr[$key] = trim($val);
				}
			}
			$final_keys = implode("\n", array_flip(array_flip($new_final_keys_arr)));

			// add the user's key
			$app->system->file_put_contents($sshkeys, $final_keys);
			$app->file->remove_blank_lines($sshkeys);
			$this->app->log("ssh-rsa authorisation keyfile created in ".$sshkeys, LOGLEVEL_DEBUG);
		}

		//* Get the keys
		$existing_keys = file($sshkeys, FILE_IGNORE_NEW_LINES);
		$new_keys = explode("\n", $sshrsa);
		$old_keys = explode("\n", $this->data['old']['ssh_rsa']);

		//* Remove all old keys
		if(is_array($old_keys)) {
			foreach($old_keys as $key => $val) {
				$k = array_search(trim($val), $existing_keys);
				if ($k !== false) {
					unset($existing_keys[$k]);
				}
			}
		}

		//* merge the remaining keys and the ones fom the ispconfig database.
		if(is_array($new_keys)) {
			$final_keys_arr = array_merge($existing_keys, $new_keys);
		} else {
			$final_keys_arr = $existing_keys;
		}

		$new_final_keys_arr = array();
		if(is_array($final_keys_arr) && !empty($final_keys_arr)){
			foreach($final_keys_arr as $key => $val){
				$new_final_keys_arr[$key] = trim($val);
			}
		}
		$final_keys = implode("\n", array_flip(array_flip($new_final_keys_arr)));

		// add the custom key
		$app->system->file_put_contents($sshkeys, $final_keys);
		$app->file->remove_blank_lines($sshkeys);
		$this->app->log("ssh-rsa key updated in ".$sshkeys, LOGLEVEL_DEBUG);

		// set proper file permissions
		$app->system->exec_safe("chown -R ?:? ?", $this->data['new']['puser'], $this->data['new']['pgroup'], $sshdir);
		$app->system->exec_safe("chmod 600 ?", $sshkeys);

	}


} // end class

?>
