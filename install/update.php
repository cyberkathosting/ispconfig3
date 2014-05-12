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

define('INSTALLER_RUN', true);

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
echo "\n\n>> Update  \n\n";

//** Include the library with the basic installer functions
require_once 'lib/install.lib.php';

//** Include the library with the basic updater functions
require_once 'lib/update.lib.php';

//** Include the base class of the installer class
require_once 'lib/installer_base.lib.php';

//** Ensure that current working directory is install directory
$cur_dir = getcwd();
if(realpath(dirname(__FILE__)) != $cur_dir) die("Please run installation/update from _inside_ the install directory!\n");

//** Install logfile
define('ISPC_LOG_FILE', '/var/log/ispconfig_install.log');
define('ISPC_INSTALL_ROOT', realpath(dirname(__FILE__).'/../'));

//** Include the templating lib
require_once 'lib/classes/tpl.inc.php';

//** Check for ISPConfig 2.x versions
if(is_dir('/root/ispconfig') || is_dir('/home/admispconfig')) {
	die('This software cannot be installed on a server wich runs ISPConfig 2.x.');
}

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

//** Set the mysql login information
$conf["mysql"]["host"] = $conf_old["db_host"];
$conf["mysql"]["database"] = $conf_old["db_database"];
$conf['mysql']['charset'] = 'utf8';
$conf["mysql"]["ispconfig_user"] = $conf_old["db_user"];
$conf["mysql"]["ispconfig_password"] = $conf_old["db_password"];
$conf['language'] = $conf_old['language'];
$conf['theme'] = $conf_old['theme'];
if($conf['language'] == '{language}') $conf['language'] = 'en';
$conf['timezone'] = (isset($conf_old['timezone']))?$conf_old['timezone']:'UTC';
if($conf['timezone'] == '{timezone}' or trim($conf['timezone']) == '') $conf['timezone'] = 'UTC';
$conf['language_file_import_enabled'] = (isset($conf_old['language_file_import_enabled']))?$conf_old['language_file_import_enabled']:true;

if(isset($conf_old["dbmaster_host"])) $conf["mysql"]["master_host"] = $conf_old["dbmaster_host"];
if(isset($conf_old["dbmaster_database"])) $conf["mysql"]["master_database"] = $conf_old["dbmaster_database"];
if(isset($conf_old["dbmaster_user"])) $conf["mysql"]["master_ispconfig_user"] = $conf_old["dbmaster_user"];
if(isset($conf_old["dbmaster_password"])) $conf["mysql"]["master_ispconfig_password"] = $conf_old["dbmaster_password"];

//* Check if this is a master / slave setup
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

echo "This application will update ISPConfig 3 on your server.\n\n";

//* Make a backup before we start the update
$do_backup = $inst->simple_query('Shall the script create a ISPConfig backup in /var/backup/ now?', array('yes', 'no'), 'yes');
if($do_backup == 'yes') {

	//* Create the backup directory
	$backup_path = '/var/backup/ispconfig_'.@date('Y-m-d_H-i');
	$conf['backup_path'] = $backup_path;
	exec("mkdir -p $backup_path");
	exec("chown root:root $backup_path");
	exec("chmod 700 $backup_path");

	//* Do the backup
	swriteln('Creating backup of "/usr/local/ispconfig" directory...');
	exec("tar pcfz $backup_path/ispconfig_software.tar.gz /usr/local/ispconfig 2> /dev/null", $out, $returnvar);
	if($returnvar != 0) die("Backup failed. We stop here...\n");

	swriteln('Creating backup of "/etc" directory...');
	exec("tar pcfz $backup_path/etc.tar.gz /etc 2> /dev/null", $out, $returnvar);
	if($returnvar != 0) die("Backup failed. We stop here...\n");

	exec("chown root:root $backup_path/*.tar.gz");
	exec("chmod 700 $backup_path/*.tar.gz");
}


