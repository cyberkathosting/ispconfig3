<?php

/*
Copyright (c) 2007 - 2016, Till Brehm, projektfarm Gmbh
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

--UPDATED 08.2009--
Full SOAP support for ISPConfig 3.1.4 b
Updated by Arkadiusz Roch & Artur Edelman
Copyright (c) Tri-Plex technology

--UPDATED 08.2013--
Migrated into new remote classes system
by Marius Cramer <m.cramer@pixcept.de>

*/

class remoting_dns extends remoting {
	// DNS Function --------------------------------------------------------------------------------------------------

	//* Create Zone with Template
	public function dns_templatezone_add($session_id, $client_id, $template_id, $domain, $ip, $ns1, $ns2, $email) {
		global $app, $conf;
		if(!$this->checkPerm($session_id, 'dns_templatezone_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$client = $app->db->queryOneRecord("SELECT default_dnsserver FROM client WHERE client_id = ?", $client_id);
		$server_id = $client["default_dnsserver"];
		$template_record = $app->db->queryOneRecord("SELECT * FROM dns_template WHERE template_id = ?", $template_id);
		$fields = explode(',', $template_record['fields']);
		$tform_def_file = "../../web/dns/form/dns_soa.tform.php";
		$app->uses('tform');
		$app->tform->loadFormDef($tform_def_file);
		$app->uses('tpl,validate_dns,remoting_lib');
		
		$app->remoting_lib->loadUserProfile($client_id);

		//* replace template placeholders
		$tpl_content = $template_record['template'];
		if($domain != '') $tpl_content = str_replace('{DOMAIN}', $domain, $tpl_content);
		if($ip != '') $tpl_content = str_replace('{IP}', $ip, $tpl_content);
		if($ns1 != '') $tpl_content = str_replace('{NS1}', $ns1, $tpl_content);
		if($ns2 != '') $tpl_content = str_replace('{NS2}', $ns2, $tpl_content);
		if($email != '') $tpl_content = str_replace('{EMAIL}', $email, $tpl_content);

		//* Parse the template
		$tpl_rows = explode("\n", $tpl_content);
		$section = '';
		$vars = array();
		$dns_rr = array();
		foreach($tpl_rows as $row) {
			$row = trim($row);
			if(substr($row, 0, 1) == '[') {
				if($row == '[ZONE]') {
					$section = 'zone';
				} elseif($row == '[DNS_RECORDS]') {
					$section = 'dns_records';
				} else {
					die('Unknown section type');
				}
			} else {
				if($row != '') {
					//* Handle zone section
					if($section == 'zone') {
						$parts = explode('=', $row);
						$key = trim($parts[0]);
						$val = trim($parts[1]);
						if($key != '') $vars[$key] = $val;
					}
					//* Handle DNS Record rows
					if($section == 'dns_records') {
						$parts = explode('|', $row);
						$dns_rr[] = array(
							'name' => $parts[1],
							'type' => $parts[0],
							'data' => $parts[2],
							'aux'  => $parts[3],
							'ttl'  => $parts[4]
						);
					}
				}
			}
		} // end foreach

		if($vars['origin'] == '') $error .= $app->lng('error_origin_empty').'<br />';
		if($vars['ns'] == '') $error .= $app->lng('error_ns_empty').'<br />';
		if($vars['mbox'] == '') $error .= $app->lng('error_mbox_empty').'<br />';
		if($vars['refresh'] == '') $error .= $app->lng('error_refresh_empty').'<br />';
		if($vars['retry'] == '') $error .= $app->lng('error_retry_empty').'<br />';
		if($vars['expire'] == '') $error .= $app->lng('error_expire_empty').'<br />';
		if($vars['minimum'] == '') $error .= $app->lng('error_minimum_empty').'<br />';
		if($vars['ttl'] == '') $error .= $app->lng('error_ttl_empty').'<br />';
		if(!isset($vars['xfer'])) $vars['xfer'] = '';

		if($error == '') {
			// Insert the soa record
			$tmp = $app->db->queryOneRecord("SELECT userid,default_group FROM sys_user WHERE client_id = ?", $client_id);
			$sys_userid = $tmp['userid'];
			$sys_groupid = $tmp['default_group'];
			unset($tmp);
			$origin = $vars['origin'];
			$ns = $vars['ns'];
			$mbox = str_replace('@', '.', $vars['mbox']);
			$refresh = $vars['refresh'];
			$retry = $vars['retry'];
			$expire = $vars['expire'];
			$minimum = $vars['minimum'];
			$ttl = $vars['ttl'];
			$xfer = $vars['xfer'];
			$also_notify = $vars['also_notify'];
			$update_acl = $vars['update_acl'];
			$serial = $app->validate_dns->increase_serial(0);
			$insert_data = array(
				"sys_userid" => $sys_userid,
				"sys_groupid" => $sys_groupid,
				"sys_perm_user" => 'riud',
				"sys_perm_group" => 'riud',
				"sys_perm_other" => '',
				"server_id" => $server_id,
				"origin" => $origin,
				"ns" => $ns,
				"mbox" => $mbox,
				"serial" => $serial,
				"refresh" => $refresh,
				"retry" => $retry,
				"expire" => $expire,
				"minimum" => $minimum,
				"ttl" => $ttl,
				"active" => 'Y',
				"xfer" => $xfer,
				"also_notify" => $also_notify,
				"update_acl" => $update_acl
			);
			$dns_soa_id = $app->db->datalogInsert('dns_soa', $insert_data, 'id');
			// Insert the dns_rr records
			if(is_array($dns_rr) && $dns_soa_id > 0) {
				foreach($dns_rr as $rr) {
					$insert_data = array(
						"sys_userid" => $sys_userid,
						"sys_groupid" => $sys_groupid,
						"sys_perm_user" => 'riud',
						"sys_perm_group" => 'riud',
						"sys_perm_other" => '',
						"server_id" => $server_id,
						"zone" => $dns_soa_id,
						"name" => $rr['name'],
						"type" => $rr['type'],
						"data" => $rr['data'],
						"aux" => $rr['aux'],
						"ttl" => $rr['ttl'],
						"active" => 'Y'
					);
					$dns_rr_id = $app->db->datalogInsert('dns_rr', $insert_data, 'id');
				}
			}
			exit;
		} else {
			throw new SoapFault('permission_denied', $error);
		}
	}


	//* Get record details
	public function dns_zone_get($session_id, $primary_id) {
		global $app;

		if(!$this->checkPerm($session_id, 'dns_zone_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_soa.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Get slave zone details
	public function dns_slave_get($session_id, $primary_id) {
		global $app;

		if(!$this->checkPerm($session_id, 'dns_zone_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_slave.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	
	//* Add a slave zone
	public function dns_slave_add($session_id, $client_id, $params) {
		if(!$this->checkPerm($session_id, 'dns_zone_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_slave.tform.php', $client_id, $params);
	}
	
	//* Update a slave zone
	public function dns_slave_update($session_id, $client_id, $primary_id, $params) {
		if(!$this->checkPerm($session_id, 'dns_zone_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_slave.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a slave zone
	public function dns_slave_delete($session_id, $primary_id) {
		if(!$this->checkPerm($session_id, 'dns_zone_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->deleteQuery('../dns/form/dns_slave.tform.php', $primary_id);
	}

	//* Get record id by origin
	public function dns_zone_get_id($session_id, $origin) {
		global $app;

		if(!$this->checkPerm($session_id, 'dns_zone_get_id')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		if(!preg_match('/^[a-z0-9][a-z0-9\-]+[a-z0-9](\.[a-z]{2,4})+$/i', $origin)){
			throw new SoapFault('no_domain_found', 'Invalid domain name.');
			return false;
		}

		$rec = $app->db->queryOneRecord("SELECT id FROM dns_soa WHERE origin like ?", $origin."%");
		if(isset($rec['id'])) {
			return $app->functions->intval($rec['id']);
		} else {
			throw new SoapFault('no_domain_found', 'There is no domain ID with informed domain name.');
			return false;
		}
	}

	//* Add a record
	public function dns_zone_add($session_id, $client_id, $params) {
		if(!$this->checkPerm($session_id, 'dns_zone_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_soa.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_zone_update($session_id, $client_id, $primary_id, $params) {
		if(!$this->checkPerm($session_id, 'dns_zone_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_soa.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_zone_delete($session_id, $primary_id) {
		if(!$this->checkPerm($session_id, 'dns_zone_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_soa.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	private function dns_rr_get($session_id, $primary_id, $rr_type = 'A') {
		global $app;
	
		$rr_type = strtolower($rr_type);
		if(!preg_match('/^[a-z]+$/', $rr_type)) {
			throw new SoapFault('permission denied', 'Invalid rr type');
		}
		
		if(!$this->checkPerm($session_id, 'dns_' . $rr_type . '_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_' . $rr_type . '.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	//* Add a record
	private function dns_rr_add($session_id, $client_id, $params, $update_serial=false, $rr_type = 'A') {
		$rr_type = strtolower($rr_type);
		if(!preg_match('/^[a-z]+$/', $rr_type)) {
			throw new SoapFault('permission denied', 'Invalid rr type');
		}
		
		if(!$this->checkPerm($session_id, 'dns_' . $rr_type . '_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
		}
		if($update_serial) {
			$this->increase_serial($session_id, $client_id, $params);
		}
		return $this->insertQuery('../dns/form/dns_' . $rr_type . '.tform.php', $client_id, $params);
	}

	//* Update a record
	private function dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial=false, $rr_type = 'A') {
		$rr_type = strtolower($rr_type);
		if(!preg_match('/^[a-z]+$/', $rr_type)) {
			throw new SoapFault('permission denied', 'Invalid rr type');
		}
		
		if(!$this->checkPerm($session_id, 'dns_' . $rr_type . '_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_' . $rr_type . '.tform.php', $client_id, $primary_id, $params);
		if($update_serial) {
			$this->increase_serial($session_id, $client_id, $params);
		}
		return $affected_rows;
	}
	
	//* Delete a record
	private function dns_rr_delete($session_id, $primary_id, $update_serial=false, $rr_type = 'A') {
		$rr_type = strtolower($rr_type);
		if(!preg_match('/^[a-z]+$/', $rr_type)) {
			throw new SoapFault('permission denied', 'Invalid rr type');
		}
		if(!$this->checkPerm($session_id, 'dns_' . $rr_type . '_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
		}
		if($update_serial) {
			$this->increase_serial($session_id, 0, array('dns_rr_id' => $primary_id));
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_' . $rr_type . '.tform.php', $primary_id);
		return $affected_rows;
	}
	
	// ----------------------------------------------------------------------------------------------------------------
	
	//* Get record details
	public function dns_aaaa_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'AAAA');
	}

	//* Add a record
	public function dns_aaaa_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'AAAA');
	}

	//* Update a record
	public function dns_aaaa_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'AAAA');
	}

	//* Delete a record
	public function dns_aaaa_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'AAAA');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_a_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'A');
	}

	//* Add a record
	public function dns_a_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'A');
	}

	//* Update a record
	public function dns_a_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'A');
	}

	//* Delete a record
	public function dns_a_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'A');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_alias_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'ALIAS');
	}

	//* Add a record
	public function dns_alias_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'ALIAS');
	}

	//* Update a record
	public function dns_alias_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'ALIAS');
	}

	//* Delete a record
	public function dns_alias_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'ALIAS');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_caa_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'CAA');
	}

	//* Add a record
	public function dns_caa_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'CAA');
	}

	//* Update a record
	public function dns_caa_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'CAA');
	}

