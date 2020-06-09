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

class remoting_sites extends remoting {
	// Website functions ---------------------------------------------------------------------------------------

	//* Get cron details
	public function sites_cron_get($session_id, $cron_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_cron_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/cron.tform.php');
		return $app->remoting_lib->getDataRecord($cron_id);
	}

	//* Add a cron record
	public function sites_cron_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_cron_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/cron.tform.php', $client_id, $params);
	}

	//* Update cron record
	public function sites_cron_update($session_id, $client_id, $cron_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_cron_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/cron.tform.php', $client_id, $cron_id, $params);
		return $affected_rows;
	}

	//* Delete cron record
	public function sites_cron_delete($session_id, $cron_id)
	{
		if(!$this->checkPerm($session_id, 'sites_cron_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/cron.tform.php', $cron_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_database_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_database_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/database.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}
	
	/* TODO: secure queries! */
	//* Add a record
	public function sites_database_add($session_id, $client_id, $params)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_database_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		//* Check for duplicates
		$tmp = $app->db->queryOneRecord("SELECT count(database_id) as dbnum FROM web_database WHERE database_name = ? AND server_id = ?", $params['database_name'], $params["server_id"]);
		if($tmp['dbnum'] > 0) {
			throw new SoapFault('database_name_error_unique', 'There is already a database with that name on the same server.');
			return false;
		}

		$sql = $this->insertQueryPrepare('../sites/form/database.tform.php', $client_id, $params);
		if($sql !== false) {
			$app->uses('sites_database_plugin');

			$this->id = 0;
			$this->dataRecord = $params;

			$retval = $this->insertQueryExecute($sql, $params);
			$app->sites_database_plugin->processDatabaseInsert($this);
			
			// set correct values for backup_interval and backup_copies
			if(isset($params['backup_interval']) || isset($params['backup_copies']) || isset($params['backup_format_web']) || isset($params['backup_format_db'])){
				$sql_set = array();
				if(isset($params['backup_interval'])) $sql_set[] = "backup_interval = '".$app->db->quote($params['backup_interval'])."'";
				if(isset($params['backup_copies'])) $sql_set[] = "backup_copies = ".$app->functions->intval($params['backup_copies']);
				if(isset($params['backup_format_web'])) $sql_set[] = "backup_format_web = ".$app->functions->intval($params['backup_format_web']);
				if(isset($params['backup_format_db'])) $sql_set[] = "backup_format_db = ".$app->functions->intval($params['backup_format_db']);
				$this->updateQueryExecute("UPDATE web_database SET ".implode(', ', $sql_set)." WHERE database_id = ".$retval, $retval, $params);
			}
			
			return $retval;
		}

		return false;
	}

	//* Update a record
	public function sites_database_update($session_id, $client_id, $primary_id, $params)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_database_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$sql = $this->updateQueryPrepare('../sites/form/database.tform.php', $client_id, $primary_id, $params);
		if($sql !== false) {
			$app->uses('sites_database_plugin');

			$this->id = $primary_id;
			$this->dataRecord = $params;
			$app->sites_database_plugin->processDatabaseUpdate($this);
			$retval = $this->updateQueryExecute($sql, $primary_id, $params);
			
			// set correct values for backup_interval and backup_copies
			if(isset($params['backup_interval']) || isset($params['backup_copies']) || isset($params['backup_format_web']) || isset($params['backup_format_db'])){
				$sql_set = array();
				if(isset($params['backup_interval'])) $sql_set[] = "backup_interval = '".$app->db->quote($params['backup_interval'])."'";
				if(isset($params['backup_copies'])) $sql_set[] = "backup_copies = ".$app->functions->intval($params['backup_copies']);
				if(isset($params['backup_format_web'])) $sql_set[] = "backup_format_web = ".$app->functions->intval($params['backup_format_web']);
				if(isset($params['backup_format_db'])) $sql_set[] = "backup_format_db = ".$app->functions->intval($params['backup_format_db']);
				$this->updateQueryExecute("UPDATE web_database SET ".implode(', ', $sql_set)." WHERE database_id = ".$primary_id, $primary_id, $params);
			}
			
			return $retval;
		}

		return false;
	}

	//* Delete a record
	public function sites_database_delete($session_id, $primary_id)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'sites_database_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$app->uses('sites_database_plugin');
		$app->sites_database_plugin->processDatabaseDelete($primary_id);

		$affected_rows = $this->deleteQuery('../sites/form/database.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_database_user_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_database_user_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/database_user.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_database_user_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_database_user_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		return $this->insertQuery('../sites/form/database_user.tform.php', $client_id, $params);
	}

	//* Update a record
	public function sites_database_user_update($session_id, $client_id, $primary_id, $params)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_database_user_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/database_user.tform.php');
		$old_rec = $app->remoting_lib->getDataRecord($primary_id);

		$result = $this->updateQuery('../sites/form/database_user.tform.php', $client_id, $primary_id, $params);

		$new_rec = $app->remoting_lib->getDataRecord($primary_id);

		$records = $app->db->queryAllRecords("SELECT DISTINCT server_id FROM web_database WHERE database_user_id = ? UNION SELECT DISTINCT server_id FROM web_database WHERE database_ro_user_id = ?", $primary_id, $primary_id);
		foreach($records as $rec) {
			$tmp_rec = $new_rec;
			$tmp_rec['server_id'] = $rec['server_id'];
			$app->remoting_lib->datalogSave('UPDATE', $primary_id, $old_rec, $tmp_rec);
		}
		unset($new_rec);
		unset($old_rec);
		unset($records);

		return $result;
	}

	//* Delete a record
	public function sites_database_user_delete($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_database_user_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		//$app->db->datalogDelete('web_database_user', 'database_user_id', $primary_id);
		$affected_rows = $this->deleteQuery('../sites/form/database_user.tform.php', $primary_id);

		$records = $app->db->queryAllRecords("SELECT database_id FROM web_database WHERE database_user_id = ?", $primary_id);
		foreach($records as $rec) {
			$app->db->datalogUpdate('web_database', array('database_user_id' => null), 'database_id', $rec['database_id']);

		}
		$records = $app->db->queryAllRecords("SELECT database_id FROM web_database WHERE database_ro_user_id = ?", $primary_id);
		foreach($records as $rec) {
			$app->db->datalogUpdate('web_database', array('database_ro_user_id' => null), 'database_id', $rec['database_id']);
		}

		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_ftp_user_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_ftp_user_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/ftp_user.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_ftp_user_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_ftp_user_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/ftp_user.tform.php', $client_id, $params);
	}

	//* Update a record
	public function sites_ftp_user_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_ftp_user_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/ftp_user.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function sites_ftp_user_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'sites_ftp_user_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/ftp_user.tform.php', $primary_id);
		return $affected_rows;
	}

	//* Get server for an ftp user
	public function sites_ftp_user_server_get($session_id, $ftp_user)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_ftp_user_server_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$data = $app->db->queryOneRecord("SELECT server_id FROM ftp_user WHERE username = ?", $ftp_user);
		//file_put_contents('/tmp/test.txt', serialize($data));
		if(!isset($data['server_id'])) return false;

		$server = $this->server_get($session_id, $data['server_id'], 'server');
		//file_put_contents('/tmp/test2.txt', serialize($server));

		return $server;
	}

	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_shell_user_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_shell_user_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/shell_user.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_shell_user_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_shell_user_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/shell_user.tform.php', $client_id, $params);
	}

	//* Update a record
	public function sites_shell_user_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_shell_user_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/shell_user.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function sites_shell_user_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'sites_shell_user_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/shell_user.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_web_domain_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_web_domain_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_vhost_domain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_web_domain_add($session_id, $client_id, $params, $readonly = false)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'sites_web_domain_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		if(!isset($params['client_group_id']) or (isset($params['client_group_id']) && empty($params['client_group_id']))) {
			$rec = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client_id);
			$params['client_group_id'] = $rec['groupid'];
		}

		//* Set a few params to "not empty" values which get overwritten by the sites_web_vhost_domain_plugin
		if($params['document_root'] == '') $params['document_root'] = '-';
		if($params['system_user'] == '') $params['system_user'] = '-';
		if($params['system_group'] == '') $params['system_group'] = '-';
		if($params['log_retention'] == '') $params['log_retention'] = 30;

		//* Set a few defaults for nginx servers
		if($params['pm_max_children'] == '') $params['pm_max_children'] = 1;
		if($params['pm_start_servers'] == '') $params['pm_start_servers'] = 1;
		if($params['pm_min_spare_servers'] == '') $params['pm_min_spare_servers'] = 1;
		if($params['pm_max_spare_servers'] == '') $params['pm_max_spare_servers'] = 1;

		$domain_id = $this->insertQuery('../sites/form/web_vhost_domain.tform.php', $client_id, $params, 'sites:web_vhost_domain:on_after_insert');
		if ($readonly === true)
			$app->db->query("UPDATE web_domain SET `sys_userid` = '1' WHERE domain_id = ?", $domain_id);
		return $domain_id;
	}

	//* Update a record
	public function sites_web_domain_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_domain_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		if($params['log_retention'] == '') $params['log_retention'] = 30;

		//* Set a few defaults for nginx servers
		if($params['pm_max_children'] == '') $params['pm_max_children'] = 1;
		if($params['pm_start_servers'] == '') $params['pm_start_servers'] = 1;
		if($params['pm_min_spare_servers'] == '') $params['pm_min_spare_servers'] = 1;
		if($params['pm_max_spare_servers'] == '') $params['pm_max_spare_servers'] = 1;

		$affected_rows = $this->updateQuery('../sites/form/web_vhost_domain.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function sites_web_domain_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'sites_web_domain_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/web_vhost_domain.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_web_vhost_aliasdomain_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_vhost_domain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_web_vhost_aliasdomain_add($session_id, $client_id, $params)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		//* Set a few params to "not empty" values which get overwritten by the sites_web_domain_plugin
		if($params['document_root'] == '') $params['document_root'] = '-';
		if($params['system_user'] == '') $params['system_user'] = '-';
		if($params['system_group'] == '') $params['system_group'] = '-';
		if($params['log_retention'] == '') $params['log_retention'] = 30;

		//* Set a few defaults for nginx servers
		if($params['pm_max_children'] == '') $params['pm_max_children'] = 1;
		if($params['pm_start_servers'] == '') $params['pm_start_servers'] = 1;
		if($params['pm_min_spare_servers'] == '') $params['pm_min_spare_servers'] = 1;
		if($params['pm_max_spare_servers'] == '') $params['pm_max_spare_servers'] = 1;

		$domain_id = $this->insertQuery('../sites/form/web_vhost_domain.tform.php', $client_id, $params, 'sites:web_vhost_aliasdomain:on_after_insert');
		return $domain_id;
	}

	//* Update a record
	public function sites_web_vhost_aliasdomain_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		if($params['log_retention'] == '') $params['log_retention'] = 30;

		//* Set a few defaults for nginx servers
		if($params['pm_max_children'] == '') $params['pm_max_children'] = 1;
		if($params['pm_start_servers'] == '') $params['pm_start_servers'] = 1;
		if($params['pm_min_spare_servers'] == '') $params['pm_min_spare_servers'] = 1;
		if($params['pm_max_spare_servers'] == '') $params['pm_max_spare_servers'] = 1;

		$affected_rows = $this->updateQuery('../sites/form/web_vhost_domain.tform.php', $client_id, $primary_id, $params, 'sites:web_vhost_aliasdomain:on_after_insert');
		return $affected_rows;
	}

	//* Delete a record
	public function sites_web_vhost_aliasdomain_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/web_vhost_domain.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_web_vhost_subdomain_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_web_subdomain_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_vhost_domain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_web_vhost_subdomain_add($session_id, $client_id, $params)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		//* Set a few params to "not empty" values which get overwritten by the sites_web_domain_plugin
		if($params['document_root'] == '') $params['document_root'] = '-';
		if($params['system_user'] == '') $params['system_user'] = '-';
		if($params['system_group'] == '') $params['system_group'] = '-';
		if($params['log_retention'] == '') $params['log_retention'] = 30;

		//* Set a few defaults for nginx servers
		if($params['pm_max_children'] == '') $params['pm_max_children'] = 1;
		if($params['pm_start_servers'] == '') $params['pm_start_servers'] = 1;
		if($params['pm_min_spare_servers'] == '') $params['pm_min_spare_servers'] = 1;
		if($params['pm_max_spare_servers'] == '') $params['pm_max_spare_servers'] = 1;

		$domain_id = $this->insertQuery('../sites/form/web_vhost_domain.tform.php', $client_id, $params, 'sites:web_vhost_subdomain:on_after_insert');
		return $domain_id;
	}

	//* Update a record
	public function sites_web_vhost_subdomain_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		if($params['log_retention'] == '') $params['log_retention'] = 30;

		//* Set a few defaults for nginx servers
		if($params['pm_max_children'] == '') $params['pm_max_children'] = 1;
		if($params['pm_start_servers'] == '') $params['pm_start_servers'] = 1;
		if($params['pm_min_spare_servers'] == '') $params['pm_min_spare_servers'] = 1;
		if($params['pm_max_spare_servers'] == '') $params['pm_max_spare_servers'] = 1;

		$affected_rows = $this->updateQuery('../sites/form/web_vhost_domain.tform.php', $client_id, $primary_id, $params, 'sites:web_vhost_subdomain:on_after_insert');
		return $affected_rows;
	}

	//* Delete a record
	public function sites_web_vhost_subdomain_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/web_vhost_domain.tform.php', $primary_id);
		return $affected_rows;
	}

	// -----------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_web_aliasdomain_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_childdomain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_web_aliasdomain_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/web_childdomain.tform.php', $client_id, $params);
	}

	//* Update a record
	public function sites_web_aliasdomain_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/web_childdomain.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function sites_web_aliasdomain_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'sites_web_aliasdomain_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/web_childdomain.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_web_subdomain_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_web_subdomain_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_childdomain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_web_subdomain_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/web_childdomain.tform.php', $client_id, $params);
	}

	//* Update a record
	public function sites_web_subdomain_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/web_childdomain.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function sites_web_subdomain_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'sites_web_subdomain_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/web_childdomain.tform.php', $primary_id);
		return $affected_rows;
	}

	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_web_folder_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_web_folder_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_folder.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_web_folder_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_folder_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/web_folder.tform.php', $client_id, $params);
	}

	//* Update a record
	public function sites_web_folder_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_folder_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/web_folder.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function sites_web_folder_delete($session_id, $primary_id)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'sites_web_folder_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		// Delete all users that belong to this folder. - taken from web_folder_delete.php
		$records = $app->db->queryAllRecords("SELECT web_folder_user_id FROM web_folder_user WHERE web_folder_id = ?", $primary_id);
		foreach($records as $rec) {
			$this->deleteQuery('../sites/form/web_folder_user.tform.php', $rec['web_folder_user_id']);
			//$app->db->datalogDelete('web_folder_user','web_folder_user_id',$rec['web_folder_user_id']);
		}
		unset($records);

		$affected_rows = $this->deleteQuery('../sites/form/web_folder.tform.php', $primary_id);
		return $affected_rows;
	}

	// -----------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_web_folder_user_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_web_folder_user_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/web_folder_user.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_web_folder_user_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_folder_user_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/web_folder_user.tform.php', $client_id, $params);
	}

	//* Update a record
	public function sites_web_folder_user_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_web_folder_user_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/web_folder_user.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function sites_web_folder_user_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'sites_web_folder_user_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../sites/form/web_folder_user.tform.php', $primary_id);
		return $affected_rows;
	}

	/**
	 * Gets sites by $sys_userid & $sys_groupid
	 * @param int  session id
	 * @param int  user id
	 * @param array list of groups
	 * @return mixed array with sites by user
	 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */


	public function client_get_sites_by_user($session_id, $sys_userid, $sys_groupid) {
		global $app;
		if(!$this->checkPerm($session_id, 'client_get_sites_by_user')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$sys_userid  = $app->functions->intval($sys_userid);
		$sys_groupid = explode(',', $sys_groupid);
		$new_group = array();
		foreach($sys_groupid as $group_id) {
			$new_group[] = $app->functions->intval( $group_id);
		}
		$group_list = implode(',', $new_group);
		$sql ="SELECT domain, domain_id, document_root, active FROM web_domain WHERE ( (sys_userid = $sys_userid  AND sys_perm_user LIKE '%r%') OR (sys_groupid IN ($group_list) AND sys_perm_group LIKE '%r%') OR  sys_perm_other LIKE '%r%') AND type = 'vhost'";
		$result = $app->db->queryAllRecords($sql);
		if(isset($result)) {
			return $result;
		} else {
			throw new SoapFault('no_client_found', 'There is no site for this user');
			return false;
		}
	}



	/**
	 * Change domains status
	 * @param int  session id
	 * @param int  site id
	 * @param string active or inactive string
	 * @return mixed false if error
	 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	public function sites_web_domain_set_status($session_id, $primary_id, $status) {
		global $app;
		if(!$this->checkPerm($session_id, 'sites_web_domain_set_status')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if(in_array($status, array('active', 'inactive'))) {
			if ($status == 'active') {
				$status = 'y';
			} else {
				$status = 'n';
			}
			$app->remoting_lib->loadFormDef('../sites/form/web_vhost_domain.tform.php');
			$params = $app->remoting_lib->getDataRecord($primary_id);
			$params['active'] = $status;
			
			$affected_rows = $this->updateQuery('../sites/form/web_vhost_domain.tform.php', 0, $primary_id, $params);
			return $affected_rows;
		} else {
			throw new SoapFault('status_undefined', 'The status is not available');
			return false;
		}
	}

	/**
	 * Get all databases by user
	 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
	 */
	public function sites_database_get_all_by_user($session_id, $client_id)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'sites_database_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$client_id = $app->functions->intval($client_id);
		$sql = "SELECT d.database_id, d.database_name, d.database_user_id, d.database_ro_user_id, du.database_user, du.database_password FROM web_database d LEFT JOIN web_database_user du ON (du.database_user_id = d.database_user_id) INNER JOIN sys_user s on(d.sys_groupid = s.default_group) WHERE client_id = ?";
		$all = $app->db->queryAllRecords($sql, $client_id);
		return $all;
	}
	
	//** backup functions -----------------------------------------------------------------------------------
	public function sites_web_domain_backup_list($session_id, $site_id = null)
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_web_domain_backup')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		$result = $app->db->queryAllRecords("SELECT * FROM web_backup".(($site_id != null)?' WHERE parent_domain_id = ?':''), $site_id);
		return $result;
	}
	
	//* Backup download and restoration by Abdi Joseph
	public function sites_web_domain_backup($session_id, $primary_id, $action_type)
	{
		global $app;
	
		if(!$this->checkPerm($session_id, 'sites_web_domain_backup')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		//*Set variables
		$backup_record = $app->db->queryOneRecord("SELECT * FROM `web_backup` WHERE `backup_id`= ?", $primary_id);
		$server_id = $backup_record['server_id'];
	
		//*Set default action state
		$action_state = "pending";
		$tstamp = time();
	
		//* Basic validation of variables
		if ($server_id <= 0) {
			throw new SoapFault('invalid_backup_id', "Invalid or non existant backup_id $primary_id");
			return false;
		}
	
		if ($action_type != 'backup_download' and $action_type != 'backup_restore' and $action_type != 'backup_delete') {
			throw new SoapFault('invalid_action', "Invalid action_type $action_type");
			return false;
		}
	
		//* Validate instance
		$instance_record = $app->db->queryOneRecord("SELECT * FROM `sys_remoteaction` WHERE `action_param`= ? and `action_type`= ? and `action_state`= ?", $primary_id, $action_type, 'pending');
		if ($instance_record['action_id'] >= 1) {
			throw new SoapFault('duplicate_action', "There is already a pending $action_type action");
			return false;
		}
	
		//* Save the record
		if ($app->db->query("INSERT INTO `sys_remoteaction` SET `server_id` = ?, `tstamp` = ?, `action_type` = ?, `action_param` = ?, `action_state` = ?", $server_id, $tstamp, $action_type, $primary_id, $action_state)) {
			return true;
		} else {
			return false;
		}
	}
	
	//** quota functions -----------------------------------------------------------------------------------
	public function quota_get_by_user($session_id, $client_id)
	{
		global $app;
		$app->uses('quota_lib');
	
		if(!$this->checkPerm($session_id, 'quota_get_by_user')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		return $app->quota_lib->get_quota_data($client_id, false);
	}
	
	public function trafficquota_get_by_user($session_id, $client_id, $lastdays = 0)
	{
		global $app;
		$app->uses('quota_lib');
		
		if(!$this->checkPerm($session_id, 'trafficquota_get_by_user')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if ($client_id != null)
			$client_id = $app->functions->intval($client_id);
		
		return $app->quota_lib->get_trafficquota_data($client_id, $lastdays);
	}
	
	public function ftptrafficquota_data($session_id, $client_id, $lastdays = 0)
	{
		global $app;
		$app->uses('quota_lib');
		
		if(!$this->checkPerm($session_id, 'trafficquota_get_by_user')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		if ($client_id != null)
			$client_id = $app->functions->intval($client_id);
		
		return $app->quota_lib->get_ftptrafficquota_data($client_id, $lastdays);
	}
	
	public function databasequota_get_by_user($session_id, $client_id)
	{
		global $app;
		$app->uses('quota_lib');
	
		if(!$this->checkPerm($session_id, 'databasequota_get_by_user')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
	
		return $app->quota_lib->get_databasequota_data($client_id, false);
	}
	
	// ----------------------------------------------------------------------------------------------------------

	//* Get record details
	public function sites_webdav_user_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'sites_webdav_user_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../sites/form/webdav_user.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function sites_webdav_user_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_webdav_user_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../sites/form/webdav_user.tform.php', $client_id, $params);
	}

	//* Update a record
	public function sites_webdav_user_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'sites_webdav_user_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../sites/form/webdav_user.tform.php', $client_id, $primary_id, $params);
		return $affected_rows;
	}

	//* Delete a record
	public function sites_webdav_user_delete($session_id, $primary_id)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'sites_webdav_user_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}

		$affected_rows = $this->deleteQuery('../sites/form/webdav_user.tform.php', $primary_id);
		return $affected_rows;
	}
	
	
}

?>
