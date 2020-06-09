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

//* Installer patch stub class
class installer_patch_update {
	public function onBeforeSQL() {
	}

	public function onAfterSQL() {
	}

}

//* DB dump function
function prepareDBDump() {
	global $conf;

	//** load the pre update sql script do perform modifications on the database before the database is dumped
	if(is_file(ISPC_INSTALL_ROOT."/install/sql/pre_update.sql")) {
		if($conf['mysql']['admin_password'] == '') {
			caselog("mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." ".escapeshellarg($conf['mysql']['database'])." < '".ISPC_INSTALL_ROOT."/install/sql/pre_update.sql' &> /dev/null", __FILE__, __LINE__, 'read in pre_update.sql', 'could not read in pre_update.sql');
		} else {
			caselog("mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -p".escapeshellarg($conf['mysql']['admin_password'])." ".escapeshellarg($conf['mysql']['database'])." < '".ISPC_INSTALL_ROOT."/install/sql/pre_update.sql' &> /dev/null", __FILE__, __LINE__, 'read in pre_update.sql', 'could not read in pre_update.sql');
		}
	}

	//** export the current database data
	if( !empty($conf["mysql"]["admin_password"]) ) {

		system("mysqldump -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -p".escapeshellarg($conf['mysql']['admin_password'])." -c -t --add-drop-table --create-options --quick --result-file=existing_db.sql ".$conf['mysql']['database']);
	}
	else {

		system("mysqldump -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -c -t --add-drop-table --create-options --quick --result-file=existing_db.sql ".$conf['mysql']['database']);
	}
	chmod('existing_db.sql', 0400);
	chown('existing_db.sql', 'root');

	/*
	 * If we have a server with nothing in it except VE's then the database of thie server is empty.
	 * so the following line will no longer work!
	 */
	//if(filesize('existing_db.sql') < 30000) die('Possible problem with dumping the database. We will stop here. Please check the file existing_db.sql');

	// create a backup copy of the ispconfig database in the root folder
	if(isset($conf['backup_path'])) {
		$backup_db_name = $conf['backup_path'].'/ispconfig_db_backup.sql';
	} else {
		$backup_db_name = '/root/ispconfig_db_backup_'.@date('Y-m-d_H-i').'.sql';
	}
	copy('existing_db.sql', $backup_db_name);
	chmod($backup_db_name, 0700);
	chown($backup_db_name, 'root');
	chgrp($backup_db_name, 'root');

	if ($conf['powerdns']['installed']) {
		//** export the current PowerDNS database data
		if( !empty($conf["mysql"]["admin_password"]) ) {
			system("mysqldump -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -p".escapeshellarg($conf['mysql']['admin_password'])." -P ".escapeshellarg($conf['mysql']['port'])." -c -t --add-drop-table --create-options --quick --result-file=existing_powerdns_db.sql ".$conf['powerdns']['database']);
		} else {
			system("mysqldump -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -P ".escapeshellarg($conf['mysql']['port'])." -c -t --add-drop-table --create-options --quick --result-file=existing_powerdns_db.sql ".$conf['powerdns']['database']);
		}

		// create a backup copy of the PowerDNS database in the root folder
		$backup_db_name = '/root/ispconfig_powerdns_db_backup_'.@date('Y-m-d_h-i').'.sql';
		copy('existing_powerdns_db.sql', $backup_db_name);
		chmod($backup_db_name, 0700);
		chown($backup_db_name, 'root');
		chgrp($backup_db_name, 'root');
	}
}

function checkDbHealth() {
	global $conf;

	//* Array containing non OK tables (can be repaired, crashed, corrupt)
	$notok = array();

	echo "Checking ISPConfig database .. ";
	exec("mysqlcheck -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -p".escapeshellarg($conf['mysql']['admin_password'])." -P ".escapeshellarg($conf['mysql']['port'])." --auto-repair ".escapeshellarg($conf["mysql"]["database"]), $result);
	for( $i=0; $i<sizeof($result);$i++) {
		if ( substr($result[$i], -2) != "OK" ) {
			$notok[] = $result[$i];
		}
	}

	if ( sizeof($notok) > 0 ) {
		echo "\nSome tables where not 'OK'. Please check the list below.\n\n";
		foreach ($notok as $key => $value) {
			echo "$value\n";
		}
		echo "\nPress enter to continue or CTRL-C to cancel the installation ..";
		sread();
	}
	else
		echo "OK\n";
}

function updateDbAndIni() {
	global $inst, $conf;

	//* check sql-mode
	/*
	$check_sql_mode = $inst->db->queryOneRecord("SELECT @@sql_mode");
	if ($check_sql_mode['@@sql_mode'] != '' && $check_sql_mode['@@sql_mode'] != 'NO_ENGINE_SUBSTITUTION') {
		echo "Wrong SQL-mode. You should use NO_ENGINE_SUBSTITUTION. Add\n\n";
		echo "    sql-mode=\"NO_ENGINE_SUBSTITUTION\"\n\n";
		echo"to the mysqld-section in your mysql-config on this server and restart mysqld afterwards\n";
		die();
	}*/

	$unwanted_sql_plugins = array('validate_password');
	$sql_plugins = $inst->db->queryAllRecords("SELECT plugin_name FROM information_schema.plugins WHERE plugin_status='ACTIVE' AND plugin_name IN ?", $unwanted_sql_plugins);
	if(is_array($sql_plugins) && !empty($sql_plugins)) {
		foreach ($sql_plugins as $plugin) echo "Login in to MySQL and disable $plugin[plugin_name] with:\n\n    UNINSTALL PLUGIN $plugin[plugin_name];";
		die();
	}

	//* Update $conf array with values from the server.ini that shall be preserved
	$tmp = $inst->db->queryOneRecord("SELECT * FROM ?? WHERE server_id = ?", $conf["mysql"]["database"] . '.server', $conf['server_id']);
	$ini_array = ini_to_array(stripslashes($tmp['config']));
	$current_db_version = (isset($tmp['dbversion']))?intval($tmp['dbversion']):0;

	if(!is_array($ini_array) or count($ini_array) == 0) die('Unable to read server configuration from database.');

	$conf['services']['mail'] = ($tmp['mail_server'] == 1)?true:false;
	$conf['services']['web'] = ($tmp['web_server'] == 1)?true:false;
	$conf['services']['dns'] = ($tmp['dns_server'] == 1)?true:false;
	$conf['services']['file'] = ($tmp['file_server'] == 1)?true:false;
	$conf['services']['db'] = ($tmp['db_server'] == 1)?true:false;
	$conf['services']['vserver'] = ($tmp['vserver_server'] == 1)?true:false;
	$conf['services']['proxy'] = (isset($tmp['proxy_server']) && $tmp['proxy_server'] == 1)?true:false;
	$conf['services']['firewall'] = (isset($tmp['firewall_server']) && $tmp['firewall_server'] == 1)?true:false;

	$conf['postfix']['vmail_mailbox_base'] = $ini_array['mail']['homedir_path'];

	if(isset($ini_array['web']['server_type']) && $ini_array['web']['server_type'] != ''){
		$conf['webserver']['server_type'] = $ini_array['web']['server_type'];
		if($conf['webserver']['server_type'] == 'nginx'){
			$conf['apache']['installed'] = false;
		} else {
			$conf['nginx']['installed'] = false;
		}
	} else {
		$conf['webserver']['server_type'] = 'apache';
		$conf['nginx']['installed'] = false;
	}

	//* Do incremental DB updates only on installed ISPConfig versions >= 3.0.3
	if(version_compare('3.0.3', ISPC_APP_VERSION, '<=')) {

		swriteln($inst->lng('Starting incremental database update.'));

		//* get the version of the db schema from the server table
		$found = true;
		$dev_patch = false;
		while($found == true) {
			if($dev_patch == true) $next_db_version = 'dev_collection';
			else $next_db_version = intval($current_db_version + 1);
			$sql_patch_filename = realpath(dirname(__FILE__).'/../').'/sql/incremental/upd_'.str_pad($next_db_version, 4, '0', STR_PAD_LEFT).'.sql';
			$php_patch_filename = realpath(dirname(__FILE__).'/../').'/patches/upd_'.str_pad($next_db_version, 4, '0', STR_PAD_LEFT).'.php';
			
			// comma separated list of version numbers were a update has to be done silently
			$silent_update_versions = 'dev_collection,75';

			if(is_file($sql_patch_filename)) {

				//* Load php patch file and instantiate object
				if(is_file($php_patch_filename)) {
					$php_patch_class_name = 'upd_'.str_pad($next_db_version, 4, '0', STR_PAD_LEFT);
					include_once $php_patch_filename;
					if(class_exists($php_patch_class_name)) {
						$php_patch = new $php_patch_class_name;
					} else {
						swriteln($inst->lng('WARNING: PHP patch file').': '.$php_patch_filename.' '.$inst->lng('contains errors.'));
					}
				}

				//* Exec onBeforeSQL function
				if(isset($php_patch) && is_object($php_patch) && method_exists($php_patch, 'onBeforeSQL')) {
					$php_patch->onBeforeSQL();
					swriteln($inst->lng('Executing PHP patch file').': '.$php_patch_filename);
				}

				//* Load patch file into database
				if( !empty($conf["mysql"]["admin_password"]) ) {
					$cmd = "mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." --force -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -p".escapeshellarg($conf['mysql']['admin_password'])." -P ".escapeshellarg($conf['mysql']['port'])." ".escapeshellarg($conf['mysql']['database'])." < ".$sql_patch_filename;
				} else {
					$cmd = "mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." --force -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -P ".escapeshellarg($conf['mysql']['port'])." ".escapeshellarg($conf['mysql']['database'])." < ".$sql_patch_filename;
				}
				
				if(in_array($next_db_version,explode(',',$silent_update_versions))) {
					$cmd .= ' > /dev/null 2> /dev/null';
				} else {
					$cmd .= ' >> /var/log/ispconfig_install.log 2>> /var/log/ispconfig_install.log';
				}
				system($cmd);
				
				swriteln($inst->lng('Loading SQL patch file').': '.$sql_patch_filename);

				//* Exec onAfterSQL function
				if(isset($php_patch) && is_object($php_patch) && method_exists($php_patch, 'onAfterSQL')) {
					$php_patch->onAfterSQL();
				}

				if($dev_patch == false) $current_db_version = $next_db_version;
				else $found = false;
				
				if(isset($php_patch)) unset($php_patch);
			} elseif($dev_patch == false) {
				$dev_patch = true;
			} else {
				$found = false;
			}
		}

		//* update the database version in server table
		$inst->db->query("UPDATE ?? SET dbversion = ? WHERE server_id = ?", $conf["mysql"]["database"] . ".server", $current_db_version, $conf['server_id']);
		if($inst->db->dbHost != $inst->dbmaster->dbHost) $inst->dbmaster->query("UPDATE ?? SET dbversion = ? WHERE server_id = ?", $conf["mysql"]["master_database"] . ".server", $current_db_version, $conf['server_id']);


		//* If ISPConfig Version < 3.0.3, we will do a full db update
	} else {

		swriteln($inst->lng('Starting full database update.'));

		//** Delete the old database
		if( !$inst->db->query('DROP DATABASE IF EXISTS ??', $conf['mysql']['database']) ) {
			$inst->error('Unable to drop MySQL database: '.$conf['mysql']['database'].'.');
		}

		//** Create the mysql database
		$inst->configure_database();

		//** empty all databases
		$db_tables = $inst->db->getTables();

		foreach($db_tables as $table) {
			$inst->db->query("TRUNCATE ??", $table);
		}

		//** load old data back into database
		if( !empty($conf["mysql"]["admin_password"]) ) {
			system("mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." --force -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -p".escapeshellarg($conf['mysql']['admin_password'])." ".escapeshellarg($conf['mysql']['database'])." < existing_db.sql");
		} else {
			system("mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." --force -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." ".escapeshellarg($conf['mysql']['database'])." < existing_db.sql");
		}

		//** Get the database version number based on the patchfile
		$found = true;
		while($found == true) {
			$next_db_version = intval($current_db_version + 1);
			$patch_filename = realpath(dirname(__FILE__).'/../').'/sql/incremental/upd_'.str_pad($next_db_version, 4, '0', STR_PAD_LEFT).'.sql';
			if(is_file($patch_filename)) {
				$current_db_version = $next_db_version;
			} else {
				$found = false;
			}
		}

		//* update the database version in server table
		$inst->db->query("UPDATE ?? SET dbversion = ? WHERE server_id = ?", $conf["mysql"]["database"] . ".server", $current_db_version, $conf['server_id']);
		if($inst->db->dbHost != $inst->dbmaster->dbHost) $inst->dbmaster->query("UPDATE ?? SET dbversion = ? WHERE server_id = ?", $conf["mysql"]["master_database"] . ".server", $current_db_version, $conf['server_id']);

		if ($conf['powerdns']['installed']) {

			swriteln($inst->lng('Starting full PowerDNS database update.'));

			//** Delete the old PowerDNS database
			if( !$inst->db->query('DROP DATABASE IF EXISTS ??', $conf['powerdns']['database']) ) {
				$inst->error('Unable to drop MySQL database: '.$conf['powerdns']['database'].'.');
			}

			//** Create the mysql database
			$inst->configure_powerdns();

			//** load old data back into the PowerDNS database
			if( !empty($conf["mysql"]["admin_password"]) ) {
				system("mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." --force -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -p".escapeshellarg($conf['mysql']['admin_password'])." ".escapeshellarg($conf['powerdns']['database'])." < existing_powerdns_db.sql");
			} else {
				system("mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." --force -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." ".escapeshellarg($conf['powerdns']['database'])." < existing_powerdns_db.sql");
			}
		}
	}


	//** Update server ini
	$tmp_server_rec = $inst->db->queryOneRecord("SELECT config FROM ?? WHERE server_id = ?", $conf["mysql"]["database"] . ".server", $conf['server_id']);
	$old_ini_array = ini_to_array(stripslashes($tmp_server_rec['config']));
	unset($tmp_server_rec);
	$tpl_ini_array = ini_to_array(rf('tpl/server.ini.master'));

	//* Update further distribution specific parameters for server config here
	//* HINT: Every line added here has to be added in installer_base.lib.php too!!
	$tpl_ini_array['jailkit']['jailkit_chroot_app_programs'] = $conf['jailkit']['jailkit_chroot_app_programs'];
	$tpl_ini_array['fastcgi']['fastcgi_phpini_path'] = $conf['fastcgi']['fastcgi_phpini_path'];
	$tpl_ini_array['fastcgi']['fastcgi_starter_path'] = $conf['fastcgi']['fastcgi_starter_path'];
	$tpl_ini_array['fastcgi']['fastcgi_bin'] = $conf['fastcgi']['fastcgi_bin'];
	$tpl_ini_array['server']['hostname'] = $conf['hostname'];
	$tpl_ini_array['server']['ip_address'] = @gethostbyname($conf['hostname']);
	$tpl_ini_array['web']['website_basedir'] = $conf['web']['website_basedir'];
	$tpl_ini_array['web']['website_path'] = $conf['web']['website_path'];
	$tpl_ini_array['web']['website_symlinks'] = $conf['web']['website_symlinks'];
	$tpl_ini_array['cron']['crontab_dir'] = $conf['cron']['crontab_dir'];
	$tpl_ini_array['web']['security_level'] = 20;
	$tpl_ini_array['web']['user'] = $conf['apache']['user'];
	$tpl_ini_array['web']['group'] = $conf['apache']['group'];
	$tpl_ini_array['web']['php_ini_path_apache'] = $conf['apache']['php_ini_path_apache'];
	$tpl_ini_array['web']['php_ini_path_cgi'] = $conf['apache']['php_ini_path_cgi'];
	$tpl_ini_array['mail']['pop3_imap_daemon'] = ($conf['dovecot']['installed'] == true)?'dovecot':'courier';
	$tpl_ini_array['mail']['mail_filter_syntax'] = ($conf['dovecot']['installed'] == true)?'sieve':'maildrop';
	// do not switch to rspamd automatically!
	// $tpl_ini_array['mail']['content_filter'] = @($conf['rspamd']['installed']) ? 'rspamd' : 'amavisd';
	$tpl_ini_array['mail']['rspamd_available'] = @($conf['rspamd']['installed']) ? 'y' : 'n';
	$tpl_ini_array['dns']['bind_user'] = $conf['bind']['bind_user'];
	$tpl_ini_array['dns']['bind_group'] = $conf['bind']['bind_group'];
	$tpl_ini_array['dns']['bind_zonefiles_dir'] = $conf['bind']['bind_zonefiles_dir'];
	$tpl_ini_array['dns']['named_conf_path'] = $conf['bind']['named_conf_path'];
	$tpl_ini_array['dns']['named_conf_local_path'] = $conf['bind']['named_conf_local_path'];

	$tpl_ini_array['web']['nginx_vhost_conf_dir'] = $conf['nginx']['vhost_conf_dir'];
	$tpl_ini_array['web']['nginx_vhost_conf_enabled_dir'] = $conf['nginx']['vhost_conf_enabled_dir'];
	$tpl_ini_array['web']['nginx_user'] = $conf['nginx']['user'];
	$tpl_ini_array['web']['nginx_group'] = $conf['nginx']['group'];
	$tpl_ini_array['web']['nginx_cgi_socket'] = $conf['nginx']['cgi_socket'];
	$tpl_ini_array['web']['php_fpm_init_script'] = $conf['nginx']['php_fpm_init_script'];
	$tpl_ini_array['web']['php_fpm_ini_path'] = $conf['nginx']['php_fpm_ini_path'];
	$tpl_ini_array['web']['php_fpm_pool_dir'] = $conf['nginx']['php_fpm_pool_dir'];
	$tpl_ini_array['web']['php_fpm_start_port'] = $conf['nginx']['php_fpm_start_port'];
	$tpl_ini_array['web']['php_fpm_socket_dir'] = $conf['nginx']['php_fpm_socket_dir'];

	if ($conf['nginx']['installed'] == true) {
		$tpl_ini_array['web']['server_type'] = 'nginx';
		$tpl_ini_array['global']['webserver'] = 'nginx';
	}

	//* update the new template with the old values
	if(is_array($old_ini_array)) {
		foreach($old_ini_array as $tmp_section_name => $tmp_section_content) {
			foreach($tmp_section_content as $tmp_var_name => $tmp_var_content) {
				$tpl_ini_array[$tmp_section_name][$tmp_var_name] = $tmp_var_content;
			}
		}
	}

	$new_ini = array_to_ini($tpl_ini_array);
	$sql = "UPDATE ?? SET config = ? WHERE server_id = ?";
	$inst->db->query($sql, $conf["mysql"]["database"] . ".server", $new_ini, $conf['server_id']);

	if($inst->db->dbHost != $inst->dbmaster->dbHost) {
		$sql = "UPDATE ?? SET config = ? WHERE server_id = ?";
		$inst->dbmaster->query($sql, $conf["mysql"]["master_database"].".server", $new_ini, $conf['server_id']);
	}
	unset($old_ini_array);
	unset($tpl_ini_array);
	unset($new_ini);


	//** Update system ini
	$tmp_server_rec = $inst->db->queryOneRecord("SELECT config FROM ?? WHERE sysini_id = 1", $conf["mysql"]["database"] . ".sys_ini");
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
	$tmp = $inst->db->queryOneRecord('SELECT count(sysini_id) as number FROM ?? WHERE 1', $conf["mysql"]["database"] . '.sys_ini');
	if($tmp['number'] == 0) {
		$inst->db->query("INSERT INTO ?? (sysini_id, config) VALUES (1,?)", $conf["mysql"]["database"] . ".sys_ini", $new_ini);
	} else {
		$inst->db->query("UPDATE ?? SET config = ? WHERE sysini_id = 1", $conf["mysql"]["database"] . ".sys_ini", $new_ini);
	}
	unset($old_ini_array);
	unset($tpl_ini_array);
	unset($new_ini);

	// Truncate sys_session
	$inst->db->query('TRUNCATE ??', $conf['mysql']['database'].'sys_session');
}



function setDefaultServers(){
	global $inst, $conf;
	
	// clients
	$clients = $inst->db->queryAllRecords("SELECT * FROM ".$conf["mysql"]["database"].".client");
	if(is_array($clients) && !empty($clients)){
		foreach($clients as $client){
			// mailserver
			if(trim($client['mail_servers']) == '') $inst->db->query("UPDATE ?? SET mail_servers = ? WHERE client_id = ?", $conf["mysql"]["database"].".client", trim($client['default_mailserver']), $client['client_id']);
			// webserver
			if(trim($client['web_servers']) == '') $inst->db->query("UPDATE ?? SET web_servers = ? WHERE client_id = ?", $conf["mysql"]["database"].".client", trim($client['default_webserver']), $client['client_id']);
			// dns server
			if(trim($client['dns_servers']) == '') $inst->db->query("UPDATE ?? SET dns_servers = ? WHERE client_id = ?", $conf["mysql"]["database"].".client", trim($client['default_dnsserver']), $client['client_id']);
			// db server
			if(trim($client['db_servers']) == '') $inst->db->query("UPDATE ?? SET db_servers = ? WHERE client_id = ?", $conf["mysql"]["database"].".client", trim($client['default_dbserver']), $client['client_id']);
		}
	}
	
}



/** Checks if a detected service differs from db setup and asks the user what to do
 *	@param $servicename string the name of the Database-Field in "servers" for this service
 *	@param $detected_value boolean The result of service detection
 */
function check_service_config_state($servicename, $detected_value) {
	global $current_svc_config, $inst, $conf;
	
	if ($current_svc_config[$servicename] == 1) $current_state = 1;
	else $current_state = 0;

	if ($detected_value) $detected_value = 1;
	else $detected_value = 0;
	
	if ($detected_value != $current_state) {
		$answer = $inst->simple_query('Service \''.$servicename.'\' '.($detected_value ? 'has been' : 'has not been').' detected ('.($current_state ? 'strongly recommended, currently enabled' : 'currently disabled').') do you want to '.($detected_value ? 'enable and configure' : 'disable').' it? ', array('yes', 'no'), ($current_state ? 'yes' : 'no'), 'svc_detect_change_'.$servicename);
		if ($answer == 'yes') return $detected_value;
		else {
			if ($servicename == 'web_server') echo "\033[0;33mWARNING: If ISPConfig-Interface (Webfrontend) is installed on this Server we will configure the Web Server anyways but will not enable it in ISPConfig.\033[0m\n\n";
			return $current_state;
		}
	} else return $current_state;
}

?>
