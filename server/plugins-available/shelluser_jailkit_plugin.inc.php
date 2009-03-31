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
			
				$this->_setup_jailkit_chroot();
				
				$command .= 'usermod -U '.escapeshellcmd($data['new']['username']);
				exec($command);
				
				$this->_add_jailkit_user();
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
			
				$this->_setup_jailkit_chroot();
				$this->_add_jailkit_user();
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
				$command = 'userdel -f -r';
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
				if(@is_file($bashrc)) exec('rm '.$bashrc);
				
				file_put_contents($bashrc,$tpl->grab());
				unset($tpl);
				
				$this->app->log("Added bashrc scrpt : ".$bashrc,LOGLEVEL_DEBUG);
				
				$tpl = new tpl();
				$tpl->newTemplate("motd.master");
				
				$tpl->setVar('domain',$web['domain']);
				
				$motd = escapeshellcmd($this->data['new']['dir']).'/var/run/motd';
				if(@is_file($motd)) exec('rm '.$motd);
				
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
		return str_replace("[username]",escapeshellcmd($username),$this->jailkit_config["jailkit_chroot_home"]);
	}
	
	function _add_jailkit_user()
	{
			//add the user to the chroot
			$jailkit_chroot_userhome = $this->_get_home_dir($this->data['new']['username']);
			$jailkit_chroot_puserhome = $this->_get_home_dir($this->data['new']['puser']);
			
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
				
			exec("mkdir -p ".escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_userhome));
			exec("chown ".$this->data['new']['username'].":".$this->data['new']['pgroup']." ".escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_userhome));
				
			$this->app->log("Added created jailkit user home in : ".$this->data['new']['dir'].$jailkit_chroot_userhome,LOGLEVEL_DEBUG);
			
			exec("mkdir -p ".escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_puserhome));
			exec("chown ".$this->data['new']['puser'].":".$this->data['new']['pgroup']." ".escapeshellcmd($this->data['new']['dir'].$jailkit_chroot_puserhome));
				
			$this->app->log("Added created jailkit parent user home in : ".$this->data['new']['dir'].$jailkit_chroot_puserhome,LOGLEVEL_DEBUG);
	}
	
	

} // end class

?>