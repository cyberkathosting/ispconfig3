<?php

/*
Copyright (c) 2009, Till Brehm, projektfarm Gmbh
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

class bind_plugin {
	
	var $plugin_name = 'bind_plugin';
	var $class_name  = 'bind_plugin';
	var $action = 'update';
	
	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;
		
		if(isset($conf['bind']['installed']) && $conf['bind']['installed'] == true) {
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
		
		//* SOA
		$app->plugins->registerEvent('dns_soa_insert',$this->plugin_name,'soa_insert');
		$app->plugins->registerEvent('dns_soa_update',$this->plugin_name,'soa_update');
		$app->plugins->registerEvent('dns_soa_delete',$this->plugin_name,'soa_delete');

    //* SLAVE
		$app->plugins->registerEvent('dns_slave_insert',$this->plugin_name,'slave_insert');
		$app->plugins->registerEvent('dns_slave_update',$this->plugin_name,'slave_update');
		$app->plugins->registerEvent('dns_slave_delete',$this->plugin_name,'slave_delete');
		
		//* RR
		$app->plugins->registerEvent('dns_rr_insert',$this->plugin_name,'rr_insert');
		$app->plugins->registerEvent('dns_rr_update',$this->plugin_name,'rr_update');
		$app->plugins->registerEvent('dns_rr_delete',$this->plugin_name,'rr_delete');
		
	}
	
	
	function soa_insert($event_name,$data) {
		global $app, $conf;
		
		$this->action = 'insert';
		$this->soa_update($event_name,$data);
		
	}
	
	function soa_update($event_name,$data) {
		global $app, $conf;
		
		//* Load libraries
		$app->uses("getconf,tpl");
		
		//* load the server configuration options
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//* Write the domain file
		if(!empty($data['new']['id'])) {
			$tpl = new tpl();
			$tpl->newTemplate("bind_pri.domain.master");
		
			$zone = $data['new'];
			$tpl->setVar($zone);
		
			$records = $app->db->queryAllRecords("SELECT * FROM dns_rr WHERE zone = ".$zone['id']." AND active = 'Y'");
			$tpl->setLoop('zones',$records);
			
			//TODO : change this when distribution information has been integrated into server record
        	if (file_exists('/etc/gentoo-release')) {
        		$filename = escapeshellcmd($dns_config['bind_zonefiles_dir'].'/pri/'.substr($zone['origin'],0,-1));
        	}
        	else {
        		$filename = escapeshellcmd($dns_config['bind_zonefiles_dir'].'/pri.'.substr($zone['origin'],0,-1));
        	}
        	
			$app->log("Writing BIND domain file: ".$filename,LOGLEVEL_DEBUG);
			file_put_contents($filename,$tpl->grab());
			chown($filename, escapeshellcmd($dns_config['bind_user']));
			chgrp($filename, escapeshellcmd($dns_config['bind_group']));
			unset($tpl);
			unset($records);
			unset($records_out);
			unset($zone);
		}
		
		//* rebuild the named.conf file if the origin has changed or when the origin is inserted.
		//if($this->action == 'insert' || $data['old']['origin'] != $data['new']['origin']) {
		$this->write_named_conf($data,$dns_config);
		//}
		
		//* Delete old domain file, if domain name has been changed
		if($data['old']['origin'] != $data['new']['origin']) {
			//TODO : change this when distribution information has been integrated into server record
        	if (file_exists('/etc/gentoo-release')) {
        		$filename = $dns_config['bind_zonefiles_dir'].'/pri/'.substr($data['old']['origin'],0,-1);
        	}
        	else {
        		$filename = $dns_config['bind_zonefiles_dir'].'/pri.'.substr($data['old']['origin'],0,-1);
        	}
			
			if(is_file($filename)) unset($filename);
		}
		
		//* Reload bind nameserver
		$app->services->restartServiceDelayed('bind','reload');
		
	}
	
	function soa_delete($event_name,$data) {
		global $app, $conf;
		
		//* load the server configuration options
		$app->uses("getconf,tpl");
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//* rebuild the named.conf file
		$this->write_named_conf($data,$dns_config);
		
		//* Delete the domain file
		//TODO : change this when distribution information has been integrated into server record
        if (file_exists('/etc/gentoo-release')) {
        	$zone_file_name = $dns_config['bind_zonefiles_dir'].'/pri/'.substr($data['old']['origin'],0,-1);
        }
        else {
        	$zone_file_name = $dns_config['bind_zonefiles_dir'].'/pri.'.substr($data['old']['origin'],0,-1);
        }
		
		if(is_file($zone_file_name)) unlink($zone_file_name);
		$app->log("Deleting BIND domain file: ".$zone_file_name,LOGLEVEL_DEBUG);
		
		//* Reload bind nameserver
		$app->services->restartServiceDelayed('bind','reload');
			
	}

	function slave_insert($event_name,$data) {
		global $app, $conf;
		
		$this->action = 'insert';
		$this->slave_update($event_name,$data);
		
	}
	
	function slave_update($event_name,$data) {
		global $app, $conf;
		
		//* Load libraries
		$app->uses("getconf,tpl");
		
		//* load the server configuration options
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//* rebuild the named.conf file if the origin has changed or when the origin is inserted.
		//if($this->action == 'insert' || $data['old']['origin'] != $data['new']['origin']) {
		$this->write_named_conf($data,$dns_config);
		//}
		
		//* Delete old domain file, if domain name has been changed
		if($data['old']['origin'] != $data['new']['origin']) {
			//TODO : change this when distribution information has been integrated into server record
	        if (file_exists('/etc/gentoo-release')) {
	        	$filename = $dns_config['bind_zonefiles_dir'].'/sec/'.substr($data['old']['origin'],0,-1);
	        }
	        else {
	        	$filename = $dns_config['bind_zonefiles_dir'].'/slave/sec.'.substr($data['old']['origin'],0,-1);
	        }
			
			if(is_file($filename)) unset($filename);
		}
		
		//* Reload bind nameserver
		$app->services->restartServiceDelayed('bind','reload');
     		
	}
	
	function slave_delete($event_name,$data) {
		global $app, $conf;
		
		
		//* load the server configuration options
		$app->uses("getconf,tpl");
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//* rebuild the named.conf file
		$this->write_named_conf($data,$dns_config);
		
		//* Delete the domain file
		//TODO : change this when distribution information has been integrated into server record
	    if (file_exists('/etc/gentoo-release')) {
	    	$zone_file_name = $dns_config['bind_zonefiles_dir'].'/sec/'.substr($data['old']['origin'],0,-1);
	    }
	    else {
	    	$zone_file_name = $dns_config['bind_zonefiles_dir'].'/slave/sec.'.substr($data['old']['origin'],0,-1);
	    }
		
		if(is_file($zone_file_name)) unlink($zone_file_name);
		$app->log("Deleting BIND domain file for secondary zone: ".$zone_file_name,LOGLEVEL_DEBUG);
		
		//* Reload bind nameserver
		$app->services->restartServiceDelayed('bind','reload');
			
		
	}
	
	function rr_insert($event_name,$data) {
		global $app, $conf;
		
		//* Get the data of the soa and call soa_update
		$tmp = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ".$data['new']['zone']);
		$data["new"] = $tmp;
		$data["old"] = $tmp;
		$this->action = 'update';
		$this->soa_update($event_name,$data);

	}
	
	function rr_update($event_name,$data) {
		global $app, $conf;
		
		//* Get the data of the soa and call soa_update
		$tmp = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ".$data['new']['zone']);
		$data["new"] = $tmp;
		$data["old"] = $tmp;
		$this->action = 'update';
		$this->soa_update($event_name,$data);
		
	}
	
	function rr_delete($event_name,$data) {
		global $app, $conf;
		
		//* Get the data of the soa and call soa_update
		$tmp = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ".intval($data['old']['zone']));
		$data["new"] = $tmp;
		$data["old"] = $tmp;
		$this->action = 'update';
		$this->soa_update($event_name,$data);
		
	}
	
	###################################################################
	
	function write_named_conf($data, $dns_config) {
		global $app, $conf;
	
		//* Only write the master file for the current server	
		$tmps = $app->db->queryAllRecords("SELECT origin, xfer, also_notify FROM dns_soa WHERE active = 'Y' AND server_id=".$conf["server_id"]);
		$zones = array();
		
		//* Check if the current zone that triggered this function has at least one NS record
		$rec_num = $app->db->queryOneRecord("SELECT count(id) as ns FROM dns_rr WHERE type = 'NS' AND zone = ".intval($data['new']['id'])." AND active = 'Y'");
		if($rec_num['ns'] == 0) {
			$exclude_zone = $data['new']['origin'];
		} else {
			$exclude_zone = '';
		}
		
		//TODO : change this when distribution information has been integrated into server record
	    if (file_exists('/etc/gentoo-release')) {
	    	$pri_zonefiles_path = $dns_config['bind_zonefiles_dir'].'/pri/';
	    	$sec_zonefiles_path = $dns_config['bind_zonefiles_dir'].'/sec/';
	    	
	    }
	    else {
	    	$pri_zonefiles_path = $dns_config['bind_zonefiles_dir'].'/pri.';
	    	$sec_zonefiles_path = $dns_config['bind_zonefiles_dir'].'/slave/sec.';
	    }

		//* Loop trough zones
		foreach($tmps as $tmp) {
			
			$options = '';
			if(trim($tmp['xfer']) != '') {
				$options .= "        allow-transfer {".str_replace(',',';',$tmp['xfer']).";};\n";
			} else {
				$options .= "        allow-transfer {none;};\n";
			}
			if(trim($tmp['also_notify']) != '') $options .= '        also-notify {'.str_replace(',',';',$tmp['also_notify']).";};\n";
			
			if($tmp['origin'] != $exclude_zone) {
				$zones[] = array(	'zone' => substr($tmp['origin'],0,-1),
									'zonefile_path' => $pri_zonefiles_path.substr($tmp['origin'],0,-1),
									'options' => $options
								);
			}
		}

		$tpl = new tpl();
		$tpl->newTemplate("bind_named.conf.local.master");
		$tpl->setLoop('zones',$zones);
		
		//* And loop through the secondary zones, but only for the current server
		$tmps_sec = $app->db->queryAllRecords("SELECT origin, xfer, ns FROM dns_slave WHERE active = 'Y' AND server_id=".$conf["server_id"]);
		$zones_sec = array();

		foreach($tmps_sec as $tmp) {
			
			$options = "        masters {".$tmp['ns'].";};\n";
            if(trim($tmp['xfer']) != '') {
                $options .= "        allow-transfer {".str_replace(',',';',$tmp['xfer']).";};\n";
            } else {
                $options .= "        allow-transfer {none;};\n";
            }

			
			$zones_sec[] = array(	'zone' => substr($tmp['origin'],0,-1),
									'zonefile_path' => $sec_zonefiles_path.substr($tmp['origin'],0,-1),
									'options' => $options
								);

//			$filename = escapeshellcmd($dns_config['bind_zonefiles_dir'].'/slave/sec.'.substr($tmp['origin'],0,-1));
//			$app->log("Writing BIND domain file: ".$filename,LOGLEVEL_DEBUG);

					
		}
		
		$tpl_sec = new tpl();
		$tpl_sec->newTemplate("bind_named.conf.local.slave");
		$tpl_sec->setLoop('zones',$zones_sec); 
    		
		file_put_contents($dns_config['named_conf_local_path'],$tpl->grab()."\n".$tpl_sec->grab()); 
		$app->log("Writing BIND named.conf.local file: ".$dns_config['named_conf_local_path'],LOGLEVEL_DEBUG);

 		unset($tpl_sec); 
		unset($zones_sec); 
		unset($tmps_sec);  
		unset($tpl);
		unset($zones);
		unset($tmps);
		
	}
	
	
	

} // end class

?>