//** Initialize the MySQL server connection
include_once 'lib/mysql.lib.php';

//** Database update is a bit brute force and should be rebuild later ;)

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

//** Test mysql root connection
$finished = false;
do {
	if(@mysql_connect($conf["mysql"]["host"], $conf["mysql"]["admin_user"], $conf["mysql"]["admin_password"])) {
		$finished = true;
	} else {
		swriteln($inst->lng('Unable to connect to mysql server').' '.mysql_error());
		$conf["mysql"]["admin_password"] = $inst->free_query('MySQL root password', $conf['mysql']['admin_password']);
	}
} while ($finished == false);
unset($finished);

/*
 *  Prepare the dump of the database
 */
prepareDBDump();

//* initialize the database
$inst->db = new db();

//* initialize the master DB, if we have a multiserver setup
if($conf['mysql']['master_slave_setup'] == 'y') {
	//** Get MySQL root credentials
	$finished = false;
	do {
		$tmp_mysql_server_host = $inst->free_query('MySQL master server hostname', $conf['mysql']['master_host']);
		$tmp_mysql_server_admin_user = $inst->free_query('MySQL master server root username', $conf['mysql']['master_admin_user']);
		$tmp_mysql_server_admin_password = $inst->free_query('MySQL master server root password', $conf['mysql']['master_admin_password']);
		$tmp_mysql_server_database = $inst->free_query('MySQL master server database name', $conf['mysql']['master_database']);

		//* Initialize the MySQL server connection
		if(@mysql_connect($tmp_mysql_server_host, $tmp_mysql_server_admin_user, $tmp_mysql_server_admin_password)) {
			$conf['mysql']['master_host'] = $tmp_mysql_server_host;
			$conf['mysql']['master_admin_user'] = $tmp_mysql_server_admin_user;
			$conf['mysql']['master_admin_password'] = $tmp_mysql_server_admin_password;
			$conf['mysql']['master_database'] = $tmp_mysql_server_database;
			$finished = true;
		} else {
			swriteln($inst->lng('Unable to connect to mysql server').' '.mysql_error());
		}
	} while ($finished == false);
	unset($finished);

	// initialize the connection to the master database
	$inst->dbmaster = new db();
	if($inst->dbmaster->linkId) $inst->dbmaster->closeConn();
	$inst->dbmaster->dbHost = $conf['mysql']["master_host"];
	$inst->dbmaster->dbName = $conf['mysql']["master_database"];
	$inst->dbmaster->dbUser = $conf['mysql']["master_admin_user"];
	$inst->dbmaster->dbPass = $conf['mysql']["master_admin_password"];
} else {
	$inst->dbmaster = $inst->db;
}

/*
 *  Check all tables
*/
checkDbHealth();

/*
 *  dump the new Database and reconfigure the server.ini
 */
updateDbAndIni();

/*
 * Reconfigure the permisson if needed
 * (if this is done at client side, only this client is updated.
 * If this is done at server side, all clients are updated.
 */
//if($conf_old['dbmaster_user'] != '' or $conf_old['dbmaster_host'] != '') {
//** Update master database rights
$reconfigure_master_database_rights_answer = $inst->simple_query('Reconfigure Permissions in master database?', array('yes', 'no'), 'no');

if($reconfigure_master_database_rights_answer == 'yes') {
	$inst->grant_master_database_rights();
}
//}

//** Shall the services be reconfigured during update
$reconfigure_services_answer = $inst->simple_query('Reconfigure Services?', array('yes', 'no'), 'yes');

