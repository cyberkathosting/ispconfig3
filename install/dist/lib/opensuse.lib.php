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

class installer_dist extends installer_base {
	protected $mailman_group = 'mailman';
	
	public function __construct() {
		//** check apache modules */
		$mods = getapachemodules();
		if(in_array('authz_compat', $mods, true)) {
			swriteln($inst->lng('    WARNING! You are using mod_authz_compat.'));
			swriteln($inst->lng('    Please make sure that your apache config uses the new auth syntax:'));
			swriteln($inst->lng('    <Directory />'));
			swriteln($inst->lng('    Options None'));
			swriteln($inst->lng('    AllowOverride None'));
			swriteln($inst->lng('    Require all denied'));
			swriteln($inst->lng('    </Directory>'."\n"));
			
			swriteln($inst->lng('    If it uses the old syntax (deny from all) ISPConfig would fail to work.'));
		}
	}

	function configure_postfix($options = '')
	{
		global $conf,$autoinstall;
		$cf = $conf['postfix'];
		$config_dir = $cf['config_dir'];

		if(!is_dir($config_dir)){
			$this->error("The postfix configuration directory '$config_dir' does not exist.");
		}

		//* mysql-virtual_domains.cf
		$this->process_postfix_config('mysql-virtual_domains.cf');

		//* mysql-virtual_forwardings.cf
		$this->process_postfix_config('mysql-virtual_forwardings.cf');

		//* mysql-virtual_mailboxes.cf
		$this->process_postfix_config('mysql-virtual_mailboxes.cf');

		//* mysql-virtual_email2email.cf
		$this->process_postfix_config('mysql-virtual_email2email.cf');

		//* mysql-virtual_transports.cf
		$this->process_postfix_config('mysql-virtual_transports.cf');

		//* mysql-virtual_recipient.cf
		$this->process_postfix_config('mysql-virtual_recipient.cf');

		//* mysql-virtual_sender.cf
		$this->process_postfix_config('mysql-virtual_sender.cf');

		//* mysql-virtual_sender_login_maps.cf
		$this->process_postfix_config('mysql-virtual_sender_login_maps.cf');
		
		//* mysql-virtual_client.cf
		$this->process_postfix_config('mysql-virtual_client.cf');

		//* mysql-virtual_relaydomains.cf
		$this->process_postfix_config('mysql-virtual_relaydomains.cf');

		//* mysql-virtual_relayrecipientmaps.cf
		$this->process_postfix_config('mysql-virtual_relayrecipientmaps.cf');

		//* mysql-virtual_policy_greylist.cf
		$this->process_postfix_config('mysql-virtual_policy_greylist.cf');

		//* mysql-virtual_gids.cf.master
		$this->process_postfix_config('mysql-virtual_gids.cf');

		//* mysql-virtual_uids.cf
		$this->process_postfix_config('mysql-virtual_uids.cf');

		//* postfix-dkim
		$filename='tag_as_originating.re';
		$full_file_name=$config_dir.'/'.$filename;
		if(is_file($full_file_name)) copy($full_file_name, $full_file_name.'~');
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/postfix-'.$filename.'.master', 'tpl/postfix-'.$filename.'.master');
		wf($full_file_name, $content);

		$filename='tag_as_foreign.re';
		$full_file_name=$config_dir.'/'.$filename;
		if(is_file($full_file_name)) copy($full_file_name, $full_file_name.'~');
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/postfix-'.$filename.'.master', 'tpl/postfix-'.$filename.'.master');
		wf($full_file_name, $content);

		//* Changing mode and group of the new created config files.
		caselog('chmod o= '.$config_dir.'/mysql-virtual_*.cf* &> /dev/null',
			__FILE__, __LINE__, 'chmod on mysql-virtual_*.cf*', 'chmod on mysql-virtual_*.cf* failed');
		caselog('chgrp '.$cf['group'].' '.$config_dir.'/mysql-virtual_*.cf* &> /dev/null',
			__FILE__, __LINE__, 'chgrp on mysql-virtual_*.cf*', 'chgrp on mysql-virtual_*.cf* failed');

		if(!is_dir($cf['vmail_mailbox_base'])) mkdir($cf['vmail_mailbox_base']);

		//* Creating virtual mail user and group
		if(is_group($cf['vmail_groupname'])) {
			$command = 'groupmod -g '.$cf['vmail_groupid'].' '.$cf['vmail_groupname'];
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		} else {
			$command = 'groupadd -g '.$cf['vmail_groupid'].' '.$cf['vmail_groupname'];
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		if(is_user($cf['vmail_username'])) {
			$command = 'usermod -g '.$cf['vmail_groupname'].' -u '.$cf['vmail_userid'].' -d '.$cf['vmail_mailbox_base'].' -s /bin/bash '.$cf['vmail_username'];
			caselog("$command &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		} else {
			$command = 'useradd -g '.$cf['vmail_groupname'].' -u '.$cf['vmail_userid'].' '.$cf['vmail_username'].' -d '.$cf['vmail_mailbox_base'].' -m';
			caselog("$command &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		if($cf['vmail_mailbox_base'] != '' && strlen($cf['vmail_mailbox_base']) >= 10 && $this->is_update === false) exec('chown -R '.$cf['vmail_username'].':'.$cf['vmail_groupname'].' '.$cf['vmail_mailbox_base']);

		//* These postconf commands will be executed on installation and update
		$server_ini_rec = $this->db->queryOneRecord("SELECT config FROM server WHERE server_id = ?", $conf['server_id']);
		$server_ini_array = ini_to_array(stripslashes($server_ini_rec['config']));
		unset($server_ini_rec);

		//* If there are RBL's defined, format the list and add them to smtp_recipient_restrictions to prevent removeal after an update
		$rbl_list = '';
		if (@isset($server_ini_array['mail']['realtime_blackhole_list']) && $server_ini_array['mail']['realtime_blackhole_list'] != '') {
			$rbl_hosts = explode(",", str_replace(" ", "", $server_ini_array['mail']['realtime_blackhole_list']));
			foreach ($rbl_hosts as $key => $value) {
				$rbl_list .= ", reject_rbl_client ". $value;
			}
		}
		unset($rbl_hosts);

		//* If Postgrey is installed, configure it
		$greylisting = '';
		if($conf['postgrey']['installed'] == true) {
			$greylisting = ', check_recipient_access mysql:/etc/postfix/mysql-virtual_policy_greylist.cf';
		}
		
		$reject_sender_login_mismatch = '';
		if(isset($server_ini_array['mail']['reject_sender_login_mismatch']) && ($server_ini_array['mail']['reject_sender_login_mismatch'] == 'y')) {
			$reject_sender_login_mismatch = ', reject_authenticated_sender_login_mismatch';
		}
		unset($server_ini_array);
		
		$postconf_placeholders = array('{config_dir}' => $config_dir,
			'{vmail_mailbox_base}' => $cf['vmail_mailbox_base'],
			'{vmail_userid}' => $cf['vmail_userid'],
			'{vmail_groupid}' => $cf['vmail_groupid'],
			'{rbl_list}' => $rbl_list,
			'{greylisting}' => $greylisting,
			'{reject_slm}' => $reject_sender_login_mismatch,
		);
		
		$postconf_tpl = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/opensuse_postfix.conf.master', 'tpl/opensuse_postfix.conf.master');
		$postconf_tpl = strtr($postconf_tpl, $postconf_placeholders);
		$postconf_commands = array_filter(explode("\n", $postconf_tpl)); // read and remove empty lines

		//* These postconf commands will be executed on installation only
		if($this->is_update == false) {
			$postconf_commands = array_merge($postconf_commands, array(
					'myhostname = '.$conf['hostname'],
					'mydestination = '.$conf['hostname'].', localhost, localhost.localdomain',
					'mynetworks = 127.0.0.0/8 [::1]/128'
				));
		}

		//* Create the header and body check files
		touch($config_dir.'/header_checks');
		touch($config_dir.'/mime_header_checks');
		touch($config_dir.'/nested_header_checks');
		touch($config_dir.'/body_checks');

		//* Create the mailman files
		if(!is_dir('/var/lib/mailman/data')) exec('mkdir -p /var/lib/mailman/data');
		if(!is_file('/var/lib/mailman/data/aliases')) touch('/var/lib/mailman/data/aliases');
		exec('postalias /var/lib/mailman/data/aliases');
		if(!is_file('/var/lib/mailman/data/virtual-mailman')) touch('/var/lib/mailman/data/virtual-mailman');
		exec('postmap /var/lib/mailman/data/virtual-mailman');
		if(!is_file('/var/lib/mailman/data/transport-mailman')) touch('/var/lib/mailman/data/transport-mailman');
		exec('/usr/sbin/postmap /var/lib/mailman/data/transport-mailman');

		//* Create auxillary postfix conf files
		$configfile = 'helo_access';
		if(is_file($config_dir.'/'.$configfile)) {
			copy($config_dir.'/'.$configfile, $config_dir.'/'.$configfile.'~');
			chmod($config_dir.'/'.$configfile.'~', 0400);
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = strtr($content, $postconf_placeholders);
		# todo: look up this server's ip addrs and loop through each
		# todo: look up domains hosted on this server and loop through each
		wf($config_dir.'/'.$configfile, $content);

		$configfile = 'blacklist_helo';
		if(is_file($config_dir.'/'.$configfile)) {
			copy($config_dir.'/'.$configfile, $config_dir.'/'.$configfile.'~');
			chmod($config_dir.'/'.$configfile.'~', 0400);
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = strtr($content, $postconf_placeholders);
		wf($config_dir.'/'.$configfile, $content);

		//* Make a backup copy of the main.cf file
		copy($config_dir.'/main.cf', $config_dir.'/main.cf~');

		//* Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__, 'EXECUTED: '.$command, 'Failed to execute the command '.$command);
		}

		if(!stristr($options, 'dont-create-certs')) {
			//* Create the SSL certificate
			if(AUTOINSTALL){
				$command = 'cd '.$config_dir.'; '
					."openssl req -new -subj '/C=".escapeshellcmd($autoinstall['ssl_cert_country'])."/ST=".escapeshellcmd($autoinstall['ssl_cert_state'])."/L=".escapeshellcmd($autoinstall['ssl_cert_locality'])."/O=".escapeshellcmd($autoinstall['ssl_cert_organisation'])."/OU=".escapeshellcmd($autoinstall['ssl_cert_organisation_unit'])."/CN=".escapeshellcmd($autoinstall['ssl_cert_common_name'])."' -outform PEM -out smtpd.cert -newkey rsa:4096 -nodes -keyout smtpd.key -keyform PEM -days 3650 -x509";
			} else {
				$command = 'cd '.$config_dir.'; '
					.'openssl req -new -outform PEM -out smtpd.cert -newkey rsa:4096 -nodes -keyout smtpd.key -keyform PEM -days 3650 -x509';
			}
			exec($command);

			$command = 'chmod o= '.$config_dir.'/smtpd.key';
			caselog($command.' &> /dev/null', __FILE__, __LINE__, 'EXECUTED: '.$command, 'Failed to execute the command '.$command);
		}

		//** We have to change the permissions of the courier authdaemon directory to make it accessible for maildrop.
		$command = 'chmod 755  /var/run/authdaemon.courier-imap';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, 'EXECUTED: '.$command, 'Failed to execute the command '.$command);

		//* Check maildrop service in posfix master.cf
		$regex = "/^maildrop   unix.*pipe flags=DRhu user=vmail argv=\\/usr\\/bin\\/maildrop -d ".$cf['vmail_username']." \\$\{extension} \\$\{recipient} \\$\{user} \\$\{nexthop} \\$\{sender}/";
		$configfile = $config_dir.'/master.cf';
		if($this->get_postfix_service('maildrop', 'unix')) {
			exec ("postconf -M maildrop.unix &> /dev/null", $out, $ret);
			$change_maildrop_flags = @(preg_match($regex, $out[0]) && $out[0] !='')?false:true;
		} else {
			$change_maildrop_flags = @(preg_match($regex, $configfile))?false:true;
		}
		if ($change_maildrop_flags) {
			//* Change maildrop service in posfix master.cf
			if(is_file($config_dir.'/master.cf')) {
				copy($config_dir.'/master.cf', $config_dir.'/master.cf~');
			}
			if(is_file($config_dir.'/master.cf~')) {
				chmod($config_dir.'/master.cf~', 0400);
			}
			$configfile = $config_dir.'/master.cf';
			$content = rf($configfile);
			$content =	str_replace('  flags=DRhu user=vmail argv=/usr/bin/maildrop -d ${recipient}',
						'flags=DRhu user='.$cf['vmail_username'].' argv=/usr/bin/maildrop -d '.$cf['vmail_username'].' ${extension} ${recipient} ${user} ${nexthop} ${sender}',
						$content);
			$content =	str_replace('  flags=DRhu user=vmail argv=/usr/local/bin/maildrop -d ${recipient}',
						'flags=DRhu user='.$cf['vmail_username'].' argv=/usr/bin/maildrop -d '.$cf['vmail_username'].' ${extension} ${recipient} ${user} ${nexthop} ${sender}',
						$content);
		}
		// enable tlsmanager
		$content = str_replace('#tlsmgr    unix  -       -       n       1000?   1       tlsmgr', 'tlsmgr    unix  -       -       n       1000?   1       tlsmgr', $content);
		wf($configfile, $content);

		//* Writing the Maildrop mailfilter file
		$configfile = 'mailfilter';
		if(is_file($cf['vmail_mailbox_base'].'/.'.$configfile)){
			copy($cf['vmail_mailbox_base'].'/.'.$configfile, $cf['vmail_mailbox_base'].'/.'.$configfile.'~');
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/$configfile.master");
		$content = str_replace('{dist_postfix_vmail_mailbox_base}', $cf['vmail_mailbox_base'], $content);
		wf($cf['vmail_mailbox_base'].'/.'.$configfile, $content);

		//* Create the directory for the custom mailfilters
		$command = 'mkdir '.$cf['vmail_mailbox_base'].'/mailfilters';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* Chmod and chown the .mailfilter file
		$command = 'chown -R '.$cf['vmail_username'].':'.$cf['vmail_groupname'].' '.$cf['vmail_mailbox_base'].'/.mailfilter';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = 'chmod -R 600 '.$cf['vmail_mailbox_base'].'/.mailfilter';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

	}

	public function configure_saslauthd() {
		global $conf;

		// Edit the file /etc/init.d/saslauthd
		$configfile = $conf["init_scripts"].'/'.$conf["saslauthd"]["init_script"];
		$content = rf($configfile);
		$content = str_replace('/sbin/startproc $AUTHD_BIN -a $SASLAUTHD_AUTHMECH -n $SASLAUTHD_THREADS > /dev/null 2>&1', '/sbin/startproc $AUTHD_BIN -r -a $SASLAUTHD_AUTHMECH -n $SASLAUTHD_THREADS > /dev/null 2>&1', $content);
		$content = str_replace('/sbin/startproc $AUTHD_BIN $SASLAUTHD_PARAMS -a $SASLAUTHD_AUTHMECH -n $SASLAUTHD_THREADS > /dev/null 2>&1', '/sbin/startproc $AUTHD_BIN $SASLAUTHD_PARAMS -r -a $SASLAUTHD_AUTHMECH -n $SASLAUTHD_THREADS > /dev/null 2>&1', $content);


		if(is_file($configfile)) wf($configfile, $content);

	}

	public function configure_pam()
	{
		global $conf;
		$pam = $conf['pam'];
		//* configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'pamd_smtp';
		if(is_file("$pam/smtp"))    copy("$pam/smtp", "$pam/smtp~");
		if(is_file("$pam/smtp~"))   exec("chmod 400 $pam/smtp~");

		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		wf("$pam/smtp", $content);
		// On some OSes smtp is world readable which allows for reading database information.  Removing world readable rights should have no effect.
		if(is_file("$pam/smtp"))    exec("chmod o= $pam/smtp");
	}

	public function configure_courier()
	{
		global $conf;
		$config_dir = $conf['courier']['config_dir'];
		//* authmysqlrc
		$configfile = 'authmysqlrc';
		if(is_file("$config_dir/$configfile")){
			copy("$config_dir/$configfile", "$config_dir/$configfile~");
		}
		exec("chmod 400 $config_dir/$configfile~");
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		$content = str_replace('{mysql_server_port}', $conf['mysql']['port'], $content);
		wf("$config_dir/$configfile", $content);

		exec("chmod 660 $config_dir/$configfile");
		exec("chown root:root $config_dir/$configfile");

		//* authdaemonrc
		$configfile = $conf['courier']['config_dir'].'/authdaemonrc';
		if(is_file($configfile)){
			copy($configfile, $configfile.'~');
		}
		if(is_file($configfile.'~')){
			exec('chmod 400 '.$configfile.'~');
		}
		$content = rf($configfile);
		$content = str_replace('authmodulelist=', 'authmodulelist="authmysql"', $content);
		wf($configfile, $content);
	}

	public function configure_dovecot()
	{
		global $conf;

		$virtual_transport = 'dovecot';

		$configure_lmtp = false;
		
		// check if virtual_transport must be changed
		if ($this->is_update) {
			$tmp = $this->db->queryOneRecord("SELECT * FROM ?? WHERE server_id = ?", $conf["mysql"]["database"] . ".server", $conf['server_id']);
			$ini_array = ini_to_array(stripslashes($tmp['config']));
			// ini_array needs not to be checked, because already done in update.php -> updateDbAndIni()
			
			if(isset($ini_array['mail']['mailbox_virtual_uidgid_maps']) && $ini_array['mail']['mailbox_virtual_uidgid_maps'] == 'y') {
				$virtual_transport = 'lmtp:unix:private/dovecot-lmtp';
				$configure_lmtp = true;
			}
		}

		$config_dir = $conf['postfix']['config_dir'];

		//* Configure master.cf and add a line for deliver
		if(!$this->get_postfix_service('dovecot', 'unix')) {
			//* backup
			if(is_file($config_dir.'/master.cf')){
				copy($config_dir.'/master.cf', $config_dir.'/master.cf~2');
			}
			if(is_file($config_dir.'/master.cf~')){
				chmod($config_dir.'/master.cf~2', 0400);
			}
			//* Configure master.cf and add a line for deliver
			$content = rf($conf["postfix"]["config_dir"].'/master.cf');
			$deliver_content = 'dovecot   unix  -       n       n       -       -       pipe'."\n".'  flags=DRhu user=vmail:vmail argv=/usr/lib/dovecot/deliver -f ${sender} -d ${user}@${nexthop}'."\n";
			af($conf["postfix"]["config_dir"].'/master.cf', $deliver_content);
			unset($content);
			unset($deliver_content);
		}

		//* Reconfigure postfix to use dovecot authentication
		// Adding the amavisd commands to the postfix configuration
		$postconf_commands = array (
			'dovecot_destination_recipient_limit = 1',
			'virtual_transport = '.$virtual_transport,
			'smtpd_sasl_type = dovecot',
			'smtpd_sasl_path = private/auth',
		);

		// Make a backup copy of the main.cf file
		copy($conf["postfix"]["config_dir"].'/main.cf', $conf["postfix"]["config_dir"].'/main.cf~3');

		// Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		$config_dir = $conf['dovecot']['config_dir'];

		//* backup dovecot.conf
		$configfile = 'dovecot.conf';
		if(is_file("$config_dir/$configfile")){
			copy("$config_dir/$configfile", "$config_dir/$configfile~");
		}

		//* Get the dovecot version
		exec('dovecot --version', $tmp);
		$parts = explode('.', trim($tmp[0]));
		$dovecot_version = $parts[0];
		unset($tmp);
		unset($parts);

		//* Copy dovecot configuration file
		if($dovecot_version == 2) {
			if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/opensuse_dovecot2.conf.master')) {
				copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/opensuse_dovecot2.conf.master', $config_dir.'/'.$configfile);
			} else {
				copy('tpl/opensuse_dovecot2.conf.master', $config_dir.'/'.$configfile);
			}
			replaceLine($config_dir.'/'.$configfile, 'postmaster_address = postmaster@example.com', 'postmaster_address = postmaster@'.$conf['hostname'], 1, 0);
			replaceLine($config_dir.'/'.$configfile, 'postmaster_address = webmaster@localhost', 'postmaster_address = postmaster@'.$conf['hostname'], 1, 0);
		} else {
			if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/opensuse_dovecot.conf.master')) {
				copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/opensuse_dovecot.conf.master', $config_dir.'/'.$configfile);
			} else {
				copy('tpl/opensuse_dovecot.conf.master', $config_dir.'/'.$configfile);
			}
		}

		//* dovecot-lmtpd
		if($configure_lmtp) {
			replaceLine($config_dir.'/'.$configfile, 'protocols = imap pop3', 'protocols = imap pop3 lmtp', 1, 0);
		}

		//* dovecot-sql.conf
		$configfile = 'dovecot-sql.conf';
		if(is_file("$config_dir/$configfile")){
			copy("$config_dir/$configfile", "$config_dir/$configfile~");
			exec("chmod 400 $config_dir/$configfile~");
		}

		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/opensuse_dovecot-sql.conf.master', "tpl/opensuse_dovecot-sql.conf.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		$content = str_replace('{mysql_server_port}', $conf['mysql']['port'], $content);
		$content = str_replace('{server_id}', $conf['server_id'], $content);
		# enable iterate_query for dovecot2
		if(version_compare($dovecot_version,2, '>=')) {
			$content = str_replace('# iterate_query', 'iterate_query', $content);
		}
		wf("$config_dir/$configfile", $content);

		exec("chmod 600 $config_dir/$configfile");
		exec("chown root:root $config_dir/$configfile");
		
		// Dovecot shall ignore mounts in website directory
		if(is_installed('doveadm')) exec("doveadm mount add '/srv/www/*' ignore > /dev/null 2> /dev/null");

	}

	public function configure_amavis() {
		global $conf;

		// amavisd user config file
		$configfile = 'opensuse_amavisd_conf';
		if(is_file($conf["amavis"]["config_dir"].'/amavisd.conf')) @copy($conf["amavis"]["config_dir"].'/amavisd.conf', $conf["amavis"]["config_dir"].'/amavisd.conf~');
		if(is_file($conf["amavis"]["config_dir"].'/amavisd.conf~')) exec('chmod 400 '.$conf["amavis"]["config_dir"].'/amavisd.conf~');
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_port}', $conf["mysql"]["port"], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		$content = str_replace('{hostname}', $conf['hostname'], $content);
		$content = str_replace('{amavis_config_dir}', $conf['amavis']['config_dir'], $content);
		wf($conf["amavis"]["config_dir"].'/amavisd.conf', $content);
		chmod($conf['amavis']['config_dir'].'/amavisd.conf', 0640);


		// Adding the amavisd commands to the postfix configuration
		$postconf_commands = array (
			'content_filter = amavis:[127.0.0.1]:10024',
			'receive_override_options = no_address_mappings'
		);

		// Make a backup copy of the main.cf file
		copy($conf["postfix"]["config_dir"].'/main.cf', $conf["postfix"]["config_dir"].'/main.cf~2');

		// Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		$config_dir = $conf['postfix']['config_dir'];

		// Adding amavis-services to the master.cf file if the service does not already exists
		$add_amavis = !$this->get_postfix_service('amavis','unix');
		$add_amavis_10025 = !$this->get_postfix_service('127.0.0.1:10025','inet');
		$add_amavis_10027 = !$this->get_postfix_service('127.0.0.1:10027','inet');

		if ($add_amavis || $add_amavis_10025 || $add_amavis_10027) {
			//* backup master.cf
			if(is_file($config_dir.'/master.cf')) copy($config_dir.'/master.cf', $config_dir.'/master.cf~');
			// adjust amavis-config
			if($add_amavis) {
				$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/master_cf_amavis.master', 'tpl/master_cf_amavis.master');
				af($config_dir.'/master.cf', $content);
				unset($content);
			}
			if ($add_amavis_10025) {
				$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/master_cf_amavis10025.master', 'tpl/master_cf_amavis10025.master');
				af($config_dir.'/master.cf', $content);
				unset($content);
			}
			if ($add_amavis_10027) {
				$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/master_cf_amavis10027.master', 'tpl/master_cf_amavis10027.master');
				af($config_dir.'/master.cf', $content);
				unset($content);
			}
		}

		// Add the clamav user to the vscan group
		//exec('groupmod --add-user clamav vscan');
		$command = 'usermod -a -G clamav vscan';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");


	}

	public function configure_spamassassin()
	{
		global $conf;

		//* Enable spamasasssin on debian and ubuntu
		/*
		$configfile = '/etc/default/spamassassin';
		if(is_file($configfile)){
            copy($configfile, $configfile.'~');
        }
		$content = rf($configfile);
		$content = str_replace('ENABLED=0', 'ENABLED=1', $content);
		wf($configfile, $content);
		*/
	}

	public function configure_getmail()
	{
		global $conf;

		$config_dir = $conf['getmail']['config_dir'];

		if(!is_dir($config_dir)) exec("mkdir -p ".escapeshellcmd($config_dir));

		$command = "useradd -d $config_dir getmail";
		if(!is_user('getmail')) caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = "chown -R getmail $config_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = "chmod -R 700 $config_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
	}


	public function configure_pureftpd()
	{
		global $conf;

		$config_dir = $conf['pureftpd']['config_dir'];

		//* configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'db/mysql.conf';
		if(is_file("$config_dir/$configfile")){
			copy("$config_dir/$configfile", "$config_dir/$configfile~");
		}
		if(is_file("$config_dir/$configfile~")){
			exec("chmod 400 $config_dir/$configfile~");
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/pureftpd_mysql.conf.master', 'tpl/pureftpd_mysql.conf.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf["mysql"]["ispconfig_user"], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf["mysql"]["ispconfig_password"], $content);
		$content = str_replace('{mysql_server_database}', $conf["mysql"]["database"], $content);
		$content = str_replace('{mysql_server_ip}', $conf["mysql"]["ip"], $content);
		$content = str_replace('{server_id}', $conf["server_id"], $content);
		wf("$config_dir/$configfile", $content);
		exec("chmod 600 $config_dir/$configfile");
		exec("chown root:root $config_dir/$configfile");

		// copy our customized copy of pureftpd.conf to the pure-ftpd config directory
		if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/opensuse_pureftpd_conf.master')) {
			exec("cp " . $conf['ispconfig_install_dir']."/server/conf-custom/install/opensuse_pureftpd_conf.master $config_dir/pure-ftpd.conf");
		} else {
			exec("cp tpl/opensuse_pureftpd_conf.master $config_dir/pure-ftpd.conf");
		}

	}

	public function configure_mydns()
	{
		global $conf;

		// configure mydns
		$configfile = 'mydns.conf';
		if(is_file($conf["mydns"]["config_dir"].'/'.$configfile)) copy($conf["mydns"]["config_dir"].'/'.$configfile, $conf["mydns"]["config_dir"].'/'.$configfile.'~');
		if(is_file($conf["mydns"]["config_dir"].'/'.$configfile.'~')) exec('chmod 400 '.$conf["mydns"]["config_dir"].'/'.$configfile.'~');
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf["mysql"]["host"], $content);
		$content = str_replace('{mysql_server_port}', $conf['mysql']['port'], $content);
		$content = str_replace('{server_id}', $conf["server_id"], $content);
		wf($conf["mydns"]["config_dir"].'/'.$configfile, $content);
		exec('chmod 600 '.$conf["mydns"]["config_dir"].'/'.$configfile);
		exec('chown root:root '.$conf["mydns"]["config_dir"].'/'.$configfile);

	}

