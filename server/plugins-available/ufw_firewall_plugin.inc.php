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

class ufw_firewall_plugin {
	
	var $plugin_name = 'ufw_firewall_plugin';
	var $class_name  = 'ufw_firewall_plugin';
	
	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;
		if ($conf['ufw']['installed'] == true && $conf['services']['firewall'] == true) {
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
		
		$app->plugins->registerEvent('firewall_insert',$this->plugin_name,'insert_basic');
		$app->plugins->registerEvent('firewall_update',$this->plugin_name,'update_basic');
		$app->plugins->registerEvent('firewall_delete',$this->plugin_name,'update_basic');
	
		$app->plugins->registerEvent('firewall_forward_insert',$this->plugin_name,'insert_forward');
		$app->plugins->registerEvent('firewall_forward_update',$this->plugin_name,'update_forward');
		$app->plugins->registerEvent('firewall_forward_delete',$this->plugin_name,'update_forward');
		
		$app->plugins->registerEvent('firewall_filter_insert',$this->plugin_name,'insert_filter');
		$app->plugins->registerEvent('firewall_filter_update',$this->plugin_name,'update_filter');
		$app->plugins->registerEvent('firewall_filter_delete',$this->plugin_name,'delete_filter');
		
		$app->plugins->registerEvent('server_insert',$this->plugin_name,'updateSettings');
		$app->plugins->registerEvent('server_update',$this->plugin_name,'updateSettings');
		
		
		
	}
	
	
	function insert_basic($event_name,$data) {
		global $app, $conf;
		
		$this->update_basic($event_name,$data);
		
	}
	
	function update_basic($event_name,$data) {
		global $app, $conf;
		
		$tcp_ports = '';
		$udp_ports = '';
		
		$delete_rule = ($data["new"]["active"] == "n" ? "delete " : "");
		/*
		$ports = explode(',',$data["new"]["tcp_port"]);
		if(is_array($ports)) {
			foreach($ports as $p) {
				if(strstr($p,':')) {
					$p_parts = explode(':',$p);
					$p_clean = intval($p_parts[0]).':'.intval($p_parts[1]);
				} else {
					$p_clean = intval($p);
				}
				
				//system("ufw $delete_rule allow " . $p_clean . "/tcp");

			}
		}*/
		
		system("ufw $delete_rule allow out" . $data["new"]["tcp_port"] . "/tcp");
		system("ufw $delete_rule allow in" . $data["new"]["tcp_port"] . "/tcp");
		system("ufw $delete_rule allow out" . $data["new"]["udp_port"] . "/udp");
		system("ufw $delete_rule allow in" . $data["new"]["udp_port"] . "/udp");
		//$tcp_ports = trim($tcp_ports);
		/*
		$ports = explode(',',$data["new"]["udp_port"]);
		if(is_array($ports)) {
			foreach($ports as $p) {
				if(strstr($p,':')) {
					$p_parts = explode(':',$p);
					$p_clean = intval($p_parts[0]).':'.intval($p_parts[1]);
				} else {
					$p_clean = intval($p);
				}

				
			}
		}
		
		system("ufw $delete_rule allow " . $p_clean . "/udp");
		*/
		
		if($data["new"]["active"] == 'y') {
			exec('/etc/init.d/ufw force-reload');
			$app->log('Restarting the firewall',LOGLEVEL_DEBUG);
		} else {
			exec('/etc/init.d/ufw force-reload');
			$app->log('Flushing the firewall',LOGLEVEL_DEBUG);
		}
		
		
	}
	
	
	function insert_filter($event_name,$data) {
		global $app, $conf;
		
		$this->update_filter($event_name,$data);
		
	}
	
	function getCidr($mask) {
		$long = ip2long($mask);
 		$base = ip2long('255.255.255.255');
  		return 32-log(($long ^ $base)+1,2);
	}
	
