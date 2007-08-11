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

// Check for existing installation
//if(is_dir("/usr/local/ispconfig")) die('We will stop here. There is already a ISPConfig installation, use the update script to update this installation.');


// Include the library with the basic installer functions
require_once('lib/install.lib.php');

// Include the base class of the installer class
require_once('lib/installer_base.lib.php');

$distname = get_distname();

// Include the distribution specific installer class library
// and configuration
include_once('dist/lib/'.$distname.'.lib.php');
include_once('dist/conf/'.$distname.'.conf.php');

$inst = new installer();



swriteln($inst->lng("This application will install ISPConfig 3 on your server.");

// Select the language
$conf["language"] = $inst->simple_query('Select language',array('en','de'),'en');

// Select installation mode
$install_mode = $inst->simple_query('Installation mode',array('Standard','Expert'),'Standard');

// Get the hostname
$tmp_out = array();
exec("hostname -f",$tmp_out);
$conf["hostname"] = $inst->free_query('Full qualified hostname (FQDN) of the server',$tmp_out[0]);
unset($tmp_out);



// Get MySQL root password
include_once('lib/mysql.lib.php');
$finished = false;
do {
	$conf["mysql_server_admin_password"] = $inst->free_query('MySQL root password','');
	// Initialize the MySQL server connection
	$inst->db = new db();
	if($inst->db->connect() == false) {
		swriteln($inst->db->errorMessage);
	} else {
		$finished = true;
	}
} while ($finished == false);



// Begin with standard or expert installation
if($install_mode == 'Standard') {
	
	// Create the mysql database
	$inst->configure_database();

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
	

	// Configure Pureftpd
	swriteln('Configuring Pureftpd');
	$inst->configure_pureftpd();

	// Configure MyDNS
	swriteln('Configuring MyDNS');
	$inst->configure_mydns();

	// Configure ISPConfig
	swriteln('Installing ISPConfig');
	$inst->install_ispconfig();

	// Configure ISPConfig
	swriteln('Installing Crontab');
	$inst->install_crontab();
	
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
	
} else {
	
	// Get Server ID
	$conf["server_id"] = $inst->free_query('Unique Numeric ID of the server','1');
	
	if(strtolower($inst->simple_query('Create Database',array('y','n'),'y')) == 'y') {
		// Create the mysql database
		$inst->configure_database();
		system("/etc/init.d/mysql restart");
	}
	
	if(strtolower($inst->simple_query('Configure Mail',array('y','n'),'y')) == 'y') {
		
		// Configure Postfix
		swriteln('Configuring Postfix');
		$inst->configure_postfix();
		
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
		
		system("/etc/init.d/postfix restart");
		system("/etc/init.d/saslauthd restart");
		system("/etc/init.d/amavis restart");
		system("/etc/init.d/clamav-daemon restart");
		system("/etc/init.d/courier-authdaemon restart");
		system("/etc/init.d/courier-imap restart");
		system("/etc/init.d/courier-imap-ssl restart");
		system("/etc/init.d/courier-pop restart");
		system("/etc/init.d/courier-pop-ssl restart");
	}
	
	if(strtolower($inst->simple_query('Configure FTP Server',array('y','n'),'y')) == 'y') {
		// Configure Pureftpd
		swriteln('Configuring Pureftpd');
		$inst->configure_pureftpd();
		system("/etc/init.d/pure-ftpd-mysql restart");
	}
	
	if(strtolower($inst->simple_query('Configure DNS Server',array('y','n'),'y')) == 'y') {
		// Configure MyDNS
		swriteln('Configuring MyDNS');
		$inst->configure_mydns();
		system("/etc/init.d/mydns restart");
	}
	
	if(strtolower($inst->simple_query('Install ISPConfig',array('y','n'),'y')) == 'y') {
		// Configure ISPConfig
		swriteln('Installing ISPConfig');
		$inst->install_ispconfig();

		// Configure ISPConfig
		swriteln('Installing Crontab');
		$inst->install_crontab();
		
		system("/etc/init.d/apache2 restart");
	}
	
	
}


echo "Installation finished.\n";


?>