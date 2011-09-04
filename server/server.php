<?php
/*
  Copyright (c) 2007-2011, Till Brehm, projektfarm Gmbh
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

define('SCRIPT_PATH', dirname($_SERVER["SCRIPT_FILENAME"]));
require(SCRIPT_PATH."/lib/config.inc.php");
require(SCRIPT_PATH."/lib/app.inc.php");

set_time_limit(0);
ini_set('error_reporting', E_ALL & ~E_NOTICE);

// make sure server_id is always an int
$conf['server_id'] = intval($conf['server_id']);

/*
  // Get server record, if updates where available for this server
  $server_db_record = $app->db->queryOneRecord("SELECT * FROM server WHERE update = 1 AND server_id = ".$conf['server_id']);
  if($server_db_record == false) {
  $app->log('Nothing to update for server_id '.$conf['server_id']);
  die();
  } else {
  // Set update status to 0, so we dont start the update process twice
  $app->db->query("UPDATE server SET update = 0 WHERE server_id = ".$conf['server_id']);
  $app->log('Begin update.');
  }
 */

/*
 * Try to Load the server configuration from the master-db
 */
if ($app->dbmaster->connect()) {
	$server_db_record = $app->dbmaster->queryOneRecord("SELECT * FROM server WHERE server_id = " . $conf['server_id']);

	$conf['last_datalog_id'] = (int) $server_db_record['updated'];
	$conf['mirror_server_id'] = (int) $server_db_record['mirror_server_id'];

	// Load the ini_parser
	$app->uses('ini_parser');

	// Get server configuration
	$conf['serverconfig'] = $app->ini_parser->parse_ini_string(stripslashes($server_db_record['config']));

	// Set the loglevel
	$conf['log_priority'] = intval($conf['serverconfig']['server']['loglevel']);

	// we do not need this variable anymore
	unset($server_db_record);
	
	/*
	 * Save the rescue-config, maybe we need it (because the database is down)
	 */
	$tmp['serverconfig']['server']['loglevel'] = $conf['log_priority'];
	$tmp['serverconfig']['rescue'] = $conf['serverconfig']['rescue'];
	file_put_contents(dirname(__FILE__) . "/temp/rescue_module_serverconfig.ser.txt", serialize($tmp));
	unset($tmp);

	// protect the file
	chmod(dirname(__FILE__) . "/temp/rescue_module_serverconfig.ser.txt", 0600);
	
} else {
	/*
	 * The master-db is not available.
	 * Problem: because we need to start the rescue-module (to rescue the DB if this IS the
	 * server, the master-db is running at) we have to initialize some config...
	 */
	
	/*
	 * If there is a temp-file with the data we could get from the database, then we use it
	 */
	$tmp = array();
	if (file_exists(dirname(__FILE__) . "/temp/rescue_module_serverconfig.ser.txt")){
		$tmp = unserialize(file_get_contents(dirname(__FILE__) . "/temp/rescue_module_serverconfig.ser.txt"));
	}
	
	// maxint at 32 and 64 bit systems
	$conf['last_datalog_id'] = intval('9223372036854775807'); 

	// no mirror
	$conf['mirror_server_id'] = 0; 

	// Set the loglevel 
	$conf['log_priority'] = (isset($tmp['serverconfig']['server']['loglevel']))? $tmp['serverconfig']['server']['loglevel'] : LOGLEVEL_ERROR;
	/*
	 * Set the configuration to rescue the database
	 */
	if (isset($tmp['serverconfig']['rescue'])){
		$conf['serverconfig']['rescue'] = $tmp['serverconfig']['rescue'];
	}
	else{
		$conf['serverconfig']['rescue']['try_rescue'] = 'n';
	}
	// we do not need this variable anymore
	unset($tmp);
}


