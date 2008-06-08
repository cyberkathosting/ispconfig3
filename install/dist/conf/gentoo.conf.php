<?php

//*** Gentoo default settings

//* Main
$dist['init_scripts'] = '/etc/init.d';
$dist['runlevel'] = '/etc';
$dist['shells'] = '/etc/shells';
$dist['cron_tab'] = '/var/spool/cron/crontabs/root';
$dist['pam'] = '/etc/pam.d';

//* MySQL
$dist['mysql']['init_script'] = 'mysql';

//* Apache
$dist['apache']['user'] = 'apache';
$dist['apache']['group'] = 'apache';
$dist['apache']['init_script'] = 'apache2';
$dist['apache']['version'] = '2.2';
$dist['apache']['vhost_dist_dir'] = '/etc/apache2/vhosts.d';
$dist['apache']['vhost_dist_enabled_dir'] = '/etc/apache2/vhosts.d';

//* Postfix
$dist['postfix']['config_dir'] = '/etc/postfix';
$dist['postfix']['init_script'] = 'postfix';
$dist['postfix']['user'] = 'postfix';
$dist['postfix']['group'] = 'postfix';
$dist['postfix']['vmail_userid'] = '5000';
$dist['postfix']['vmail_username'] = 'vmail';
$dist['postfix']['vmail_groupid'] = '5000';
$dist['postfix']['vmail_groupname'] = 'vmail';
$dist['postfix']['vmail_mailbox_base'] = '/home/vmail';

//* Getmail
$dist['getmail']['config_dir'] = '/etc/getmail';
$dist['getmail']['program'] = '/usr/bin/getmail';

//* Courier
$dist['courier']['config_dir'] = '/etc/courier';
$dist['courier']['courier-authdaemon'] = 'courier-authlib';
$dist['courier']['courier-imap'] = 'courier-imapd';
$dist['courier']['courier-imap-ssl'] = 'courier-imapd-ssl';
$dist['courier']['courier-pop'] = 'courier-pop3d';
$dist['courier']['courier-pop-ssl'] = 'courier-pop3d-ssl';

//* SASL
$dist['saslauthd']['config'] = '/etc/default/saslauthd';
$dist['saslauthd']['init_script'] = 'saslauthd';

//* Amavisd
$dist['amavis']['config_dir'] = '/etc/amavis';
$dist['amavis']['init_script'] = 'amavisd';

//* ClamAV
$dist['clamav']['init_script'] = 'clamd';

//* Pureftpd
$dist['pureftpd']['config_dir'] = '/etc/pure-ftpd';
$dist['pureftpd']['init_script'] = 'pure-ftpd';

//* MyDNS
$dist['mydns']['config_dir'] = '/etc';
$dist['mydns']['init_script'] = 'mydns';

//* Jailkit
$conf['jailkit']['config_dir'] = '/etc/jailkit';
$conf['jailkit']['jk_init'] = 'jk_init.ini';
$conf['jailkit']['jk_chrootsh'] = 'jk_chrootsh.ini';

?>