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

class remoting_domains extends remoting {
	// -----------------------------------------------------------------------------------------------

	//* Get record details
	public function domains_domain_get($session_id, $primary_id)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'domains_domain_get')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$app->uses('remoting_lib');
		$app->remoting_lib->loadFormDef('../client/form/domain.tform.php');
		return $app->remoting_lib->getDataRecord($primary_id);
	}

	//* Add a record
	public function domains_domain_add($session_id, $client_id, $params)
	{
		if(!$this->checkPerm($session_id, 'domains_domain_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../client/form/domain.tform.php', $client_id, $params);
	}

	//* Update a record
	public function domains_domain_update($session_id, $client_id, $primary_id, $params)
	{
		if(!$this->checkPerm($session_id, 'domains_domain_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->updateQuery('../client/form/domain.tform.php', $client_id, $primary_id, $params);
	}

	//* Delete a record
	public function domains_domain_delete($session_id, $primary_id)
	{
		if(!$this->checkPerm($session_id, 'domains_domain_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../client/form/domain.tform.php', $primary_id);
		return $affected_rows;
	}

	// -----------------------------------------------------------------------------------------------

	public function domains_get_all_by_user($session_id, $group_id)
	{
		global $app;
		if(!$this->checkPerm($session_id, 'domains_get_all_by_user')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$group_id = $app->functions->intval($group_id);
		$sql = "SELECT domain_id, domain FROM domain WHERE sys_groupid  = ?";
		$all = $app->db->queryAllRecords($sql, $group_id);
		return $all;
	}

}

?>