	function update_filter($event_name,$data) {
		global $app, $conf;
		
		$app->uses("getconf");
		$server_config = $app->getconf->get_server_config($conf["server_id"], 'ufw');
			$network = $server_config["ufw_network"];
		
		$records = $app->db->queryAllRecords(
			"SELECT 
					 protocol,
					 IF 
					 	(src_from_port=src_to_port, src_from_port, CONCAT(src_from_port, ':',src_to_port)) 
					 AS 
					 	src_port,
					 IF 
					 	(dst_from_port=dst_to_port, dst_from_port, CONCAT(dst_from_port, ':',dst_to_port)) 
					 AS 
					 	dst_port, 
					 IF
					 	(src_ip='0.0.0.0','any',src_ip) 
					 AS 
					 	src_ip, 
					 IF
					 	(dst_ip='0.0.0.0','any',dst_ip) 
					 AS 
					 	dst_ip, 
					 src_netmask, 
					 dst_netmask, 
					 inbound_policy,
					 outbound_policy,
					 rule_id,
					 active
			FROM 
				firewall_filter 
			ORDER BY
				rule_id
			ASC");
		
		$commands = array();
		foreach ($records as $record) {
			$src_netmask = $this->getCidr($record["src_netmask"]);
			$dst_netmask = $this->getCidr($record["dst_netmask"]);
			$src_port = ($record["src_port"] == "0:65535" ? "" : " port " .$record["src_port"]);
			$dst_port = ($record["dst_port"] == "0:65535" ? "" : " port " . $record["dst_port"]);
			$src_ip = ($record["src_ip"] == "any" ? "any" : $record["src_ip"] . "/" . $src_netmask);
			$dst_ip = ($record["dst_ip"] == "any" ? "any" : $record["dst_ip"] . "/" . $dst_netmask);
			//$protocol = $record["protocol"];
			$delete = ($record["active"] == "n" ? true : false);
			//$protocols = array_split($record["protocol"]);
			//$inbound = ($record["inbound"] == 0 ? "deny " : "allow");
			//$outbound = ($record["outbound"] == 0 ? "deny out" : "allow out");
			
			//foreach ($protocols as $protocol) {
				
				
				$ufw = new UFW();
				//$ufw->setDelete($record["active"] == "n");
				$ufw->setRuleID($record["rule_id"]);
				$ufw->setSrcIP($src_ip);
				$ufw->setDstIP($dst_ip);
				$ufw->setSrcPort($src_port);
				$ufw->setDstPort($dst_port);
				$ufw->setInboundPolicy($record["inbound_policy"]);
				$ufw->setOutboundPolicy($record["outbound_policy"]);
				$ufw->setProtocol($record["protocol"]);
				$ufw->setNetwork($network);
				
				if ($delete) {
					$ufw->delete();
				} else {
					$ufw->insert();
				}				
			//}
			
			/*
			if ($record["active"] == 'n') {
				$inbound = ($record["inbound"] == 0 ? "deny " : "allow");
				$outbound = ($record["outbound"] == 0 ? "deny out" : "allow out");
				array_push($commands, "ufw deny proto udp from $src_ip $src_port to $dst_ip $dst_port");
				if ($protocol == "tcp/udp") {
					array_push($commands, "ufw delete $inbound proto udp from $src_ip $src_port to $dst_ip $dst_port");
					array_push($commands, "ufw delete $outbound proto udp from $src_ip $src_port to $dst_ip $dst_port");
					array_push($commands, "ufw delete $inbound proto tcp from $src_ip $src_port to $dst_ip $dst_port");
					array_push($commands, "ufw delete $outbound proto tcp from $src_ip $src_port to $dst_ip $dst_port");
				} else {
					array_push($commands, "ufw delete $inbound proto $protocol from $src_ip $src_port to $dst_ip $dst_port");
					array_push($commands, "ufw delete $outbound proto $protocol from $src_ip $src_port to $dst_ip $dst_port");
				}
			} elseif ($record["inbound"] == 0) {
				if ($protocol == "tcp/udp") {
					array_push($commands, "ufw deny proto udp from $src_ip $src_port to $dst_ip $dst_port");
					array_push($commands, "ufw deny proto tcp from $src_ip $src_port to $dst_ip $dst_port");
				} else {
					array_push($commands, "ufw deny proto $protocol from $src_ip $src_port to $dst_ip $dst_port");
				}

			} elseif ($record["outbound"] == 0) {
				if ($protocol == "tcp/udp") {
					array_push($commands, "ufw deny out proto udp from $network to any $dst_port");
					array_push($commands, "ufw deny out proto tcp from $network to any $dst_port");
				} else {
					array_push($commands, "ufw deny out proto $protocol from $network to any $dst_port");
				}
			}*/
			
			
		}
		
		/*
		
		$records = $app->db->queryAllRecords(
			"SELECT 
					 protocol,
					 IF 
					 	(src_from_port=src_to_port, src_from_port, CONCAT(src_from_port, ':',src_to_port)) 
					 AS 
					 	src_port,
					 IF 
					 	(dst_from_port=dst_to_port, dst_from_port, CONCAT(dst_from_port, ':',dst_to_port)) 
					 AS 
					 	dst_port, 
					 IF
					 	(src_ip='0.0.0.0','any',src_ip) 
					 AS 
					 	src_ip, 
					 IF
					 	(dst_ip='0.0.0.0','any',dst_ip) 
					 AS 
					 	dst_ip, 
					 src_netmask, 
					 dst_netmask, 
					 inbound,
					 outbound,
					 active
			FROM 
				firewall_filter 
			WHERE 
				inbound=1
			OR 
				outbound=1 
			AND 
				active='y'");	
		
	
		foreach ($records as $record) {
			$src_netmask = $this->getCidr($record["src_netmask"]);
			$dst_netmask = $this->getCidr($record["dst_netmask"]);
			$src_port = ($record["src_port"] == "0:65535" ? "" : " port " .$record["src_port"]);
			$dst_port = ($record["dst_port"] == "0:65535" ? "" : " port " . $record["dst_port"]);
			$src_ip = ($record["src_ip"] == "any" ? "any" : $record["src_ip"] . "/" . $src_netmask);
			$dst_ip = ($record["dst_ip"] == "any" ? "any" : $record["dst_ip"] . "/" . $dst_netmask);
			$protocol = $record["protocol"];
			$outbound = ($record["outbound"] == 1 ? "out" : "");
			
			
		
			if ($record["inbound"] == 1) {
				if ($protocol == "tcp/udp") {
					array_push($commands, "ufw allow proto udp from $src_ip $src_port to $dst_ip $dst_port");
					array_push($commands, "ufw allow proto tcp from $src_ip $src_port to $dst_ip $dst_port");
				} else {
					array_push($commands, "ufw allow proto $protocol from $src_ip $src_port to $dst_ip $dst_port");
				}

			} elseif ($record["outbound"] == 1) {
				if ($protocol == "tcp/udp") {
					array_push($commands, "ufw allow out proto udp from $network to any $dst_port");
					array_push($commands, "ufw allow out proto tcp from $network to any $dst_port");
				} else {
					array_push($commands, "ufw allow out proto $protocol from $network to any $dst_port");
				}
			}
			
			
		}
		
		foreach ($commands as $command) {
			system($command);
		}
		*/
	}
	
	function insert_forward($event_name,$data) {
		global $app, $conf;
		
		$this->update_filter($event_name,$data);
		
	}
	
	function update_forward($event_name,$data) {
		global $app, $conf;
		
		
		
	}
	
	//update server config
	
	function backupConfigs()
	{
		copy('/etc/default/ufw','/etc/default/ufw~');
		copy('/etc/ufw/ufw.conf','/etc/ufw/ufw.conf~');
		copy('/etc/ufw/before.rules','/etc/ufw/before.rules~');
	}
	
	function updateSettings($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses("getconf");
		$server_config = $app->getconf->get_server_config($conf["server_id"], 'ufw');
		

		if(is_dir('/etc/ufw') && is_file('/etc/default/ufw')) {
			$this->backupConfigs();
			
			$app->load('tpl');
			
			$ufw_tpl = new tpl();
			$ufw_tpl->newTemplate("ufw.conf.master");
				
			$ufw_tpl->setVar('enable',($server_config["ufw_enable"] == "" ? "no" : $server_config["ufw_enable"]));
			$ufw_tpl->setVar('log_level',$server_config["ufw_log_level"]);
			
			
			file_put_contents('/etc/ufw/ufw.conf',$ufw_tpl->grab());
			unset($ufw_tpl);
			
			$app->log("Changed UFW settings",LOGLEVEL_DEBUG);
			
			$ufw_tpl = new tpl();
			$ufw_tpl->newTemplate("ufw.default.master");
			
			$ufw_tpl->setVar('ipv6',$server_config["ufw_ipv6"] == "" ? "no" : $server_config["ufw_ipv6"]);
			$ufw_tpl->setVar('default_input_policy',$server_config["ufw_default_input_policy"]);
			$ufw_tpl->setVar('default_output_policy',$server_config["ufw_default_output_policy"]);
			$ufw_tpl->setVar('default_forward_policy',$server_config["ufw_default_forward_policy"]);
			$ufw_tpl->setVar('default_application_policy',$server_config["ufw_default_application_policy"]);
			$ufw_tpl->setVar('manage_builtins',$server_config["ufw_manage_builtins"] == "" ? "no" : $server_config["ufw_manage_builtins"]);
			
			file_put_contents('/etc/default/ufw',$ufw_tpl->grab());
			unset($ufw_tpl);
			
			$app->log("Changed default UFW settings",LOGLEVEL_DEBUG);
			
			$app->services->restartServiceDelayed('ufw','--force-reload');
			
		} else {
			$app->log("Ubuntu  Uncomplicated Firewall configuration not available for this linux distribution.",LOGLEVEL_DEBUG);
		}
		
	}
	
	
	

} // end class

class UFW {
		
