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

class installer_base {

	var $wb = array();
	var $language = 'en';
	var $db;
	public $conf;
	public $install_ispconfig_interface = true;
	public $is_update = false; // true if it is an update, falsi if it is a new install


	public function __construct() {
		global $conf; //TODO: maybe $conf  should be passed to constructor
		//$this->conf = $conf;
	}

	//: TODO  Implement the translation function and language files for the installer.
	public function lng($text) {
		return $text;
	}

	public function error($msg) {
		die('ERROR: '.$msg."\n");
	}

	public function warning($msg) {
		echo 'WARNING: '.$msg."\n";
	}

	public function simple_query($query, $answers, $default) {
		$finished = false;
		do {
			$answers_str = implode(',', $answers);
			swrite($this->lng($query).' ('.$answers_str.') ['.$default.']: ');
			$input = sread();

			//* Stop the installation
			if($input == 'quit') {
				swriteln($this->lng("Installation terminated by user.\n"));
				die();
			}

			//* Select the default
			if($input == '') {
				$answer = $default;
				$finished = true;
			}

			//* Set answer id valid
			if(in_array($input, $answers)) {
				$answer = $input;
				$finished = true;
			}

		} while ($finished == false);
		swriteln();
		return $answer;
	}

	public function free_query($query, $default) {
		swrite($this->lng($query).' ['.$default.']: ');
		$input = sread();

		//* Stop the installation
		if($input == 'quit') {
			swriteln($this->lng("Installation terminated by user.\n"));
			die();
		}

		$answer =  ($input == '') ? $default : $input;
		swriteln();
		return $answer;
	}

	/*
	// TODO: this function is not used atmo I think - pedro
	function request_language(){

		swriteln(lng('Enter your language'));
		swriteln(lng('de, en'));

	}
	*/

	//** Detect installed applications
	public function find_installed_apps() {
		global $conf;

		if(is_installed('mysql') || is_installed('mysqld')) $conf['mysql']['installed'] = true;
		if(is_installed('postfix')) $conf['postfix']['installed'] = true;
		if(is_installed('mailman')) $conf['mailman']['installed'] = true;
		if(is_installed('apache') || is_installed('apache2') || is_installed('httpd') || is_installed('httpd2')) $conf['apache']['installed'] = true;
		if(is_installed('getmail')) $conf['getmail']['installed'] = true;
		if(is_installed('courierlogger')) $conf['courier']['installed'] = true;
		if(is_installed('dovecot')) $conf['dovecot']['installed'] = true;
		if(is_installed('saslauthd')) $conf['saslauthd']['installed'] = true;
		if(is_installed('amavisd-new') || is_installed('amavisd')) $conf['amavis']['installed'] = true;
		if(is_installed('clamdscan')) $conf['clamav']['installed'] = true;
		if(is_installed('pure-ftpd') || is_installed('pure-ftpd-wrapper')) $conf['pureftpd']['installed'] = true;
		if(is_installed('mydns') || is_installed('mydns-ng')) $conf['mydns']['installed'] = true;
		if(is_installed('jk_chrootsh')) $conf['jailkit']['installed'] = true;
		if(is_installed('pdns_server') || is_installed('pdns_control')) $conf['powerdns']['installed'] = true;
		if(is_installed('named') || is_installed('bind') || is_installed('bind9')) $conf['bind']['installed'] = true;
		if(is_installed('squid')) $conf['squid']['installed'] = true;
		if(is_installed('nginx')) $conf['nginx']['installed'] = true;
		// if(is_installed('iptables') && is_installed('ufw')) $conf['ufw']['installed'] = true;
		if(is_installed('fail2ban-server')) $conf['fail2ban']['installed'] = true;
		if(is_installed('vzctl')) $conf['openvz']['installed'] = true;
		if(is_dir("/etc/Bastille")) $conf['bastille']['installed'] = true;

		if ($conf['services']['web'] && (($conf['apache']['installed'] && is_file($conf['apache']["vhost_conf_enabled_dir"]."/000-ispconfig.vhost")) || ($conf['nginx']['installed'] && is_file($conf['nginx']["vhost_conf_enabled_dir"]."/000-ispconfig.vhost")))) $this->ispconfig_interface_installed = true;
	}

	/** Create the database for ISPConfig */


	public function configure_database() {
		global $conf;

		//** Create the database
		if(!$this->db->query('CREATE DATABASE IF NOT EXISTS '.$conf['mysql']['database'].' DEFAULT CHARACTER SET '.$conf['mysql']['charset'])) {
			$this->error('Unable to create MySQL database: '.$conf['mysql']['database'].'.');
		}

		//* Set the database name in the DB library
		$this->db->dbName = $conf['mysql']['database'];

		//* Load the database dump into the database, if database contains no tables
		$db_tables = $this->db->getTables();
		if(count($db_tables) > 0) {
			$this->error('Stopped: Database already contains some tables.');
		} else {
			if($conf['mysql']['admin_password'] == '') {
				caselog("mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." ".escapeshellarg($conf['mysql']['database'])." < '".ISPC_INSTALL_ROOT."/install/sql/ispconfig3.sql' &> /dev/null",
					__FILE__, __LINE__, 'read in ispconfig3.sql', 'could not read in ispconfig3.sql');
			} else {
				caselog("mysql --default-character-set=".escapeshellarg($conf['mysql']['charset'])." -h ".escapeshellarg($conf['mysql']['host'])." -u ".escapeshellarg($conf['mysql']['admin_user'])." -p".escapeshellarg($conf['mysql']['admin_password'])." ".escapeshellarg($conf['mysql']['database'])." < '".ISPC_INSTALL_ROOT."/install/sql/ispconfig3.sql' &> /dev/null",
					__FILE__, __LINE__, 'read in ispconfig3.sql', 'could not read in ispconfig3.sql');
			}
			$db_tables = $this->db->getTables();
			if(count($db_tables) == 0) {
				$this->error('Unable to load SQL-Dump into database table.');
			}

			//* Load system.ini into the sys_ini table
			$system_ini = $this->db->quote(rf('tpl/system.ini.master'));
			$this->db->query("UPDATE sys_ini SET config = '$system_ini' WHERE sysini_id = 1");

		}
	}

	//** Create the server record in the database
	public function add_database_server_record() {

		global $conf;

		if($conf['mysql']['host'] == 'localhost') {
			$from_host = 'localhost';
		} else {
			$from_host = $conf['hostname'];
		}

		// Delete ISPConfig user in the local database, in case that it exists
		$this->db->query("DELETE FROM mysql.user WHERE User = '".$conf['mysql']['ispconfig_user']."' AND Host = '".$from_host."';");
		$this->db->query("DELETE FROM mysql.db WHERE Db = '".$conf['mysql']['database']."' AND Host = '".$from_host."';");
		$this->db->query('FLUSH PRIVILEGES;');

		//* Create the ISPConfig database user in the local database
		$query = 'GRANT SELECT, INSERT, UPDATE, DELETE ON '.$conf['mysql']['database'].".* "
			."TO '".$conf['mysql']['ispconfig_user']."'@'".$from_host."' "
			."IDENTIFIED BY '".$conf['mysql']['ispconfig_password']."';";
		if(!$this->db->query($query)) {
			$this->error('Unable to create database user: '.$conf['mysql']['ispconfig_user'].' Error: '.$this->db->errorMessage);
		}

		//* Reload database privelages
		$this->db->query('FLUSH PRIVILEGES;');

		//* Set the database name in the DB library
		$this->db->dbName = $conf['mysql']['database'];

		$tpl_ini_array = ini_to_array(rf('tpl/server.ini.master'));

		//* Update further distribution specific parameters for server config here
		//* HINT: Every line added here has to be added in update.lib.php too!!
		$tpl_ini_array['web']['vhost_conf_dir'] = $conf['apache']['vhost_conf_dir'];
		$tpl_ini_array['web']['vhost_conf_enabled_dir'] = $conf['apache']['vhost_conf_enabled_dir'];
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

		if (array_key_exists('awstats', $conf)) {
			foreach ($conf['awstats'] as $aw_sett => $aw_value) {
				$tpl_ini_array['web']['awstats_'.$aw_sett] = $aw_value;
			}
		}

		$server_ini_content = array_to_ini($tpl_ini_array);
		$server_ini_content = mysql_real_escape_string($server_ini_content);

		$mail_server_enabled = ($conf['services']['mail'])?1:0;
		$web_server_enabled = ($conf['services']['web'])?1:0;
		$dns_server_enabled = ($conf['services']['dns'])?1:0;
		$file_server_enabled = ($conf['services']['file'])?1:0;
		$db_server_enabled = ($conf['services']['db'])?1:0;
		$vserver_server_enabled = ($conf['openvz']['installed'])?1:0;
		$proxy_server_enabled = (isset($conf['services']['proxy']) && $conf['services']['proxy'])?1:0;
		$firewall_server_enabled = (isset($conf['services']['firewall']) && $conf['services']['firewall'])?1:0;

		//** Get the database version number based on the patchfiles
		$found = true;
		$current_db_version = 1;
		while($found == true) {
			$next_db_version = intval($current_db_version + 1);
			$patch_filename = realpath(dirname(__FILE__).'/../').'/sql/incremental/upd_'.str_pad($next_db_version, 4, '0', STR_PAD_LEFT).'.sql';
			if(is_file($patch_filename)) {
				$current_db_version = $next_db_version;
			} else {
				$found = false;
			}
		}
		$current_db_version = intval($current_db_version);


		if($conf['mysql']['master_slave_setup'] == 'y') {

			//* Insert the server record in master DB
			$sql = "INSERT INTO `server` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_name`, `mail_server`, `web_server`, `dns_server`, `file_server`, `db_server`, `vserver_server`, `config`, `updated`, `active`, `dbversion`,`firewall_server`,`proxy_server`) VALUES (1, 1, 'riud', 'riud', 'r', '".$conf['hostname']."', '$mail_server_enabled', '$web_server_enabled', '$dns_server_enabled', '$file_server_enabled', '$db_server_enabled', '$vserver_server_enabled', '$server_ini_content', 0, 1, $current_db_version, $proxy_server_enabled, $firewall_server_enabled);";
			$this->dbmaster->query($sql);
			$conf['server_id'] = $this->dbmaster->insertID();
			$conf['server_id'] = $conf['server_id'];

			//* Insert the same record in the local DB
			$sql = "INSERT INTO `server` (`server_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_name`, `mail_server`, `web_server`, `dns_server`, `file_server`, `db_server`, `vserver_server`, `config`, `updated`, `active`, `dbversion`,`firewall_server`,`proxy_server`) VALUES ('".$conf['server_id']."',1, 1, 'riud', 'riud', 'r', '".$conf['hostname']."', '$mail_server_enabled', '$web_server_enabled', '$dns_server_enabled', '$file_server_enabled', '$db_server_enabled', '$vserver_server_enabled', '$server_ini_content', 0, 1, $current_db_version, $proxy_server_enabled, $firewall_server_enabled);";
			$this->db->query($sql);

			//* username for the ispconfig user
			$conf['mysql']['master_ispconfig_user'] = 'ispcsrv'.$conf['server_id'];

			$this->grant_master_database_rights();

		} else {
			//* Insert the server, if its not a mster / slave setup
			$sql = "INSERT INTO `server` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_name`, `mail_server`, `web_server`, `dns_server`, `file_server`, `db_server`, `vserver_server`, `config`, `updated`, `active`, `dbversion`,`firewall_server`,`proxy_server`) VALUES (1, 1, 'riud', 'riud', 'r', '".$conf['hostname']."', '$mail_server_enabled', '$web_server_enabled', '$dns_server_enabled', '$file_server_enabled', '$db_server_enabled', '$vserver_server_enabled', '$server_ini_content', 0, 1, $current_db_version, $proxy_server_enabled, $firewall_server_enabled);";
			$this->db->query($sql);
			$conf['server_id'] = $this->db->insertID();
			$conf['server_id'] = $conf['server_id'];
		}


	}

