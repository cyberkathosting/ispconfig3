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

class postfix_server_plugin {

	var $plugin_name = 'postfix_server_plugin';
	var $class_name = 'postfix_server_plugin';


	var $postfix_config_dir = '/etc/postfix';

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

		$app->plugins->registerEvent('server_insert', $this->plugin_name, 'insert');
		$app->plugins->registerEvent('server_update', $this->plugin_name, 'update');

		$app->plugins->registerEvent('server_ip_insert', $this->plugin_name, 'server_ip');
		$app->plugins->registerEvent('server_ip_update', $this->plugin_name, 'server_ip');
		$app->plugins->registerEvent('server_ip_delete', $this->plugin_name, 'server_ip');
	}

	function insert($event_name, $data) {

		$this->update($event_name, $data);

	}

	// The purpose of this plugin is to rewrite the main.cf file
	function update($event_name, $data) {
		global $app, $conf;

		// get the config
		$app->uses("getconf,system");
		$old_ini_data = $app->ini_parser->parse_ini_string($data['old']['config']);
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

		copy('/etc/postfix/main.cf', '/etc/postfix/main.cf~');
		
		if ($mail_config['relayhost'].$mail_config['relayhost_user'].$mail_config['relayhost_password'] != $old_ini_data['mail']['relayhost'].$old_ini_data['mail']['relayhost_user'].$old_ini_data['mail']['relayhost_password']) {
			$content = file_exists('/etc/postfix/sasl_passwd') ? file_get_contents('/etc/postfix/sasl_passwd') : '';
			$content = preg_replace('/^'.preg_quote($old_ini_data['email']['relayhost']).'\s+[^\n]*(:?\n|)/m','',$content);

			if (!empty($mail_config['relayhost']) || !empty($mail_config['relayhost_user']) || !empty($mail_config['relayhost_password'])) {
				$content .= "\n".$mail_config['relayhost'].'   '.$mail_config['relayhost_user'].':'.$mail_config['relayhost_password'];
			}
			
			if (preg_replace('/^(#[^\n]*|\s+)(:?\n+|)/m','',$content) != '') {
				exec("postconf -e 'smtp_sasl_auth_enable = yes'");
			} else {
				exec("postconf -e 'smtp_sasl_auth_enable = no'");
			}
			
			exec("postconf -e 'relayhost = ".$mail_config['relayhost']."'");
			file_put_contents('/etc/postfix/sasl_passwd', $content);
			chmod('/etc/postfix/sasl_passwd', 0600);
			chown('/etc/postfix/sasl_passwd', 'root');
			chgrp('/etc/postfix/sasl_passwd', 'root');
			exec("postconf -e 'smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd'");
			exec("postconf -e 'smtp_sasl_security_options ='");
			exec('postmap /etc/postfix/sasl_passwd');
			exec($conf['init_scripts'] . '/' . 'postfix restart');
		}

		if($mail_config['realtime_blackhole_list'] != $old_ini_data['mail']['realtime_blackhole_list']) {
			$rbl_updated = false;
			$rbl_hosts = trim(preg_replace('/\s+/', '', $mail_config['realtime_blackhole_list']));
			if($rbl_hosts != ''){
				$rbl_hosts = explode(",", $rbl_hosts);
			}
			$options = preg_split("/,\s*/", exec("postconf -h smtpd_recipient_restrictions"));
			$new_options = array();
			foreach ($options as $key => $value) {
				if (!preg_match('/reject_rbl_client/', $value)) {
					$new_options[] = $value;
				} else {
					if(is_array($rbl_hosts) && !empty($rbl_hosts) && !$rbl_updated){
						$rbl_updated = true;
						foreach ($rbl_hosts as $key => $value) {
							$value = trim($value);
							if($value != '') $new_options[] = "reject_rbl_client ".$value;
						}
					}
				}
			}
			//* first time add rbl-list
			if (!$rbl_updated && is_array($rbl_hosts) && !empty($rbl_hosts)) {
				foreach ($rbl_hosts as $key => $value) {
					$value = trim($value);
					if($value != '') $new_options[] = "reject_rbl_client ".$value;
				}
			}
			exec("postconf -e 'smtpd_recipient_restrictions = ".implode(", ", $new_options)."'");
			exec('postfix reload');
		}
		
		if($mail_config['reject_sender_login_mismatch'] != $old_ini_data['mail']['reject_sender_login_mismatch']) {
			$options = explode(", ", exec("postconf -h smtpd_sender_restrictions"));
			$new_options = array();
			foreach ($options as $key => $value) {
				if (!preg_match('/reject_authenticated_sender_login_mismatch/', $value)) {
					$new_options[] = $value;
				}
			}
				
			if ($mail_config['reject_sender_login_mismatch'] == 'y') {
				reset($new_options); $i = 0;
				// insert after check_sender_access but before permit_...
				while (isset($new_options[$i]) && substr($new_options[$i], 0, 19) == 'check_sender_access') ++$i;
				array_splice($new_options, $i, 0, array('reject_authenticated_sender_login_mismatch'));
			}
			exec("postconf -e 'smtpd_sender_restrictions = ".implode(", ", $new_options)."'");
			exec('postfix reload');
		}		
		
		if($app->system->is_installed('dovecot')) {
			$out = null;
			exec("postconf -n virtual_transport", $out);
			if ($mail_config["mailbox_virtual_uidgid_maps"] == 'y') {
				// If dovecot switch to lmtp
				if($out[0] != "virtual_transport = lmtp:unix:private/dovecot-lmtp") {
					exec("postconf -e 'virtual_transport = lmtp:unix:private/dovecot-lmtp'");
					exec('postfix reload');
					$app->system->replaceLine("/etc/dovecot/dovecot.conf", "protocols = imap pop3", "protocols = imap pop3 lmtp");
					exec($conf['init_scripts'] . '/' . 'dovecot restart');
				}
			} else {
				// If dovecot switch to dovecot
				if($out[0] != "virtual_transport = dovecot") {
					exec("postconf -e 'virtual_transport = dovecot'");
					exec('postfix reload');
					$app->system->replaceLine("/etc/dovecot/dovecot.conf", "protocols = imap pop3 lmtp", "protocols = imap pop3");
					exec($conf['init_scripts'] . '/' . 'dovecot restart');
				}
			}
		}

		if($mail_config['content_filter'] != $old_ini_data['mail']['content_filter']) {
			if($mail_config['content_filter'] == 'rspamd'){
				exec("postconf -X 'receive_override_options'");
				exec("postconf -X 'content_filter'");
				
				exec("postconf -e 'smtpd_milters = inet:localhost:11332'");
				exec("postconf -e 'milter_protocol = 6'");
				exec("postconf -e 'milter_mail_macros = i {mail_addr} {client_addr} {client_name} {auth_authen}'");
				exec("postconf -e 'milter_default_action = accept'");
				
				exec("postconf -e 'smtpd_sender_restrictions = check_sender_access mysql:/etc/postfix/mysql-virtual_sender.cf, permit_mynetworks, permit_sasl_authenticated'");
				
				$new_options = array();
				$options = preg_split("/,\s*/", exec("postconf -h smtpd_recipient_restrictions"));
				foreach ($options as $key => $value) {
					if (!preg_match('/check_policy_service\s+inet:127.0.0.1:10023/', $value)) {
						$new_options[] = $value;
					}
				}
				exec("postconf -e 'smtpd_recipient_restrictions = ".implode(", ", $new_options)."'");
				
				if(!is_dir('/etc/rspamd/local.d/')){
					$app->system->mkdirpath('/etc/rspamd/local.d/');
				}
				
				$this->server_ip($event_name, $data);
				
				if(file_exists($conf['rootpath'].'/conf-custom/rspamd_antivirus.conf.master')) {
					exec('cp '.$conf['rootpath'].'/conf-custom/rspamd_antivirus.conf.master /etc/rspamd/local.d/antivirus.conf');
				} else {
					exec('cp '.$conf['rootpath'].'/conf/rspamd_antivirus.conf.master /etc/rspamd/local.d/antivirus.conf');
				}

				if(file_exists($conf['rootpath'].'/conf-custom/rspamd_classifier-bayes.conf.master')) {
					exec('cp '.$conf['rootpath'].'/conf-custom/rspamd_classifier-bayes.conf.master /etc/rspamd/local.d/classifier-bayes.conf');
				} else {
					exec('cp '.$conf['rootpath'].'/conf/rspamd_classifier-bayes.conf.master /etc/rspamd/local.d/classifier-bayes.conf');
				}

				if(file_exists($conf['rootpath'].'/conf-custom/rspamd_greylist.conf.master')) {
					exec('cp '.$conf['rootpath'].'/conf-custom/rspamd_greylist.conf.master /etc/rspamd/local.d/greylist.conf');
				} else {
					exec('cp '.$conf['rootpath'].'/conf/rspamd_greylist.conf.master /etc/rspamd/local.d/greylist.conf');
				}
				
				if(file_exists($conf['rootpath'].'/conf-custom/rspamd_metrics.conf.master')) {
					exec('cp '.$conf['rootpath'].'/conf-custom/rspamd_metrics.conf.master /etc/rspamd/local.d/metrics.conf');
				} else {
					exec('cp '.$conf['rootpath'].'/conf/rspamd_metrics.conf.master /etc/rspamd/local.d/metrics.conf');
				}
				
				if(file_exists($conf['rootpath'].'/conf-custom/rspamd_metrics_override.conf.master')) {
					exec('cp '.$conf['rootpath'].'/conf-custom/rspamd_metrics_override.conf.master /etc/rspamd/override.d/metrics.conf');
				} else {
					exec('cp '.$conf['rootpath'].'/conf/rspamd_metrics_override.conf.master /etc/rspamd/override.d/metrics.conf');
				}
			
				if(file_exists($conf['rootpath'].'/conf-custom/rspamd_mx_check.conf.master')) {
					exec('cp '.$conf['rootpath'].'/conf-custom/rspamd_mx_check.conf.master /etc/rspamd/local.d/mx_check.conf');
				} else {
					exec('cp '.$conf['rootpath'].'/conf/rspamd_mx_check.conf.master /etc/rspamd/local.d/mx_check.conf');
				}
				
				if(file_exists($conf['rootpath'].'/conf-custom/rspamd.local.lua.master')) {
					exec('cp '.$conf['rootpath'].'/conf-custom/rspamd.local.lua.master /etc/rspamd/rspamd.local.lua');
				} else {
					exec('cp '.$conf['rootpath'].'/conf/rspamd.local.lua.master /etc/rspamd/rspamd.local.lua');
				}
				
				$tpl = new tpl();
				$tpl->newTemplate('rspamd_dkim_signing.conf.master');
				$tpl->setVar('dkim_path', $mail_config['dkim_path']);
				$app->system->file_put_contents('/etc/rspamd/local.d/dkim_signing.conf', $tpl->grab());

				$app->system->add_user_to_group('amavis', '_rspamd');
				
				if(strpos($app->system->file_get_contents('/etc/rspamd/rspamd.conf'), '.include "$LOCAL_CONFDIR/local.d/users.conf"') === false){
					$app->uses('file');
					$app->file->af('/etc/rspamd/rspamd.conf', '.include "$LOCAL_CONFDIR/local.d/users.conf"');
				}
				
				if(is_file('/etc/init.d/rspamd')) $app->services->restartServiceDelayed('rspamd', 'reload');
			} elseif($mail_config['content_filter'] == 'amavisd'){
				exec("postconf -X 'smtpd_milters'");
				exec("postconf -X 'milter_protocol'");
				exec("postconf -X 'milter_mail_macros'");
				exec("postconf -X 'milter_default_action'");
				
				exec("postconf -e 'receive_override_options = no_address_mappings'");
				exec("postconf -e 'content_filter = amavis:[127.0.0.1]:10024'");
				
				exec("postconf -e 'smtpd_sender_restrictions = check_sender_access mysql:/etc/postfix/mysql-virtual_sender.cf regexp:/etc/postfix/tag_as_originating.re, permit_mynetworks, permit_sasl_authenticated, check_sender_access regexp:/etc/postfix/tag_as_foreign.re'");
			}
		}
		
		if($mail_config['content_filter'] == 'rspamd' && ($mail_config['rspamd_password'] != $old_ini_data['mail']['rspamd_password'] || $mail_config['content_filter'] != $old_ini_data['mail']['content_filter'])) {
			$tpl = new tpl();
			$tpl->newTemplate('rspamd_worker-controller.inc.master');
			$tpl->setVar('rspamd_password', $mail_config['rspamd_password']);
			$app->system->file_put_contents('/etc/rspamd/local.d/worker-controller.inc', $tpl->grab());
			if(is_file('/etc/init.d/rspamd')) $app->services->restartServiceDelayed('rspamd', 'reload');
		}

		exec("postconf -e 'mailbox_size_limit = ".intval($mail_config['mailbox_size_limit']*1024*1024)."'"); //TODO : no reload?
		exec("postconf -e 'message_size_limit = ".intval($mail_config['message_size_limit']*1024*1024)."'"); //TODO : no reload?
	
	}

	function server_ip($event_name, $data) {
		global $app, $conf;
 
		// get the config
		$app->uses("getconf,system");
		$app->load('tpl');

		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		if($mail_config['content_filter'] == 'rspamd'){
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
				
			if(is_file('/etc/init.d/rspamd')) $app->services->restartServiceDelayed('rspamd', 'reload');
		}
	}
} // end class