	var $_delete = false;
	var $_ufwCmd = "ufw";
	var $_inboundPolicy = "allow";
	var $_outboundPolicy = "allow";
	var $_protocol = "tcp";
	var $_ruleID = 1;
	var $_srcIP;
	var $_dstIP;
	var $_srcPort;
	var $_dstPort;
	var $_network = "0.0.0.0/24";
	
	function UFW() {
		
	}
	
	function setDelete($delete) {
		$this->_delete = $delete;
	}
	
	function setInboundPolicy($policy) {
		$this->_inboundPolicy = $policy;
	}
	
	function setOutboundPolicy($policy) {
		$this->_outboundPolicy = $policy;
	}
	
	function setProtocol($protocol) {
		$this->_outboundPolicy = $protocol;
	}
	
	function setRuleID($id) {
		$this->_ruleID = $id;
	}
	
	function setSrcIP($ip) {
		$this->_srcIP = $ip;	
	}
	
	function setDstIP($ip) {
		$this->_dstIP = $ip;
	}
	
	function setSrcPort($port) {
		$this->_srcPort = $port;
	}
	
	function setDstPort($port) {
		$this->_dstPort = $port;
	}
	
	function setNetwork($network) {
		$this->_network = $network;
	}
	
	
	function insert() {
		$protocols = split("/",$this->_protocol);
		foreach ($protocols as $protocol) {
			$inbound = sprintf("ufw insert %s %s proto %s from %s port %s to %s port %s ", $this->_ruleID, $this->_inboundPolicy, $protocol, $this->_srcIP, $this->_srcPort, $this->_dstIP, $this->_dstPort);
			$outbound = sprintf("ufw insert %s %s proto %s from %s port %s to %s port %s ", $this->_ruleID, $this->_outboundPolicy, $protocol, $this->_network, $this->_srcPort, $this->_dstIP, $this->_dstPort);
			
			echo $inbound."\n";
			echo $outbound."\n";
			system($inbound);
			system($outbound);
		}
	}
	
	function delete() {
		$protocols = split("/",$this->_protocol);
		foreach ($protocols as $protocol) {
			$inbound = sprintf("ufw delete %s proto %s from %s port %s to %s port %s ", $this->_ruleID, $this->_inboundPolicy, $protocol, $this->_srcIP, $this->_srcPort, $this->_dstIP, $this->_dstPort);
			$outbound = sprintf("ufw delete %s proto %s from %s port %s to %s port %s ", $this->_ruleID, $this->_outboundPolicy, $protocol, $this->_network, $this->_srcPort, $this->_dstIP, $this->_dstPort);
			
			echo $inbound."\n";
			echo $outbound."\n";
			
			system($inbound);
			system($outbound);
		}
	}
		
}

?>
