<?php

//***  Fedora 9 default settings

//* Main
$conf['language'] = 'en';
$conf['distname'] = 'centos52';
$conf['hostname'] = 'server1.domain.tld'; // Full hostname
$conf['ispconfig_install_dir'] = '/usr/local/ispconfig';
$conf['ispconfig_config_dir'] = '/usr/local/ispconfig';
$conf['ispconfig_log_priority'] = 2;  // 0 = Debug, 1 = Warning, 2 = Error
$conf['server_id'] = 1;
$conf['init_scripts'] = '/etc/init.d';
$conf['runlevel'] = '/etc';
$conf['shells'] = '/etc/shells';
$conf['cron_tab'] = '/var/spool/cron/root';
$conf['pam'] = '/etc/pam.d';

//* MySQL
$conf['mysql']['init_script'] = 'mysqld';
$conf['mysql']['host'] = 'localhost';
$conf['mysql']['ip'] = '127.0.0.1';
$conf['mysql']['port'] = '3306';
$conf['mysql']['database'] = 'dbispconfig';
$conf['mysql']['admin_user'] = 'root';
$conf['mysql']['admin_password'] = '';
$conf['mysql']['charset'] = 'utf8';
$conf['mysql']['ispconfig_user'] = 'ispconfig';
$conf['mysql']['ispconfig_password'] = md5 (uniqid (rand()));

//* Apache
$conf['apache']['user'] = 'apache';
$conf['apache']['group'] = 'apache';
$conf['apache']['init_script'] = 'httpd';
$conf['apache']['version'] = '2.2';
$conf['apache']['vhost_conf_dir'] = '/etc/httpd/conf/sites-available';
$conf['apache']['vhost_conf_enabled_dir'] = '/etc/httpd/conf/sites-enabled';
$conf['apache']['vhost_port'] = '8080';

//* Postfix
$conf['postfix']['config_dir'] = '/etc/postfix';
$conf['postfix']['init_script'] = 'postfix';
$conf['postfix']['user'] = 'postfix';
$conf['postfix']['group'] = 'postfix';
$conf['postfix']['vmail_userid'] = '5000';
$conf['postfix']['vmail_username'] = 'vmail';
$conf['postfix']['vmail_groupid'] = '5000';
$conf['postfix']['vmail_groupname'] = 'vmail';
$conf['postfix']['vmail_mailbox_base'] = '/home/vmail';

//* Getmail
$conf['getmail']['config_dir'] = '/etc/getmail';
$conf['getmail']['program'] = '/usr/bin/getmail';

//* Courier
$conf['courier']['config_dir'] = '/etc/authlib';
$conf['courier']['courier-authdaemon'] = 'courier-authlib';
$conf['courier']['courier-imap'] = 'courier-imap';
$conf['courier']['courier-imap-ssl'] = '';
$conf['courier']['courier-pop'] = '';
$conf['courier']['courier-pop-ssl'] = '';

//* SASL
$conf['saslauthd']['config'] = '/etc/sysconfig/saslauthd';
$conf['saslauthd']['init_script'] = 'saslauthd';

//* Amavisd
$conf['amavis']['config_dir'] = '/etc/amavisd';
$conf['amavis']['init_script'] = 'amavisd';

//* ClamAV
$conf['clamav']['init_script'] = 'clamd';

//* Pureftpd
$conf['pureftpd']['config_dir'] = '/etc/pure-ftpd';
$conf['pureftpd']['init_script'] = 'pure-ftpd';

//* MyDNS
$conf['mydns']['config_dir'] = '/etc';
$conf['mydns']['init_script'] = 'mydns';

//* Jailkit
$conf['jailkit']['config_dir'] = '/etc/jailkit';
$conf['jailkit']['jk_init'] = 'jk_init.ini';
$conf['jailkit']['jk_chrootsh'] = 'jk_chrootsh.ini';

?>