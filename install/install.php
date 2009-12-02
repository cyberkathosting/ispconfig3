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

error_reporting(E_ALL|E_STRICT);

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
echo "\n\n>> Initial configuration  \n\n";

//** Include the library with the basic installer functions
require_once('lib/install.lib.php');

//** Include the base class of the installer class
require_once('lib/installer_base.lib.php');

//** Ensure that current working directory is install directory
$cur_dir = getcwd();
if(realpath(dirname(__FILE__)) != $cur_dir) {
	chdir( realpath(dirname(__FILE__)) );
}

//** Install logfile
define('ISPC_LOG_FILE', '/var/log/ispconfig_install.log');
define('ISPC_INSTALL_ROOT', realpath(dirname(__FILE__).'/../'));

//** Check for existing installation
/*if(is_dir("/usr/local/ispconfig")) {
    die('We will stop here. There is already a ISPConfig installation, use the update script to update this installation.');
}*/

//** Get distribution identifier
$dist = get_distname();

if($dist['id'] == '') die('Linux Distribution or Version not recognized.');

//** Include the distribution specific installer class library and configuration
if(is_file('dist/lib/'.$dist['baseid'].'.lib.php')) include_once('dist/lib/'.$dist['baseid'].'.lib.php');
include_once('dist/lib/'.$dist['id'].'.lib.php');
include_once('dist/conf/'.$dist['id'].'.conf.php');

//****************************************************************************************************
//** Installer Interface 
//****************************************************************************************************
$inst = new installer();
swriteln($inst->lng('    Following will be a few questions for primary configuration so be careful.'));
swriteln($inst->lng('    Default values are in [brackets] and can be accepted with <ENTER>.'));
swriteln($inst->lng('    Tap in "quit" (without the quotes) to stop the installer.'."\n\n"));

//** Check log file is writable (probably not root or sudo)
if(!is_writable(dirname(ISPC_LOG_FILE))){
    die("ERROR: Cannot write to the directory ".dirname(ISPC_LOG_FILE).". Are you root or sudo ?\n\n");
}

if(is_dir('/root/ispconfig') || is_dir('/home/admispconfig')) {
	die('This software can not be installed on a server wich runs ISPConfig 2.x.');
}

//** Detect the installed applications
$inst->find_installed_apps();

//** Select the language
$conf['language'] = $inst->simple_query('Select language', array('en','de'), 'en');

//** Select installation mode
$install_mode = $inst->simple_query('Installation mode', array('standard','expert'), 'standard');


//** Get the hostname
$tmp_out = array();
exec('hostname -f', $tmp_out);
$conf['hostname'] = $inst->free_query('Full qualified hostname (FQDN) of the server, eg server1.domain.tld ', $tmp_out[0]);
unset($tmp_out);

// Check if the mysql functions are loaded in PHP
if(!function_exists('mysql_connect')) die('No PHP mysql functions available. Please ensure that the PHP mysql module is loaded.');

//** Get MySQL root credentials
$finished = false;
do {
	$tmp_mysql_server_host = $inst->free_query('MySQL server hostname', $conf['mysql']['host']);
	$tmp_mysql_server_admin_user = $inst->free_query('MySQL root username', $conf['mysql']['admin_user']);
	$tmp_mysql_server_admin_password = $inst->free_query('MySQL root password', $conf['mysql']['admin_password']);
    $tmp_mysql_server_database = $inst->free_query('MySQL database to create', $conf['mysql']['database']);
    $tmp_mysql_server_charset = $inst->free_query('MySQL charset', $conf['mysql']['charset']);
	
	//* Initialize the MySQL server connection
	if(@mysql_connect($tmp_mysql_server_host, $tmp_mysql_server_admin_user, $tmp_mysql_server_admin_password)) {
		$conf['mysql']['host'] = $tmp_mysql_server_host;
		$conf['mysql']['admin_user'] = $tmp_mysql_server_admin_user;
		$conf['mysql']['admin_password'] = $tmp_mysql_server_admin_password;
        $conf['mysql']['database'] = $tmp_mysql_server_database;
        $conf['mysql']['charset'] = $tmp_mysql_server_charset;
		$finished = true;
	} else {
		swriteln($inst->lng('Unable to connect to mysql server').' '.mysql_error());
	}
} while ($finished == false);
unset($finished);

