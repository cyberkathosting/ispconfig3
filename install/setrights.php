<?php
/*
Copyright (c) 2007-2011, Till Brehm, projektfarm Gmbh and Oliver Vogel , Meins und Vogel
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

/*
	ISPConfig 3 Set Rights. Tries to correct the rights of the clients if they are wrong
*/

error_reporting(E_ALL|E_STRICT);

//** The banner on the command line
echo "\n\n".str_repeat('-', 80)."\n";
echo " _____ ___________   _____              __ _         ____
|_   _/  ___| ___ \ /  __ \            / _(_)       /__  \
  | | \ `--.| |_/ / | /  \/ ___  _ __ | |_ _  __ _    _/ /
  | |  `--. \  __/  | |    / _ \| '_ \|  _| |/ _` |  |_ |
 _| |_/\__/ / |     | \__/\ (_) | | | | | | | (_| | ___\ \
 \___/\____/\_|      \____/\___/|_| |_|_| |_|\__, | \____/
                                              __/ |
                                             |___/ ";
echo "\n".str_repeat('-', 80)."\n";
echo "\n\n>>This script tries to repair the client rights  \n\n";

//** Include the library with the basic installer functions
require_once 'lib/install.lib.php';

//** Include the library with the basic updater functions
require_once 'lib/update.lib.php';

//** Include the base class of the installer class
require_once 'lib/installer_base.lib.php';

//** Ensure that current working directory is install directory
$cur_dir = getcwd();
if(realpath(dirname(__FILE__)) != $cur_dir) die("Please run installation/update from _inside_ the install directory!\n");

//** Get distribution identifier
$dist = get_distname();

include_once "/usr/local/ispconfig/server/lib/config.inc.php";
$conf_old = $conf;
unset($conf);

if($dist['id'] == '') die('Linux distribution or version not recognized.');

//** Include the distribution-specific installer class library and configuration
if(is_file('dist/lib/'.$dist['baseid'].'.lib.php')) include_once 'dist/lib/'.$dist['baseid'].'.lib.php';
include_once 'dist/lib/'.$dist['id'].'.lib.php';
include_once 'dist/conf/'.$dist['id'].'.conf.php';

//** Get hostname
exec('hostname -f', $tmp_out);
$conf['hostname'] = $tmp_out[0];
unset($tmp_out);

//* Check if this is a master / slave setup
$conf['mysql']['master_slave_setup'] = 'n';
if($conf["mysql"]["master_host"] != '' && $conf["mysql"]["host"] != $conf["mysql"]["master_host"]) {
	$conf['mysql']['master_slave_setup'] = 'y';
}

/*
 * Try to read the DB-admin settings
 */
$clientdb_host   = '';
$clientdb_user   = '';
$clientdb_password  = '';
include_once "/usr/local/ispconfig/server/lib/mysql_clientdb.conf";
$conf["mysql"]["admin_user"] = $clientdb_user;
$conf["mysql"]["admin_password"] = $clientdb_password;
$clientdb_host   = '';
$clientdb_user   = '';
$clientdb_password  = '';

//** There is a error if user for mysql admin_password if empty
if( empty($conf["mysql"]["admin_password"]) ) {
	die("internal error - MYSQL-Root passord not known");
}

$inst = new installer();

//** Initialize the MySQL server connection
include_once 'lib/mysql.lib.php';

//* initialize the database
$inst->db = new db();

/*
 * The next line is a bit tricky!
 * At the automated update we have no connection to the master-db (we don't need it, because
 * there are only TWO points, where this is needed)
 * 1) update the rights --> the autoupdater sets the rights of all clients when the server is
 *    autoupdated)
 * 2) update the server-settings (is web installed, is mail installed) --> the autoupdates
 *    doesn't change any of this settings, so there ist no need to update this.
 * This means, the autoupdater did not need any connection to the master-db (only to the local bd
 * of the master-server). To avoid any problems, we set the master-db to the local one.
 */
$inst->dbmaster = $inst->db;

/*
 * If it is NOT a master-slave - Setup then we are at the Master-DB. So set all rights
*/
if($conf['mysql']['master_slave_setup'] != 'y') {
	$inst->grant_master_database_rights(true);
}

echo "finished.\n";

?>
