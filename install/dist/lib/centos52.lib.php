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

class installer extends installer_dist {

	public function configure_mailman($status = 'insert') {
		global $conf;

		$config_dir = $conf['mailman']['config_dir'].'/';
		$full_file_name = $config_dir.'mm_cfg.py';
		//* Backup exiting file
		if(is_file($full_file_name)) {
			copy($full_file_name, $config_dir.'mm_cfg.py~');
		}

		// load files
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/mm_cfg.py.master', 'tpl/mm_cfg.py.master');
		$old_file = rf($full_file_name);

		$old_options = array();
		$lines = explode("\n", $old_file);
		foreach ($lines as $line)
		{
			if (trim($line) != '' && substr($line, 0, 1) != '#')
			{
				@list($key, $value) = @explode("=", $line);
				if (!empty($value))
				{
					$key = rtrim($key);
					$old_options[$key] = trim($value);
				}
			}
		}

		$config_dir = $conf['mailman']['config_dir'].'/';
		$full_file_name = $config_dir.'virtual_to_transport.sh';

		//* Backup exiting virtual_to_transport.sh script
		if(is_file($full_file_name)) {
			copy($full_file_name, $config_dir.'virtual_to_transport.sh~');
		}

		if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/mailman-virtual_to_transport.sh')) {
			copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/mailman-virtual_to_transport.sh', $full_file_name);
		} else {
			copy('tpl/mailman-virtual_to_transport.sh', $full_file_name);
		}
		chgrp($full_file_name, 'mailman');
		chmod($full_file_name, 0750);

		if(!is_file('/var/lib/mailman/data/transport-mailman')) touch('/var/lib/mailman/data/transport-mailman');
		exec('/usr/sbin/postmap /var/lib/mailman/data/transport-mailman');

		exec('/usr/lib/mailman/bin/genaliases 2>/dev/null');

		$virtual_domains = '';
		if($status == 'update')
		{
			// create virtual_domains list
			$domainAll = $this->db->queryAllRecords("SELECT domain FROM mail_mailinglist GROUP BY domain");

			if(is_array($domainAll)) {
				foreach($domainAll as $domain)
				{
					if ($domainAll[0]['domain'] == $domain['domain'])
						$virtual_domains .= "'".$domain['domain']."'";
					else
						$virtual_domains .= ", '".$domain['domain']."'";
				}
			}
		}
		else
			$virtual_domains = "' '";

		$content = str_replace('{hostname}', $conf['hostname'], $content);
		if(!isset($old_options['DEFAULT_SERVER_LANGUAGE'])) $old_options['DEFAULT_SERVER_LANGUAGE'] = '';
		$content = str_replace('{default_language}', $old_options['DEFAULT_SERVER_LANGUAGE'], $content);
		$content = str_replace('{virtual_domains}', $virtual_domains, $content);

		wf($full_file_name, $content);
	}

	public function configure_amavis() {
		global $conf;

		// amavisd user config file
		$configfile = 'fedora_amavisd_conf';
		if(is_file($conf["amavis"]["config_dir"].'/amavisd.conf')) copy($conf["amavis"]["config_dir"].'/amavisd.conf', $conf["amavis"]["config_dir"].'/amavisd.conf~');
		if(is_file($conf["amavis"]["config_dir"].'/amavisd.conf~')) exec('chmod 400 '.$conf["amavis"]["config_dir"].'/amavisd.conf~');
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_port}', $conf["mysql"]["port"], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		$content = str_replace('{hostname}', $conf['hostname'], $content);
		$content = str_replace('/var/spool/amavisd/clamd.sock', '/tmp/clamd.socket', $content);
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

		// Append the configuration for amavisd to the master.cf file
		if(is_file($conf['postfix']['config_dir'].'/master.cf')) copy($conf['postfix']['config_dir'].'/master.cf', $conf['postfix']['config_dir'].'/master.cf~');
		$content = rf($conf['postfix']['config_dir'].'/master.cf');
		// Only add the content if we had not addded it before
		if(!preg_match('/^amavis\s+unix\s+/m', $content)) {
			unset($content);
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/master_cf_amavis.master', 'tpl/master_cf_amavis.master');
			af($conf['postfix']['config_dir'].'/master.cf', $content);
			$content = rf($conf['postfix']['config_dir'].'/master.cf');
		}
		if(!preg_match('/^127.0.0.1:10025\s+/m', $content)) {
			unset($content);
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/master_cf_amavis10025.master', 'tpl/master_cf_amavis10025.master');
			af($conf['postfix']['config_dir'].'/master.cf', $content);
			$content = rf($conf['postfix']['config_dir'].'/master.cf');
		}
		if(!preg_match('/^127.0.0.1:10027\s+/m', $content)) {
			unset($content);
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/master_cf_amavis10027.master', 'tpl/master_cf_amavis10027.master');
			af($conf['postfix']['config_dir'].'/master.cf', $content);
		}
		unset($content);

		removeLine('/etc/sysconfig/freshclam', 'FRESHCLAM_DELAY=disabled-warn   # REMOVE ME', 1);
		replaceLine('/etc/freshclam.conf', 'Example', '# Example', 1);


	}


}

?>
