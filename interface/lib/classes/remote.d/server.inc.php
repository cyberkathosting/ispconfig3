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

class remoting_server extends remoting {
    /**
	    Gets the server configuration
	    @param int session id
	    @param int server id
	    @param string  section of the config field in the server table. Could be 'web', 'dns', 'mail', 'dns', 'cron', etc
	    @author Julio Montoya <gugli100@gmail.com> BeezNest 2010
    */
	
	public function server_get_serverid_by_ip($session_id, $ipaddress)
    {
        global $app;
		if(!$this->checkPerm($session_id, 'server_get_serverid_by_ip')) {
        	throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
            return false;
		}
        $sql = "SELECT server_id FROM server_ip WHERE ip_address  = '$ipaddress' LIMIT 1 ";
        $all = $app->db->queryAllRecords($sql);
        return $all;
	}
	
	//* Add a IP address record
	public function server_ip_add($session_id, $client_id, $params)
    {
		if(!$this->checkPerm($session_id, 'server_ip_add')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		return $this->insertQuery('../admin/form/server_ip.tform.php',$client_id,$params);
	}
	
	//* Update IP address record
	public function server_ip_update($session_id, $client_id, $ip_id, $params)
    {
		if(!$this->checkPerm($session_id, 'server_ip_update')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->updateQuery('../admin/form/server_ip.tform.php',$client_id,$ip_id,$params);
		return $affected_rows;
	}
	
	//* Delete IP address record
	public function server_ip_delete($session_id, $ip_id)
    {
		if(!$this->checkPerm($session_id, 'server_ip_delete')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		$affected_rows = $this->deleteQuery('../admin/form/server_ip.tform.php',$ip_id);
		return $affected_rows;
	}
}

?>