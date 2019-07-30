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

//*** Gentoo default settings

//* Main
$conf['language'] = 'en';
$conf['distname'] = 'gentoo';
$conf['hostname'] = 'server1.domain.tld'; // Full hostname
$conf['ispconfig_install_dir'] = '/usr/local/ispconfig';
$conf['ispconfig_config_dir'] = '/usr/local/ispconfig';
$conf['ispconfig_log_priority'] = 2;  // 0 = Debug, 1 = Warning, 2 = Error
$conf['ispconfig_log_dir'] = '/var/log/ispconfig';
$conf['server_id'] = 1;
$conf['init_scripts'] = '/etc/init.d';
$conf['runlevel'] = '/etc';
$conf['shells'] = '/etc/shells';
$conf['pam'] = '/etc/pam.d';

//* Services provided by this server, this selection will be overridden by the expert mode
$conf['services']['mail'] = true;
$conf['services']['web'] = true;
$conf['services']['dns'] = true;
$conf['services']['file'] = true;
$conf['services']['db'] = true;
$conf['services']['vserver'] = true;

//* MySQL
$conf['mysql']['installed'] = false; // will be detected automatically during installation
$conf['mysql']['init_script'] = 'mysql';
$conf['mysql']['host'] = 'localhost';
$conf['mysql']['ip'] = '127.0.0.1';
$conf['mysql']['port'] = '3306';
$conf['mysql']['database'] = 'dbispconfig';
$conf['mysql']['admin_user'] = 'root';
$conf['mysql']['admin_password'] = '';
$conf['mysql']['charset'] = 'utf8';
$conf['mysql']['ispconfig_user'] = 'ispconfig';
$conf['mysql']['ispconfig_password'] = md5(uniqid(rand()));
$conf['mysql']['master_slave_setup'] = 'n';
$conf['mysql']['master_host'] = '';
$conf['mysql']['master_database'] = 'dbispconfig';
$conf['mysql']['master_admin_user'] = 'root';
$conf['mysql']['master_admin_password'] = '';
$conf['mysql']['master_ispconfig_user'] = '';
$conf['mysql']['master_ispconfig_password'] = md5(uniqid(rand()));

//* SuPHP
$conf['suphp']['config_file'] = '/etc/suphp.conf';

//* Apache
$conf['apache']['installed'] = false; // will be detected automatically during installation
$conf['apache']['user'] = 'apache';
$conf['apache']['group'] = 'apache';
$conf['apache']['init_script'] = 'apache2';
$conf['apache']['version'] = '2.2';
$conf['apache']['config_dir'] = '/etc/apache2';
$conf['apache']['config_file'] = $conf['apache']['config_dir'] .'/httpd.conf';
$conf['apache']['ssl_dir'] = '/etc/ssl/apache2';
$conf['apache']['vhost_conf_dir'] = $conf['apache']['config_dir'] . '/vhosts.d';
$conf['apache']['vhost_conf_enabled_dir'] = $conf['apache']['vhost_conf_dir'];
$conf['apache']['vhost_default'] = '00_default_vhost.conf';
$conf['apache']['vhost_port'] = '8080';
$conf['apache']['php_ini_path_apache'] = '/etc/php/apache2-php5/php.ini';
$conf['apache']['php_ini_path_cgi'] = '/etc/php/cgi-php5/php.ini';

//* Website base settings
$conf['web']['website_basedir'] = '/var/www';
$conf['web']['website_path'] = '/var/www/clients/client[client_id]/web[website_id]';
$conf['web']['website_symlinks'] = '/var/www/[website_domain]/:/var/www/clients/client[client_id]/[website_domain]/';

//* Apps base settings
$conf['web']['apps_vhost_ip'] = '_default_';
$conf['web']['apps_vhost_port'] = '8081';
$conf['web']['apps_vhost_servername'] = '';
$conf['web']['apps_vhost_user'] = 'ispapps';
$conf['web']['apps_vhost_group'] = 'ispapps';

//* Awstats settings
$conf['awstats']['conf_dir'] = '/etc/awstats';
$conf['awstats']['data_dir'] = '/var/lib/awstats';
$conf['awstats']['pl'] = '/usr/bin/awstats.pl';
$conf['awstats']['buildstaticpages_pl'] = '/usr/bin/awstats_buildstaticpages.pl';

//* Fastcgi
$conf['fastcgi']['fastcgi_phpini_path'] = '/etc/php/cgi-php5';
$conf['fastcgi']['fastcgi_starter_path'] = '/var/www/php-fcgi-scripts/[system_user]/';
$conf['fastcgi']['fastcgi_bin'] = '/usr/bin/php-cgi';

//* Postfix
$conf['postfix']['installed'] = false; // will be detected automatically during installation
$conf['postfix']['config_dir'] = '/etc/postfix';
$conf['postfix']['init_script'] = 'postfix';
$conf['postfix']['user'] = 'postfix';
$conf['postfix']['group'] = 'postfix';
$conf['postfix']['vmail_userid'] = '5000';
$conf['postfix']['vmail_username'] = 'vmail';
$conf['postfix']['vmail_groupid'] = '5000';
$conf['postfix']['vmail_groupname'] = 'vmail';
$conf['postfix']['vmail_mailbox_base'] = '/var/vmail';

//* Mailman
$conf['mailman']['installed'] = false; // will be detected automatically during installation
$conf['mailman']['config_dir'] = '/etc/mailman';
$conf['mailman']['init_script'] = 'mailman';

