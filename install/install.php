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
	
	-------------------------------------------------------------------------------------
	- Interactive install
	-------------------------------------------------------------------------------------
	run:
	
	php install.php
	
	-------------------------------------------------------------------------------------
	- Noninteractive (autoinstall) mode
	-------------------------------------------------------------------------------------
	
	The autoinstall mode can read the installer questions from a .ini style file or from
	a php config file. Examples for both file types are in the docs folder. 
	See autoinstall.ini.sample and autoinstall.conf_sample.php.
	
	run:
	
	php install.php --autoinstall=autoinstall.ini
	
	or
	
	php install.php --autoinstall=autoinstall.conf.php
	
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

//** Get commandline options
$cmd_opt = getopt('', array('autoinstall::'));

//** Load autoinstall file
if(isset($cmd_opt['autoinstall']) && is_file($cmd_opt['autoinstall'])) {
	$path_parts = pathinfo($cmd_opt['autoinstall']);
	if($path_parts['extension'] == 'php') {
		include_once $cmd_opt['autoinstall'];
	} elseif($path_parts['extension'] == 'ini') {
		$tmp = ini_to_array(file_get_contents('autoinstall.ini'));
		if(!is_array($tmp['install'])) $tmp['install'] = array();
		if(!is_array($tmp['ssl_cert'])) $tmp['ssl_cert'] = array();
		if(!is_array($tmp['expert'])) $tmp['expert'] = array();
		if(!is_array($tmp['update'])) $tmp['update'] = array();
		$autoinstall = $tmp['install'] + $tmp['ssl_cert'] + $tmp['expert'] + $tmp['update'];
		unset($tmp);
	}
	unset($path_parts);
	define('AUTOINSTALL', true);
} else {
	$autoinstall = array();
	define('AUTOINSTALL', false);
}


//** Include the distribution-specific installer class library and configuration
if(is_file('dist/lib/'.$dist['baseid'].'.lib.php')) include_once 'dist/lib/'.$dist['baseid'].'.lib.php';
include_once 'dist/lib/'.$dist['id'].'.lib.php';
include_once 'dist/conf/'.$dist['id'].'.conf.php';

//****************************************************************************************************
//** Installer Interface
//****************************************************************************************************
$inst = new installer();
if (!$inst->get_php_version()) die('ISPConfig requieres PHP '.$inst->min_php."\n");

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
$conf['language'] = $inst->simple_query('Select language', array('en', 'de'), 'en','language');
$conf['timezone'] = get_system_timezone();

//* Set default theme
$conf['theme'] = 'default';
$conf['language_file_import_enabled'] = true;

//** Select installation mode
$install_mode = $inst->simple_query('Installation mode', array('standard', 'expert'), 'standard','install_mode');


//** Get the hostname
$tmp_out = array();
exec('hostname -f', $tmp_out);
$conf['hostname'] = @$tmp_out[0];
unset($tmp_out);
//** Prevent empty hostname
$check = false;
do {
	$conf['hostname'] = $inst->free_query('Full qualified hostname (FQDN) of the server, eg server1.domain.tld ', $conf['hostname'], 'hostname');
	$conf['hostname']=trim($conf['hostname']);
	$check = @($conf['hostname'] !== '')?true:false;
	if(!$check) swriteln('Hostname may not be empty.');
} while (!$check);

// Check if the mysql functions are loaded in PHP
if(!function_exists('mysql_connect')) die('No PHP MySQL functions available. Please ensure that the PHP MySQL module is loaded.');