if($reconfigure_services_answer == 'yes') {

	if($conf['services']['mail']) {
		//** Configure postfix
		swriteln('Configuring Postfix');
		$inst->configure_postfix('dont-create-certs');

		//** Configure mailman
		if($conf['mailman']['installed'] == true) {
			swriteln('Configuring Mailman');
			$inst->configure_mailman('update');
		}

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
		if($conf['amavis']['installed'] == true) {
			swriteln('Configuring Amavisd');
			$inst->configure_amavis();
		}

		//** Configure Getmail
		swriteln('Configuring Getmail');
		$inst->configure_getmail();
	}

	if($conf['services']['web'] && $conf['pureftpd']['installed'] == true) {
		//** Configure Pureftpd
		swriteln('Configuring Pureftpd');
		$inst->configure_pureftpd();
	}

	if($conf['services']['dns']) {
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

	if($conf['services']['web']) {
		if($conf['webserver']['server_type'] == 'apache'){
			//** Configure Apache
			swriteln('Configuring Apache');
			$inst->configure_apache();

			//** Configure vlogger
			swriteln('Configuring vlogger');
			$inst->configure_vlogger();
		} else {
			//** Configure nginx
			swriteln('Configuring nginx');
			$inst->configure_nginx();
		}

		//** Configure apps vhost
		swriteln('Configuring Apps vhost');
		$inst->configure_apps_vhost();
	}


	//* Configure DBServer
	swriteln('Configuring Database');
	$inst->configure_dbserver();


	if($conf['services']['firewall']) {
		if($conf['ufw']['installed'] == true) {
			//* Configure Ubuntu Firewall
			$conf['services']['firewall'] = true;
			swriteln('Configuring Ubuntu Firewall');
			$inst->configure_ufw_firewall();
		} else {
			//* Configure Bastille Firewall
			swriteln('Configuring Bastille Firewall');
			$inst->configure_bastille_firewall();
		}
	}

	/*
	if($conf['squid']['installed'] == true) {
		swriteln('Configuring Squid');
		$inst->configure_squid();
	} else if($conf['nginx']['installed'] == true) {
		swriteln('Configuring Nginx');
		$inst->configure_nginx();
	}
	*/
}

//** Configure ISPConfig
swriteln('Updating ISPConfig');
if($conf['apache']['installed'] == true){
	if(!is_file($conf['apache']['vhost_conf_dir'].'/ispconfig.vhost')) $inst->install_ispconfig_interface = false;
}
if($conf['nginx']['installed'] == true){
	if(!is_file($conf['nginx']['vhost_conf_dir'].'/ispconfig.vhost')) $inst->install_ispconfig_interface = false;
}

if ($conf['services']['web'] && $inst->install_ispconfig_interface) {
	//** Customise the port ISPConfig runs on
	$ispconfig_port_number = get_ispconfig_port_number();
	if($conf['webserver']['server_type'] == 'nginx'){
		$conf['nginx']['vhost_port'] = $inst->free_query('ISPConfig Port', $ispconfig_port_number);
	} else {
		$conf['apache']['vhost_port'] = $inst->free_query('ISPConfig Port', $ispconfig_port_number);
	}


	// $ispconfig_ssl_default = (is_ispconfig_ssl_enabled() == true)?'y':'n';
	if(strtolower($inst->simple_query('Create new ISPConfig SSL certificate', array('yes', 'no'), 'no')) == 'yes') {
		$inst->make_ispconfig_ssl_cert();
	}
}

$inst->install_ispconfig();

//** Configure Crontab
$update_crontab_answer = $inst->simple_query('Reconfigure Crontab?', array('yes', 'no'), 'yes');
if($update_crontab_answer == 'yes') {
	swriteln('Updating Crontab');
	$inst->install_crontab();
}

//** Restart services:
if($reconfigure_services_answer == 'yes') {
	swriteln('Restarting services ...');
	if($conf['mysql']['installed'] == true && $conf['mysql']['init_script'] != '') system($inst->getinitcommand($conf['mysql']['init_script'], 'restart'));
	if($conf['services']['mail']) {
		if($conf['postfix']['installed'] == true && $conf['postfix']['init_script'] != '') system($inst->getinitcommand($conf['postfix']['init_script'], 'restart'));
		if($conf['saslauthd']['installed'] == true && $conf['saslauthd']['init_script'] != '') system($inst->getinitcommand($conf['saslauthd']['init_script'], 'restart'));
		if($conf['amavis']['installed'] == true && $conf['amavis']['init_script'] != '') system($inst->getinitcommand($conf['amavis']['init_script'], 'restart'));
		if($conf['clamav']['installed'] == true && $conf['clamav']['init_script'] != '') system($inst->getinitcommand($conf['clamav']['init_script'], 'restart'));
		if($conf['courier']['installed'] == true){
			if($conf['courier']['courier-authdaemon'] != '') system($inst->getinitcommand($conf['courier']['courier-authdaemon'], 'restart'));
			if($conf['courier']['courier-imap'] != '') system($inst->getinitcommand($conf['courier']['courier-imap'], 'restart'));
			if($conf['courier']['courier-imap-ssl'] != '') system($inst->getinitcommand($conf['courier']['courier-imap-ssl'], 'restart'));
			if($conf['courier']['courier-pop'] != '') system($inst->getinitcommand($conf['courier']['courier-pop'], 'restart'));
			if($conf['courier']['courier-pop-ssl'] != '') system($inst->getinitcommand($conf['courier']['courier-pop-ssl'], 'restart'));
		}
		if($conf['dovecot']['installed'] == true && $conf['dovecot']['init_script'] != '') system($inst->getinitcommand($conf['dovecot']['init_script'], 'restart'));
		if($conf['mailman']['installed'] == true && $conf['mailman']['init_script'] != '') system('nohup '.$inst->getinitcommand($conf['mailman']['init_script'], 'restart').' >/dev/null 2>&1 &');
	}
	if($conf['services']['web']) {
		if($conf['webserver']['server_type'] == 'apache' && $conf['apache']['init_script'] != '') system($inst->getinitcommand($conf['apache']['init_script'], 'restart'));
		//* Reload is enough for nginx
		if($conf['webserver']['server_type'] == 'nginx'){
			if($conf['nginx']['php_fpm_init_script'] != '') system($inst->getinitcommand($conf['nginx']['php_fpm_init_script'], 'reload'));
			if($conf['nginx']['init_script'] != '') system($inst->getinitcommand($conf['nginx']['init_script'], 'reload'));
		}
		if($conf['pureftpd']['installed'] == true && $conf['pureftpd']['init_script'] != '') system($inst->getinitcommand($conf['pureftpd']['init_script'], 'restart'));
	}
	if($conf['services']['dns']) {
		if($conf['mydns']['installed'] == true && $conf['mydns']['init_script'] != '') system($inst->getinitcommand($conf['mydns']['init_script'], 'restart').' &> /dev/null');
		if($conf['powerdns']['installed'] == true && $conf['powerdns']['init_script'] != '') system($inst->getinitcommand($conf['powerdns']['init_script'], 'restart').' &> /dev/null');
		if($conf['bind']['installed'] == true && $conf['bind']['init_script'] != '') system($inst->getinitcommand($conf['bind']['init_script'], 'restart').' &> /dev/null');
	}

	if($conf['services']['proxy']) {
		// if($conf['squid']['installed'] == true && $conf['squid']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['squid']['init_script']))     system($conf['init_scripts'].'/'.$conf['squid']['init_script'].' restart &> /dev/null');
		if($conf['nginx']['installed'] == true && $conf['nginx']['init_script'] != '') system($inst->getinitcommand($conf['nginx']['init_script'], 'restart').' &> /dev/null');
	}

	if($conf['services']['firewall']) {
		if($conf['ufw']['installed'] == true && $conf['ufw']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['ufw']['init_script']))     system($conf['init_scripts'].'/'.$conf['ufw']['init_script'].' restart &> /dev/null');
	}
}

echo "Update finished.\n";

?>
