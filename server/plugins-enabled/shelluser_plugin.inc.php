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

class apache2_plugin {
	
	var $plugin_name = 'apache2_plugin';
	var $class_name = 'apache2_plugin';
	
		
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		$app->plugins->registerEvent('web_domain_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('web_domain_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('web_domain_delete',$this->plugin_name,'delete');

		
	}
	
	
	function insert($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		// Get the UID of the parent user
		$uid = intval($app->system->getuid($data['new']['puser']));
		if($uid > 999) {
			$command = 'useradd';
			$command .= ' --home '.escapeshellcmd($data['new']['dir']);
			$command .= ' --gid '.escapeshellcmd($data['new']['pgroup']);
			$command .= ' --non-unique ';
			$command .= ' --password '.escapeshellcmd($data['new']['password']);
			$command .= ' --shell '.escapeshellcmd($data['new']['shell']);
			$command .= ' --uid '.escapeshellcmd($uid);
			$command .= ' '.escapeshellcmd($data['new']['username']);
			
			exec($command);
			$app->log("Added shelluser: ".$data['new']['username'],LOGLEVEL_DEBUG);
			
		} else {
			$app->log("UID = $uid for shelluser:".$data['new']['username']." not allowed.",LOGLEVEL_ERROR);
		}
	}
	
	function update($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		// Get the UID of the parent user
		$uid = intval($app->system->getuid($data['new']['puser']));
		if($uid > 999) {
			$command = 'usermod';
			$command .= ' --home '.escapeshellcmd($data['new']['dir']);
			$command .= ' --gid '.escapeshellcmd($data['new']['pgroup']);
			$command .= ' --non-unique ';
			$command .= ' --password '.escapeshellcmd($data['new']['password']);
			$command .= ' --shell '.escapeshellcmd($data['new']['shell']);
			$command .= ' --uid '.escapeshellcmd($uid);
			$command .= ' --login '.escapeshellcmd($data['new']['username']);
			$command .= ' '.escapeshellcmd($data['old']['username']);
			
			exec($command);
			$app->log("Updated shelluser: ".$data['new']['username'],LOGLEVEL_DEBUG);
			
		} else {
			$app->log("UID = $uid for shelluser:".$data['new']['username']." not allowed.",LOGLEVEL_ERROR);
		}
		
	}
	
	function delete($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');
		
		// Get the UID of the user
		$userid = intval($app->system->getuid($data['old']['username']));
		if($userid > 999) {
			$command = 'userdel';
			$command .= ' '.escapeshellcmd($data['old']['username']);
			
			exec($command);
			$app->log("Deleted shelluser: ".$data['old']['username'],LOGLEVEL_DEBUG);
			
		} else {
			$app->log("UID = $userid for shelluser:".$data['new']['username']." not allowed.",LOGLEVEL_ERROR);
		}
		
	}
	
	
	

} // end class

?>