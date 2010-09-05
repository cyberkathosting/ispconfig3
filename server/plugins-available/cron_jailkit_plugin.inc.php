<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
Modified 2009, Marius Cramer, pixcept KG
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

class cron_jailkit_plugin {
	
	//* $plugin_name and $class_name have to be the same then the name of this class
	var $plugin_name = 'cron_jailkit_plugin';
	var $class_name = 'cron_jailkit_plugin';
	
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
		
        $app->plugins->registerEvent('cron_insert', $this->plugin_name, 'insert');
        $app->plugins->registerEvent('cron_update', $this->plugin_name, 'update');
        $app->plugins->registerEvent('cron_delete', $this->plugin_name, 'delete');
		
	}
	
	//* This function is called, when a cron job is inserted in the database
	function insert($event_name,$data) {
		global $app, $conf;
		
        if($data["new"]["parent_domain_id"] == '') {
            $app->log("Parent domain not set",LOGLEVEL_WARN);
            return 0;
        }
        
        //* get data from web
        $parent_domain = $app->db->queryOneRecord("SELECT `domain_id`, `system_user`, `system_group`, `document_root`, `domain` FROM `web_domain` WHERE `domain_id` = ".intval($data["new"]["parent_domain_id"]));
        if(!$parent_domain["domain_id"]) {
            $app->log("Parent domain not found",LOGLEVEL_WARN);
            return 0;
        } elseif($parent_domain["system_user"] == 'root' or $parent_domain["system_group"] == 'root') {
            $app->log("Websites (and Crons) cannot be owned by the root user or group.",LOGLEVEL_WARN);
            return 0;
        }
		
		$this->parent_domain = $parent_domain;
		
        $app->uses('system');
		
		if($app->system->is_user($parent_domain['system_user'])) {
		
			/**
		 	* Setup Jailkit Chroot System If Enabled 
		 	*/
			if ($data['new']['type'] == "chrooted")
			{
				// load the server configuration options
				/*
				$app->uses("getconf");
				$this->data = $data;
				$this->app = $app;
				$this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');
                $this->parent_domain = $parent_domain;
			
				$this->_setup_jailkit_chroot();
				
				//$command .= 'usermod -U '.escapeshellcmd($parent_domain['system_user']);
				//exec($command);
				
				$this->_add_jailkit_user();
				*/
				$app->uses("getconf");
				$this->data = $data;
				$this->app = $app;
				$this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');
				
				$this->_update_website_security_level();
			
				$this->_setup_jailkit_chroot();
				
				$this->_add_jailkit_user();
				
				$command .= 'usermod -U '.escapeshellcmd($parent_domain["system_user"]);
				exec($command);
				
				$this->_update_website_security_level();
			}
		
			$app->log("Jailkit Plugin (Cron) -> insert username:".$parent_domain['system_user'],LOGLEVEL_DEBUG);
			
		} else {
			$app->log("Jailkit Plugin (Cron) -> insert username:".$parent_domain['system_user']." skipped, the user does not exist.",LOGLEVEL_WARN);
		}
		
	}
	
	//* This function is called, when a cron job is updated in the database
	function update($event_name,$data) {
		global $app, $conf;
		
        if($data["new"]["parent_domain_id"] == '') {
            $app->log("Parent domain not set",LOGLEVEL_WARN);
            return 0;
        }
        //* get data from web
        $parent_domain = $app->db->queryOneRecord("SELECT `domain_id`, `system_user`, `system_group`, `document_root`, `domain` FROM `web_domain` WHERE `domain_id` = ".intval($data["new"]["parent_domain_id"]));
        if(!$parent_domain["domain_id"]) {
            $app->log("Parent domain not found",LOGLEVEL_WARN);
            return 0;
        } elseif($parent_domain["system_user"] == 'root' or $parent_domain["system_group"] == 'root') {
            $app->log("Websites (and Crons) cannot be owned by the root user or group.",LOGLEVEL_WARN);
            return 0;
        }
		
        $app->uses('system');
		
		$this->parent_domain = $parent_domain;
		
		if($app->system->is_user($parent_domain['system_user'])) {
        
			/**
		 	* Setup Jailkit Chroot System If Enabled 
		 	*/
			if ($data['new']['type'] == "chrooted")
			{
                $app->log("Jailkit Plugin (Cron) -> setting up jail", LOGLEVEL_DEBUG);
				// load the server configuration options
				/*
				$app->uses("getconf");
				$this->data = $data;
				$this->app = $app;
				$this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');
                $this->parent_domain = $parent_domain;
			
				$this->_setup_jailkit_chroot();
				$this->_add_jailkit_user();
				*/
				$app->uses("getconf");
				$this->data = $data;
				$this->app = $app;
				$this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');
				
				$this->_update_website_security_level();
			
				$this->_setup_jailkit_chroot();
				$this->_add_jailkit_user();
				
				$this->_update_website_security_level();
			}
		
			$app->log("Jailkit Plugin (Cron) -> update username:".$parent_domain['system_user'],LOGLEVEL_DEBUG);
			
		} else {
			$app->log("Jailkit Plugin (Cron) -> update username:".$parent_domain['system_user']." skipped, the user does not exist.",LOGLEVEL_WARN);
		}
		
	}
	
	//* This function is called, when a cron job is deleted in the database
	function delete($event_name,$data) {
		global $app, $conf;
		
		//* nothing to do here!
		
	}
	
	function _setup_jailkit_chroot()
	{
			//check if the chroot environment is created yet if not create it with a list of program sections from the config
			if (!is_dir($this->parent_domain['document_root'].'/etc/jailkit'))
			{
				$command = '/usr/local/ispconfig/server/scripts/create_jailkit_chroot.sh';
				$command .= ' '.escapeshellcmd($this->parent_domain['document_root']);
				$command .= ' \''.$this->jailkit_config['jailkit_chroot_app_sections'].'\'';
				exec($command);
				
				$this->app->log("Added jailkit chroot with command: ".$command,LOGLEVEL_DEBUG);
				
				//$this->_add_jailkit_programs(); // done later on
				
				$this->app->load('tpl');
		
				$tpl = new tpl();
				$tpl->newTemplate("bash.bashrc.master");
				
				$tpl->setVar('jailkit_chroot',true);
				$tpl->setVar('domain',$this->parent_domain['domain']);
                $tpl->setVar('home_dir',$this->_get_home_dir(""));
				
				$bashrc = escapeshellcmd($this->parent_domain['document_root']).'/etc/bash.bashrc';
				if(@is_file($bashrc)) unlink($bashrc);
				
				file_put_contents($bashrc,$tpl->grab());
				unset($tpl);
				
				$this->app->log('Added bashrc script: '.$bashrc,LOGLEVEL_DEBUG);
				
				$tpl = new tpl();
				$tpl->newTemplate('motd.master');
				
				$tpl->setVar('domain',$this->parent_domain['domain']);
				
				$motd = escapeshellcmd($this->parent_domain['document_root']).'/var/run/motd';
				if(@is_file($motd)) unlink($motd);
				
				file_put_contents($motd,$tpl->grab());
				
			}
            $this->_add_jailkit_programs();
	}
	
	function _add_jailkit_programs()
	{
		//copy over further programs and its libraries
		$command = '/usr/local/ispconfig/server/scripts/create_jailkit_programs.sh';
		$command .= ' '.escapeshellcmd($this->parent_domain['document_root']);
		$command .= ' \''.$this->jailkit_config['jailkit_chroot_app_programs'].'\'';
		exec($command);
		
		$this->app->log("Added programs to jailkit chroot with command: ".$command,LOGLEVEL_DEBUG);
        
        $command = '/usr/local/ispconfig/server/scripts/create_jailkit_programs.sh';
        $command .= ' '.escapeshellcmd($this->parent_domain['document_root']);
        $command .= ' \''.$this->jailkit_config['jailkit_chroot_cron_programs'].'\'';
        exec($command);
        
        $this->app->log("Added cron programs to jailkit chroot with command: ".$command,LOGLEVEL_DEBUG);
	}
	
	function _add_jailkit_user()
	{
			//add the user to the chroot
            $jailkit_chroot_userhome = $this->_get_home_dir($this->parent_domain['system_user']);
			
			if(!is_dir($this->parent_domain['document_root'].'/etc')) mkdir($this->parent_domain['document_root'].'/etc');
			if(!is_file($this->parent_domain['document_root'].'/etc/passwd')) exec('touch '.$this->parent_domain['document_root'].'/etc/passwd');
			
			// IMPORTANT!
			// ALWAYS create the user. Even if the user was created before
			// if we check if the user exists, then a update (no shell -> jailkit) will not work
			// and the user has FULL ACCESS to the root of the server!
			$command = '/usr/local/ispconfig/server/scripts/create_jailkit_user.sh';
			$command .= ' '.escapeshellcmd($this->parent_domain['system_user']);
			$command .= ' '.escapeshellcmd($this->parent_domain['document_root']);
			$command .= ' '.$jailkit_chroot_userhome;
			$command .= ' '.escapeshellcmd("/bin/bash");
			exec($command);
				
			$this->app->log("Added jailkit user to chroot with command: ".$command,LOGLEVEL_DEBUG);
				
			mkdir(escapeshellcmd($this->parent_domain['document_root'].$jailkit_chroot_userhome), 0755, true);
	}
	
    function _get_home_dir($username)
    {
        return str_replace("[username]",escapeshellcmd($username),$this->jailkit_config["jailkit_chroot_home"]);
    }
	
	//* Update the website root directory permissions depending on the security level
	function _update_website_security_level() {
		global $app,$conf;
		
		// load the server configuration options
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');
				
		//* If the security level is set to high
		if($web_config['security_level'] == 20) {
			$this->_exec('chmod 755 '.escapeshellcmd($this->parent_domain['document_root']));
			$this->_exec('chown root:root '.escapeshellcmd($this->parent_domain['document_root']));
		}
		
	}
	
	//* Wrapper for exec function for easier debugging
	private function _exec($command) {
		global $app;
		$app->log('exec: '.$command,LOGLEVEL_DEBUG);
		exec($command);
	}
    
	

} // end class

?>
