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
	ISPConfig 3 updater.
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
echo "\n\n>> Update  \n\n";

//** Include the library with the basic installer functions
require_once('lib/install.lib.php');

//** Include the base class of the installer class
require_once('lib/installer_base.lib.php');

//** Install logfile
define('ISPC_LOG_FILE', '/var/log/ispconfig_install.log');
define('ISPC_INSTALL_ROOT', realpath(dirname(__FILE__).'/../'));

//** Check for ISPConfig 2.x versions
if(is_dir('/root/ispconfig') || is_dir('/home/admispconfig')) {
	die('This software can not be installed on a server wich runs ISPConfig 2.x.');
}

//** Get distribution identifier
$dist = get_distname();

include_once("/usr/local/ispconfig/server/lib/config.inc.php");
$conf_old = $conf;
unset($conf);

if($dist['id'] == '') die('Linux Dustribution or Version not recognized.');

//** Include the distribution specific installer class library and configuration
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

if(isset($conf_old["dbmaster_host"])) $conf["mysql"]["master_host"] = $conf_old["dbmaster_host"];
if(isset($conf_old["dbmaster_database"])) $conf["mysql"]["master_database"] = $conf_old["dbmaster_database"];
if(isset($conf_old["dbmaster_user"])) $conf["mysql"]["master_ispconfig_user"] = $conf_old["dbmaster_user"];
if(isset($conf_old["dbmaster_password"])) $conf["mysql"]["master_ispconfig_password"] = $conf_old["dbmaster_password"];

// Resolve the IP address of the mysql hostname.
if(!$conf['mysql']['ip'] = gethostbyname($conf['mysql']['host'])) die('Unable to resolve hostname'.$conf['mysql']['host']);

$conf['server_id'] = intval($conf_old["server_id"]);
$conf['ispconfig_log_priority'] = $conf_old["log_priority"];

$inst = new installer();

//** Detect the installed applications
$inst->find_installed_apps();

echo "This application will update ISPConfig 3 on your server.\n";

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


//* initialize the database
$inst->db = new db();

//* Update $conf array with values from the server.ini that shall be preserved
$tmp = $inst->db->queryOneRecord("SELECT * FROM ".$conf["mysql"]["database"].".server WHERE server_id = ".$conf['server_id']);
$ini_array = ini_to_array(stripslashes($tmp['config']));

$conf['services']['mail'] = ($tmp['mail_server'] == 1)?true:false;
$conf['services']['web'] = ($tmp['web_server'] == 1)?true:false;
$conf['services']['dns'] = ($tmp['dns_server'] == 1)?true:false;
$conf['services']['file'] = ($tmp['file_server'] == 1)?true:false;
$conf['services']['db'] = ($tmp['db_server'] == 1)?true:false;
$conf['services']['vserver'] = ($tmp['vserver_server'] == 1)?true:false;
$conf['postfix']['vmail_mailbox_base'] = $ini_array['mail']['homedir_path'];

//** Delete the old database
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

	system("mysql --default-character-set=".$conf['mysql']['charset']." -h ".$conf['mysql']['host']." -u ".$conf['mysql']['admin_user']." -p".$conf['mysql']['admin_password']." ".$conf['mysql']['database']." < existing_db.sql");
} else {

	system("mysql --default-character-set=".$conf['mysql']['charset']." -h ".$conf['mysql']['host']." -u ".$conf['mysql']['admin_user']." ".$conf['mysql']['database']." < existing_db.sql");
}

// create a backup copy of the ispconfig database in the root folder
$backup_db_name = '/root/ispconfig_db_backup_'.@date('Y-m-d_h-i').'.sql';
copy('existing_db.sql',$backup_db_name);
exec("chmod 700 $backup_db_name");
exec("chown root:root $backup_db_name");


//** Update server ini
$tmp_server_rec = $inst->db->queryOneRecord("SELECT config FROM server WHERE server_id = ".$conf['server_id']);
$old_ini_array = ini_to_array(stripslashes($tmp_server_rec['config']));
unset($tmp_server_rec);
$tpl_ini_array = ini_to_array(rf('tpl/server.ini.master'));

// update the new template with the old values
if(is_array($old_ini_array)) {
	foreach($old_ini_array as $tmp_section_name => $tmp_section_content) {
		foreach($tmp_section_content as $tmp_var_name => $tmp_var_content) {
			$tpl_ini_array[$tmp_section_name][$tmp_var_name] = $tmp_var_content;
		}
	}
}

$new_ini = array_to_ini($tpl_ini_array);
$inst->db->query("UPDATE server SET config = '".mysql_real_escape_string($new_ini)."' WHERE server_id = ".$conf['server_id']);
unset($old_ini_array);
unset($tpl_ini_array);
unset($new_ini);


//** Update system ini
$tmp_server_rec = $inst->db->queryOneRecord("SELECT config FROM sys_ini WHERE sysini_id = 1");
$old_ini_array = ini_to_array(stripslashes($tmp_server_rec['config']));
unset($tmp_server_rec);
$tpl_ini_array = ini_to_array(rf('tpl/system.ini.master'));

