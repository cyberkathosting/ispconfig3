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

require("lib/config.inc.php");
require("lib/app.inc.php");

set_time_limit(0);
ini_set('error_reporting','E_ALL & ~E_NOTICE');

// make sure server_id is always an int
$conf["server_id"] = intval($conf["server_id"]);

/*
// Get server record, if updates where available for this server
$server_db_record = $app->db->queryOneRecord("SELECT * FROM server WHERE update = 1 AND server_id = ".$conf["server_id"]);
if($server_db_record == false) {
	$app->log("Nothing to update for server_id ".$conf["server_id"]);
	die();
} else {
	// Set update status to 0, so we dont start the update process twice
	$app->db->query("UPDATE server SET update = 0 WHERE server_id = ".$conf["server_id"]);
	$app->log("Begin update.");
}
*/


// Check if another process is running
if(is_file($conf["temppath"].$conf["fs_div"].".ispconfig_lock")){
  clearstatcache();
  for($i=0;$i<120;$i++){ // Wait max. 1200 sec, then proceed
    if(is_file($conf["temppath"].$conf["fs_div"].".ispconfig_lock")){
	  $app->log("There is already a lockfile set. Waiting another 10 seconds...", LOGLEVEL_DEBUG);
      sleep(10);
      clearstatcache();
    }
  }
}

// Set Lockfile
@touch($conf["temppath"].$conf["fs_div"].".ispconfig_lock");
$app->log("Set Lock: ".$conf["temppath"].$conf["fs_div"].".ispconfig_lock", LOGLEVEL_DEBUG);


if($app->db->connect() && $app->dbmaster->connect()) {

	// get the dalaog_id of the last performed record
	$server_db_record = $app->dbmaster->queryOneRecord("SELECT updated, config FROM server WHERE server_id = ".$conf["server_id"]);
	$conf['last_datalog_id'] = (int)$server_db_record['updated'];

	// Check if there is anything to update
	$tmp_rec = $app->dbmaster->queryOneRecord("SELECT count(server_id) as number from sys_datalog WHERE datalog_id > ".$conf['last_datalog_id']." AND (server_id = ".$conf["server_id"]." OR server_id = 0)");
	$tmp_num_records = $tmp_rec["number"];
	unset($tmp_rec);

	if($tmp_num_records > 0) {
		/*
	 	There is something to do, triggert by the database -> do it!
		*/
		// Write the Log
		$app->log("Found $tmp_num_records changes, starting update process.", LOGLEVEL_DEBUG);
		// Load required base-classes
		$app->uses('ini_parser,modules,plugins,file,services');
		// Get server configuration
		$conf["serverconfig"] = $app->ini_parser->parse_ini_string(stripslashes($server_db_record["config"]));
		// Load the modules that are im the mods-enabled folder
		$app->modules->loadModules('all');
		// Load the plugins that are in the plugins-enabled folder
		$app->plugins->loadPlugins('all');
		// Go trough the sys_datalog table and call the processing functions
		// in the modules that are hooked on to the table actions
		$app->modules->processDatalog();
		// Restart services that need to be restarted after configuration
		$app->services->processDelayedActions();
	} else {
		/*
	 	There is no trigger inside the database -> load only the core, maybe they have to do something
		*/
		// Write the log
		$app->log('No Updated records found, starting only the core.', LOGLEVEL_DEBUG);
		// Load required base-classes
		$app->uses('ini_parser,modules,plugins,file,services');
		// Get server configuration
		$conf["serverconfig"] = $app->ini_parser->parse_ini_string(stripslashes($server_db_record["config"]));
		// Load the modules that are im the mods-core folder
		$app->modules->loadModules('core');
		// Load the plugins that are in the plugins-core folder
		$app->plugins->loadPlugins('core');
	}
} else {
	if(!$app->db->connect()) {
		$app->log("Unable to connect to local server.".$app->db->errorMessage,LOGLEVEL_WARN);
	} else {
		$app->log("Unable to connect to master server.".$app->dbmaster->errorMessage,LOGLEVEL_WARN);
	}
}

// Remove lock
@unlink($conf["temppath"].$conf["fs_div"].".ispconfig_lock");
$app->log("Remove Lock: ".$conf["temppath"].$conf["fs_div"].".ispconfig_lock",LOGLEVEL_DEBUG);


die("finished.\n");
?>