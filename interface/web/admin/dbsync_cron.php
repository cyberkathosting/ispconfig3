<?php
/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

$dbsync_list = $app->db->queryAllRecords("SELECT * FROM sys_dbsync WHERE active = 1");

if(is_array($dbsync_list)) {
	foreach($dbsync_list as $dbsync) {
		// erstelle database connection
		
		$db = new db();
		$db->dbHost = $dbsync["db_host"];
		$db->dbName = $dbsync["db_name"];
		$db->dbUser = $dbsync["db_username"];
		$db->dbPass = $dbsync["db_password"];
		
		// hole neue Einträge aus dblog
		$datalog_records = $app->db->queryAllRecords("SELECT * FROM sys_datalog WHERE dbtable in ('".str_replace(",","','",$dbsync["db_tables"])."') AND datalog_id > $dbsync[last_datalog_id]");
		foreach($datalog_records as $d) {
			
			if($d["action"] == 'n') {
				$data = unserialize(stripslashes($d["data"]));
				$idx = explode(":",$d["dbidx"]);
				$tmp_sql1 = '';
				$tmp_sql2 = '';
				foreach($data as $fieldname => $val) {
					$tmp_sql1 .= "$fieldname,";
					$tmp_sql2 .= "'$val[new]',";
				}
				$tmp_sql1 .= "$idx[0]";
				$tmp_sql2 .= "$idx[1]";
				$sql = "INSERT INTO $d[dbtable] ($tmp_sql1) VALUES ($tmp_sql2)";
				$db->query($sql);
			}
			
			if($d["action"] == 'u') {
				$sql = "UPDATE $d[dbtable] SET ";
				$data = unserialize(stripslashes($d["data"]));
				foreach($data as $fieldname => $val) {
					$sql .= "$fieldname = '$val[new]',";
				}
				$sql = substr($sql,0,-1);
				$idx = explode(":",$d["dbidx"]);
				$sql .= " WHERE $idx[0] = $idx[1]";
				$db->query($sql);
			}
			
			if($d["action"] == 'd') {
				$idx = explode(":",$d["dbidx"]);
				$sql = "DELETE FROM $d[dbtable] SET ";
				$sql .= " WHERE $idx[0] = $idx[1]";
				$db->query($sql);
			}
			
			if($dbsync["sync_datalog_external"] == 1) {
				$sql = "INSERT INTO sys_datalog (dbtable,dbidx,action,tstamp,user,data) VALUES ('$d[dbtable]','$d[dbidx]','$d[action]','".time()."','$d[user]','$d[data]')";
				$db->query($sql);
			}
			
			if($dbsync["empty_datalog"] == 1) {
				$sql = "DELETE FROM sys_datalog WHERE datalog_id = $d[datalog_id]";
				$app->db->query($sql);
			}
			
			$app->db->query("UPDATE sys_dbsync SET last_datalog_id = $d[datalog_id] WHERE id = $dbsync[id]");
			
			echo "Synchronisiere: $d[dbtable]:$d[dbidx]<br>";
			
		}
	}
}

?>