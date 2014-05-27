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
	ISPConfig 3 installer.
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
echo "\n\n>> Initial configuration  \n\n";

//** Include the library with the basic installer functions
require_once 'lib/install.lib.php';

//** Include the base class of the installer class
require_once 'lib/installer_base.lib.php';

//** Ensure that current working directory is install directory
$cur_dir = getcwd();
if(realpath(dirname(__FILE__)) != $cur_dir) {
	chdir( realpath(dirname(__FILE__)) );
}

//** Install logfile
define('ISPC_LOG_FILE', '/var/log/ispconfig_install.log');
define('ISPC_INSTALL_ROOT', realpath(dirname(__FILE__).'/../'));

//** Include the templating lib
require_once 'lib/classes/tpl.inc.php';

//** Check for existing installation
/*if(is_dir("/usr/local/ispconfig")) {
    die('We will stop here. There is already a ISPConfig installation, use the update script to update this installation.');
}*/

//** Get distribution identifier
$dist = get_distname();

if($dist['id'] == '') die('Linux distribution or version not recognized.');

//** Include the autoinstaller configuration (for non-interactive setups)
error_reporting(E_ALL ^ E_NOTICE);
if(is_file('autoinstall.conf.php')) include_once 'autoinstall.conf.php';

//** Include the distribution-specific installer class library and configuration
if(is_file('dist/lib/'.$dist['baseid'].'.lib.php')) include_once 'dist/lib/'.$dist['baseid'].'.lib.php';
include_once 'dist/lib/'.$dist['id'].'.lib.php';
include_once 'dist/conf/'.$dist['id'].'.conf.php';

//****************************************************************************************************
//** Installer Interface
//****************************************************************************************************
$inst = new installer();

swriteln($inst->lng('    Following will be a few questions for primary configuration so be careful.'));
swriteln($inst->lng('    Default values are in [brackets] and can be accepted with <ENTER>.'));
swriteln($inst->lng('    Tap in "quit" (without the quotes) to stop the installer.'."\n\n"));

//** Check log file is writable (probably not root or sudo)
if(!is_writable(dirname(ISPC_LOG_FILE))){
	die("ERROR: Cannot write to the ".dirname(ISPC_LOG_FILE)." directory. Are you root or sudo ?\n\n");
}

if(is_dir('/root/ispconfig') || is_dir('/home/admispconfig')) {
	die('This software cannot be installed on a server wich runs ISPConfig 2.x.');
}

if(is_dir('/usr/local/ispconfig')) {
	die('ISPConfig 3 installation found. Please use update.php instead if install.php to update the installation.');
}

//** Detect the installed applications
$inst->find_installed_apps();

//** Select the language and set default timezone
if($autoinstall['language'] == 'default') $autoinstall['language'] = 'en';
if($autoinstall['language'] == 'en' || $autoinstall['language'] == 'de'){
	$conf['language'] = $autoinstall['language'];
} else {
	$conf['language'] = $inst->simple_query('Select language', array('en', 'de'), 'en');
}
$conf['timezone'] = get_system_timezone();

//* Set default theme
$conf['theme'] = 'default';
$conf['language_file_import_enabled'] = true;

//** Select installation mode
if($autoinstall['install_mode'] == 'default') $autoinstall['install_mode'] = 'standard';
if($autoinstall['install_mode'] == 'standard' || $autoinstall['install_mode'] == 'expert'){
	$install_mode = $autoinstall['install_mode'];
} else {
	$install_mode = $inst->simple_query('Installation mode', array('standard', 'expert'), 'standard');
}


//** Get the hostname
$tmp_out = array();
exec('hostname -f', $tmp_out);
if($autoinstall['hostname'] == 'default') $autoinstall['hostname'] = @$tmp_out[0];
$conf['hostname'] = ($autoinstall['hostname'] != '' ? $autoinstall['hostname'] : $inst->free_query('Full qualified hostname (FQDN) of the server, eg server1.domain.tld ', @$tmp_out[0]));
unset($tmp_out);

// Check if the mysql functions are loaded in PHP
if(!function_exists('mysql_connect')) die('No PHP MySQL functions available. Please ensure that the PHP MySQL module is loaded.');