	public function configure_apache()
	{
		global $conf;

		if($conf['apache']['installed'] == false) return;
		//* Create the logging directory for the vhost logfiles
		exec('mkdir -p /var/log/ispconfig/httpd');
		
		//* enable apache logio module
		exec('a2enmod logio');

		//if(is_file('/etc/suphp.conf')) {
		replaceLine('/etc/suphp.conf', 'php=php', 'x-httpd-suphp="php:/srv/www/cgi-bin/php5"', 0, 0);
		replaceLine('/etc/suphp.conf', 'php="php', 'x-httpd-suphp="php:/srv/www/cgi-bin/php5"', 0, 0);
		replaceLine('/etc/suphp.conf', 'docroot=', 'docroot=/srv/www', 0, 0);
		replaceLine('/etc/suphp.conf', 'umask=0077', 'umask=0022', 0);
		//}

		if(!file_exists('/srv/www/cgi-bin/php5') && file_exists('/srv/www/cgi-bin/php')) {
			symlink('/srv/www/cgi-bin/php', '/srv/www/cgi-bin/php5');
		}

		// Sites enabled and available dirs
		exec('mkdir -p '.$conf['apache']['vhost_conf_enabled_dir']);
		exec('mkdir -p '.$conf['apache']['vhost_conf_dir']);

		$content = rf('/etc/apache2/httpd.conf');
		if(!stristr($content, 'Include /etc/apache2/sites-enabled/')) {
			af('/etc/apache2/httpd.conf', "\n<Directory /srv/www>\n    Options +FollowSymlinks\n</Directory>\n\nInclude /etc/apache2/sites-enabled/\n\n");
		}
		unset($content);

		//* Copy the ISPConfig configuration include
		$vhost_conf_dir = $conf['apache']['vhost_conf_dir'];
		$vhost_conf_enabled_dir = $conf['apache']['vhost_conf_enabled_dir'];

		$tpl = new tpl('apache_ispconfig.conf.master');
		$tpl->setVar('apache_version',getapacheversion());
		
		if($this->is_update == true) {
			$tpl->setVar('logging',get_logging_state());
		} else {
			$tpl->setVar('logging','yes');
		}
		
		$records = $this->db->queryAllRecords("SELECT * FROM ?? WHERE server_id = ? AND virtualhost = 'y'", $conf['mysql']['master_database'] . '.server_ip', $conf['server_id']);
		$ip_addresses = array();
		
		if(is_array($records) && count($records) > 0) {
			foreach($records as $rec) {
				if($rec['ip_type'] == 'IPv6') {
					$ip_address = '['.$rec['ip_address'].']';
				} else {
					$ip_address = $rec['ip_address'];
				}
				$ports = explode(',', $rec['virtualhost_port']);
				if(is_array($ports)) {
					foreach($ports as $port) {
						$port = intval($port);
						if($port > 0 && $port < 65536 && $ip_address != '') {
							$ip_addresses[] = array('ip_address' => $ip_address, 'port' => $port);
						}
					}
				}
			}
		}
		
		if(count($ip_addresses) > 0) $tpl->setLoop('ip_adresses',$ip_addresses);

		wf($vhost_conf_dir.'/ispconfig.conf', $tpl->grab());
		unset($tpl);

		if(!@is_link($vhost_conf_enabled_dir."/000-ispconfig.conf")) {
			exec("ln -s ".$vhost_conf_dir."/ispconfig.conf ".$vhost_conf_enabled_dir."/000-ispconfig.conf");
		}

		//* make sure that webalizer finds its config file when it is directly in /etc
		if(@is_file('/etc/webalizer.conf') && !@is_dir('/etc/webalizer')) {
			exec('mkdir /etc/webalizer');
			exec('ln -s /etc/webalizer.conf /etc/webalizer/webalizer.conf');
		}

		if(is_file('/etc/webalizer/webalizer.conf')) {
			// Change webalizer mode to incremental
			replaceLine('/etc/webalizer/webalizer.conf', '#IncrementalName', 'IncrementalName webalizer.current', 0, 0);
			replaceLine('/etc/webalizer/webalizer.conf', '#Incremental', 'Incremental     yes', 0, 0);
			replaceLine('/etc/webalizer/webalizer.conf', '#HistoryName', 'HistoryName     webalizer.hist', 0, 0);
		}

		//* add a sshusers group
		$command = 'groupadd sshusers';
		if(!is_group('sshusers')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		// create PHP-FPM pool dir
		exec('mkdir -p '.$conf['nginx']['php_fpm_pool_dir']);

		$content = rf('/etc/php5/fpm/php-fpm.conf');
		if(stripos($content, 'include=/etc/php5/fpm/pool.d/*.conf') === false){
			af('/etc/php5/fpm/php-fpm.conf', "\ninclude=/etc/php5/fpm/pool.d/*.conf");
		}
		unset($content);
		if(!@is_file($conf['nginx']['php_fpm_ini_path'])){
			if(@is_file('/etc/php5/cli/php.ini')){
				exec('cp -f /etc/php5/cli/php.ini '.$conf['nginx']['php_fpm_ini_path']);
			} elseif(@is_file('/etc/php5/fastcgi/php.ini')){
				exec('cp -f /etc/php5/fastcgi/php.ini '.$conf['nginx']['php_fpm_ini_path']);
			} elseif(@is_file('/etc/php5/apache2/php.ini')){
				exec('cp -f /etc/php5/apache2/php.ini '.$conf['nginx']['php_fpm_ini_path']);
			}
		}

	}

	public function configure_nginx(){
		global $conf;

		if($conf['nginx']['installed'] == false) return;
		//* Create the logging directory for the vhost logfiles
		if(!@is_dir($conf['ispconfig_log_dir'].'/httpd')) mkdir($conf['ispconfig_log_dir'].'/httpd', 0755, true);

		// Sites enabled and available dirs
		exec('mkdir -p '.$conf['nginx']['vhost_conf_enabled_dir']);
		exec('mkdir -p '.$conf['nginx']['vhost_conf_dir']);

		$content = rf('/etc/nginx/nginx.conf');
		if(stripos($content, 'include /etc/nginx/sites-enabled/*.vhost;') === false){
			$content = trim($content);
			$content = substr($content, 0, -1)."\n    include /etc/nginx/sites-enabled/*.vhost;\n}";
			wf('/etc/nginx/nginx.conf', $content);
		}
		unset($content);

		// create PHP-FPM pool dir
		exec('mkdir -p '.$conf['nginx']['php_fpm_pool_dir']);

		$content = rf('/etc/php5/fpm/php-fpm.conf');
		if(stripos($content, 'include=/etc/php5/fpm/pool.d/*.conf') === false){
			af('/etc/php5/fpm/php-fpm.conf', "\ninclude=/etc/php5/fpm/pool.d/*.conf");
		}
		unset($content);
		if(!@is_file($conf['nginx']['php_fpm_ini_path'])){
			if(@is_file('/etc/php5/cli/php.ini')){
				exec('cp -f /etc/php5/cli/php.ini '.$conf['nginx']['php_fpm_ini_path']);
			} elseif(@is_file('/etc/php5/fastcgi/php.ini')){
				exec('cp -f /etc/php5/fastcgi/php.ini '.$conf['nginx']['php_fpm_ini_path']);
			} elseif(@is_file('/etc/php5/apache2/php.ini')){
				exec('cp -f /etc/php5/apache2/php.ini '.$conf['nginx']['php_fpm_ini_path']);
			}
		}

		//* make sure that webalizer finds its config file when it is directly in /etc
		if(@is_file('/etc/webalizer.conf') && !@is_dir('/etc/webalizer')) {
			mkdir('/etc/webalizer');
			symlink('/etc/webalizer.conf', '/etc/webalizer/webalizer.conf');
		}

		if(is_file('/etc/webalizer/webalizer.conf')) {
			// Change webalizer mode to incremental
			replaceLine('/etc/webalizer/webalizer.conf', '#IncrementalName', 'IncrementalName webalizer.current', 0, 0);
			replaceLine('/etc/webalizer/webalizer.conf', '#Incremental', 'Incremental     yes', 0, 0);
			replaceLine('/etc/webalizer/webalizer.conf', '#HistoryName', 'HistoryName     webalizer.hist', 0, 0);
		}

		// Check the awsatst script
		if(!is_dir('/usr/share/awstats/tools')) exec('mkdir -p /usr/share/awstats/tools');
		if(!file_exists('/usr/share/awstats/tools/awstats_buildstaticpages.pl') && file_exists('/usr/share/doc/awstats/examples/awstats_buildstaticpages.pl')) symlink('/usr/share/doc/awstats/examples/awstats_buildstaticpages.pl', '/usr/share/awstats/tools/awstats_buildstaticpages.pl');
		if(file_exists('/etc/awstats/awstats.conf.local')) replaceLine('/etc/awstats/awstats.conf.local', 'LogFormat=4', 'LogFormat=1', 0, 1);

		//* add a sshusers group
		$command = 'groupadd sshusers';
		if(!is_group('sshusers')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
	
		// add anonymized log option to nginxx.conf file
		$nginx_conf_file = $conf['nginx']['config_dir'].'/nginx.conf';
		if(is_file($nginx_conf_file)) {
			$tmp = file_get_contents($nginx_conf_file);
			if(!stristr($tmp, 'log_format anonymized')) {
				copy($nginx_conf_file,$nginx_conf_file.'~');
				replaceLine($nginx_conf_file, 'http {', "http {\n\n".file_get_contents('tpl/nginx_anonlog.master'), 0, 0);
			}
		}
	}

	public function configure_bastille_firewall()
	{
		global $conf;

		$dist_init_scripts = $conf['init_scripts'];

		if(is_dir("/etc/Bastille.backup")) caselog("rm -rf /etc/Bastille.backup", __FILE__, __LINE__);
		if(is_dir("/etc/Bastille")) caselog("mv -f /etc/Bastille /etc/Bastille.backup", __FILE__, __LINE__);
		@mkdir("/etc/Bastille", octdec($directory_mode));
		if(is_dir("/etc/Bastille.backup/firewall.d")) caselog("cp -pfr /etc/Bastille.backup/firewall.d /etc/Bastille/", __FILE__, __LINE__);
		if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/bastille-firewall.cfg.master')) {
			caselog("cp -f " . $conf['ispconfig_install_dir']."/server/conf-custom/install/bastille-firewall.cfg.master /etc/Bastille/bastille-firewall.cfg", __FILE__, __LINE__);
		} else {
			caselog("cp -f tpl/bastille-firewall.cfg.master /etc/Bastille/bastille-firewall.cfg", __FILE__, __LINE__);
		}
		caselog("chmod 644 /etc/Bastille/bastille-firewall.cfg", __FILE__, __LINE__);
		$content = rf("/etc/Bastille/bastille-firewall.cfg");
		$content = str_replace("{DNS_SERVERS}", "", $content);

		$tcp_public_services = '';
		$udp_public_services = '';

		$row = $this->db->queryOneRecord('SELECT * FROM ?? WHERE server_id = ?', $conf["mysql"]["database"] . '.firewall', $conf['server_id']);

		if(trim($row["tcp_port"]) != '' || trim($row["udp_port"]) != ''){
			$tcp_public_services = trim(str_replace(',', ' ', $row["tcp_port"]));
			$udp_public_services = trim(str_replace(',', ' ', $row["udp_port"]));
		} else {
			$tcp_public_services = '21 22 25 53 80 110 443 3306 8080 10000';
			$udp_public_services = '53';
		}
		if(!stristr($tcp_public_services, $conf['apache']['vhost_port'])) {
			$tcp_public_services .= ' '.intval($conf['apache']['vhost_port']);
			if($row["tcp_port"] != '') $this->db->query("UPDATE firewall SET tcp_port = tcp_port + ? WHERE server_id = ?", ',' . intval($conf['apache']['vhost_port']), $conf['server_id']);
		}

		$content = str_replace("{TCP_PUBLIC_SERVICES}", $tcp_public_services, $content);
		$content = str_replace("{UDP_PUBLIC_SERVICES}", $udp_public_services, $content);

		wf("/etc/Bastille/bastille-firewall.cfg", $content);

		if(is_file($dist_init_scripts."/bastille-firewall")) caselog("mv -f $dist_init_scripts/bastille-firewall $dist_init_scripts/bastille-firewall.backup", __FILE__, __LINE__);
		caselog("cp -f apps/bastille-firewall $dist_init_scripts", __FILE__, __LINE__);
		caselog("chmod 700 $dist_init_scripts/bastille-firewall", __FILE__, __LINE__);

		if(is_file("/sbin/bastille-ipchains")) caselog("mv -f /sbin/bastille-ipchains /sbin/bastille-ipchains.backup", __FILE__, __LINE__);
		caselog("cp -f apps/bastille-ipchains /sbin", __FILE__, __LINE__);
		caselog("chmod 700 /sbin/bastille-ipchains", __FILE__, __LINE__);

		if(is_file("/sbin/bastille-netfilter")) caselog("mv -f /sbin/bastille-netfilter /sbin/bastille-netfilter.backup", __FILE__, __LINE__);
		caselog("cp -f apps/bastille-netfilter /sbin", __FILE__, __LINE__);
		caselog("chmod 700 /sbin/bastille-netfilter", __FILE__, __LINE__);

		if(!@is_dir('/var/lock/subsys')) caselog("mkdir /var/lock/subsys", __FILE__, __LINE__);

		exec("which ipchains &> /dev/null", $ipchains_location, $ret_val);
		if(!is_file("/sbin/ipchains") && !is_link("/sbin/ipchains") && $ret_val == 0) phpcaselog(@symlink(shell_exec("which ipchains"), "/sbin/ipchains"), 'create symlink', __FILE__, __LINE__);
		unset($ipchains_location);
		exec("which iptables &> /dev/null", $iptables_location, $ret_val);
		if(!is_file("/sbin/iptables") && !is_link("/sbin/iptables") && $ret_val == 0) phpcaselog(@symlink(trim(shell_exec("which iptables")), "/sbin/iptables"), 'create symlink', __FILE__, __LINE__);
		unset($iptables_location);

	}

	public function install_ispconfig()
	{
		global $conf;

		$install_dir = $conf['ispconfig_install_dir'];

		//* Create the ISPConfig installation directory
		if(!@is_dir("$install_dir")) {
			$command = "mkdir $install_dir";
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		//* Create a ISPConfig user and group
		$command = 'groupadd ispconfig';
		if(!is_group('ispconfig')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = "useradd -g ispconfig -d $install_dir ispconfig";
		if(!is_user('ispconfig')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* copy the ISPConfig interface part
		$command = "cp -rf ../interface $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* copy the ISPConfig server part
		$command = "cp -rf ../server $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Make a backup of the security settings
		if(is_file('/usr/local/ispconfig/security/security_settings.ini')) copy('/usr/local/ispconfig/security/security_settings.ini','/usr/local/ispconfig/security/security_settings.ini~');
		
		//* copy the ISPConfig security part
		$command = 'cp -rf ../security '.$install_dir;
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Apply changed security_settings.ini values to new security_settings.ini file
		if(is_file('/usr/local/ispconfig/security/security_settings.ini~')) {
			$security_settings_old = ini_to_array(file_get_contents('/usr/local/ispconfig/security/security_settings.ini~'));
			$security_settings_new = ini_to_array(file_get_contents('/usr/local/ispconfig/security/security_settings.ini'));
			if(is_array($security_settings_new) && is_array($security_settings_old)) {
				foreach($security_settings_new as $section => $sval) {
					if(is_array($sval)) {
						foreach($sval as $key => $val) {
							if(isset($security_settings_old[$section]) && isset($security_settings_old[$section][$key])) {
								$security_settings_new[$section][$key] = $security_settings_old[$section][$key];
							}
						}
					}
				}
				file_put_contents('/usr/local/ispconfig/security/security_settings.ini',array_to_ini($security_settings_new));
			}
		}

		//* Create a symlink, so ISPConfig is accessible via web
		// Replaced by a separate vhost definition for port 8080
		// $command = "ln -s $install_dir/interface/web/ /var/www/ispconfig";
		// caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* Create the config file for ISPConfig interface
		$configfile = 'config.inc.php';
		if(is_file($install_dir.'/interface/lib/'.$configfile)){
			copy("$install_dir/interface/lib/$configfile", "$install_dir/interface/lib/$configfile~");
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		$content = str_replace('{mysql_server_port}', $conf['mysql']['port'], $content);

		$content = str_replace('{mysql_master_server_ispconfig_user}', $conf['mysql']['master_ispconfig_user'], $content);
		$content = str_replace('{mysql_master_server_ispconfig_password}', $conf['mysql']['master_ispconfig_password'], $content);
		$content = str_replace('{mysql_master_server_database}', $conf['mysql']['master_database'], $content);
		$content = str_replace('{mysql_master_server_host}', $conf['mysql']['master_host'], $content);
		$content = str_replace('{mysql_master_server_port}', $conf['mysql']['master_port'], $content);

		$content = str_replace('{server_id}', $conf['server_id'], $content);
		$content = str_replace('{ispconfig_log_priority}', $conf['ispconfig_log_priority'], $content);
		$content = str_replace('{language}', $conf['language'], $content);
		$content = str_replace('{timezone}', $conf['timezone'], $content);
		$content = str_replace('{theme}', $conf['theme'], $content);
		$content = str_replace('{language_file_import_enabled}', ($conf['language_file_import_enabled'] == true)?'true':'false', $content);

		wf("$install_dir/interface/lib/$configfile", $content);

		//* Create the config file for ISPConfig server
		$configfile = 'config.inc.php';
		if(is_file($install_dir.'/server/lib/'.$configfile)){
			copy("$install_dir/server/lib/$configfile", "$install_dir/interface/lib/$configfile~");
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		$content = str_replace('{mysql_server_port}', $conf['mysql']['port'], $content);

		$content = str_replace('{mysql_master_server_ispconfig_user}', $conf['mysql']['master_ispconfig_user'], $content);
		$content = str_replace('{mysql_master_server_ispconfig_password}', $conf['mysql']['master_ispconfig_password'], $content);
		$content = str_replace('{mysql_master_server_database}', $conf['mysql']['master_database'], $content);
		$content = str_replace('{mysql_master_server_host}', $conf['mysql']['master_host'], $content);
		$content = str_replace('{mysql_master_server_port}', $conf['mysql']['master_port'], $content);

		$content = str_replace('{server_id}', $conf['server_id'], $content);
		$content = str_replace('{ispconfig_log_priority}', $conf['ispconfig_log_priority'], $content);
		$content = str_replace('{language}', $conf['language'], $content);
		$content = str_replace('{timezone}', $conf['timezone'], $content);
		$content = str_replace('{theme}', $conf['theme'], $content);
		$content = str_replace('{language_file_import_enabled}', ($conf['language_file_import_enabled'] == true)?'true':'false', $content);

		wf("$install_dir/server/lib/$configfile", $content);

		//* Create the config file for remote-actions (but only, if it does not exist, because
		//  the value is a autoinc-value and so changed by the remoteaction_core_module
		if (!file_exists($install_dir.'/server/lib/remote_action.inc.php')) {
			$content = '<?php' . "\n" . '$maxid_remote_action = 0;' . "\n" . '?>';
			wf($install_dir.'/server/lib/remote_action.inc.php', $content);
		}

		//* Enable the server modules and plugins.
		// TODO: Implement a selector which modules and plugins shall be enabled.
		$dir = $install_dir.'/server/mods-available/';
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..' && substr($file, -8, 8) == '.inc.php') {
						include_once $install_dir.'/server/mods-available/'.$file;
						$module_name = substr($file, 0, -8);
						$tmp = new $module_name;
						if($tmp->onInstall()) {
							if(!@is_link($install_dir.'/server/mods-enabled/'.$file)) @symlink($install_dir.'/server/mods-available/'.$file, $install_dir.'/server/mods-enabled/'.$file);
							if (strpos($file, '_core_module') !== false) {
								if(!@is_link($install_dir.'/server/mods-core/'.$file)) @symlink($install_dir.'/server/mods-available/'.$file, $install_dir.'/server/mods-core/'.$file);
							}
						}
						unset($tmp);
					}
				}
				closedir($dh);
			}
		}

		$dir = $install_dir.'/server/plugins-available/';
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($conf['apache']['installed'] == true && $file == 'nginx_plugin.inc.php') continue;
					if($conf['nginx']['installed'] == true && $file == 'apache2_plugin.inc.php') continue;
					if($file != '.' && $file != '..' && substr($file, -8, 8) == '.inc.php') {
						include_once $install_dir.'/server/plugins-available/'.$file;
						$plugin_name = substr($file, 0, -8);
						$tmp = new $plugin_name;
						if($tmp->onInstall()) {
							if(!@is_link($install_dir.'/server/plugins-enabled/'.$file)) @symlink($install_dir.'/server/plugins-available/'.$file, $install_dir.'/server/plugins-enabled/'.$file);
							if (strpos($file, '_core_plugin') !== false) {
								if(!@is_link($install_dir.'/server/plugins-core/'.$file)) @symlink($install_dir.'/server/plugins-available/'.$file, $install_dir.'/server/plugins-core/'.$file);
							}
						}
						unset($tmp);
					}
				}
				closedir($dh);
			}
		}

