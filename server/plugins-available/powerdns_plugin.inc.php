<?php

/*
Copyright (c) 2009, Falko Timme, Till Brehm, projektfarm Gmbh
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

/*
The powerdns database name has to be "powerdns" and it must be accessible
by the "ispconfig" database user

TABLE STRUCTURE of the "powerdns" database:

CREATE TABLE `domains` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `master` varchar(128) default NULL,
  `last_check` int(11) default NULL,
  `type` varchar(6) NOT NULL,
  `notified_serial` int(11) default NULL,
  `account` varchar(40) default NULL,
  `ispconfig_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name_index` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE `records` (
  `id` int(11) NOT NULL auto_increment,
  `domain_id` int(11) default NULL,
  `name` varchar(255) default NULL,
  `type` varchar(6) default NULL,
  `content` varchar(255) default NULL,
  `ttl` int(11) default NULL,
  `prio` int(11) default NULL,
  `change_date` int(11) default NULL,
  `ispconfig_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `rec_name_index` (`name`),
  KEY `nametype_index` (`name`,`type`),
  KEY `domain_id` (`domain_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

CREATE TABLE `supermasters` (
  `ip` varchar(25) NOT NULL,
  `nameserver` varchar(255) NOT NULL,
  `account` varchar(40) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


IMPORTANT:
- This plugin does not support ALIAS records (supported only by MyDNS).

TODO:
- introduce a variable for the PowerDNS database
*/

class powerdns_plugin {

