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

class installer_base {
	
	var $wb = array();
	var $language = 'en';
	var $db;
	public $conf;
	public $install_ispconfig_interface = true;
	public $is_update = false; // true if it is an update, falsi if it is a new install


    public function __construct()
    {
        global $conf; //TODO: maybe $conf  should be passed to constructor
        //$this->conf = $conf;
    }
	
    //: TODO  Implement the translation function and language files for the installer.
	public function lng($text)
    {
		return $text;
	}
	
	public function error($msg)
    {
		die("ERROR: ".$msg."\n");
	}
	
	public function simple_query($query, $answers, $default)
    {		
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
	
	public function free_query($query,$default)
    {		
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
		if(is_installed('apache') || is_installed('apache2') || is_installed('httpd')) $conf['apache']['installed'] = true;
		if(is_installed('getmail')) $conf['getmail']['installed'] = true;
		if(is_installed('couriertcpd')) $conf['courier']['installed'] = true;
		if(is_installed('saslsauthd')) $conf['saslauthd']['installed'] = true;
		if(is_installed('amavisd-new')) $conf['amavis']['installed'] = true;
		if(is_installed('clamdscan')) $conf['clamav']['installed'] = true;
		if(is_installed('pure-ftpd') || is_installed('pure-ftpd-wrapper')) $conf['pureftpd']['installed'] = true;
		if(is_installed('mydns') || is_installed('mydns-ng')) $conf['mydns']['installed'] = true;
		if(is_installed('jk_chrootsh')) $conf['jailkit']['installed'] = true;
		
		
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
				caselog("mysql --default-character-set=".$conf['mysql']['charset']." -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' '".$conf['mysql']['database']."' < '".ISPC_INSTALL_ROOT."/install/sql/ispconfig3.sql' &> /dev/null", 
                        __FILE__, __LINE__, 'read in ispconfig3.sql', 'could not read in ispconfig3.sql');
			} else {
				caselog("mysql --default-character-set=".$conf['mysql']['charset']." -h '".$conf['mysql']['host']."' -u '".$conf['mysql']['admin_user']."' -p'".$conf['mysql']['admin_password']."' '".$conf['mysql']['database']."' < '".ISPC_INSTALL_ROOT."/install/sql/ispconfig3.sql' &> /dev/null", 
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
		
		// TODO: Update further distribution specific parameters for server config here
		$tpl_ini_array['web']['vhost_conf_dir'] = $conf['apache']['vhost_conf_dir'];
		$tpl_ini_array['web']['vhost_conf_enabled_dir'] = $conf['apache']['vhost_conf_enabled_dir'];
		
		$server_ini_content = array_to_ini($tpl_ini_array);
		$server_ini_content = mysql_real_escape_string($server_ini_content);
		
		$mail_server_enabled = ($conf['services']['mail'])?1:0;
		$web_server_enabled = ($conf['services']['web'])?1:0;
		$dns_server_enabled = ($conf['services']['dns'])?1:0;
		$file_server_enabled = ($conf['services']['file'])?1:0;
		$db_server_enabled = ($conf['services']['db'])?1:0;
		$vserver_server_enabled = ($conf['services']['vserver'])?1:0;
		
		if($conf['mysql']['master_slave_setup'] == 'y') {
			
			//* Insert the server record in master DB
			$sql = "INSERT INTO `server` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_name`, `mail_server`, `web_server`, `dns_server`, `file_server`, `db_server`, `vserver_server`, `config`, `updated`, `active`) VALUES (1, 1, 'riud', 'riud', 'r', '".$conf['hostname']."', '$mail_server_enabled', '$web_server_enabled', '$dns_server_enabled', '$file_server_enabled', '$db_server_enabled', '$vserver_server_enabled', '$server_ini_content', 0, 1);";
			$this->dbmaster->query($sql);
			$conf['server_id'] = $this->dbmaster->insertID();
			$conf['server_id'] = $conf['server_id'];
			
			//* Insert the same record in the local DB
			$sql = "INSERT INTO `server` (`server_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_name`, `mail_server`, `web_server`, `dns_server`, `file_server`, `db_server`, `vserver_server`, `config`, `updated`, `active`) VALUES ('".$conf['server_id']."',1, 1, 'riud', 'riud', 'r', '".$conf['hostname']."', '$mail_server_enabled', '$web_server_enabled', '$dns_server_enabled', '$file_server_enabled', '$db_server_enabled', '$vserver_server_enabled', '$server_ini_content', 0, 1);";
			$this->db->query($sql);
			
			//* insert the ispconfig user in the remote server
			$from_host = $conf['hostname'];
			$from_ip = gethostbyname($conf['hostname']);
			
			//* username for the ispconfig user
			$conf['mysql']['master_ispconfig_user'] = 'ispconfigserver'.$conf['server_id'];
		
			//* Delete ISPConfig user in the master database, in case that it exists
			$this->dbmaster->query("DELETE FROM mysql.user WHERE User = '".$conf['mysql']['master_ispconfig_user']."' AND Host = '".$from_host."';");
			$this->dbmaster->query("DELETE FROM mysql.db WHERE Db = '".$conf['mysql']['master_database']."' AND Host = '".$from_host."';");
			$this->dbmaster->query("DELETE FROM mysql.user WHERE User = '".$conf['mysql']['master_ispconfig_user']."' AND Host = '".$from_ip."';");
			$this->dbmaster->query("DELETE FROM mysql.db WHERE Db = '".$conf['mysql']['master_database']."' AND Host = '".$from_ip."';");
			$this->dbmaster->query('FLUSH PRIVILEGES;');
		
			//* Create the ISPConfig database user in the local database
        	$query = 'GRANT SELECT, INSERT, UPDATE, DELETE ON '.$conf['mysql']['master_database'].".* "
                	."TO '".$conf['mysql']['master_ispconfig_user']."'@'".$from_host."' "
                	."IDENTIFIED BY '".$conf['mysql']['master_ispconfig_password']."';";
			$query = 'GRANT SELECT, INSERT, UPDATE, DELETE ON '.$conf['mysql']['master_database'].".* "
                	."TO '".$conf['mysql']['master_ispconfig_user']."'@'".$from_ip."' "
                	."IDENTIFIED BY '".$conf['mysql']['master_ispconfig_password']."';";
			if(!$this->dbmaster->query($query)) {
				$this->error('Unable to create database user in master database: '.$conf['mysql']['master_ispconfig_user'].' Error: '.$this->dbmaster->errorMessage);
			}
		
		} else {
			//* Insert the server, if its not a mster / slave setup
			$sql = "INSERT INTO `server` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_name`, `mail_server`, `web_server`, `dns_server`, `file_server`, `db_server`, `vserver_server`, `config`, `updated`, `active`) VALUES (1, 1, 'riud', 'riud', 'r', '".$conf['hostname']."', '$mail_server_enabled', '$web_server_enabled', '$dns_server_enabled', '$file_server_enabled', '$db_server_enabled', '$vserver_server_enabled', '$server_ini_content', 0, 1);";
			$this->db->query($sql);
			$conf['server_id'] = $this->db->insertID();
			$conf['server_id'] = $conf['server_id'];
		}
		
		
	}
	

    //** writes postfix configuration files
    public function process_postfix_config($configfile)
    {	
		global $conf;
		
        $config_dir = $conf['postfix']['config_dir'].'/';
        $full_file_name = $config_dir.$configfile; 
        //* Backup exiting file
        if(is_file($full_file_name)){
            copy($full_file_name, $config_dir.$configfile.'~');
        }
        $content = rf('tpl/'.$configfile.'.master');
        $content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
        $content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
        $content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
        $content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
        $content = str_replace('{server_id}', $conf['server_id'], $content);
        wf($full_file_name, $content);
    }

	public function configure_jailkit()
    {
        global $conf;
		
		$cf = $conf['jailkit'];
		$config_dir = $cf['config_dir'];
		$jk_init = $cf['jk_init'];
		$jk_chrootsh = $cf['jk_chrootsh'];
		
		if (is_dir($config_dir))
		{
			if(is_file($config_dir.'/'.$jk_init)) copy($config_dir.'/'.$jk_init, $config_dir.'/'.$jk_init.'~');
			if(is_file($config_dir.'/'.$jk_chrootsh.".master")) copy($config_dir.'/'.$jk_chrootsh.".master", $config_dir.'/'.$jk_chrootsh.'~');
			
			copy('tpl/'.$jk_init.".master", $config_dir.'/'.$jk_init);
			copy('tpl/'.$jk_chrootsh.".master", $config_dir.'/'.$jk_chrootsh);
		}
		
    }
        
	public function configure_postfix($options = '')
    {
        global $conf;
		$cf = $conf['postfix'];
		$config_dir = $cf['config_dir'];
        
		if(!is_dir($config_dir)){
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

		$postconf_commands = array (
			'myhostname = '.$conf['hostname'],
			'mydestination = '.$conf['hostname'].', localhost, localhost.localdomain',
			'mynetworks = 127.0.0.0/8',
			'virtual_alias_domains =',
			'virtual_alias_maps = proxy:mysql:'.$config_dir.'/mysql-virtual_forwardings.cf, mysql:'.$config_dir.'/mysql-virtual_email2email.cf',
			'virtual_mailbox_domains = proxy:mysql:'.$config_dir.'/mysql-virtual_domains.cf',
			'virtual_mailbox_maps = proxy:mysql:'.$config_dir.'/mysql-virtual_mailboxes.cf',
			'virtual_mailbox_base = '.$cf['vmail_mailbox_base'],
			'virtual_uid_maps = static:'.$cf['vmail_userid'],
			'virtual_gid_maps = static:'.$cf['vmail_groupid'],
			'smtpd_sasl_auth_enable = yes',
			'broken_sasl_auth_clients = yes',
			'smtpd_recipient_restrictions = permit_mynetworks, permit_sasl_authenticated, check_recipient_access mysql:'.$config_dir.'/mysql-virtual_recipient.cf, reject_unauth_destination',
			'smtpd_use_tls = yes',
			'smtpd_tls_security_level = may',
			'smtpd_tls_cert_file = '.$config_dir.'/smtpd.cert',
			'smtpd_tls_key_file = '.$config_dir.'/smtpd.key',
			'transport_maps = proxy:mysql:'.$config_dir.'/mysql-virtual_transports.cf',
			'relay_domains = mysql:'.$config_dir.'/mysql-virtual_relaydomains.cf',
			'virtual_create_maildirsize = yes',
			'virtual_mailbox_extended = yes',
			'virtual_mailbox_limit_maps = proxy:mysql:'.$config_dir.'/mysql-virtual_mailbox_limit_maps.cf',
			'virtual_mailbox_limit_override = yes',
			'virtual_maildir_limit_message = "The user you are trying to reach is over quota."',
			'virtual_overquota_bounce = yes',
			'proxy_read_maps = $local_recipient_maps $mydestination $virtual_alias_maps $virtual_alias_domains $virtual_mailbox_maps $virtual_mailbox_domains $relay_recipient_maps $relay_domains $canonical_maps $sender_canonical_maps $recipient_canonical_maps $relocated_maps $transport_maps $mynetworks $virtual_mailbox_limit_maps',
			'smtpd_sender_restrictions = check_sender_access mysql:'.$config_dir.'/mysql-virtual_sender.cf',
			'smtpd_client_restrictions = check_client_access mysql:'.$config_dir.'/mysql-virtual_client.cf',
			'maildrop_destination_concurrency_limit = 1',
			'maildrop_destination_recipient_limit   = 1',
			'virtual_transport = maildrop',
			'header_checks = regexp:'.$config_dir.'/header_checks',
			'mime_header_checks = regexp:'.$config_dir.'/mime_header_checks',
			'nested_header_checks = regexp:'.$config_dir.'/nested_header_checks',
			'body_checks = regexp:'.$config_dir.'/body_checks'
		);
		
		//* Create the header and body check files
		touch($config_dir.'/header_checks');
		touch($config_dir.'/mime_header_checks');
		touch($config_dir.'/nested_header_checks');
		touch($config_dir.'/body_checks');
		
		
		//* Make a backup copy of the main.cf file
		copy($config_dir.'/main.cf', $config_dir.'/main.cf~');
		
		//* Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__, 'EXECUTED: '.$command, 'Failed to execute the command '.$command);
		}
		
		if(!stristr($options,'dont-create-certs')) {
			//* Create the SSL certificate
			$command = 'cd '.$config_dir.'; '
                      .'openssl req -new -outform PEM -out smtpd.cert -newkey rsa:2048 -nodes -keyout smtpd.key -keyform PEM -days 365 -x509';
			exec($command);
		
			$command = 'chmod o= '.$config_dir.'/smtpd.key';
			caselog($command.' &> /dev/null', __FILE__, __LINE__, 'EXECUTED: '.$command, 'Failed to execute the command '.$command);
		}
		
		//** We have to change the permissions of the courier authdaemon directory to make it accessible for maildrop.
		$command = 'chmod 755  /var/run/courier/authdaemon/';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, 'EXECUTED: '.$command, 'Failed to execute the command '.$command);
		
		//* Changing maildrop lines in posfix master.cf
		if(is_file($config_dir.'/master.cf')){
            copy($config_dir.'/master.cf', $config_dir.'/master.cf~');
        }
		if(is_file($config_dir.'/master.cf~')){
            exec('chmod 400 '.$config_dir.'/master.cf~');
        }
		$configfile = $config_dir.'/master.cf';
		$content = rf($configfile);
		$content = str_replace('  flags=DRhu user=vmail argv=/usr/bin/maildrop -d ${recipient}', 
                   '  flags=R user='.$cf['vmail_username'].' argv=/usr/bin/maildrop -d ${recipient} ${extension} ${recipient} ${user} ${nexthop} ${sender}',
                     $content);
		wf($configfile, $content);
		
		//* Writing the Maildrop mailfilter file
		$configfile = 'mailfilter';
		if(is_file($cf['vmail_mailbox_base'].'/.'.$configfile)){
            copy($cf['vmail_mailbox_base'].'/.'.$configfile, $cf['vmail_mailbox_base'].'/.'.$configfile.'~');
        }
		$content = rf("tpl/$configfile.master");
		$content = str_replace('{dist_postfix_vmail_mailbox_base}', $cf['vmail_mailbox_base'], $content);
		wf($cf['vmail_mailbox_base'].'/.'.$configfile, $content);
		
		//* Create the directory for the custom mailfilters
		if(!is_dir($cf['vmail_mailbox_base'].'/mailfilters')) {
			$command = 'mkdir '.$cf['vmail_mailbox_base'].'/mailfilters';
			caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}
		
		//* Chmod and chown the .mailfilter file
		$command = 'chown -R '.$cf['vmail_username'].':'.$cf['vmail_groupname'].' '.$cf['vmail_mailbox_base'].'/.mailfilter';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		$command = 'chmod -R 600 '.$cf['vmail_mailbox_base'].'/.mailfilter';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
	}
	
	public function configure_saslauthd() {
		global $conf;
		
	
		$configfile = 'sasl_smtpd.conf';
		if(is_file($conf["postfix"]["config_dir"].'/sasl/smtpd.conf')) copy($conf["postfix"]["config_dir"].'/sasl/smtpd.conf',$conf["postfix"]["config_dir"].'/sasl/smtpd.conf~');
		if(is_file($conf["postfix"]["config_dir"].'/sasl/smtpd.conf~')) exec('chmod 400 '.$conf["postfix"]["config_dir"].'/sasl/smtpd.conf~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf['mysql']['ispconfig_user'],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}',$conf['mysql']['database'],$content);
		$content = str_replace('{mysql_server_ip}',$conf['mysql']['ip'],$content);
		wf($conf["postfix"]["config_dir"].'/sasl/smtpd.conf',$content);
		
		// TODO: Chmod and chown on the config file
		
		
		
		// Create the spool directory
		exec('mkdir -p /var/spool/postfix/var/run/saslauthd');
		
		// Edit the file /etc/default/saslauthd
		$configfile = $conf["saslauthd"]["config"];
		if(is_file($configfile)) copy($configfile,$configfile.'~');
		if(is_file($configfile.'~')) exec('chmod 400 '.$configfile.'~');
		$content = rf($configfile);
		$content = str_replace('START=no','START=yes',$content);
		// Debian
		$content = str_replace('OPTIONS="-c"','OPTIONS="-m /var/spool/postfix/var/run/saslauthd -r"',$content);
		// Ubuntu
		$content = str_replace('OPTIONS="-c -m /var/run/saslauthd"','OPTIONS="-c -m /var/spool/postfix/var/run/saslauthd -r"',$content);
		wf($configfile,$content);
		
		// Edit the file /etc/init.d/saslauthd
		$configfile = $conf["init_scripts"].'/'.$conf["saslauthd"]["init_script"];
		$content = rf($configfile);
		$content = str_replace('PIDFILE=$RUN_DIR/saslauthd.pid','PIDFILE="/var/spool/postfix/var/run/${NAME}/saslauthd.pid"',$content);
		wf($configfile,$content);
		
		// add the postfix user to the sasl group (at least nescessary for ubuntu 8.04 and most likely debian lenny too.
		exec('adduser postfix sasl');
		
		
	}
	
	public function configure_pam()
    {
		global $conf;
		$pam = $conf['pam'];
		//* configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'pamd_smtp';
		if(is_file("$pam/smtp"))    copy("$pam/smtp", "$pam/smtp~");
		if(is_file("$pam/smtp~"))   exec("chmod 400 $pam/smtp~");

		$content = rf("tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_ip}', $conf['mysql']['ip'], $content);
		wf("$pam/smtp", $content);
		exec("chmod 660 $pam/smtp");
		exec("chown daemon:daemon $pam/smtp");
	
	}
	
	public function configure_courier()
    {
		global $conf;
		$config_dir = $conf['courier']['config_dir'];
		//* authmysqlrc
		$configfile = 'authmysqlrc';
		if(is_file("$config_dir/$configfile")){
            copy("$config_dir/$configfile", "$config_dir/$configfile~");
        }
		exec("chmod 400 $config_dir/$configfile~");
		$content = rf("tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf['mysql']['ispconfig_user'],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}',$conf['mysql']['database'],$content);
		$content = str_replace('{mysql_server_host}',$conf['mysql']['host'],$content);
		wf("$config_dir/$configfile", $content);
		
		exec("chmod 660 $config_dir/$configfile");
		exec("chown daemon:daemon $config_dir/$configfile");
		
		//* authdaemonrc
		$configfile = $conf['courier']['config_dir'].'/authdaemonrc';
		if(is_file($configfile)){
            copy($configfile, $configfile.'~');
        }
		if(is_file($configfile.'~')){
            exec('chmod 400 '.$configfile.'~');
        }
		$content = rf($configfile);
		$content = str_replace('authmodulelist="authpam"', 'authmodulelist="authmysql"', $content);
		wf($configfile, $content);
	}
	
	public function configure_amavis() {
		global $conf;
		
		// amavisd user config file
		$configfile = 'amavisd_user_config';
		if(is_file($conf["amavis"]["config_dir"].'/conf.d/50-user')) copy($conf["amavis"]["config_dir"].'/conf.d/50-user',$conf["courier"]["config_dir"].'/50-user~');
		if(is_file($conf["amavis"]["config_dir"].'/conf.d/50-user~')) exec('chmod 400 '.$conf["amavis"]["config_dir"].'/conf.d/50-user~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf['mysql']['ispconfig_user'],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}',$conf['mysql']['database'],$content);
		$content = str_replace('{mysql_server_port}',$conf["mysql"]["port"],$content);
		$content = str_replace('{mysql_server_ip}',$conf['mysql']['ip'],$content);
		wf($conf["amavis"]["config_dir"].'/conf.d/50-user',$content);
		
		// TODO: chmod and chown on the config file
		
		
		// Adding the amavisd commands to the postfix configuration
		$postconf_commands = array (
			'content_filter = amavis:[127.0.0.1]:10024',
			'receive_override_options = no_address_mappings'
		);
		
		// Make a backup copy of the main.cf file
		copy($conf["postfix"]["config_dir"].'/main.cf',$conf["postfix"]["config_dir"].'/main.cf~2');
		
		// Executing the postconf commands
		foreach($postconf_commands as $cmd) {
			$command = "postconf -e '$cmd'";
			caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}
		
		// Append the configuration for amavisd to the master.cf file
		if(is_file($conf["postfix"]["config_dir"].'/master.cf')) copy($conf["postfix"]["config_dir"].'/master.cf',$conf["postfix"]["config_dir"].'/master.cf~');
		$content = rf($conf["postfix"]["config_dir"].'/master.cf');
		// Only add the content if we had not addded it before
		if(!stristr($content,"127.0.0.1:10025")) {
			unset($content);
			$content = rf("tpl/master_cf_amavis.master");
			af($conf["postfix"]["config_dir"].'/master.cf',$content);
		}
		unset($content);
		
		// Add the clamav user to the amavis group
		exec('adduser clamav amavis');
		
		
	}
	
	public function configure_spamassassin()
    {
		global $conf;
		
		//* Enable spamasasssin on debian and ubuntu
		$configfile = '/etc/default/spamassassin';
		if(is_file($configfile)){
            copy($configfile, $configfile.'~');
        }
		$content = rf($configfile);
		$content = str_replace('ENABLED=0', 'ENABLED=1', $content);
		wf($configfile, $content);
	}
	
	public function configure_getmail()
    {
		global $conf;
		
		$config_dir = $conf['getmail']['config_dir'];
		
		if(!is_dir($config_dir)) exec("mkdir -p ".escapeshellcmd($config_dir));

		$command = "useradd -d $config_dir getmail";
		if(!is_user('getmail')) caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		$command = "chown -R getmail $config_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		$command = "chmod -R 700 $config_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
	}
	
	
	public function configure_pureftpd()
    {
		global $conf;
		
		$config_dir = $conf['pureftpd']['config_dir'];

		//* configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'db/mysql.conf';
		if(is_file("$config_dir/$configfile")){
            copy("$config_dir/$configfile", "$config_dir/$configfile~");
        }
		if(is_file("$config_dir/$configfile~")){
            exec("chmod 400 $config_dir/$configfile~");
        }
		$content = rf('tpl/pureftpd_mysql.conf.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $conf["mysql"]["ispconfig_user"], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $conf["mysql"]["ispconfig_password"], $content);
		$content = str_replace('{mysql_server_database}', $conf["mysql"]["database"], $content);
		$content = str_replace('{mysql_server_ip}', $conf["mysql"]["ip"], $content);
		$content = str_replace('{server_id}', $conf["server_id"], $content);
		wf("$config_dir/$configfile", $content);
		exec("chmod 600 $config_dir/$configfile");
		exec("chown root:root $config_dir/$configfile");
		// **enable chrooting
		//exec('mkdir -p '.$config_dir.'/conf/ChrootEveryone');
		exec('echo "yes" > '.$config_dir.'/conf/ChrootEveryone');
	}
	
	public function configure_mydns()
    {
		global $conf;
		
		// configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'mydns.conf';
		if(is_file($conf["mydns"]["config_dir"].'/'.$configfile)) copy($conf["mydns"]["config_dir"].'/'.$configfile,$conf["mydns"]["config_dir"].'/'.$configfile.'~');
		if(is_file($conf["mydns"]["config_dir"].'/'.$configfile.'~')) exec('chmod 400 '.$conf["mydns"]["config_dir"].'/'.$configfile.'~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$conf['mysql']['ispconfig_user'],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}',$conf['mysql']['database'],$content);
		$content = str_replace('{mysql_server_host}',$conf["mysql"]["host"],$content);
		$content = str_replace('{server_id}',$conf["server_id"],$content);
		wf($conf["mydns"]["config_dir"].'/'.$configfile,$content);
		exec('chmod 600 '.$conf["mydns"]["config_dir"].'/'.$configfile);
		exec('chown root:root '.$conf["mydns"]["config_dir"].'/'.$configfile);
	
	}
	
	public function configure_apache()
    {	
		global $conf;
		
		//* Create the logging directory for the vhost logfiles
		exec('mkdir -p /var/log/ispconfig/httpd');
		
		if(is_file('/etc/suphp/suphp.conf')) {
			replaceLine('/etc/suphp/suphp.conf','php=php:/usr/bin','x-httpd-suphp=php:/usr/bin/php-cgi',0);
			replaceLine('/etc/suphp/suphp.conf','docroot=','docroot=/var/clients',0);
		}
		
	}
	
	public function configure_firewall()
	{
		global $conf;
		
		$dist_init_scripts = $conf['init_scripts'];
  		
		if(is_dir("/etc/Bastille.backup")) caselog("rm -rf /etc/Bastille.backup", __FILE__, __LINE__);
		if(is_dir("/etc/Bastille")) caselog("mv -f /etc/Bastille /etc/Bastille.backup", __FILE__, __LINE__);
  		@mkdir("/etc/Bastille", octdec($directory_mode));
  		if(is_dir("/etc/Bastille.backup/firewall.d")) caselog("cp -pfr /etc/Bastille.backup/firewall.d /etc/Bastille/", __FILE__, __LINE__);
  		caselog("cp -f tpl/bastille-firewall.cfg.master /etc/Bastille/bastille-firewall.cfg", __FILE__, __LINE__);
  		caselog("chmod 644 /etc/Bastille/bastille-firewall.cfg", __FILE__, __LINE__);
  		$content = rf("/etc/Bastille/bastille-firewall.cfg");
  		$content = str_replace("{DNS_SERVERS}", "", $content);

  		$tcp_public_services = '';
  		$udp_public_services = '';
		
		$row = $this->db->queryOneRecord("SELECT * FROM firewall WHERE server_id = ".intval($conf['server_id']));
		
  		if(trim($row["tcp_port"]) != '' || trim($row["udp_port"]) != ''){
    		$tcp_public_services = trim(str_replace(',',' ',$row["tcp_port"]));
    		$udp_public_services = trim(str_replace(',',' ',$row["udp_port"]));
  		} else {
    		$tcp_public_services = '21 22 25 53 80 110 443 3306 8080 10000';
    		$udp_public_services = '53';
  		}
		
		if(!stristr($tcp_public_services, $conf['apache']['vhost_port'])) {
			$tcp_public_services .= ' '.intval($conf['apache']['vhost_port']);
			if($row["tcp_port"]) != '') $this->db->query("UPDATE firewall SET tcp_port = tcp_port + ',".intval($conf['apache']['vhost_port'])."' WHERE server_id = ".intval($conf['server_id']));
		}
		
  		$content = str_replace("{TCP_PUBLIC_SERVICES}", $tcp_public_services, $content);
  		$content = str_replace("{UDP_PUBLIC_SERVICES}", $udp_public_services, $content);

  		wf("/etc/Bastille/bastille-firewall.cfg", $content);

  		if(is_file($dist_init_scripts."/bastille-firewall")) caselog("mv -f $dist_init_scripts/bastille-firewall $dist_init_scripts/bastille-firewall.backup", __FILE__, __LINE__);
  		caselog("cp -f apps/bastille-firewall $dist_init_scripts", __FILE__, __LINE__);
  		caselog("chmod 700 $dist_init_scripts/bastille-firewall", __FILE__, __LINE__);

  		if(is_file("/sbin/bastille-ipchains")) caselog("mv -f /sbin/bastille-ipchains /sbin/bastille-ipchains.backup", __FILE__, __LINE__);
  		caselog("cp -f apps/bastille-ipchains /sbin", __FILE__, __LINE__);
  		caselog("chmod 700 /sbin/bastille-ipchains", __FILE__, __LINE__);

  		if(is_file("/sbin/bastille-netfilter")) caselog("mv -f /sbin/bastille-netfilter /sbin/bastille-netfilter.backup", __FILE__, __LINE__);
  		caselog("cp -f apps/bastille-netfilter /sbin", __FILE__, __LINE__);
  		caselog("chmod 700 /sbin/bastille-netfilter", __FILE__, __LINE__);
		
		if(!@is_dir('/var/lock/subsys')) caselog("mkdir /var/lock/subsys", __FILE__, __LINE__);

  		exec("which ipchains &> /dev/null", $ipchains_location, $ret_val);
  		if(!is_file("/sbin/ipchains") && !is_link("/sbin/ipchains") && $ret_val == 0) phpcaselog(@symlink(shell_exec("which ipchains"), "/sbin/ipchains"), 'create symlink', __FILE__, __LINE__);
  		unset($ipchains_location);
  		exec("which iptables &> /dev/null", $iptables_location, $ret_val);
  		if(!is_file("/sbin/iptables") && !is_link("/sbin/iptables") && $ret_val == 0) phpcaselog(@symlink(trim(shell_exec("which iptables")), "/sbin/iptables"), 'create symlink', __FILE__, __LINE__);
  		unset($iptables_location);

	}
	
	
	public function install_ispconfig()
    {
		global $conf;
		
		$install_dir = $conf['ispconfig_install_dir'];

		//* Create the ISPConfig installation directory
		if(!@is_dir("$install_dir")) {
			$command = "mkdir $install_dir";
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}
		
		//* Create a ISPConfig user and group
		$command = 'groupadd ispconfig';
		if(!is_group('ispconfig')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		$command = "useradd -g ispconfig -d $install_dir ispconfig";
		if(!is_user('ispconfig')) caselog($command.' &> /dev/null 2> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* copy the ISPConfig interface part
		$command = "cp -rf ../interface $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* copy the ISPConfig server part
		$command = "cp -rf ../server $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Create a symlink, so ISPConfig is accessible via web
		// Replaced by a separate vhost definition for port 8080
		// $command = "ln -s $install_dir/interface/web/ /var/www/ispconfig";
		// caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Create the config file for ISPConfig interface
		$configfile = 'config.inc.php';
		if(is_file($install_dir.'/interface/lib/'.$configfile)){
            copy("$install_dir/interface/lib/$configfile", "$install_dir/interface/lib/$configfile~");
        }
		$content = rf("tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}',$conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $conf['mysql']['host'], $content);
		
		$content = str_replace('{mysql_master_server_ispconfig_user}', $conf['mysql']['master_ispconfig_user'], $content);
		$content = str_replace('{mysql_master_server_ispconfig_password}', $conf['mysql']['master_ispconfig_password'], $content);
		$content = str_replace('{mysql_master_server_database}', $conf['mysql']['master_database'], $content);
		$content = str_replace('{mysql_master_server_host}', $conf['mysql']['master_host'], $content);
		
		$content = str_replace('{ispconfig_log_priority}', $conf['ispconfig_log_priority'], $content);
		wf("$install_dir/interface/lib/$configfile", $content);
		
		//* Create the config file for ISPConfig server
		$configfile = 'config.inc.php';
		if(is_file($install_dir.'/server/lib/'.$configfile)){
            copy("$install_dir/server/lib/$configfile", "$install_dir/interface/lib/$configfile~");
        }
		$content = rf("tpl/$configfile.master");
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
		wf("$install_dir/server/lib/$configfile", $content);
		
		
		//* Enable the server modules and plugins.
		// TODO: Implement a selector which modules and plugins shall be enabled.
		$dir = $install_dir.'/server/mods-available/';
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..' && substr($file,-8,8) == '.inc.php') {
						include_once($install_dir.'/server/mods-available/'.$file);
						$module_name = substr($file,0,-8);
						$tmp = new $module_name;
						if($tmp->onInstall()) {
							if(!@is_link($install_dir.'/server/mods-enabled/'.$file)) @symlink($install_dir.'/server/mods-available/'.$file, $install_dir.'/server/mods-enabled/'.$file);
							if (strpos($file, '_core_module') !== false) {
								if(!@is_link($install_dir.'/server/mods-core/'.$file)) @symlink($install_dir.'/server/mods-available/'.$file, $install_dir.'/server/mods-core/'.$file);
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
					if($file != '.' && $file != '..' && substr($file,-8,8) == '.inc.php') {
						include_once($install_dir.'/server/plugins-available/'.$file);
						$plugin_name = substr($file,0,-8);
						$tmp = new $plugin_name;
						if($tmp->onInstall()) {
							if(!@is_link($install_dir.'/server/plugins-enabled/'.$file)) @symlink($install_dir.'/server/plugins-available/'.$file, $install_dir.'/server/plugins-enabled/'.$file);
							if (strpos($file, '_core_plugin') !== false) {
								if(!@is_link($install_dir.'/server/plugins-core/'.$file)) @symlink($install_dir.'/server/plugins-available/'.$file, $install_dir.'/server/plugins-core/'.$file);
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
		$vserver_server_enabled = ($conf['services']['vserver'])?1:0;
		$sql = "UPDATE `server` SET mail_server = '$mail_server_enabled', web_server = '$web_server_enabled', dns_server = '$dns_server_enabled', file_server = '$file_server_enabled', db_server = '$db_server_enabled', vserver_server = '$vserver_server_enabled' WHERE server_id = ".intval($conf['server_id']);
		
		if($conf['mysql']['master_slave_setup'] == 'y') {
			$this->dbmaster->query($sql);
			$this->db->query($sql);
		} else {
			$this->db->query($sql);
		}
		
		
		//* Chmod the files
		$command = "chmod -R 750 $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* chown the files to the ispconfig user and group
		$command = "chown -R ispconfig:ispconfig $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Make the global language file directory group writable
		exec("chmod -R 770 $install_dir/interface/lib/lang");
		
		//* Make the temp directory for language file exports writable
		exec("chmod -R 770 $install_dir/interface/web/temp");
		
		//* Make all interface language file directories group writable
		$handle = @opendir($install_dir.'/interface/web');
		while ($file = @readdir ($handle)) { 
	   		if ($file != '.' && $file != '..') {
	        	if(@is_dir($install_dir.'/interface/web'.'/'.$file.'/lib/lang')) {
					$handle2 = opendir($install_dir.'/interface/web'.'/'.$file.'/lib/lang');
					chmod($install_dir.'/interface/web'.'/'.$file.'/lib/lang',0770);
					while ($lang_file = @readdir ($handle2)) {
						if ($lang_file != '.' && $lang_file != '..') {
							chmod($install_dir.'/interface/web'.'/'.$file.'/lib/lang/'.$lang_file,0770);
						}
					}
				}
			}
		}
		
		//* make sure that the server config file (not the interface one) is only readable by the root user
		exec("chmod 600 $install_dir/server/lib/$configfile");
		exec("chown root:root $install_dir/server/lib/$configfile");
		if(@is_file("$install_dir/server/lib/mysql_clientdb.conf")) {
			exec("chmod 600 $install_dir/server/lib/mysql_clientdb.conf");
			exec("chown root:root $install_dir/server/lib/mysql_clientdb.conf");
		}
		
		// TODO: FIXME: add the www-data user to the ispconfig group. This is just for testing
		// and must be fixed as this will allow the apache user to read the ispconfig files.
		// Later this must run as own apache server or via suexec!
		$command = 'adduser www-data ispconfig';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Make the shell scripts executable
		$command = "chmod +x $install_dir/server/scripts/*.sh";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Copy the ISPConfig vhost for the controlpanel
        $vhost_conf_dir = $conf['apache']['vhost_conf_dir'];
        $vhost_conf_enabled_dir = $conf['apache']['vhost_conf_enabled_dir'];
        
        
        // Dont just copy over the virtualhost template but add some custom settings
        $content = rf("tpl/apache_ispconfig.vhost.master");
		$content = str_replace('{vhost_port}', $conf['apache']['vhost_port'], $content);
		
		// comment out the listen directive if port is 80 or 443
		if($conf['apache']['vhost_port'] == 80 or $conf['apache']['vhost_port'] == 443) {
			$content = str_replace('{vhost_port_listen}', '#', $content);
		} else {
			$content = str_replace('{vhost_port_listen}', '', $content);
		}
		
		wf("$vhost_conf_dir/ispconfig.vhost", $content);
		
		//copy('tpl/apache_ispconfig.vhost.master', "$vhost_conf_dir/ispconfig.vhost");
		//* and create the symlink
		if($this->install_ispconfig_interface == true && $this->is_update == false) {
			if(@is_link("$vhost_conf_enabled_dir/ispconfig.vhost")) unlink("$vhost_conf_enabled_dir/ispconfig.vhost");
			if(!@is_link("$vhost_conf_enabled_dir/000-ispconfig.vhost")) {
				exec("ln -s $vhost_conf_dir/ispconfig.vhost $vhost_conf_enabled_dir/000-ispconfig.vhost");
			}
		}
		
		// Make the Clamav log files readable by ISPConfig
		exec('chmod +r /var/log/clamav/clamav.log');
		exec('chmod +r /var/log/clamav/freshclam.log');
		
		//* Install the SVN update script
		exec('cp ../helper_scripts/update_from_svn.sh /usr/local/bin/ispconfig_update_from_svn.sh');
		exec('chown root /usr/local/bin/ispconfig_update_from_svn.sh');
		exec('chmod 700 /usr/local/bin/ispconfig_update_from_svn.sh');
		
		//set the fast cgi starter script to executable
		exec('chmod 755 '.$install_dir.'/interface/bin/php-fcgi');
		
		//* Make the logs readable for the ispconfig user
		if(@is_file('/var/log/mail.log')) exec('chmod +r /var/log/mail.log');
		if(@is_file('/var/log/mail.warn')) exec('chmod +r /var/log/mail.warn');
		if(@is_file('/var/log/mail.err')) exec('chmod +r /var/log/mail.err');
		if(@is_file('/var/log/messages')) exec('chmod +r /var/log/messages');
		
		//* Create the ispconfig log directory
		if(!is_dir('/var/log/ispconfig')) mkdir('/var/log/ispconfig');
		if(!is_file('/var/log/ispconfig/ispconfig.log')) exec('touch /var/log/ispconfig/ispconfig.log');
		
		
	}
	
	public function configure_dbserver()
	{
		global $conf;
		
		//* If this server shall act as database server for client DB's, we configure this here
		$install_dir = $conf['ispconfig_install_dir'];
		
		// Create a file with the database login details which 
		// are used to create the client databases.
		
		if(!is_dir("$install_dir/server/lib")) {
			$command = "mkdir $install_dir/server/lib";
			caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		}
		
		$content = rf("tpl/mysql_clientdb.conf.master");
		$content = str_replace('{username}',$conf['mysql']['admin_user'],$content);
		$content = str_replace('{password}',$conf['mysql']['admin_password'], $content);
		wf("$install_dir/server/lib/mysql_clientdb.conf",$content);
		exec('chmod 600 '."$install_dir/server/lib/mysql_clientdb.conf");
		exec('chown root:root '."$install_dir/server/lib/mysql_clientdb.conf");
		
	}
	
	public function install_crontab()
    {		
		global $conf;
		
		//* Root Crontab
		exec('crontab -u root -l > crontab.txt');
		$existing_root_cron_jobs = file('crontab.txt');
		
		// remove existing ispconfig cronjobs, in case the syntax has changed
		foreach($existing_root_cron_jobs as $key => $val) {
			if(stristr($val,'/usr/local/ispconfig')) unset($existing_root_cron_jobs[$key]);
		}
		
		$root_cron_jobs = array(
			'* * * * * /usr/local/ispconfig/server/server.sh > /dev/null',
			'30 00 * * * /usr/local/ispconfig/server/cron_daily.sh > /dev/null'
		);
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
		
			$cron_jobs = array('*/5 * * * * '.$cf['program'].' -g '.$cf['config_dir'].' -r '.$cf['config_dir'].'/*.conf > /dev/null');
		
			// remove existing ispconfig cronjobs, in case the syntax has changed
			foreach($cron_jobs as $key => $val) {
				if(stristr($val,$cf['program'])) unset($cron_jobs[$key]);
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
	}
	
}

?>