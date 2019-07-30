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
		
		//* global mail access filters
		$app->plugins->registerEvent('mail_access_insert', $this->plugin_name, 'spamfilter_wblist_insert');
		$app->plugins->registerEvent('mail_access_update', $this->plugin_name, 'spamfilter_wblist_update');
		$app->plugins->registerEvent('mail_access_delete', $this->plugin_name, 'spamfilter_wblist_delete');
	}

	function spamfilter_users_insert($event_name, $data) {
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
		$this->action = 'insert';
		// just run the spamfilter_wblist_update function
		$this->spamfilter_wblist_update($event_name, $data);
	}

	function spamfilter_wblist_update($event_name, $data) {
		global $app, $conf;

		$app->uses('getconf,system,functions');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		if(is_dir('/etc/rspamd')) {
			$global_filter = false;
			//* Create the config file
			$filter = null;
			if($event_name === 'mail_access_insert' || $event_name === 'mail_access_update') {
				$global_filter = true;
				$record_id = intval($data['new']['access_id']);
				$wblist_file = $this->users_config_dir.'global_wblist_'.$record_id.'.conf';
				$filter = array(
					'wb' => ($data['new']['access'] === 'OK' ? 'W' : 'B'),
					'from' => ($data['new']['type'] === 'sender' ? $app->functions->idn_encode($data['new']['source']) : ''),
					'rcpt' => ($data['new']['type'] === 'recipient' ? $app->functions->idn_encode($data['new']['source']) : ''),
					'ip' => ($data['new']['type'] === 'client' && $this->_is_valid_ip_address($data['new']['source']) ? $data['new']['source'] : ''),
					'hostname' => ($data['new']['type'] === 'client' && !$this->_is_valid_ip_address($data['new']['source']) ? $data['new']['source'] : '')
				);
			} else {
				$record_id = intval($data['new']['wblist_id']);
				$wblist_file = $this->users_config_dir.'spamfilter_wblist_'.$record_id.'.conf';
				$tmp = $app->db->queryOneRecord("SELECT email FROM spamfilter_users WHERE id = ?", intval($data['new']['rid']));
				if($tmp && !empty($tmp)) {
					$filter = array(
						'wb' => $data['new']['wb'],
						'from' => $app->functions->idn_encode($data['new']['email']),
						'rcpt' => $app->functions->idn_encode($tmp['email']),
						'ip' => '',
						'hostname' => ''
					);
				}
			}
		
			if($data['new']['active'] == 'y' && is_array($filter) && !empty($filter)){
				if(!is_dir($this->users_config_dir)){
					$app->system->mkdirpath($this->users_config_dir);
				}
		
				$app->load('tpl');

				$filter_from = $filter['from'];
				if($filter_from != '') {
					if(strpos($filter_from, '@') === false) {
						$filter_from = '@' . $filter_from;
					} elseif(substr($filter_from, 0, 2) === '*@') {
						$filter_from = substr($filter_from, 1);
					}
				}
				$filter_rcpt = $filter['rcpt'];
				if($filter_rcpt != '') {
					if(strpos($filter_rcpt, '@') === false) {
						$filter_rcpt = '@' . $filter_rcpt;
					} elseif(substr($filter_rcpt, 0, 2) === '*@') {
						$filter_rcpt = substr($filter_rcpt, 1);
					}
				}
				
				$tpl = new tpl();
				$tpl->newTemplate('rspamd_wblist.inc.conf.master');
				$tpl->setVar('list_scope', ($global_filter ? 'global' : 'spamfilter'));
				$tpl->setVar('record_id', $record_id);
				// we need to add 10 to priority to avoid mailbox/domain spamfilter settings overriding white/blacklists
				$tpl->setVar('priority', intval($data['new']['priority']) + ($global_filter ? 20 : 10));
				$tpl->setVar('from', $filter_from);
				$tpl->setVar('recipient', $filter_rcpt);
				$tpl->setVar('hostname', $filter['hostname']);
				$tpl->setVar('ip', $filter['ip']);
				$tpl->setVar('wblist', $filter['wb']);
		
				$app->system->file_put_contents($wblist_file, $tpl->grab());
			} elseif(is_file($wblist_file)) {
				unlink($wblist_file);
			}
			
			if($mail_config['content_filter'] == 'rspamd' && is_file('/etc/init.d/rspamd')) {
				$app->services->restartServiceDelayed('rspamd', 'reload');
			}
		}
	}
	
	function spamfilter_wblist_delete($event_name, $data) {
		global $app, $conf;

		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		if(is_dir('/etc/rspamd')) {
			//* delete the config file
			if($event_name === 'mail_access_delete') {
				$wblist_file = $this->users_config_dir.'global_wblist_'.intval($data['old']['access_id']).'.conf';
			} else {
				$wblist_file = $this->users_config_dir.'spamfilter_wblist_'.intval($data['old']['wblist_id']).'.conf';
			}
			if(is_file($wblist_file)) {
				unlink($wblist_file);
			}

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
	
	private function _is_valid_ip_address($ip) {
		if(function_exists('filter_var')) {
			if(!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	
} // end class