//** Get MySQL root credentials
$finished = false;
do {
	if($autoinstall['mysql_hostname'] == 'default') $autoinstall['mysql_hostname'] = $conf['mysql']['host'];
	if($autoinstall['mysql_root_user'] == 'default') $autoinstall['mysql_root_user'] = $conf['mysql']['admin_user'];
	if($autoinstall['mysql_database'] == 'default') $autoinstall['mysql_database'] = $conf['mysql']['database'];
	if($autoinstall['mysql_charset'] == 'default') $autoinstall['mysql_charset'] = $conf['mysql']['charset'];
	
	$tmp_mysql_server_host = ($autoinstall['mysql_hostname'] != ''? $autoinstall['mysql_hostname'] : $inst->free_query('MySQL server hostname', $conf['mysql']['host']));
	$tmp_mysql_server_admin_user = ($autoinstall['mysql_root_user'] != ''? $autoinstall['mysql_root_user'] : $inst->free_query('MySQL root username', $conf['mysql']['admin_user']));
	$tmp_mysql_server_admin_password = (isset($autoinstall['mysql_root_password'])? $autoinstall['mysql_root_password'] : $inst->free_query('MySQL root password', $conf['mysql']['admin_password']));
	$tmp_mysql_server_database = ($autoinstall['mysql_database'] != ''? $autoinstall['mysql_database'] : $inst->free_query('MySQL database to create', $conf['mysql']['database']));
	$tmp_mysql_server_charset = ($autoinstall['mysql_charset'] != ''? $autoinstall['mysql_charset'] : $inst->free_query('MySQL charset', $conf['mysql']['charset']));

	if($install_mode == 'expert') {
		swriteln("The next two questions are about the internal ISPConfig database user and password.\nIt is recommended to accept the defaults which are 'ispconfig' as username and a random password.\nIf you use a different password, use only numbers and chars for the password.\n");
		
		if($autoinstall['mysql_ispconfig_user'] == 'default') $autoinstall['mysql_ispconfig_user'] = $conf['mysql']['ispconfig_user'];
		
		$conf['mysql']['ispconfig_user'] = ($autoinstall['mysql_ispconfig_user'] != ''? $autoinstall['mysql_ispconfig_user'] : $inst->free_query('ISPConfig mysql database username', $conf['mysql']['ispconfig_user']));
		$conf['mysql']['ispconfig_password'] = (isset($autoinstall['mysql_ispconfig_password'])? $autoinstall['mysql_ispconfig_password'] : $inst->free_query('ISPConfig mysql database password', $conf['mysql']['ispconfig_password']));
	}

	//* Initialize the MySQL server connection
	if(@mysql_connect($tmp_mysql_server_host, $tmp_mysql_server_admin_user, $tmp_mysql_server_admin_password)) {
		$conf['mysql']['host'] = $tmp_mysql_server_host;
		$conf['mysql']['admin_user'] = $tmp_mysql_server_admin_user;
		$conf['mysql']['admin_password'] = $tmp_mysql_server_admin_password;
		$conf['mysql']['database'] = $tmp_mysql_server_database;
		$conf['mysql']['charset'] = $tmp_mysql_server_charset;
		$finished = true;
	} else {
		swriteln($inst->lng('Unable to connect to the specified MySQL server').' '.mysql_error());
	}
} while ($finished == false);
unset($finished);

// Resolve the IP address of the MySQL hostname.
$tmp = explode(':', $conf['mysql']['host']);
if(!$conf['mysql']['ip'] = gethostbyname($tmp[0])) die('Unable to resolve hostname'.$tmp[0]);
unset($tmp);


//** Initializing database connection
include_once 'lib/mysql.lib.php';
$inst->db = new db();

