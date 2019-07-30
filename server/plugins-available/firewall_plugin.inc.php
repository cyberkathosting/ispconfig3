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

class firewall_plugin {

	private $plugin_name = 'firewall_plugin';
	private $class_name  = 'firewall_plugin';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	public function onInstall() {
		global $conf;

		if(($conf['bastille']['installed'] == true || $conf['ufw']['installed'] == true || $conf['firewall']['installed'] == true) && $conf['services']['firewall'] == true) {
			return true;
		} else {
			return false;
		}

	}


	/*
	 	This function is called when the plugin is loaded
	*/

	public function onLoad() {
		global $app;

		/*
		Register for the events
		*/

		//* Mailboxes
		$app->plugins->registerEvent('firewall_insert', $this->plugin_name, 'insert');
		$app->plugins->registerEvent('firewall_update', $this->plugin_name, 'update');
		$app->plugins->registerEvent('firewall_delete', $this->plugin_name, 'delete');
	}


	public function insert($event_name, $data) {
		global $app, $conf;

		$this->update($event_name, $data);

	}

	public function update($event_name, $data) {
		global $app, $conf;

		//* load the server configuration options
		if(!$data['mirrored']) {
			$app->uses('getconf');
			$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
			if($server_config['firewall'] == 'ufw') {
				$this->ufw_update($event_name, $data);
			} else {
				$this->bastille_update($event_name, $data);
			}
		}
	}

	public function delete($event_name, $data) {
		global $app, $conf;

		//* load the server configuration options
		if(!$data['mirrored']) {
			$app->uses('getconf');
			$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');

			if($server_config['firewall'] == 'ufw') {
				$this->ufw_delete($event_name, $data);
			} else {
				$this->bastille_delete($event_name, $data);
			}
		}
	}

	private function ufw_update($event_name, $data) {
		global $app, $conf;

		$app->uses('system');

		if(!$app->system->is_installed('ufw')) {
			$app->log('UFW Firewall is not installed', LOGLEVEL_WARN);
			return false;
		}

		exec('ufw --version', $out);
		$parts = explode(' ', $out[0]);
		$ufwversion = $parts[1];
		unset($parts);
		unset($out);

		if(version_compare( $ufwversion , '0.30') < 0) {
			$app->log('The installed UFW Firewall version is too old. Minimum required version 0.30', LOGLEVEL_WARN);
			return false;
		}

		//* Basic firewall setup when the firewall is added the first time
		if($event_name == 'firewall_insert') {
			exec('ufw --force disable');
			exec('ufw --force reset');
			exec('ufw default deny incoming');
			exec('ufw default allow outgoing');
		}

		$tcp_ports_new = $this->clean_ports($data['new']['tcp_port'], ',');
		$tcp_ports_old = $this->clean_ports($data['old']['tcp_port'], ',');
		$udp_ports_new = $this->clean_ports($data['new']['udp_port'], ',');
		$udp_ports_old = $this->clean_ports($data['old']['udp_port'], ',');

		$tcp_ports_new_array = explode(',', $tcp_ports_new);
		$tcp_ports_old_array = explode(',', $tcp_ports_old);
		$udp_ports_new_array = explode(',', $udp_ports_new);
		$udp_ports_old_array = explode(',', $udp_ports_old);

		//* add tcp ports
		foreach($tcp_ports_new_array as $port) {
			if(!in_array($port, $tcp_ports_old_array) && $port > 0) {
				$app->system->exec_safe('ufw allow ?', $port.'/tcp');
				$app->log('ufw allow '.$port.'/tcp', LOGLEVEL_DEBUG);
				sleep(1);
			}
		}

		//* remove tcp ports
		foreach($tcp_ports_old_array as $port) {
			if(!in_array($port, $tcp_ports_new_array) && $port > 0) {
				$app->system->exec_safe('ufw delete allow ?', $port.'/tcp');
				$app->log('ufw delete allow '.$port.'/tcp', LOGLEVEL_DEBUG);
				sleep(1);
			}
		}

		//* add udp ports
		foreach($udp_ports_new_array as $port) {
			if(!in_array($port, $udp_ports_old_array) && $port > 0) {
				$app->system->exec_safe('ufw allow ?', $port.'/udp');
				$app->log('ufw allow '.$port.'/udp', LOGLEVEL_DEBUG);
				sleep(1);
			}
		}

		//* remove udp ports
		foreach($udp_ports_old_array as $port) {
			if(!in_array($port, $udp_ports_new_array) && $port > 0) {
				$app->system->exec_safe('ufw delete allow ?', $port.'/udp');
				$app->log('ufw delete allow '.$port.'/udp', LOGLEVEL_DEBUG);
				sleep(1);
			}
		}

		if($data['new']['active'] == 'y') {
			if($data['new']['active'] == $data['old']['active']) {
				exec('ufw reload');
				$app->log('Reloading the firewall', LOGLEVEL_DEBUG);
			} else {
				//* Ensure that bastille firewall is stopped
				exec($conf['init_scripts'] . '/' . 'bastille-firewall stop 2>/dev/null');
				if(@is_file('/etc/debian_version')) exec('update-rc.d -f bastille-firewall remove');

				//* Start ufw firewall
				exec('ufw --force enable');
				$app->log('Starting the firewall', LOGLEVEL_DEBUG);
			}
		} else {
			exec('ufw disable');
			$app->log('Stopping the firewall', LOGLEVEL_DEBUG);
		}
	}

