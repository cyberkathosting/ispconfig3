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

	private function isValidEmail($email) {
		$atIndex = strrpos($email, '@');
		if($atIndex === false) {
			return false;
		}

		$domain = substr($email, $atIndex + 1);
		$local = substr($email, 0, $atIndex);
		$localLen = strlen($local);
		$domainLen = strlen($domain);
		if($localLen > 64) {
			return false;
		} elseif($domainLen < 1 || $domainLen > 255) {
			return false;
		} elseif(substr($local, 0, 1) == '.' || substr($local, -1, 1) == '.') {
			return false; // first or last sign is dot
		} elseif(strpos($local, '..') !== false) {
			return false; // two dots not allowed
		} elseif(!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
			return false; // invalid character
		} elseif(strpos($domain, '..') !== false) {
			return false; // two dots not allowed
		} elseif($local && !preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
			// character not valid in local part unless
			// local part is quoted
			if(!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
				return false;
			}
		}

		$domain_array = explode('.', $domain);
		for($i = 0; $i < count($domain_array); $i++) {
			if(!preg_match("/^(([A-Za-z0-9!#$%&'*+\/=?^_`{|}~-][A-Za-z0-9!#$%&'*+\/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $domain_array[$i])) {
				return false;
			}
		}

		if(!preg_match("/^\[?[0-9\.]+\]?$/", $domain)) {
			$domain_array = explode('.', $domain);
			if(count($domain_array) < 2) {
				return false; // Not enough parts to domain
			}

			for($i = 0; $i < count($domain_array); $i++) {
				if(!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i])) {
					return false;
				}
			}
		}

		return true;
	}

	/*
	 	This function is called when the plugin is loaded
	*/

	function onLoad() {
		global $app;

		/*
		Register for the events
		*/

		//* spamfilter_wblist
		$app->plugins->registerEvent('spamfilter_wblist_insert', $this->plugin_name, 'spamfilter_wblist_insert');
		$app->plugins->registerEvent('spamfilter_wblist_update', $this->plugin_name, 'spamfilter_wblist_update');
		$app->plugins->registerEvent('spamfilter_wblist_delete', $this->plugin_name, 'spamfilter_wblist_delete');

		//* global mail access filters
		$app->plugins->registerEvent('mail_access_insert', $this->plugin_name, 'spamfilter_wblist_insert');
		$app->plugins->registerEvent('mail_access_update', $this->plugin_name, 'spamfilter_wblist_update');
		$app->plugins->registerEvent('mail_access_delete', $this->plugin_name, 'spamfilter_wblist_delete');

		//* server ip
		$app->plugins->registerEvent('server_ip_insert', $this->plugin_name, 'server_ip');
		$app->plugins->registerEvent('server_ip_update', $this->plugin_name, 'server_ip');
		$app->plugins->registerEvent('server_ip_delete', $this->plugin_name, 'server_ip');

		//* spamfilter_users
		$app->plugins->registerEvent('spamfilter_users_insert', $this->plugin_name, 'user_settings_update');
		$app->plugins->registerEvent('spamfilter_users_update', $this->plugin_name, 'user_settings_update');
		$app->plugins->registerEvent('spamfilter_users_delete', $this->plugin_name, 'user_settings_update');

		//* mail user / fwd / catchall changed (greylisting)
		$app->plugins->registerEvent('mail_user_insert', $this->plugin_name, 'user_settings_update');
		$app->plugins->registerEvent('mail_user_update', $this->plugin_name, 'user_settings_update');
		$app->plugins->registerEvent('mail_user_delete', $this->plugin_name, 'user_settings_update');
		$app->plugins->registerEvent('mail_forwarding_insert', $this->plugin_name, 'user_settings_update');
		$app->plugins->registerEvent('mail_forwarding_update', $this->plugin_name, 'user_settings_update');
		$app->plugins->registerEvent('mail_forwarding_delete', $this->plugin_name, 'user_settings_update');
	}

	function user_settings_update($event_name, $data) {
		global $app, $conf;

		if(!is_dir('/etc/rspamd')) {
			return;
		}

		$use_data = 'new';
		if(substr($event_name, -7) === '_delete') {
			$mode = 'delete';
			$use_data = 'old';
		} elseif(substr($event_name, -7) === '_insert') {
			$mode = 'insert';
		} else {
			$mode = 'update';
		}

		// get the config
		$app->uses('getconf,system,functions');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		$type = false;
		$identifier = false;
		$entry_id = false;
		if(substr($event_name, 0, 17) === 'spamfilter_users_') {
			$identifier = 'email';
			$type = 'spamfilter_user';
			$entry_id = $data[$use_data]['id'];
		} elseif(substr($event_name, 0, 16) === 'mail_forwarding_') {
			$identifier = 'source';
			$type = 'mail_forwarding';
			$entry_id = $data[$use_data]['forwarding_id'];
		} elseif(substr($event_name, 0, 10) === 'mail_user_') {
			$identifier = 'email';
			$type = 'mail_user';
			$entry_id = $data[$use_data]['mailuser_id'];
		} else {
			// invalid event
			$app->log('Invalid event name for rspamd_plugin: ' . $event_name, LOGLEVEL_WARN);
			return;
		}

		$is_domain = false;
		$email_address = $data[$use_data][$identifier];
		$settings_name =  $email_address;
		if($email_address === '*@' || $email_address === '@') {
			// we will ignore those global targets
			$app->log('Ignoring @ spamfilter_user as rspamd does not support it this way.', LOGLEVEL_DEBUG);
			return;
		} elseif(!$email_address) {
			// problem reading identifier
			$app->log('Empty email address in rspamd_plugin from identifier: ' . $use_data . '/' . $identifier, LOGLEVEL_WARN);
			return;
		} elseif(substr($email_address, 0, 1) === '@') {
			$settings_name = substr($email_address, 1);
			$is_domain = true;
		} elseif(strpos($email_address, '@') === false) {
			$email_address = '@' . $email_address;
			$is_domain = true;
		}

		if($settings_name == '') {
			// missing settings file name
			$app->log('Empty rspamd identifier in rspamd_plugin from identifier: ' . $use_data . '/' . $identifier, LOGLEVEL_WARN);
			return;
		}

		$settings_file = $this->users_config_dir . str_replace('@', '_', $settings_name) . '.conf';
		//$app->log('Settings file for rspamd is ' . $settings_file, LOGLEVEL_WARN);
		if($mode === 'delete') {
			if(is_file($settings_file)) {
				unlink($settings_file);
			}
		} else {
			$settings_priority = 20;
			if(isset($data[$use_data]['priority'])) {
				$settings_priority = intval($data[$use_data]['priority']);
			} elseif($is_domain === true) {
				$settings_priority = 18;
			}

			// get policy for entry
			if($type === 'spamfilter_user') {
				$policy = $app->db->queryOneRecord("SELECT * FROM spamfilter_policy WHERE id = ?", intval($data['new']['policy_id']));

				$check = $app->db->queryOneRecord('SELECT `greylisting` FROM `mail_user` WHERE `server_id` = ? AND `email` = ? UNION SELECT `greylisting` FROM `mail_forwarding` WHERE `server_id` = ? AND `source` = ? ORDER BY (`greylisting` = ?) DESC', $conf['server_id'], $email_address, $conf['server_id'], $email_address, 'y');
				if($check) {
					$greylisting = $check['greylisting'];
				} else {
					$greylisting = 'n';
				}
			} else {
				$search_for_policy[] = $email_address;
				$search_for_policy[] = substr($email_address, strpos($email_address, '@'));

				$policy = $app->db->queryOneRecord("SELECT p.* FROM spamfilter_users as u INNER JOIN spamfilter_policy as p ON (p.id = u.policy_id) WHERE u.server_id = ? AND u.email IN ? ORDER BY u.priority DESC", $conf['server_id'], $search_for_policy);

				$greylisting = $data[$use_data]['greylisting'];
			}

			if(!is_dir($this->users_config_dir)){
				$app->system->mkdirpath($this->users_config_dir);
			}

			if(!$this->isValidEmail($app->functions->idn_encode($email_address))) {
				if(is_file($settings_file)) {
					unlink($settings_file);
				}
			} else {

				$app->load('tpl');

				$tpl = new tpl();
				$tpl->newTemplate('rspamd_users.inc.conf.master');

				$tpl->setVar('record_identifier', 'ispc_' . $type . '_' . $entry_id);
				$tpl->setVar('priority', $settings_priority);

				if($type === 'spamfilter_user') {
					if($data[$use_data]['local'] === 'Y') {
						$tpl->setVar('to_email', $app->functions->idn_encode($email_address));
					} else {
						$tpl->setVar('from_email', $app->functions->idn_encode($email_address));
					}
					$spamfilter = $data[$use_data];
				} else {
					$tpl->setVar('to_email', $app->functions->idn_encode($email_address));

					// need to get matching spamfilter user if any
					$spamfilter = $app->db->queryOneRecord('SELECT * FROM spamfilter_users WHERE `email` = ?', $email_address);
				}

				if(!isset($policy['rspamd_spam_tag_level'])) {
					$policy['rspamd_spam_tag_level'] = 6.0;
				}
				if(!isset($policy['rspamd_spam_tag_method'])) {
					$policy['rspamd_spam_tag_method'] = 'add_header';
				}
				if(!isset($policy['rspamd_spam_kill_level'])) {
					$policy['rspamd_spam_kill_level'] = 15.0;
				}
				if(!isset($policy['rspamd_virus_kill_level'])) {
					$policy['rspamd_virus_kill_level'] = floatval($policy['rspamd_spam_kill_level']) + 1000;
				}

				$tpl->setVar('rspamd_spam_tag_level', floatval($policy['rspamd_spam_tag_level']));
				$tpl->setVar('rspamd_spam_tag_method', $policy['rspamd_spam_tag_method']);
				$tpl->setVar('rspamd_spam_kill_level', floatval($policy['rspamd_spam_kill_level']));
				$tpl->setVar('rspamd_virus_kill_level', floatval($policy['rspamd_spam_kill_level']) + 1000);

				if(isset($policy['spam_lover']) && $policy['spam_lover'] == 'Y') {
					$tpl->setVar('spam_lover', true);
				}
				if(isset($policy['virus_lover']) && $policy['virus_lover'] == 'Y') {
					$tpl->setVar('virus_lover', true);
				}

				$tpl->setVar('greylisting', $greylisting);

				if(isset($policy['rspamd_spam_greylisting_level'])) {
					$tpl->setVar('greylisting_level', floatval($policy['rspamd_spam_greylisting_level']));
				} else {
					$tpl->setVar('greylisting_level', 0.1);
				}

				$app->system->file_put_contents($settings_file, $tpl->grab());
			}
		}

		if($mail_config['content_filter'] == 'rspamd'){
			$app->services->restartServiceDelayed('rspamd', 'reload');
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

				if(!$this->isValidEmail($filter_from)) {
					$filter_from = '';
				}
				if(!$this->isValidEmail($filter_rcpt)) {
					$filter_rcpt = '';
				}
				if(($global_filter === true && !$filter_from && !$filter_rcpt) || ($global_filter === false && (!$filter_from || !$filter_rcpt))) {
					if(is_file($wblist_file)) {
						unlink($wblist_file);
					}
				} else {
					$tpl = new tpl();
					$tpl->newTemplate('rspamd_wblist.inc.conf.master');
					$tpl->setVar('list_scope', ($global_filter ? 'global' : 'spamfilter'));
					$tpl->setVar('record_id', $record_id);
					// we need to add 10 to priority to avoid mailbox/domain spamfilter settings overriding white/blacklists
					$tpl->setVar('priority', intval($data['new']['priority']) + ($global_filter ? 10 : 20));
					$tpl->setVar('from', $filter_from);
					$tpl->setVar('recipient', $filter_rcpt);
					$tpl->setVar('hostname', $filter['hostname']);
					$tpl->setVar('ip', $filter['ip']);
					$tpl->setVar('wblist', $filter['wb']);

					$app->system->file_put_contents($wblist_file, $tpl->grab());
				}
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