// update the new template with the old values
if(is_array($old_ini_array)) {
	foreach($old_ini_array as $tmp_section_name => $tmp_section_content) {
		foreach($tmp_section_content as $tmp_var_name => $tmp_var_content) {
			$tpl_ini_array[$tmp_section_name][$tmp_var_name] = $tmp_var_content;
		}
	}
}

$new_ini = array_to_ini($tpl_ini_array);
$inst->db->query("UPDATE sys_ini SET config = '".mysql_real_escape_string($new_ini)."' WHERE sysini_id = 1");
unset($old_ini_array);
unset($tpl_ini_array);
unset($new_ini);


//** Shall the services be reconfigured during update
$reconfigure_services_answer = $inst->simple_query('Reconfigure Services?', array('yes','no'),'yes');

if($reconfigure_services_answer == 'yes') {
	
	if($conf['services']['mail']) {
		//** Configure postfix
		$inst->configure_postfix('dont-create-certs');
	
		//* Configure postfix
		swriteln('Configuring Jailkit');
		$inst->configure_jailkit();
	
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
	}
	
	if($conf['services']['web']) {
		//** Configure Pureftpd
		swriteln('Configuring Pureftpd');
		$inst->configure_pureftpd();
	}
	
	if($conf['services']['dns']) {
		//** Configure MyDNS
		swriteln('Configuring MyDNS');
		$inst->configure_mydns();
	}
	
	if($conf['services']['web']) {
		//** Configure Apache
		swriteln('Configuring Apache');
		$inst->configure_apache();
	}
	

	//* Configure DBServer
	swriteln('Configuring Database');
	$inst->configure_dbserver();

	
	//if(@is_dir('/etc/Bastille')) {
	//* Configure Firewall
	swriteln('Configuring Firewall');
	$inst->configure_firewall();
	//}
}

//** Configure ISPConfig
swriteln('Updating ISPConfig');


//** Customise the port ISPConfig runs on
$conf['apache']['vhost_port'] = $inst->free_query('ISPConfig Port', '8080');

$inst->install_ispconfig();

//** Configure Crontab
$update_crontab_answer = $inst->simple_query('Reconfigure Crontab?', array('yes','no'),'yes');
if($update_crontab_answer == 'yes') {
	swriteln('Updating Crontab');
	$inst->install_crontab();
}

//** Restart services:
if($reconfigure_services_answer == 'yes') {
	swriteln('Restarting services ...');
	if($conf['mysql']['init_script'] != '' && is_file($conf['mysql']['init_script']))					system($conf['init_scripts'].'/'.$conf['mysql']['init_script'].' restart');
	if($conf['services']['mail']) {
		if($conf['postfix']['init_script'] != '' && is_file($conf['postfix']['init_script']))				system($conf['init_scripts'].'/'.$conf['postfix']['init_script'].' restart');
		if($conf['saslauthd']['init_script'] != '' && is_file($conf['saslauthd']['init_script']))			system($conf['init_scripts'].'/'.$conf['saslauthd']['init_script'].' restart');
		if($conf['amavis']['init_script'] != '' && is_file($conf['amavis']['init_script']))					system($conf['init_scripts'].'/'.$conf['amavis']['init_script'].' restart');
		if($conf['clamav']['init_script'] != '' && is_file($conf['clamav']['init_script']))					system($conf['init_scripts'].'/'.$conf['clamav']['init_script'].' restart');
		if($conf['courier']['courier-authdaemon'] != '' && is_file($conf['courier']['courier-authdaemon'])) system($conf['init_scripts'].'/'.$conf['courier']['courier-authdaemon'].' restart');
		if($conf['courier']['courier-imap'] != '' && is_file($conf['courier']['courier-imap'])) 			system($conf['init_scripts'].'/'.$conf['courier']['courier-imap'].' restart');
		if($conf['courier']['courier-imap-ssl'] != '' && is_file($conf['courier']['courier-imap-ssl'])) 	system($conf['init_scripts'].'/'.$conf['courier']['courier-imap-ssl'].' restart');
		if($conf['courier']['courier-pop'] != '' && is_file($conf['courier']['courier-pop'])) 				system($conf['init_scripts'].'/'.$conf['courier']['courier-pop'].' restart');
		if($conf['courier']['courier-pop-ssl'] != '' && is_file($conf['courier']['courier-pop-ssl'])) 		system($conf['init_scripts'].'/'.$conf['courier']['courier-pop-ssl'].' restart');
	}
	if($conf['services']['web']) {
		if($conf['apache']['init_script'] != '' && is_file($conf['apache']['init_script'])) 				system($conf['init_scripts'].'/'.$conf['apache']['init_script'].' restart');
		if($conf['pureftpd']['init_script'] != '' && is_file($conf['pureftpd']['init_script']))				system($conf['init_scripts'].'/'.$conf['pureftpd']['init_script'].' restart');
	}
	if($conf['services']['dns']) {
		if($conf['mydns']['init_script'] != '' && is_file($conf['mydns']['init_script']))					system($conf['init_scripts'].'/'.$conf['mydns']['init_script'].' restart &> /dev/null');
	}
}

echo "Update finished.\n";

?>
