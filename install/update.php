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

/*
	ISPConfig 3 installer.
*/

// Include the library with the basic installer functions
require_once('lib/install.lib.php');

// Include the base class of the installer class
require_once('lib/installer_base.lib.php');

$distname = get_distname();

include_once("/usr/local/ispconfig/server/lib/config.inc.php");
$conf_old = $conf;
unset $conf;

// Include the distribution specific installer class library
// and configuration
include_once('dist/lib/'.$distname.'.lib.php');
include_once('dist/conf/'.$distname.'.conf.php');

// Set the mysql login information
$conf["mysql_server_host"] = $conf_old["db_host"];
$conf["mysql_server_database"] = $conf_old["db_database"];
$conf["mysql_server_ispconfig_user"] = $conf_old["db_user"];
$conf["mysql_server_ispconfig_password"] = $conf_old["db_password"];

$inst = new installer();


echo "This application will update ISPConfig 3 on your server.\n";

// $conf["language"] = $inst->request_language();

// TODO: all other queries, for testing I will setup everything in $conf

// Initialize the MySQL server connection
include_once('lib/mysql.lib.php');
$inst->db = new db();

// Database update is a bit brute force and should be rebuild later ;)

// export the current database data
exec("mysqldump -h $conf[mysql_server_host] -u $conf[mysql_server_ispconfig_user] -p$conf[mysql_server_ispconfig_password] -c -t --add-drop-table --add-locks --all --quick --lock-tables $conf[mysql_server_database] > existing_db.sql  &> /dev/null");

// Delete the old database
exec("/etc/init.d/mysql stop");
exec("rm -rf /var/lib/mysql/".$conf["db_database"]);
exec("/etc/init.d/mysql start");

// Create the mysql database
$inst->configure_database();

// empty all databases
$db_tables = $inst->db->getTables();
foreach($db_tables as $table) {
	$inst->db->query("TRUNCATE $table");
}

// load old data back into database
exec("mysql -h $conf[mysql_server_host] -u $conf[mysql_server_ispconfig_user] -p$conf[mysql_server_ispconfig_password] $conf[mysql_server_database] < existing_db.sql  &> /dev/null");

// Configure postfix
$inst->configure_postfix();

// Configure saslauthd
swriteln('Configuring SASL');
$inst->configure_saslauthd();


// Configure PAM
swriteln('Configuring PAM');
$inst->configure_pam();

// Configure courier
swriteln('Configuring Courier');
$inst->configure_courier();

// Configure Spamasassin
swriteln('Configuring Spamassassin');
$inst->configure_spamassassin();

// Configure Amavis
swriteln('Configuring Amavisd');
$inst->configure_amavis();

// Configure Getmail
swriteln('Configuring Getmail');
$inst->configure_getmail();

// Configure Getmail
swriteln('Configuring Pureftpd');
$inst->configure_pureftpd();

// Configure ISPConfig
swriteln('Installing ISPConfig');
$inst->install_ispconfig();

// Configure ISPConfig
swriteln('Installing Crontab');
$inst->install_crontab();


/*
Restart services:
*/

swriteln('Restarting services ...');
system("/etc/init.d/mysql restart");
system("/etc/init.d/postfix restart");
system("/etc/init.d/saslauthd restart");
system("/etc/init.d/amavis restart");
system("/etc/init.d/clamav-daemon restart");
system("/etc/init.d/courier-authdaemon restart");
system("/etc/init.d/courier-imap restart");
system("/etc/init.d/courier-imap-ssl restart");
system("/etc/init.d/courier-pop restart");
system("/etc/init.d/courier-pop-ssl restart");
system("/etc/init.d/apache2 restart");
system("/etc/init.d/pure-ftpd-mysql restart");

echo "Update finished.\n";


?>