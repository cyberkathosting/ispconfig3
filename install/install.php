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
echo ' _   _____   _____   _____   _____   __   _   _____   _   _____  
| | /  ___/ |  _  \ /  ___| /  _  \ |  \ | | |  ___| | | /  ___| 
| | | |___  | |_| | | |     | | | | |   \| | | |__   | | | |     
| | \___  \ |  ___/ | |     | | | | | |\   | |  __|  | | | |  _  
| |  ___| | | |     | |___  | |_| | | | \  | | |     | | | |_| | 
|_| /_____/ |_|     \_____| \_____/ |_|  \_| |_|     |_| \_____/ ';

echo "# Setup routine started...\n";

//** Include the library with the basic installer functions
require_once('lib/install.lib.php');

//** Include the base class of the installer class
require_once('lib/installer_base.lib.php');

include_once('options.conf.php');
$distname = get_distname();

//** Include the distribution specific installer class library and configuration
include_once('dist/lib/'.$conf['distname'].'.lib.php');
include_once('dist/conf/'.$conf['distname'].'.conf.php');

$conf['dist'] = $dist;

//** Lets go !
$inst = new installer();
swriteln($inst->lng('This application will install ISPConfig 3 on your server.'));

//** Select the language
$conf['language'] = $inst->simple_query('Select language', array('en','de'), 'en');

//** Select installation mode
$install_mode = $inst->simple_query('Installation mode', array('Standard','Expert'), 'Standard');

//** Get the hostname
$tmp_out = array();
exec('hostname -f', $tmp_out);
$conf['hostname'] = $inst->free_query('Full qualified hostname (FQDN) of the server', $tmp_out[0]);
unset($tmp_out);

//** Get MySQL root credentials
$finished = false;
do {
	$tmp_mysql_server_host = $inst->free_query('MySQL server hostname',$conf['mysql']['host']);
	$tmp_mysql_server_admin_user = $inst->free_query('MySQL root username',$conf['mysql']['admin_user']);
	$tmp_mysql_server_admin_password = $inst->free_query('MySQL root password',$conf['mysql']['admin_password']);
	
	//* Initialize the MySQL server connection
	if(@mysql_connect($tmp_mysql_server_host, $tmp_mysql_server_admin_user, $tmp_mysql_server_admin_password)) {
		$conf['mysql']['host'] = $tmp_mysql_server_host;
		$conf['mysql']['admin_user'] = $tmp_mysql_server_admin_user;
		$conf['mysql']['admin_password'] = $tmp_mysql_server_admin_password;
		$finished = true;
	} else {
		swriteln($inst->lng('Unable to connect to mysql server').' '.mysql_error());
	}
} while ($finished == false);
unset($finished);

//** initializing database connection
include_once('lib/mysql.lib.php');
$inst->db = new db();

//** Begin with standard or expert installation
if($install_mode == 'Standard') {
	
	//* Create the mysql database
	$inst->configure_database();

	//* Configure postfix
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
	

	//* Configure Pureftpd
	swriteln('Configuring Pureftpd');
	$inst->configure_pureftpd();

	//* Configure MyDNS
	swriteln('Configuring MyDNS');
	$inst->configure_mydns();
	
	//* Configure Apache
	swriteln('Configuring Apache');
	$inst->configure_apache();

	//* Configure ISPConfig
	swriteln('Installing ISPConfig');
	$inst->install_ispconfig();

	//* Configure ISPConfig
	swriteln('Installing Crontab');
	$inst->install_crontab();
	
	swriteln('Restarting services ...');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['mysql']['init_script'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['postfix']['init_script'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['saslauthd']['init_script'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['amavis']['init_script'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['clamav']['init_script'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-authdaemon'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-imap'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-imap-ssl'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-pop'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-pop-ssl'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['apache']['init_script'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['pureftpd']['init_script'].' restart');
	system($conf['dist']["init_scripts"].'/'.$conf['dist']['mydns']['init_script'].' restart');
	
}else{

	//** Get Server ID
	$conf['server_id'] = $inst->free_query('Unique Numeric ID of the server','1');
	
	if(strtolower($inst->simple_query('Create Database',array('y','n'),'y')) == 'y') {
		//* Create the mysql database
		$inst->configure_database();
		system('/etc/init.d/mysql restart');
	}
	
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
		
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['postfix']['init_script'].' restart');
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['saslauthd']['init_script'].' restart');
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['amavis']['init_script'].' restart');
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['clamav']['init_script'].' restart');
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-authdaemon'].' restart');
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-imap'].' restart');
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-imap-ssl'].' restart');
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-pop'].' restart');
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['courier']['courier-pop-ssl'].' restart');
	}
	
	//** Configure Pureftpd
	if(strtolower($inst->simple_query('Configure FTP Server', array('y','n'),'y') ) == 'y') {	
		swriteln('Configuring Pureftpd');
		$inst->configure_pureftpd();
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['pureftpd']['init_script'].' restart');
	}
	
	//** Configure MyDNS
	if(strtolower($inst->simple_query('Configure DNS Server',array('y','n'),'y')) == 'y') {
		swriteln('Configuring MyDNS');
		$inst->configure_mydns();
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['mydns']['init_script'].' restart');
	}
	
	//** Configure Apache
	if(strtolower($inst->simple_query('Configure Apache Server',array('y','n'),'y')) == 'y') {	
		swriteln('Configuring Apache');
		$inst->configure_apache();
	}
	
	//** Configure ISPConfig :-)
	if(strtolower($inst->simple_query('Install ISPConfig',array('y','n'),'y')) == 'y') {
		swriteln('Installing ISPConfig');
		$inst->install_ispconfig();
		
		//* Configure ISPConfig
		swriteln('Installing Crontab');
		$inst->install_crontab();
		system($conf['dist']["init_scripts"].'/'.$conf['dist']['apache']['init_script'].' restart');	
	}
	
} //* << $install_mode / 'Standard' or Genius


echo "Installation completed.\n";


?>