		// Update the server config
		$mail_server_enabled = ($conf['services']['mail'])?1:0;
		$web_server_enabled = ($conf['services']['web'])?1:0;
		$dns_server_enabled = ($conf['services']['dns'])?1:0;
		$file_server_enabled = ($conf['services']['file'])?1:0;
		$db_server_enabled = ($conf['services']['db'])?1:0;
		$vserver_server_enabled = ($conf['services']['vserver'])?1:0;
		$sql = "UPDATE `server` SET mail_server = ?, web_server = ?, dns_server = ?, file_server = ?, db_server = ?, vserver_server = ? WHERE server_id = ?";

		$this->db->query($sql, $mail_server_enabled, $web_server_enabled, $dns_server_enabled, $file_server_enabled, $db_server_enabled, $vserver_server_enabled, $conf['server_id']);
		if($conf['mysql']['master_slave_setup'] == 'y') {
			$this->dbmaster->query($sql, $mail_server_enabled, $web_server_enabled, $dns_server_enabled, $file_server_enabled, $db_server_enabled, $vserver_server_enabled, $conf['server_id']);
		}

		// chown install dir to root and chmod 755
		$command = 'chown root:root '.$install_dir;
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		$command = 'chmod 755 '.$install_dir;
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* Chmod the files and directories in the install dir
		$command = 'chmod -R 750 '.$install_dir.'/*';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* chown the interface files to the ispconfig user and group
		$command = 'chown -R ispconfig:ispconfig '.$install_dir.'/interface';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* chown the server files to the root user and group
		$command = 'chown -R root:root '.$install_dir.'/server';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* chown the security files to the root user and group
		$command = 'chown -R root:root '.$install_dir.'/security';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* chown the security directory and security_settings.ini to root:ispconfig
		$command = 'chown root:ispconfig '.$install_dir.'/security/security_settings.ini';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		$command = 'chown root:ispconfig '.$install_dir.'/security';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		$command = 'chown root:ispconfig '.$install_dir.'/security/ids.whitelist';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		$command = 'chown root:ispconfig '.$install_dir.'/security/ids.htmlfield';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		$command = 'chown root:ispconfig '.$install_dir.'/security/apache_directives.blacklist';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		$command = 'chown root:ispconfig '.$install_dir.'/security/nginx_directives.blacklist';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Make the global language file directory group writable
		exec("chmod -R 770 $install_dir/interface/lib/lang");

