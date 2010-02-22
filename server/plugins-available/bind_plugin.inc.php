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
		
			$filename = escapeshellcmd($dns_config['bind_zonefiles_dir'].'/pri.'.substr($zone['origin'],0,-1));
			$app->log("Writing BIND domain file: ".$filename,LOGLEVEL_DEBUG);
			file_put_contents($filename,$tpl->grab());
			exec('chown '.escapeshellcmd($dns_config['bind_user']).':'.escapeshellcmd($dns_config['bind_group']).' '.$filename);
			unset($tpl);
			unset($records);
			unset($zone);
		}
		
		//* rebuild the named.conf file if the origin has changed or when the origin is inserted.
		//if($this->action == 'insert' || $data['old']['origin'] != $data['new']['origin']) {
		$this->write_named_conf($data,$dns_config);
		//}
		
		//* Delete old domain file, if domain name has been changed
		if($data['old']['origin'] != $data['new']['origin']) {
			$filename = $dns_config['bind_zonefiles_dir'].'/pri.'.substr($data['old']['origin'],0,-1);
			if(is_file($filename)) unset($filename);
		}
		
		//* Reload bind nameserver
		$app->services->restartServiceDelayed('bind','reload');
		
	}
	
	function soa_delete($event_name,$data) {
		global $app, $conf;
		
		//* load the server configuration options
		$app->uses("getconf");
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//* rebuild the named.conf file
		$this->write_named_conf($data,$dns_config);
		
		//* Delete the domain file
		$filename = $dns_config['bind_zonefiles_dir'].'/pri.'.substr($data['old']['origin'],0,-1);
		if(is_file($filename)) unset($filename);
		$app->log("Deleting BIND domain file: ".$filename,LOGLEVEL_DEBUG);
		
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
		$tmp = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ".$data['old']['zone']);
		$data["new"] = $tmp;
		$data["old"] = $tmp;
		$this->action = 'update';
		$this->soa_update($event_name,$data);
		
	}
	
	###################################################################
	
	function write_named_conf($data, $dns_config) {
		global $app, $conf;
		
		$tmps = $app->db->queryAllRecords("SELECT origin FROM dns_soa WHERE active = 'Y'");
		$zones = array();
		foreach($tmps as $tmp) {
			$zones[] = array(	'zone' => substr($tmp['origin'],0,-1),
								'zonefile_path' => $dns_config['bind_zonefiles_dir'].'/pri.'.substr($tmp['origin'],0,-1)
							);
		}
		
		$tpl = new tpl();
		$tpl->newTemplate("bind_named.conf.local.master");
		$tpl->setLoop('zones',$zones);
		
		file_put_contents($dns_config['named_conf_local_path'],$tpl->grab());
		$app->log("Writing BIND named.conf.local file: ".$dns_config['named_conf_local_path'],LOGLEVEL_DEBUG);
		
		unset($tpl);
		unset($zones);
		unset($tmps);
		
	}
	
	
	

} // end class

?>