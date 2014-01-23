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

class remoting_admin extends remoting {
	
	/**
	 * set record permissions in any table
	 * @param string session_id
	 * @param string index_field
	 * @param string index_value
	 * @param array permissions
	 * @author "ispcomm", improved by M. Cramer <m.cramer@pixcept.de>
	 */
	public function update_record_permissions($tablename, $index_field, $index_value, $permissions) {
		global $app;
		
		if(!$this->checkPerm($session_id, 'admin_record_permissions')) {
			$this->server->fault('permission_denied', 'You do not have the permissions to access this function.');
			return false;
		}
		
		foreach($permissions as $key => $value) {  // make sure only sys_ fields are updated
			switch($key) {
				case 'sys_userid':
					// check if userid is valid
					$check = $app->db->queryOneRecord('SELECT userid FROM sys_user WHERE userid = ' . $app->functions->intval($value));
					if(!$check || !$check['userid']) {
						$this->server->fault('invalid parameters', $value . ' is no valid sys_userid.');
						return false;
					}
					$permissions[$key] = $app->functions->intval($value);
					break;
				case 'sys_groupid':
					// check if groupid is valid
					$check = $app->db->queryOneRecord('SELECT groupid FROM sys_group WHERE groupid = ' . $app->functions->intval($value));
					if(!$check || !$check['groupid']) {
						$this->server->fault('invalid parameters', $value . ' is no valid sys_groupid.');
						return false;
					}
					$permissions[$key] = $app->functions->intval($value);
					break;
				case 'sys_perm_user':
				case 'sys_perm_group':
					// check if permissions are valid
					$value = strtolower($value);
					if(!preg_match('/^[riud]+$/', $value)) {
						$this->server->fault('invalid parameters', $value . ' is no valid permission string.');
						return false;
					}
					
					$newvalue = '';
					if(strpos($value, 'r') !== false) $newvalue .= 'r';
					if(strpos($value, 'i') !== false) $newvalue .= 'i';
					if(strpos($value, 'u') !== false) $newvalue .= 'u';
					if(strpos($value, 'd') !== false) $newvalue .= 'd';
					$permissions[$key] = $newvalue;
					unset($newvalue);
					
					break;
				default:
					$this->server->fault('invalid parameters', 'Only sys_userid, sys_groupid, sys_perm_user and sys_perm_group parameters can be changed with this function.');
					break;
			}
		}
		
		return $app->db->datalogUpdate( $tablename, $permissions, $index_field, $index_value ) ;
	}
	

}

?>