//** Begin with standard or expert installation
if($install_mode == 'standard') {

	//* Create the MySQL database
	$inst->configure_database();

	//* Configure Webserver - Apache or nginx
	if($conf['apache']['installed'] == true && $conf['nginx']['installed'] == true) {
		if($autoinstall['http_server'] == 'default') $autoinstall['http_server'] = 'apache';
		if($autoinstall['http_server'] == 'apache' || $autoinstall['http_server'] == 'nginx'){
			$http_server_to_use = $autoinstall['http_server'];
		} else {
			$http_server_to_use = $inst->simple_query('Apache and nginx detected. Select server to use for ISPConfig:', array('apache', 'nginx'), 'apache');
		}
		if($http_server_to_use == 'apache'){
			$conf['nginx']['installed'] = false;
		} else {
			$conf['apache']['installed'] = false;
		}
	}

	//* Insert the Server record into the database
	$inst->add_database_server_record();

	//* Configure Postfix
	$inst->configure_postfix();

	//* Configure Mailman
	if($conf['mailman']['installed'] == true) {
		$inst->configure_mailman('install');
	}

	//* Configure jailkit
	swriteln('Configuring Jailkit');
	$inst->configure_jailkit();

	if($conf['dovecot']['installed'] == true) {
		//* Configure Dovecot
		swriteln('Configuring Dovecot');
		$inst->configure_dovecot();
	} else {
		//* Configure saslauthd
		swriteln('Configuring SASL');
		$inst->configure_saslauthd();

		//* Configure PAM
		swriteln('Configuring PAM');
		$inst->configure_pam();

		//* Configure Courier
		swriteln('Configuring Courier');
		$inst->configure_courier();
	}

	//* Configure Spamasassin
	swriteln('Configuring Spamassassin');
	$inst->configure_spamassassin();

	//* Configure Amavis
	swriteln('Configuring Amavisd');
	$inst->configure_amavis();

	//* Configure Getmail
	swriteln('Configuring Getmail');
	$inst->configure_getmail();

	//* Configure Pureftpd
	swriteln('Configuring Pureftpd');
	$inst->configure_pureftpd();

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

	//* Configure Apache
	if($conf['apache']['installed'] == true){
		swriteln('Configuring Apache');
		$inst->configure_apache();
	}

	//* Configure nginx
	if($conf['nginx']['installed'] == true){
		swriteln('Configuring nginx');
		$inst->configure_nginx();
	}

	//** Configure Vlogger
	swriteln('Configuring Vlogger');
	$inst->configure_vlogger();

	//** Configure apps vhost
	swriteln('Configuring Apps vhost');
	$inst->configure_apps_vhost();

	//* Configure Firewall
	//* Configure Bastille Firewall
	$conf['services']['firewall'] = true;
	swriteln('Configuring Bastille Firewall');
	$inst->configure_firewall();

	//* Configure Fail2ban
	if($conf['fail2ban']['installed'] == true) {
		swriteln('Configuring Fail2ban');
		$inst->configure_fail2ban();
	}

	/*
	if($conf['squid']['installed'] == true) {
		$conf['services']['proxy'] = true;
		swriteln('Configuring Squid');
		$inst->configure_squid();
	} else if($conf['nginx']['installed'] == true) {
		$conf['services']['proxy'] = true;
		swriteln('Configuring Nginx');
		$inst->configure_nginx();
	}
	*/

	//* Configure ISPConfig
	swriteln('Installing ISPConfig');

	//** Customize the port ISPConfig runs on
	if($autoinstall['isspconfig_port'] == 'default') $autoinstall['ispconfig_port'] = '8080';
	$ispconfig_vhost_port = (intval($autoinstall['ispconfig_port']) > 0 ? intval($autoinstall['ispconfig_port']) : $inst->free_query('ISPConfig Port', '8080'));
	if($conf['apache']['installed'] == true) $conf['apache']['vhost_port']  = $ispconfig_vhost_port;
	if($conf['nginx']['installed'] == true) $conf['nginx']['vhost_port']  = $ispconfig_vhost_port;
	unset($ispconfig_vhost_port);

	if($autoinstall['ispconfig_use_ssl'] == 'default') $autoinstall['ispconfig_use_ssl'] = 'y';
	if($autoinstall['ispconfig_use_ssl'] == 'y' || $autoinstall['ispconfig_use_ssl'] == 'n'){
		if($autoinstall['ispconfig_use_ssl'] == 'y') $inst->make_ispconfig_ssl_cert();
	} else {
		if(strtolower($inst->simple_query('Do you want a secure (SSL) connection to the ISPConfig web interface', array('y', 'n'), 'y')) == 'y') {
			$inst->make_ispconfig_ssl_cert();
		}
	}

	$inst->install_ispconfig();

	//* Configure DBServer
	swriteln('Configuring DBServer');
	$inst->configure_dbserver();

	//* Configure ISPConfig
	swriteln('Installing ISPConfig crontab');
	$inst->install_crontab();

	swriteln('Restarting services ...');
	if($conf['mysql']['installed'] == true && $conf['mysql']['init_script'] != '') system($inst->getinitcommand($conf['mysql']['init_script'], 'restart'));
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
	if($conf['apache']['installed'] == true && $conf['apache']['init_script'] != '') system($inst->getinitcommand($conf['apache']['init_script'], 'restart'));
	//* Reload is enough for nginx
	if($conf['nginx']['installed'] == true){
		if($conf['nginx']['php_fpm_init_script'] != '') system($inst->getinitcommand($conf['nginx']['php_fpm_init_script'], 'reload'));
		if($conf['nginx']['init_script'] != '') system($inst->getinitcommand($conf['nginx']['init_script'], 'reload'));
	}
	if($conf['pureftpd']['installed'] == true && $conf['pureftpd']['init_script'] != '') system($inst->getinitcommand($conf['pureftpd']['init_script'], 'restart'));
	if($conf['mydns']['installed'] == true && $conf['mydns']['init_script'] != '') system($inst->getinitcommand($conf['mydns']['init_script'], 'restart').' &> /dev/null');
	if($conf['powerdns']['installed'] == true && $conf['powerdns']['init_script'] != '') system($inst->getinitcommand($conf['powerdns']['init_script'], 'restart').' &> /dev/null');
	if($conf['bind']['installed'] == true && $conf['bind']['init_script'] != '') system($inst->getinitcommand($conf['bind']['init_script'], 'restart').' &> /dev/null');
	//if($conf['squid']['installed'] == true && $conf['squid']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['squid']['init_script']))     system($conf['init_scripts'].'/'.$conf['squid']['init_script'].' restart &> /dev/null');
	if($conf['nginx']['installed'] == true && $conf['nginx']['init_script'] != '') system($inst->getinitcommand($conf['nginx']['init_script'], 'restart').' &> /dev/null');
	//if($conf['ufw']['installed'] == true && $conf['ufw']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['ufw']['init_script']))     system($conf['init_scripts'].'/'.$conf['ufw']['init_script'].' restart &> /dev/null');
} else {

	//* In expert mode, we select the services in the following steps, only db is always available
	$conf['services']['mail'] = false;
	$conf['services']['web'] = false;
	$conf['services']['dns'] = false;
	$conf['services']['db'] = true;
	$conf['services']['firewall'] = false;
	$conf['services']['proxy'] = false;


	//** Get Server ID
	// $conf['server_id'] = $inst->free_query('Unique Numeric ID of the server','1');
	// Server ID is an autoInc value of the mysql database now
	$tmp_join_multiserver_setup = 'n';
	if($autoinstall['join_multiserver_setup'] == 'default') $autoinstall['join_multiserver_setup'] = 'n';
	if($autoinstall['join_multiserver_setup'] == 'y' || $autoinstall['join_multiserver_setup'] == 'n'){
		$tmp_join_multiserver_setup = $autoinstall['join_multiserver_setup'];
	} else {
		$tmp_join_multiserver_setup = strtolower($inst->simple_query('Shall this server join an existing ISPConfig multiserver setup', array('y', 'n'), 'n'));
	}

	if($tmp_join_multiserver_setup == 'y') {
		$conf['mysql']['master_slave_setup'] = 'y';

		//** Get MySQL root credentials
		$finished = false;
		do {
			if($autoinstall['mysql_master_database'] == 'default') $autoinstall['mysql_master_database'] = $conf['mysql']['master_database'];
			
			$tmp_mysql_server_host = ($autoinstall['mysql_master_hostname'] != ''? $autoinstall['mysql_master_hostname'] : $inst->free_query('MySQL master server hostname', $conf['mysql']['master_host']));
			$tmp_mysql_server_admin_user = ($autoinstall['mysql_master_root_user'] != ''? $autoinstall['mysql_master_root_user'] : $inst->free_query('MySQL master server root username', $conf['mysql']['master_admin_user']));
			$tmp_mysql_server_admin_password = (isset($autoinstall['mysql_master_root_password'])? $autoinstall['mysql_master_root_password'] : $inst->free_query('MySQL master server root password', $conf['mysql']['master_admin_password']));
			$tmp_mysql_server_database = ($autoinstall['mysql_master_database'] != ''? $autoinstall['mysql_master_database'] : $inst->free_query('MySQL master server database name', $conf['mysql']['master_database']));

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
		// the master DB is the same then the slave DB
		$inst->dbmaster = $inst->db;
	}

	//* Create the mysql database
	$inst->configure_database();

	//* Configure Webserver - Apache or nginx
	if($conf['apache']['installed'] == true && $conf['nginx']['installed'] == true) {
		if($autoinstall['http_server'] == 'default') $autoinstall['http_server'] = 'apache';
		if($autoinstall['http_server'] == 'apache' || $autoinstall['http_server'] == 'nginx'){
			$http_server_to_use = $autoinstall['http_server'];
		} else {
			$http_server_to_use = $inst->simple_query('Apache and nginx detected. Select server to use for ISPConfig:', array('apache', 'nginx'), 'apache');
		}
		if($http_server_to_use == 'apache'){
			$conf['nginx']['installed'] = false;
		} else {
			$conf['apache']['installed'] = false;
		}
	}

	//* Insert the Server record into the database
	swriteln('Adding ISPConfig server record to database.');
	swriteln('');
	$inst->add_database_server_record();

	$tmp_configure_mail = 'y';
	if($autoinstall['configure_mail'] == 'default') $autoinstall['configure_mail'] = 'y';
	if($autoinstall['configure_mail'] == 'y' || $autoinstall['configure_mail'] == 'n'){
		$tmp_configure_mail = $autoinstall['configure_mail'];
	} else {
		$tmp_configure_mail = strtolower($inst->simple_query('Configure Mail', array('y', 'n') , 'y'));
	}

	if($tmp_configure_mail == 'y') {

		$conf['services']['mail'] = true;

		//* Configure Postfix
		swriteln('Configuring Postfix');
		$inst->configure_postfix();

		//* Configure Mailman
		swriteln('Configuring Mailman');
		$inst->configure_mailman();

		if($conf['dovecot']['installed'] == true) {
			//* Configure dovecot
			swriteln('Configuring Dovecot');
			$inst->configure_dovecot();
		} else {

			//* Configure saslauthd
			swriteln('Configuring SASL');
			$inst->configure_saslauthd();

			//* Configure PAM
			swriteln('Configuring PAM');
			$inst->configure_pam();

			//* Configure courier
			swriteln('Configuring Courier');
			$inst->configure_courier();
		}

		//* Configure Spamasassin
		swriteln('Configuring Spamassassin');
		$inst->configure_spamassassin();

		//* Configure Amavis
		swriteln('Configuring Amavisd');
		$inst->configure_amavis();

		//* Configure Getmail
		swriteln('Configuring Getmail');
		$inst->configure_getmail();

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

	//** Configure Jailkit
	$tmp_configure_jailkit = 'y';
	if($autoinstall['configure_jailkit'] == 'default') $autoinstall['configure_jailkit'] = 'y';
	if($autoinstall['configure_jailkit'] == 'y' || $autoinstall['configure_jailkit'] == 'n'){
		$tmp_configure_jailkit = $autoinstall['configure_jailkit'];
	} else {
		$tmp_configure_jailkit = strtolower($inst->simple_query('Configure Jailkit', array('y', 'n'), 'y'));
	}
	if($tmp_configure_jailkit == 'y') {
		swriteln('Configuring Jailkit');
		$inst->configure_jailkit();
	}

	//** Configure Pureftpd
	$tmp_configure_ftp = 'y';
	if($autoinstall['configure_ftp'] == 'default') $autoinstall['configure_ftp'] = 'y';
	if($autoinstall['configure_ftp'] == 'y' || $autoinstall['configure_ftp'] == 'n'){
		$tmp_configure_ftp = $autoinstall['configure_ftp'];
	} else {
		$tmp_configure_ftp = strtolower($inst->simple_query('Configure FTP Server', array('y', 'n'), 'y'));
	}
	if($tmp_configure_ftp == 'y') {
		swriteln('Configuring Pureftpd');
		$inst->configure_pureftpd();
		if($conf['pureftpd']['installed'] == true && $conf['pureftpd']['init_script'] != '') system($inst->getinitcommand($conf['pureftpd']['init_script'], 'restart'));
	}

	//** Configure DNS
	$tmp_configure_dns = 'y';
	if($autoinstall['configure_dns'] == 'default') $autoinstall['configure_dns'] = 'y';
	if($autoinstall['configure_dns'] == 'y' || $autoinstall['configure_dns'] == 'n'){
		$tmp_configure_dns = $autoinstall['configure_dns'];
	} else {
		$tmp_configure_dns = strtolower($inst->simple_query('Configure DNS Server', array('y', 'n'), 'y'));
	}
	if($tmp_configure_dns == 'y') {
		$conf['services']['dns'] = true;
		//* Configure DNS
		if($conf['powerdns']['installed'] == true) {
			swriteln('Configuring PowerDNS');
			$inst->configure_powerdns();
			if($conf['powerdns']['init_script'] != '') system($inst->getinitcommand($conf['powerdns']['init_script'], 'restart').' &> /dev/null');
		} elseif($conf['bind']['installed'] == true) {
			swriteln('Configuring BIND');
			$inst->configure_bind();
			if($conf['bind']['init_script'] != '') system($inst->getinitcommand($conf['bind']['init_script'], 'restart').' &> /dev/null');
		} else {
			swriteln('Configuring MyDNS');
			$inst->configure_mydns();
			if($conf['mydns']['init_script'] != '') system($inst->getinitcommand($conf['mydns']['init_script'], 'restart').' &> /dev/null');
		}

	}

	/*
	//** Configure Squid
	if(strtolower($inst->simple_query('Configure Proxy Server', array('y','n'),'y') ) == 'y') {
		if($conf['squid']['installed'] == true) {
			$conf['services']['proxy'] = true;
			swriteln('Configuring Squid');
			$inst->configure_squid();
			if($conf['squid']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['squid']['init_script']))system($conf['init_scripts'].'/'.$conf['squid']['init_script'].' restart &> /dev/null');
		} else if($conf['nginx']['installed'] == true) {
			$conf['services']['proxy'] = true;
			swriteln('Configuring Nginx');
			$inst->configure_nginx();
			if($conf['nginx']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['nginx']['init_script']))system($conf['init_scripts'].'/'.$conf['nginx']['init_script'].' restart &> /dev/null');
		}
	}
	*/

	//** Configure Apache
	if($conf['apache']['installed'] == true){
		swriteln("\nHint: If this server shall run the ISPConfig interface, select 'y' in the 'Configure Apache Server' option.\n");
		$tmp_configure_apache = 'y';
		if($autoinstall['configure_apache'] == 'default') $autoinstall['configure_apache'] = 'y';
		if($autoinstall['configure_apache'] == 'y' || $autoinstall['configure_apache'] == 'n'){
			$tmp_configure_apache = $autoinstall['configure_apache'];
		} else {
			$tmp_configure_apache = strtolower($inst->simple_query('Configure Apache Server', array('y', 'n'), 'y'));
		}
		if($tmp_configure_apache == 'y') {
			$conf['services']['web'] = true;
			swriteln('Configuring Apache');
			$inst->configure_apache();

			//** Configure Vlogger
			swriteln('Configuring Vlogger');
			$inst->configure_vlogger();

			//** Configure apps vhost
			swriteln('Configuring Apps vhost');
			$inst->configure_apps_vhost();
		}
	}

	//** Configure nginx
	if($conf['nginx']['installed'] == true){
		swriteln("\nHint: If this server shall run the ISPConfig interface, select 'y' in the 'Configure nginx Server' option.\n");
		$tmp_configure_nginx = 'y';
		if($autoinstall['configure_nginx'] == 'default') $autoinstall['configure_nginx'] = 'y';
		if($autoinstall['configure_nginx'] == 'y' || $autoinstall['configure_nginx'] == 'n'){
			$tmp_configure_nginx = $autoinstall['configure_nginx'];
		} else {
			$tmp_configure_nginx = strtolower($inst->simple_query('Configure nginx Server', array('y', 'n'), 'y'));
		}
		if($tmp_configure_nginx == 'y') {
			$conf['services']['web'] = true;
			swriteln('Configuring nginx');
			$inst->configure_nginx();

			//** Configure Vlogger
			//swriteln('Configuring Vlogger');
			//$inst->configure_vlogger();

			//** Configure apps vhost
			swriteln('Configuring Apps vhost');
			$inst->configure_apps_vhost();
		}
	}

	//** Configure Firewall
	$tmp_configure_firewall = 'y';
	if($autoinstall['configure_firewall'] == 'default') $autoinstall['configure_firewall'] = 'y';
	if($autoinstall['configure_firewall'] == 'y' || $autoinstall['configure_firewall'] == 'n'){
		$tmp_configure_firewall = $autoinstall['configure_firewall'];
	} else {
		$tmp_configure_firewall = strtolower($inst->simple_query('Configure Firewall Server', array('y', 'n'), 'y'));
	}
	if($tmp_configure_firewall == 'y') {
		//if($conf['bastille']['installed'] == true) {
		//* Configure Bastille Firewall
		$conf['services']['firewall'] = true;
		swriteln('Configuring Bastille Firewall');
		$inst->configure_firewall();
		/*} elseif($conf['ufw']['installed'] == true) {
			//* Configure Ubuntu Firewall
			$conf['services']['firewall'] = true;
			swriteln('Configuring Ubuntu Firewall');
			$inst->configure_ufw_firewall();
		}
		*/
	}

	//** Configure Firewall
	/*if(strtolower($inst->simple_query('Configure Firewall Server',array('y','n'),'y')) == 'y') {
		swriteln('Configuring Firewall');
		$inst->configure_firewall();
	}*/

	//** Configure ISPConfig :-)
	$install_ispconfig_interface_default = ($conf['mysql']['master_slave_setup'] == 'y')?'n':'y';
	$tmp_install_ispconfig_web_interface = $install_ispconfig_interface_default;
	if($autoinstall['install_ispconfig_web_interface'] == 'default') $autoinstall['install_ispconfig_web_interface'] = $install_ispconfig_interface_default;
	if($autoinstall['install_ispconfig_web_interface'] == 'y' || $autoinstall['install_ispconfig_web_interface'] == 'n'){
		$tmp_install_ispconfig_web_interface = $autoinstall['install_ispconfig_web_interface'];
	} else {
		$tmp_install_ispconfig_web_interface = strtolower($inst->simple_query('Install ISPConfig Web Interface', array('y', 'n'), $install_ispconfig_interface_default));
	}
	if($tmp_install_ispconfig_web_interface == 'y') {
		swriteln('Installing ISPConfig');

		//** We want to check if the server is a module or cgi based php enabled server
		//** TODO: Don't always ask for this somehow ?
		/*
		$fast_cgi = $inst->simple_query('CGI PHP Enabled Server?', array('yes','no'),'no');

		if($fast_cgi == 'yes') {
	 		$alias = $inst->free_query('Script Alias', '/php/');
	 		$path = $inst->free_query('Script Alias Path', '/path/to/cgi/bin');
	 		$conf['apache']['vhost_cgi_alias'] = sprintf('ScriptAlias %s %s', $alias, $path);
		} else {
	 		$conf['apache']['vhost_cgi_alias'] = "";
		}
		*/

		//** Customise the port ISPConfig runs on
		if($autoinstall['ispconfig_port'] == 'default') $autoinstall['ispconfig_port'] = '8080';
		$ispconfig_vhost_port = (intval($autoinstall['ispconfig_port']) > 0 ? intval($autoinstall['ispconfig_port']) : $inst->free_query('ISPConfig Port', '8080'));
		if($conf['apache']['installed'] == true) $conf['apache']['vhost_port']  = $ispconfig_vhost_port;
		if($conf['nginx']['installed'] == true) $conf['nginx']['vhost_port']  = $ispconfig_vhost_port;
		unset($ispconfig_vhost_port);

		if($autoinstall['ispconfig_use_ssl'] == 'default') $autoinstall['ispconfig_use_ssl'] = 'y';
		if($autoinstall['ispconfig_use_ssl'] == 'y' || $autoinstall['ispconfig_use_ssl'] == 'n'){
			if($autoinstall['ispconfig_use_ssl'] == 'y') $inst->make_ispconfig_ssl_cert();
		} else {
			if(strtolower($inst->simple_query('Enable SSL for the ISPConfig web interface', array('y', 'n'), 'y')) == 'y') {
				$inst->make_ispconfig_ssl_cert();
			}
		}

		$inst->install_ispconfig_interface = true;

	} else {
		$inst->install_ispconfig_interface = false;
	}

	$inst->install_ispconfig();

	//* Configure DBServer
	swriteln('Configuring DBServer');
	$inst->configure_dbserver();

	//* Configure ISPConfig
	swriteln('Installing ISPConfig crontab');
	$inst->install_crontab();
	if($conf['apache']['installed'] == true && $conf['apache']['init_script'] != '') system($inst->getinitcommand($conf['apache']['init_script'], 'restart'));
	//* Reload is enough for nginx
	if($conf['nginx']['installed'] == true){
		if($conf['nginx']['php_fpm_init_script'] != '') system($inst->getinitcommand($conf['nginx']['php_fpm_init_script'], 'reload'));
		if($conf['nginx']['init_script'] != '') system($inst->getinitcommand($conf['nginx']['init_script'], 'reload'));
	}



} //* << $install_mode / 'Standard' or Genius


echo "Installation completed.\n";


?>
