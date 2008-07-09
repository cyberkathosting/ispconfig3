<?php

/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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
	
	var $plugin_name = 'firewall_plugin';
	var $class_name  = 'firewall_plugin';
	
		
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		//* Mailboxes
		$app->plugins->registerEvent('firewall_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('firewall_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('firewall_delete',$this->plugin_name,'delete');
		
		
	}
	
	
	function insert($event_name,$data) {
		global $app, $conf;
		
		$this->update($event_name,$data);
		
	}
	
	function update($event_name,$data) {
		global $app, $conf;
		
		$tcp_ports = '';
		$udp_ports = '';
		
		$ports = explode(',',$data["new"]["tcp_port"]);
		if(is_array($ports)) {
			foreach($ports as $p) {
				$p_int = intval($p);
				if($p_int > 0) $tcp_ports .= $p_int . ' ';
			}
		}
		$tcp_ports = trim($tcp_ports);
		
		$ports = explode(',',$data["new"]["udp_port"]);
		if(is_array($ports)) {
			foreach($ports as $p) {
				$p_int = intval($p);
				if($p_int > 0) $udp_ports .= $p_int . ' ';
			}
		}
		$udp_ports = trim($udp_ports);
		
		$app->load('tpl');
		$tpl = new tpl();
		$tpl->newTemplate("bastille-firewall.cfg.master");
		
		$tpl->setVar("TCP_PUBLIC_SERVICES",$tcp_ports);
		$tpl->setVar("UDP_PUBLIC_SERVICES",$udp_ports);
		
		file_put_contents('/etc/Bastille/bastille-firewall.cfg',$tpl->grab());
		$app->log('Writing firewall configuration /etc/Bastille/bastille-firewall.cfg',LOGLEVEL_DEBUG);
		unset($tpl);
		
		if($data["new"]["active"] == 'y') {
			exec('/etc/init.d/bastille-firewall restart');
			if(@is_file('/etc/debian_version')) exec('update-rc.d bastille-firewall defaults');
			$app->log('Restarting the firewall',LOGLEVEL_DEBUG);
		} else {
			exec('/etc/init.d/bastille-firewall stop');
			if(@is_file('/etc/debian_version')) exec('update-rc.d bastille-firewall remove');
			$app->log('Stopping the firewall',LOGLEVEL_DEBUG);
		}
		
		
	}
	
	function delete($event_name,$data) {
		global $app, $conf;
		
		exec('/etc/init.d/bastille-firewall stop');
		if(@is_file('/etc/debian_version')) exec('update-rc.d bastille-firewall remove');
		$app->log('Stopping the firewall',LOGLEVEL_DEBUG);
		
	}
	
	
	

} // end class

?>