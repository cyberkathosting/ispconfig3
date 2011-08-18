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

class shelluser_jailkit_plugin {
	
	//* $plugin_name and $class_name have to be the same then the name of this class
	var $plugin_name = 'shelluser_jailkit_plugin';
	var $class_name = 'shelluser_jailkit_plugin';
	
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
	
	//* This function is called, when a shell user is inserted in the database
	function insert($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		if($app->system->is_user($data['new']['username'])) {
		
			/**
		 	* Setup Jailkit Chroot System If Enabled 
		 	*/
			if ($data['new']['chroot'] == "jailkit")
			{
				// load the server configuration options
				$app->uses("getconf");
				$this->data = $data;
				$this->app = $app;
				$this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');
				
				$this->_update_website_security_level();
			
				$this->_setup_jailkit_chroot();
				
				$this->_add_jailkit_user();
				
				// call the ssh-rsa update function
				$this->_setup_ssh_rsa();
				
				$command .= 'usermod -s /usr/sbin/jk_chrootsh -U '.escapeshellcmd($data['new']['username']);
				exec($command);
				
				$this->_update_website_security_level();
			}
		
			$app->log("Jailkit Plugin -> insert username:".$data['new']['username'],LOGLEVEL_DEBUG);
			
		} else {
			$app->log("Jailkit Plugin -> insert username:".$data['new']['username']." skipped, the user does not exist.",LOGLEVEL_WARN);
		}
		
	}
	
	//* This function is called, when a shell user is updated in the database
	function update($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		if($app->system->is_user($data['new']['username'])) {
		
			/**
		 	* Setup Jailkit Chroot System If Enabled 
		 	*/
			if ($data['new']['chroot'] == "jailkit")
			{
				// load the server configuration options
				$app->uses("getconf");
				$this->data = $data;
				$this->app = $app;
				$this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');
				
				$this->_update_website_security_level();
			
				$this->_setup_jailkit_chroot();
				$this->_add_jailkit_user();
				
				// call the ssh-rsa update function
				$this->_setup_ssh_rsa();
				
				$this->_update_website_security_level();
			}
		
			$app->log("Jailkit Plugin -> update username:".$data['new']['username'],LOGLEVEL_DEBUG);
			
		} else {
			$app->log("Jailkit Plugin -> update username:".$data['new']['username']." skipped, the user does not exist.",LOGLEVEL_WARN);
		}
		
	}
	
	//* This function is called, when a shell user is deleted in the database
	/**
	 * TODO: Remove chroot user home and from the chroot passwd file
	 */ 
	function delete($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		if ($data['old']['chroot'] == "jailkit")
		{
			$app->uses("getconf");
			$this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');
			
			$jailkit_chroot_userhome = $this->_get_home_dir($data['old']['username']);
			
			//commented out proved to be dangerous on config errors
			//exec('rm -rf '.$data['old']['dir'].$jailkit_chroot_userhome);
			
			if(@is_dir($data['old']['dir'].$jailkit_chroot_userhome)) {
				$command = 'userdel';
				$command .= ' '.escapeshellcmd($data['old']['username']);
				exec($command);
				$app->log("Jailkit Plugin -> delete chroot home:".$data['old']['dir'].$jailkit_chroot_userhome,LOGLEVEL_DEBUG);
			}
			
		}
		
		$app->log("Jailkit Plugin -> delete username:".$data['old']['username'],LOGLEVEL_DEBUG);
		
		
	}
	
	function _setup_jailkit_chroot()
	{
			//check if the chroot environment is created yet if not create it with a list of program sections from the config
			if (!is_dir($this->data['new']['dir'].'/etc/jailkit'))
			{
				$command = '/usr/local/ispconfig/server/scripts/create_jailkit_chroot.sh';
				$command .= ' '.escapeshellcmd($this->data['new']['dir']);
				$command .= ' \''.$this->jailkit_config['jailkit_chroot_app_sections'].'\'';
				exec($command);
				
				$this->app->log("Added jailkit chroot with command: ".$command,LOGLEVEL_DEBUG);
				
				$this->_add_jailkit_programs();
				
				//add bash.bashrc script
				//we need to collect the domain name to be used as the HOSTNAME in the bashrc script
				$web = $this->app->db->queryOneRecord("SELECT domain FROM web_domain WHERE domain_id = ".intval($this->data['new']["parent_domain_id"]));
				
				$this->app->load('tpl');
		
				$tpl = new tpl();
				$tpl->newTemplate("bash.bashrc.master");
				
				$tpl->setVar('jailkit_chroot',true);
				$tpl->setVar('domain',$web['domain']);
				$tpl->setVar('home_dir',$this->_get_home_dir(""));
				
				$bashrc = escapeshellcmd($this->data['new']['dir']).'/etc/bash.bashrc';
				if(@is_file($bashrc)) unlink($bashrc);
				
				file_put_contents($bashrc,$tpl->grab());
				unset($tpl);
				
				$this->app->log("Added bashrc scrpt : ".$bashrc,LOGLEVEL_DEBUG);
				
				$tpl = new tpl();
				$tpl->newTemplate("motd.master");
				
				$tpl->setVar('domain',$web['domain']);
				
				$motd = escapeshellcmd($this->data['new']['dir']).'/var/run/motd';
				if(@is_file($motd)) unlink($motd);
				
				file_put_contents($motd,$tpl->grab());
				
			}
	}
	
