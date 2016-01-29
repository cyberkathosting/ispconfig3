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

		if(isset($conf['bind']['installed']) && $conf['bind']['installed'] == true && @is_link('/usr/local/ispconfig/server/mods-enabled/dns_module.inc.php')) {
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
		$app->plugins->registerEvent('dns_soa_insert', $this->plugin_name, 'soa_insert');
		$app->plugins->registerEvent('dns_soa_update', $this->plugin_name, 'soa_update');
		$app->plugins->registerEvent('dns_soa_delete', $this->plugin_name, 'soa_delete');

		//* SLAVE
		$app->plugins->registerEvent('dns_slave_insert', $this->plugin_name, 'slave_insert');
		$app->plugins->registerEvent('dns_slave_update', $this->plugin_name, 'slave_update');
		$app->plugins->registerEvent('dns_slave_delete', $this->plugin_name, 'slave_delete');

		//* RR
		$app->plugins->registerEvent('dns_rr_insert', $this->plugin_name, 'rr_insert');
		$app->plugins->registerEvent('dns_rr_update', $this->plugin_name, 'rr_update');
		$app->plugins->registerEvent('dns_rr_delete', $this->plugin_name, 'rr_delete');

	}

	//* This creates DNSSEC-Keys and calls soa_dnssec_update.
	function soa_dnssec_create(&$data) {
		global $app, $conf;

		//* Load libraries
		$app->uses("getconf,tpl");

		//* load the server configuration options
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//TODO : change this when distribution information has been integrated into server record
		$filespre = (file_exists('/etc/gentoo-release')) ? 'pri/' : 'pri.';
		
		$domain = substr($data['new']['origin'], 0, strlen($data['new']['origin'])-1);
		if (!file_exists($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain)) return false;
		
		//* Check Entropy
		if (file_get_contents('/proc/sys/kernel/random/entropy_avail') < 400) {
			$app->log('DNSSEC ERROR: We are low on entropy. Not generating new Keys for '.$domain.'. Please consider installing package haveged.', LOGLEVEL_WARN);
			return false;
		}
		
		//* Verify that we do not already have keys (overwriting-protection)
		if (file_exists($dns_config['bind_zonefiles_dir'].'/dsset-'.$domain.'.')) {
			return $this->soa_dnssec_update($data);
		} else if ($data['new']['dnssec_initialized'] == 'Y') { //In case that we generated keys but the dsset-file was not generated
			$keycount=0;
			foreach (glob($dns_config['bind_zonefiles_dir'].'/K'.$domain.'*.key') as $keyfile) {
				$keycount++;
			}
			if ($keycount > 0) {
				$this->soa_dnssec_sign($data);
				return true;
			}
		}
		
		//Do some magic...
		exec('cd '.escapeshellcmd($dns_config['bind_zonefiles_dir']).';'.
		'dnssec-keygen -a NSEC3RSASHA1 -b 2048 -n ZONE '.escapeshellcmd($domain).';'.
		'dnssec-keygen -f KSK -a NSEC3RSASHA1 -b 4096 -n ZONE '.escapeshellcmd($domain));

		$this->soa_dnssec_sign($data); //Now sign the zone for the first time
		$data['new']['dnssec_initialized']='Y';
	}
	
	function soa_dnssec_sign(&$data) {
		global $app, $conf;
		
		//* Load libraries
		$app->uses("getconf,tpl");

		//* load the server configuration options
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//TODO : change this when distribution information has been integrated into server record
		$filespre = (file_exists('/etc/gentoo-release')) ? 'pri/' : 'pri.';
		
		$domain = substr($data['new']['origin'], 0, strlen($data['new']['origin'])-1);
		if (!file_exists($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain)) return false;
		
		$zonefile = file_get_contents($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain);
		$keycount=0;
		foreach (glob($dns_config['bind_zonefiles_dir'].'/K'.$domain.'*.key') as $keyfile) {
			$includeline = '$INCLUDE '.basename($keyfile);
			if (!preg_match('@'.preg_quote($includeline).'@', $zonefile)) $zonefile .= "\n".$includeline."\n";
			$keycount++;
		}
		if ($keycount != 2) $app->log('DNSSEC Warning: There are more or less than 2 keyfiles for zone '.$domain, LOGLEVEL_WARN);
		file_put_contents($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain, $zonefile);
		
		//Sign the zone and set it valid for max. 16 days
		exec('cd '.escapeshellcmd($dns_config['bind_zonefiles_dir']).';'.
			 'dnssec-signzone -A -e +1382400 -3 $(head -c 1000 /dev/random | sha1sum | cut -b 1-16) -N increment -o '.escapeshellcmd($domain).' -t '.$filespre.escapeshellcmd($domain));
			 
		//Write Data back ino DB
		$dnssecdata = "DS-Records:\n".file_get_contents($dns_config['bind_zonefiles_dir'].'/dsset-'.$domain.'.');
		$dnssecdata .= "\n------------------------------------\n\nDNSKEY-Records:\n";
		foreach (glob($dns_config['bind_zonefiles_dir'].'/K'.$domain.'*.key') as $keyfile) {
			$dnssecdata .= file_get_contents($keyfile)."\n\n";
		}
		
		$app->db->query('UPDATE dns_soa SET dnssec_info=\''.$dnssecdata.'\', dnssec_initialized=\'Y\', dnssec_last_signed=\''.time().'\' WHERE id='.$data['new']['id']);
	}
	
	function soa_dnssec_update(&$data, $new=false) {
		global $app, $conf;

		//* Load libraries
		$app->uses("getconf,tpl");

		//* load the server configuration options
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//TODO : change this when distribution information has been integrated into server record
		$filespre = (file_exists('/etc/gentoo-release')) ? 'pri/' : 'pri.';
		
		$domain = substr($data['new']['origin'], 0, strlen($data['new']['origin'])-1);
		if (!file_exists($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain)) return false;
		
		//* Check for available entropy
		if (file_get_contents('/proc/sys/kernel/random/entropy_avail') < 200) {
			$app->log('DNSSEC ERROR: We are low on entropy. This could cause server script to fail. Please consider installing package haveged.', LOGLEVEL_ERR);
			return false;
		}
		
		if (!$new && !file_exists($dns_config['bind_zonefiles_dir'].'/dsset-'.$domain.'.')) $this->soa_dnssec_create($data);
		
		$dbdata = $app->db->queryOneRecord('SELECT id,serial FROM dns_soa WHERE id='.$data['new']['id']);
		exec('cd '.escapeshellcmd($dns_config['bind_zonefiles_dir']).';'.
			 'named-checkzone '.escapeshellcmd($domain).' '.escapeshellcmd($dns_config['bind_zonefiles_dir']).'/'.$filespre.escapeshellcmd($domain).' | egrep -ho \'[0-9]{10}\'', $serial, $retState);
		if ($retState != 0) {
			$app->log('DNSSEC Error: Error in Zonefile for '.$domain, LOGLEVEL_ERR);
			return false;
		}
		
		$this->soa_dnssec_sign($data);
	}
	
	function soa_dnssec_delete(&$data) {
		global $app, $conf;

		//* Load libraries
		$app->uses("getconf,tpl");

		//* load the server configuration options
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');
		
		//TODO : change this when distribution information has been integrated into server record
		$filespre = (file_exists('/etc/gentoo-release')) ? 'pri/' : 'pri.';
		
		$domain = substr($data['new']['origin'], 0, strlen($data['new']['origin'])-1);
		
		unlink($dns_config['bind_zonefiles_dir'].'/K'.$domain.'.+*');
		unlink($dns_config['bind_zonefiles_dir'].'/'.$filespre.$domain.'.signed');
		unlink($dns_config['bind_zonefiles_dir'].'/dsset-'.$domain.'.');
		
		$app->db->query('UPDATE dns_soa SET dnssec_info=\'\', dnssec_initialized=\'N\' WHERE id='.$data['new']['id']);
	}

	function soa_insert($event_name, $data) {
		global $app, $conf;

		$this->action = 'insert';
		$this->soa_update($event_name, $data);

	}

	function soa_update($event_name, $data) {
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

			$records = $app->db->queryAllRecords("SELECT * FROM dns_rr WHERE zone = ? AND active = 'Y'", $zone['id']);
			if(is_array($records) && !empty($records)){
				for($i=0;$i<sizeof($records);$i++){
					if($records[$i]['ttl'] == 0) $records[$i]['ttl'] = '';
					if($records[$i]['name'] == '') $records[$i]['name'] = '@';
					//* Split TXT records, if nescessary
					if($records[$i]['type'] == 'TXT' && strlen($records[$i]['data']) > 255) {
						$records[$i]['data'] = implode('" "',str_split( $records[$i]['data'], 255));
					}
				}
			}
			$tpl->setLoop('zones', $records);

			//TODO : change this when distribution information has been integrated into server record
			if (file_exists('/etc/gentoo-release')) {
				$filename = escapeshellcmd($dns_config['bind_zonefiles_dir'].'/pri/'.str_replace("/", "_", substr($zone['origin'], 0, -1)));
			}
			else {
				$filename = escapeshellcmd($dns_config['bind_zonefiles_dir'].'/pri.'.str_replace("/", "_", substr($zone['origin'], 0, -1)));
			}

			file_put_contents($filename, $tpl->grab());
			chown($filename, escapeshellcmd($dns_config['bind_user']));
			chgrp($filename, escapeshellcmd($dns_config['bind_group']));

			//* Check the zonefile
			if(is_file($filename.'.err')) unlink($filename.'.err');
			exec('named-checkzone '.escapeshellarg($zone['origin']).' '.escapeshellarg($filename), $out, $return_status);
			if($return_status === 0) {
				$app->log("Writing BIND domain file: ".$filename, LOGLEVEL_DEBUG);
			} else {
				if($dns_config['disable_bind_log'] === 'y') {
					$app->log("Writing BIND domain file failed: ".$filename." ".implode(' ', $out), LOGLEVEL_DEBUG);
				} else {
					$app->log("Writing BIND domain file failed: ".$filename." ".implode(' ', $out), LOGLEVEL_WARN);
				}
				rename($filename, $filename.'.err');
			}
			unset($tpl);
			unset($records);
			unset($records_out);
			unset($zone);
		}
		
		//* DNSSEC-Implementation
		if($data['old']['origin'] != $data['new']['origin']) {			
			if (@$data['old']['dnssec_initialized'] == 'Y' && strlen(@$data['old']['origin']) > 3) $this->soa_dnssec_delete($data); //delete old keys
			if ($data['new']['dnssec_wanted'] == 'Y') $this->soa_dnssec_create($data);
		}
		else if ($data['new']['dnssec_wanted'] == 'Y' && $data['old']['dnssec_initialized'] == 'N') $this->soa_dnssec_create($data);
		else if ($data['new']['dnssec_wanted'] == 'N' && $data['old']['dnssec_initialized'] == 'Y') {	//delete old signed file if dnssec is no longer wanted
			//TODO : change this when distribution information has been integrated into server record
			if (file_exists('/etc/gentoo-release')) {
				$filename = $dns_config['bind_zonefiles_dir'].'/pri/'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
			}
			else {
				$filename = $dns_config['bind_zonefiles_dir'].'/pri.'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
			}
			if(is_file($filename.'.signed')) unlink($filename.'.signed');
 		} else if ($data['new']['dnssec_wanted'] == 'Y') $this->soa_dnssec_update($data);
		// END DNSSEC
		
		//* rebuild the named.conf file if the origin has changed or when the origin is inserted.
		//if($this->action == 'insert' || $data['old']['origin'] != $data['new']['origin']) {
		$this->write_named_conf($data, $dns_config);
		//}

		//* Delete old domain file, if domain name has been changed
		if($data['old']['origin'] != $data['new']['origin']) {
			//TODO : change this when distribution information has been integrated into server record
			if (file_exists('/etc/gentoo-release')) {
				$filename = $dns_config['bind_zonefiles_dir'].'/pri/'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
			}
			else {
				$filename = $dns_config['bind_zonefiles_dir'].'/pri.'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
			}

			if(is_file($filename)) unlink($filename);
			if(is_file($filename.'.err')) unlink($filename.'.err');
			if(is_file($filename.'.signed')) unlink($filename.'.signed');
 		}
 		
		//* Restart bind nameserver if update_acl is not empty, otherwise reload it
		if($data['new']['update_acl'] != '') {
			$app->services->restartServiceDelayed('bind', 'restart');
		} else {
			$app->services->restartServiceDelayed('bind', 'reload');
		}

	}

	function soa_delete($event_name, $data) {
		global $app, $conf;

		//* load the server configuration options
		$app->uses("getconf,tpl");
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');

		//* rebuild the named.conf file
		$this->write_named_conf($data, $dns_config);

		//* Delete the domain file
		//TODO : change this when distribution information has been integrated into server record
		if (file_exists('/etc/gentoo-release')) {
			$zone_file_name = $dns_config['bind_zonefiles_dir'].'/pri/'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
		}
		else {
			$zone_file_name = $dns_config['bind_zonefiles_dir'].'/pri.'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
		}

		if(is_file($zone_file_name)) unlink($zone_file_name);
		if(is_file($zone_file_name.'.err')) unlink($zone_file_name.'.err');
		$app->log("Deleting BIND domain file: ".$zone_file_name, LOGLEVEL_DEBUG);

 		//* DNSSEC-Implementation
 		if ($data['old']['dnssec_initialized'] == 'Y') exec('/usr/local/ispconfig/server/scripts/dnssec-delete.sh '.$data['old']['origin']); //delete keys
 		
		//* Reload bind nameserver
		$app->services->restartServiceDelayed('bind', 'reload');

	}

	function slave_insert($event_name, $data) {
		global $app, $conf;

		$this->action = 'insert';
		$this->slave_update($event_name, $data);

	}

	function slave_update($event_name, $data) {
		global $app, $conf;

		//* Load libraries
		$app->uses("getconf,tpl");

		//* load the server configuration options
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');

		//* rebuild the named.conf file if the origin has changed or when the origin is inserted.
		//if($this->action == 'insert' || $data['old']['origin'] != $data['new']['origin']) {
		$this->write_named_conf($data, $dns_config);
		//}

		//* Delete old domain file, if domain name has been changed
		if($data['old']['origin'] != $data['new']['origin']) {
			//TODO : change this when distribution information has been integrated into server record
			if (file_exists('/etc/gentoo-release')) {
				$filename = $dns_config['bind_zonefiles_dir'].'/sec/'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
			}
			else {
				$filename = $dns_config['bind_zonefiles_dir'].'/slave/sec.'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
			}

			if(is_file($filename)) unset($filename);
		}

		//* Ensure that the named slave directory is writable by the named user
		if (file_exists('/etc/gentoo-release')) {
			$slave_record_dir = $dns_config['bind_zonefiles_dir'].'/sec';
		} else {
			$slave_record_dir = $dns_config['bind_zonefiles_dir'].'/slave';
		}
		if(!@is_dir($slave_record_dir)) mkdir($slave_record_dir, 0770);
		chown($slave_record_dir, $dns_config['bind_user']);
		chgrp($slave_record_dir, $dns_config['bind_group']);

		//* Reload bind nameserver
		$app->services->restartServiceDelayed('bind', 'reload');

	}

	function slave_delete($event_name, $data) {
		global $app, $conf;


		//* load the server configuration options
		$app->uses("getconf,tpl");
		$dns_config = $app->getconf->get_server_config($conf["server_id"], 'dns');

		//* rebuild the named.conf file
		$this->write_named_conf($data, $dns_config);

		//* Delete the domain file
		//TODO : change this when distribution information has been integrated into server record
		if (file_exists('/etc/gentoo-release')) {
			$zone_file_name = $dns_config['bind_zonefiles_dir'].'/sec/'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
		}
		else {
			$zone_file_name = $dns_config['bind_zonefiles_dir'].'/slave/sec.'.str_replace("/", "_", substr($data['old']['origin'], 0, -1));
		}

		if(is_file($zone_file_name)) unlink($zone_file_name);
		$app->log("Deleting BIND domain file for secondary zone: ".$zone_file_name, LOGLEVEL_DEBUG);

		//* Reload bind nameserver
		$app->services->restartServiceDelayed('bind', 'reload');


	}

	function rr_insert($event_name, $data) {
		global $app, $conf;

		//* Get the data of the soa and call soa_update
		$tmp = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ?", $data['new']['zone']);
		$data["new"] = $tmp;
		$data["old"] = $tmp;
		$this->action = 'update';
		$this->soa_update($event_name, $data);

	}

	function rr_update($event_name, $data) {
		global $app, $conf;

		//* Get the data of the soa and call soa_update
		$tmp = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ?", $data['new']['zone']);
		$data["new"] = $tmp;
		$data["old"] = $tmp;
		$this->action = 'update';
		$this->soa_update($event_name, $data);

	}

	function rr_delete($event_name, $data) {
		global $app, $conf;

		//* Get the data of the soa and call soa_update
		$tmp = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ?", $data['old']['zone']);
		$data["new"] = $tmp;
		$data["old"] = $tmp;
		$this->action = 'update';
		$this->soa_update($event_name, $data);

	}

	//##################################################################

	function write_named_conf($data, $dns_config) {
		global $app, $conf;

		//* Only write the master file for the current server
		$tmps = $app->db->queryAllRecords("SELECT origin, xfer, also_notify, update_acl, dnssec_wanted FROM dns_soa WHERE active = 'Y' AND server_id=?", $conf["server_id"]);
		$zones = array();

		//* Check if the current zone that triggered this function has at least one NS record

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
			$zone_file = $pri_zonefiles_path.str_replace("/", "_", substr($tmp['origin'], 0, -1));
			if ($tmp['dnssec_wanted'] == 'Y') $zone_file .= '.signed'; //.signed is for DNSSEC-Implementation

			$options = '';
			if(trim($tmp['xfer']) != '') {
				$options .= "        allow-transfer {".str_replace(',', ';', $tmp['xfer']).";};\n";
			} else {
				$options .= "        allow-transfer {none;};\n";
			}
			if(trim($tmp['also_notify']) != '') $options .= '        also-notify {'.str_replace(',', ';', $tmp['also_notify']).";};\n";
			if(trim($tmp['update_acl']) != '') $options .= "        allow-update {".str_replace(',', ';', $tmp['update_acl']).";};\n";

			if(file_exists($zone_file)) {
				$zones[] = array( 'zone' => substr($tmp['origin'], 0, -1),
					'zonefile_path' => $zone_file,
					'options' => $options
				);
			}
		}

		$tpl = new tpl();
		$tpl->newTemplate("bind_named.conf.local.master");
		$tpl->setLoop('zones', $zones);

		//* And loop through the secondary zones, but only for the current server
		$tmps_sec = $app->db->queryAllRecords("SELECT origin, xfer, ns FROM dns_slave WHERE active = 'Y' AND server_id=?", $conf["server_id"]);
		$zones_sec = array();

		foreach($tmps_sec as $tmp) {

			$options = "        masters {".$tmp['ns'].";};\n";
			if(trim($tmp['xfer']) != '') {
				$options .= "        allow-transfer {".str_replace(',', ';', $tmp['xfer']).";};\n";
			} else {
				$options .= "        allow-transfer {none;};\n";
			}


			$zones_sec[] = array( 'zone' => substr($tmp['origin'], 0, -1),
				'zonefile_path' => $sec_zonefiles_path.str_replace("/", "_", substr($tmp['origin'], 0, -1)),
				'options' => $options
			);

			//   $filename = escapeshellcmd($dns_config['bind_zonefiles_dir'].'/slave/sec.'.substr($tmp['origin'],0,-1));
			//   $app->log("Writing BIND domain file: ".$filename,LOGLEVEL_DEBUG);


		}

		$tpl_sec = new tpl();
		$tpl_sec->newTemplate("bind_named.conf.local.slave");
		$tpl_sec->setLoop('zones', $zones_sec);

		file_put_contents($dns_config['named_conf_local_path'], $tpl->grab()."\n".$tpl_sec->grab());
		$app->log("Writing BIND named.conf.local file: ".$dns_config['named_conf_local_path'], LOGLEVEL_DEBUG);

		unset($tpl_sec);
		unset($zones_sec);
		unset($tmps_sec);
		unset($tpl);
		unset($zones);
		unset($tmps);

	}




} // end class

?>