		//* Make the temp directory for language file exports writable
		exec("chmod -R 770 $install_dir/interface/web/temp");

		//* Make all interface language file directories group writable
		$handle = @opendir($install_dir.'/interface/web');
		while ($file = @readdir($handle)) {
			if ($file != '.' && $file != '..') {
				if(@is_dir($install_dir.'/interface/web'.'/'.$file.'/lib/lang')) {
					$handle2 = opendir($install_dir.'/interface/web'.'/'.$file.'/lib/lang');
					chmod($install_dir.'/interface/web'.'/'.$file.'/lib/lang', 0770);
					while ($lang_file = @readdir($handle2)) {
						if ($lang_file != '.' && $lang_file != '..') {
							chmod($install_dir.'/interface/web'.'/'.$file.'/lib/lang/'.$lang_file, 0770);
						}
					}
				}
			}
		}

		//* Make the APS directories group writable
		exec("chmod -R 770 $install_dir/interface/web/sites/aps_meta_packages");
		exec("chmod -R 770 $install_dir/server/aps_packages");

		//* make sure that the server config file (not the interface one) is only readable by the root user
		chmod($install_dir.'/server/lib/config.inc.php', 0600);
		chown($install_dir.'/server/lib/config.inc.php', 'root');
		chgrp($install_dir.'/server/lib/config.inc.php', 'root');