	var $plugin_name = 'powerdns_plugin';
	var $class_name  = 'powerdns_plugin';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if(isset($conf['powerdns']['installed']) && $conf['powerdns']['installed'] == true) {
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


	function soa_insert($event_name, $data) {
		global $app, $conf;

		if($data["new"]["active"] != 'Y') return;

		$origin = substr($data["new"]["origin"], 0, -1);
		$ispconfig_id = $data["new"]["id"];
		$serial = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ?", $ispconfig_id);
		$serial_id = $serial["serial"];
		$app->db->query("INSERT INTO powerdns.domains (name, type, notified_serial, ispconfig_id) VALUES (?, ?, ?, ?)", $origin, 'MASTER', $serial_id, $ispconfig_id);
		$zone_id = $app->db->insertID();
		if(substr($data["new"]["ns"], -1) == '.'){
			$ns = substr($data["new"]["ns"], 0, -1);
		} else {
			$ns = $data["new"]["ns"].'.'.$origin;
		}
		if($ns == '') $ns = $origin;

		$hostmaster = substr($data["new"]["mbox"], 0, -1);
		$content = $ns.' '.$hostmaster.' '.$data["new"]["serial"].' '.$data["new"]["refresh"].' '.$data["new"]["retry"].' '.$data["new"]["expire"].' '.$data["new"]["minimum"];
		$ttl = $data["new"]["ttl"];

		$app->db->query("INSERT INTO powerdns.records (domain_id, name, type, content, ttl, prio, change_date, ispconfig_id) VALUES (?, ?, 'SOA', ?, ?, 0, UNIX_TIMESTAMP(), ?)", $zone_id, $origin, $content, $ttl, $ispconfig_id);

		//* tell pdns to rediscover zones in DB
		$this->zoneRediscover();
		//* handle dnssec
		$this->handle_dnssec($data);
		//* tell pdns to use 'pdnssec rectify' on the new zone
		$this->rectifyZone($data);
		//* tell pdns to send notify to slave
		$this->notifySlave($data);
	}

	function soa_update($event_name, $data) {
		global $app, $conf;

		if($data["new"]["active"] != 'Y'){
			if($data["old"]["active"] != 'Y') return;
			$this->soa_delete($event_name, $data);
		} else {
			$exists = $app->db->queryOneRecord("SELECT * FROM powerdns.domains WHERE ispconfig_id = ?", $data["new"]["id"]);
			if($data["old"]["active"] == 'Y' && is_array($exists)){
				$origin = substr($data["new"]["origin"], 0, -1);
				$ispconfig_id = $data["new"]["id"];

				if(substr($data["new"]["ns"], -1) == '.'){
					$ns = substr($data["new"]["ns"], 0, -1);
				} else {
					$ns = $data["new"]["ns"].'.'.$origin;
				}
				if($ns == '') $ns = $origin;

				$hostmaster = substr($data["new"]["mbox"], 0, -1);
				$content = $ns.' '.$hostmaster.' '.$data["new"]["serial"].' '.$data["new"]["refresh"].' '.$data["new"]["retry"].' '.$data["new"]["expire"].' '.$data["new"]["minimum"];
				$ttl = $data["new"]["ttl"];
				$app->db->query("UPDATE powerdns.records SET name = ?, content = ?, ttl = ?, change_date = UNIX_TIMESTAMP() WHERE ispconfig_id = ? AND type = 'SOA'", $origin, $content, $ttl, $data["new"]["id"]);

			} else {
				$this->soa_insert($event_name, $data);
				$ispconfig_id = $data["new"]["id"];
				if($records = $app->db->queryAllRecords("SELECT * FROM dns_rr WHERE zone = ? AND active = 'Y'", $ispconfig_id)){
					foreach($records as $record){
						foreach($record as $key => $val){
							$data["new"][$key] = $val;
						}
						$this->rr_insert("dns_rr_insert", $data);
					}
				}
			}

			//* handle dnssec
			$this->handle_dnssec($data);
			//* tell pdns to use 'pdnssec rectify' on the new zone
			$this->rectifyZone($data);
			//* tell pdns to send notify to slave
			$this->notifySlave($data);
		}
	}

	function soa_delete($event_name, $data) {
		global $app, $conf;

		$zone = $app->db->queryOneRecord("SELECT * FROM powerdns.domains WHERE ispconfig_id = ? AND type = 'MASTER'", $data["old"]["id"]);
		$zone_id = $zone["id"];
		$app->db->query("DELETE FROM powerdns.records WHERE domain_id = ?", $zone_id);
		$app->db->query("DELETE FROM powerdns.domains WHERE id = ?", $zone_id);
	}

	function slave_insert($event_name, $data) {
		global $app, $conf;

		if($data["new"]["active"] != 'Y') return;

		$origin = substr($data["new"]["origin"], 0, -1);
		$ispconfig_id = $data["new"]["id"];
		$master_ns = $data["new"]["ns"];

		$app->db->query("INSERT INTO powerdns.domains (name, type, master, ispconfig_id) VALUES (?, ?, ?, ?)", $origin, 'SLAVE', $master_ns, $ispconfig_id);

		$zone_id = $app->db->insertID();

		//* tell pdns to fetch zone from master server
		$this->fetchFromMaster($data);
	}

	function slave_update($event_name, $data) {
		global $app, $conf;

		if($data["new"]["active"] != 'Y'){
			if($data["old"]["active"] != 'Y') return;
			$this->slave_delete($event_name, $data);
		} else {
			if($data["old"]["active"] == 'Y'){

				$origin = substr($data["new"]["origin"], 0, -1);
				$ispconfig_id = $data["new"]["id"];
				$master_ns = $data["new"]["ns"];

				$app->db->query("UPDATE powerdns.domains SET name = ?, type = 'SLAVE', master = ? WHERE ispconfig_id=? AND type = 'SLAVE'", $origin, $master_ns, $ispconfig_id);
				$zone_id = $app->db->insertID();

				$zone = $app->db->queryOneRecord("SELECT * FROM powerdns.domains WHERE ispconfig_id = ? AND type = 'SLAVE'", $ispconfig_id);
				$zone_id = $zone["id"];
				$app->db->query("DELETE FROM powerdns.records WHERE domain_id = ? AND ispconfig_id = 0", $zone_id);

				//* tell pdns to fetch zone from master server
				$this->fetchFromMaster($data);

			} else {
				$this->slave_insert($event_name, $data);

			}
		}

	}

	function slave_delete($event_name, $data) {
		global $app, $conf;

		$zone = $app->db->queryOneRecord("SELECT * FROM powerdns.domains WHERE ispconfig_id = ? AND type = 'SLAVE'", $data["old"]["id"]);
		$zone_id = $zone["id"];
		$app->db->query("DELETE FROM powerdns.records WHERE domain_id = ?", $zone_id);
		$app->db->query("DELETE FROM powerdns.domains WHERE id = ?", $zone_id);
	}

	function rr_insert($event_name, $data) {
		global $app, $conf;
		if($data["new"]["active"] != 'Y') return;
		$exists = $app->db->queryOneRecord("SELECT * FROM powerdns.records WHERE ispconfig_id = ?", $data["new"]["id"]);
		if ( is_array($exists) ) return;

		$zone = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ?", $data["new"]["zone"]);
		$origin = substr($zone["origin"], 0, -1);
		$powerdns_zone = $app->db->queryOneRecord("SELECT * FROM powerdns.domains WHERE ispconfig_id = ? AND type = 'MASTER'", $data["new"]["zone"]);
		$zone_id = $powerdns_zone["id"];

		$type = $data["new"]["type"];

		if(substr($data["new"]["name"], -1) == '.'){
			$name = substr($data["new"]["name"], 0, -1);
		} else {
			if($data["new"]["name"] == ""){
				$name = $origin;
			} else {
				$name = $data["new"]["name"].'.'.$origin;
			}
		}
		if($name == '') $name = $origin;

		switch ($type) {
		case "CNAME":
		case "MX":
		case "NS":
		case "ALIAS":
		case "PTR":
		case "SRV":
			if(substr($data["new"]["data"], -1) == '.'){
				$content = substr($data["new"]["data"], 0, -1);
			} else {
				$content = $data["new"]["data"].'.'.$origin;
			}
			break;
		case "HINFO":
			$content = $data["new"]["data"];
			$quote1 = strpos($content, '"');
			if($quote1 !== FALSE){
				$quote2 = strpos(substr($content, ($quote1 + 1)), '"');
			}
			if($quote1 !== FALSE && $quote2 !== FALSE){
				$text_between_quotes = str_replace(' ', '_', substr($content, ($quote1 + 1), (($quote2 - $quote1))));
				$content = $text_between_quotes.substr($content, ($quote2 + 2));
			}
			break;
		default:
			$content = $data["new"]["data"];
		}

		$ttl = $data["new"]["ttl"];
		$prio = $data["new"]["aux"];
		$change_date = time();
		$ispconfig_id = $data["new"]["id"];

		$app->db->query("INSERT INTO powerdns.records (domain_id, name, type, content, ttl, prio, change_date, ispconfig_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", $zone_id, $name, $type, $content, $ttl, $prio, $change_date, $ispconfig_id);

		//* tell pdns to use 'pdnssec rectify' on the new zone
		$this->rectifyZone($data);
	}

	function rr_update($event_name, $data) {
		global $app, $conf;

		if($data["new"]["active"] != 'Y'){
			if($data["old"]["active"] != 'Y') return;
			$this->rr_delete($event_name, $data);
		} else {
			$exists = $app->db->queryOneRecord("SELECT * FROM powerdns.records WHERE ispconfig_id = ?", $data["new"]["id"]);
			if($data["old"]["active"] == 'Y' && is_array($exists)){
				$zone = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ?", $data["new"]["zone"]);
				$origin = substr($zone["origin"], 0, -1);
				$powerdns_zone = $app->db->queryOneRecord("SELECT * FROM powerdns.domains WHERE ispconfig_id = ? AND type = 'MASTER'", $data["new"]["zone"]);
				$zone_id = $powerdns_zone["id"];

				$type = $data["new"]["type"];

				if(substr($data["new"]["name"], -1) == '.'){
					$name = substr($data["new"]["name"], 0, -1);
				} else {
					if($data["new"]["name"] == ""){
						$name = $origin;
					} else {
						$name = $data["new"]["name"].'.'.$origin;
					}
				}
				if($name == '') $name = $origin;

				switch ($type) {
				case "CNAME":
				case "MX":
				case "NS":
				case "ALIAS":
				case "PTR":
				case "SRV":
					if(substr($data["new"]["data"], -1) == '.'){
						$content = substr($data["new"]["data"], 0, -1);
					} else {
						$content = $data["new"]["data"].'.'.$origin;
					}
					break;
				case "HINFO":
					$content = $data["new"]["data"];
					$quote1 = strpos($content, '"');
					if($quote1 !== FALSE){
						$quote2 = strpos(substr($content, ($quote1 + 1)), '"');
					}
					if($quote1 !== FALSE && $quote2 !== FALSE){
						$text_between_quotes = str_replace(' ', '_', substr($content, ($quote1 + 1), (($quote2 - $quote1))));
						$content = $text_between_quotes.substr($content, ($quote2 + 2));
					}
					break;
				default:
					$content = $data["new"]["data"];
				}

				$ttl = $data["new"]["ttl"];
				$prio = $data["new"]["aux"];
				$change_date = time();
				$ispconfig_id = $data["new"]["id"];
				$app->db->query("UPDATE powerdns.records SET name = ?, type = ?, content = ?, ttl = ?, prio = ?, change_date = UNIX_TIMESTAMP() WHERE ispconfig_id = ? AND type != 'SOA'", $name, $type, $content, $ttl, $prio, $ispconfig_id);

				//* tell pdns to use 'pdnssec rectify' on the new zone
				$this->rectifyZone($data);
			} else {
				$this->rr_insert($event_name, $data);
			}
		}
	}

	function rr_delete($event_name, $data) {
		global $app, $conf;

		$ispconfig_id = $data["old"]["id"];
		$app->db->query("DELETE FROM powerdns.records WHERE ispconfig_id = ? AND type != 'SOA'", $ispconfig_id);
	}

	function find_pdns_control() {
		$output = array();
		$retval = '';
		exec("type -p pdns_control", $output, $retval);
		if ($retval == 0 && is_file($output[0])){
			return $output[0];
		} else {
			return false;
		}
	}

	function find_pdns_pdnssec_or_pdnsutil() {
		$output = array();
		$retval = '';

		// The command is named pdnssec in PowerDNS 3
		exec("type -p pdnssec", $output, $retval);
		if ($retval == 0 && is_file($output[0])){
			return $output[0];
		}

		// But in PowerNDS 4 they renamed it to pdnsutil
		exec("type -p pdnsutil", $output, $retval);
		if ($retval == 0 && is_file($output[0])){
			return $output[0];
		}

		return false;
	}

	function zoneRediscover() {
		$pdns_control = $this->find_pdns_control();
		if ( $pdns_control != false ) {
			exec($pdns_control . ' rediscover');
		}
	}

	function notifySlave($data) {
		global $app;
		
		$pdns_control = $this->find_pdns_control();
		if ( $pdns_control != false ) {
			$app->system->exec_safe($pdns_control . ' notify ?', rtrim($data["new"]["origin"],"."));
		}
	}

	function fetchFromMaster($data) {
		global $app;
		
		$pdns_control = $this->find_pdns_control();
		if ( $pdns_control != false ) {
			$app->system->exec_safe($pdns_control . ' retrieve ?', rtrim($data["new"]["origin"],"."));
		}
	}

	function get_pdns_version() {
		$pdns_control = $this->find_pdns_control();
		if ( $pdns_control != false ) {
			$output=array();
			$retval='';
			exec($pdns_control . ' version',$output,$retval);
			return $output[0];
		} else {
			//* fallback to version 2
			return 2;
		}
	}

	function is_pdns_version_supported() {
		if (preg_match('/^[34]/',$this->get_pdns_version())) {
			return true;
		}

		return false;
	}

	function handle_dnssec($data) {
		// If origin changed, delete keys first
		if ($data['old']['origin'] != $data['new']['origin']) {
			if (@$data['old']['dnssec_initialized'] == 'Y' && strlen(@$data['old']['origin']) > 3) {
				$this->soa_dnssec_delete($data);
			}
		}

		// If DNSSEC is disabled, but was enabled before, just disable DNSSEC but leave the keys in dns_info
		if ($data['new']['dnssec_wanted'] === 'N' && $data['old']['dnssec_wanted'] === 'Y') {
			$this->soa_dnssec_disable($data);

			return;
		}

		// If DNSSEC is wanted, enable it
		if ($data['new']['dnssec_wanted'] === 'Y' && $data['old']['dnssec_wanted'] === 'N') {
			$this->soa_dnssec_create($data);
		}
	}

	function soa_dnssec_create($data) {
		global $app;

		if (false === $this->is_pdns_version_supported()) {
			return;
		}

		$pdns_pdnssec = $this->find_pdns_pdnssec_or_pdnsutil();
		if ($pdns_pdnssec === false) {
			return;
		}

		$zone = rtrim($data['new']['origin'],'.');
		$log = array();

		// We don't log the actual commands here, because having commands in the dnssec_info field will trigger
		// the IDS if you try to save the record using the interface afterwards.
		$cmd_add_zone_key_ksk = sprintf('%s add-zone-key %s ksk active 2048 rsasha256', $pdns_pdnssec, $zone);
		$log[] = sprintf("\r\n%s %s", date('c'), 'Running add-zone-key ksk command...');
		exec($cmd_add_zone_key_ksk, $log);

		$cmd_add_zone_key_zsk = sprintf('%s add-zone-key %s zsk active 1024 rsasha256', $pdns_pdnssec, $zone);
		$log[] = sprintf("\r\n%s %s", date('c'), 'Running add-zone-key zsk command...');
		exec($cmd_add_zone_key_zsk, $log);

		$cmd_set_nsec3 = sprintf('%s set-nsec3 %s "1 0 10 deadbeef" 2>&1', $pdns_pdnssec, $zone);
		$log[] = sprintf("\r\n%s %s", date('c'), 'Running set-nsec3 command...');
		exec($cmd_set_nsec3, $log);

		$pubkeys = array();
		$cmd_show_zone = sprintf('%s show-zone %s 2>&1', $pdns_pdnssec, $zone);
		$log[] = sprintf("\r\n%s %s", date('c'), 'Running show-zone command...');
		exec($cmd_show_zone, $pubkeys);

		$log = array_merge($log, $pubkeys);

		$dnssec_info = array_merge($this->format_dnssec_pubkeys($pubkeys), array('', '== Raw log ============================'), $log);
		$dnssec_info = implode("\r\n", $dnssec_info);

		if ($app->dbmaster !== $app->db) {
			$app->dbmaster->query('UPDATE dns_soa SET dnssec_info=?, dnssec_initialized=? WHERE id=?', $dnssec_info, 'Y', intval($data['new']['id']));
		}
		$app->db->query('UPDATE dns_soa SET dnssec_info=?, dnssec_initialized=? WHERE id=?', $dnssec_info, 'Y', intval($data['new']['id']));
	}

	function format_dnssec_pubkeys($lines) {
		$formatted = array();

		// We don't care about the first two lines about presigning and NSEC
		array_shift($lines);
		array_shift($lines);

		foreach ($lines as $line) {
			switch ($part = substr($line, 0, 3)) {
				case 'ID ':
					// Only process active keys
					// 'Active: 1' is pdnssec (PowerDNS 3.x) output
					// 'Active (' is pdnsutil (PowerDNS 4.x) output
					if (!strpos($line, 'Active: 1') && !strpos($line, 'Active ( ')) {
						break;
					}

					// Determine key type (KSK, ZSK or CSK)
					preg_match('/(KSK|ZSK|CSK)/', $line, $matches_key_type);
					$key_type = $matches_key_type[1];

					// We only care about the KSK or CSK
					if (!in_array($key_type, array('KSK', 'CSK'), true)) {
						break;
					}

					// Determine key tag
					preg_match('/ tag = (\d+),/', $line, $matches_key_tag);
					$formatted[] = sprintf('%s key tag: %d', $key_type, $matches_key_tag[1]);

					// Determine algorithm
					preg_match('/ algo = (\d+),/', $line, $matches_algo_id);
					preg_match('/ \( (.*) \)$/', $line, $matches_algo_name);
					$formatted[] = sprintf('Algo: %d (%s)', $matches_algo_id[1], $matches_algo_name[1]);

					// Determine bits
					preg_match('/ bits = (\d+)/', $line, $matches_bits);
					$formatted[] = sprintf('Bits: %d', $matches_bits[1]);

					break;

				case 'KSK':
				case 'CSK':
					// Determine DNSKEY
					preg_match('/ IN DNSKEY \d+ \d+ \d+ (.*) ;/', $line, $matches_dnskey);
					$formatted[] = sprintf('DNSKEY: %s', $matches_dnskey[1]);

					break;

				case 'DS ':
					// Determine key tag
					preg_match('/ IN DS (\d+) \d+ \d+ /', $line, $matches_ds_key_tag);
					$formatted[] = sprintf('  - DS key tag: %d', $matches_ds_key_tag[1]);

					// Determine key tag
					preg_match('/ IN DS \d+ (\d+) \d+ /', $line, $matches_ds_algo);
					$formatted[] = sprintf('    Algo: %d', $matches_ds_algo[1]);

					// Determine digest
					preg_match('/ IN DS \d+ \d+ (\d+) /', $line, $matches_ds_digest_id);
					preg_match('/ \( (.*) \)$/', $line, $matches_ds_digest_name);
					$formatted[] = sprintf('    Digest: %d (%s)', $matches_ds_digest_id[1], $matches_ds_digest_name[1]);

					// Determine public key
					preg_match('/ IN DS \d+ \d+ \d+ (.*) ;/', $line, $matches_ds_key);
					$formatted[] = sprintf('    Public key: %s', $matches_ds_key[1]);
					break;

				default:
					break;
			}
		}

		return $formatted;
	}

	function soa_dnssec_disable($data) {
		global $app;

		if (false === $this->is_pdns_version_supported()) {
			return;
		}

		$pdns_pdnssec = $this->find_pdns_pdnssec_or_pdnsutil();
		if ($pdns_pdnssec === false) {
			return;
		}

		$zone = rtrim($data['new']['origin'],'.');
		$log = array();

		// We don't log the actual commands here, because having commands in the dnssec_info field will trigger
		// the IDS if you try to save the record using the interface afterwards.
		$cmd_disable_dnssec = sprintf('%s disable-dnssec %s 2>&1', $pdns_pdnssec, $zone);
		$log[] = sprintf("\r\n%s %s", date('c'), 'Running disable-dnssec command...');
		exec($cmd_disable_dnssec, $log);

		if ($app->dbmaster !== $app->db) {
			$app->dbmaster->query('UPDATE dns_soa SET dnssec_initialized=? WHERE id=?', 'N', intval($data['new']['id']));
		}
		$app->db->query('UPDATE dns_soa SET dnssec_initialized=? WHERE id=?', 'N', intval($data['new']['id']));
	}

	function soa_dnssec_delete($data) {
		global $app;

		if (false === $this->is_pdns_version_supported()) {
			return;
		}

		$pdns_pdnssec = $this->find_pdns_pdnssec_or_pdnsutil();
		if ($pdns_pdnssec === false) {
			return;
		}

		$zone = rtrim($data['old']['origin'],'.');
		$log = array();

		// We don't log the actual commands here, because having commands in the dnssec_info field will trigger
		// the IDS if you try to save the record using the interface afterwards.
		$cmd_disable_dnssec = sprintf('%s disable-dnssec %s 2>&1', $pdns_pdnssec, $zone);
		$log[] = sprintf("\r\n%s %s", date('c'), 'Running disable-dnssec command...');
		exec($cmd_disable_dnssec, $log);


		$dnssec_info = array_merge(array('== Raw log ============================'), $log);
		$dnssec_info = implode("\r\n", $dnssec_info);

		if ($app->dbmaster !== $app->db) {
			$app->dbmaster->query('UPDATE dns_soa SET dnssec_info=?, dnssec_initialized=? WHERE id=?', $dnssec_info, 'N', intval($data['new']['id']));
		}
		$app->db->query('UPDATE dns_soa SET dnssec_info=?, dnssec_initialized=? WHERE id=?', $dnssec_info, 'N', intval($data['new']['id']));
	}

	function rectifyZone($data) {
		global $app, $conf;

		if (false === $this->is_pdns_version_supported()) {
			return;
		}

		$pdns_pdnssec = $this->find_pdns_pdnssec_or_pdnsutil();
		if ( $pdns_pdnssec != false ) {
			if (isset($data["new"]["origin"])) {
				//* data has origin field only for SOA recordtypes
				$app->system->exec_safe($pdns_pdnssec . ' rectify-zone ?', rtrim($data["new"]["origin"],"."));
			} else {
				// get origin from DB for all other recordtypes
				$zn = $app->db->queryOneRecord("SELECT d.name AS name FROM powerdns.domains d, powerdns.records r WHERE r.ispconfig_id=? AND r.domain_id = d.id", $data["new"]["id"]);
				$app->system->exec_safe($pdns_pdnssec . ' rectify-zone ?', trim($zn["name"]));
			}
		}
	}

} // end class

?>
