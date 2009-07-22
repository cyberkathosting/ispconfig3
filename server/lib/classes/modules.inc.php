<?php

/*
Copyright (c) 2007 - 2009, Till Brehm, projektfarm Gmbh
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
	var $current_datalog_id = 0;
	var $debug = false;
	
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
						if($this->debug) $app->log("Loading Module: $module_name",LOGLEVEL_DEBUG);
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
		if($this->debug) $app->log("Registered TableHook '$table_name' in module '$module_name' for processing function '$function_name'",LOGLEVEL_DEBUG);
	}
	
	/*
	 This function goes through all new records in the
	 sys_datalog table and and calls the function in the
	 modules that hooked on to the table change.
	*/
	
	function processDatalog() {
		global $app,$conf;
		
		//* If its a multiserver setup
		if($app->db->dbHost != $app->dbmaster->dbHost) {
			$sql = "SELECT * FROM sys_datalog WHERE datalog_id > ".$conf['last_datalog_id']." AND (server_id = ".$conf["server_id"]." OR server_id = 0) ORDER BY datalog_id";
			$records = $app->dbmaster->queryAllRecords($sql);
			foreach($records as $d) {
				
				//** encode data to utf-8 and unserialize it
				if(!$data = unserialize(utf8_encode(stripslashes($d["data"])))) {
					$data = unserialize(utf8_encode($d["data"]));
				}
				//** Decode data back to locale
				foreach($data['old'] as $key => $val) {
					$data['old'][$key] = utf8_decode($val);
				}
				foreach($data['new'] as $key => $val) {
					$data['new'][$key] = utf8_decode($val);
				}
				
				$replication_error = false;
				
				$this->current_datalog_id = $d["datalog_id"];
				
				if(count($data['new']) > 0) {
					if($d["action"] == 'i' || $d["action"] == 'u') {
						$idx = explode(":",$d["dbidx"]);
						$tmp_sql1 = '';
						$tmp_sql2 = '';
						foreach($data['new'] as $fieldname => $val) {
							$tmp_sql1 .= "`$fieldname`,";
							$tmp_sql2 .= "'$val',";
						}
						$tmp_sql1 = substr($tmp_sql1,0,-1);
						$tmp_sql2 = substr($tmp_sql2,0,-1);
						//$tmp_sql1 .= "$idx[0]";
						//$tmp_sql2 .= "$idx[1]";
						$sql = "REPLACE INTO $d[dbtable] ($tmp_sql1) VALUES ($tmp_sql2)";
						$app->db->errorNumber = 0;
						$app->db->errorMessage = '';
						$app->db->query($sql);
						if($app->db->errorNumber > 0) {
							$replication_error = true;
							$app->log("Replication failed. Error: (" . $d[dbtable] . ") in mysql server: (".$app->db->dbHost.") " . $app->db->errorMessage . " # SQL: " . $sql,LOGLEVEL_ERROR);
						}
						$app->log("Replicated from master: ".$sql,LOGLEVEL_DEBUG);
					}
					/*
					if($d["action"] == 'u') {
						$sql = "UPDATE $d[dbtable] SET ";
						foreach($data['new'] as $fieldname => $val) {
							$sql .= "`$fieldname` = '$val',";
						}
						$sql = substr($sql,0,-1);
						$idx = explode(":",$d["dbidx"]);
						$sql .= " WHERE $idx[0] = $idx[1]";
						$app->db->query($sql);
						if($app->db->errorNumber > 0) {
							$replication_error = true;
							$app->log("Replication failed. Error: (" . $d[dbtable] . ") " . $app->db->errorMessage . " # SQL: " . $sql,LOGLEVEL_ERROR);
						}
						$app->log("Replicated from master: ".$sql,LOGLEVEL_DEBUG);
					}
					*/
					if($d["action"] == 'd') {
						$idx = explode(":",$d["dbidx"]);
						$sql = "DELETE FROM $d[dbtable] ";
						$sql .= " WHERE $idx[0] = $idx[1]";
						$app->db->query($sql);
						if($app->db->errorNumber > 0) {
							$replication_error = true;
							$app->log("Replication failed. Error: (" . $d[dbtable] . ") " . $app->db->errorMessage . " # SQL: " . $sql,LOGLEVEL_ERROR);
						}
						$app->log("Replicated from master: ".$sql,LOGLEVEL_DEBUG);
					}
				
				
					if($replication_error == false) {
						$this->raiseTableHook($d["dbtable"],$d["action"],$data);
						//$app->dbmaster->query("DELETE FROM sys_datalog WHERE datalog_id = ".$d["datalog_id"]);
						//$app->log("Deleting sys_datalog ID ".$d["datalog_id"],LOGLEVEL_DEBUG);
						$app->dbmaster->query("UPDATE server SET updated = ".$d["datalog_id"]." WHERE server_id = ".$conf["server_id"]);
						$app->log("Processed datalog_id ".$d["datalog_id"],LOGLEVEL_DEBUG);
					} else {
						$app->log("Error in Replication, changes were not processed.",LOGLEVEL_ERROR);
						/*
						 * If there is any error in processing the datalog we can't continue, because
						 * we do not know if the newer actions require this (old) one.
						 */
						return;
					}
				} else {
					$app->log("Datalog does not conatin any changes for this record ".$d["datalog_id"],LOGLEVEL_DEBUG);
				}
			}
			
		//* if we have a single server setup
		} else {
			$sql = "SELECT * FROM sys_datalog WHERE datalog_id > ".$conf['last_datalog_id']." AND (server_id = ".$conf["server_id"]." OR server_id = 0) ORDER BY datalog_id";
			$records = $app->db->queryAllRecords($sql);
			foreach($records as $d) {
				
				//** encode data to utf-8 to be able to unserialize it and then unserialize it
				if(!$data = unserialize(utf8_encode(stripslashes($d["data"])))) {
					$data = unserialize(utf8_encode($d["data"]));
				}
				//** decode data back to current locale
				foreach($data['old'] as $key => $val) {
					$data['old'][$key] = utf8_decode($val);
				}
				foreach($data['new'] as $key => $val) {
					$data['new'][$key] = utf8_decode($val);
				}
				
				$this->current_datalog_id = $d["datalog_id"];
				$this->raiseTableHook($d["dbtable"],$d["action"],$data);
				//$app->db->query("DELETE FROM sys_datalog WHERE datalog_id = ".$rec["datalog_id"]);
				//$app->log("Deleting sys_datalog ID ".$rec["datalog_id"],LOGLEVEL_DEBUG);
				$app->db->query("UPDATE server SET updated = ".$d["datalog_id"]." WHERE server_id = ".$conf["server_id"]);
				$app->log("Processed datalog_id ".$d["datalog_id"],LOGLEVEL_DEBUG);
			}
		}
		
		
		
		
		
	}
	
	function raiseTableHook($table_name,$action,$data) {
		global $app;
		
		// Get the hooks for this table
		$hooks = (isset($this->notification_hooks[$table_name]))?$this->notification_hooks[$table_name]:'';
		if($this->debug) $app->log("Raised TableHook for table: '$table_name'",LOGLEVEL_DEBUG);
		
		if(is_array($hooks)) {
			foreach($hooks as $hook) {
				$module_name = $hook["module"];
				$function_name = $hook["function"];
				// Claa the processing function of the module
				if($this->debug) $app->log("Call function '$function_name' in module '$module_name' raised by TableHook '$table_name'.",LOGLEVEL_DEBUG);
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