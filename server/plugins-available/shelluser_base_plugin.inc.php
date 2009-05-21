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
		
		$app->plugins->registerEvent('shell_user_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('shell_user_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('shell_user_delete',$this->plugin_name,'delete');

		
	}
	
	
	function insert($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		if($app->system->is_user($data['new']['puser'])) {
			// Get the UID of the parent user
			$uid = intval($app->system->getuid($data['new']['puser']));
			if($uid > $this->min_uid) {
				$command = 'useradd';
				$command .= ' -d '.escapeshellcmd($data['new']['dir']);
				$command .= ' -g '.escapeshellcmd($data['new']['pgroup']);
				$command .= ' -o '; // non unique
				if($data['new']['password'] != '') $command .= ' -p '.escapeshellcmd($data['new']['password']);
				$command .= ' -s '.escapeshellcmd($data['new']['shell']);
				$command .= ' -u '.escapeshellcmd($uid);
				$command .= ' '.escapeshellcmd($data['new']['username']);
			
				exec($command);
				$app->log("Executed command: ".$command,LOGLEVEL_DEBUG);
				$app->log("Added shelluser: ".$data['new']['username'],LOGLEVEL_DEBUG);
				
				//* Create .bash_history file
				exec('touch '.escapeshellcmd($data['new']['dir']).'/.bash_history');
				exec('chmod 755 '.escapeshellcmd($data['new']['dir']).'/.bash_history');
				exec('chown '.escapeshellcmd($data['new']['username']).':'.escapeshellcmd($data['new']['pgroup']).' '.escapeshellcmd($data['new']['dir']).'/.bash_history');
				
				//* Disable shell user temporarily if we use jailkit
				if($data['new']['chroot'] == 'jailkit') {
					$command = 'usermod -L '.escapeshellcmd($data['new']['username']);
					exec($command);
					$app->log("Disabling shelluser temporarily: ".$command,LOGLEVEL_DEBUG);
				}
			
			} else {
				$app->log("UID = $uid for shelluser:".$data['new']['username']." not allowed.",LOGLEVEL_ERROR);
			}
		} else {
			$app->log("Skippung insert of user:".$data['new']['username'].", parent user ".$data['new']['puser']." does not exist.",LOGLEVEL_WARN);
		}
	}
	
	function update($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		if($app->system->is_user($data['new']['puser'])) {
			// Get the UID of the parent user
			$uid = intval($app->system->getuid($data['new']['puser']));
			if($uid > $this->min_uid) {
				// Check if the user that we want to update exists, if not, we insert it
				if($app->system->is_user($data['old']['username'])) {
					$command = 'usermod';
					$command .= ' --home '.escapeshellcmd($data['new']['dir']);
					$command .= ' --gid '.escapeshellcmd($data['new']['pgroup']);
					// $command .= ' --non-unique ';
					$command .= ' --password '.escapeshellcmd($data['new']['password']);
					if($data['new']['chroot'] != 'jailkit') $command .= ' --shell '.escapeshellcmd($data['new']['shell']);
					// $command .= ' --uid '.escapeshellcmd($uid);
					$command .= ' --login '.escapeshellcmd($data['new']['username']);
					$command .= ' '.escapeshellcmd($data['old']['username']);
			
					exec($command);
					$app->log("Executed command: $command ",LOGLEVEL_DEBUG);
					$app->log("Updated shelluser: ".$data['old']['username'],LOGLEVEL_DEBUG);
					
					
					//* Create .bash_history file
					if(!is_file($data['new']['dir']).'/.bash_history') {
						exec('touch '.escapeshellcmd($data['new']['dir']).'/.bash_history');
						exec('chmod 755 '.escapeshellcmd($data['new']['dir']).'/.bash_history');
						exec('chown '.escapeshellcmd($data['new']['username']).':'.escapeshellcmd($data['new']['pgroup']).' '.escapeshellcmd($data['new']['dir']).'/.bash_history');
					}
					
				} else {
					// The user does not exist, so we insert it now
					$this->insert($event_name,$data);
				}
			} else {
				$app->log("UID = $uid for shelluser:".$data['new']['username']." not allowed.",LOGLEVEL_ERROR);
			}
		} else {
			$app->log("Skippung update for user:".$data['new']['username'].", parent user ".$data['new']['puser']." does not exist.",LOGLEVEL_WARN);
		}
	}
	
	function delete($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		if($app->system->is_user($data['old']['username'])) {
			// Get the UID of the user
			$userid = intval($app->system->getuid($data['old']['username']));
			if($userid > $this->min_uid) {
				// We delete only non jailkit users, jailkit users will be deleted by the jailkit plugin.
				if ($data['old']['chroot'] != "jailkit") {
					$command = 'userdel -f';
					$command .= ' '.escapeshellcmd($data['old']['username']);
			
					exec($command);
					$app->log("Deleted shelluser: ".$data['old']['username'],LOGLEVEL_DEBUG);
				}
			
			} else {
				$app->log("UID = $userid for shelluser:".$data['old']['username']." not allowed.",LOGLEVEL_ERROR);
			}
		} else {
			$app->log("User:".$data['new']['username']." does not exist in in /etc/passwd, skipping delete.",LOGLEVEL_WARN);
		}
		
	}
	
	
	

} // end class

?>