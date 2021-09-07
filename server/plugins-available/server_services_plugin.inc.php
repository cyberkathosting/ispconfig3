<?php

/*
Copyright (c) 2017, Florian Schaal, schaal @it UG
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


// The purpose of this plugin is to handle the server/plugins-enabled folder

class server_services_plugin {

	var $plugin_name = 'server_services_plugin';
	var $class_name = 'server_services_plugin';

	var $services = array('mail_server', 'web_server', 'dns_server', 'db_server', 'vserver_server', 'xmpp_server');

	var $mail_plugins = array('getmail_plugin', 'mail_plugin', 'mail_plugin_dkim', 'mailman_plugin', 'postfix_filter_plugin', 'postfix_server_plugin');
	var $courier_plugins = array('maildrop_plugin');
	var $dovecot_plugins = array('maildeliver_plugin');

	var $web_plugins = array('cron_plugin', 'cron_jailkit_plugin', 'ftpuser_base_plugin', 'shelluser_base_plugin', 'shelluser_jailkit_plugin', 'webserver_plugin');
	var $apache_plugins = array('apache2_plugin');
	var $nginx_plugins = array('nginx_plugin', 'nginx_reverseproxy_plugin');

	var $bind_plugins = array('bind_dlz_plugin', 'bind_plugin');
	var $powerdns_plugins = array('powerdns_plugin');

	var $db_plugins = array('mysql_clientdb_plugin');

	var $openvz_plugins = array('openvz_plugin');

	var $xmpp_plugins = array('xmpp_plugin');

	function onInstall() {

		return true;

	}

	function onLoad() {
		global $app;

		$app->plugins->registerEvent('server_insert', 'server_services_plugin', 'insert');
		$app->plugins->registerEvent('server_update', 'server_services_plugin', 'update');
		$app->plugins->registerEvent('server_delete', 'server_services_delete', 'delete');

	}

	function insert($event_name, $data) {

		$this->update($event_name, $data);

	}

	function delete($event_name, $data) {

		$this->update($event_name, $data);

	}

	function update($event_name, $data) {
		global $app, $conf;

		$app->uses('getconf');
		$old_services = array();
		$new_services = array();
		foreach($this->services as $service) {
			$old_services[$service] = $data['old'][$service];
			$new_services[$service] = $data['new'][$service];
		}
		$changed_services=array_diff_assoc($new_services,$old_services);
		foreach($changed_services as $service => $value) {
			switch($service) {
				case 'mail_server':
        			$config = $app->getconf->get_server_config($conf['server_id'], 'mail');
					$plugins = @($config['pop3_imap_daemon'] == 'dovecot')?$this->dovecot_plugins:$this->courier_plugins;
					$plugins = array_merge($plugins, $this->mail_plugins);
					$this->change_state($plugins, $value, $config);
				break;
				case 'web_server':
        			$config = $app->getconf->get_server_config($conf['server_id'], 'web');
					$plugins = @($config['server_type'] == 'apache')?$this->apache_plugins:$this->nginx_plugins;
					$plugins = array_merge($plugins, $this->web_plugins);
					$this->change_state($plugins, $value, $config);
				break;
				case 'dns_server':
        			$config = $app->getconf->get_server_config($conf['server_id'], 'dns');
					$plugins = @(isset($config['bind_user']))?$this->bind_plugins:$this->powerdns_plugins;
					$this->change_state($plugins, $value, $config);
				break;
				case 'db_server':
					$this->change_state($this->db_plugins, $value, $config);
				break;
				case 'vserver_server':
					$this->change_state($this->openvz_plugins, $value, $config);
				break;
				case 'xmpp_server':
					$this->change_state($this->xmpp_plugins, $value, $config);
				break;
			}
		}

	}

	function change_state($plugins, $value, $config) {

		$enabled_dir = '/usr/local/ispconfig/server/plugins-enabled/';
		$available_dir = '/usr/local/ispconfig/server/plugins-available/';

		if($value == 0) { //* disable services
			foreach($plugins as $plugin) {
				if(is_link($enabled_dir.$plugin.'.inc.php')) {
					unlink($enabled_dir.$plugin.'.inc.php');
				}
			}
		} 
		if ($value == 1) { //* enable services
			foreach($plugins as $plugin) {
				if(is_file($available_dir.$plugin.'.inc.php') && !is_link($enabled_dir.$plugin.'.inc.php')) {
					symlink($available_dir.$plugin.'.inc.php', $enabled_dir.$plugin.'.inc.php');
				}
			}
		}

	}
	
} // end class



?>