//* Getmail
$conf['getmail']['installed'] = false; // will be detected automatically during installation
$conf['getmail']['user'] = 'getmail';
$conf['getmail']['config_dir'] = '/etc/getmail';
$conf['getmail']['program'] = '/usr/bin/getmail';

//* Courier
$conf['courier']['installed'] = false; // will be detected automatically during installation
$conf['courier']['config_dir'] = '/etc/courier/authlib';
$conf['courier']['courier-authdaemon'] = 'courier-authlib';
$conf['courier']['courier-imap'] = 'courier-imapd';
$conf['courier']['courier-imap-ssl'] = 'courier-imapd-ssl';
$conf['courier']['courier-pop'] = 'courier-pop3d';
$conf['courier']['courier-pop-ssl'] = 'courier-pop3d-ssl';

//* Dovecot
$conf['dovecot']['installed'] = false; // will be detected automatically during installation
$conf['dovecot']['config_dir'] = '/etc/dovecot';
$conf['dovecot']['init_script'] = 'dovecot';

//* SASL
$conf['saslauthd']['installed'] = false; // will be detected automatically during installation
$conf['saslauthd']['config_file'] = '/etc/conf.d/saslauthd';
$conf['saslauthd']['config_dir'] = '/etc/sasl2';
$conf['saslauthd']['init_script'] = 'saslauthd';

//* Amavisd
$conf['amavis']['installed'] = false; // will be detected automatically during installation
$conf['amavis']['config_file'] = '/etc/amavisd.conf';
$conf['amavis']['init_script'] = 'amavisd';

//* Rspamd
$conf['rspamd']['installed'] = false; // will be detected automatically during installation
$conf['rspamd']['config_dir'] = '/etc/rspamd';
$conf['rspamd']['init_script'] = 'rspamd';

//* ClamAV
$conf['clamav']['installed'] = false; // will be detected automatically during installation
$conf['clamav']['init_script'] = 'clamd';

//* Pureftpd
$conf['pureftpd']['installed'] = false; // will be detected automatically during installation
$conf['pureftpd']['config_file'] = '/etc/conf.d/pure-ftpd';
$conf['pureftpd']['mysql_config_file'] = '/etc/pureftpd-mysql.conf';
$conf['pureftpd']['init_script'] = 'pure-ftpd';

//* MyDNS
$conf['mydns']['installed'] = false; // will be detected automatically during installation
$conf['mydns']['config_dir'] = '/etc';
$conf['mydns']['init_script'] = 'mydns';

//* PowerDNS
$conf['powerdns']['installed'] = false; // will be detected automatically during installation
$conf['powerdns']['database'] = 'powerdns';
$conf["powerdns"]["config_dir"] = '/etc/powerdns';
$conf["powerdns"]["config_file"] = 'pdns-local.conf';
$conf['powerdns']['init_script'] = 'pdns.local';

//* BIND DNS Server
$conf['bind']['installed'] = false; // will be detected automatically during installation
$conf['bind']['bind_user'] = 'root';
$conf['bind']['bind_group'] = 'named';
$conf['bind']['bind_zonefiles_dir'] = '/etc/bind';
$conf['bind']['named_conf_path'] = '/etc/bind/named.conf';
$conf['bind']['named_conf_local_path'] = '/etc/bind/named.conf.local';
$conf['bind']['init_script'] = 'named';

//* Jailkit
$conf['jailkit']['installed'] = false; // will be detected automatically during installation
$conf['jailkit']['config_dir'] = '/etc/jailkit';
$conf['jailkit']['jk_init'] = 'jk_init.ini';
$conf['jailkit']['jk_chrootsh'] = 'jk_chrootsh.ini';
$conf['jailkit']['jailkit_chroot_app_programs'] = '/bin/groups /usr/bin/id /usr/bin/dircolors /usr/bin/less /usr/bin/basename /usr/bin/dirname /usr/bin/nano /usr/bin/vim';

//* Squid
$conf['squid']['installed'] = false; // will be detected automatically during installation
$conf['squid']['config_dir'] = '/etc/squid';
$conf['squid']['init_script'] = 'squid';

//* Nginx
$conf['nginx']['installed'] = false; // will be detected automatically during installation
$conf['nginx']['user'] = 'nginx';
$conf['nginx']['group'] = 'nginx';
$conf['nginx']['config_dir'] = '/etc/nginx';
$conf['nginx']['vhost_conf_dir'] = '/etc/nginx/sites-available';
$conf['nginx']['vhost_conf_enabled_dir'] = '/etc/nginx/sites-enabled';
$conf['nginx']['init_script'] = 'nginx';
$conf['nginx']['vhost_port'] = '8080';
$conf['nginx']['cgi_socket'] = '/var/run/fcgiwrap.socket';
$conf['nginx']['php_fpm_init_script'] = 'php5-fpm';
$conf['nginx']['php_fpm_ini_path'] = '/etc/php5/fpm/php.ini';
$conf['nginx']['php_fpm_pool_dir'] = '/etc/php5/fpm/pool.d';
$conf['nginx']['php_fpm_start_port'] = 9010;
$conf['nginx']['php_fpm_socket_dir'] = '/var/lib/php5-fpm';

//* vlogger
$conf['vlogger']['config_dir'] = '/etc/vlogger';

//* cron
$conf['cron']['init_script'] = 'vixie-cron';
$conf['cron']['crontab_dir'] = '/etc/cron.d';
$conf['cron']['group'] = 'cron';
$conf['cron']['wget'] = '/usr/bin/wget';

//* OpenVZ
$conf['openvz']['installed'] = false;

?>
