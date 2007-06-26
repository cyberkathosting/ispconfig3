<?php

$conf["language"] = "en";
$conf["distname"] = "debian40";
$conf["hostname"] = "server1.example.com"; // Full hostname
$conf["ispconfig_install_dir"] = "/usr/local/ispconfig";
$conf["ispconfig_config_dir"] = "/usr/local/ispconfig";
$conf["server_id"] = 1;

/*
	Distribution specific settings
*/

$conf["dist_init_scripts"] = "/etc/init.d";
$conf["dist_runlevel"] = "/etc";
$conf["dist_shells"] = "/etc/shells";
$conf["dist_cron_tab"] = "/var/spool/cron/crontabs/root";

// Apache
$conf["dist_apache_user"] = "www-data";
$conf["dist_apache_group"] = "www-data";
$conf["dist_apache_init_script"] = "apache2";
$conf["dist_apache_version"] = "2.2";
$conf["dist_apache_vhost_conf_dir"] = "/etc/apache2/sites-available";
$conf["dist_apache_vhost_conf_enabled_dir"] = "/etc/apache2/sites-enabled";

// Postfix
$conf["dist_postfix_config_dir"] = "/etc/postfix";
$conf["dist_postfix_username"] = "postfix";
$conf["dist_postfix_groupname"] = "postfix";
$conf["dist_postfix_vmail_userid"] = "5000";
$conf["dist_postfix_vmail_username"] = "vmail";
$conf["dist_postfix_vmail_groupid"] = "5000";
$conf["dist_postfix_vmail_groupname"] = "vmail";
$conf["dist_postfix_vmail_mailbox_base"] = "/home/vmail";

// Getmail
$conf["dist_getmail_config_dir"] = "/etc/getmail";
$conf["dist_getmail_program"] = "/usr/bin/getmail";

// Courier
$conf["dist_courier_config_dir"] = "/etc/courier";

// Amavisd
$conf["dist_amavis_config_dir"] = "/etc/amavis";

// Pureftpd
$conf["dist_pureftpd_config_dir"] = "/etc/pure-ftpd/db";

/*
	MySQL Database settings
*/
$conf["mysql_server_host"] = "localhost";
$conf["mysql_server_ip"] = "127.0.0.1";
$conf["mysql_server_port"] = "3306";
$conf["mysql_server_database"] = "dbispconfig";
$conf["mysql_server_admin_user"] = "root";
$conf["mysql_server_admin_password"] = "";
$conf["mysql_server_ispconfig_user"] = "ispconfig";
$conf["mysql_server_ispconfig_password"] = "5sDrewBhk";








?>