// Resolve the IP address of the mysql hostname.
$tmp = explode(':',$conf['mysql']['host']);
if(!$conf['mysql']['ip'] = gethostbyname($tmp[0])) die('Unable to resolve hostname'.$tmp[0]);
unset($tmp);


//** initializing database connection
include_once('lib/mysql.lib.php');
$inst->db = new db();

//** Begin with standard or expert installation
if($install_mode == 'standard') {
	
	//* Create the mysql database
	$inst->configure_database();
	
	//* Insert the Server record into the database
	$inst->add_database_server_record();

	//* Configure postfix
	$inst->configure_postfix();
	
	//* Configure jailkit
	swriteln('Configuring Jailkit');
	$inst->configure_jailkit();

	//* Configure saslauthd
	swriteln('Configuring SASL');
	$inst->configure_saslauthd();

	//* Configure PAM
	swriteln('Configuring PAM');
	$inst->configure_pam();

	//* Configure courier
	swriteln('Configuring Courier');
	$inst->configure_courier();

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
	swriteln('Configuring Apache');
	$inst->configure_apache();
	
    //** Configure vlogger
    swriteln('Configuring vlogger');
    $inst->configure_vlogger();
	
	//** Configure apps vhost
	swriteln('Configuring Apps vhost');
	$inst->configure_apps_vhost();
    
	//* Configure Firewall
	swriteln('Configuring Firewall');
	$inst->configure_firewall();

	//* Configure ISPConfig
	swriteln('Installing ISPConfig');
	
	//** Customise the port ISPConfig runs on
	$conf['apache']['vhost_port'] = $inst->free_query('ISPConfig Port', '8080');

	$inst->install_ispconfig();
	
	//* Configure DBServer
	swriteln('Configuring DBServer');
	$inst->configure_dbserver();

	//* Configure ISPConfig
	swriteln('Installing Crontab');
	$inst->install_crontab();
	
	swriteln('Restarting services ...');
	if($conf['mysql']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['mysql']['init_script']))					system($conf['init_scripts'].'/'.$conf['mysql']['init_script'].' restart');
	if($conf['postfix']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['postfix']['init_script']))				system($conf['init_scripts'].'/'.$conf['postfix']['init_script'].' restart');
	if($conf['saslauthd']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['saslauthd']['init_script']))			system($conf['init_scripts'].'/'.$conf['saslauthd']['init_script'].' restart');
	if($conf['amavis']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['amavis']['init_script']))					system($conf['init_scripts'].'/'.$conf['amavis']['init_script'].' restart');
	if($conf['clamav']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['clamav']['init_script']))					system($conf['init_scripts'].'/'.$conf['clamav']['init_script'].' restart');
	if($conf['courier']['courier-authdaemon'] != '' && is_file($conf['init_scripts'].'/'.$conf['courier']['courier-authdaemon'])) system($conf['init_scripts'].'/'.$conf['courier']['courier-authdaemon'].' restart');
	if($conf['courier']['courier-imap'] != '' && is_file($conf['init_scripts'].'/'.$conf['courier']['courier-imap'])) 			system($conf['init_scripts'].'/'.$conf['courier']['courier-imap'].' restart');
	if($conf['courier']['courier-imap-ssl'] != '' && is_file($conf['init_scripts'].'/'.$conf['courier']['courier-imap-ssl'])) 	system($conf['init_scripts'].'/'.$conf['courier']['courier-imap-ssl'].' restart');
	if($conf['courier']['courier-pop'] != '' && is_file($conf['init_scripts'].'/'.$conf['courier']['courier-pop'])) 				system($conf['init_scripts'].'/'.$conf['courier']['courier-pop'].' restart');
	if($conf['courier']['courier-pop-ssl'] != '' && is_file($conf['init_scripts'].'/'.$conf['courier']['courier-pop-ssl'])) 		system($conf['init_scripts'].'/'.$conf['courier']['courier-pop-ssl'].' restart');
	if($conf['apache']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['apache']['init_script'])) 				system($conf['init_scripts'].'/'.$conf['apache']['init_script'].' restart');
	if($conf['pureftpd']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['pureftpd']['init_script']))				system($conf['init_scripts'].'/'.$conf['pureftpd']['init_script'].' restart');
	if($conf['mydns']['installed'] == true && $conf['mydns']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['mydns']['init_script']))					system($conf['init_scripts'].'/'.$conf['mydns']['init_script'].' restart &> /dev/null');
	if($conf['powerdns']['installed'] == true && $conf['powerdns']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['powerdns']['init_script']))					system($conf['init_scripts'].'/'.$conf['powerdns']['init_script'].' restart &> /dev/null');
	if($conf['bind']['installed'] == true && $conf['bind']['init_script'] != '' && is_file($conf['init_scripts'].'/'.$conf['bind']['init_script']))					system($conf['init_scripts'].'/'.$conf['bind']['init_script'].' restart &> /dev/null');
	
}else{
	
	//* In expert mode, we select the services in the following steps, only db is always available
	$conf['services']['mail'] = false;
	$conf['services']['web'] = false;
	$conf['services']['dns'] = false;
	$conf['services']['db'] = true;
	
	
	//** Get Server ID
	// $conf['server_id'] = $inst->free_query('Unique Numeric ID of the server','1');
	// Server ID is an autoInc value of the mysql database now
	
	if(strtolower($inst->simple_query('Shall this server join an existing ISPConfig multiserver setup',array('y','n'),'n')) == 'y') {
		$conf['mysql']['master_slave_setup'] = 'y';
		
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
		// the master DB is the same then the slave DB
		$inst->dbmaster = $inst->db;
	}
	
	//* Create the mysql database
	$inst->configure_database();
		
	//* Insert the Server record into the database
	swriteln('Adding ISPConfig server record to database.');
	swriteln('');
	$inst->add_database_server_record();

	
	if(strtolower($inst->simple_query('Configure Mail', array('y','n') ,'y') ) == 'y') {
		
		$conf['services']['mail'] = true;
		
		//* Configure Postfix
		swriteln('Configuring Postfix');
		$inst->configure_postfix();
		
		//* Configure saslauthd
		swriteln('Configuring SASL');
		$inst->configure_saslauthd();
		
		//* Configure PAM
		swriteln('Configuring PAM');
		$inst->configure_pam();

		//* Configure courier
		swriteln('Configuring Courier');
		$inst->configure_courier();

		//* Configure Spamasassin
		swriteln('Configuring Spamassassin');
		$inst->configure_spamassassin();

		//* Configure Amavis
		swriteln('Configuring Amavisd');
		$inst->configure_amavis();

		//* Configure Getmail
		swriteln('Configuring Getmail');
		$inst->configure_getmail();
		
		if($conf['postfix']['init_script'] != '')			system($conf['init_scripts'].'/'.$conf['postfix']['init_script'].' restart');
		if($conf['saslauthd']['init_script'] != '')			system($conf['init_scripts'].'/'.$conf['saslauthd']['init_script'].' restart');
		if($conf['amavis']['init_script'] != '')			system($conf['init_scripts'].'/'.$conf['amavis']['init_script'].' restart');
		if($conf['clamav']['init_script'] != '')			system($conf['init_scripts'].'/'.$conf['clamav']['init_script'].' restart');
		if($conf['courier']['courier-authdaemon'] != '') 	system($conf['init_scripts'].'/'.$conf['courier']['courier-authdaemon'].' restart');
		if($conf['courier']['courier-imap'] != '') 			system($conf['init_scripts'].'/'.$conf['courier']['courier-imap'].' restart');
		if($conf['courier']['courier-imap-ssl'] != '') 		system($conf['init_scripts'].'/'.$conf['courier']['courier-imap-ssl'].' restart');
		if($conf['courier']['courier-pop'] != '') 			system($conf['init_scripts'].'/'.$conf['courier']['courier-pop'].' restart');
		if($conf['courier']['courier-pop-ssl'] != '') 		system($conf['init_scripts'].'/'.$conf['courier']['courier-pop-ssl'].' restart');
	}
	
	//** Configure Jailkit
	if(strtolower($inst->simple_query('Configure Jailkit', array('y','n'),'y') ) == 'y') {	
		swriteln('Configuring Jailkit');
		$inst->configure_jailkit();
	}
	
	//** Configure Pureftpd
	if(strtolower($inst->simple_query('Configure FTP Server', array('y','n'),'y') ) == 'y') {	
		swriteln('Configuring Pureftpd');
		$inst->configure_pureftpd();
		if($conf['pureftpd']['init_script'] != '') system($conf['init_scripts'].'/'.$conf['pureftpd']['init_script'].' restart');
	}
	
	//** Configure DNS
	if(strtolower($inst->simple_query('Configure DNS Server',array('y','n'),'y')) == 'y') {
		$conf['services']['dns'] = true;
		//* Configure DNS
		if($conf['powerdns']['installed'] == true) {
			swriteln('Configuring PowerDNS');
			$inst->configure_powerdns();
			if($conf['powerdns']['init_script'] != '')	system($conf['init_scripts'].'/'.$conf['powerdns']['init_script'].' restart &> /dev/null');
		} elseif($conf['bind']['installed'] == true) {
			swriteln('Configuring BIND');
			$inst->configure_bind();
			if($conf['bind']['init_script'] != '')	system($conf['init_scripts'].'/'.$conf['bind']['init_script'].' restart &> /dev/null');
		} else {
			swriteln('Configuring MyDNS');
			$inst->configure_mydns();
			if($conf['mydns']['init_script'] != '')	system($conf['init_scripts'].'/'.$conf['mydns']['init_script'].' restart &> /dev/null');
		}
		
	}
	
	//** Configure Apache
	swriteln("\nHint: If this server shall run the ispconfig interface, select 'y' in the 'Configure Apache Server' option.\n");
	if(strtolower($inst->simple_query('Configure Apache Server',array('y','n'),'y')) == 'y') {	
		$conf['services']['web'] = true;
		swriteln('Configuring Apache');
		$inst->configure_apache();
        
        //** Configure vlogger
        swriteln('Configuring vlogger');
        $inst->configure_vlogger();
		
		//** Configure apps vhost
		swriteln('Configuring Apps vhost');
		$inst->configure_apps_vhost();
	}
	
	//** Configure Firewall
	if(strtolower($inst->simple_query('Configure Firewall Server',array('y','n'),'y')) == 'y') {	
		swriteln('Configuring Firewall');
		$inst->configure_firewall();
	}
	
	//** Configure ISPConfig :-)
	if(strtolower($inst->simple_query('Install ISPConfig Web-Interface',array('y','n'),'y')) == 'y') {
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
		$conf['apache']['vhost_port'] = $inst->free_query('ISPConfig Port', '8080');
		
		$inst->install_ispconfig_interface = true;
			
	} else {
		$inst->install_ispconfig_interface = false;
	}
	
	$inst->install_ispconfig();
	
	//* Configure DBServer
	swriteln('Configuring DBServer');
	$inst->configure_dbserver();
		
	//* Configure ISPConfig
	swriteln('Installing Crontab');
	$inst->install_crontab();
	if($conf['apache']['init_script'] != '' && @is_file($conf['init_scripts'].'/'.$conf['apache']['init_script'])) system($conf['init_scripts'].'/'.$conf['apache']['init_script'].' restart');
	
	
	
} //* << $install_mode / 'Standard' or Genius


echo "Installation completed.\n";


?>