//** Get MySQL root credentials
$finished = false;
do {
	$tmp_mysql_server_host = $inst->free_query('MySQL server hostname', $conf['mysql']['host'],'mysql_hostname');	 
	$tmp_mysql_server_port = $inst->free_query('MySQL server port', $conf['mysql']['port'],'mysql_port');
	$tmp_mysql_server_admin_user = $inst->free_query('MySQL root username', $conf['mysql']['admin_user'],'mysql_root_user');	 
	$tmp_mysql_server_admin_password = $inst->free_query('MySQL root password', $conf['mysql']['admin_password'],'mysql_root_password');	 
	$tmp_mysql_server_database = $inst->free_query('MySQL database to create', $conf['mysql']['database'],'mysql_database');	 
	$tmp_mysql_server_charset = $inst->free_query('MySQL charset', $conf['mysql']['charset'],'mysql_charset');
	
	if($install_mode == 'expert') {
		swriteln("The next two questions are about the internal ISPConfig database user and password.\nIt is recommended to accept the defaults which are 'ispconfig' as username and a random password.\nIf you use a different password, use only numbers and chars for the password.\n");
		
		$conf['mysql']['ispconfig_user'] = $inst->free_query('ISPConfig mysql database username', $conf['mysql']['ispconfig_user'],'mysql_ispconfig_user');	 
		$conf['mysql']['ispconfig_password'] = $inst->free_query('ISPConfig mysql database password', $conf['mysql']['ispconfig_password'],'mysql_ispconfig_password');
	}

	//* Initialize the MySQL server connection
	if(@mysql_connect($tmp_mysql_server_host . ':' . (int)$tmp_mysql_server_port, $tmp_mysql_server_admin_user, $tmp_mysql_server_admin_password)) {
		$conf['mysql']['host'] = $tmp_mysql_server_host;
		$conf['mysql']['port'] = $tmp_mysql_server_port;
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

$conf['services']['mail'] = false;
$conf['services']['web'] = false;
$conf['services']['dns'] = false;
$conf['services']['file'] = false;
$conf['services']['db'] = true;
$conf['services']['vserver'] = false;
$conf['services']['firewall'] = false;
$conf['services']['proxy'] = false;
$conf['services']['xmpp'] = false;

if($install_mode == 'standard') {

	$inst->dbmaster = $inst->db;
	
	//* Create the MySQL database
	$inst->configure_database();

	//* Insert the Server record into the database
	$inst->add_database_server_record();

	//* Configure Postgrey
	$force = @($conf['postgrey']['installed']) ? true : $inst->force_configure_app('Postgrey', false);
	if($force) swriteln('Configuring Postgrey');

	//* Configure Postfix
	$force = @($conf['postfix']['installed']) ? true : $inst->force_configure_app('Postfix', false);
	if($force) {
		swriteln('Configuring Postfix');
		$inst->configure_postfix();
		$conf['services']['mail'] = true;
	}

	if($conf['services']['mail']) {

		//* Configure Mailman
		$force = @($conf['mailman']['installed']) ? true : $inst->force_configure_app('Mailman', false);
		if($force) {
			swriteln('Configuring Mailman');
			$inst->configure_mailman();
		} 

		//* Check for Dovecot and Courier
		if(!$conf['dovecot']['installed'] && !$conf['courier']['installed']) {
			$conf['dovecot']['installed'] = $inst->force_configure_app('Dovecot', false);
			$conf['courier']['installed'] = $inst->force_configure_app('Courier', false);
		}
		//* Configure Mailserver - Dovecot or Courier
		if($conf['dovecot']['installed'] && $conf['courier']['installed']) {
			$mail_server_to_use = $inst->simple_query('Dovecot and Courier detected. Select server to use with ISPConfig:', array('dovecot', 'courier'), 'dovecot','mail_server');
			if($mail_server_to_use == 'dovecot'){
				$conf['courier']['installed'] = false;
			} else {
				$conf['dovecot']['installed'] = false;
			}
		}
		//* Configure Dovecot
		if($conf['dovecot']['installed']) {
			swriteln('Configuring Dovecot');
			$inst->configure_dovecot();
		}
		//* Configure Courier
		if($conf['courier']['installed']) {
			swriteln('Configuring Courier');
			$inst->configure_courier();
			swriteln('Configuring SASL');
			$inst->configure_saslauthd();
			swriteln('Configuring PAM');
			$inst->configure_pam();
		}

		//* Configure Spamasassin
		$force = @($conf['spamassassin']['installed']) ? true : $inst->force_configure_app('Spamassassin', false);
		if($force) {
			swriteln('Configuring Spamassassin');
			$inst->configure_spamassassin();
		}
    
		//* Configure Amavis
		$force = @($conf['amavis']['installed']) ? true : $inst->force_configure_app('Amavisd', false);
		if($force) {
			swriteln('Configuring Amavisd');
			$inst->configure_amavis();
		}

		//* Configure Getmail
		$force = @($conf['getmail']['installed']) ? true : $inst->force_configure_app('Getmail', false);
		if($force) {
			swriteln('Configuring Getmail');
			$inst->configure_getmail();
		}

	} else swriteln('[ERROR] Postfix not installed - skipping Mail');

	//* Check for DNS
	if(!$conf['powerdns']['installed'] && !$conf['bind']['installed'] && !$conf['mydns']['installed']) {
		$conf['powerdns']['installed'] = $inst->force_configure_app('PowerDNS', false);
		$conf['bind']['installed'] = $inst->force_configure_app('BIND', false);
		$conf['mydns']['installed'] = $inst->force_configure_app('MyDNS', false);
	}
	//* Configure PowerDNS
	if($conf['powerdns']['installed']) {
		swriteln('Configuring PowerDNS');
		$inst->configure_powerdns();
		$conf['services']['dns'] = true;
	}
	//* Configure Bind
	if($conf['bind']['installed']) {
		swriteln('Configuring BIND');
		$inst->configure_bind();
		$conf['services']['dns'] = true;
	}
	//* Configure MyDNS
	if($conf['mydns']['installed']) {
		swriteln('Configuring MyDNS');
		$inst->configure_mydns();
		$conf['services']['dns'] = true;
	}

	//* Configure Jailkit
	$force = @($conf['jailkit']['installed']) ? true : $inst->force_configure_app('Jailkit', false);
	if($force) {
		swriteln('Configuring Jailkit');
		$inst->configure_jailkit();
	}

	//* Configure Pureftpd
	$force = @($conf['pureftpd']['installed']) ? true : $inst->force_configure_app('pureftpd', false);
	if($force) {
		swriteln('Configuring Pureftpd');
		$inst->configure_pureftpd();
	}

	//* Check for Web-Server
	if(!$conf['apache']['installed'] && !$conf['nginx']['installed']) {
		$conf['apache']['installed'] = $inst->force_configure_app('Apache', false);
		$conf['nginx']['installed'] = $inst->force_configure_app('nginx', false);
	}

	//* Configure Webserver - Apache or nginx
	if($conf['apache']['installed'] && $conf['nginx']['installed']) {
		$http_server_to_use = $inst->simple_query('Apache and nginx detected. Select server to use for ISPConfig:', array('apache', 'nginx'), 'apache','http_server');
		if($http_server_to_use == 'apache'){
			$conf['nginx']['installed'] = false;
		} else {
			$conf['apache']['installed'] = false;
		}
	}

	//* Configure Apache
	if($conf['apache']['installed']){
		swriteln('Configuring Apache');
		$inst->configure_apache();
		$conf['services']['web'] = true;
		$conf['services']['file'] = true;
		//* Configure Vlogger
		$force = @($conf['vlogger']['installed']) ? true : $inst->force_configure_app('vlogger', false);
		if($force) {
			swriteln('Configuring vlogger');
			$inst->configure_vlogger();
		}
		//* Configure squid
/*
		$force = @($conf['squid']['installed']) ? true : $inst->force_configure_app('squid');
		if($force) {
			swriteln('Configuring Squid');
			$inst->configure_squid();
			$conf['services']['proxy'] = true;
		}
*/
	}

	//* Configure nginx
	if($conf['nginx']['installed']){
		swriteln('Configuring nginx');
		$inst->configure_nginx();
		$conf['services']['web'] = true;
	}

    //* Configure XMPP
	$force = @($conf['xmpp']['installed']) ? true : $inst->force_configure_app('Metronome XMPP Server', false);
	if($force) {
        swriteln('Configuring Metronome XMPP Server');
        $inst->configure_xmpp();
	    $conf['services']['xmpp'] = true;
	}

	//* Check for Firewall
	if(!$conf['ufw']['installed'] && !$conf['firewall']['installed']) {
		$conf['ufw']['installed'] = $inst->force_configure_app('Ubuntu Firewall', false);
		$conf['firewall']['installed'] = $inst->force_configure_app('Bastille Firewall', false);
	}
	//* Configure Firewall - Ubuntu or Bastille
	if($conf['ufw']['installed'] && $conf['firewall']['installed']) {
		$firewall_to_use = $inst->simple_query('Ubuntu and Bastille Firewall detected. Select firewall to use with ISPConfig:', array('bastille', 'ubuntu'), 'bastille','firewall_server');
		if($firewall_to_use == 'bastille'){
			$conf['ufw']['installed'] = false;
		} else {
			$conf['firewall']['installed'] = false;
		}
	}
	//* Configure Ubuntu Firewall
	if($conf['ufw']['installed']){
		swriteln('Configuring Ubuntu Firewall');
		$inst->configure_ufw_firewall();
		$conf['services']['firewall'] = true;
	}
	//* Configure Bastille Firewall
	if($conf['firewall']['installed']){
		swriteln('Configuring Bastille Firewall');
		$inst->configure_bastille_firewall();
		$conf['services']['firewall'] = true;
	}

	//* Configure Fail2ban
	$force = @($conf['fail2ban']['installed']) ? true : $inst->force_configure_app('Fail2ban', false);
	if($force) {
		swriteln('Configuring Fail2ban');
		$inst->configure_fail2ban();
	}

	//* Configure OpenVZ
	$force = @($conf['openvz']['installed']) ? true : $inst->force_configure_app('OpenVZ', false);
	if($force) {
		$conf['services']['vserver'] = true;
		swriteln('Configuring OpenVZ');
	}

	//** Configure apps vhost
	swriteln('Configuring Apps vhost');
	$inst->configure_apps_vhost();

	//* Configure ISPConfig
	swriteln('Installing ISPConfig');

	//** Customize the port ISPConfig runs on
	$ispconfig_vhost_port = $inst->free_query('ISPConfig Port', '8080','ispconfig_port');
	$conf['interface_password'] = $inst->free_query('Admin password', 'admin');
	if($conf['interface_password'] != 'admin') {
		$check = false;
		do {
			unset($temp_password);
			$temp_password = $inst->free_query('Re-enter admin password', '');
			$check = @($temp_password == $conf['interface_password'])?true:false;
			if(!$check) swriteln('Passwords do not match.');
		} while (!$check);
	}
	unset($check);
	unset($temp_password);
	if($conf['apache']['installed'] == true) $conf['apache']['vhost_port']  = $ispconfig_vhost_port;
	if($conf['nginx']['installed'] == true) $conf['nginx']['vhost_port']  = $ispconfig_vhost_port;
	unset($ispconfig_vhost_port);

	if(strtolower($inst->simple_query('Do you want a secure (SSL) connection to the ISPConfig web interface', array('y', 'n'), 'y','ispconfig_use_ssl')) == 'y') {	 
		$inst->make_ispconfig_ssl_cert();
	}

	$inst->install_ispconfig();

	//* Configure DBServer
	swriteln('Configuring DBServer');
	$inst->configure_dbserver();

	//* Configure ISPConfig
	if($conf['cron']['installed']) {
		swriteln('Installing ISPConfig crontab');
		$inst->install_crontab();
	} else swriteln('[ERROR] Cron not found');

	swriteln('Detect IP addresses');
	$inst->detect_ips();

	swriteln('Restarting services ...');
	if($conf['mysql']['installed'] == true && $conf['mysql']['init_script'] != '') system($inst->getinitcommand($conf['mysql']['init_script'], 'restart').' >/dev/null 2>&1');
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
	if($conf['ufw']['installed'] == true && $conf['ufw']['init_script'] != '') system($inst->getinitcommand($conf['ufw']['init_script'], 'restart').' &> /dev/null');
    if($conf['xmpp']['installed'] == true && $conf['xmpp']['init_script'] != '') system($inst->getinitcommand($conf['xmpp']['init_script'], 'restart').' &> /dev/null');

} else { //* expert mode

	//** Get Server ID
	// $conf['server_id'] = $inst->free_query('Unique Numeric ID of the server','1');
	// Server ID is an autoInc value of the mysql database now
	if(strtolower($inst->simple_query('Shall this server join an existing ISPConfig multiserver setup', array('y', 'n'), 'n','join_multiserver_setup')) == 'y') {
		$conf['mysql']['master_slave_setup'] = 'y';

		//** Get MySQL root credentials
		$finished = false;
		do {
			$tmp_mysql_server_host = $inst->free_query('MySQL master server hostname', $conf['mysql']['master_host'],'mysql_master_hostname'); 
			$tmp_mysql_server_port = $inst->free_query('MySQL master server port', $conf['mysql']['master_port'],'mysql_master_port');
			$tmp_mysql_server_admin_user = $inst->free_query('MySQL master server root username', $conf['mysql']['master_admin_user'],'mysql_master_root_user');	 
			$tmp_mysql_server_admin_password = $inst->free_query('MySQL master server root password', $conf['mysql']['master_admin_password'],'mysql_master_root_password'); 
			$tmp_mysql_server_database = $inst->free_query('MySQL master server database name', $conf['mysql']['master_database'],'mysql_master_database');

			//* Initialize the MySQL server connection
			if(@mysql_connect($tmp_mysql_server_host . ':' . (int)$tmp_mysql_server_port, $tmp_mysql_server_admin_user, $tmp_mysql_server_admin_password)) {
				$conf['mysql']['master_host'] = $tmp_mysql_server_host;
				$conf['mysql']['master_port'] = $tmp_mysql_server_port;
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
		$inst->dbmaster->setDBData($conf['mysql']["master_host"], $conf['mysql']["master_admin_user"], $conf['mysql']["master_admin_password"]);
		$inst->dbmaster->setDBName($conf['mysql']["master_database"]);

	} else {
		// the master DB is the same then the slave DB
		$inst->dbmaster = $inst->db;
	}

	//* Create the mysql database
	$inst->configure_database();

	//* Check for Web-Server
	if($conf['apache']['installed'] != true && $conf['nginx']['installed'] != true) {
		$conf['apache']['installed'] = $inst->force_configure_app('Apache');
		$conf['nginx']['installed'] = $inst->force_configure_app('nginx');
	}
	//* Configure Webserver - Apache or nginx
	if($conf['apache']['installed'] == true && $conf['nginx']['installed'] == true) {
		$http_server_to_use = $inst->simple_query('Apache and nginx detected. Select server to use for ISPConfig:', array('apache', 'nginx'), 'apache','http_server');
		if($http_server_to_use == 'apache'){
			$conf['nginx']['installed'] = false;
			$conf['services']['file'] = true;
		} else {
			$conf['apache']['installed'] = false;
		}
	}

	//* Insert the Server record into the database
	swriteln('Adding ISPConfig server record to database.');
	swriteln('');
	$inst->add_database_server_record();

	if(strtolower($inst->simple_query('Configure Mail', array('y', 'n') , 'y','configure_mail') ) == 'y') {

		$conf['services']['mail'] = true;

		//* Configure Postgrey
		$force = @($conf['postgrey']['installed']) ? true : $inst->force_configure_app('Postgrey');
		if($force) swriteln('Configuring Postgrey');

		//* Configure Postfix
		$force = @($conf['postfix']['installed']) ? true : $inst->force_configure_app('Postfix');
		if($force) {
			swriteln('Configuring Postfix');
			$inst->configure_postfix();
		}

		//* Configure Mailman
		$force = @($conf['mailman']['installed']) ? true : $inst->force_configure_app('Mailman');
		if($force) {
			swriteln('Configuring Mailman');
			$inst->configure_mailman();
		}

		//* Check for Dovecot and Courier
		if(!$conf['dovecot']['installed'] && !$conf['courier']['installed']) {
			$conf['dovecot']['installed'] = $inst->force_configure_app('Dovecot');
			$conf['courier']['installed'] = $inst->force_configure_app('Courier');
		}
		//* Configure Mailserver - Dovecot or Courier
		if($conf['dovecot']['installed'] && $conf['courier']['installed']) {
			$mail_server_to_use = $inst->simple_query('Dovecot and Courier detected. Select server to use with ISPConfig:', array('dovecot', 'courier'), 'dovecot','mail_server');
			if($mail_server_to_use == 'dovecot'){
				$conf['courier']['installed'] = false;
			} else {
				$conf['dovecot']['installed'] = false;
			}
		}
		//* Configure Dovecot
		if($conf['dovecot']['installed']) {
			swriteln('Configuring Dovecot');
			$inst->configure_dovecot();
		}
		//* Configure Courier
		if($conf['courier']['installed']) {
			swriteln('Configuring Courier');
			$inst->configure_courier();
			swriteln('Configuring SASL');
			$inst->configure_saslauthd();
			swriteln('Configuring PAM');
			$inst->configure_pam();
		}

		//* Configure Spamasassin
		$force = @($conf['spamassassin']['installed']) ? true : $inst->force_configure_app('Spamassassin');
		if($force) {
			swriteln('Configuring Spamassassin');
			$inst->configure_spamassassin();
		}
    
		//* Configure Amavis
		$force = @($conf['amavis']['installed']) ? true : $inst->force_configure_app('Amavisd');
		if($force) {
			swriteln('Configuring Amavisd');
			$inst->configure_amavis();
		}

		//* Configure Getmail
		$force = @($conf['getmail']['installed']) ? true : $inst->force_configure_app('Getmail');
		if($force) {
			swriteln('Configuring Getmail');
			$inst->configure_getmail();
		}

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

	//* Configure Jailkit
	$force = @($conf['jailkit']['installed']) ? true : $inst->force_configure_app('Jailkit');
	if($force) {
		swriteln('Configuring Jailkit');
		$inst->configure_jailkit();
	}

	//* Configure Pureftpd
	$force = @($conf['pureftpd']['installed']) ? true : $inst->force_configure_app('pureftpd');
	if($force) {
		swriteln('Configuring Pureftpd');
		$inst->configure_pureftpd();
	}
	
	swriteln('Detect IP addresses');
	$inst->detect_ips();

	//** Configure DNS
	if(strtolower($inst->simple_query('Configure DNS Server', array('y', 'n'), 'y','configure_dns')) == 'y') {
		$conf['services']['dns'] = true;

		//* Check for DNS
		if(!$conf['powerdns']['installed'] && !$conf['bind']['installed'] && !$conf['mydns']['installed']) {
			$conf['powerdns']['installed'] = $inst->force_configure_app('PowerDNS');
			$conf['bind']['installed'] = $inst->force_configure_app('BIND');
			$conf['mydns']['installed'] = $inst->force_configure_app('MyDNS');
		}
		//* Configure PowerDNS
		if($conf['powerdns']['installed']) {
			swriteln('Configuring PowerDNS');
			$inst->configure_powerdns();
			$conf['services']['dns'] = true;
		}
		//* Configure Bind
		if($conf['bind']['installed']) {
			swriteln('Configuring BIND');
			$inst->configure_bind();
			$conf['services']['dns'] = true;
		}
		//* Configure MyDNS
		if($conf['mydns']['installed']) {
			swriteln('Configuring MyDNS');
			$inst->configure_mydns();
			$conf['services']['dns'] = true;
		}

	}

	if(strtolower($inst->simple_query('Configure Web Server', array('y', 'n'), 'y','configure_webserver')) == 'y') {
		$conf['services']['web'] = true;

		//* Configure Apache
		if($conf['apache']['installed']){
			swriteln('Configuring Apache');
			$inst->configure_apache();
			$conf['services']['file'] = true;
			//* Configure Vlogger
			$force = @($conf['vlogger']['installed']) ? true : $inst->force_configure_app('vlogger');
			if($force) {
				swriteln('Configuring vlogger');
				$inst->configure_vlogger();
			}
			//* Configure squid
/*
			$force = @($conf['squid']['installed']) ? true : $inst->force_configure_app('squid');
			if($force) {
				swriteln('Configuring Squid');
				$inst->configure_squid();
				$conf['services']['proxy'] = true;
				if($conf['squid']['init_script'] != '' && is_executable($conf['init_scripts'].'/'.$conf['squid']['init_script']))system($conf['init_scripts'].'/'.$conf['squid']['init_script'].' restart &> /dev/null');
			}
*/
		}
		//* Configure nginx
		if($conf['nginx']['installed']){
			swriteln('Configuring nginx');
			$inst->configure_nginx();
		}
	}

	//* Configure OpenVZ
	$force = @($conf['openvz']['installed']) ? true : $inst->force_configure_app('OpenVZ');
	if($force) {
		$conf['services']['vserver'] = true;
		swriteln('Configuring OpenVZ');
	}

	if(strtolower($inst->simple_query('Configure Firewall Server', array('y', 'n'), 'y','configure_firewall')) == 'y') {
		//* Check for Firewall
		if(!$conf['ufw']['installed'] && !$conf['firewall']['installed']) {
			$conf['ufw']['installed'] = $inst->force_configure_app('Ubuntu Firewall');
			$conf['firewall']['installed'] = $inst->force_configure_app('Bastille Firewall');
		}
		//* Configure Firewall - Ubuntu or Bastille
		if($conf['ufw']['installed'] && $conf['firewall']['installed']) {
			$firewall_to_use = $inst->simple_query('Ubuntu and Bastille Firewall detected. Select firewall to use with ISPConfig:', array('bastille', 'ubuntu'), 'bastille','firewall_server');
			if($firewall_to_use == 'bastille'){
				$conf['ufw']['installed'] = false;
			} else {
				$conf['firewall']['installed'] = false;
			}
		}
		//* Configure Ubuntu Firewall
		if($conf['ufw']['installed']){
			swriteln('Configuring Ubuntu Firewall');
			$inst->configure_ufw_firewall();
			$conf['services']['firewall'] = true;
		}
		//* Configure Bastille Firewall
		if($conf['firewall']['installed']){
			swriteln('Configuring Bastille Firewall');
			$inst->configure_bastille_firewall();
			$conf['services']['firewall'] = true;
		}
	}

    //* Configure XMPP
	$force = @($conf['xmpp']['installed']) ? true : $inst->force_configure_app('Metronome XMPP Server');
	if($force) {
        swriteln('Configuring Metronome XMPP Server');
        $inst->configure_xmpp();
	    $conf['services']['xmpp'] = true;
	}

	//** Configure ISPConfig :-)
	$install_ispconfig_interface_default = ($conf['mysql']['master_slave_setup'] == 'y')?'n':'y';
	if(strtolower($inst->simple_query('Install ISPConfig Web Interface', array('y', 'n'), $install_ispconfig_interface_default,'install_ispconfig_web_interface')) == 'y') {
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
		$ispconfig_vhost_port = $inst->free_query('ISPConfig Port', '8080','ispconfig_port');
		$conf['interface_password'] = $inst->free_query('Admin password', 'admin');
		if($conf['interface_password'] != 'admin') {
			$check = false;
			do {
				unset($temp_password);
				$temp_password = $inst->free_query('Re-enter admin password', '');
				$check = @($temp_password == $conf['interface_password'])?true:false;
				if(!$check) swriteln('Passwords do not match.');
			} while (!$check);
		}
		unset($check);
		unset($temp_password);
		if($conf['apache']['installed'] == true) $conf['apache']['vhost_port']  = $ispconfig_vhost_port;
		if($conf['nginx']['installed'] == true) $conf['nginx']['vhost_port']  = $ispconfig_vhost_port;
		unset($ispconfig_vhost_port);

		if(strtolower($inst->simple_query('Enable SSL for the ISPConfig web interface', array('y', 'n'), 'y','ispconfig_use_ssl')) == 'y') {
			$inst->make_ispconfig_ssl_cert();
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
	
	swriteln('Detect IP addresses');
	$inst->detect_ips();



} //* << $install_mode / 'Standard' or Genius

$inst->create_mount_script();

//* Create md5 filelist
$md5_filename = '/usr/local/ispconfig/security/data/file_checksums_'.date('Y-m-d_h-i').'.md5';
exec('find /usr/local/ispconfig -type f -print0 | xargs -0 md5sum > '.$md5_filename);
chmod($md5_filename,0700);


echo "Installation completed.\n";


?>
