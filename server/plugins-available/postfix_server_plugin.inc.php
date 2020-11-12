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
	}

	function insert($event_name, $data) {

		$this->update($event_name, $data);

	}

	// The purpose of this plugin is to rewrite the main.cf file
	function update($event_name, $data) {
		global $app, $conf;
		$postfix_restart = false;

		// get the config
		$app->uses("getconf,system");
		$old_ini_data = $app->ini_parser->parse_ini_string($data['old']['config']);
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');

                // Get postfix version
		exec('postconf -d mail_version 2>&1', $out);
		$postfix_version = preg_replace('/.*=\s*/', '', $out[0]);
		unset($out);

		copy('/etc/postfix/main.cf', '/etc/postfix/main.cf~');

		if ($mail_config['relayhost'].$mail_config['relayhost_user'].$mail_config['relayhost_password'] != $old_ini_data['mail']['relayhost'].$old_ini_data['mail']['relayhost_user'].$old_ini_data['mail']['relayhost_password']) {
			$content = file_exists('/etc/postfix/sasl_passwd') ? file_get_contents('/etc/postfix/sasl_passwd') : '';
			$content = preg_replace('/^'.preg_quote($old_ini_data['email']['relayhost'], '/').'\s+[^\n]*(:?\n|)/m','',$content);

			if (!empty($mail_config['relayhost_user']) || !empty($mail_config['relayhost_password'])) {
				$content .= "\n".$mail_config['relayhost'].'   '.$mail_config['relayhost_user'].':'.$mail_config['relayhost_password'];
			}

			if (preg_replace('/^(#[^\n]*|\s+)(:?\n+|)/m','',$content) != '') {
				exec("postconf -e 'smtp_sasl_auth_enable = yes'");
			} else {
				exec("postconf -e 'smtp_sasl_auth_enable = no'");
			}

			$app->system->exec_safe("postconf -e ?", 'relayhost = '.$mail_config['relayhost']);
			file_put_contents('/etc/postfix/sasl_passwd', $content);
			chmod('/etc/postfix/sasl_passwd', 0600);
			chown('/etc/postfix/sasl_passwd', 'root');
			chgrp('/etc/postfix/sasl_passwd', 'root');
			exec("postconf -e 'smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd'");
			exec("postconf -e 'smtp_sasl_security_options ='");
			exec('postmap /etc/postfix/sasl_passwd');
			$postfix_restart=true;
		}

		if($mail_config['realtime_blackhole_list'] != $old_ini_data['mail']['realtime_blackhole_list']) {
			# reject_rbl_client is now in smtpd_client_restrictions, remove here:
			$options = preg_split("/,\s*/", exec("postconf -h smtpd_recipient_restrictions"));
			$new_options = array();
			foreach ($options as $key => $value) {
				$value = trim($value);
				if ($value == '') continue;
				if (preg_match('/^reject_rbl_client/', $value)) continue;
				$new_options[] = $value;
			}
			$app->system->exec_safe("postconf -e ?", 'smtpd_recipient_restrictions = '.implode(", ", $new_options));

			$rbl_options = array();
			$rbl_hosts = trim(preg_replace('/\s+/', '', $mail_config['realtime_blackhole_list']));
			if($rbl_hosts != ''){
				$rbl_hosts = explode(",", $rbl_hosts);
				foreach ($rbl_hosts as $key => $value) {
					$value = trim($value);
					if($value != '') $rbl_options[] = "reject_rbl_client ".$value;
				}
			}

			$options = preg_split("/,\s*/", exec("postconf -h smtpd_client_restrictions"));
			$new_options = array();
			foreach ($options as $key => $value) {
				$value = trim($value);
				if ($value == '') continue;
				if (preg_match('/^reject_rbl_client/', $value)) continue;
				$new_options[] = $value;
				if (preg_match('/^permit_mynetworks/', $value)) {
					$new_options = array_merge($new_options, $rbl_options);
					$rbl_options = array(); // so we don't ever array_merge twice
				}
			}
			$app->system->exec_safe("postconf -e ?", 'smtpd_client_restrictions = '.implode(", ", $new_options));
		}

		if ($mail_config['reject_sender_login_mismatch'] != $old_ini_data['mail']['reject_sender_login_mismatch']) {
			$options = preg_split("/,\s*/", exec("postconf -h smtpd_sender_restrictions"));
			$new_options = array();
			foreach ($options as $key => $value) {
				$value = trim($value);
				if ($value == '') continue;
				if (preg_match('/reject_(authenticated_)?sender_login_mismatch/', $value)) {
					continue;
				}
				$new_options[] = $value;
			}

			if ($mail_config['reject_sender_login_mismatch'] == 'y') {
				array_splice($new_options, 0, 0, array('reject_authenticated_sender_login_mismatch'));

				for ($i = 0; isset($new_options[$i]); $i++) {
					if ($new_options[$i] == 'permit_mynetworks') {
						array_splice($new_options, $i+1, 0, array('reject_sender_login_mismatch'));
						break;
					}
				}
			}

			$app->system->exec_safe("postconf -e ?", 'smtpd_sender_restrictions = '.implode(", ", $new_options));
		}

		if ($mail_config['reject_unknown']) {
			if (($mail_config['reject_unknown'] === 'client') || ($mail_config['reject_unknown'] === 'client_helo')) {
				$options = preg_split("/,\s*/", exec("postconf -h smtpd_client_restrictions"));
				$new_options = array();
				foreach ($options as $key => $value) {
					$value = trim($value);
					if ($value == '') continue;
					if (preg_match('/reject_unknown(_client)?_hostname/', $value)) {
						continue;
					}
					$new_options[] = $value;
				}

				// insert before explicit permit, or append
				for ($i = 0; isset($new_options[$i]); $i++) {
					if ($new_options[$i] == 'permit') {
						array_splice($new_options, $i, 0, array('reject_unknown_client_hostname'));
						break;
					}
				}
				if ($i == count($new_options)) {
					$new_options[] = 'reject_unknown_client_hostname';
				}

				$app->system->exec_safe("postconf -e ?", 'smtpd_client_restrictions = '.implode(", ", $new_options));
			} else {
				$options = preg_split("/,\s*/", exec("postconf -h smtpd_client_restrictions"));
				$new_options = array();
				foreach ($options as $key => $value) {
					$value = trim($value);
					if ($value == '') continue;
					if (preg_match('/reject_unknown(_client)?_hostname/', $value)) {
						continue;
					}
					$new_options[] = $value;
				}
				$app->system->exec_safe("postconf -e ?", 'smtpd_client_restrictions = '.implode(", ", $new_options));
			}

			if (($mail_config['reject_unknown'] === 'helo') || ($mail_config['reject_unknown'] === 'client_helo')) {
				$options = preg_split("/,\s*/", exec("postconf -h smtpd_helo_restrictions"));
				$new_options = array();
				foreach ($options as $key => $value) {
					$value = trim($value);
					if ($value == '') continue;
					if (preg_match('/reject_unknown(_helo)?_hostname/', $value)) {
						continue;
					}
					$new_options[] = $value;
				}

				// insert before explicit permit, or append
				for ($i = 0; isset($new_options[$i]); $i++) {
					if ($new_options[$i] == 'permit') {
						array_splice($new_options, $i, 0, array('reject_unknown_helo_hostname'));
						break;
					}
				}
				if ($i == count($new_options)) {
					$new_options[] = 'reject_unknown_helo_hostname';
				}

				$app->system->exec_safe("postconf -e ?", 'smtpd_helo_restrictions = '.implode(", ", $new_options));
			} else {
				$options = preg_split("/,\s*/", exec("postconf -h smtpd_helo_restrictions"));
				$new_options = array();
				foreach ($options as $key => $value) {
					$value = trim($value);
					if ($value == '') continue;
					if (preg_match('/reject_unknown(_helo)?_hostname/', $value)) {
						continue;
					}
					$new_options[] = $value;
				}
				$app->system->exec_safe("postconf -e ?", 'smtpd_helo_restrictions = '.implode(", ", $new_options));
			}
		}

		if ($mail_config['stress_adaptive']) {
			if ($mail_config['stress_adaptive'] == 'y') {
				if (version_compare($postfix_version , '3.0', '>=')) {
					$app->system->exec_safe("postconf -e ?", 'in_flow_delay = ${stress?{3}:{1}}s');
					$app->system->exec_safe("postconf -e ?", 'smtp_connect_timeout = ${stress?{10}:{30}}s');
					$app->system->exec_safe("postconf -e ?", 'smtp_helo_timeout = ${stress?{10}:{60}}s');
					$app->system->exec_safe("postconf -e ?", 'smtp_mail_timeout = ${stress?{10}:{60}}s');
					$app->system->exec_safe("postconf -e ?", 'smtpd_error_sleep_time = ${stress?{1}:{2}}s');
					$app->system->exec_safe("postconf -e ?", 'smtpd_hard_error_limit = ${stress?{1}:{10}}');
					$app->system->exec_safe("postconf -e ?", 'smtpd_recipient_overshoot_limit = ${stress?{60}:{600}}');
					$app->system->exec_safe("postconf -e ?", 'smtpd_soft_error_limit = ${stress?{2}:{5}}');
					$app->system->exec_safe("postconf -e ?", 'smtpd_timeout = ${stress?{10}:{60}}s');
				} elseif (version_compare($postfix_version , '2.5', '>=')) {
					$app->system->exec_safe("postconf -e ?", 'in_flow_delay = ${stress?3}${stress:1}s');
					$app->system->exec_safe("postconf -e ?", 'smtp_connect_timeout = ${stress?10}${stress:30}s');
					$app->system->exec_safe("postconf -e ?", 'smtp_helo_timeout = ${stress?10}${stress:60}s');
					$app->system->exec_safe("postconf -e ?", 'smtp_mail_timeout = ${stress?10}${stress:60}s');
					$app->system->exec_safe("postconf -e ?", 'smtpd_error_sleep_time = ${stress?1}${stress:2}s');
					$app->system->exec_safe("postconf -e ?", 'smtpd_hard_error_limit = ${stress?1}${stress:10}');
					$app->system->exec_safe("postconf -e ?", 'smtpd_recipient_overshoot_limit = ${stress?60}${stress:600}');
					$app->system->exec_safe("postconf -e ?", 'smtpd_soft_error_limit = ${stress?2}${stress:5}');
					$app->system->exec_safe("postconf -e ?", 'smtpd_timeout = ${stress?10}${stress:60}s');
				}
			} else { // mail_config['stress_adaptive'] == 'n'
				exec("postconf -X 'in_flow_delay'");
				exec("postconf -X 'smtp_connect_timeout'");
				exec("postconf -X 'smtp_helo_timeout'");
				exec("postconf -X 'smtp_mail_timeout'");
				exec("postconf -X 'smtpd_error_sleep_time'");
				exec("postconf -X 'smtpd_hard_error_limit'");
				exec("postconf -X 'smtpd_recipient_overshoot_limit'");
				exec("postconf -X 'smtpd_soft_error_limit'");
				exec("postconf -X 'smtpd_timeout'");
			}
		}

		if($app->system->is_installed('dovecot')) {
			$virtual_transport = 'dovecot';
			$configure_lmtp = false;
			$dovecot_protocols = 'imap pop3';

			//* dovecot-lmtpd
			if( ($configure_lmtp = (is_file('/usr/lib/dovecot/lmtp') || is_file('/usr/libexec/dovecot/lmtp'))) ||
			    ($mail_config["mailbox_virtual_uidgid_maps"] == 'y') )
			{
				$virtual_transport = 'lmtp:unix:private/dovecot-lmtp';
				$dovecot_protocols .= ' lmtp';
			}

			//* dovecot-managesieved
			if(is_file('/usr/lib/dovecot/managesieve') || is_file('/usr/libexec/dovecot/managesieve')) {
				$dovecot_protocols .= ' sieve';
			}

			$out = null;
			exec("postconf -n virtual_transport", $out);
			if($out[0] != "virtual_transport = $virtual_transport") {
				exec("postconf -e 'virtual_transport = $virtual_transport'");
				exec('postfix reload');
			}

			$out = null;
			exec("grep '^protocols\s' /etc/dovecot/dovecot.conf", $out);
			if($out[0] != "protocols = $dovecot_protocols") {
				$app->system->replaceLine("/etc/dovecot/dovecot.conf", 'REGEX:/^protocols\s=/', "protocols = $dovecot_protocols");
				exec($conf['init_scripts'] . '/' . 'dovecot restart');
			}
		}

		$quoted_postfix_config_dir = preg_quote($conf['postfix']['config_dir'], '|');
		$new_options = array();
		$options = preg_split("/,\s*/", exec("postconf -h smtpd_recipient_restrictions"));
		foreach ($options as $key => $value) {
			$value = trim($value);
			if ($value == '') continue;
			if (preg_match("|check_recipient_access\s+proxy:mysql:${quoted_postfix_config_dir}/mysql-verify_recipients.cf|", $value)) {
				continue;
			}
			$new_options[] = $value;
		}
		if (defined($configure_lmtp) && $configure_lmtp && $mail_config['content_filter'] == 'amavisd') {
			for ($i = 0; isset($new_options[$i]); $i++) {
				if ($new_options[$i] == 'reject_unlisted_recipient') {
					array_splice($new_options, $i+1, 0, array("check_recipient_access proxy:mysql:${quoted_postfix_config_dir}/mysql-verify_recipients.cf"));
					break;
				}
			}
			# postfix < 3.3 needs this when using reject_unverified_recipient:
			if(version_compare($postfix_version, 3.3, '<')) {
				exec("postconf -e 'enable_original_recipient = yes'");
			}
		}
		exec("postconf -e 'smtpd_recipient_restrictions = ".implode(", ", $new_options)."'");

		if($mail_config['content_filter'] != $old_ini_data['mail']['content_filter']) {
			$rslm = ($mail_config['reject_sender_login_mismatch'] == 'y') ? "reject_sender_login_mismatch," : "";
			$raslm = ($mail_config['reject_sender_login_mismatch'] == 'y') ? "reject_authenticated_sender_login_mismatch," : "";

			if($mail_config['content_filter'] == 'rspamd'){
				exec("postconf -X 'receive_override_options'");
				exec("postconf -X 'content_filter'");

				exec("postconf -e 'smtpd_milters = inet:localhost:11332'");
				exec("postconf -e 'non_smtpd_milters = inet:localhost:11332'");
				exec("postconf -e 'milter_protocol = 6'");
				exec("postconf -e 'milter_mail_macros = i {mail_addr} {client_addr} {client_name} {auth_authen}'");
				exec("postconf -e 'milter_default_action = accept'");

				exec("postconf -e 'smtpd_sender_restrictions = ${raslm} permit_mynetworks, ${rslm} permit_sasl_authenticated, reject_non_fqdn_sender, check_sender_access proxy:mysql:/etc/postfix/mysql-virtual_sender.cf'");

				$new_options = array();
				$options = preg_split("/,\s*/", exec("postconf -h smtpd_recipient_restrictions"));
				foreach ($options as $key => $value) {
					$value = trim($value);
					if ($value == '') continue;
					if (preg_match('/check_policy_service\s+inet:127.0.0.1:10023/', $value)) {
						continue;
					}
					$new_options[] = $value;
				}
				exec("postconf -e 'smtpd_recipient_restrictions = ".implode(", ", $new_options)."'");

				// get all domains that have dkim enabled
				if ( substr($mail_config['dkim_path'], strlen($mail_config['dkim_path'])-1) == '/' ) {
					$mail_config['dkim_path'] = substr($mail_config['dkim_path'], 0, strlen($mail_config['dkim_path'])-1);
				}
				$dkim_domains = $app->db->queryAllRecords('SELECT `dkim_selector`, `domain` FROM `mail_domain` WHERE `dkim` = ? ORDER BY `domain` ASC', 'y');
				$fpp = fopen('/etc/rspamd/local.d/dkim_domains.map', 'w');
				$fps = fopen('/etc/rspamd/local.d/dkim_selectors.map', 'w');
				foreach($dkim_domains as $dkim_domain) {
					fwrite($fpp, $dkim_domain['domain'] . ' ' . $mail_config['dkim_path'] . '/' . $dkim_domain['domain'] . '.private' . "\n");
					fwrite($fps, $dkim_domain['domain'] . ' ' . $dkim_domain['dkim_selector'] . "\n");
				}
				fclose($fpp);
				fclose($fps);
				unset($dkim_domains);
			} else {
				exec("postconf -X 'smtpd_milters'");
				exec("postconf -X 'non_smtpd_milters'");
				exec("postconf -X 'milter_protocol'");
				exec("postconf -X 'milter_mail_macros'");
				exec("postconf -X 'milter_default_action'");

				exec("postconf -e 'receive_override_options = no_address_mappings'");
				exec("postconf -e 'content_filter = " . ($configure_lmtp ? "lmtp" : "amavis" ) . ":[127.0.0.1]:10024'");

				// fixme: should read this from conf templates
				exec("postconf -e 'smtpd_sender_restrictions = ${raslm} check_sender_access regexp:/etc/postfix/tag_as_originating.re, permit_mynetworks, ${rslm} permit_sasl_authenticated, reject_non_fqdn_sender, check_sender_access regexp:/etc/postfix/tag_as_foreign.re, check_sender_access proxy:mysql:/etc/postfix/mysql-virtual_sender.cf'");
			}
		}

		if($mail_config['content_filter'] == 'rspamd' && ($mail_config['rspamd_password'] != $old_ini_data['mail']['rspamd_password'] || $mail_config['content_filter'] != $old_ini_data['mail']['content_filter'])) {
			$app->load('tpl');

			$rspamd_password = $mail_config['rspamd_password'];
			$crypted_password = trim(exec('rspamadm pw -p ' . escapeshellarg($rspamd_password)));
			if($crypted_password) {
				$rspamd_password = $crypted_password;
			}

			$tpl = new tpl();
			$tpl->newTemplate('rspamd_worker-controller.inc.master');
			$tpl->setVar('rspamd_password', $rspamd_password);
			$app->system->file_put_contents('/etc/rspamd/local.d/worker-controller.inc', $tpl->grab());
			chmod('/etc/rspamd/local.d/worker-controller.inc', 0644);
			$app->services->restartServiceDelayed('rspamd', 'reload');
		}

		exec("postconf -e 'mailbox_size_limit = ".intval($mail_config['mailbox_size_limit']*1024*1024)."'");
		exec("postconf -e 'message_size_limit = ".intval($mail_config['message_size_limit']*1024*1024)."'");
		$app->services->restartServiceDelayed('postfix', ($postfix_restart ? 'restart' : 'reload'));
	}
} // end class
