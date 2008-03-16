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
	
		
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		//* Mailboxes
		$app->plugins->registerEvent('mail_user_insert',$this->plugin_name,'user_insert');
		$app->plugins->registerEvent('mail_user_update',$this->plugin_name,'user_update');
		$app->plugins->registerEvent('mail_user_delete',$this->plugin_name,'user_delete');
		
		//* Mail Domains
		//$app->plugins->registerEvent('mail_domain_insert',$this->plugin_name,'domain_insert');
		//$app->plugins->registerEvent('mail_domain_update',$this->plugin_name,'domain_update');
		//$app->plugins->registerEvent('mail_domain_delete',$this->plugin_name,'domain_delete');
		
	}
	
	
	function user_insert($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		
		// Create the maildir, if it does not exist
		if(!is_dir($data['new']['maildir'])) {
			exec('mkdir -p '.escapeshellcmd($data['new']['maildir']));
			exec('maildirmake '.escapeshellcmd($data['new']['maildir']));
			exec('chown '.$mail_config['mailuser_name'].':'.$mail_config['mailuser_group'].' '.escapeshellcmd($data['new']['maildir']));
			$app->log('Created Maildir: '.$data['new']['maildir'],LOGLEVEL_DEBUG);
		}
	}
	
	function user_update($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		
		// Create the maildir, if it does not exist
		if(!is_dir($data['new']['maildir'])) {
			exec('mkdir -p '.escapeshellcmd($data['new']['maildir']));
			exec('chown '.$mail_config['mailuser_name'].':'.$mail_config['mailuser_group'].' '.escapeshellcmd($data['new']['maildir']));
			$app->log('Created Maildir: '.$data['new']['maildir'],LOGLEVEL_DEBUG);
		}
		
		// Move mailbox, if domain has changed and delete old mailbox
		if($data['new']['maildir'] != $data['old']['maildir'] && is_dir($data['old']['maildir'])) {
			exec('mv -f '.escapeshellcmd($data['old']['maildir']).'* '.escapeshellcmd($data['new']['maildir']));
			if(is_file($data['old']['maildir'].'.ispconfig_mailsize'))exec('mv -f '.escapeshellcmd($data['old']['maildir']).'.ispconfig_mailsize '.escapeshellcmd($data['new']['maildir']));
			rmdir($data['old']['maildir']);
			$app->log('Moved Maildir from: '.$data['old']['maildir'].' to '.$data['new']['maildir'],LOGLEVEL_DEBUG);
		}
	}
	
	function user_delete($event_name,$data) {
		global $app, $conf;
		
		$old_maildir_path = escapeshellcmd($data['old']['maildir']);
		if(!stristr($old_maildir_path,'..') && !stristr($old_maildir_path,'*') && strlen($old_maildir_path) >= 10) {
			exec('rm -rf '.escapeshellcmd($old_maildir_path));
			$app->log('Deleted the Maildir: '.$data['old']['maildir'],LOGLEVEL_DEBUG);
		} else {
			$app->log('Possible security violation when deleting the maildir: '.$data['old']['maildir'],LOGLEVEL_ERROR);
		}
	}
	
	
	

} // end class

?>