<?php

/*
Copyright (c) 2017, Till Brehm, ISPConfig UG
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

//* Remote functions of the monitor module
class remoting_monitor extends remoting {

	//* get the number of pending jobs from jobqueue
	public function monitor_jobqueue_count($session_id, $server_id = 0)
	{
		global $app;

		if(!$this->checkPerm($session_id, 'monitor_jobqueue_count')) {
			throw new SoapFault('permission_denied', 'You do not have the permissions to access this function.');
		}
		
		$server_id = intval($server_id);
		
		if($server_id == 0) {
			$servers = $app->db->queryAllRecords("SELECT server_id, updated FROM server");
			$sql = 'SELECT count(datalog_id) as jobqueue_count FROM sys_datalog WHERE ';
			foreach($servers as $sv) {
				$sql .= " (datalog_id > ".$sv['updated']." AND server_id IN (0,".$sv['server_id'].")) OR ";
			}
			$sql = substr($sql, 0, -4);
			$tmp = $app->db->queryOneRecord($sql);
			return $tmp['jobqueue_count'];
			
		} else {
			$server = $app->db->queryOneRecord("SELECT updated FROM server WHERE server_id = ?",$server_id);
			$tmp = $app->db->queryOneRecord('SELECT count(datalog_id) as jobqueue_count FROM sys_datalog WHERE datalog_id > ? AND server_id IN ?',$server['updated'], array(0, $server_id));
			return $tmp['jobqueue_count'];
		}
	}

}

?>
