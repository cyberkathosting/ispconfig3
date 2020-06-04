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

/**
 * Prints usage info
 * @author Ramil Valitov <ramilvalitov@gmail.com>
 */
function printUsageInfo(){
    echo <<<EOT
Usage:
	php backup-now.php --id=<4> [--type=<all>]
Options:
	--id		id of the website to backup.
	--type		backup type: all, web or mysql. Default is all.

EOT;
}

/**
 * Makes a backup
 * @param int $domain_id id of the domain
 * @param string $type type: mysql, web or all
 * @return bool true if success
 * @uses backup::run_backup() to make backups
 * @author Ramil Valitov <ramilvalitov@gmail.com>
 */
function makeBackup($domain_id, $type)
{
    global $app;

    echo "Making backup of website id=" . $domain_id . ", type=" . $type . ", please wait...\n";

    // Load required class
    $app->load('backup');

    switch ($type) {
        case "all":
            $success = backup::run_backup($domain_id, "web", "manual");
            $success = $success && backup::run_backup($domain_id, "mysql", "manual");
            break;
        case "mysql":
            $success = backup::run_backup($domain_id, "mysql", "manual");
            break;
        case "web":
            $success = backup::run_backup($domain_id, "web", "manual");
            break;
        default:
            echo "Unknown format=" . $type . "\n";
            printUsageInfo();
            $success = false;
    }
    return $success;
}

//** Get commandline options
$cmd_opt = getopt('', array('id::', 'type::'));
$id = filter_var($cmd_opt['id'], FILTER_VALIDATE_INT);;
if (!isset($cmd_opt['id']) || !is_int($id)) {
    printUsageInfo();
    exit(1);
}

if (isset($cmd_opt['type']) && !empty($cmd_opt['type'])) {
    $type = $cmd_opt['type'];
} else
    $type = "all";

$success = makeBackup($id, $type);

echo "All operations finished, status " . ($success ? "success" : "failed") . ".\n";

exit($success ? 0 : 2);

?>
