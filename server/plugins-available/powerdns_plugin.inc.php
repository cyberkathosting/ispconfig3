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

				//* tell pdns to use 'pdnssec rectify' on the new zone
				$this->rectifyZone($data);
				//* tell pdns to send notify to slave
				$this->notifySlave($data);
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
				//* tell pdns to use 'pdnssec rectify' on the new zone
				$this->rectifyZone($data);
				//* tell pdns to send notify to slave
				$this->notifySlave($data);
			}
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

	function find_pdns_pdnssec() {
		$output = array();
		$retval = '';
		exec("type -p pdnssec", $output, $retval);
		if ($retval == 0 && is_file($output[0])){
			return $output[0];
		} else {
			return false;
		}
	}

	function zoneRediscover() {
		$pdns_control = $this->find_pdns_control();
		if ( $pdns_control != false ) {
			exec($pdns_control . ' rediscover');
		}
	}

	function notifySlave($data) {
		$pdns_control = $this->find_pdns_control();
		if ( $pdns_control != false ) {
			exec($pdns_control . ' notify ' . rtrim($data["new"]["origin"],"."));
		}
	}

	function fetchFromMaster($data) {
		$pdns_control = $this->find_pdns_control();
		if ( $pdns_control != false ) {
			exec($pdns_control . ' retrieve ' . rtrim($data["new"]["origin"],"."));
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

	function rectifyZone($data) {
		global $app, $conf;
		if ( preg_match('/^3/',$this->get_pdns_version()) ) {
			$pdns_pdnssec = $this->find_pdns_pdnssec();
			if ( $pdns_pdnssec != false ) {
				if (isset($data["new"]["origin"])) {
					//* data has origin field only for SOA recordtypes
					exec($pdns_pdnssec . ' rectify-zone ' . rtrim($data["new"]["origin"],"."));
				} else {
					// get origin from DB for all other recordtypes
					$zn = $app->db->queryOneRecord("SELECT d.name AS name FROM powerdns.domains d, powerdns.records r WHERE r.ispconfig_id=? AND r.domain_id = d.id", $data["new"]["id"]);
					exec($pdns_pdnssec . ' rectify-zone ' . trim($zn["name"]));
				}
			}
		}
	}

} // end class

?>
