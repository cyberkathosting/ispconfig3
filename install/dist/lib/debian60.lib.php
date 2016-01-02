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

class installer extends installer_base {

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
		if ($this->postfix_master()) {
			exec ("postconf -M dovecot.unix &> /dev/null", $out, $ret);
 			$add_dovecot_service = @($out[0]=='')?true:false;
		} else { //* fallback - postfix < 2.9
			$content = rf($config_dir.'/master.cf');
			$add_dovecot_service = @(!stristr($content, "dovecot/deliver"))?true:false;
		}
		if($add_dovecot_service) {
			//* backup
			if(is_file($config_dir.'/master.cf')){
				copy($config_dir.'/master.cf', $config_dir.'/master.cf~2');
			}
			if(is_file($config_dir.'/master.cf~')){
				chmod($config_dir.'/master.cf~2', 0400);
			}
			//* Configure master.cf and add a line for deliver
			$content = rf($conf["postfix"]["config_dir"].'/master.cf');
			$deliver_content = 'dovecot   unix  -       n       n       -       -       pipe'."\n".'  flags=DRhu user=vmail:vmail argv=/usr/lib/dovecot/deliver -f ${sender} -d ${user}@${nexthop}';
			af($config_dir.'/master.cf', $deliver_content);
			unset($content);
			unset($deliver_content);
		}

		//* Reconfigure postfix to use dovecot authentication
		// Adding the amavisd commands to the postfix configuration
		$postconf_commands = array (
			'dovecot_destination_recipient_limit = 1',
			'virtual_transport = '.$virtual_transport,
			'smtpd_sasl_type = dovecot',
			'smtpd_sasl_path = private/auth'
		);

		// Make a backup copy of the main.cf file
		copy($conf["postfix"]["config_dir"].'/main.cf', $conf["postfix"]["config_dir"].'/main.cf~3');

		// Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		//* copy dovecot.conf
		$config_dir = $conf['dovecot']['config_dir'];
		$configfile = 'dovecot.conf';
		if(is_file($config_dir.'/'.$configfile)){
			copy($config_dir.'/'.$configfile, $config_dir.'/'.$configfile.'~');
		}

		//* Get the dovecot version
		exec('dovecot --version', $tmp);
		$dovecot_version = $tmp[0];
		unset($tmp);

		//* Copy dovecot configuration file
		if(version_compare($dovecot_version,2) >= 0) {
			if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian6_dovecot2.conf.master')) {
				copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian6_dovecot2.conf.master', $config_dir.'/'.$configfile);
			} else {
				copy('tpl/debian6_dovecot2.conf.master', $config_dir.'/'.$configfile);
			}
			replaceLine($config_dir.'/'.$configfile, 'postmaster_address = postmaster@example.com', 'postmaster_address = postmaster@'.$conf['hostname'], 1, 0);
			if(version_compare($dovecot_version,2.1) < 0) {
				removeLine($config_dir.'/'.$configfile, 'ssl_protocols =');
			}
		} else {
			if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian6_dovecot.conf.master')) {
				copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian6_dovecot.conf.master', $config_dir.'/'.$configfile);
			} else {
				copy('tpl/debian6_dovecot.conf.master', $config_dir.'/'.$configfile);
			}
		}
		
		//* dovecot-lmtpd
		if($configure_lmtp) {
			replaceLine($config_dir.'/'.$configfile, 'protocols = imap pop3', 'protocols = imap pop3 lmtp', 1, 0);
		}

		//* dovecot-sql.conf
		$configfile = 'dovecot-sql.conf';
		if(is_file($config_dir.'/'.$configfile)){
			copy($config_dir.'/'.$configfile, $config_dir.'/'.$configfile.'~');
			chmod($config_dir.'/'.$configfile.'~', 0400);
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian6_dovecot-sql.conf.master', 'tpl/debian6_dovecot-sql.conf.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		$content = str_replace('{mysql_server_port}', $conf['mysql']['port'], $content);
		$content = str_replace('{server_id}', $conf['server_id'], $content);
		wf($config_dir.'/'.$configfile, $content);

		chmod($config_dir.'/'.$configfile, 0600);
		chown($config_dir.'/'.$configfile, 'root');
		chgrp($config_dir.'/'.$configfile, 'root');
		
		// Dovecot shall ignore mounts in website directory
		if(is_installed('doveadm')) exec("doveadm mount add '/var/www/*' ignore > /dev/null 2> /dev/null");

	}

	public function configure_apache() {
		global $conf;

		if(file_exists('/etc/apache2/mods-available/fcgid.conf')) replaceLine('/etc/apache2/mods-available/fcgid.conf', 'MaxRequestLen', 'MaxRequestLen 15728640', 0, 1);

		parent::configure_apache();
	}

	public function configure_fail2ban() {
		/*
        copy('tpl/dovecot-pop3imap.conf.master',"/etc/fail2ban/filter.d/dovecot-pop3imap.conf");
        copy('tpl/dovecot_fail2ban_jail.local.master','/etc/fail2ban/jail.local');
	*/
	}

}

?>