	public function grant_master_database_rights($verbose = false) {
		global $conf;

		/*
		 * The following code is a little bit tricky:
		 * * If we HAVE a master-slave - Setup then the client has to grant the rights for himself
		 *   at the master.
		 * * If we DO NOT have a master-slave - Setup then we have two possibilities
		 *   1) it is a single server
		 *   2) it is the MASTER of n clients
		*/
		$hosts = array();

		if($conf['mysql']['master_slave_setup'] == 'y') {
			/*
			 * it is a master-slave - Setup so the slave has to grant its rights in the master
			 * database
			 */

			//* insert the ispconfig user in the remote server
			$from_host = $conf['hostname'];
			$from_ip = gethostbyname($conf['hostname']);

			$hosts[$from_host]['user'] = $conf['mysql']['master_ispconfig_user'];
			$hosts[$from_host]['db'] = $conf['mysql']['master_database'];
			$hosts[$from_host]['pwd'] = $conf['mysql']['master_ispconfig_password'];

			$hosts[$from_ip]['user'] = $conf['mysql']['master_ispconfig_user'];
			$hosts[$from_ip]['db'] = $conf['mysql']['master_database'];
			$hosts[$from_ip]['pwd'] = $conf['mysql']['master_ispconfig_password'];
		} else{
			/*
			 * it is NOT a master-slave - Setup so we have to find out all clients and their
			 * host
			 */
			$query = "SELECT Host, User FROM mysql.user WHERE User like 'ispcsrv%' ORDER BY User, Host";
			$data = $this->dbmaster->queryAllRecords($query);
			if($data === false) {
				$this->error('Unable to get the user rights: '.$value['db'].' Error: '.$this->dbmaster->errorMessage);
			}
			foreach ($data as $item){
				$hosts[$item['Host']]['user'] = $item['User'];
				$hosts[$item['Host']]['db'] = $conf['mysql']['master_database'];
				$hosts[$item['Host']]['pwd'] = ''; // the user already exists, so we need no pwd!
			}
		}

		if(count($hosts) > 0) {
			foreach($hosts as $host => $value) {
				/*
			 * If a pwd exists, this means, we have to add the new user (and his pwd).
			 * if not, the user already exists and we do not need the pwd
			 */
				if ($value['pwd'] != ''){
					$query = "CREATE USER '".$value['user']."'@'".$host."' IDENTIFIED BY '" . $value['pwd'] . "'";
					if ($verbose){
						echo "\n\n" . $query ."\n";
					}
					$this->dbmaster->query($query); // ignore the error
				}

				/*
			 *  Try to delete all rights of the user in case that it exists.
			 *  In Case that it will not exist, do nothing (ignore the error!)
			 */
				$query = "REVOKE ALL PRIVILEGES, GRANT OPTION FROM '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo "\n\n" . $query ."\n";
				}
				$this->dbmaster->query($query); // ignore the error

				//* Create the ISPConfig database user in the remote database
				$query = "GRANT SELECT ON ".$value['db'].".`server` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, INSERT ON ".$value['db'].".`sys_log` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, UPDATE(`status`, `error`) ON ".$value['db'].".`sys_datalog` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, UPDATE(`status`) ON ".$value['db'].".`software_update_inst` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, UPDATE(`updated`) ON ".$value['db'].".`server` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, UPDATE (`ssl_request`, `ssl_cert`, `ssl_action`, `ssl_key`) ON ".$value['db'].".`web_domain` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT ON ".$value['db'].".`sys_group` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, UPDATE (`action_state`, `response`) ON ".$value['db'].".`sys_remoteaction` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, INSERT , DELETE ON ".$value['db'].".`monitor_data` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, INSERT, UPDATE ON ".$value['db'].".`mail_traffic` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, INSERT, UPDATE ON ".$value['db'].".`web_traffic` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, UPDATE, DELETE ON ".$value['db'].".`aps_instances` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}
				
				$query = "GRANT SELECT, DELETE ON ".$value['db'].".`aps_instances_settings` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

				$query = "GRANT SELECT, INSERT, DELETE ON ".$value['db'].".`web_backup` TO '".$value['user']."'@'".$host."' ";
				if ($verbose){
					echo $query ."\n";
				}
				if(!$this->dbmaster->query($query)) {
					$this->warning('Unable to set rights of user in master database: '.$value['db']."\n Query: ".$query."\n Error: ".$this->dbmaster->errorMessage);
				}

			}

			/*
		 * It is all done. Relod the rights...
		 */
			$this->dbmaster->query('FLUSH PRIVILEGES;');
		}

	}

	//** writes postfix configuration files
	public function process_postfix_config($configfile) {
		global $conf;

		$config_dir = $conf['postfix']['config_dir'].'/';
		$full_file_name = $config_dir.$configfile;
		//* Backup exiting file
		if(is_file($full_file_name)) {
			copy($full_file_name, $config_dir.$configfile.'~');
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		$content = str_replace('{server_id}', $conf['server_id'], $content);
		wf($full_file_name, $content);
	}

	public function configure_jailkit() {
		global $conf;

		$cf = $conf['jailkit'];
		$config_dir = $cf['config_dir'];
		$jk_init = $cf['jk_init'];
		$jk_chrootsh = $cf['jk_chrootsh'];

		if (is_dir($config_dir)) {
			if(is_file($config_dir.'/'.$jk_init)) copy($config_dir.'/'.$jk_init, $config_dir.'/'.$jk_init.'~');
			if(is_file($config_dir.'/'.$jk_chrootsh.'.master')) copy($config_dir.'/'.$jk_chrootsh.'.master', $config_dir.'/'.$jk_chrootsh.'~');

			if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$jk_init.'.master')) {
				copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$jk_init.'.master', $config_dir.'/'.$jk_init);
			} else {
				copy('tpl/'.$jk_init.'.master', $config_dir.'/'.$jk_init);
			}
			if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$jk_chrootsh.'.master')) {
				copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$jk_chrootsh.'.master', $config_dir.'/'.$jk_chrootsh);
			} else {
				copy('tpl/'.$jk_chrootsh.'.master', $config_dir.'/'.$jk_chrootsh);
			}
		}

		//* help jailkit fo find its ini files
		if(!is_link('/usr/jk_socketd.ini')) exec('ln -s /etc/jailkit/jk_socketd.ini /usr/jk_socketd.ini');
		if(!is_link('/usr/jk_init.ini')) exec('ln -s /etc/jailkit/jk_init.ini /usr/jk_init.ini');

	}

	public function configure_mailman($status = 'insert') {
		global $conf;

		$config_dir = $conf['mailman']['config_dir'].'/';
		$full_file_name = $config_dir.'mm_cfg.py';
		//* Backup exiting file
		if(is_file($full_file_name)) {
			copy($full_file_name, $config_dir.'mm_cfg.py~');
		}

		// load files
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/mm_cfg.py.master', 'tpl/mm_cfg.py.master');
		$old_file = rf($full_file_name);

		$old_options = array();
		$lines = explode("\n", $old_file);
		foreach ($lines as $line)
		{
			if (trim($line) != '' && substr($line, 0, 1) != '#')
			{
				@list($key, $value) = @explode("=", $line);
				if (!empty($value))
				{
					$key = rtrim($key);
					$old_options[$key] = trim($value);
				}
			}
		}

		$virtual_domains = '';
		if($status == 'update')
		{
			// create virtual_domains list
			$domainAll = $this->db->queryAllRecords("SELECT domain FROM mail_mailinglist GROUP BY domain");

			if(is_array($domainAll)) {
				foreach($domainAll as $domain)
				{
					if ($domainAll[0]['domain'] == $domain['domain'])
						$virtual_domains .= "'".$domain['domain']."'";
					else
						$virtual_domains .= ", '".$domain['domain']."'";
				}
			}
		}
		else
			$virtual_domains = "' '";

		$content = str_replace('{hostname}', $conf['hostname'], $content);
		if(!isset($old_options['DEFAULT_SERVER_LANGUAGE'])) $old_options['DEFAULT_SERVER_LANGUAGE'] = '';
		$content = str_replace('{default_language}', $old_options['DEFAULT_SERVER_LANGUAGE'], $content);
		$content = str_replace('{virtual_domains}', $virtual_domains, $content);

		wf($full_file_name, $content);

		//* Write virtual_to_transport.sh script
		$config_dir = $conf['mailman']['config_dir'].'/';
		$full_file_name = $config_dir.'virtual_to_transport.sh';

		//* Backup exiting virtual_to_transport.sh script
		if(is_file($full_file_name)) {
			copy($full_file_name, $config_dir.'virtual_to_transport.sh~');
		}

		if(is_dir('/etc/mailman')) {
			if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/mailman-virtual_to_transport.sh')) {
				copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/mailman-virtual_to_transport.sh', $full_file_name);
			} else {
				copy('tpl/mailman-virtual_to_transport.sh', $full_file_name);
			}
			chgrp($full_file_name, 'list');
			chmod($full_file_name, 0750);
		}

		//* Create aliasaes
		exec('/usr/lib/mailman/bin/genaliases 2>/dev/null');

	}

	public function configure_postfix($options = '') {
		global $conf;
		$cf = $conf['postfix'];
		$config_dir = $cf['config_dir'];

		if(!is_dir($config_dir)) {
			$this->error("The postfix configuration directory '$config_dir' does not exist.");
		}

		//* mysql-virtual_domains.cf
		$this->process_postfix_config('mysql-virtual_domains.cf');

		//* mysql-virtual_forwardings.cf
		$this->process_postfix_config('mysql-virtual_forwardings.cf');

		//* mysql-virtual_mailboxes.cf
		$this->process_postfix_config('mysql-virtual_mailboxes.cf');

		//* mysql-virtual_email2email.cf
		$this->process_postfix_config('mysql-virtual_email2email.cf');

		//* mysql-virtual_transports.cf
		$this->process_postfix_config('mysql-virtual_transports.cf');

		//* mysql-virtual_recipient.cf
		$this->process_postfix_config('mysql-virtual_recipient.cf');

		//* mysql-virtual_sender.cf
		$this->process_postfix_config('mysql-virtual_sender.cf');

		//* mysql-virtual_client.cf
		$this->process_postfix_config('mysql-virtual_client.cf');

		//* mysql-virtual_relaydomains.cf
		$this->process_postfix_config('mysql-virtual_relaydomains.cf');

		//* mysql-virtual_relayrecipientmaps.cf
		$this->process_postfix_config('mysql-virtual_relayrecipientmaps.cf');

		//* Changing mode and group of the new created config files.
		caselog('chmod o= '.$config_dir.'/mysql-virtual_*.cf* &> /dev/null',
			__FILE__, __LINE__, 'chmod on mysql-virtual_*.cf*', 'chmod on mysql-virtual_*.cf* failed');
		caselog('chgrp '.$cf['group'].' '.$config_dir.'/mysql-virtual_*.cf* &> /dev/null',
			__FILE__, __LINE__, 'chgrp on mysql-virtual_*.cf*', 'chgrp on mysql-virtual_*.cf* failed');

		//* Creating virtual mail user and group
		$command = 'groupadd -g '.$cf['vmail_groupid'].' '.$cf['vmail_groupname'];
		if(!is_group($cf['vmail_groupname'])) caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = 'useradd -g '.$cf['vmail_groupname'].' -u '.$cf['vmail_userid'].' '.$cf['vmail_username'].' -d '.$cf['vmail_mailbox_base'].' -m';
		if(!is_user($cf['vmail_username'])) caselog("$command &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* These postconf commands will be executed on installation and update
		$server_ini_rec = $this->db->queryOneRecord("SELECT config FROM server WHERE server_id = ".$conf['server_id']);
		$server_ini_array = ini_to_array(stripslashes($server_ini_rec['config']));
		unset($server_ini_rec);

		//* If there are RBL's defined, format the list and add them to smtp_recipient_restrictions to prevent removeal after an update
		$rbl_list = '';
		if (@isset($server_ini_array['mail']['realtime_blackhole_list']) && $server_ini_array['mail']['realtime_blackhole_list'] != '') {
			$rbl_hosts = explode(",", str_replace(" ", "", $server_ini_array['mail']['realtime_blackhole_list']));
			foreach ($rbl_hosts as $key => $value) {
				$rbl_list .= ", reject_rbl_client ". $value;
			}
		}
		unset($rbl_hosts);
		unset($server_ini_array);

		$postconf_placeholders = array('{config_dir}' => $config_dir,
			'{vmail_mailbox_base}' => $cf['vmail_mailbox_base'],
			'{vmail_userid}' => $cf['vmail_userid'],
			'{vmail_groupid}' => $cf['vmail_groupid'],
			'{rbl_list}' => $rbl_list);

		$postconf_tpl = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian_postfix.conf.master', 'tpl/debian_postfix.conf.master');
		$postconf_tpl = strtr($postconf_tpl, $postconf_placeholders);
		$postconf_commands = array_filter(explode("\n", $postconf_tpl)); // read and remove empty lines

		//* These postconf commands will be executed on installation only
		if($this->is_update == false) {
			$postconf_commands = array_merge($postconf_commands, array(
					'myhostname = '.$conf['hostname'],
					'mydestination = '.$conf['hostname'].', localhost, localhost.localdomain',
					'mynetworks = 127.0.0.0/8 [::1]/128'
				));
		}

		//* Create the header and body check files
		touch($config_dir.'/header_checks');
		touch($config_dir.'/mime_header_checks');
		touch($config_dir.'/nested_header_checks');
		touch($config_dir.'/body_checks');

		//* Create the mailman files
		if(!is_dir('/var/lib/mailman/data')) exec('mkdir -p /var/lib/mailman/data');
		if(!is_file('/var/lib/mailman/data/aliases')) touch('/var/lib/mailman/data/aliases');
		exec('postalias /var/lib/mailman/data/aliases');
		if(!is_file('/var/lib/mailman/data/virtual-mailman')) touch('/var/lib/mailman/data/virtual-mailman');
		exec('postmap /var/lib/mailman/data/virtual-mailman');
		if(!is_file('/var/lib/mailman/data/transport-mailman')) touch('/var/lib/mailman/data/transport-mailman');
		exec('/usr/sbin/postmap /var/lib/mailman/data/transport-mailman');

		//* Make a backup copy of the main.cf file
		copy($config_dir.'/main.cf', $config_dir.'/main.cf~');

		//* Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__, 'EXECUTED: '.$command, 'Failed to execute the command '.$command);
		}

		if(!stristr($options, 'dont-create-certs')) {
			//* Create the SSL certificate
			$command = 'cd '.$config_dir.'; '
				.'openssl req -new -outform PEM -out smtpd.cert -newkey rsa:4096 -nodes -keyout smtpd.key -keyform PEM -days 3650 -x509';
			exec($command);

			$command = 'chmod o= '.$config_dir.'/smtpd.key';
			caselog($command.' &> /dev/null', __FILE__, __LINE__, 'EXECUTED: '.$command, 'Failed to execute the command '.$command);
		}

		//** We have to change the permissions of the courier authdaemon directory to make it accessible for maildrop.
		$command = 'chmod 755  /var/run/courier/authdaemon/';
		if(is_file('/var/run/courier/authdaemon/')) caselog($command.' &> /dev/null', __FILE__, __LINE__, 'EXECUTED: '.$command, 'Failed to execute the command '.$command);

		//* Changing maildrop lines in posfix master.cf
		if(is_file($config_dir.'/master.cf')) {
			copy($config_dir.'/master.cf', $config_dir.'/master.cf~');
		}
		if(is_file($config_dir.'/master.cf~')) {
			chmod($config_dir.'/master.cf~', 0400);
		}
		$configfile = $config_dir.'/master.cf';
		$content = rf($configfile);
		$content = str_replace('flags=DRhu user=vmail argv=/usr/bin/maildrop -d ${recipient}',
			'flags=DRhu user='.$cf['vmail_username'].' argv=/usr/bin/maildrop -d '.$cf['vmail_username'].' ${extension} ${recipient} ${user} ${nexthop} ${sender}',
			$content);
		wf($configfile, $content);

		//* Writing the Maildrop mailfilter file
		$configfile = 'mailfilter';
		if(is_file($cf['vmail_mailbox_base'].'/.'.$configfile)) {
			copy($cf['vmail_mailbox_base'].'/.'.$configfile, $cf['vmail_mailbox_base'].'/.'.$configfile.'~');
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{dist_postfix_vmail_mailbox_base}', $cf['vmail_mailbox_base'], $content);
		wf($cf['vmail_mailbox_base'].'/.'.$configfile, $content);

		//* Create the directory for the custom mailfilters
		if(!is_dir($cf['vmail_mailbox_base'].'/mailfilters')) {
			$command = 'mkdir '.$cf['vmail_mailbox_base'].'/mailfilters';
			caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		//* Chmod and chown the .mailfilter file
		$command = 'chown '.$cf['vmail_username'].':'.$cf['vmail_groupname'].' '.$cf['vmail_mailbox_base'].'/.mailfilter';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = 'chmod 600 '.$cf['vmail_mailbox_base'].'/.mailfilter';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

	}

	public function configure_saslauthd() {
		global $conf;

		//* Get saslsauthd version
		exec('saslauthd -v 2>&1', $out);
		$parts = explode(' ', $out[0]);
		$saslversion = $parts[1];
		unset($parts);
		unset($out);

		if(version_compare($saslversion , '2.1.23') > 0) {
			//* Configfile for saslauthd versions 2.1.24 and newer
			$configfile = 'sasl_smtpd2.conf';
		} else {
			//* Configfile for saslauthd versions up to 2.1.23
			$configfile = 'sasl_smtpd.conf';
		}

		if(is_file($conf['postfix']['config_dir'].'/sasl/smtpd.conf')) copy($conf['postfix']['config_dir'].'/sasl/smtpd.conf', $conf['postfix']['config_dir'].'/sasl/smtpd.conf~');
		if(is_file($conf['postfix']['config_dir'].'/sasl/smtpd.conf~')) chmod($conf['postfix']['config_dir'].'/sasl/smtpd.conf~', 0400);
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		wf($conf['postfix']['config_dir'].'/sasl/smtpd.conf', $content);

		// TODO: Chmod and chown on the config file


		// Recursively create the spool directory
		if(!@is_dir('/var/spool/postfix/var/run/saslauthd')) mkdir('/var/spool/postfix/var/run/saslauthd', 0755, true);

		// Edit the file /etc/default/saslauthd
		$configfile = $conf['saslauthd']['config'];
		if(is_file($configfile)) copy($configfile, $configfile.'~');
		if(is_file($configfile.'~')) chmod($configfile.'~', 0400);
		$content = rf($configfile);
		$content = str_replace('START=no', 'START=yes', $content);
		// Debian
		$content = str_replace('OPTIONS="-c"', 'OPTIONS="-m /var/spool/postfix/var/run/saslauthd -r"', $content);
		// Ubuntu
		$content = str_replace('OPTIONS="-c -m /var/run/saslauthd"', 'OPTIONS="-c -m /var/spool/postfix/var/run/saslauthd -r"', $content);
		wf($configfile, $content);

		// Edit the file /etc/init.d/saslauthd
		$configfile = $conf['init_scripts'].'/'.$conf['saslauthd']['init_script'];
		$content = rf($configfile);
		$content = str_replace('PIDFILE=$RUN_DIR/saslauthd.pid', 'PIDFILE="/var/spool/postfix/var/run/${NAME}/saslauthd.pid"', $content);
		wf($configfile, $content);

		// add the postfix user to the sasl group (at least necessary for Ubuntu 8.04 and most likely Debian Lenny as well.
		exec('adduser postfix sasl');


	}

	public function configure_pam() {
		global $conf;
		$pam = $conf['pam'];
		//* configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'pamd_smtp';
		if(is_file($pam.'/smtp'))    copy($pam.'/smtp', $pam.'/smtp~');
		if(is_file($pam.'/smtp~'))   chmod($pam.'/smtp~', 0400);

		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		wf($pam.'/smtp', $content);
		// On some OSes smtp is world readable which allows for reading database information.  Removing world readable rights should have no effect.
		if(is_file($pam.'/smtp'))    exec("chmod o= $pam/smtp");
		chmod($pam.'/smtp', 0660);
		chown($pam.'/smtp', 'daemon');
		chgrp($pam.'/smtp', 'daemon');

	}

	public function configure_courier() {
		global $conf;
		$config_dir = $conf['courier']['config_dir'];
		//* authmysqlrc
		$configfile = 'authmysqlrc';
		if(is_file($config_dir.'/'.$configfile)) {
			copy($config_dir.'/'.$configfile, $config_dir.'/'.$configfile.'~');
		}
		chmod($config_dir.'/'.$configfile.'~', 0400);
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		wf($config_dir.'/'.$configfile, $content);

		chmod($config_dir.'/'.$configfile, 0660);
		chown($config_dir.'/'.$configfile, 'daemon');
		chgrp($config_dir.'/'.$configfile, 'daemon');

		//* authdaemonrc
		$configfile = $config_dir.'/authdaemonrc';
		if(is_file($configfile)) {
			copy($configfile, $configfile.'~');
		}
		if(is_file($configfile.'~')) {
			chmod($configfile.'~', 0400);
		}
		$content = rf($configfile);
		$content = str_replace('authmodulelist="authpam"', 'authmodulelist="authmysql"', $content);
		wf($configfile, $content);
	}

	public function configure_dovecot() {
		global $conf;

		$config_dir = $conf['dovecot']['config_dir'];

		//* Configure master.cf and add a line for deliver
		if(is_file($conf['postfix']['config_dir'].'/master.cf')) {
			copy($conf['postfix']['config_dir'].'/master.cf', $conf['postfix']['config_dir'].'/master.cf~2');
		}
		if(is_file($conf['postfix']['config_dir'].'/master.cf~')) {
			chmod($conf['postfix']['config_dir'].'/master.cf~2', 0400);
		}
		$content = rf($conf['postfix']['config_dir'].'/master.cf');
		// Only add the content if we had not addded it before
		if(!stristr($content, 'dovecot/deliver')) {
			$deliver_content = 'dovecot   unix  -       n       n       -       -       pipe'."\n".'  flags=DROhu user=vmail:vmail argv=/usr/lib/dovecot/deliver -f ${sender} -d ${user}@${nexthop}';
			af($conf['postfix']['config_dir'].'/master.cf', $deliver_content);
		}
		unset($content);
		unset($deliver_content);


		//* Reconfigure postfix to use dovecot authentication
		// Adding the amavisd commands to the postfix configuration
		$postconf_commands = array (
			'dovecot_destination_recipient_limit = 1',
			'virtual_transport = dovecot',
			'smtpd_sasl_type = dovecot',
			'smtpd_sasl_path = private/auth'
		);

		// Make a backup copy of the main.cf file
		copy($conf['postfix']['config_dir'].'/main.cf', $conf['postfix']['config_dir'].'/main.cf~3');

		// Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		//* backup dovecot.conf
		$configfile = 'dovecot.conf';
		if(is_file($config_dir.'/'.$configfile)) {
			copy($config_dir.'/'.$configfile, $config_dir.'/'.$configfile.'~');
		}

		//* Get the dovecot version
		exec('dovecot --version', $tmp);
		$parts = explode('.', trim($tmp[0]));
		$dovecot_version = $parts[0];
		unset($tmp);
		unset($parts);

		//* Copy dovecot configuration file
		if($dovecot_version == 2) {
			if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian_dovecot2.conf.master')) {
				copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian_dovecot2.conf.master', $config_dir.'/'.$configfile);
			} else {
				copy('tpl/debian_dovecot2.conf.master', $config_dir.'/'.$configfile);
			}
			replaceLine($config_dir.'/'.$configfile, 'postmaster_address = postmaster@example.com', 'postmaster_address = postmaster@'.$conf['hostname'], 1, 0);
		} else {
			if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian_dovecot.conf.master')) {
				copy($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian_dovecot.conf.master', $config_dir.'/'.$configfile);
			} else {
				copy('tpl/debian_dovecot.conf.master', $config_dir.'/'.$configfile);
			}
		}

		//* dovecot-sql.conf
		$configfile = 'dovecot-sql.conf';
		if(is_file($config_dir.'/'.$configfile)) {
			copy($config_dir.'/'.$configfile, $config_dir.'/'.$configfile.'~');
		}
		if(is_file($config_dir.'/'.$configfile.'~')) chmod($config_dir.'/'.$configfile.'~', 0400);
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/debian_dovecot-sql.conf.master', 'tpl/debian_dovecot-sql.conf.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		wf($config_dir.'/'.$configfile, $content);

		chmod($config_dir.'/'.$configfile, 0600);
		chown($config_dir.'/'.$configfile, 'root');
		chgrp($config_dir.'/'.$configfile, 'root');
		
		// Dovecot shall ignore mounts in website directory
		exec("doveadm mount add '/var/www/*' ignore");

	}

	public function configure_amavis() {
		global $conf;

		// amavisd user config file
		$configfile = 'amavisd_user_config';
		if(is_file($conf['amavis']['config_dir'].'/conf.d/50-user')) copy($conf['amavis']['config_dir'].'/conf.d/50-user', $conf['amavis']['config_dir'].'/50-user~');
		if(is_file($conf['amavis']['config_dir'].'/conf.d/50-user~')) chmod($conf['amavis']['config_dir'].'/conf.d/50-user~', 0400);
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_port}', $conf['mysql']['port'], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		wf($conf['amavis']['config_dir'].'/conf.d/50-user', $content);

		// TODO: chmod and chown on the config file


		// Adding the amavisd commands to the postfix configuration
		// Add array for no error in foreach and maybe future options
		$postconf_commands = array ();

		// Check for amavisd -> pure webserver with postfix for mailing without antispam
		if ($conf['amavis']['installed']) {
			$postconf_commands[] = 'content_filter = amavis:[127.0.0.1]:10024';
			$postconf_commands[] = 'receive_override_options = no_address_mappings';
		}

		// Make a backup copy of the main.cf file
		copy($conf['postfix']['config_dir'].'/main.cf', $conf['postfix']['config_dir'].'/main.cf~2');

		// Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		// Append the configuration for amavisd to the master.cf file
		if(is_file($conf['postfix']['config_dir'].'/master.cf')) copy($conf['postfix']['config_dir'].'/master.cf', $conf['postfix']['config_dir'].'/master.cf~');
		$content = rf($conf['postfix']['config_dir'].'/master.cf');
		// Only add the content if we had not addded it before
		if(!stristr($content, '127.0.0.1:10025')) {
			unset($content);
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/master_cf_amavis.master', 'tpl/master_cf_amavis.master');
			af($conf['postfix']['config_dir'].'/master.cf', $content);
		}
		unset($content);

		// Add the clamav user to the amavis group
		exec('adduser clamav amavis');


	}

	public function configure_spamassassin() {
		global $conf;

		//* Enable spamasasssin on debian and ubuntu
		$configfile = '/etc/default/spamassassin';
		if(is_file($configfile)) {
			copy($configfile, $configfile.'~');
		}
		$content = rf($configfile);
		$content = str_replace('ENABLED=0', 'ENABLED=1', $content);
		wf($configfile, $content);
	}

	public function configure_getmail() {
		global $conf;

		$config_dir = $conf['getmail']['config_dir'];

		if(!@is_dir($config_dir)) mkdir(escapeshellcmd($config_dir), 0700, true);

		$command = 'useradd -d '.$config_dir.' getmail';
		if(!is_user('getmail')) caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = "chown -R getmail $config_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = "chmod -R 700 $config_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
	}


	public function configure_pureftpd() {
		global $conf;

		$config_dir = $conf['pureftpd']['config_dir'];

		//* configure pure-ftpd for MySQL authentication against the ispconfig database
		$configfile = 'db/mysql.conf';
		if(is_file($config_dir.'/'.$configfile)) {
			copy($config_dir.'/'.$configfile, $config_dir.'/'.$configfile.'~');
		}
		if(is_file($config_dir.'/'.$configfile.'~')) {
			chmod($config_dir.'/'.$configfile.'~', 0400);
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/pureftpd_mysql.conf.master', 'tpl/pureftpd_mysql.conf.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		$content = str_replace('{server_id}', $conf['server_id'], $content);
		wf($config_dir.'/'.$configfile, $content);
		chmod($config_dir.'/'.$configfile, 0600);
		chown($config_dir.'/'.$configfile, 'root');
		chgrp($config_dir.'/'.$configfile, 'root');
		// **enable chrooting
		//exec('mkdir -p '.$config_dir.'/conf/ChrootEveryone');
		exec('echo "yes" > '.$config_dir.'/conf/ChrootEveryone');
		exec('echo "yes" > '.$config_dir.'/conf/BrokenClientsCompatibility');
		exec('echo "yes" > '.$config_dir.'/conf/DisplayDotFiles');

		if(is_file('/etc/default/pure-ftpd-common')) {
			replaceLine('/etc/default/pure-ftpd-common', 'STANDALONE_OR_INETD=inetd', 'STANDALONE_OR_INETD=standalone', 1, 0);
			replaceLine('/etc/default/pure-ftpd-common', 'VIRTUALCHROOT=false', 'VIRTUALCHROOT=true', 1, 0);
		}

		if(is_file('/etc/inetd.conf')) {
			replaceLine('/etc/inetd.conf', '/usr/sbin/pure-ftpd-wrapper', '#ftp     stream  tcp     nowait  root    /usr/sbin/tcpd /usr/sbin/pure-ftpd-wrapper', 0, 0);
			exec($this->getinitcommand('openbsd-inetd', 'restart'));
			//if(is_file($conf['init_scripts'].'/'.'openbsd-inetd')) exec($conf['init_scripts'].'/'.'openbsd-inetd restart');
		}

		if(!is_file('/etc/pure-ftpd/conf/DontResolve')) exec('echo "yes" > /etc/pure-ftpd/conf/DontResolve');
	}

	public function configure_mydns() {
		global $conf;

		// configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'mydns.conf';
		if(is_file($conf['mydns']['config_dir'].'/'.$configfile)) copy($conf['mydns']['config_dir'].'/'.$configfile, $conf['mydns']['config_dir'].'/'.$configfile.'~');
		if(is_file($conf['mydns']['config_dir'].'/'.$configfile.'~')) chmod($conf['mydns']['config_dir'].'/'.$configfile.'~', 0400);
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		$content = str_replace('{server_id}', $conf['server_id'], $content);
		wf($conf['mydns']['config_dir'].'/'.$configfile, $content);
		chmod($conf['mydns']['config_dir'].'/'.$configfile, 0600);
		chown($conf['mydns']['config_dir'].'/'.$configfile, 'root');
		chgrp($conf['mydns']['config_dir'].'/'.$configfile, 'root');

	}

	public function configure_powerdns() {
		global $conf;

		//* Create the database
		if(!$this->db->query('CREATE DATABASE IF NOT EXISTS '.$conf['powerdns']['database'].' DEFAULT CHARACTER SET '.$conf['mysql']['charset'])) {
			$this->error('Unable to create MySQL database: '.$conf['powerdns']['database'].'.');
		}

		//* Create the ISPConfig database user in the local database
		$query = "GRANT ALL ON `".$conf['powerdns']['database']."` . * TO '".$conf['mysql']['ispconfig_user']."'@'localhost';";
		if(!$this->db->query($query)) {
			$this->error('Unable to create user for powerdns database Error: '.$this->db->errorMessage);
		}

		//* Reload database privelages
		$this->db->query('FLUSH PRIVILEGES;');

		//* load the powerdns databse dump
		if($conf['mysql']['admin_password'] == '') {
			caselog("mysql --default-character-set=".$conf['mysql']['charset']." -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' '".$conf['powerdns']['database']."' < '".ISPC_INSTALL_ROOT."/install/sql/powerdns.sql' &> /dev/null",
				__FILE__, __LINE__, 'read in ispconfig3.sql', 'could not read in powerdns.sql');
		} else {
			caselog("mysql --default-character-set=".$conf['mysql']['charset']." -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' -p'".$conf['mysql']['admin_password']."' '".$conf['powerdns']['database']."' < '".ISPC_INSTALL_ROOT."/install/sql/powerdns.sql' &> /dev/null",
				__FILE__, __LINE__, 'read in ispconfig3.sql', 'could not read in powerdns.sql');
		}

		//* Create the powerdns config file
		$configfile = 'pdns.local';
		if(is_file($conf['powerdns']['config_dir'].'/'.$configfile)) copy($conf['powerdns']['config_dir'].'/'.$configfile, $conf['powerdns']['config_dir'].'/'.$configfile.'~');
		if(is_file($conf['powerdns']['config_dir'].'/'.$configfile.'~')) chmod($conf['powerdns']['config_dir'].'/'.$configfile.'~', 0400);
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{powerdns_database}', $conf['powerdns']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		wf($conf['powerdns']['config_dir'].'/'.$configfile, $content);
		chmod($conf['powerdns']['config_dir'].'/'.$configfile, 0600);
		chown($conf['powerdns']['config_dir'].'/'.$configfile, 'root');
		chgrp($conf['powerdns']['config_dir'].'/'.$configfile, 'root');


	}

	public function configure_bind() {
		global $conf;

		//* Check if the zonefile directory has a slash at the end
		$content=$conf['bind']['bind_zonefiles_dir'];
		if(substr($content, -1, 1) != '/') {
			$content .= '/';
		}

		//* Create the slave subdirectory
		$content .= 'slave';
		if(!@is_dir($content)) mkdir($content, 0770, true);

		//* Chown the slave subdirectory to $conf['bind']['bind_user']
		chown($content, $conf['bind']['bind_user']);
		chgrp($content, $conf['bind']['bind_group']);

	}



	public function configure_apache() {
		global $conf;

		if($conf['apache']['installed'] == false) return;
		//* Create the logging directory for the vhost logfiles
		if(!@is_dir($conf['ispconfig_log_dir'].'/httpd')) mkdir($conf['ispconfig_log_dir'].'/httpd', 0755, true);

		if(is_file('/etc/suphp/suphp.conf')) {
			replaceLine('/etc/suphp/suphp.conf', 'php=php:/usr/bin', 'x-httpd-suphp="php:/usr/bin/php-cgi"', 0);
			//replaceLine('/etc/suphp/suphp.conf','docroot=','docroot=/var/clients',0);
			replaceLine('/etc/suphp/suphp.conf', 'umask=0077', 'umask=0022', 0);
		}

		if(is_file('/etc/apache2/sites-enabled/000-default')) {
			replaceLine('/etc/apache2/sites-available/000-default', 'NameVirtualHost *', 'NameVirtualHost *:80', 1, 0);
			replaceLine('/etc/apache2/sites-available/000-default', '<VirtualHost *>', '<VirtualHost *:80>', 1, 0);
		}

		if(is_file('/etc/apache2/ports.conf')) {
			// add a line "Listen 443" to ports conf if line does not exist
			replaceLine('/etc/apache2/ports.conf', 'Listen 443', 'Listen 443', 1);
		}

		if(is_file('/etc/apache2/apache.conf')) {
			if(hasLine('/etc/apache2/apache.conf', 'Include sites-enabled/', 1) == false) {
				if(hasLine('/etc/apache2/apache.conf', 'IncludeOptional sites-enabled/*.conf', 1) == false) {
					replaceLine('/etc/apache2/apache.conf', 'Include sites-enabled/', 'Include sites-enabled/', 1, 1);
				} elseif(hasLine('/etc/apache2/apache.conf', 'IncludeOptional sites-enabled/*.vhost', 1) == false) {
					replaceLine('/etc/apache2/apache.conf', 'IncludeOptional sites-enabled/*.vhost', 'IncludeOptional sites-enabled/*.vhost', 1, 1);
				}
			}
		}

		//* Copy the ISPConfig configuration include
		$vhost_conf_dir = $conf['apache']['vhost_conf_dir'];
		$vhost_conf_enabled_dir = $conf['apache']['vhost_conf_enabled_dir'];

		// copy('tpl/apache_ispconfig.conf.master',$vhost_conf_dir.'/ispconfig.conf');

		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/apache_ispconfig.conf.master', 'tpl/apache_ispconfig.conf.master');
		$records = $this->db->queryAllRecords('SELECT * FROM '.$conf['mysql']['master_database'].'.server_ip WHERE server_id = '.$conf['server_id']." AND virtualhost = 'y'");

		if(is_array($records) && count($records) > 0) {
			foreach($records as $rec) {
				if($rec['ip_type'] == 'IPv6') {
					$ip_address = '['.$rec['ip_address'].']';
				} else {
					$ip_address = $rec['ip_address'];
				}
				$ports = explode(',', $rec['virtualhost_port']);
				if(is_array($ports)) {
					foreach($ports as $port) {
						$port = intval($port);
						if($port > 0 && $port < 65536 && $ip_address != '') {
							$content .= 'NameVirtualHost '.$ip_address.":".$port."\n";
						}
					}
				}
			}
		}

		$content .= "\n";
		wf($vhost_conf_dir.'/ispconfig.conf', $content);

		if(!@is_link($vhost_conf_enabled_dir.'/000-ispconfig.conf')) {
			symlink($vhost_conf_dir.'/ispconfig.conf', $vhost_conf_enabled_dir.'/000-ispconfig.conf');
		}

		//* make sure that webalizer finds its config file when it is directly in /etc
		if(@is_file('/etc/webalizer.conf') && !@is_dir('/etc/webalizer')) {
			mkdir('/etc/webalizer');
			symlink('/etc/webalizer.conf', '/etc/webalizer/webalizer.conf');
		}

		if(is_file('/etc/webalizer/webalizer.conf')) {
			// Change webalizer mode to incremental
			replaceLine('/etc/webalizer/webalizer.conf', '#IncrementalName', 'IncrementalName webalizer.current', 0, 0);
			replaceLine('/etc/webalizer/webalizer.conf', '#Incremental', 'Incremental     yes', 0, 0);
			replaceLine('/etc/webalizer/webalizer.conf', '#HistoryName', 'HistoryName     webalizer.hist', 0, 0);
		}

		// Check the awsatst script
		if(!is_dir('/usr/share/awstats/tools')) exec('mkdir -p /usr/share/awstats/tools');
		if(!file_exists('/usr/share/awstats/tools/awstats_buildstaticpages.pl') && file_exists('/usr/share/doc/awstats/examples/awstats_buildstaticpages.pl')) symlink('/usr/share/doc/awstats/examples/awstats_buildstaticpages.pl', '/usr/share/awstats/tools/awstats_buildstaticpages.pl');
		if(file_exists('/etc/awstats/awstats.conf.local')) replaceLine('/etc/awstats/awstats.conf.local', 'LogFormat=4', 'LogFormat=1', 0, 1);

		//* add a sshusers group
		$command = 'groupadd sshusers';
		if(!is_group('sshusers')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

	}

	public function configure_nginx(){
		global $conf;

		if($conf['nginx']['installed'] == false) return;
		//* Create the logging directory for the vhost logfiles
		if(!@is_dir($conf['ispconfig_log_dir'].'/httpd')) mkdir($conf['ispconfig_log_dir'].'/httpd', 0755, true);

		//* make sure that webalizer finds its config file when it is directly in /etc
		if(@is_file('/etc/webalizer.conf') && !@is_dir('/etc/webalizer')) {
			mkdir('/etc/webalizer');
			symlink('/etc/webalizer.conf', '/etc/webalizer/webalizer.conf');
		}

		if(is_file('/etc/webalizer/webalizer.conf')) {
			// Change webalizer mode to incremental
			replaceLine('/etc/webalizer/webalizer.conf', '#IncrementalName', 'IncrementalName webalizer.current', 0, 0);
			replaceLine('/etc/webalizer/webalizer.conf', '#Incremental', 'Incremental     yes', 0, 0);
			replaceLine('/etc/webalizer/webalizer.conf', '#HistoryName', 'HistoryName     webalizer.hist', 0, 0);
		}

		// Check the awsatst script
		if(!is_dir('/usr/share/awstats/tools')) exec('mkdir -p /usr/share/awstats/tools');
		if(!file_exists('/usr/share/awstats/tools/awstats_buildstaticpages.pl') && file_exists('/usr/share/doc/awstats/examples/awstats_buildstaticpages.pl')) symlink('/usr/share/doc/awstats/examples/awstats_buildstaticpages.pl', '/usr/share/awstats/tools/awstats_buildstaticpages.pl');
		if(file_exists('/etc/awstats/awstats.conf.local')) replaceLine('/etc/awstats/awstats.conf.local', 'LogFormat=4', 'LogFormat=1', 0, 1);

		//* add a sshusers group
		$command = 'groupadd sshusers';
		if(!is_group('sshusers')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		/*
		$row = $this->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ".$conf["server_id"]."");
		$ip_address = gethostbyname($row["server_name"]);
		$server_name = $row["server_name"];

        //setup proxy.conf
		$configfile = 'proxy.conf';
		if(is_file($conf["nginx"]["config_dir"].'/'.$configfile)) copy($conf["nginx"]["config_dir"].'/'.$configfile,$conf["nginx"]["config_dir"].'/'.$configfile.'~');
		if(is_file($conf["nginx"]["config_dir"].'/'.$configfile.'~')) exec('chmod 400 '.$conf["nginx"]["config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/nginx_".$configfile.".master");
		wf($conf["nginx"]["config_dir"].'/'.$configfile,$content);
		exec('chmod 600 '.$conf["nginx"]["config_dir"].'/'.$configfile);
		exec('chown root:root '.$conf["nginx"]["config_dir"].'/'.$configfile);

        //setup conf.d/cache.conf
        $configfile = 'cache.conf';
		if(is_file($conf["nginx"]["config_dir"].'/conf.d/'.$configfile)) copy($conf["nginx"]["config_dir"].'/conf.d/'.$configfile,$conf["nginx"]["config_dir"].'/conf.d/'.$configfile.'~');
		if(is_file($conf["nginx"]["config_dir"].'/conf.d/'.$configfile.'~')) exec('chmod 400 '.$conf["nginx"]["config_dir"].'/conf.d/'.$configfile.'~');
		$content = rf("tpl/nginx_".$configfile.".master");
		wf($conf["nginx"]["config_dir"].'/conf.d/'.$configfile,$content);
		exec('chmod 600 '.$conf["nginx"]["config_dir"].'/conf.d/'.$configfile);
		exec('chown root:root '.$conf["nginx"]["config_dir"].'/conf.d/'.$configfile);

        //setup cache directories
        mkdir('/var/cache/nginx/cache');
        exec('chown www-data:www-data /var/cache/nginx/cache');
        mkdir('/var/cache/nginx/temp');
        exec('chown www-data:www-data /var/cache/nginx/temp');
		*/
	}

	public function configure_fail2ban() {
		// To Do
	}

	public function configure_squid()
	{
		global $conf;
		$row = $this->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ".$conf["server_id"]."");
		$ip_address = gethostbyname($row["server_name"]);
		$server_name = $row["server_name"];

		$configfile = 'squid.conf';
		if(is_file($conf["squid"]["config_dir"].'/'.$configfile)) copy($conf["squid"]["config_dir"].'/'.$configfile, $conf["squid"]["config_dir"].'/'.$configfile.'~');
		if(is_file($conf["squid"]["config_dir"].'/'.$configfile.'~')) exec('chmod 400 '.$conf["squid"]["config_dir"].'/'.$configfile.'~');
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', "tpl/".$configfile.".master");
		$content = str_replace('{server_name}', $server_name, $content);
		$content = str_replace('{ip_address}', $ip_address, $content);
		$content = str_replace('{config_dir}', $conf['squid']['config_dir'], $content);
		wf($conf["squid"]["config_dir"].'/'.$configfile, $content);
		exec('chmod 600 '.$conf["squid"]["config_dir"].'/'.$configfile);
		exec('chown root:root '.$conf["squid"]["config_dir"].'/'.$configfile);
	}

	/*
	public function configure_ufw_firewall()
	{
		$configfile = 'ufw.conf';
		if(is_file('/etc/ufw/ufw.conf')) copy('/etc/ufw/ufw.conf','/etc/ufw/ufw.conf~');
		$content = rf("tpl/".$configfile.".master");
		wf('/etc/ufw/ufw.conf',$content);
		exec('chmod 600 /etc/ufw/ufw.conf');
		exec('chown root:root /etc/ufw/ufw.conf');
	}
	*/

	public function configure_firewall() {
		global $conf;

		$dist_init_scripts = $conf['init_scripts'];

		if(is_dir('/etc/Bastille.backup')) caselog('rm -rf /etc/Bastille.backup', __FILE__, __LINE__);
		if(is_dir('/etc/Bastille')) caselog('mv -f /etc/Bastille /etc/Bastille.backup', __FILE__, __LINE__);
		@mkdir('/etc/Bastille', 0700);
		if(is_dir('/etc/Bastille.backup/firewall.d')) caselog('cp -pfr /etc/Bastille.backup/firewall.d /etc/Bastille/', __FILE__, __LINE__);
		if(is_file($conf['ispconfig_install_dir'].'/server/conf-custom/install/bastille-firewall.cfg.master')) {
			caselog('cp -f ' . $conf['ispconfig_install_dir'].'/server/conf-custom/install/bastille-firewall.cfg.master /etc/Bastille/bastille-firewall.cfg', __FILE__, __LINE__);
		} else {
			caselog('cp -f tpl/bastille-firewall.cfg.master /etc/Bastille/bastille-firewall.cfg', __FILE__, __LINE__);
		}
		caselog('chmod 644 /etc/Bastille/bastille-firewall.cfg', __FILE__, __LINE__);
		$content = rf('/etc/Bastille/bastille-firewall.cfg');
		$content = str_replace('{DNS_SERVERS}', '', $content);

		$tcp_public_services = '';
		$udp_public_services = '';

		$row = $this->db->queryOneRecord('SELECT * FROM '.$conf["mysql"]["database"].'.firewall WHERE server_id = '.intval($conf['server_id']));

		if(trim($row['tcp_port']) != '' || trim($row['udp_port']) != '') {
			$tcp_public_services = trim(str_replace(',', ' ', $row['tcp_port']));
			$udp_public_services = trim(str_replace(',', ' ', $row['udp_port']));
		} else {
			$tcp_public_services = '21 22 25 53 80 110 143 443 3306 8080 10000';
			$udp_public_services = '53';
		}

		if(!stristr($tcp_public_services, $conf['apache']['vhost_port'])) {
			$tcp_public_services .= ' '.intval($conf['apache']['vhost_port']);
			if($row['tcp_port'] != '') $this->db->query("UPDATE firewall SET tcp_port = tcp_port + ',".intval($conf['apache']['vhost_port'])."' WHERE server_id = ".intval($conf['server_id']));
		}

		$content = str_replace('{TCP_PUBLIC_SERVICES}', $tcp_public_services, $content);
		$content = str_replace('{UDP_PUBLIC_SERVICES}', $udp_public_services, $content);

		wf('/etc/Bastille/bastille-firewall.cfg', $content);

		if(is_file($dist_init_scripts.'/bastille-firewall')) caselog('mv -f '.$dist_init_scripts.'/bastille-firewall '.$dist_init_scripts.'/bastille-firewall.backup', __FILE__, __LINE__);
		caselog('cp -f apps/bastille-firewall '.$dist_init_scripts, __FILE__, __LINE__);
		caselog('chmod 700 '.$dist_init_scripts.'/bastille-firewall', __FILE__, __LINE__);

		if(is_file('/sbin/bastille-ipchains')) caselog('mv -f /sbin/bastille-ipchains /sbin/bastille-ipchains.backup', __FILE__, __LINE__);
		caselog('cp -f apps/bastille-ipchains /sbin', __FILE__, __LINE__);
		caselog('chmod 700 /sbin/bastille-ipchains', __FILE__, __LINE__);

		if(is_file('/sbin/bastille-netfilter')) caselog('mv -f /sbin/bastille-netfilter /sbin/bastille-netfilter.backup', __FILE__, __LINE__);
		caselog('cp -f apps/bastille-netfilter /sbin', __FILE__, __LINE__);
		caselog('chmod 700 /sbin/bastille-netfilter', __FILE__, __LINE__);

		if(!@is_dir('/var/lock/subsys')) caselog('mkdir /var/lock/subsys', __FILE__, __LINE__);

		exec('which ipchains &> /dev/null', $ipchains_location, $ret_val);
		if(!is_file('/sbin/ipchains') && !is_link('/sbin/ipchains') && $ret_val == 0) phpcaselog(@symlink(shell_exec('which ipchains'), '/sbin/ipchains'), 'create symlink', __FILE__, __LINE__);
		unset($ipchains_location);
		exec('which iptables &> /dev/null', $iptables_location, $ret_val);
		if(!is_file('/sbin/iptables') && !is_link('/sbin/iptables') && $ret_val == 0) phpcaselog(@symlink(trim(shell_exec('which iptables')), '/sbin/iptables'), 'create symlink', __FILE__, __LINE__);
		unset($iptables_location);

	}

	public function configure_vlogger() {
		global $conf;

		//** Configure vlogger to use traffic logging to mysql (master) db
		$configfile = 'vlogger-dbi.conf';
		if(is_file($conf['vlogger']['config_dir'].'/'.$configfile)) copy($conf['vlogger']['config_dir'].'/'.$configfile, $conf['vlogger']['config_dir'].'/'.$configfile.'~');
		if(is_file($conf['vlogger']['config_dir'].'/'.$configfile.'~')) chmod($conf['vlogger']['config_dir'].'/'.$configfile.'~', 0400);
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		if($conf['mysql']['master_slave_setup'] == 'y') {
			$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['master_ispconfig_user'], $content);
			$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['master_ispconfig_password'], $content);
			$content = str_replace('{mysql_server_database}', $conf['mysql']['master_database'], $content);
			$content = str_replace('{mysql_server_ip}', $conf['mysql']['master_host'], $content);
		} else {
			$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
			$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
			$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
			$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		}
		wf($conf['vlogger']['config_dir'].'/'.$configfile, $content);
		chmod($conf['vlogger']['config_dir'].'/'.$configfile, 0600);
		chown($conf['vlogger']['config_dir'].'/'.$configfile, 'root');
		chgrp($conf['vlogger']['config_dir'].'/'.$configfile, 'root');

	}

	public function configure_apps_vhost() {
		global $conf;

		//* Create the ispconfig apps vhost user and group
		if($conf['apache']['installed'] == true){
			$apps_vhost_user = escapeshellcmd($conf['web']['apps_vhost_user']);
			$apps_vhost_group = escapeshellcmd($conf['web']['apps_vhost_group']);
			$install_dir = escapeshellcmd($conf['web']['website_basedir'].'/apps');

			$command = 'groupadd '.$apps_vhost_user;
			if(!is_group($apps_vhost_group)) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

			$command = 'useradd -g '.$apps_vhost_group.' -d '.$install_dir.' '.$apps_vhost_group;
			if(!is_user($apps_vhost_user)) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");


			//$command = 'adduser '.$conf['apache']['user'].' '.$apps_vhost_group;
			$command = 'usermod -a -G '.$apps_vhost_group.' '.$conf['apache']['user'];
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

			if(!@is_dir($install_dir)){
				mkdir($install_dir, 0755, true);
			} else {
				chmod($install_dir, 0755);
			}
			chown($install_dir, $apps_vhost_user);
			chgrp($install_dir, $apps_vhost_group);

			//* Copy the apps vhost file
			$vhost_conf_dir = $conf['apache']['vhost_conf_dir'];
			$vhost_conf_enabled_dir = $conf['apache']['vhost_conf_enabled_dir'];
			$apps_vhost_servername = ($conf['web']['apps_vhost_servername'] == '')?'':'ServerName '.$conf['web']['apps_vhost_servername'];
			
			//* Get the apps vhost port
			if($this->is_update == true) {
				$conf['web']['apps_vhost_port'] = get_apps_vhost_port_number();
			}

			// Dont just copy over the virtualhost template but add some custom settings
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/apache_apps.vhost.master', 'tpl/apache_apps.vhost.master');

			$content = str_replace('{apps_vhost_ip}', $conf['web']['apps_vhost_ip'], $content);
			$content = str_replace('{apps_vhost_port}', $conf['web']['apps_vhost_port'], $content);
			$content = str_replace('{apps_vhost_dir}', $conf['web']['website_basedir'].'/apps', $content);
			$content = str_replace('{website_basedir}', $conf['web']['website_basedir'], $content);
			$content = str_replace('{apps_vhost_servername}', $apps_vhost_servername, $content);


			// comment out the listen directive if port is 80 or 443
			if($conf['web']['apps_vhost_ip'] == 80 or $conf['web']['apps_vhost_ip'] == 443) {
				$content = str_replace('{vhost_port_listen}', '#', $content);
			} else {
				$content = str_replace('{vhost_port_listen}', '', $content);
			}

			wf($vhost_conf_dir.'/apps.vhost', $content);

			//copy('tpl/apache_ispconfig.vhost.master', "$vhost_conf_dir/ispconfig.vhost");
			//* and create the symlink
			if(@is_link($vhost_conf_enabled_dir.'/apps.vhost')) unlink($vhost_conf_enabled_dir.'/apps.vhost');
			if(!@is_link($vhost_conf_enabled_dir.'/000-apps.vhost')) {
				symlink($vhost_conf_dir.'/apps.vhost', $vhost_conf_enabled_dir.'/000-apps.vhost');
			}

			if(!is_file($conf['web']['website_basedir'].'/php-fcgi-scripts/apps/.php-fcgi-starter')) {
				$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/apache_apps_fcgi_starter.master', 'tpl/apache_apps_fcgi_starter.master');
				$content = str_replace('{fastcgi_bin}', $conf['fastcgi']['fastcgi_bin'], $content);
				$content = str_replace('{fastcgi_phpini_path}', $conf['fastcgi']['fastcgi_phpini_path'], $content);
				mkdir($conf['web']['website_basedir'].'/php-fcgi-scripts/apps', 0755, true);
				//copy('tpl/apache_apps_fcgi_starter.master',$conf['web']['website_basedir'].'/php-fcgi-scripts/apps/.php-fcgi-starter');
				wf($conf['web']['website_basedir'].'/php-fcgi-scripts/apps/.php-fcgi-starter', $content);
				exec('chmod +x '.$conf['web']['website_basedir'].'/php-fcgi-scripts/apps/.php-fcgi-starter');
				exec('chown -R ispapps:ispapps '.$conf['web']['website_basedir'].'/php-fcgi-scripts/apps');

			}
		}
		if($conf['nginx']['installed'] == true){
			$apps_vhost_user = escapeshellcmd($conf['web']['apps_vhost_user']);
			$apps_vhost_group = escapeshellcmd($conf['web']['apps_vhost_group']);
			$install_dir = escapeshellcmd($conf['web']['website_basedir'].'/apps');

			$command = 'groupadd '.$apps_vhost_user;
			if(!is_group($apps_vhost_group)) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

			$command = 'useradd -g '.$apps_vhost_group.' -d '.$install_dir.' '.$apps_vhost_group;
			if(!is_user($apps_vhost_user)) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");


			$command = 'adduser '.$conf['nginx']['user'].' '.$apps_vhost_group;
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

			if(!@is_dir($install_dir)){
				mkdir($install_dir, 0755, true);
			} else {
				chmod($install_dir, 0755);
			}
			chown($install_dir, $apps_vhost_user);
			chgrp($install_dir, $apps_vhost_group);

			//* Copy the apps vhost file
			$vhost_conf_dir = $conf['nginx']['vhost_conf_dir'];
			$vhost_conf_enabled_dir = $conf['nginx']['vhost_conf_enabled_dir'];
			$apps_vhost_servername = ($conf['web']['apps_vhost_servername'] == '')?'_':$conf['web']['apps_vhost_servername'];

			// Dont just copy over the virtualhost template but add some custom settings
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/nginx_apps.vhost.master', 'tpl/nginx_apps.vhost.master');

			if($conf['web']['apps_vhost_ip'] == '_default_'){
				$apps_vhost_ip = '';
			} else {
				$apps_vhost_ip = $conf['web']['apps_vhost_ip'].':';
			}

			$socket_dir = escapeshellcmd($conf['nginx']['php_fpm_socket_dir']);
			if(substr($socket_dir, -1) != '/') $socket_dir .= '/';
			if(!is_dir($socket_dir)) exec('mkdir -p '.$socket_dir);
			$fpm_socket = $socket_dir.'apps.sock';
			$cgi_socket = escapeshellcmd($conf['nginx']['cgi_socket']);

			$content = str_replace('{apps_vhost_ip}', $apps_vhost_ip, $content);
			$content = str_replace('{apps_vhost_port}', $conf['web']['apps_vhost_port'], $content);
			$content = str_replace('{apps_vhost_dir}', $conf['web']['website_basedir'].'/apps', $content);
			$content = str_replace('{apps_vhost_servername}', $apps_vhost_servername, $content);
			//$content = str_replace('{fpm_port}', ($conf['nginx']['php_fpm_start_port']+1), $content);
			$content = str_replace('{fpm_socket}', $fpm_socket, $content);
			$content = str_replace('{cgi_socket}', $cgi_socket, $content);

			if(file_exists('/var/run/php5-fpm.sock')){
				$use_tcp = '#';
				$use_socket = '';
			} else {
				$use_tcp = '';
				$use_socket = '#';
			}
			$content = str_replace('{use_tcp}', $use_tcp, $content);
			$content = str_replace('{use_socket}', $use_socket, $content);

			wf($vhost_conf_dir.'/apps.vhost', $content);

			// PHP-FPM
			// Dont just copy over the php-fpm pool template but add some custom settings
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/apps_php_fpm_pool.conf.master', 'tpl/apps_php_fpm_pool.conf.master');
			$content = str_replace('{fpm_pool}', 'apps', $content);
			//$content = str_replace('{fpm_port}', ($conf['nginx']['php_fpm_start_port']+1), $content);
			$content = str_replace('{fpm_socket}', $fpm_socket, $content);
			$content = str_replace('{fpm_user}', $apps_vhost_user, $content);
			$content = str_replace('{fpm_group}', $apps_vhost_group, $content);
			wf($conf['nginx']['php_fpm_pool_dir'].'/apps.conf', $content);

			//copy('tpl/nginx_ispconfig.vhost.master', "$vhost_conf_dir/ispconfig.vhost");
			//* and create the symlink
			if(@is_link($vhost_conf_enabled_dir.'/apps.vhost')) unlink($vhost_conf_enabled_dir.'/apps.vhost');
			if(!@is_link($vhost_conf_enabled_dir.'/000-apps.vhost')) {
				symlink($vhost_conf_dir.'/apps.vhost', $vhost_conf_enabled_dir.'/000-apps.vhost');
			}

		}
	}

	public function make_ispconfig_ssl_cert() {
		global $conf;

		$install_dir = $conf['ispconfig_install_dir'];

		$ssl_crt_file = $install_dir.'/interface/ssl/ispserver.crt';
		$ssl_csr_file = $install_dir.'/interface/ssl/ispserver.csr';
		$ssl_key_file = $install_dir.'/interface/ssl/ispserver.key';

		if(!@is_dir($install_dir.'/interface/ssl')) mkdir($install_dir.'/interface/ssl', 0755, true);

		$ssl_pw = substr(md5(mt_rand()), 0, 6);
		exec("openssl genrsa -des3 -passout pass:$ssl_pw -out $ssl_key_file 4096");
		exec("openssl req -new -passin pass:$ssl_pw -passout pass:$ssl_pw -key $ssl_key_file -out $ssl_csr_file");
		exec("openssl req -x509 -passin pass:$ssl_pw -passout pass:$ssl_pw -key $ssl_key_file -in $ssl_csr_file -out $ssl_crt_file -days 3650");
		exec("openssl rsa -passin pass:$ssl_pw -in $ssl_key_file -out $ssl_key_file.insecure");
		rename($ssl_key_file, $ssl_key_file.'.secure');
		rename($ssl_key_file.'.insecure', $ssl_key_file);

	}

	public function install_ispconfig() {
		global $conf;

		$install_dir = $conf['ispconfig_install_dir'];

		//* Create the ISPConfig installation directory
		if(!@is_dir($install_dir)) {
			$command = "mkdir $install_dir";
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		//* Create a ISPConfig user and group
		$command = 'groupadd ispconfig';
		if(!is_group('ispconfig')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = 'useradd -g ispconfig -d '.$install_dir.' ispconfig';
		if(!is_user('ispconfig')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* copy the ISPConfig interface part
		$command = 'cp -rf ../interface '.$install_dir;
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* copy the ISPConfig server part
		$command = 'cp -rf ../server '.$install_dir;
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* Create a symlink, so ISPConfig is accessible via web
		// Replaced by a separate vhost definition for port 8080
		// $command = "ln -s $install_dir/interface/web/ /var/www/ispconfig";
		// caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* Create the config file for ISPConfig interface
		$configfile = 'config.inc.php';
		if(is_file($install_dir.'/interface/lib/'.$configfile)) {
			copy($install_dir.'/interface/lib/'.$configfile, $install_dir.'/interface/lib/'.$configfile.'~');
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);

		$content = str_replace('{mysql_master_server_ispconfig_user}', $conf['mysql']['master_ispconfig_user'], $content);
		$content = str_replace('{mysql_master_server_ispconfig_password}', $conf['mysql']['master_ispconfig_password'], $content);
		$content = str_replace('{mysql_master_server_database}', $conf['mysql']['master_database'], $content);
		$content = str_replace('{mysql_master_server_host}', $conf['mysql']['master_host'], $content);

		$content = str_replace('{server_id}', $conf['server_id'], $content);
		$content = str_replace('{ispconfig_log_priority}', $conf['ispconfig_log_priority'], $content);
		$content = str_replace('{language}', $conf['language'], $content);
		$content = str_replace('{timezone}', $conf['timezone'], $content);
		$content = str_replace('{theme}', $conf['theme'], $content);
		$content = str_replace('{language_file_import_enabled}', ($conf['language_file_import_enabled'] == true)?'true':'false', $content);

		wf($install_dir.'/interface/lib/'.$configfile, $content);

		//* Create the config file for ISPConfig server
		$configfile = 'config.inc.php';
		if(is_file($install_dir.'/server/lib/'.$configfile)) {
			copy($install_dir.'/server/lib/'.$configfile, $install_dir.'/interface/lib/'.$configfile.'~');
		}
		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/'.$configfile.'.master', 'tpl/'.$configfile.'.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);

		$content = str_replace('{mysql_master_server_ispconfig_user}', $conf['mysql']['master_ispconfig_user'], $content);
		$content = str_replace('{mysql_master_server_ispconfig_password}', $conf['mysql']['master_ispconfig_password'], $content);
		$content = str_replace('{mysql_master_server_database}', $conf['mysql']['master_database'], $content);
		$content = str_replace('{mysql_master_server_host}', $conf['mysql']['master_host'], $content);

		$content = str_replace('{server_id}', $conf['server_id'], $content);
		$content = str_replace('{ispconfig_log_priority}', $conf['ispconfig_log_priority'], $content);
		$content = str_replace('{language}', $conf['language'], $content);
		$content = str_replace('{timezone}', $conf['timezone'], $content);
		$content = str_replace('{theme}', $conf['theme'], $content);
		$content = str_replace('{language_file_import_enabled}', ($conf['language_file_import_enabled'] == true)?'true':'false', $content);

		wf($install_dir.'/server/lib/'.$configfile, $content);

		//* Create the config file for remote-actions (but only, if it does not exist, because
		//  the value is a autoinc-value and so changed by the remoteaction_core_module
		if (!file_exists($install_dir.'/server/lib/remote_action.inc.php')) {
			$content = '<?php' . "\n" . '$maxid_remote_action = 0;' . "\n" . '?>';
			wf($install_dir.'/server/lib/remote_action.inc.php', $content);
		}

		//* Enable the server modules and plugins.
		// TODO: Implement a selector which modules and plugins shall be enabled.
		$dir = $install_dir.'/server/mods-available/';
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..' && substr($file, -8, 8) == '.inc.php') {
						include_once $install_dir.'/server/mods-available/'.$file;
						$module_name = substr($file, 0, -8);
						$tmp = new $module_name;
						if($tmp->onInstall()) {
							if(!@is_link($install_dir.'/server/mods-enabled/'.$file)) {
								@symlink($install_dir.'/server/mods-available/'.$file, $install_dir.'/server/mods-enabled/'.$file);
								// @symlink($install_dir.'/server/mods-available/'.$file, '../mods-enabled/'.$file);
							}
							if (strpos($file, '_core_module') !== false) {
								if(!@is_link($install_dir.'/server/mods-core/'.$file)) {
									@symlink($install_dir.'/server/mods-available/'.$file, $install_dir.'/server/mods-core/'.$file);
									// @symlink($install_dir.'/server/mods-available/'.$file, '../mods-core/'.$file);
								}
							}
						}
						unset($tmp);
					}
				}
				closedir($dh);
			}
		}

		$dir = $install_dir.'/server/plugins-available/';
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($conf['apache']['installed'] == true && $file == 'nginx_plugin.inc.php') continue;
					if($conf['nginx']['installed'] == true && $file == 'apache2_plugin.inc.php') continue;
					if($file != '.' && $file != '..' && substr($file, -8, 8) == '.inc.php') {
						include_once $install_dir.'/server/plugins-available/'.$file;
						$plugin_name = substr($file, 0, -8);
						$tmp = new $plugin_name;
						if(method_exists($tmp, 'onInstall') && $tmp->onInstall()) {
							if(!@is_link($install_dir.'/server/plugins-enabled/'.$file)) {
								@symlink($install_dir.'/server/plugins-available/'.$file, $install_dir.'/server/plugins-enabled/'.$file);
								//@symlink($install_dir.'/server/plugins-available/'.$file, '../plugins-enabled/'.$file);
							}
							if (strpos($file, '_core_plugin') !== false) {
								if(!@is_link($install_dir.'/server/plugins-core/'.$file)) {
									@symlink($install_dir.'/server/plugins-available/'.$file, $install_dir.'/server/plugins-core/'.$file);
									//@symlink($install_dir.'/server/plugins-available/'.$file, '../plugins-core/'.$file);
								}
							}
						}
						unset($tmp);
					}
				}
				closedir($dh);
			}
		}

		// Update the server config
		$mail_server_enabled = ($conf['services']['mail'])?1:0;
		$web_server_enabled = ($conf['services']['web'])?1:0;
		$dns_server_enabled = ($conf['services']['dns'])?1:0;
		$file_server_enabled = ($conf['services']['file'])?1:0;
		$db_server_enabled = ($conf['services']['db'])?1:0;
		$vserver_server_enabled = ($conf['openvz']['installed'])?1:0;
		$proxy_server_enabled = ($conf['services']['proxy'])?1:0;
		$firewall_server_enabled = ($conf['services']['firewall'])?1:0;

		$sql = "UPDATE `server` SET mail_server = '$mail_server_enabled', web_server = '$web_server_enabled', dns_server = '$dns_server_enabled', file_server = '$file_server_enabled', db_server = '$db_server_enabled', vserver_server = '$vserver_server_enabled', proxy_server = '$proxy_server_enabled', firewall_server = '$firewall_server_enabled' WHERE server_id = ".intval($conf['server_id']);

		if($conf['mysql']['master_slave_setup'] == 'y') {
			$this->dbmaster->query($sql);
			$this->db->query($sql);
		} else {
			$this->db->query($sql);
		}


		//* Chmod the files
		$command = 'chmod -R 750 '.$install_dir;
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* chown the files to the ispconfig user and group
		$command = 'chown -R ispconfig:ispconfig '.$install_dir;
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* Make the global language file directory group writable
		exec("chmod -R 770 $install_dir/interface/lib/lang");

		//* Make the temp directory for language file exports writable
		if(is_dir($install_dir.'/interface/web/temp')) exec("chmod -R 770 $install_dir/interface/web/temp");

		//* Make all interface language file directories group writable
		$handle = @opendir($install_dir.'/interface/web');
		while ($file = @readdir($handle)) {
			if ($file != '.' && $file != '..') {
				if(@is_dir($install_dir.'/interface/web'.'/'.$file.'/lib/lang')) {
					$handle2 = opendir($install_dir.'/interface/web'.'/'.$file.'/lib/lang');
					chmod($install_dir.'/interface/web'.'/'.$file.'/lib/lang', 0770);
					while ($lang_file = @readdir($handle2)) {
						if ($lang_file != '.' && $lang_file != '..') {
							chmod($install_dir.'/interface/web'.'/'.$file.'/lib/lang/'.$lang_file, 0770);
						}
					}
				}
			}
		}

		//* Make the APS directories group writable
		exec("chmod -R 770 $install_dir/interface/web/sites/aps_meta_packages");
		exec("chmod -R 770 $install_dir/server/aps_packages");

		//* make sure that the server config file (not the interface one) is only readable by the root user
		chmod($install_dir.'/server/lib/config.inc.php', 0600);
		chown($install_dir.'/server/lib/config.inc.php', 'root');
		chgrp($install_dir.'/server/lib/config.inc.php', 'root');

		//* Make sure thet the interface config file is readable by user ispconfig only
		chmod($install_dir.'/interface/lib/config.inc.php', 0600);
		chown($install_dir.'/interface/lib/config.inc.php', 'ispconfig');
		chgrp($install_dir.'/interface/lib/config.inc.php', 'ispconfig');

		chmod($install_dir.'/server/lib/remote_action.inc.php', 0600);
		chown($install_dir.'/server/lib/remote_action.inc.php', 'root');
		chgrp($install_dir.'/server/lib/remote_action.inc.php', 'root');

		if(@is_file($install_dir.'/server/lib/mysql_clientdb.conf')) {
			chmod($install_dir.'/server/lib/mysql_clientdb.conf', 0600);
			chown($install_dir.'/server/lib/mysql_clientdb.conf', 'root');
			chgrp($install_dir.'/server/lib/mysql_clientdb.conf', 'root');
		}

		if(is_dir($install_dir.'/interface/invoices')) {
			exec('chmod -R 770 '.escapeshellarg($install_dir.'/interface/invoices'));
			exec('chown -R ispconfig:ispconfig '.escapeshellarg($install_dir.'/interface/invoices'));
		}

		// TODO: FIXME: add the www-data user to the ispconfig group. This is just for testing
		// and must be fixed as this will allow the apache user to read the ispconfig files.
		// Later this must run as own apache server or via suexec!
		if($conf['apache']['installed'] == true){
			$command = 'adduser '.$conf['apache']['user'].' ispconfig';
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
			if(is_group('ispapps')){
				$command = 'adduser '.$conf['apache']['user'].' ispapps';
				caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
			}
		}
		if($conf['nginx']['installed'] == true){
			$command = 'adduser '.$conf['nginx']['user'].' ispconfig';
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
			if(is_group('ispapps')){
				$command = 'adduser '.$conf['nginx']['user'].' ispapps';
				caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
			}
		}

		//* Make the shell scripts executable
		$command = "chmod +x $install_dir/server/scripts/*.sh";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		if($conf['apache']['installed'] == true && $this->install_ispconfig_interface == true){
			//* Copy the ISPConfig vhost for the controlpanel
			$vhost_conf_dir = $conf['apache']['vhost_conf_dir'];
			$vhost_conf_enabled_dir = $conf['apache']['vhost_conf_enabled_dir'];

			// Dont just copy over the virtualhost template but add some custom settings
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/apache_ispconfig.vhost.master', 'tpl/apache_ispconfig.vhost.master');
			$content = str_replace('{vhost_port}', $conf['apache']['vhost_port'], $content);

			// comment out the listen directive if port is 80 or 443
			if($conf['apache']['vhost_port'] == 80 or $conf['apache']['vhost_port'] == 443) {
				$content = str_replace('{vhost_port_listen}', '#', $content);
			} else {
				$content = str_replace('{vhost_port_listen}', '', $content);
			}

			if(is_file($install_dir.'/interface/ssl/ispserver.crt') && is_file($install_dir.'/interface/ssl/ispserver.key')) {
				$content = str_replace('{ssl_comment}', '', $content);
			} else {
				$content = str_replace('{ssl_comment}', '#', $content);
			}
			if(is_file($install_dir.'/interface/ssl/ispserver.crt') && is_file($install_dir.'/interface/ssl/ispserver.key') && is_file($install_dir.'/interface/ssl/ispserver.bundle')) {
				$content = str_replace('{ssl_bundle_comment}', '', $content);
			} else {
				$content = str_replace('{ssl_bundle_comment}', '#', $content);
			}

			wf($vhost_conf_dir.'/ispconfig.vhost', $content);

			//copy('tpl/apache_ispconfig.vhost.master', $vhost_conf_dir.'/ispconfig.vhost');
			//* and create the symlink
			if($this->is_update == false) {
				if(@is_link($vhost_conf_enabled_dir.'/ispconfig.vhost')) unlink($vhost_conf_enabled_dir.'/ispconfig.vhost');
				if(!@is_link($vhost_conf_enabled_dir.'/000-ispconfig.vhost')) {
					symlink($vhost_conf_dir.'/ispconfig.vhost', $vhost_conf_enabled_dir.'/000-ispconfig.vhost');
				}
			}
			//if(!is_file('/var/www/php-fcgi-scripts/ispconfig/.php-fcgi-starter')) {
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/apache_ispconfig_fcgi_starter.master', 'tpl/apache_ispconfig_fcgi_starter.master');
			$content = str_replace('{fastcgi_bin}', $conf['fastcgi']['fastcgi_bin'], $content);
			$content = str_replace('{fastcgi_phpini_path}', $conf['fastcgi']['fastcgi_phpini_path'], $content);
			@mkdir('/var/www/php-fcgi-scripts/ispconfig', 0755, true);
			wf('/var/www/php-fcgi-scripts/ispconfig/.php-fcgi-starter', $content);
			exec('chmod +x /var/www/php-fcgi-scripts/ispconfig/.php-fcgi-starter');
			@symlink($install_dir.'/interface/web', '/var/www/ispconfig');
			exec('chown -R ispconfig:ispconfig /var/www/php-fcgi-scripts/ispconfig');
			//}
		}

		if($conf['nginx']['installed'] == true && $this->install_ispconfig_interface == true){
			//* Copy the ISPConfig vhost for the controlpanel
			$vhost_conf_dir = $conf['nginx']['vhost_conf_dir'];
			$vhost_conf_enabled_dir = $conf['nginx']['vhost_conf_enabled_dir'];

			// Dont just copy over the virtualhost template but add some custom settings
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/nginx_ispconfig.vhost.master', 'tpl/nginx_ispconfig.vhost.master');
			$content = str_replace('{vhost_port}', $conf['nginx']['vhost_port'], $content);

			if(is_file($install_dir.'/interface/ssl/ispserver.crt') && is_file($install_dir.'/interface/ssl/ispserver.key')) {
				$content = str_replace('{ssl_on}', 'on', $content);
				$content = str_replace('{ssl_comment}', '', $content);
				$content = str_replace('{fastcgi_ssl}', 'on', $content);
			} else {
				$content = str_replace('{ssl_on}', 'off', $content);
				$content = str_replace('{ssl_comment}', '#', $content);
				$content = str_replace('{fastcgi_ssl}', 'off', $content);
			}

			$socket_dir = escapeshellcmd($conf['nginx']['php_fpm_socket_dir']);
			if(substr($socket_dir, -1) != '/') $socket_dir .= '/';
			if(!is_dir($socket_dir)) exec('mkdir -p '.$socket_dir);
			$fpm_socket = $socket_dir.'ispconfig.sock';

			//$content = str_replace('{fpm_port}', $conf['nginx']['php_fpm_start_port'], $content);
			$content = str_replace('{fpm_socket}', $fpm_socket, $content);

			wf($vhost_conf_dir.'/ispconfig.vhost', $content);

			unset($content);

			// PHP-FPM
			// Dont just copy over the php-fpm pool template but add some custom settings
			$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/php_fpm_pool.conf.master', 'tpl/php_fpm_pool.conf.master');
			$content = str_replace('{fpm_pool}', 'ispconfig', $content);
			//$content = str_replace('{fpm_port}', $conf['nginx']['php_fpm_start_port'], $content);
			$content = str_replace('{fpm_socket}', $fpm_socket, $content);
			$content = str_replace('{fpm_user}', 'ispconfig', $content);
			$content = str_replace('{fpm_group}', 'ispconfig', $content);
			wf($conf['nginx']['php_fpm_pool_dir'].'/ispconfig.conf', $content);

			//copy('tpl/nginx_ispconfig.vhost.master', $vhost_conf_dir.'/ispconfig.vhost');
			//* and create the symlink
			if($this->is_update == false) {
				if(@is_link($vhost_conf_enabled_dir.'/ispconfig.vhost')) unlink($vhost_conf_enabled_dir.'/ispconfig.vhost');
				if(!@is_link($vhost_conf_enabled_dir.'/000-ispconfig.vhost')) {
					symlink($vhost_conf_dir.'/ispconfig.vhost', $vhost_conf_enabled_dir.'/000-ispconfig.vhost');
				}
			}
		}

		//* Install the update script
		if(is_file('/usr/local/bin/ispconfig_update_from_dev.sh')) unlink('/usr/local/bin/ispconfig_update_from_dev.sh');
		chown($install_dir.'/server/scripts/update_from_dev.sh', 'root');
		chmod($install_dir.'/server/scripts/update_from_dev.sh', 0700);
		chown($install_dir.'/server/scripts/update_from_tgz.sh', 'root');
		chmod($install_dir.'/server/scripts/update_from_tgz.sh', 0700);
		chown($install_dir.'/server/scripts/ispconfig_update.sh', 'root');
		chmod($install_dir.'/server/scripts/ispconfig_update.sh', 0700);
		if(!is_link('/usr/local/bin/ispconfig_update_from_dev.sh')) symlink($install_dir.'/server/scripts/ispconfig_update.sh', '/usr/local/bin/ispconfig_update_from_dev.sh');
		if(!is_link('/usr/local/bin/ispconfig_update.sh')) symlink($install_dir.'/server/scripts/ispconfig_update.sh', '/usr/local/bin/ispconfig_update.sh');

		//* Make the logs readable for the ispconfig user
		if(@is_file('/var/log/mail.log')) exec('chmod +r /var/log/mail.log');
		if(@is_file('/var/log/mail.warn')) exec('chmod +r /var/log/mail.warn');
		if(@is_file('/var/log/mail.err')) exec('chmod +r /var/log/mail.err');
		if(@is_file('/var/log/messages')) exec('chmod +r /var/log/messages');
		if(@is_file('/var/log/clamav/clamav.log')) exec('chmod +r /var/log/clamav/clamav.log');
		if(@is_file('/var/log/clamav/freshclam.log')) exec('chmod +r /var/log/clamav/freshclam.log');

		//* Create the ispconfig log file and directory
		if(!is_file($conf['ispconfig_log_dir'].'/ispconfig.log')) {
			if(!is_dir($conf['ispconfig_log_dir'])) mkdir($conf['ispconfig_log_dir'], 0755);
			touch($conf['ispconfig_log_dir'].'/ispconfig.log');
		}

		//* Create the ispconfig auth log file and set uid/gid
		if(!is_file($conf['ispconfig_log_dir'].'/auth.log')) {
			touch($conf['ispconfig_log_dir'].'/auth.log');
		}
		exec('chown ispconfig:ispconfig '. $conf['ispconfig_log_dir'].'/auth.log');
		exec('chmod 660 '. $conf['ispconfig_log_dir'].'/auth.log');

		if(is_user('getmail')) {
			rename($install_dir.'/server/scripts/run-getmail.sh', '/usr/local/bin/run-getmail.sh');
			if(is_user('getmail')) chown('/usr/local/bin/run-getmail.sh', 'getmail');
			chmod('/usr/local/bin/run-getmail.sh', 0744);
		}

		//* Add Log-Rotation
		if (is_dir('/etc/logrotate.d')) {
			@unlink('/etc/logrotate.d/logispc3'); // ignore, if the file is not there
			/* We rotate these logs in cron_daily.php
			$fh = fopen('/etc/logrotate.d/logispc3', 'w');
			fwrite($fh,
					"$conf['ispconfig_log_dir']/ispconfig.log { \n" .
					"	weekly \n" .
					"	missingok \n" .
					"	rotate 4 \n" .
					"	compress \n" .
					"	delaycompress \n" .
					"} \n" .
					"$conf['ispconfig_log_dir']/cron.log { \n" .
					"	weekly \n" .
					"	missingok \n" .
					"	rotate 4 \n" .
					"	compress \n" .
					"	delaycompress \n" .
					"}");
			fclose($fh);
			*/
		}

		//* Remove Domain module as its functions are available in the client module now
		if(@is_dir('/usr/local/ispconfig/interface/web/domain')) exec('rm -rf /usr/local/ispconfig/interface/web/domain');


	}

	public function configure_dbserver() {
		global $conf;

		//* If this server shall act as database server for client DB's, we configure this here
		$install_dir = $conf['ispconfig_install_dir'];

		// Create a file with the database login details which
		// are used to create the client databases.

		if(!is_dir($install_dir.'/server/lib')) {
			$command = "mkdir $install_dir/server/lib";
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}

		$content = rfsel($conf['ispconfig_install_dir'].'/server/conf-custom/install/mysql_clientdb.conf.master', 'tpl/mysql_clientdb.conf.master');
		$content = str_replace('{hostname}', $conf['mysql']['host'], $content);
		$content = str_replace('{username}', $conf['mysql']['admin_user'], $content);
		$content = str_replace('{password}', $conf['mysql']['admin_password'], $content);
		wf($install_dir.'/server/lib/mysql_clientdb.conf', $content);
		chmod($install_dir.'/server/lib/mysql_clientdb.conf', 0600);
		chown($install_dir.'/server/lib/mysql_clientdb.conf', 'root');
		chgrp($install_dir.'/server/lib/mysql_clientdb.conf', 'root');

	}

	public function install_crontab() {
		global $conf;

		$install_dir = $conf['ispconfig_install_dir'];

		//* Root Crontab
		exec('crontab -u root -l > crontab.txt');
		$existing_root_cron_jobs = file('crontab.txt');

		// remove existing ispconfig cronjobs, in case the syntax has changed
		foreach($existing_root_cron_jobs as $key => $val) {
			if(stristr($val, $install_dir)) unset($existing_root_cron_jobs[$key]);
		}

		$root_cron_jobs = array(
			"* * * * * ".$install_dir."/server/server.sh 2>&1 > /dev/null | while read line; do echo `/bin/date` \"\$line\" >> ".$conf['ispconfig_log_dir']."/cron.log; done",
			"30 00 * * * ".$install_dir."/server/cron_daily.sh 2>&1 > /dev/null | while read line; do echo `/bin/date` \"\$line\" >> ".$conf['ispconfig_log_dir']."/cron.log; done"
		);

		if ($conf['nginx']['installed'] == true) {
			$root_cron_jobs[] = "0 0 * * * ".$install_dir."/server/scripts/create_daily_nginx_access_logs.sh &> /dev/null";
		}

		foreach($root_cron_jobs as $cron_job) {
			if(!in_array($cron_job."\n", $existing_root_cron_jobs)) {
				$existing_root_cron_jobs[] = $cron_job."\n";
			}
		}
		file_put_contents('crontab.txt', $existing_root_cron_jobs);
		exec('crontab -u root crontab.txt &> /dev/null');
		unlink('crontab.txt');

		//* Getmail crontab
		if(is_user('getmail')) {
			$cf = $conf['getmail'];
			exec('crontab -u getmail -l > crontab.txt');
			$existing_cron_jobs = file('crontab.txt');

			$cron_jobs = array(
				'*/5 * * * * /usr/local/bin/run-getmail.sh > /dev/null 2>> /dev/null'
			);

			// remove existing ispconfig cronjobs, in case the syntax has changed
			foreach($existing_cron_jobs as $key => $val) {
				if(stristr($val, 'getmail')) unset($existing_cron_jobs[$key]);
			}

			foreach($cron_jobs as $cron_job) {
				if(!in_array($cron_job."\n", $existing_cron_jobs)) {
					$existing_cron_jobs[] = $cron_job."\n";
				}
			}
			file_put_contents('crontab.txt', $existing_cron_jobs);
			exec('crontab -u getmail crontab.txt &> /dev/null');
			unlink('crontab.txt');
		}

		touch($conf['ispconfig_log_dir'].'/cron.log');
		chmod($conf['ispconfig_log_dir'].'/cron.log', 0660);

	}

	public function getinitcommand($servicename, $action, $init_script_directory = ''){
		global $conf;
		// systemd
		if(is_executable('/bin/systemd')){
			return 'systemctl '.$action.' '.$servicename.'.service';
		}
		// upstart
		if(is_executable('/sbin/initctl')){
			exec('/sbin/initctl version 2>/dev/null | /bin/grep -q upstart', $retval['output'], $retval['retval']);
			if(intval($retval['retval']) == 0) return 'service '.$servicename.' '.$action;
		}
		// sysvinit
		if($init_script_directory == '') $init_script_directory = $conf['init_scripts'];
		if(substr($init_script_directory, -1) === '/') $init_script_directory = substr($init_script_directory, 0, -1);
		return $init_script_directory.'/'.$servicename.' '.$action;
	}

	/**
	 * Helper function - get the path to a template file based on
	 * the local part of the filename. Checks first for the existence
	 * of a distribution specific file and if not found looks in the
	 * base template folder. Optionally the behaviour can be changed
	 * by setting the 2nd parameter which will fetch the contents
	 * of the template file and return it instead of the path. The 3rd
	 * parameter further extends this behaviour by filtering the contents
	 * by inserting the ispconfig database credentials using the {} placeholders.
	 *
	 * @param string $tLocal local part of filename
	 * @param bool $tRf
	 * @param bool $tDBCred
	 * @return string Relative path to the chosen template file
	 */
	protected function get_template_file($tLocal, $tRf=false, $tDBCred=false) {
		global $conf, $dist;

		$final_path = '';
		$dist_template = $conf['ispconfig_install_dir'] . '/server/conf-custom/install/' . $tLocal . '.master';
		if (file_exists($dist_template)) {
			$final_path = $dist_template;
		} else {
			$dist_template = 'dist/tpl/'.strtolower($dist['name'])."/$tLocal.master";
			if (file_exists($dist_template)) {
				$final_path = $dist_template;
			} else {
				$final_path = "tpl/$tLocal.master";
			}
		}

		if (!$tRf) {
			return $final_path;
		} else {
			return (!$tDBCred) ? rf($final_path) : $this->insert_db_credentials(rf($final_path));
		}
	}

	/**
	 * Helper function - writes the contents to a config file
	 * and performs a backup if the file exist. Additionally
	 * if the file exists the new file will be given the
	 * same rights and ownership as the original. Optionally the
	 * rights and/or ownership can be overriden by appending umask,
	 * user and group to the parameters. Providing only uid and gid
	 * values will result in only a chown.
	 *
	 * @param $tConf
	 * @param $tContents
	 * @return bool
	 */
	protected function write_config_file($tConf, $tContents) {
		// Backup config file before writing new contents and stat file
		if ( is_file($tConf) ) {
			$stat = exec('stat -c \'%a %U %G\' '.escapeshellarg($tConf), $output, $res);
			if ($res == 0) { // stat successfull
				list($access, $user, $group) = explode(" ", $stat);
			}

			if ( copy($tConf, $tConf.'~') ) {
				chmod($tConf.'~', 0400);
			}
		}

		wf($tConf, $tContents); // write file

		if (func_num_args() >= 4) // override rights and/or ownership
			{
			$args = func_get_args();
			$output = array_slice($args, 2);

			switch (sizeof($output)) {
			case 3:
				$umask = array_shift($output);
				if (is_numeric($umask) && preg_match('/^0?[0-7]{3}$/', $umask)) {
					$access = $umask;
				}
			case 2:
				if (is_user($output[0]) && is_group($output[1])) {
					list($user, $group) = $output;
				}
				break;
			}
		}

		if (!empty($user) && !empty($group)) {
			chown($tConf, $user);
			chgrp($tConf, $group);
		}

		if (!empty($access)) {
			exec("chmod $access $tConf");
		}
	}

	/**
	 * Helper function - filter the contents of a config
	 * file by inserting the common ispconfig database
	 * credentials.
	 *
	 * @param $tContents
	 * @return string
	 */
	protected function insert_db_credentials($tContents) {
		global $conf;

		$tContents = str_replace('{mysql_server_ispconfig_user}', $conf["mysql"]["ispconfig_user"], $tContents);
		$tContents = str_replace('{mysql_server_ispconfig_password}', $conf["mysql"]["ispconfig_password"], $tContents);
		$tContents = str_replace('{mysql_server_database}', $conf["mysql"]["database"], $tContents);
		$tContents = str_replace('{mysql_server_ip}', $conf["mysql"]["ip"], $tContents);
		$tContents = str_replace('{mysql_server_host}', $conf['mysql']['host'], $tContents);
		$tContents = str_replace('{mysql_server_port}', $conf["mysql"]["port"], $tContents);

		return $tContents;
	}

}

?>
