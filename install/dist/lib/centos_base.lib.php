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

class installer_centos extends installer_dist {
	
	protected $clamav_socket = '/tmp/clamd.socket';
	
	public function configure_amavis() {
		global $conf, $dist;

		// amavisd user config file
		$configfile = 'fedora_amavisd_conf';
		if(!is_dir($conf["amavis"]["config_dir"])) mkdir($conf["amavis"]["config_dir"]);
		if(is_file($conf["amavis"]["config_dir"].'/amavisd.conf')) copy($conf["amavis"]["config_dir"].'/amavisd.conf', $conf["amavis"]["config_dir"].'/amavisd.conf~');
		if(is_file($conf["amavis"]["config_dir"].'/amavisd.conf~')) exec('chmod 400 '.$conf["amavis"]["config_dir"].'/amavisd.conf~');
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_port}', $conf["mysql"]["port"], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		$content = str_replace('{hostname}', $conf['hostname'], $content);
		$content = str_replace('/var/spool/amavisd/clamd.sock', $this->clamav_socket, $content);
		$content = str_replace('{amavis_config_dir}', $conf['amavis']['config_dir'], $content);
		wf($conf["amavis"]["config_dir"].'/amavisd.conf', $content);
		chmod($conf['amavis']['config_dir'].'/amavisd.conf', 0640);
		
		if(!is_file($conf['amavis']['config_dir'].'/60-dkim')) {
			touch($conf['amavis']['config_dir'].'/60-dkim');
			chmod($conf['amavis']['config_dir'].'/60-dkim', 0640);
		}
		
		// for CentOS 7.2 only
		if($dist['confid'] == 'centos72') {
			chmod($conf['amavis']['config_dir'].'/amavisd.conf', 0750);
			chgrp($conf['amavis']['config_dir'].'/amavisd.conf', 'amavis');
			chmod($conf['amavis']['config_dir'].'/60-dkim', 0750);
			chgrp($conf['amavis']['config_dir'].'/60-dkim', 'amavis');
		}


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

		removeLine('/etc/sysconfig/freshclam', 'FRESHCLAM_DELAY=disabled-warn   # REMOVE ME', 1);
		replaceLine('/etc/freshclam.conf', 'Example', '# Example', 1);
		
		// get shell-group for amavis
		$amavis_group=exec('grep -o "^amavis:\|^vscan:" /etc/group');
		if(!empty($amavis_group)) {
			$amavis_group=rtrim($amavis_group, ":");
		}
		// get shell-user for amavis
		$amavis_user=exec('grep -o "^amavis:\|^vscan:" /etc/passwd');
		if(!empty($amavis_user)) {
			$amavis_user=rtrim($amavis_user, ":");
		}
		
		// Create the director for DKIM-Keys
		if(!is_dir('/var/lib/amavis')) mkdir('/var/lib/amavis', 0750, true);
		if(!empty($amavis_user)) exec('chown '.$amavis_user.' /var/lib/amavis');
		if(!empty($amavis_group)) exec('chgrp '.$amavis_group.' /var/lib/amavis');
		if(!is_dir('/var/lib/amavis/dkim')) mkdir('/var/lib/amavis/dkim', 0750);
		if(!empty($amavis_user)) exec('chown -R '.$amavis_user.' /var/lib/amavis/dkim');
		if(!empty($amavis_group)) exec('chgrp -R '.$amavis_group.' /var/lib/amavis/dkim');


	}


}

?>