	private function ufw_delete($event_name, $data) {
		global $app, $conf;

		$app->uses('system');

		if(!$app->system->is_installed('ufw')) {
			$app->log('UFW Firewall is not installed', LOGLEVEL_DEBUG);
			return false;
		}

		exec('ufw --force reset');
		exec('ufw disable');
		$app->log('Stopping the firewall', LOGLEVEL_DEBUG);

	}

	private function bastille_update($event_name, $data) {
		global $app, $conf;

		$app->uses('system');

		$tcp_ports = $this->clean_ports($data['new']['tcp_port'], ' ');
		$udp_ports = $this->clean_ports($data['new']['udp_port'], ' ');

		$app->load('tpl');
		$tpl = new tpl();
		$tpl->newTemplate('bastille-firewall.cfg.master');

		$tpl->setVar('TCP_PUBLIC_SERVICES', $tcp_ports);
		$tpl->setVar('UDP_PUBLIC_SERVICES', $udp_ports);

		file_put_contents('/etc/Bastille/bastille-firewall.cfg', $tpl->grab());
		$app->log('Writing firewall configuration /etc/Bastille/bastille-firewall.cfg', LOGLEVEL_DEBUG);
		unset($tpl);

		if($data['new']['active'] == 'y') {
			//* ensure that ufw firewall is disabled in case both firewalls are installed
			if($app->system->is_installed('ufw')) {
				exec('ufw disable');
			}
			exec($conf['init_scripts'] . '/' . 'bastille-firewall restart 2>/dev/null');
			if(@is_file('/etc/debian_version')) exec('update-rc.d bastille-firewall defaults');
			if(@is_file('/sbin/insserv')) exec('insserv -d bastille-firewall');
			$app->log('Restarting the firewall', LOGLEVEL_DEBUG);
		} else {
			exec($conf['init_scripts'] . '/' . 'bastille-firewall stop 2>/dev/null');
			if(@is_file('/etc/debian_version')) exec('update-rc.d -f bastille-firewall remove');
			if(@is_file('/sbin/insserv')) exec('insserv -r -f bastille-firewall');
			$app->log('Stopping the firewall', LOGLEVEL_DEBUG);
		}


	}

	private function bastille_delete($event_name, $data) {
		global $app, $conf;

		exec($conf['init_scripts'] . '/' . 'bastille-firewall stop 2>/dev/null');
		if(@is_file('/etc/debian_version')) exec('update-rc.d -f bastille-firewall remove');
		if(@is_file('/sbin/insserv')) exec('insserv -r -f bastille-firewall');
		$app->log('Stopping the firewall', LOGLEVEL_DEBUG);

	}


	private function clean_ports($portlist, $spacer) {

		$ports = explode(',', $portlist);
		$ports_out = '';

		if(is_array($ports)) {
			foreach($ports as $p) {
				$p_clean = '';
				if(strstr($p, ':')) {
					$p_parts = explode(':', $p);
					$tmp_lower = intval($p_parts[0]);
					$tmp_higher = intval($p_parts[1]);
					if($tmp_lower > 0 && $tmp_lower <= 65535 && $tmp_higher > 0 && $tmp_higher <= 65535 && $tmp_lower < $tmp_higher) {
						$p_clean = $tmp_lower.':'.$tmp_higher;
					}
				} else {
					$tmp = intval($p);
					if($tmp > 0 && $tmp <= 65535) {
						$p_clean = $tmp;
					}
				}
				if($p_clean != '') $ports_out .= $p_clean . $spacer;

			}
		}
		return substr($ports_out, 0, strlen($spacer)*-1);
	}



} // end class

?>
