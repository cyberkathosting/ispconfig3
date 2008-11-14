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

//** Check for existing installation
//if(is_dir("/usr/local/ispconfig")) die('We will stop here. There is already a ISPConfig installation, use the update script to update this installation.');

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

//** Get distribution identifier
$dist = get_distname();

if($dist['id'] == '') die('Linux Dustribution or Version not recognized.');

//** Include the distribution specific installer class library and configuration
if(is_file('dist/lib/'.$dist['baseid'].'.lib.php')) include_once('dist/lib/'.$dist['baseid'].'.lib.php');
include_once('dist/lib/'.$dist['id'].'.lib.php');
include_once('dist/conf/'.$dist['id'].'.conf.php');

//** Install logfile
define('ISPC_LOG_FILE', '/var/log/ispconfig_install.log');
define('ISPC_INSTALL_ROOT', realpath(dirname(__FILE__).'/../'));

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

//** Select the language
$conf['language'] = $inst->simple_query('Select language', array('en','de'), 'en');

//** Select installation mode
$install_mode = $inst->simple_query('Installation mode', array('Standard','Expert'), 'Standard');


//** Get the hostname
$tmp_out = array();
exec('hostname -f', $tmp_out);
$conf['hostname'] = $inst->free_query('Full qualified hostname (FQDN) of the server, eg foo.example.com ', $tmp_out[0]);
unset($tmp_out);

//** Get MySQL root credentials
$finished = false;
do {
	$tmp_mysql_server_host = $inst->free_query('MySQL server hostname', $conf['mysql']['host']);
	$tmp_mysql_server_admin_user = $inst->free_query('MySQL root username', $conf['mysql']['admin_user']);
	$tmp_mysql_server_admin_password = $inst->free_query('MySQL root password', $conf['mysql']['admin_password']);
    $tmp_mysql_server_database = $inst->free_query('MySQL database to create', $conf['mysql']['database']);
	
	//* Initialize the MySQL server connection
	if(@mysql_connect($tmp_mysql_server_host, $tmp_mysql_server_admin_user, $tmp_mysql_server_admin_password)) {
		$conf['mysql']['host'] = $tmp_mysql_server_host;
		$conf['mysql']['admin_user'] = $tmp_mysql_server_admin_user;
		$conf['mysql']['admin_password'] = $tmp_mysql_server_admin_password;
        $conf['mysql']['database'] = $tmp_mysql_server_database;
		$finished = true;
	} else {
		swriteln($inst->lng('Unable to connect to mysql server').' '.mysql_error());
	}
} while ($finished == false);
unset($finished);

// Resolve the IP address of the mysql hostname.
if(!$conf['mysql']['ip'] = gethostbyname($conf['mysql']['host'])) die('Unable to resolve hostname'.$conf['mysql']['host']);


//** initializing database connection
include_once('lib/mysql.lib.php');
$inst->db = new db();

//** Begin with standard or expert installation
if($install_mode == 'Standard') {
	
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

	//* Configure MyDNS
	swriteln('Configuring MyDNS');
	$inst->configure_mydns();
	
	//* Configure Apache
	swriteln('Configuring Apache');
	$inst->configure_apache();
	
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
	if($conf['mysql']['init_script'] != '')				system($conf['init_scripts'].'/'.$conf['mysql']['init_script'].' restart');
	if($conf['postfix']['init_script'] != '')			system($conf['init_scripts'].'/'.$conf['postfix']['init_script'].' restart');
	if($conf['saslauthd']['init_script'] != '')			system($conf['init_scripts'].'/'.$conf['saslauthd']['init_script'].' restart');
	if($conf['amavis']['init_script'] != '')			system($conf['init_scripts'].'/'.$conf['amavis']['init_script'].' restart');
	if($conf['clamav']['init_script'] != '')			system($conf['init_scripts'].'/'.$conf['clamav']['init_script'].' restart');
	if($conf['courier']['courier-authdaemon'] != '') 	system($conf['init_scripts'].'/'.$conf['courier']['courier-authdaemon'].' restart');
	if($conf['courier']['courier-imap'] != '') 			system($conf['init_scripts'].'/'.$conf['courier']['courier-imap'].' restart');
	if($conf['courier']['courier-imap-ssl'] != '') 		system($conf['init_scripts'].'/'.$conf['courier']['courier-imap-ssl'].' restart');
	if($conf['courier']['courier-pop'] != '') 			system($conf['init_scripts'].'/'.$conf['courier']['courier-pop'].' restart');
	if($conf['courier']['courier-pop-ssl'] != '') 		system($conf['init_scripts'].'/'.$conf['courier']['courier-pop-ssl'].' restart');
	if($conf['apache']['init_script'] != '') 			system($conf['init_scripts'].'/'.$conf['apache']['init_script'].' restart');
	if($conf['pureftpd']['init_script'] != '')			system($conf['init_scripts'].'/'.$conf['pureftpd']['init_script'].' restart');
	if($conf['mydns']['init_script'] != '')				system($conf['init_scripts'].'/'.$conf['mydns']['init_script'].' restart &> /dev/null');
	
}else{

	//** Get Server ID
	// $conf['server_id'] = $inst->free_query('Unique Numeric ID of the server','1');
	// Server ID is an autoInc value of the mysql database now
	
	if(strtolower($inst->simple_query('Create a new database? (We do not want to join a existing ISPConfig server setup)',array('y','n'),'y')) == 'y') {
		//* Create the mysql database
		$inst->configure_database();
		//system('/etc/init.d/mysql restart');
	}
		
	//* Insert the Server record into the database
	swriteln('Adding ISPConfig server record to database.');
	swriteln('');
	$inst->add_database_server_record();

	
	if(strtolower($inst->simple_query('Configure Mail', array('y','n') ,'y') ) == 'y') {
		
		//* Configure Postfix
		swriteln('Configuring Postfix');
		$inst->configure_postfix();
		
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
	
	//** Configure MyDNS
	if(strtolower($inst->simple_query('Configure DNS Server',array('y','n'),'y')) == 'y') {
		swriteln('Configuring MyDNS');
		$inst->configure_mydns();
		if($conf['mydns']['init_script'] != '')	system($conf['init_scripts'].'/'.$conf['mydns']['init_script'].' restart &> /dev/null');
	}
	
	//** Configure Apache
	swriteln("If this server shall run the ispconfig interface, select 'y' in the next option.");
	if(strtolower($inst->simple_query('Configure Apache Server',array('y','n'),'y')) == 'y') {	
		swriteln('Configuring Apache');
		$inst->configure_apache();
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
	if($conf['apache']['init_script'] != '') system($conf['init_scripts'].'/'.$conf['apache']['init_script'].' restart');
	
	
	
} //* << $install_mode / 'Standard' or Genius


echo "Installation completed.\n";


?>