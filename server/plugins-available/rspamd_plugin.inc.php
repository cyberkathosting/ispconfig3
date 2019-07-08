<?php

/*
Copyright (c) 2018, Falko Timme, Timme Hosting GmbH & Co. KG
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

class rspamd_plugin {

	var $plugin_name = 'rspamd_plugin';
	var $class_name  = 'rspamd_plugin';
	var $users_config_dir = '/etc/rspamd/local.d/users/';

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

		//* spamfilter_users
		$app->plugins->registerEvent('spamfilter_users_insert', $this->plugin_name, 'spamfilter_users_insert');
		$app->plugins->registerEvent('spamfilter_users_update', $this->plugin_name, 'spamfilter_users_update');
		$app->plugins->registerEvent('spamfilter_users_delete', $this->plugin_name, 'spamfilter_users_delete');

		//* spamfilter_wblist
		$app->plugins->registerEvent('spamfilter_wblist_insert', $this->plugin_name, 'spamfilter_wblist_insert');
		$app->plugins->registerEvent('spamfilter_wblist_update', $this->plugin_name, 'spamfilter_wblist_update');
		$app->plugins->registerEvent('spamfilter_wblist_delete', $this->plugin_name, 'spamfilter_wblist_delete');
		
		//* server ip
		$app->plugins->registerEvent('server_ip_insert', $this->plugin_name, 'server_ip');
		$app->plugins->registerEvent('server_ip_update', $this->plugin_name, 'server_ip');
		$app->plugins->registerEvent('server_ip_delete', $this->plugin_name, 'server_ip');
	}

	function spamfilter_users_insert($event_name, $data) {
		global $app, $conf;

		$this->action = 'insert';
		// just run the spamfilter_users_update function
		$this->spamfilter_users_update($event_name, $data);
	}

	function spamfilter_users_update($event_name, $data) {
		global $app, $conf;

		// get the config
		$app->uses('getconf,system,functions');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		if(is_dir('/etc/rspamd')) {
			$policy = $app->db->queryOneRecord("SELECT * FROM spamfilter_policy WHERE id = ?", intval($data['new']['policy_id']));
			
			//* Create the config file
			$user_file = $this->users_config_dir.'spamfilter_user_'.intval($data['new']['id']).'.conf';
		
			if(is_array($policy) && !empty($policy)){
				if(!is_dir($this->users_config_dir)){
					$app->system->mkdirpath($this->users_config_dir);
				}
		
				$app->load('tpl');

				$tpl = new tpl();
				$tpl->newTemplate('rspamd_users.inc.conf.master');
				$tpl->setVar('record_id', intval($data['new']['id']));
				$tpl->setVar('priority', intval($data['new']['priority']));
				$tpl->setVar('email', $app->functions->idn_encode($data['new']['email']));
				$tpl->setVar('local', $data['new']['local']);
				
				$tpl->setVar('rspamd_greylisting', $policy['rspamd_greylisting']);
				$tpl->setVar('rspamd_spam_greylisting_level', floatval($policy['rspamd_spam_greylisting_level']));
				
				$tpl->setVar('rspamd_spam_tag_level', floatval($policy['rspamd_spam_tag_level']));
				$tpl->setVar('rspamd_spam_tag_method', $policy['rspamd_spam_tag_method']);
				
				$tpl->setVar('rspamd_spam_kill_level', floatval($policy['rspamd_spam_kill_level']));
				$tpl->setVar('rspamd_virus_kill_level', floatval($policy['rspamd_spam_kill_level']) + 1000);
				
				$spam_lover_virus_lover = '';
				if($policy['spam_lover'] == 'Y' && $policy['virus_lover'] == 'Y') $spam_lover_virus_lover = 'spam_lover_AND_virus_lover';
				if($policy['spam_lover'] == 'Y' && $policy['virus_lover'] != 'Y') $spam_lover_virus_lover = 'spam_lover_AND_NOTvirus_lover';
				if($policy['spam_lover'] != 'Y' && $policy['virus_lover'] == 'Y') $spam_lover_virus_lover = 'NOTspam_lover_AND_virus_lover';
				if($policy['spam_lover'] != 'Y' && $policy['virus_lover'] != 'Y') $spam_lover_virus_lover = 'NOTspam_lover_AND_NOTvirus_lover';
				
				$tpl->setVar('spam_lover_virus_lover', $spam_lover_virus_lover);
				
				//$groups_disabled = array();
				//if($policy['virus_lover'] == 'Y') $groups_disabled[] = '';
		
				$app->system->file_put_contents($user_file, $tpl->grab());
			} else {
				if(is_file($user_file)) {
					unlink($user_file);
				}
			}
			
			if($mail_config['content_filter'] == 'rspamd'){
				if(is_file('/etc/init.d/rspamd')) $app->services->restartServiceDelayed('rspamd', 'reload');
			}
		}
	}

	function spamfilter_users_delete($event_name, $data) {
		global $app, $conf;

		// get the config
		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		if(is_dir('/etc/rspamd')) {
			//* delete the config file
			$user_file = $this->users_config_dir.'spamfilter_user_'.intval($data['old']['id']).'.conf';
			if(is_file($user_file)) unlink($user_file);
			
		}
		
		if($mail_config['content_filter'] == 'rspamd') {
			if(is_file('/etc/init.d/rspamd')) $app->services->restartServiceDelayed('rspamd', 'reload');
		}
	}

	function spamfilter_wblist_insert($event_name, $data) {
		global $app, $conf;
		
		$this->action = 'insert';
		// just run the spamfilter_wblist_update function
		$this->spamfilter_wblist_update($event_name, $data);
	}

	function spamfilter_wblist_update($event_name, $data) {
		global $app, $conf;

		$app->uses('getconf,system,functions');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		if(is_dir('/etc/rspamd')) {
			$recipient = $app->db->queryOneRecord("SELECT email FROM spamfilter_users WHERE id = ?", intval($data['new']['rid']));
			//* Create the config file
			$wblist_file = $this->users_config_dir.'spamfilter_wblist_'.intval($data['new']['wblist_id']).'.conf';
		
			if($data['new']['active'] == 'y' && is_array($recipient) && !empty($recipient)){
				if(!is_dir($this->users_config_dir)){
					$app->system->mkdirpath($this->users_config_dir);
				}
		
				$app->load('tpl');

				$tpl = new tpl();
				$tpl->newTemplate('rspamd_wblist.inc.conf.master');
				$tpl->setVar('record_id', intval($data['new']['wblist_id']));
				$tpl->setVar('priority', intval($data['new']['priority']));
				$tpl->setVar('from', $app->functions->idn_encode($data['new']['email']));
				$tpl->setVar('recipient', $app->functions->idn_encode($recipient['email']));
				//$tpl->setVar('action', ($data['new']['wb'] == 'W'? 'want_spam = yes;' : 'action = "reject";'));
				$tpl->setVar('wblist', $data['new']['wb']);
		
				$app->system->file_put_contents($wblist_file, $tpl->grab());
			} else {
				if(is_file($wblist_file)) unlink($wblist_file);
			}
			
			if($mail_config['content_filter'] == 'rspamd'){
				if(is_file('/etc/init.d/rspamd')) $app->services->restartServiceDelayed('rspamd', 'reload');
			}
		}
	}
	
	function spamfilter_wblist_delete($event_name, $data) {
		global $app, $conf;

		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		if(is_dir('/etc/rspamd')) {
			//* delete the config file
			$wblist_file = $this->users_config_dir.'spamfilter_wblist_'.intval($data['old']['wblist_id']).'.conf';
			if(is_file($wblist_file)) unlink($wblist_file);
			
			if($mail_config['content_filter'] == 'rspamd'){
				if(is_file('/etc/init.d/rspamd')) $app->services->restartServiceDelayed('rspamd', 'reload');
			}
		}
	}

	function server_ip($event_name, $data) {
		global $app, $conf;
 
		// get the config
		$app->uses("getconf,system");
		$app->load('tpl');

		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		if(is_dir('/etc/rspamd')) {
			$tpl = new tpl();
			$tpl->newTemplate('rspamd_users.conf.master');
				
			$whitelist_ips = array();
			$ips = $app->db->queryAllRecords("SELECT * FROM server_ip WHERE server_id = ?", $conf['server_id']);
			if(is_array($ips) && !empty($ips)){
				foreach($ips as $ip){
					$whitelist_ips[] = array('ip' => $ip['ip_address']);
				}
			}
			$tpl->setLoop('whitelist_ips', $whitelist_ips);
			$app->system->file_put_contents('/etc/rspamd/local.d/users.conf', $tpl->grab());
				
			if($mail_config['content_filter'] == 'rspamd'){
				$app->services->restartServiceDelayed('rspamd', 'reload');
			}
		}
	}
	
} // end class
