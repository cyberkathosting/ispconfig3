<?php

/*
Copyright (c) 2007 - 2013, Till Brehm, projektfarm Gmbh
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
	public function dns_templatezone_add($session_id, $client_id, $template_id, $domain, $ip, $ns1, $ns2, $email)
	{
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
		$app->uses('tpl,validate_dns');

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
							'name' => $app->db->quote($parts[1]),
							'type' => $app->db->quote($parts[0]),
							'data' => $app->db->quote($parts[2]),
							'aux'  => $app->db->quote($parts[3]),
							'ttl'  => $app->db->quote($parts[4])
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

		if($error == '') {
			// Insert the soa record
			$tmp = $app->db->queryOneRecord("SELECT userid,default_group FROM sys_user WHERE client_id = ?", $client_id);
			$sys_userid = $tmp['userid'];
			$sys_groupid = $tmp['default_group'];
			unset($tmp);
			$origin = $app->db->quote($vars['origin']);
			$ns = $app->db->quote($vars['ns']);
			$mbox = $app->db->quote(str_replace('@', '.', $vars['mbox']));
			$refresh = $app->db->quote($vars['refresh']);
			$retry = $app->db->quote($vars['retry']);
			$expire = $app->db->quote($vars['expire']);
			$minimum = $app->db->quote($vars['minimum']);
			$ttl = $app->db->quote($vars['ttl']);
			$xfer = $app->db->quote($vars['xfer']);
			$also_notify = $app->db->quote($vars['also_notify']);
			$update_acl = $app->db->quote($vars['update_acl']);
			$serial = $app->validate_dns->increase_serial(0);
			$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `origin`, `ns`, `mbox`, `serial`, `refresh`, `retry`, `expire`, `minimum`, `ttl`, `active`, `xfer`, `also_notify`, `update_acl`) VALUES
			('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$origin', '$ns', '$mbox', '$serial', '$refresh', '$retry', '$expire', '$minimum', '$ttl', 'Y', '$xfer', '$also_notify', '$update_acl')";
			$dns_soa_id = $app->db->datalogInsert('dns_soa', $insert_data, 'id');
			// Insert the dns_rr records
			if(is_array($dns_rr) && $dns_soa_id > 0) {
				foreach($dns_rr as $rr) {
					$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `zone`, `name`, `type`, `data`, `aux`, `ttl`, `active`) VALUES
					('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$dns_soa_id', '$rr[name]', '$rr[type]', '$rr[data]', '$rr[aux]', '$rr[ttl]', 'Y')";
					$dns_rr_id = $app->db->datalogInsert('dns_rr', $insert_data, 'id');
				}
			}
			exit;
		} else {
			throw new SoapFault('permission_denied', $error);
		}
	}


	//* Get record details
	public function dns_zone_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_zone_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_soa.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Get record id by origin
	public function dns_zone_get_id($session_id, $origin)
	{
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
	public function dns_zone_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_zone_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_soa.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_zone_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_zone_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_soa.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_zone_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_zone_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_soa.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_aaaa_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_aaaa_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_aaaa.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_aaaa_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_aaaa_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_aaaa.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_aaaa_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_aaaa_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_aaaa.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_aaaa_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_aaaa_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_aaaa.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_a_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_a_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_a.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_a_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_a_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_a.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_a_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_a_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_a.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_a_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_a_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_a.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_alias_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_alias_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_alias.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_alias_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_alias_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_alias.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_alias_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_alias_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_alias.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_alias_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_alias_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_alias.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_cname_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_cname_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_cname.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_cname_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_cname_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_cname.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_cname_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_cname_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_cname.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_cname_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_cname_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_cname.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_hinfo_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_hinfo_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_hinfo.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_hinfo_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_hinfo_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_hinfo.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_hinfo_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_hinfo_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_hinfo.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_hinfo_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_hinfo_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_hinfo.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_mx_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_mx_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_mx.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_mx_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_mx_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_mx.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_mx_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_mx_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_mx.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_mx_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_mx_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_mx.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_ns_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_ns_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_ns.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_ns_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_ns_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_ns.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_ns_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_ns_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_ns.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_ns_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_ns_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_ns.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_ptr_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_ptr_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_ptr.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_ptr_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_ptr_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_ptr.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_ptr_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_ptr_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_ptr.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_ptr_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_ptr_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_ptr.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_rp_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_rp_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_rp.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_rp_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_rp_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_rp.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_rp_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_rp_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_rp.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_rp_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_rp_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_rp.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_srv_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_srv_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_srv.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_srv_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_srv_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_srv.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_srv_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_srv_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_srv.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_srv_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_srv_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_srv.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------------

	//* Get record details
	public function dns_txt_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'dns_txt_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../dns/form/dns_txt.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function dns_txt_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_txt_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../dns/form/dns_txt.tform.php', $client_id, $params);
	}

	//* Update a record
	public function dns_txt_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'dns_txt_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../dns/form/dns_txt.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function dns_txt_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'dns_txt_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../dns/form/dns_txt.tform.php', $primary_id);
		return $affected_rows;
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

}

?>
