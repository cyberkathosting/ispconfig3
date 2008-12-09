<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

class modules {
	
	var $notification_hooks = array();
	
	/*
	 This function is called to load the modules from the mods-enabled or the mods-core folder
	*/
	function loadModules($type) {
		global $app, $conf;
		
		$subPath = 'mods-enabled';
		if ($type == 'core') $subPath = 'mods-core';

		$modules_dir = $conf["rootpath"].$conf["fs_div"].$subPath.$conf["fs_div"];
		if (is_dir($modules_dir)) {
			if ($dh = opendir($modules_dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..' && substr($file,-8,8) == '.inc.php') {
						$module_name = substr($file,0,-8);
						include_once($modules_dir.$file);
						$app->log("Loading Module: $module_name",LOGLEVEL_DEBUG);
						$app->loaded_modules[$module_name] = new $module_name;
						$app->loaded_modules[$module_name]->onLoad();
					}
				}
			}
		} else {
			$app->log("Modules directory missing: $modules_dir",LOGLEVEL_ERROR);
		}
		
	}
	
	/*
	 This function is called by the modules to register for a specific
	 table change notification
	*/
	
	function registerTableHook($table_name,$module_name,$function_name) {
		global $app;
		$this->notification_hooks[$table_name][] = array('module' => $module_name, 'function' => $function_name);
		$app->log("Registered TableHook '$table_name' in module '$module_name' for processing function '$function_name'",LOGLEVEL_DEBUG);
	}
	
	/*
	 This function goes through all new records in the
	 sys_datalog table and and calls the function in the
	 modules that hooked on to the table change.
	*/
	
	function processDatalog() {
		global $app,$conf;
		
		// TODO: process only new entries.
		//* If its a multiserver setup
		if($app->db->dbHost != $app->dbmaster->dbHost) {
			$sql = "SELECT * FROM sys_datalog WHERE server_id = ".$conf["server_id"]." ORDER BY datalog_id";
			$records = $app->dbmaster->queryAllRecords($sql);
			foreach($records as $d) {
				
				$data = unserialize(stripslashes($d["data"]));
				$replication_error = false;
				
				if($d["action"] == 'i') {
					$idx = explode(":",$d["dbidx"]);
					$tmp_sql1 = '';
					$tmp_sql2 = '';
					foreach($data['new'] as $fieldname => $val) {
						$tmp_sql1 .= "$fieldname,";
						$tmp_sql2 .= "'$val',";
					}
					$tmp_sql1 = substr($tmp_sql1,0,-1);
					$tmp_sql2 = substr($tmp_sql2,0,-1);
					//$tmp_sql1 .= "$idx[0]";
					//$tmp_sql2 .= "$idx[1]";
					$sql = "INSERT INTO $d[dbtable] ($tmp_sql1) VALUES ($tmp_sql2)";
					$app->db->query($sql);
					if($app->db->errorNumber > 0) {
						$replication_error = true;
						$app->log("Replication failed. Error: ".$app->db->errorMessage,LOGLEVEL_ERROR);
					}
					$app->log("Replicated from master: ".$sql,LOGLEVEL_DEBUG);
				}
			
				if($d["action"] == 'u') {
					$sql = "UPDATE $d[dbtable] SET ";
					foreach($data['new'] as $fieldname => $val) {
						$sql .= "$fieldname = '$val',";
					}
					$sql = substr($sql,0,-1);
					$idx = explode(":",$d["dbidx"]);
					$sql .= " WHERE $idx[0] = $idx[1]";
					$app->db->query($sql);
					if($app->db->errorNumber > 0) {
						$replication_error = true;
						$app->log("Replication failed. Error: ".$app->db->errorMessage,LOGLEVEL_ERROR);
					}
					$app->log("Replicated from master: ".$sql,LOGLEVEL_DEBUG);
				}
			
				if($d["action"] == 'd') {
					$idx = explode(":",$d["dbidx"]);
					$sql = "DELETE FROM $d[dbtable] ";
					$sql .= " WHERE $idx[0] = $idx[1]";
					$app->db->query($sql);
					if($app->db->errorNumber > 0) {
						$replication_error = true;
						$app->log("Replication failed. Error: ".$app->db->errorMessage,LOGLEVEL_ERROR);
					}
					$app->log("Replicated from master: ".$sql,LOGLEVEL_DEBUG);
				}
				
				
				if($replication_error == false) {
					$this->raiseTableHook($d["dbtable"],$d["action"],$data);
					$app->dbmaster->query("DELETE FROM sys_datalog WHERE datalog_id = ".$d["datalog_id"]);
					$app->log("Deleting sys_datalog ID ".$d["datalog_id"],LOGLEVEL_DEBUG);
					$app->db->query("UPDATE sys_datalog SET status = 'ok' WHERE datalog_id = ".$d["datalog_id"]);
				} else {
					$app->log("Error in Repliction, changes were not processed.",LOGLEVEL_ERROR);
					$app->db->query("UPDATE sys_datalog SET status = 'error' WHERE datalog_id = ".$d["datalog_id"]);
				}
			}
			
		//* if we have a single server setup
		} else {
			$sql = "SELECT * FROM sys_datalog WHERE server_id = ".$conf["server_id"]." ORDER BY datalog_id";
			$records = $app->db->queryAllRecords($sql);
			foreach($records as $rec) {
				$data = unserialize(stripslashes($rec["data"]));
				$this->raiseTableHook($rec["dbtable"],$rec["action"],$data);
				$app->db->query("DELETE FROM sys_datalog WHERE datalog_id = ".$rec["datalog_id"]);
				$app->log("Deleting sys_datalog ID ".$rec["datalog_id"],LOGLEVEL_DEBUG);
				$app->db->query("UPDATE sys_datalog SET status = 'ok' WHERE datalog_id = ".$rec["datalog_id"]);
			}
		}
		
		
		
		
		
	}
	
	function raiseTableHook($table_name,$action,$data) {
		global $app;
		
		// Get the hooks for this table
		$hooks = $this->notification_hooks[$table_name];
		$app->log("Raised TableHook for table: '$table_name'",LOGLEVEL_DEBUG);
		
		if(is_array($hooks)) {
			foreach($hooks as $hook) {
				$module_name = $hook["module"];
				$function_name = $hook["function"];
				// Claa the processing function of the module
				$app->log("Call function '$function_name' in module '$module_name' raised by TableHook '$table_name'.",LOGLEVEL_DEBUG);
				call_user_method($function_name,$app->loaded_modules[$module_name],$table_name,$action,$data);
				unset($module_name);
				unset($function_name);
			}
		}
		unset($hook);
		unset($hooks);
	}
	
}

?>