	function _add_jailkit_programs()
	{
		//copy over further programs and its libraries
		$command = '/usr/local/ispconfig/server/scripts/create_jailkit_programs.sh';
		$command .= ' '.escapeshellcmd($this->data['new']['dir']);
		$command .= ' \''.$this->jailkit_config['jailkit_chroot_app_programs'].'\'';
		exec($command);
		
		$this->app->log("Added programs to jailkit chroot with command: ".$command,LOGLEVEL_DEBUG);
	}
	
	function _get_home_dir($username)
	{
		return str_replace("[username]",escapeshellcmd($username),$this->jailkit_config['jailkit_chroot_home']);
	}
	
	function _add_jailkit_user()
	{
			//add the user to the chroot
			$jailkit_chroot_userhome = $this->_get_home_dir($this->data['new']['username']);
			$jailkit_chroot_puserhome = $this->_get_home_dir($this->data['new']['puser']);
			
			if(!is_dir($this->data['new']['dir'].'/etc')) mkdir($this->data['new']['dir'].'/etc', 0755);
			if(!is_file($this->data['new']['dir'].'/etc/passwd')) touch($this->data['new']['dir'].'/etc/passwd', 0755);
			
			// IMPORTANT!
			// ALWAYS create the user. Even if the user was created before
			// if we check if the user exists, then a update (no shell -> jailkit) will not work
			// and the user has FULL ACCESS to the root of the server!
			$command = '/usr/local/ispconfig/server/scripts/create_jailkit_user.sh';
			$command .= ' '.escapeshellcmd($this->data['new']['username']);
			$command .= ' '.escapeshellcmd($this->data['new']['dir']);
			$command .= ' '.$jailkit_chroot_userhome;
			$command .= ' '.escapeshellcmd($this->data['new']['shell']);
			$command .= ' '.$this->data['new']['puser'];
			$command .= ' '.$jailkit_chroot_puserhome;
			exec($command);
				
			$this->app->log("Added jailkit user to chroot with command: ".$command,LOGLEVEL_DEBUG);
				
			mkdir(escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_userhome), 0755, true);
			chown(escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_userhome), $this->data['new']['username']);
			chgrp(escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_userhome), $this->data['new']['pgroup']);
				
			$this->app->log("Added created jailkit user home in : ".$this->data['new']['dir'].$jailkit_chroot_userhome,LOGLEVEL_DEBUG);
			
			mkdir(escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_puserhome), 0755, true);
			chown(escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_puserhome), $this->data['new']['puser']);
			chgrp(escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_puserhome), $this->data['new']['pgroup']);
				
			$this->app->log("Added created jailkit parent user home in : ".$this->data['new']['dir'].$jailkit_chroot_puserhome,LOGLEVEL_DEBUG);
			
			/*
			// ssh-rsa authentication variables
			$sshrsa = escapeshellcmd($this->data['new']['ssh_rsa']);
			$usrdir = escapeshellcmd($this->data['new']['dir']).'/'.$jailkit_chroot_userhome;
			$sshdir = escapeshellcmd($this->data['new']['dir']).'/'.$jailkit_chroot_userhome.'/.ssh';
			$sshkeys= escapeshellcmd($this->data['new']['dir']).'/'.$jailkit_chroot_userhome.'/.ssh/authorized_keys';
			global $app;
			
			// determine the client id
			$id = $this->data['new']['sys_groupid'];
			if ($id>0) $id = $id -1;
			
			$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE client_id  = ".$id);
			$userkey = $user['ssh_rsa'];
			$username= $user['username'];
			
			// If this user has no key yet, generate a pair
			if ($userkey == '') 
			{
				//Generate ssh-rsa-keys
				exec('ssh-keygen -t rsa -C '.$username.'-rsa-key-'.time().' -f /tmp/id_rsa -N ""');
				
				$privatekey = file_get_contents('/tmp/id_rsa');
				$publickey  = file_get_contents('/tmp/id_rsa.pub');
				
				exec('rm -f /tmp/id_rsa /tmp/id_rsa.pub');
				
				// Set the missing keypair
				$app->db->query("UPDATE sys_user SET id_rsa='$privatekey' ,ssh_rsa='$publickey' WHERE client_id = ".$id);
				$userkey = $publickey;
				
				$this->app->log("ssh-rsa keypair generated for ".$username,LOGLEVEL_DEBUG);
			
			};
			
			if (!file_exists($sshkeys))
			{
				// add root's key
				exec("mkdir '$sshdir'");
				exec("cat /root/.ssh/authorized_keys > '$sshkeys'");
				exec("echo '' >> '$sshkeys'");
			
				// add the user's key
				exec("echo '$userkey' >> '$sshkeys'");
				exec("echo '' >> '$sshkeys'");
			}
			// add the custom key 
			exec("echo '$sshrsa' >> '$sshkeys'");
			exec("echo '' >> '$sshkeys'");
			
			// set proper file permissions
			exec("chown -R ".escapeshellcmd($this->data['new']['puser']).":".escapeshellcmd($this->data['new']['pgroup'])." ".$usrdir);
			exec("chmod 600 '$sshkeys'");
			
			$this->app->log("ssh-rsa key added to ".$sshkeys,LOGLEVEL_DEBUG);
			*/
	}
	
	//* Update the website root directory permissions depending on the security level
	function _update_website_security_level() {
		global $app,$conf;
		
		// load the server configuration options
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');
		
		// Get the parent website of this shell user
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".$this->data['new']['parent_domain_id']);
		
		//* If the security level is set to high
		if($web_config['security_level'] == 20) {
			$this->_exec('chmod 755 '.escapeshellcmd($web["document_root"]));
			$this->_exec('chown root:root '.escapeshellcmd($web["document_root"]));
		}
		
	}
	
	//* Wrapper for exec function for easier debugging
	private function _exec($command) {
		global $app;
		$app->log('exec: '.$command,LOGLEVEL_DEBUG);
		exec($command);
	}

	private function _setup_ssh_rsa() {
		$this->app->log("ssh-rsa setup shelluser_jailkit",LOGLEVEL_DEBUG); 
		// Get the client ID, username, and the key
		$domain_data = $this->app->db->queryOneRecord('SELECT sys_groupid FROM web_domain WHERE web_domain.domain_id = '.intval($this->data['new']['parent_domain_id']));
		$sys_group_data = $this->app->db->queryOneRecord('SELECT * FROM sys_group WHERE sys_group.groupid = '.intval($domain_data['sys_groupid']));
		$id = intval($sys_group_data['client_id']);
		$username= $sys_group_data['name'];
		$client_data = $this->app->db->queryOneRecord('SELECT * FROM client WHERE client.client_id = '.$id);
		$userkey = $client_data['ssh_rsa'];
		unset($domain_data);
		unset($client_data);
		
		// ssh-rsa authentication variables
		$sshrsa = escapeshellcmd($this->data['new']['ssh_rsa']);
		$usrdir = escapeshellcmd($this->data['new']['dir']).'/'.$this->_get_home_dir($this->data['new']['username']);
			$sshdir = $usrdir.'/.ssh';
			$sshkeys= $usrdir.'/.ssh/authorized_keys';
		
		// If this user has no key yet, generate a pair
		if ($userkey == '' && $id>0) 
		{
			//Generate ssh-rsa-keys
			exec('ssh-keygen -t rsa -C '.$username.'-rsa-key-'.time().' -f /tmp/id_rsa -N ""');
			// save keypair in client table
			$this->app->db->query("UPDATE client SET created_at = ".time().", id_rsa = '".file_get_contents('/tmp/id_rsa')."', ssh_rsa = '".file_get_contents('/tmp/id_rsa.pub')."' WHERE client_id = ".$id);
			// and use the public key that has been generated
			$userkey = file_get_contents('/tmp/id_rsa.pub')
			;
			exec('rm -f /tmp/id_rsa /tmp/id_rsa.pub');
			$this->app->log("ssh-rsa keypair generated for ".$username,LOGLEVEL_DEBUG);
		};
		
		if (!file_exists($sshkeys))
		{
			// add root's key
			exec("mkdir '$sshdir'");
			exec("cat /root/.ssh/authorized_keys > '$sshkeys'");
			exec("echo '' >> '$sshkeys'");
		
			// add the user's key
			exec("echo '$userkey' >> '$sshkeys'");
			exec("echo '' >> '$sshkeys'");
			$this->app->log("ssh-rsa authorisation keyfile created in ".$sshkeys,LOGLEVEL_DEBUG);
		}
		if ($sshrsa!=''){
			// add the custom key 
			exec("echo '$sshrsa' >> '$sshkeys'");
			exec("echo '' >> '$sshkeys'");
			$this->app->log("ssh-rsa key updated in ".$sshkeys,LOGLEVEL_DEBUG);
		}
		// set proper file permissions
		exec("chown -R ".escapeshellcmd($this->data['new']['puser']).":".escapeshellcmd($this->data['new']['pgroup'])." ".$usrdir);
		exec("chmod 600 '$sshkeys'");
		
	}
} // end class

?>
