<?php

/*
Copyright (c) 2007-2016, Till Brehm, projektfarm Gmbh
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
require SCRIPT_PATH."/lib/config.inc.php";
require SCRIPT_PATH."/lib/app.inc.php";

set_time_limit(0);
ini_set('error_reporting', E_ALL & ~E_NOTICE);

// make sure server_id is always an int
$conf['server_id'] = intval($conf['server_id']);

// Load required base-classes
$app->uses('ini_parser,file,services,getconf,system,cron,functions');
$app->load('libdatetime,cronjob');

// Path settings
$path = SCRIPT_PATH . '/lib/classes/cron.d';

//** Get commandline options
$cmd_opt = getopt('', array('cronjob::', 'force', 'firstrun'));

if(isset($cmd_opt['cronjob']) && is_file($path.'/'.$cmd_opt['cronjob'])) {
	// Cronjob that shell be run
	$cronjob_file = $cmd_opt['cronjob'];
} else {
	echo "Usage example: php cron_debug.php --cronjob=100-mailbox_stats.inc.php [--force] [--firstrun]\n" ;
	echo "Available cronjobs:\n";
	foreach(glob($path.'/*-*.inc.php') as $cronjob) {
		echo basename($cronjob)."\n";
	}
	die();
}

// Load and run the cronjob
$name = substr($cronjob_file, 0, strpos($cronjob_file, '.'));
if(preg_match('/^\d+\-(.*)$/', $name, $match)) $name = $match[1]; // strip numerical prefix from file name
include $path . '/' . $cronjob_file;
$class_name = 'cronjob_' . $name;
$cronjob = new $class_name();

if(isset($cmd_opt['force'])) {
	$app->db->query("UPDATE `sys_cron` SET `running` = 0 WHERE `name` = ?", $class_name);
}

$cronjob->onPrepare();
$cronjob->onBeforeRun(isset($cmd_opt['firstrun']));
if(!$cronjob->isRunning()) {
	$app->db->query("UPDATE `sys_cron` SET `running` = ? WHERE `name` = ?", ($cronjob->canBeRunInParallel() !== true ? "1" : "0"), $class_name);
	$cronjob->onRunJob();
	$cronjob->onAfterRun();
	$cronjob->onCompleted();
}

die("finished.\n");

?>