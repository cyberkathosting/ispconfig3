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

class remoting_openvz extends remoting {
	//* Functions for virtual machine management

	//* Get OpenVZ OStemplate details
	public function openvz_ostemplate_get($session_id, $ostemplate_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../vm/form/openvz_ostemplate.tform.php');
		return $app->remoting_lib->getDataRecord($ostemplate_id);
	}

	//* Add a openvz ostemplate record
	public function openvz_ostemplate_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../vm/form/openvz_ostemplate.tform.php', $client_id, $params);
	}

	//* Update openvz ostemplate record
	public function openvz_ostemplate_update($session_id, $client_id, $ostemplate_id, $params)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../vm/form/openvz_ostemplate.tform.php', $client_id, $ostemplate_id, $params);
		return $affected_rows;
	}

	//* Delete openvz ostemplate record
	public function openvz_ostemplate_delete($session_id, $ostemplate_id)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../vm/form/openvz_ostemplate.tform.php', $ostemplate_id);
		return $affected_rows;
	}

	//* Get OpenVZ template details
	public function openvz_template_get($session_id, $template_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../vm/form/openvz_template.tform.php');
		return $app->remoting_lib->getDataRecord($template_id);
	}

	//* Add a openvz template record
	public function openvz_template_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../vm/form/openvz_template.tform.php', $client_id, $params);
	}

	//* Update openvz template record
	public function openvz_template_update($session_id, $client_id, $template_id, $params)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../vm/form/openvz_template.tform.php', $client_id, $template_id, $params);
		return $affected_rows;
	}

	//* Delete openvz template record
	public function openvz_template_delete($session_id, $template_id)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../vm/form/openvz_template.tform.php', $template_id);
		return $affected_rows;
	}

	//* Get OpenVZ ip details
	public function openvz_ip_get($session_id, $ip_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../vm/form/openvz_ip.tform.php');
		return $app->remoting_lib->getDataRecord($ip_id);
	}

	//* Get OpenVZ a free IP address
	public function openvz_get_free_ip($session_id, $server_id = 0)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$server_id = $app->functions->intval($server_id);

		if($server_id > 0) {
			$tmp = $app->db->queryOneRecord("SELECT ip_address_id, server_id, ip_address FROM openvz_ip WHERE reserved = 'n' AND vm_id = 0 AND server_id = ? LIMIT 0,1", $server_id);
		} else {
			$tmp = $app->db->queryOneRecord("SELECT ip_address_id, server_id, ip_address FROM openvz_ip WHERE reserved = 'n' AND vm_id = 0 LIMIT 0,1");
		}

		if(count($tmp) > 0) {
			return $tmp;
		} else {
			throw new SoapFault('no_free_ip', 'There is no free IP available.');
		}
	}

	//* Add a openvz ip record
	public function openvz_ip_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../vm/form/openvz_ip.tform.php', $client_id, $params);
	}

	//* Update openvz ip record
	public function openvz_ip_update($session_id, $client_id, $ip_id, $params)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../vm/form/openvz_ip.tform.php', $client_id, $ip_id, $params);
		return $affected_rows;
	}

	//* Delete openvz ip record
	public function openvz_ip_delete($session_id, $ip_id)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../vm/form/openvz_ip.tform.php', $ip_id);
		return $affected_rows;
	}

	//* Get OpenVZ vm details
	public function openvz_vm_get($session_id, $vm_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../vm/form/openvz_vm.tform.php');
		return $app->remoting_lib->getDataRecord($vm_id);
	}

	//* Get OpenVZ list
	public function openvz_vm_get_by_client($session_id, $client_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		if (!empty($client_id)) {
			$client_id      = $app->functions->intval($client_id);
			$tmp    = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client_id);
			$sql            = "SELECT * FROM openvz_vm WHERE sys_groupid = ?";
			$result         = $app->db->queryAllRecords($sql, $tmp['groupid']);
			return          $result;
		}
		return false;
	}

	//* Add a openvz vm record
	public function openvz_vm_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../vm/form/openvz_vm.tform.php', $client_id, $params);
	}

	//* Add a openvz vm record from template
	public function openvz_vm_add_from_template($session_id, $client_id, $ostemplate_id, $template_id, $override_params = array())
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}


		$template_id = $app->functions->intval($template_id);
		$ostemplate_id = $app->functions->intval($ostemplate_id);

		//* Verify parameters
		if($template_id == 0) {
			throw new SoapFault('template_id_error', 'Template ID must be > 0.');
			return false;
		}
		if($ostemplate_id == 0) {
			throw new SoapFault('ostemplate_id_error', 'OSTemplate ID must be > 0.');
			return false;
		}

		// Verify if template and ostemplate exist
		$tmp = $app->db->queryOneRecord("SELECT template_id FROM openvz_template WHERE template_id = ?", $template_id);
		if(!is_array($tmp)) {
			throw new SoapFault('template_id_error', 'Template does not exist.');
			return false;
		}
		$tmp = $app->db->queryOneRecord("SELECT ostemplate_id FROM openvz_ostemplate WHERE ostemplate_id = ?", $ostemplate_id);
		if(!is_array($tmp)) {
			throw new SoapFault('ostemplate_id_error', 'OSTemplate does not exist.');
			return false;
		}

		//* Get the template
		$vtpl = $app->db->queryOneRecord("SELECT * FROM openvz_template WHERE template_id = ?", $template_id);

		//* Get the IP address and server_id
		if($override_params['server_id'] > 0) {
			$vmip = $app->db->queryOneRecord("SELECT ip_address_id, server_id, ip_address FROM openvz_ip WHERE reserved = 'n' AND vm_id = 0 AND server_id = ? LIMIT 0,1", $override_params['server_id']);
		} else {
			$vmip = $app->db->queryOneRecord("SELECT ip_address_id, server_id, ip_address FROM openvz_ip WHERE reserved = 'n' AND vm_id = 0 LIMIT 0,1");
		}
		if(!is_array($vmip)) {
			throw new SoapFault('vm_ip_error', 'Unable to get a free VM IP.');
			return false;
		}

		//* Build the $params array
		$params = array();
		$params['server_id'] = $vmip['server_id'];
		$params['ostemplate_id'] = $ostemplate_id;
		$params['template_id'] = $template_id;
		$params['ip_address'] = $vmip['ip_address'];
		$params['hostname'] = (isset($override_params['hostname']))?$override_params['hostname']:$vtpl['hostname'];
		$params['vm_password'] = (isset($override_params['vm_password']))?$override_params['vm_password']:$app->auth->get_random_password(10);
		$params['start_boot'] = (isset($override_params['start_boot']))?$override_params['start_boot']:'y';
		$params['active'] = (isset($override_params['active']))?$override_params['active']:'y';
		$params['active_until_date'] = (isset($override_params['active_until_date']))?$override_params['active_until_date']:null;
		$params['description'] = (isset($override_params['description']))?$override_params['description']:'';

		//* The next params get filled with pseudo values, as the get replaced
		//* by the openvz event plugin anyway with values from the template
		$params['veid'] = 1;
		$params['diskspace'] = 1;
		$params['ram'] = 1;
		$params['ram_burst'] = 1;
		$params['cpu_units'] = 1;
		$params['cpu_num'] = 1;
		$params['cpu_limit'] = 1;
		$params['io_priority'] = 1;
		$params['nameserver'] = '8.8.8.8 8.8.4.4';
		$params['create_dns'] = 'n';
		$params['capability'] = '';

		return $this->insertQuery('../vm/form/openvz_vm.tform.php', $client_id, $params, 'vm:openvz_vm:on_after_insert');
	}

	//* Update openvz vm record
	public function openvz_vm_update($session_id, $client_id, $vm_id, $params)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../vm/form/openvz_vm.tform.php', $client_id, $vm_id, $params, 'vm:openvz_vm:on_after_update');
		return $affected_rows;
	}

	//* Delete openvz vm record
	public function openvz_vm_delete($session_id, $vm_id)
	{
		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../vm/form/openvz_vm.tform.php', $vm_id, 'vm:openvz_vm:on_after_delete');
		return $affected_rows;
	}

	//* Start VM
	public function openvz_vm_start($session_id, $vm_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../vm/form/openvz_vm.tform.php');
		$vm = $app->remoting_lib->getDataRecord($vm_id);

		if(!is_array($vm)) {
			throw new SoapFault('action_pending', 'No VM with this ID available.');
			return false;
		}

		if($vm['active'] == 'n') {
			throw new SoapFault('action_pending', 'VM is not in active state.');
			return false;
		}

		$action = 'openvz_start_vm';

		$tmp = $app->db->queryOneRecord("SELECT count(action_id) as actions FROM sys_remoteaction
				WHERE server_id = ?
				AND action_type = ?
				AND action_param = ?
				AND action_state = 'pending'", $vm['server_id'], $action, $vm['veid']);

		if($tmp['actions'] > 0) {
			throw new SoapFault('action_pending', 'There is already a action pending for this VM.');
			return false;
		} else {
			$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
				"VALUES (?, ?, ?, ?, 'pending', '')";
			$app->db->query($sql, (int)$vm['server_id'], time(), $action, $vm['veid']);
		}
	}

	//* Stop VM
	public function openvz_vm_stop($session_id, $vm_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../vm/form/openvz_vm.tform.php');
		$vm = $app->remoting_lib->getDataRecord($vm_id);

		if(!is_array($vm)) {
			throw new SoapFault('action_pending', 'No VM with this ID available.');
			return false;
		}

		if($vm['active'] == 'n') {
			throw new SoapFault('action_pending', 'VM is not in active state.');
			return false;
		}

		$action = 'openvz_stop_vm';

		$tmp = $app->db->queryOneRecord("SELECT count(action_id) as actions FROM sys_remoteaction
				WHERE server_id = ?
				AND action_type = ?
				AND action_param = ?
				AND action_state = 'pending'", $vm['server_id'], $action, $vm['veid']);

		if($tmp['actions'] > 0) {
			throw new SoapFault('action_pending', 'There is already a action pending for this VM.');
			return false;
		} else {
			$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
				"VALUES (?, ?, ?, ?, 'pending', '')";
			$app->db->query($sql, (int)$vm['server_id'], time(), $action, $vm['veid']);
		}
	}

	//* Restart VM
	public function openvz_vm_restart($session_id, $vm_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'vm_openvz')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../vm/form/openvz_vm.tform.php');
		$vm = $app->remoting_lib->getDataRecord($vm_id);

		if(!is_array($vm)) {
			throw new SoapFault('action_pending', 'No VM with this ID available.');
			return false;
		}

		if($vm['active'] == 'n') {
			throw new SoapFault('action_pending', 'VM is not in active state.');
			return false;
		}

		$action = 'openvz_restart_vm';

		$tmp = $app->db->queryOneRecord("SELECT count(action_id) as actions FROM sys_remoteaction
				WHERE server_id = ?
				AND action_type = ?
				AND action_param = ?
				AND action_state = 'pending'", $vm['server_id'], $action, $vm['veid']);

		if($tmp['actions'] > 0) {
			throw new SoapFault('action_pending', 'There is already a action pending for this VM.');
			return false;
		} else {
			$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
				"VALUES (?, ?, ?, ?, 'pending', '')";
			$app->db->query($sql, (int)$vm['server_id'], time(), $action, $vm['veid']);
		}
	}

}

?>
