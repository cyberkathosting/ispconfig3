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


//** ISPConfig 3 installer.
//** The banner on the command line
echo "\n\n".str_repeat('-',80)."\n";
echo " _____ ___________   _____              __ _       
|_   _/  ___| ___ \ /  __ \            / _(_)      
  | | \ `--.| |_/ / | /  \/ ___  _ __ | |_ _  __ _ 
  | |  `--. \  __/  | |    / _ \| '_ \|  _| |/ _` |
 _| |_/\__/ / |     | \__/\ (_) | | | | | | | (_| |
 \___/\____/\_|      \____/\___/|_| |_|_| |_|\__, |
                                              __/ |
                                             |___/ ";
echo "\n".str_repeat('-',80)."\n";
echo "\n\n>> Update  \n\n";


//** Include the library with the basic installer functions
require_once('lib/install.lib.php');

//** Include the base class of the installer class
require_once('lib/installer_base.lib.php');

//** Installer/updater logfile
define('ISPC_LOG_FILE', '/var/log/ispconfig_install.log');

//** Check for ISPConfig 2.x versions
if(is_dir('/root/ispconfig') || is_dir('/home/admispconfig')) {
	die('This software can not be installed on a server wich runs ISPConfig 2.x.');
}

//** Get distribution identifier
$distname = get_distname();

include_once("/usr/local/ispconfig/server/lib/config.inc.php");
$conf_old = $conf;
unset($conf);

//** Include the distribution specific installer class library and configuration
include_once('dist/lib/'.$distname.'.lib.php');
include_once('dist/conf/'.$distname.'.conf.php');

//** Set the mysql login information
$conf["mysql"]["host"] = $conf_old["db_host"];
$conf["mysql"]["database"] = $conf_old["db_database"];
$conf["mysql"]["ispconfig_user"] = $conf_old["db_user"];
$conf["mysql"]["ispconfig_password"] = $conf_old["db_password"];

$inst = new installer();

echo "This application will update ISPConfig 3 on your server.\n";

//** TODO: all other queries, for testing I will setup everything in $conf

//** Initialize the MySQL server connection
include_once('lib/mysql.lib.php');

//** Database update is a bit brute force and should be rebuild later ;)

//** Ask user for mysql admin_password if empty
if( empty($conf["mysql"]["admin_password"]) ) {

	$conf["mysql"]["admin_password"] = $inst->free_query('MySQL root password', $conf['mysql']['admin_password']);
}

//** export the current database data
if( !empty($conf["mysql"]["admin_password"]) ) {

	system("mysqldump -h ".$conf['mysql']['host']." -u ".$conf['mysql']['admin_user']." -p".$conf['mysql']['admin_password']." -c -t --add-drop-table --all --quick ".$conf['mysql']['database']." > existing_db.sql");
}
else {

	system("mysqldump -h ".$conf['mysql']['host']." -u ".$conf['mysql']['admin_user']." -c -t --add-drop-table --all --quick ".$conf['mysql']['database']." > existing_db.sql");
}

//** Delete the old database
$inst->db = new db();

if( !$inst->db->query('DROP DATABASE IF EXISTS '.$conf['mysql']['database']) ) {

	$inst->error('Unable to drop MySQL database: '.$conf['mysql']['database'].'.');
}

//** Create the mysql database
$inst->configure_database();

//** empty all databases
$db_tables = $inst->db->getTables();

foreach($db_tables as $table) {

	$inst->db->query("TRUNCATE $table");
}

//** load old data back into database
if( !empty($conf["mysql"]["admin_password"]) ) {

	system("mysql -h ".$conf['mysql']['host']." -u ".$conf['mysql']['admin_user']." -p".$conf['mysql']['admin_password']." ".$conf['mysql']['database']." < existing_db.sql");
} else {

	system("mysql -h ".$conf['mysql']['host']." -u ".$conf['mysql']['admin_user']." ".$conf['mysql']['database']." < existing_db.sql");
}

//** Configure postfix
$inst->configure_postfix('dont-create-certs');

//** Configure saslauthd
swriteln('Configuring SASL');
$inst->configure_saslauthd();

//** Configure PAM
swriteln('Configuring PAM');
$inst->configure_pam();

//** Configure courier
swriteln('Configuring Courier');
$inst->configure_courier();

//** Configure Spamasassin
swriteln('Configuring Spamassassin');
$inst->configure_spamassassin();

//** Configure Amavis
swriteln('Configuring Amavisd');
$inst->configure_amavis();

//** Configure Getmail
swriteln('Configuring Getmail');
$inst->configure_getmail();

//** Configure Pureftpd
swriteln('Configuring Pureftpd');
$inst->configure_pureftpd();

//** Configure MyDNS
swriteln('Configuring MyDNS');
$inst->configure_mydns();

//** Configure Apache
swriteln('Configuring Apache');
$inst->configure_apache();

//** Configure ISPConfig
swriteln('Installing ISPConfig');
$inst->install_ispconfig();

//** Configure ISPConfig
swriteln('Installing Crontab');
$inst->install_crontab();

//** Restart services:
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
system("/etc/init.d/mydns restart");

echo "Update finished.\n";

?>
