<?php
/*
Copyright (c) 2007-2010, Till Brehm, projektfarm Gmbh
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
	ISPConfig 3 updater.
*/

error_reporting(E_ALL|E_STRICT);

/*
 * If the auto-updater flag is not on (the file does not exist) then cancel the auto-update!
*/
if (!file_exists('autoupdate')) {

	//** The banner on the command line
	echo "\n\n".str_repeat('-',80)."\n";
	echo " _____ ___________   _____              __ _         ____
|_   _/  ___| ___ \ /  __ \            / _(_)       /__  \
  | | \ `--.| |_/ / | /  \/ ___  _ __ | |_ _  __ _    _/ /
  | |  `--. \  __/  | |    / _ \| '_ \|  _| |/ _` |  |_ |
 _| |_/\__/ / |     | \__/\ (_) | | | | | | | (_| | ___\ \
 \___/\____/\_|      \____/\___/|_| |_|_| |_|\__, | \____/
                                              __/ |
                                             |___/ ";
	echo "\n".str_repeat('-',80)."\n";
	echo "\n\n>>This script is for internal use only! Please use update.php!  \n\n";
	exit;
}

//** Include the library with the basic installer functions
require_once('lib/install.lib.php');

//** Include the library with the basic updater functions
require_once('lib/update.lib.php');

//** Include the base class of the installer class
require_once('lib/installer_base.lib.php');

//** Ensure that current working directory is install directory
$cur_dir = getcwd();
if(realpath(dirname(__FILE__)) != $cur_dir) die("Please run installation/update from _inside_ the install directory!\n");

//** Install logfile
define('ISPC_LOG_FILE', '/var/log/ispconfig_install.log');
define('ISPC_INSTALL_ROOT', realpath(dirname(__FILE__).'/../'));

//** Get distribution identifier
$dist = get_distname();

include_once("/usr/local/ispconfig/server/lib/config.inc.php");
$conf_old = $conf;
unset($conf);

if($dist['id'] == '') die('Linux distribution or version not recognized.');

//** Include the distribution-specific installer class library and configuration
if(is_file('dist/lib/'.$dist['baseid'].'.lib.php')) include_once('dist/lib/'.$dist['baseid'].'.lib.php');
include_once('dist/lib/'.$dist['id'].'.lib.php');
include_once('dist/conf/'.$dist['id'].'.conf.php');

//** Get hostname
exec('hostname -f', $tmp_out);
$conf['hostname'] = $tmp_out[0];
unset($tmp_out);

//** Set the mysql login information
$conf["mysql"]["host"] = $conf_old["db_host"];
$conf["mysql"]["database"] = $conf_old["db_database"];
$conf['mysql']['charset'] = 'utf8';
$conf["mysql"]["ispconfig_user"] = $conf_old["db_user"];
$conf["mysql"]["ispconfig_password"] = $conf_old["db_password"];
$conf['language'] = $conf_old['language'];
if($conf['language'] == '{language}') $conf['language'] = 'en';

if(isset($conf_old["dbmaster_host"])) $conf["mysql"]["master_host"] = $conf_old["dbmaster_host"];
if(isset($conf_old["dbmaster_database"])) $conf["mysql"]["master_database"] = $conf_old["dbmaster_database"];
if(isset($conf_old["dbmaster_user"])) $conf["mysql"]["master_ispconfig_user"] = $conf_old["dbmaster_user"];
if(isset($conf_old["dbmaster_password"])) $conf["mysql"]["master_ispconfig_password"] = $conf_old["dbmaster_password"];

//* Check if this is a master / slave setup
$conf['mysql']['master_slave_setup'] = 'n';
if($conf["mysql"]["master_host"] != '' && $conf["mysql"]["host"] != $conf["mysql"]["master_host"]) {
	$conf['mysql']['master_slave_setup'] = 'y';
}

// Resolve the IP address of the mysql hostname.
if(!$conf['mysql']['ip'] = gethostbyname($conf['mysql']['host'])) die('Unable to resolve hostname'.$conf['mysql']['host']);

$conf['server_id'] = intval($conf_old["server_id"]);
$conf['ispconfig_log_priority'] = $conf_old["log_priority"];

$inst = new installer();
$inst->is_update = true;

//** Detect the installed applications
$inst->find_installed_apps();

//** Initialize the MySQL server connection
include_once('lib/mysql.lib.php');

//** Database update is a bit brute force and should be rebuild later ;)

/*
 * Try to read the DB-admin settings
 */
$clientdb_host			= '';
$clientdb_user			= '';
$clientdb_password		= '';
include_once("/usr/local/ispconfig/server/lib/mysql_clientdb.conf");
$conf["mysql"]["admin_user"] = $clientdb_user;
$conf["mysql"]["admin_password"] = $clientdb_password;
$clientdb_host			= '';
$clientdb_user			= '';
$clientdb_password		= '';

//** There is a error if user for mysql admin_password if empty
if( empty($conf["mysql"]["admin_password"]) ) {
	die("internal error - MYSQL-Root passord not known");
}

/*
 *  Prepare the dump of the database 
*/
prepareDBDump();

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
	$inst->grant_master_database_rights();
}

/*
 *  dump the new Database and reconfigure the server.ini
 */
updateDbAndIni();

/*
 * Reconfigure all Services
 */