// Check whether another instance of this script is already running
if (is_file($conf['temppath'] . $conf['fs_div'] . '.ispconfig_lock')) {
	clearstatcache();
	for ($i = 0; $i < 120; $i++) { // Wait max. 1200 sec, then retry
		if (is_file($conf['temppath'] . $conf['fs_div'] . '.ispconfig_lock')) {
			exec("ps aux | grep '/usr/local/ispconfig/server/[s]erver.php' | wc -l", $check);
			if (intval($check[0]) > 1) { // 1 because this is 2nd instance!
				$app->log('There is already an instance of server.php running. Exiting.', LOGLEVEL_DEBUG);
				exit;
			}
			$app->log('There is already a lockfile set. Waiting another 10 seconds...', LOGLEVEL_DEBUG);
			sleep(10);
			clearstatcache();
		}
	}
}

// Set Lockfile
@touch($conf['temppath'] . $conf['fs_div'] . '.ispconfig_lock');
$app->log('Set Lock: ' . $conf['temppath'] . $conf['fs_div'] . '.ispconfig_lock', LOGLEVEL_DEBUG);

/** Do we need to start the core-modules */
$needStartCore = true;

/*
 * Next we try to process the datalog
 */
if ($app->db->connect() && $app->dbmaster->connect()) {

	// Check if there is anything to update
	if ($conf['mirror_server_id'] > 0) {
		$tmp_rec = $app->dbmaster->queryOneRecord("SELECT count(server_id) as number from sys_datalog WHERE datalog_id > " . $conf['last_datalog_id'] . " AND (server_id = " . $conf['server_id'] . " OR server_id = " . $conf['mirror_server_id'] . " OR server_id = 0)");
	} else {
		$tmp_rec = $app->dbmaster->queryOneRecord("SELECT count(server_id) as number from sys_datalog WHERE datalog_id > " . $conf['last_datalog_id'] . " AND (server_id = " . $conf['server_id'] . " OR server_id = 0)");
	}

	$tmp_num_records = $tmp_rec['number'];
	unset($tmp_rec);

	if ($tmp_num_records > 0) {
		/*
		  There is something to do, triggert by the database -> do it!
		 */
		// Write the Log
		$app->log("Found $tmp_num_records changes, starting update process.", LOGLEVEL_DEBUG);
		// Load required base-classes
		$app->uses('modules,plugins,file,services');
		// Load the modules that are in the mods-enabled folder
		$app->modules->loadModules('all');
		// Load the plugins that are in the plugins-enabled folder
		$app->plugins->loadPlugins('all');
		// Go through the sys_datalog table and call the processing functions
		// from the modules that are hooked on to the table actions
		$app->modules->processDatalog();
		// Restart services that need to after configuration
		$app->services->processDelayedActions();
		// All modules are already loaded and processed, so there is NO NEED to load the core once again...
		$needStartCore = false;
	}
} else {
	if (!$app->db->connect()) {
		$app->log('Unable to connect to local server.' . $app->db->errorMessage, LOGLEVEL_WARN);
	} else {
		$app->log('Unable to connect to master server.' . $app->dbmaster->errorMessage, LOGLEVEL_WARN);
	}
}

/*
 * Under normal circumstances the system was loaded and all updates are done.
 * but if we do not have to update anything or if the database is not accessible, then we
 * have to start the core-system (if the database is accessible, we need the core because of the
 * monitoring. If the databse is NOT accessible, we need the core because of rescue the db...
 */
if ($needStartCore) {
	// Write the log
	$app->log('No Updated records found, starting only the core.', LOGLEVEL_DEBUG);
	// Load required base-classes
	$app->uses('modules,plugins,file,services');
	// Load the modules that are im the mods-core folder
	$app->modules->loadModules('core');
	// Load the plugins that are in the plugins-core folder
	$app->plugins->loadPlugins('core');
}


// Remove lock
@unlink($conf['temppath'] . $conf['fs_div'] . '.ispconfig_lock');
$app->log('Remove Lock: ' . $conf['temppath'] . $conf['fs_div'] . '.ispconfig_lock', LOGLEVEL_DEBUG);


die("finished.\n");
?>