		//* Make sure thet the interface config file is readable by user ispconfig only
		chmod($install_dir.'/interface/lib/config.inc.php', 0600);
		chown($install_dir.'/interface/lib/config.inc.php', 'ispconfig');
		chgrp($install_dir.'/interface/lib/config.inc.php', 'ispconfig');

		if(@is_file("$install_dir/server/lib/mysql_clientdb.conf")) {
			exec("chmod 600 $install_dir/server/lib/mysql_clientdb.conf");
			exec("chown root:root $install_dir/server/lib/mysql_clientdb.conf");
		}
		
		if(is_dir($install_dir.'/interface/invoices')) {
			exec('chmod -R 770 '.escapeshellarg($install_dir.'/interface/invoices'));
			exec('chown -R ispconfig:ispconfig '.escapeshellarg($install_dir.'/interface/invoices'));
		}
		
		exec('chown -R root:root /usr/local/ispconfig/interface/ssl');

		// TODO: FIXME: add the www-data user to the ispconfig group. This is just for testing
		// and must be fixed as this will allow the apache user to read the ispconfig files.
		// Later this must run as own apache server or via suexec!
		if($conf['apache']['installed'] == true){
			$command = 'usermod -a -G ispconfig '.$conf['apache']['user'];
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
			if(is_group('ispapps')){
				$command = 'usermod -a -G ispapps '.$conf['apache']['user'];
				caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
			}
		}
		if($conf['nginx']['installed'] == true){
			$command = 'usermod -a -G ispconfig '.$conf['nginx']['user'];
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
			if(is_group('ispapps')){
				$command = 'usermod -a -G ispapps '.$conf['nginx']['user'];
				caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
			}
			// add nobody user to www group, as the default php-fpm pool from opensuse runs as nobody
			$command = 'usermod -a -G www nobody';
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		//* Make the shell scripts executable
		$command = "chmod +x $install_dir/server/scripts/*.sh";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		if ($this->install_ispconfig_interface == true && isset($conf['interface_password']) && $conf['interface_password']!='admin') {
			$sql = "UPDATE sys_user SET passwort = md5(?) WHERE username = 'admin';";
			$this->db->query($sql, $conf['interface_password']);
		}
		
		if($conf['apache']['installed'] == true && $this->install_ispconfig_interface == true){
			//* Copy the ISPConfig vhost for the controlpanel
			// TODO: These are missing! should they be "vhost_dist_*_dir" ?
			$vhost_conf_dir = $conf['apache']['vhost_conf_dir'];
			$vhost_conf_enabled_dir = $conf['apache']['vhost_conf_enabled_dir'];


			// Dont just copy over the virtualhost template but add some custom settings
			$tpl = new tpl('apache_ispconfig.vhost.master');
			$tpl->setVar('vhost_port',$conf['apache']['vhost_port']);

			// comment out the listen directive if port is 80 or 443
			if($conf['apache']['vhost_port'] == 80 or $conf['apache']['vhost_port'] == 443) {
				$tpl->setVar('vhost_port_listen','#');
			} else {
				$tpl->setVar('vhost_port_listen','');
			}

			if(is_file($install_dir.'/interface/ssl/ispserver.crt') && is_file($install_dir.'/interface/ssl/ispserver.key')) {
				$tpl->setVar('ssl_comment','');
			} else {
				$tpl->setVar('ssl_comment','#');
			}
			if(is_file($install_dir.'/interface/ssl/ispserver.crt') && is_file($install_dir.'/interface/ssl/ispserver.key') && is_file($install_dir.'/interface/ssl/ispserver.bundle')) {
				$tpl->setVar('ssl_bundle_comment','');
			} else {
				$tpl->setVar('ssl_bundle_comment','#');
			}
			
			$tpl->setVar('apache_version',getapacheversion());

			$content = $tpl->grab();
			$content = str_replace('/var/www/', '/srv/www/', $content);
			wf($vhost_conf_dir.'/ispconfig.vhost', $content);

			//if(!is_file('/srv/www/php-fcgi-scripts/ispconfig/.php-fcgi-starter')) {
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/apache_ispconfig_fcgi_starter.master', 'tpl/apache_ispconfig_fcgi_starter.master');
			$content = str_replace('{fastcgi_bin}', $conf['fastcgi']['fastcgi_bin'], $content);
			$content = str_replace('{fastcgi_phpini_path}', $conf['fastcgi']['fastcgi_phpini_path'], $content);
			exec('mkdir -p /srv/www/php-fcgi-scripts/ispconfig');
			wf('/srv/www/php-fcgi-scripts/ispconfig/.php-fcgi-starter', $content);
			exec('chmod +x /srv/www/php-fcgi-scripts/ispconfig/.php-fcgi-starter');
			exec('ln -s /usr/local/ispconfig/interface/web /srv/www/ispconfig');
			exec('chown -R ispconfig:ispconfig /srv/www/php-fcgi-scripts/ispconfig');

			//}

			//copy('tpl/apache_ispconfig.vhost.master', "$vhost_conf_dir/ispconfig.vhost");
			//* and create the symlink
			if($this->is_update == false) {
				if(@is_link("$vhost_conf_enabled_dir/ispconfig.vhost")) unlink("$vhost_conf_enabled_dir/ispconfig.vhost");
				if(!@is_link("$vhost_conf_enabled_dir/000-ispconfig.vhost")) {
					exec("ln -s $vhost_conf_dir/ispconfig.vhost $vhost_conf_enabled_dir/000-ispconfig.vhost");
				}

			}

			// Fix a setting in vhost master file for suse
			replaceLine('/usr/local/ispconfig/server/conf/vhost.conf.master', "suPHP_UserGroup", "        suPHP_UserGroup <tmpl_var name='system_user'> <tmpl_var name='system_group'>", 0);
		}

		if($conf['nginx']['installed'] == true && $this->install_ispconfig_interface == true){
			//* Copy the ISPConfig vhost for the controlpanel
			$vhost_conf_dir = $conf['nginx']['vhost_conf_dir'];
			$vhost_conf_enabled_dir = $conf['nginx']['vhost_conf_enabled_dir'];

			// Dont just copy over the virtualhost template but add some custom settings
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/nginx_ispconfig.vhost.master', 'tpl/nginx_ispconfig.vhost.master');
			$content = str_replace('{vhost_port}', $conf['nginx']['vhost_port'], $content);

			if(is_file($install_dir.'/interface/ssl/ispserver.crt') && is_file($install_dir.'/interface/ssl/ispserver.key')) {
				$content = str_replace('{ssl_on}', 'ssl', $content);
				$content = str_replace('{ssl_comment}', '', $content);
				$content = str_replace('{fastcgi_ssl}', 'on', $content);
			} else {
				$content = str_replace('{ssl_on}', '', $content);
				$content = str_replace('{ssl_comment}', '#', $content);
				$content = str_replace('{fastcgi_ssl}', 'off', $content);
			}

			$socket_dir = escapeshellcmd($conf['nginx']['php_fpm_socket_dir']);
			if(substr($socket_dir, -1) != '/') $socket_dir .= '/';
			if(!is_dir($socket_dir)) exec('mkdir -p '.$socket_dir);
			$fpm_socket = $socket_dir.'ispconfig.sock';

			//$content = str_replace('{fpm_port}', $conf['nginx']['php_fpm_start_port'], $content);
			$content = str_replace('{fpm_socket}', $fpm_socket, $content);

			wf($vhost_conf_dir.'/ispconfig.vhost', $content);

			unset($content);

			// PHP-FPM
			// Dont just copy over the php-fpm pool template but add some custom settings
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/php_fpm_pool.conf.master', 'tpl/php_fpm_pool.conf.master');
			$content = str_replace('{fpm_pool}', 'ispconfig', $content);
			//$content = str_replace('{fpm_port}', $conf['nginx']['php_fpm_start_port'], $content);
			$content = str_replace('{fpm_socket}', $fpm_socket, $content);
			$content = str_replace('{fpm_user}', 'ispconfig', $content);
			$content = str_replace('{fpm_group}', 'ispconfig', $content);
			wf($conf['nginx']['php_fpm_pool_dir'].'/ispconfig.conf', $content);

			//copy('tpl/nginx_ispconfig.vhost.master', $vhost_conf_dir.'/ispconfig.vhost');
			//* and create the symlink
			if($this->is_update == false) {
				if(@is_link($vhost_conf_enabled_dir.'/ispconfig.vhost')) unlink($vhost_conf_enabled_dir.'/ispconfig.vhost');
				if(!@is_link($vhost_conf_enabled_dir.'/000-ispconfig.vhost')) {
					symlink($vhost_conf_dir.'/ispconfig.vhost', $vhost_conf_enabled_dir.'/000-ispconfig.vhost');
				}
			}

			// create symlinks from /usr/share to phpMyAdmin and SquirrelMail, if they are installed
			if(!@file_exists('/usr/share/phpmyadmin') && @is_dir('/srv/www/htdocs/phpMyAdmin')) symlink('/srv/www/htdocs/phpMyAdmin/', '/usr/share/phpmyadmin');
			if(!@file_exists('/usr/share/squirrelmail') && @is_dir('/srv/www/htdocs/squirrelmail')) symlink('/srv/www/htdocs/squirrelmail/', '/usr/share/squirrelmail');
		}

		// Make the Clamav log files readable by ISPConfig
		//exec('chmod +r /var/log/clamav/clamav.log');
		//exec('chmod +r /var/log/clamav/freshclam.log');

		//* Install the update script
		if(is_file('/usr/local/bin/ispconfig_update_from_dev.sh')) unlink('/usr/local/bin/ispconfig_update_from_dev.sh');
		exec('chown root /usr/local/ispconfig/server/scripts/update_from_dev.sh');
		exec('chmod 700 /usr/local/ispconfig/server/scripts/update_from_dev.sh');
		exec('chown root /usr/local/ispconfig/server/scripts/update_stable.sh');
		exec('chmod 700 /usr/local/ispconfig/server/scripts/update_stable.sh');
		exec('chown root /usr/local/ispconfig/server/scripts/ispconfig_update.sh');
		exec('chmod 700 /usr/local/ispconfig/server/scripts/ispconfig_update.sh');
		if(!is_link('/usr/local/bin/ispconfig_update_from_dev.sh')) exec('ln -s /usr/local/ispconfig/server/scripts/ispconfig_update.sh /usr/local/bin/ispconfig_update_from_dev.sh');
		if(!is_link('/usr/local/bin/ispconfig_update.sh')) exec('ln -s /usr/local/ispconfig/server/scripts/ispconfig_update.sh /usr/local/bin/ispconfig_update.sh');

		//set the fast cgi starter script to executable
		//exec('chmod 755 '.$install_dir.'/interface/bin/php-fcgi');

		//* Make the logs readable for the ispconfig user
		if(@is_file('/var/log/mail.log')) exec('chmod +r /var/log/mail.log');
		if(@is_file('/var/log/mail.warn')) exec('chmod +r /var/log/mail.warn');
		if(@is_file('/var/log/mail.err')) exec('chmod +r /var/log/mail.err');
		if(@is_file('/var/log/messages')) exec('chmod +r /var/log/messages');

		//To enable apache to read the directories
		exec('chmod a+rx /usr/local/ispconfig');
		exec('chmod -R 751 /usr/local/ispconfig/interface');
		exec('chmod a+rx /usr/local/ispconfig/interface/web');

		//* Create the ispconfig log directory
		if(!is_dir($conf['ispconfig_log_dir'])) mkdir($conf['ispconfig_log_dir']);
		if(!is_file($conf['ispconfig_log_dir'].'/ispconfig.log')) exec('touch '.$conf['ispconfig_log_dir'].'/ispconfig.log');

		if(is_user('getmail')) {
			exec('mv /usr/local/ispconfig/server/scripts/run-getmail.sh /usr/local/bin/run-getmail.sh');
			exec('chown getmail /usr/local/bin/run-getmail.sh');
			exec('chmod 744 /usr/local/bin/run-getmail.sh');
		}

		if(is_dir($install_dir.'/interface/invoices')) {
			exec('chmod -R 770 '.escapeshellarg($install_dir.'/interface/invoices'));
			exec('chown -R ispconfig:ispconfig '.escapeshellarg($install_dir.'/interface/invoices'));
		}

		//* Create the ispconfig auth log file and set uid/gid
		if(!is_file($conf['ispconfig_log_dir'].'/auth.log')) {
			touch($conf['ispconfig_log_dir'].'/auth.log');
		}
		exec('chown ispconfig:ispconfig '. $conf['ispconfig_log_dir'].'/auth.log');
		exec('chmod 660 '. $conf['ispconfig_log_dir'].'/auth.log');

		//* Remove Domain module as its functions are available in the client module now
		if(@is_dir('/usr/local/ispconfig/interface/web/domain')) exec('rm -rf /usr/local/ispconfig/interface/web/domain');
		
		// Add symlink for patch tool
		if(!is_link('/usr/local/bin/ispconfig_patch')) exec('ln -s /usr/local/ispconfig/server/scripts/ispconfig_patch /usr/local/bin/ispconfig_patch');
		
		// Change mode of a few files from amavisd
		if(is_file($conf['amavis']['config_dir'].'/conf.d/50-user')) chmod($conf['amavis']['config_dir'].'/conf.d/50-user', 0640);
		if(is_file($conf['amavis']['config_dir'].'/50-user~')) chmod($conf['amavis']['config_dir'].'/50-user~', 0400);
		if(is_file($conf['amavis']['config_dir'].'/amavisd.conf')) chmod($conf['amavis']['config_dir'].'/amavisd.conf', 0640);
		if(is_file($conf['amavis']['config_dir'].'/amavisd.conf~')) chmod($conf['amavis']['config_dir'].'/amavisd.conf~', 0400);
	}
}

?>