if($conf['services']['mail'] == true) {
	//** Configure postfix
	swriteln('Configuring Postfix');
	$inst->configure_postfix('dont-create-certs');
	
	//** Configure mailman
	swriteln('Configuring Mailman');
	$inst->configure_mailman('update');

	//* Configure Jailkit
	swriteln('Configuring Jailkit');
	$inst->configure_jailkit();

	if($conf['dovecot']['installed'] == true) {
		//* Configure dovecot
		swriteln('Configuring Dovecot');
		$inst->configure_dovecot();
	} else {
		//** Configure saslauthd
		swriteln('Configuring SASL');
		$inst->configure_saslauthd();

		//** Configure PAM
		swriteln('Configuring PAM');
		$inst->configure_pam();
		
		//* Configure courier
		swriteln('Configuring Courier');
		$inst->configure_courier();
	}

	//** Configure Spamasassin
	swriteln('Configuring Spamassassin');
	$inst->configure_spamassassin();

	//** Configure Amavis
	swriteln('Configuring Amavisd');
	$inst->configure_amavis();

	//** Configure Getmail
	swriteln('Configuring Getmail');
	$inst->configure_getmail();
}

if($conf['services']['web'] == true) {
	//** Configure Pureftpd
	swriteln('Configuring Pureftpd');
	$inst->configure_pureftpd();
}

if($conf['services']['dns'] == true) {
	//* Configure DNS
	if($conf['powerdns']['installed'] == true) {
		swriteln('Configuring PowerDNS');
		$inst->configure_powerdns();
	} elseif($conf['bind']['installed'] == true) {
		swriteln('Configuring BIND');
		$inst->configure_bind();
	} else {
		swriteln('Configuring MyDNS');
		$inst->configure_mydns();
	}
}

if($conf['services']['web'] == true) {
	//** Configure Apache
	swriteln('Configuring Apache');
	$inst->configure_apache();

	//** Configure vlogger
	swriteln('Configuring vlogger');
	$inst->configure_vlogger();

	//** Configure apps vhost
	swriteln('Configuring Apps vhost');
	$inst->configure_apps_vhost();
}


//* Configure DBServer
swriteln('Configuring Database');
$inst->configure_dbserver();


//if(@is_dir('/etc/Bastille')) {
//* Configure Firewall
swriteln('Configuring Firewall');
$inst->configure_firewall();
//}

//** Configure ISPConfig
swriteln('Updating ISPConfig');


//** Customise the port ISPConfig runs on
$conf['apache']['vhost_port'] = get_ispconfig_port_number();

$inst->install_ispconfig();

//** Configure Crontab
swriteln('Updating Crontab');
$inst->install_crontab();

//** Restart services:
swriteln('Restarting services ...');
if($conf['mysql']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['mysql']['init_script']))					system($conf['init_scripts'].'/'.$conf['mysql']['init_script'].' reload');
if($conf['services']['mail']) {
	if($conf['postfix']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['postfix']['init_script']))				system($conf['init_scripts'].'/'.$conf['postfix']['init_script'].' restart');
	if($conf['saslauthd']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['saslauthd']['init_script']))			system($conf['init_scripts'].'/'.$conf['saslauthd']['init_script'].' restart');
	if($conf['amavis']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['amavis']['init_script']))					system($conf['init_scripts'].'/'.$conf['amavis']['init_script'].' restart');
	if($conf['clamav']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['clamav']['init_script']))					system($conf['init_scripts'].'/'.$conf['clamav']['init_script'].' restart');
	if($conf['courier']['courier-authdaemon'] != '' && is_executable($conf['init_scripts'].'/'.$conf['courier']['courier-authdaemon'])) system($conf['init_scripts'].'/'.$conf['courier']['courier-authdaemon'].' restart');
	if($conf['courier']['courier-imap'] != '' && is_executable($conf['init_scripts'].'/'.$conf['courier']['courier-imap'])) 			system($conf['init_scripts'].'/'.$conf['courier']['courier-imap'].' restart');
	if($conf['courier']['courier-imap-ssl'] != '' && is_executable($conf['init_scripts'].'/'.$conf['courier']['courier-imap-ssl'])) 	system($conf['init_scripts'].'/'.$conf['courier']['courier-imap-ssl'].' restart');
	if($conf['courier']['courier-pop'] != '' && is_executable($conf['init_scripts'].'/'.$conf['courier']['courier-pop'])) 				system($conf['init_scripts'].'/'.$conf['courier']['courier-pop'].' restart');
	if($conf['courier']['courier-pop-ssl'] != '' && is_executable($conf['init_scripts'].'/'.$conf['courier']['courier-pop-ssl'])) 		system($conf['init_scripts'].'/'.$conf['courier']['courier-pop-ssl'].' restart');
	if($conf['dovecot']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['dovecot']['init_script'])) 		system($conf['init_scripts'].'/'.$conf['dovecot']['init_script'].' restart');
	if($conf['mailman']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['mailman']['init_script'])) 		system($conf['init_scripts'].'/'.$conf['mailman']['init_script'].' restart');
}
if($conf['services']['web']) {
	if($conf['apache']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['apache']['init_script'])) 				system($conf['init_scripts'].'/'.$conf['apache']['init_script'].' restart');
	if($conf['pureftpd']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['pureftpd']['init_script']))				system($conf['init_scripts'].'/'.$conf['pureftpd']['init_script'].' restart');
}
if($conf['services']['dns']) {
	if($conf['mydns']['installed'] == true && $conf['mydns']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['mydns']['init_script']))					system($conf['init_scripts'].'/'.$conf['mydns']['init_script'].' restart &> /dev/null');
	if($conf['powerdns']['installed'] == true && $conf['powerdns']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['powerdns']['init_script']))					system($conf['init_scripts'].'/'.$conf['powerdns']['init_script'].' restart &> /dev/null');
	if($conf['bind']['installed'] == true && $conf['bind']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['bind']['init_script']))					system($conf['init_scripts'].'/'.$conf['bind']['init_script'].' restart &> /dev/null');
}

echo "Update finished.\n";

?>