	//* Delete a record
	public function dns_caa_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'CAA');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_cname_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'CNAME');
	}

	//* Add a record
	public function dns_cname_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'CNAME');
	}

	//* Update a record
	public function dns_cname_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'CNAME');
	}

	//* Delete a record
	public function dns_cname_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'CNAME');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_hinfo_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'HINFO');
	}

	//* Add a record
	public function dns_hinfo_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'HINFO');
	}

	//* Update a record
	public function dns_hinfo_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'HINFO');
	}

	//* Delete a record
	public function dns_hinfo_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'HINFO');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_mx_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'MX');
	}

	//* Add a record
	public function dns_mx_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'MX');
	}

	//* Update a record
	public function dns_mx_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'MX');
	}

	//* Delete a record
	public function dns_mx_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'MX');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_naptr_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'NAPTR');
	}

	//* Add a record
	public function dns_naptr_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'NAPTR');
	}

	//* Update a record
	public function dns_naptr_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'NAPTR');
	}

	//* Delete a record
	public function dns_naptr_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'NAPTR');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_ns_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'NS');
	}

	//* Add a record
	public function dns_ns_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'NS');
	}

	//* Update a record
	public function dns_ns_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'NS');
	}

	//* Delete a record
	public function dns_ns_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'NS');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_ptr_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'PTR');
	}

	//* Add a record
	public function dns_ptr_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'PTR');
	}

	//* Update a record
	public function dns_ptr_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'PTR');
	}

	//* Delete a record
	public function dns_ptr_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'PTR');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_rp_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'RP');
	}

	//* Add a record
	public function dns_rp_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'RP');
	}

	//* Update a record
	public function dns_rp_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'RP');
	}

	//* Delete a record
	public function dns_rp_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'RP');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_srv_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'SRV');
	}

	//* Add a record
	public function dns_srv_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'SRV');
	}

	//* Update a record
	public function dns_srv_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'SRV');
	}

	//* Delete a record
	public function dns_srv_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'SRV');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_tlsa_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'TLSA');
	}

	//* Add a record
	public function dns_tlsa_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'TLSA');
	}

	//* Update a record
	public function dns_tlsa_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'TLSA');
	}

	//* Delete a record
	public function dns_tlsa_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'TLSA');
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_txt_get($session_id, $primary_id) {
		return $this->dns_rr_get($session_id, $primary_id, 'TXT');
	}

	//* Add a record
	public function dns_txt_add($session_id, $client_id, $params, $update_serial=false) {
		return $this->dns_rr_add($session_id, $client_id, $params, $update_serial, 'TXT');
	}

	//* Update a record
	public function dns_txt_update($session_id, $client_id, $primary_id, $params, $update_serial=false) {
		return $this->dns_rr_update($session_id, $client_id, $primary_id, $params, $update_serial, 'TXT');
	}

	//* Delete a record
	public function dns_txt_delete($session_id, $primary_id, $update_serial=false) {
		return $this->dns_rr_delete($session_id, $primary_id, $update_serial, 'TXT');
	}

	/**
	 * Get all DNS zone by user
	 *@author Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */


	public function dns_zone_get_by_user($session_id, $client_id, $server_id) {
		global $app;
		if(!$this->checkPerm($session_id, 'dns_zone_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if (!empty($client_id) && !empty($server_id)) {
			$server_id      = $app->functions->intval($server_id);
			$client_id      = $app->functions->intval($client_id);
			$sql            = "SELECT id, origin FROM dns_soa d INNER JOIN sys_user s on(d.sys_groupid = s.default_group) WHERE client_id = ? AND server_id = ?";
			$result         = $app->db->queryAllRecords($sql, $client_id, $server_id);
			return          $result;
		}
		return false;
	}



	/**
	 *  Get all dns records for a zone
	 * @param  int  session id
	 * @param  int  dns zone id
	 * @author Sebastian Mogilowski <sebastian@mogilowski.net> 2011
	 */
	public function dns_rr_get_all_by_zone($session_id, $zone_id) {
		global $app;
		if(!$this->checkPerm($session_id, 'dns_zone_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$sql    = "SELECT * FROM dns_rr WHERE zone = ?";
		$result = $app->db->queryAllRecords($sql, $zone_id);
		return $result;
	}

	/**
	 * Changes DNS zone status
	 * @param  int  session id
	 * @param int  dns soa id
	 * @param string status active or inactive string
	 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	public function dns_zone_set_status($session_id, $primary_id, $status) {
		global $app;
		if(!$this->checkPerm($session_id, 'dns_zone_set_status')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if(in_array($status, array('active', 'inactive'))) {
			if ($status == 'active') {
				$status = 'Y';
			} else {
				$status = 'N';
			}
			$sql = "UPDATE dns_soa SET active = ? WHERE id = ?";
			$app->db->query($sql, $status, $primary_id);
			$result = $app->db->affectedRows();
			return $result;
		} else {
			throw new SoapFault('status_undefined', 'The status is not available');
			return false;
		}
	}

	private function increase_serial($session_id, $client_id, $params) {
		global $app;
		if(!isset($params['zone']) && isset($params['dns_rr_id'])) {
			$tmp = $app->db->queryOneRecord('SELECT zone FROM dns_rr WHERE id = ?',$params['dns_rr_id']);
			$params['zone'] = $tmp['zone'];
			unset($tmp);
		}
		$soa = $this->dns_zone_get($session_id, $params['zone']);
		$serial=$soa['serial'];
		$serial_date = intval(substr($serial, 0, 8));
		$count = intval(substr($serial, 8, 2));
		$current_date = date("Ymd");
		if($serial_date >= $current_date){
			$count += 1;
			if ($count > 99) {
				$serial_date += 1;
				$count = 0;
			}
			$count = str_pad($count, 2, "0", STR_PAD_LEFT);
			$new_serial = $serial_date.$count;
		} else {
			$new_serial = $current_date.'01';
		}
		$soa['serial'] = $new_serial;
		$this->dns_zone_update($session_id, $client_id, $soa['id'], $soa);
	}
}
