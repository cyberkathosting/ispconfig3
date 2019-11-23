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
	function loadModules($type = 'all') {
		global $app, $conf;

		$subPath = 'mods-enabled';
		if ($type == 'core') {
			$subPath = 'mods-core';
		} elseif ($type == 'all') {
			$type = '';
		} elseif (!preg_match('/^\w+$/', $type)) {
			$app->log('Invalid loadModules type ' . $type, LOGLEVEL_ERROR);
			return false;
		} else {
			$subPath = 'mods-available';
		}

		$loaded = false;
		$modules_dir = $conf['rootpath'].$conf['fs_div'].$subPath.$conf['fs_div'];
		if (is_dir($modules_dir)) {
			if ($dh = opendir($modules_dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..' && substr($file, -8, 8) == '.inc.php') {
						$module_name = substr($file, 0, -8);
						if($type && $type !== 'core' && $type != $module_name) {
							continue;
						}
						$loaded = true;
						include_once $modules_dir.$file;
						if($this->debug) $app->log('Loading Module: '.$module_name, LOGLEVEL_DEBUG);
						$app->loaded_modules[$module_name] = new $module_name;
						$app->loaded_modules[$module_name]->onLoad();
					}
				}
			}
		} else {
			$app->log('Modules directory missing: '.$modules_dir, LOGLEVEL_ERROR);
		}

		if($type && $type !== 'core' && $loaded === false) {
			$app->log('Module ' . $type . ' not found.', LOGLEVEL_ERROR);
		}
	}

	/*
	 This function is called by the modules to register for a specific
	 table change notification
	*/

	function registerTableHook($table_name, $module_name, $function_name) {
		global $app;
		$this->notification_hooks[$table_name][] = array('module' => $module_name, 'function' => $function_name);
		if($this->debug) $app->log("Registered TableHook '$table_name' in module '$module_name' for processing function '$function_name'", LOGLEVEL_DEBUG);
	}

	/*
	 This function goes through all new records in the
	 sys_datalog table and and calls the function in the
	 modules that hooked on to the table change.
	*/

	function processDatalog() {
		global $app, $conf;

		//* If its a multiserver setup
		if($app->db->dbHost != $app->dbmaster->dbHost || ($app->db->dbHost == $app->dbmaster->dbHost && $app->db->dbName != $app->dbmaster->dbName)) {
			if($conf['mirror_server_id'] > 0) {
				$sql = "SELECT * FROM sys_datalog WHERE datalog_id > ? AND (server_id = ? OR server_id = ? OR server_id = 0) ORDER BY datalog_id LIMIT 0,1000";
			} else {
				$sql = "SELECT * FROM sys_datalog WHERE datalog_id > ? AND (server_id = ? OR server_id = 0) ORDER BY datalog_id LIMIT 0,1000";
			}

			$records = $app->dbmaster->queryAllRecords($sql, $conf['last_datalog_id'], $conf['server_id'], $conf['mirror_server_id']);
			foreach($records as $d) {

				//** encode data to utf-8 and unserialize it
				if(!$data = unserialize(stripslashes($d['data']))) {
					$data = unserialize($d['data']);
				}
				//** Decode data back to locale
				/*
				foreach($data['old'] as $key => $val) {
					$data['old'][$key] = utf8_decode($val);
				}
				foreach($data['new'] as $key => $val) {
					$data['new'][$key] = utf8_decode($val);
				}
				*/

				$replication_error = false;
				$data['mirrored'] = false;

				$this->current_datalog_id = $d['datalog_id'];

				/*
				* If we are in a mirror setup, rewrite the server_id of records that originally
				* belonged to the mirrored server to the local server_id
				*/

				if($conf['mirror_server_id'] > 0 && $d['dbtable'] != 'server') {
					if(isset($data['new']['server_id']) && $data['new']['server_id'] == $conf['mirror_server_id']) {
						$data['new']['server_id'] = $conf['server_id'];
						$data['mirrored'] = true;
					}
					if(isset($data['old']['server_id']) && $data['old']['server_id'] == $conf['mirror_server_id']) {
						$data['old']['server_id'] = $conf['server_id'];
						$data['mirrored'] = true;
					}
				}

				if(count($data['new']) > 0) {
					if($d['action'] == 'i' || $d['action'] == 'u') {
						$idx = explode(':', $d['dbidx']);
						$tmp_sql1 = '';
						$tmp_sql2 = '';
						$f_params = array($d['dbtable']);
						$params = array();
						foreach($data['new'] as $fieldname => $val) {
							$tmp_sql1 .= "??,";
							$tmp_sql2 .= "?,";
							$f_params[] = $fieldname;
							$params[] = $val;
						}
						$params = array_merge($f_params, $params);
						unset($f_params);
						
						$tmp_sql1 = substr($tmp_sql1, 0, -1);
						$tmp_sql2 = substr($tmp_sql2, 0, -1);
						//$tmp_sql1 .= "$idx[0]";
						//$tmp_sql2 .= "$idx[1]";
						$sql = "REPLACE INTO ?? ($tmp_sql1) VALUES ($tmp_sql2)";
						$app->db->errorNumber = 0;
						$app->db->errorMessage = '';
						$app->db->query($sql, true, $params);
						if($app->db->errorNumber > 0) {
							$replication_error = true;
							$app->log("Replication failed. Error: (" . $d['dbtable'] . ") in MySQL server: (".$app->db->dbHost.") " . $app->db->errorMessage . " # SQL: " . $sql, LOGLEVEL_ERROR);
						}
						$log = $app->db->_build_query_string($sql, true, $params);
						$app->log('Replicated from master: '.$log, LOGLEVEL_DEBUG);
						unset($params);
						unset($log);
					}
					
					if($d['action'] == 'd') {
						$idx = explode(':', $d['dbidx']);
						$sql = "DELETE FROM ?? ";
						$sql .= " WHERE ?? = ?";
						$app->db->query($sql, $d['dbtable'], $idx[0], $idx[1]);
						if($app->db->errorNumber > 0) {
							$replication_error = true;
							$app->log("Replication failed. Error: (" . $d[dbtable] . ") " . $app->db->errorMessage . " # SQL: " . $sql, LOGLEVEL_ERROR);
						}
						$log = $app->db->_build_query_string($sql, $d['dbtable'], $idx[0], $idx[1]);
						$app->log('Replicated from master: '.$log, LOGLEVEL_DEBUG);
						unset($log);
					}


					if($replication_error == false) {
						if(is_array($data['old']) || is_array($data['new'])) {
							$app->db->query("UPDATE server SET updated = ? WHERE server_id = ?", $d["datalog_id"], $conf['server_id']);
							$this->raiseTableHook($d['dbtable'], $d['action'], $data);
						} else {
							$app->log('Data array was empty for datalog_id '.$d['datalog_id'], LOGLEVEL_WARN);
						}
						$app->dbmaster->query("UPDATE server SET updated = ? WHERE server_id = ?", $d["datalog_id"], $conf['server_id']);
						$app->log('Processed datalog_id '.$d['datalog_id'], LOGLEVEL_DEBUG);
					} else {
						$app->log('Error in Replication, changes were not processed.', LOGLEVEL_ERROR);
						/*
						 * If there is any error in processing the datalog we can't continue, because
						 * we do not know if the newer actions require this (old) one.
						 */
						return;
					}
				} else {
					$app->log('Datalog does not contain any changes for this record '.$d['datalog_id'], LOGLEVEL_DEBUG);
				}
			}

			//* if we have a single server setup
		} else {
			$sql = "SELECT * FROM sys_datalog WHERE datalog_id > ? AND (server_id = ? OR server_id = 0) ORDER BY datalog_id LIMIT 0,1000";
			$records = $app->db->queryAllRecords($sql, $conf['last_datalog_id'], $conf['server_id']);
			foreach($records as $d) {

				//** encode data to utf-8 to be able to unserialize it and then unserialize it
				if(!$data = unserialize(stripslashes($d['data']))) {
					$data = unserialize($d['data']);
				}

				//* Data on a single server is never mirrored
				$data['mirrored'] = false;

				$this->current_datalog_id = $d['datalog_id'];
				if(is_array($data['old']) || is_array($data['new'])) {
					$this->raiseTableHook($d['dbtable'], $d['action'], $data);
				} else {
					$app->log('Data array was empty for datalog_id '.$d['datalog_id'], LOGLEVEL_WARN);
				}
				$app->db->query("UPDATE server SET updated = ? WHERE server_id = ?", $d['datalog_id'], $conf['server_id']);
				$app->log('Processed datalog_id '.$d['datalog_id'], LOGLEVEL_DEBUG);
			}
		}
	}

	function processActions() {
		global $app, $conf;

		//* get the server_id of the local server
		$server_id = intval($conf["server_id"]);

		include_once SCRIPT_PATH."/lib/remote_action.inc.php";

		//* SQL query to get all pending actions
		$sql = "SELECT action_id, action_type, action_param " .
			"FROM sys_remoteaction " .
			"WHERE server_id = ? ".
			" AND  action_id > ? ".
			"ORDER BY action_id";

		$actions = $app->dbmaster->queryAllRecords($sql, $server_id, $maxid_remote_action);

		if(is_array($actions)) {
			foreach($actions as $action) {

				//* Raise the action
				$state = $app->plugins->raiseAction($action['action_type'], $action['action_param']);

				//* Update the action state
				$sql = "UPDATE sys_remoteaction " .
					"SET action_state = ? " .
					"WHERE action_id = ?";
				$app->dbmaster->query($sql, $state, $action['action_id']);

				/*
				* Then save the maxid for the next time...
				*/
				$fp = fopen(ISPC_LIB_PATH."/remote_action.inc.php", 'wb');
				$content = '<?php' . "\n" . '$maxid_remote_action = ' . $action['action_id'] . ';' . "\n?>";
				fwrite($fp, $content);
				fclose($fp);
			}
		}



	}

	function raiseTableHook($table_name, $action, $data) {
		global $app;

		// Get the hooks for this table
		$hooks = (isset($this->notification_hooks[$table_name]))?$this->notification_hooks[$table_name]:'';
		if($this->debug) $app->log("Raised TableHook for table: '$table_name'", LOGLEVEL_DEBUG);

		if(is_array($hooks)) {
			foreach($hooks as $hook) {
				$module_name = $hook['module'];
				$function_name = $hook['function'];
				// Call the processing function of the module
				if($this->debug) $app->log("Call function '$function_name' in module '$module_name' raised by TableHook '$table_name'.", LOGLEVEL_DEBUG);
				// call_user_method($function_name,$app->loaded_modules[$module_name],$table_name,$action,$data);
				call_user_func(array($app->loaded_modules[$module_name], $function_name), $table_name, $action, $data);
				unset($module_name);
				unset($function_name);
			}
		}
		unset($hook);
		unset($hooks);
	}

}

?>
