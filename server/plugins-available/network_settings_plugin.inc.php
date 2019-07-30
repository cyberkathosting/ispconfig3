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

class network_settings_plugin {

	var $plugin_name = 'network_settings_plugin';
	var $class_name = 'network_settings_plugin';


	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

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

		$app->plugins->registerEvent('server_insert', 'network_settings_plugin', 'insert');
		$app->plugins->registerEvent('server_update', 'network_settings_plugin', 'update');

		$app->plugins->registerEvent('server_ip_insert', 'network_settings_plugin', 'insert');
		$app->plugins->registerEvent('server_ip_update', 'network_settings_plugin', 'update');



	}

	function insert($event_name, $data) {
		global $app, $conf;

		$this->update($event_name, $data);

	}

	// The purpose of this plugin is to rewrite the main.cf file
	function update($event_name, $data) {
		global $app, $conf;

		// get the config
		$app->uses('getconf');
		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');

		//* Configure the debian network card settings.
		//* Dont configure
		if($server_config['auto_network_configuration'] == 'y' &&
			$data['mirrored'] == false &&
			$server_config['ip_address'] != '0.0.0.0' &&
			$server_config['gateway'] != '0.0.0.0') {

			if (is_file('/etc/debian_version'))
			{
				copy('/etc/network/interfaces', '/etc/network/interfaces~');

				$app->load('tpl');

				$network_tpl = new tpl();
				$network_tpl->newTemplate('debian_network_interfaces.master');

				$network_tpl->setVar('ip_address', $server_config['ip_address']);
				$network_tpl->setVar('netmask', $server_config['netmask']);
				$network_tpl->setVar('gateway', $server_config['gateway']);
				$network_tpl->setVar('broadcast', $this->broadcast($server_config['ip_address'], $server_config['netmask']));
				$network_tpl->setVar('network', $this->network($server_config['ip_address'], $server_config['netmask']));

				$records = $app->db->queryAllRecords("SELECT ip_address FROM server_ip WHERE server_id = ? ORDER BY server_ip_id ASC", $conf['server_id']);
				$ip_records = array();
				$additionl_ip_records = 0;
				$n = 0;
				if(is_array($records)) {
					foreach($records as $rec) {
						/*
						 * don't insert the main-ip again!
						 */
						if ($rec['ip_address'] != $server_config['ip_address'] && filter_var($rec['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
						{
							$ip_records[$n] = array(
								'id' => $n,
								'ip_address' => $rec['ip_address'],
								'netmask' => $server_config['netmask'],
								'gateway' => $server_config['gateway'],
								'broadcast' => $this->broadcast($rec['ip_address'], $server_config['netmask']),
								'network' => $this->network($rec['ip_address'], $server_config['netmask'])
							);
							$additionl_ip_records = 1;
							$n++;
						}
					}
				}

				/*
				 * If we have more than 1 IP we have to add the main-ip at the end
				 * of the network-ip-list. If we don't do so, there may be problems
				 * in multi-server-settings (with the acces from other server to the
				 * main-server) because the LAST IP in the list is the IP mysql uses
				 * to determine the host, the user is logging in from.
				 */
				/*
				// Disabled this part as it causes problems on multiserver setups
				if ($additionl_ip_records != 0)
				{
					$swap['ip_address'] = $ip_records[$n-1]['ip_address'];
					$swap['netmask'] = $ip_records[$n-1]['netmask'];
					$swap['gateway'] = $ip_records[$n-1]['gateway'];

					$ip_records[$n-1] = array(
						'id' => $n-1,
						'ip_address' => $server_config['ip_address'],
						'netmask' => $server_config['netmask'],
						'gateway' => $server_config['gateway'],
						'broadcast' => $this->broadcast($server_config['ip_address'],$server_config['netmask']),
						'network' => $this->network($server_config['ip_address'],$server_config['netmask'])
					);
					$network_tpl->setVar('ip_address',$swap['ip_address']);
					$network_tpl->setVar('netmask',$swap['netmask']);
					$network_tpl->setVar('gateway',$swap['gateway']);
					$network_tpl->setVar('broadcast',$this->broadcast($swap['ip_address'],$swap['netmask']));
					$network_tpl->setVar('network',$this->network($swap['ip_address'],$swap['netmask']));
				}
				*/

				$network_tpl->setVar('additionl_ip_records', $additionl_ip_records);
				$network_tpl->setLoop('interfaces', $ip_records);
				file_put_contents('/etc/network/interfaces', $network_tpl->grab());
				unset($network_tpl);

				$app->log('Changed Network settings', LOGLEVEL_DEBUG);
				exec($conf['init_scripts'] . '/' . 'networking force-reload');
			}
			elseif (is_file('/etc/gentoo-release'))
			{
				copy('/etc/conf.d/net', '/etc/conf.d/net~');

				$app->load('tpl');

				$network_tpl = new tpl();
				$network_tpl->newTemplate('gentoo_network_interfaces.master');

				$network_tpl->setVar('ip_address', $server_config['ip_address']);
				$network_tpl->setVar('netmask', $server_config['netmask']);
				$network_tpl->setVar('gateway', $server_config['gateway']);
				$network_tpl->setVar('broadcast', $this->broadcast($server_config['ip_address'], $server_config['netmask']));

				$records = $app->db->queryAllRecords("SELECT ip_address FROM server_ip WHERE server_id = ? order by ip_address", $conf['server_id']);
				$ip_records = array();
				$additionl_ip_records = 0;
				$n = 0;
				if(is_array($records)) {
					foreach($records as $rec) {
						/*
						 * don't insert the main-ip again!
						 */
						if ($rec['ip_address'] != $server_config['ip_address'] && filter_var($rec['ip_address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
						{
							$ip_records[$n] = array(
								'id' => $n,
								'ip_address' => $rec['ip_address'],
								'netmask' => $server_config['netmask'],
								'gateway' => $server_config['gateway'],
								'broadcast' => $this->broadcast($rec['ip_address'], $server_config['netmask'])
							);
							$additionl_ip_records = 1;
							$n++;
						}
					}
				}

				/*
				 * If we have more than 1 IP we have to add the main-ip at the end
				 * of the network-ip-list. If we don't do so, there may be problems
				 * in multi-server-settings (with the acces from other server to the
				 * main-server) because the LAST IP in the list is the IP mysql uses
				 * to determine the host, the user is logging in from.
				 */
				if ($additionl_ip_records != 0)
				{
					$swap['ip_address'] = $ip_records[$n-1]['ip_address'];
					$swap['netmask'] = $ip_records[$n-1]['netmask'];
					$swap['gateway'] = $ip_records[$n-1]['gateway'];

					$ip_records[$n-1] = array(
						'id' => $n-1,
						'ip_address' => $server_config['ip_address'],
						'netmask' => $server_config['netmask'],
						'gateway' => $server_config['gateway'],
						'broadcast' => $this->broadcast($server_config['ip_address'], $server_config['netmask'])
					);
					$network_tpl->setVar('ip_address', $swap['ip_address']);
					$network_tpl->setVar('netmask', $swap['netmask']);
					$network_tpl->setVar('gateway', $swap['gateway']);
					$network_tpl->setVar('broadcast', $this->broadcast($swap['ip_address'], $swap['netmask']));
				}

				$network_tpl->setVar('additionl_ip_records', $additionl_ip_records);
				$network_tpl->setLoop('interfaces', $ip_records);
				file_put_contents('/etc/conf.d/net', $network_tpl->grab());
				unset($network_tpl);

				$app->log('Changed Network settings', LOGLEVEL_DEBUG);
				exec($conf['init_scripts'] . '/' . 'net.eth0 restart');
			}
			else {
				$app->log('Network configuration not available for this Linux distribution.', LOGLEVEL_DEBUG);
			}

		} else {
			if($data['mirrored'] == true) {
				$app->log('Skipping network config request. IP addresses from master are not configured on the mirror.', LOGLEVEL_DEBUG);
			}
			if($server_config['auto_network_configuration'] == 'n') {
				$app->log('Network configuration disabled in server settings.', LOGLEVEL_DEBUG);
			}
		}
		
		//* Configure hostname
		if($event_name == 'server_update' && $data['mirrored'] == false) {
			
			//* get old server config
			$tmp = $app->ini_parser->parse_ini_string(stripslashes($data['old']['config']));
			$old_server_config = $tmp['server'];
			unset($tmp);
			
			$new_hostname = trim($server_config['hostname']);
			$old_hostname = trim($old_server_config['hostname']);
			
			if($new_hostname != '' && $old_hostname != $new_hostname) {
				
				if(is_file('/etc/hostname')) {
					$app->system->file_put_contents('/etc/hostname',$new_hostname);
					$app->log('Changed /etc/hostname to '.$new_hostname, LOGLEVEL_DEBUG);
				}
				
				if(is_file('/etc/mailname')) {
					$app->system->file_put_contents('/etc/mailname',$new_hostname);
					$app->log('Changed /etc/mailname to '.$new_hostname, LOGLEVEL_DEBUG);
				}
				
				$postconf_commands = array(
					'myhostname = '.$new_hostname,
					'mydestination = '.$new_hostname.', localhost, localhost.localdomain'
				);
				
				//* Executing the postconf commands
				foreach($postconf_commands as $cmd) {
					$command = "postconf -e ?";
					$app->system->exec_safe($command, $cmd);
				}
				
				$app->log('Changed changed myhostname and mydestination in postfix main.cf to '.$new_hostname, LOGLEVEL_DEBUG);
				
				//* change /etc/hosts
				$hosts = file_get_contents('/etc/hosts');
				$hosts = str_replace($old_hostname,$new_hostname,$hosts);
				$app->system->file_put_contents('/etc/hosts',$hosts);
				
				exec($app->system->getinitcommand('postfix', 'restart').' 2>&1');
				exec($app->system->getinitcommand('networking', 'restart').' 2>&1');
				
			}
			
		}
		

	}

	function network($ip, $netmask){
		$netmask = $this->netmask($netmask);
		list($f1, $f2, $f3, $f4) = explode('.', $netmask);
		$netmask_bin = str_pad(decbin($f1), 8, '0', STR_PAD_LEFT).str_pad(decbin($f2), 8, '0', STR_PAD_LEFT).str_pad(decbin($f3), 8, '0', STR_PAD_LEFT).str_pad(decbin($f4), 8, '0', STR_PAD_LEFT);
		list($f1, $f2, $f3, $f4) = explode('.', $ip);
		$ip_bin = str_pad(decbin($f1), 8, '0', STR_PAD_LEFT).str_pad(decbin($f2), 8, '0', STR_PAD_LEFT).str_pad(decbin($f3), 8, '0', STR_PAD_LEFT).str_pad(decbin($f4), 8, '0', STR_PAD_LEFT);
		for($i=0;$i<32;$i++){
			$network_bin .= substr($netmask_bin, $i, 1) * substr($ip_bin, $i, 1);
		}
		$network_bin = wordwrap($network_bin, 8, '.', 1);
		list($f1, $f2, $f3, $f4) = explode('.', trim($network_bin));
		return bindec($f1).'.'.bindec($f2).'.'.bindec($f3).'.'.bindec($f4);
	}

	function broadcast($ip, $netmask){
		$netmask = $this->netmask($netmask);
		$binary_netmask = $this->binary_netmask($netmask);
		list($f1, $f2, $f3, $f4) = explode('.', $ip);
		$ip_bin = str_pad(decbin($f1), 8, '0', STR_PAD_LEFT).str_pad(decbin($f2), 8, '0', STR_PAD_LEFT).str_pad(decbin($f3), 8, '0', STR_PAD_LEFT).str_pad(decbin($f4), 8, '0', STR_PAD_LEFT);
		$broadcast_bin = str_pad(substr($ip_bin, 0, $binary_netmask), 32, '1', STR_PAD_RIGHT);
		$broadcast_bin = wordwrap($broadcast_bin, 8, '.', 1);
		list($f1, $f2, $f3, $f4) = explode('.', trim($broadcast_bin));
		return bindec($f1).'.'.bindec($f2).'.'.bindec($f3).'.'.bindec($f4);
	}

	function netmask($netmask){
		list($f1, $f2, $f3, $f4) = explode('.', trim($netmask));
		$bin = str_pad(decbin($f1), 8, '0', STR_PAD_LEFT).str_pad(decbin($f2), 8, '0', STR_PAD_LEFT).str_pad(decbin($f3), 8, '0', STR_PAD_LEFT).str_pad(decbin($f4), 8, '0', STR_PAD_LEFT);
		$parts = explode('0', $bin);
		$bin = str_pad($parts[0], 32, '0', STR_PAD_RIGHT);
		$bin = wordwrap($bin, 8, '.', 1);
		list($f1, $f2, $f3, $f4) = explode('.', trim($bin));
		return bindec($f1).'.'.bindec($f2).'.'.bindec($f3).'.'.bindec($f4);
	}

	function binary_netmask($netmask){
		list($f1, $f2, $f3, $f4) = explode('.', trim($netmask));
		$bin = str_pad(decbin($f1), 8, '0', STR_PAD_LEFT).str_pad(decbin($f2), 8, '0', STR_PAD_LEFT).str_pad(decbin($f3), 8, '0', STR_PAD_LEFT).str_pad(decbin($f4), 8, '0', STR_PAD_LEFT);
		$parts = explode('0', $bin);
		return substr_count($parts[0], '1');
	}

} // end class



?>
