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


    public function __construct()
    {
        global $conf; //TODO: maybe $conf  should be passed to constructor
        $this->conf = $conf;
    }
	
    //: TODO  Implement the translation function and langauge files for the installer.
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
	
	/** Create the database for ISPConfig */ 
	public function configure_database()
    {
		global $conf;
		$cf = $conf['mysql']; // make $conf['mysql'] more accessible
		//** Create the database
		if(!$this->db->query('CREATE DATABASE IF NOT EXISTS '.$cf['database'])) {
			$this->error('Unable to create MySQL database: '.$cf['database'].'.');
		}
		
		//* Create the ISPConfig database user
        $query = 'GRANT SELECT, INSERT, UPDATE, DELETE ON '.$cf['database'].".* "
                ."TO '".$cf['ispconfig_user']."'@'".$cf['host']."' "
                ."IDENTIFIED BY '".$cf['ispconfig_password']."';";
		if(!$this->db->query($query)) {
			$this->error('Unable to create database user: '.$cf['ispconfig_user']);
		}
		
		//* Reload database privelages
		$this->db->query('FLUSH PRIVILEGES;');
		
		//* Set the database name in the DB library
		$this->db->dbName = $cf['database'];
		
		//* Load the database dump into the database, if database contains no tables
		$db_tables = $this->db->getTables();
		if(count($db_tables) > 0) {
			$this->error('Stopped: Database already contains some tables.');
		} else {
			if($cf['admin_password'] == '') {
				caselog("mysql -h '".$cf['host']."' -u '".$cf['admin_user']."' '".$cf['database']."' < 'sql/ispconfig3.sql' &> /dev/null", 
                        __FILE__, __LINE__, 'read in ispconfig3.sql', 'could not read in ispconfig3.sql');
			} else {
				caselog("mysql -h '".$cf['host']."' -u '".$cf['admin_user']."' -p'".$cf['admin_password']."' '".$cf['database']."' < 'sql/ispconfig3.sql' &> /dev/null", 
                        __FILE__, __LINE__, 'read in ispconfig3.sql', 'could not read in ispconfig3.sql');
			}
			$db_tables = $this->db->getTables();
			if(count($db_tables) == 0) {
				$this->error('Unable to load SQL-Dump into database table.');
			}
		}
	}
	

    //** writes postfix configuration files
    private function process_postfix_config($configfile)
    {
        $config_dir = $this->conf['postfix']['config_dir'].'/';
        $full_file_name = $config_dir.$configfile; 
        //* Backup exiting file
        if(is_file($full_file_name)){
            copy($full_file_name, $config_dir.$configfile.'~');
        }
        $content = rf('tpl/'.$configfile.'.master');
        $content = str_replace('{mysql_server_ispconfig_user}', $this->conf['mysql']['ispconfig_user'], $content);
        $content = str_replace('{mysql_server_ispconfig_password}', $this->conf['mysql']['ispconfig_password'], $content);
        $content = str_replace('{mysql_server_database}', $this->conf['mysql']['database'], $content);
        $content = str_replace('{mysql_server_ip}', $this->conf['mysql']['ip'], $content);
        $content = str_replace('{server_id}', $this->conf['server_id'], $content);
        wf($full_file_name, $content);
    }


	public function configure_postfix($options = '')
    {
        $cf = $this->conf['postfix'];
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
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		$command = 'useradd -g '.$cf['vmail_groupname'].' -u '.$cf['vmail_userid'].' '.$cf['vmail_username'].' -d '.$cf['vmail_mailbox_base'].' -m';
		caselog("$command &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");		

		$postconf_commands = array (
			'myhostname = '.$this->conf['hostname'],
			'mydestination = '.$this->conf['hostname'].', localhost, localhost.localdomain',
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
		
		// TODO: Change the master.cf file
		/*
		Add:
        maildrop  unix  -       n       n       -       -       pipe
        flags=R user=vmail argv=/usr/bin/maildrop -d ${recipient} ${extension} ${recipient} ${user} ${nexthop} ${sender}
		*/
		if(!stristr($options,'dont-create-certs')) {
			//* Create the SSL certificate
			$command = 'cd '.$config_dir.'; '
                      .'openssl req -new -outform PEM -out smtpd.cert -newkey rsa:2048 -nodes -keyout '
                      .'smtpd.key -keyform PEM -days 365 -x509';
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
		$command = 'mkdir '.$cf['vmail_mailbox_base'].'/mailfilters';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Chmod and chown the .mailfilter file
		$command = 'chown -R '.$cf['vmail_username'].':'.$cf['vmail_groupname'].' '.$cf['vmail_mailbox_base'].'/.mailfilter';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		$command = 'chmod -R 600 '.$cf['vmail_mailbox_base'].'/.mailfilter';
		caselog($command." &> /dev/null", __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
	}
	
	function configure_saslauthd() {
		global $conf;
		
	
		$configfile = 'sasl_smtpd.conf';
		if(is_file($conf["postfix"]["config_dir"].'/sasl/smtpd.conf')) copy($conf["postfix"]["config_dir"].'/sasl/smtpd.conf',$conf["postfix"]["config_dir"].'/sasl/smtpd.conf~');
		if(is_file($conf["postfix"]["config_dir"].'/sasl/smtpd.conf~')) exec('chmod 400 '.$conf["postfix"]["config_dir"].'/sasl/smtpd.conf~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$this->conf['mysql']['ispconfig_user'],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$this->conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}',$this->conf['mysql']['database'],$content);
		$content = str_replace('{mysql_server_ip}',$this->conf['mysql']['ip'],$content);
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
		$content = str_replace('OPTIONS="-c"','OPTIONS="-m /var/spool/postfix/var/run/saslauthd -r"',$content);
		wf($configfile,$content);
		
		// Edit the file /etc/init.d/saslauthd
		$configfile = $conf["init_scripts"].'/'.$conf["saslauthd"]["init_script"];
		$content = rf($configfile);
		$content = str_replace('PIDFILE=$RUN_DIR/saslauthd.pid','PIDFILE="/var/spool/postfix/var/run/${NAME}/saslauthd.pid"',$content);
		wf($configfile,$content);
		
		
	}
	
	public function configure_pam()
    {
		$pam = $this->conf['pam'];
		//* configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'pamd_smtp';
		if(is_file("$pam/smtp"))    copy("$pam/smtp", "$pam/smtp~");
		if(is_file("$pam/smtp~"))   exec("chmod 400 $pam/smtp~");

		$content = rf("tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $this->conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $this->conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $this->conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_ip}', $this->conf['mysql']['ip'], $content);
		wf("$pam/smtp", $content);
		exec("chmod 660 $pam/smtp");
		exec("chown daemon:daemon $pam/smtp");
	
	}
	
	public function configure_courier()
    {
		$config_dir = $this->conf['courier']['config_dir'];
		//* authmysqlrc
		$configfile = 'authmysqlrc';
		if(is_file("$config_dir/$configfile")){
            copy("$config_dir/$configfile", "$config_dir/$configfile~");
        }
		exec("chmod 400 $config_dir/$configfile~");
		$content = rf("tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}',$this->conf['mysql']['ispconfig_user'],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$this->conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}',$this->conf['mysql']['database'],$content);
		$content = str_replace('{mysql_server_host}',$this->conf['mysql']['host'],$content);
		wf("$config_dir/$configfile", $content);
		
		exec("chmod 660 $config_dir/$configfile");
		exec("chown daemon:daemon $config_dir/$configfile");
		
		//* authdaemonrc
		$configfile = $this->conf['courier']['config_dir'].'/authdaemonrc';
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
	
	function configure_amavis() {
		global $conf;
		
		// amavisd user config file
		$configfile = 'amavisd_user_config';
		if(is_file($conf["amavis"]["config_dir"].'/conf.d/50-user')) copy($conf["amavis"]["config_dir"].'/conf.d/50-user',$conf["courier"]["config_dir"].'/50-user~');
		if(is_file($conf["amavis"]["config_dir"].'/conf.d/50-user~')) exec('chmod 400 '.$conf["amavis"]["config_dir"].'/conf.d/50-user~');
		$content = rf("tpl/".$configfile.".master");
		$content = str_replace('{mysql_server_ispconfig_user}',$this->conf['mysql']['ispconfig_user'],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$this->conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}',$this->conf['mysql']['database'],$content);
		$content = str_replace('{mysql_server_port}',$conf["mysql"]["port"],$content);
		$content = str_replace('{mysql_server_ip}',$this->conf['mysql']['ip'],$content);
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
		$content = rf("tpl/master_cf_amavis.master");
		// Only add the content if we had not addded it before
		if(!stristr("127.0.0.1:10025 inet n - - - - smtpd",$content)) {
			af($conf["postfix"]["config_dir"].'/master.cf',$content);
		}
		
		// Add the clamav user to the amavis group
		exec('adduser clamav amavis');
		
		
	}
	
	public function configure_spamassassin()
    {
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
		$config_dir = $this->conf['getmail']['config_dir'];
		
		if(!is_dir($config_dir)) exec("mkdir -p ".escapeshellcmd($config_dir));

		$command = "useradd -d $config_dir getmail";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		$command = "chown -R getmail $config_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		$command = "chmod -R 700 $config_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
	}
	
	
	public function configure_pureftpd()
    {
		$config_dir = $this->conf['pureftpd']['config_dir'];

		//* configure pam for SMTP authentication agains the ispconfig database
		$configfile = 'db/mysql.conf';
		if(is_file("$config_dir/$configfile")){
            copy("$config_dir/$configfile", "$config_dir/$configfile~");
        }
		if(is_file("$config_dir/$configfile~")){
            exec("chmod 400 $config_dir/$configfile~");
        }
		$content = rf('tpl/pureftpd_mysql.conf.master');
		$content = str_replace('{mysql_server_ispconfig_user}', $this->conf["mysql"]["ispconfig_user"], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $this->conf["mysql"]["ispconfig_password"], $content);
		$content = str_replace('{mysql_server_database}', $this->conf["mysql"]["database"], $content);
		$content = str_replace('{mysql_server_ip}', $this->conf["mysql"]["ip"], $content);
		$content = str_replace('{server_id}', $this->conf["server_id"], $content);
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
		$content = str_replace('{mysql_server_ispconfig_user}',$this->conf['mysql']['ispconfig_user'],$content);
		$content = str_replace('{mysql_server_ispconfig_password}',$this->conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}',$this->conf['mysql']['database'],$content);
		$content = str_replace('{mysql_server_host}',$conf["mysql"]["host"],$content);
		$content = str_replace('{server_id}',$conf["server_id"],$content);
		wf($conf["mydns"]["config_dir"].'/'.$configfile,$content);
		exec('chmod 600 '.$conf["mydns"]["config_dir"].'/'.$configfile);
		exec('chown root:root '.$conf["mydns"]["config_dir"].'/'.$configfile);
	
	}
	
	public function configure_apache()
    {	
		//* Create the logging directory for the vhost logfiles
		exec('mkdir -p /var/log/ispconfig/httpd');
		
	}
	
	
	public function install_ispconfig()
    {
		$install_dir = $this->conf['ispconfig_install_dir'];

		//* Create the ISPConfig installation directory
		$command = "mkdir $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Create a ISPConfig user and group
		$command = 'groupadd ispconfig';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		$command = "useradd -g ispconfig -d $install_dir ispconfig";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* copy the ISPConfig interface part
		$command = "cp -rf ../interface $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* copy the ISPConfig server part
		$command = "cp -rf ../server $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Create a symlink, so ISPConfig is accessible via web
		$command = "ln -s $install_dir/interface/web/ /var/www/ispconfig";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Create the config file for ISPConfig interface
		$configfile = 'config.inc.php';
		if(is_file($install_dir.'/interface/lib/'.$configfile)){
            copy("$install_dir/interface/lib/$configfile", "$install_dir/interface/lib/$configfile~");
        }
		$content = rf("tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $this->conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}',$this->conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $this->conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $this->conf['mysql']['host'], $content);
		wf("$install_dir/interface/lib/$configfile", $content);
		
		//* Create the config file for ISPConfig server
		$configfile = 'config.inc.php';
		if(is_file($install_dir.'/server/lib/'.$configfile)){
            copy("$install_dir/server/lib/$configfile", "$install_dir/interface/lib/$configfile~");
        }
		$content = rf("tpl/$configfile.master");
		$content = str_replace('{mysql_server_ispconfig_user}', $this->conf['mysql']['ispconfig_user'], $content);
		$content = str_replace('{mysql_server_ispconfig_password}', $this->conf['mysql']['ispconfig_password'], $content);
		$content = str_replace('{mysql_server_database}', $this->conf['mysql']['database'], $content);
		$content = str_replace('{mysql_server_host}', $this->conf['mysql']['host'], $content);
		$content = str_replace('{server_id}', $this->conf['server_id'], $content);
		wf("$install_dir/server/lib/$configfile", $content);
		
		//* Enable the server modules and plugins.
		// TODO: Implement a selector which modules and plugins shall be enabled.
		$dir = $install_dir.'/server/mods-available/';
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..') {
						symlink($install_dir.'/server/mods-available/'.$file, $install_dir.'/server/mods-enabled/'.$file);
					}
				}
				closedir($dh);
			}
		}
		
		$dir = $install_dir.'/server/plugins-available/';
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..') {
						symlink($install_dir.'/server/plugins-available/'.$file, $install_dir.'/server/plugins-enabled/'.$file);
					}
				}
				closedir($dh);
			}
		}
		
		//* Chmod the files
		$command = "chmod -R 750 $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");

		//* chown the files to the ispconfig user and group
		$command = "chown -R ispconfig:ispconfig $install_dir";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* make sure that the server config file (not the interface one) is only readable by the root user
		exec("chmod 600 $install_dir/server/lib/$configfile");
		exec("chown root:root $install_dir/server/lib/$configfile");
		
		// TODO: FIXME: add the www-data user to the ispconfig group. This is just for testing
		// and must be fixed as this will allow the apache user to read the ispconfig files.
		// Later this must run as own apache server or via suexec!
		$command = 'adduser www-data ispconfig';
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Make the shell scripts executable
		$command = "chmod +x $install_dir/server/scripts/*.sh";
		caselog($command.' &> /dev/null', __FILE__, __LINE__, "EXECUTED: $command", "Failed to execute the command $command");
		
		//* Copy the ISPConfig vhost for the controlpanel
        // TODO: These are missing! should they be "vhost_dist_*_dir" ?
        $vhost_conf_dir = $this->conf['apache']['vhost_conf_dir'];
        $vhost_conf_enabled_dir = $this->conf['apache']['vhost_conf_enabled_dir'];
		copy('tpl/apache_ispconfig.vhost.master', "$vhost_conf_dir/ispconfig.vhost");
		//* and create the symlink
		if(!is_link("$vhost_conf_enabled_dir/ispconfig.vhost")) {
			exec("ln -s $vhost_conf_dir/ispconfig.vhost $vhost_conf_enabled_dir/ispconfig.vhost");
		}
	}
	
	public function install_crontab()
    {		
		//* Root Crontab
		exec('crontab -u root -l > crontab.txt');
		$existing_root_cron_jobs = file('crontab.txt');
		
		$root_cron_jobs = array(
			'* * * * * /usr/local/ispconfig/server/server.sh &> /dev/null',
			'30 00 * * * /usr/local/ispconfig/server/cron_daily.sh &> /dev/null'
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
        $cf = $this->conf['getmail'];
		exec('crontab -u getmail -l > crontab.txt');
		$existing_cron_jobs = file('crontab.txt');
		
		$cron_jobs = array('*/5 * * * * '.$cf['program'].' -g '.$cf['config_dir'].' -r '.$cf['config_dir'].'/*.conf &> /dev/null');
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

?>