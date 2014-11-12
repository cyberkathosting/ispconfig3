<?php
$autoinstall['language'] = 'en'; // de, en (default)
$autoinstall['install_mode'] = 'standard'; // standard (default), expert

$autoinstall['hostname'] = 'server1.example.com'; // default
$autoinstall['mysql_hostname'] = 'localhost'; // default: localhost
$autoinstall['mysql_root_user'] = 'root'; // default: root
$autoinstall['mysql_root_password'] = 'howtoforge';
$autoinstall['mysql_database'] = 'dbispconfig'; // default: dbispcongig
$autoinstall['mysql_charset'] = 'utf8'; // default: utf8
$autoinstall['http_server'] = 'nginx'; // apache (default), nginx
$autoinstall['ispconfig_port'] = '8080'; // default: 8080
$autoinstall['ispconfig_use_ssl'] = 'y'; // y (default), n

/* SSL Settings */
$autoinstall['ssl_cert_country'] = 'AU';
$autoinstall['ssl_cert_state'] = 'Some-State';
$autoinstall['ssl_cert_locality'] = 'Chicago';
$autoinstall['ssl_cert_organisation'] = 'Internet Widgits Pty Ltd';
$autoinstall['ssl_cert_organisation_unit'] = 'IT department';
$autoinstall['ssl_cert_common_name'] = $autoinstall['hostname'];

/* optional expert mode settings, needed only for expert mode */
$autoinstall['mysql_ispconfig_user'] = 'ispconfig'; // default: ispconfig
$autoinstall['mysql_ispconfig_password'] = md5(uniqid(rand()));
$autoinstall['join_multiserver_setup'] = 'n'; // y, n (default)
$autoinstall['mysql_master_hostname'] = 'master.example.com';
$autoinstall['mysql_master_root_user'] = 'root';
$autoinstall['mysql_master_root_password'] = 'howtoforge';
$autoinstall['mysql_master_database'] = 'dbispconfig'; // default: dbispconfig
$autoinstall['configure_mail'] = 'y'; // y (default), n
$autoinstall['configure_jailkit'] = 'y'; // y (default), n
$autoinstall['configure_ftp'] = 'y'; // y (default), n
$autoinstall['configure_dns'] = 'y'; // y (default), n
$autoinstall['configure_apache'] = 'y'; // y (default), n
$autoinstall['configure_nginx'] = 'y'; // y (default), n
$autoinstall['configure_firewall'] = 'y'; // y (default), n
$autoinstall['install_ispconfig_web_interface'] = 'y'; // y (default), n

/* optional update settings, needed only for updates */
$autoupdate['do_backup'] = 'yes'; // yes (default), no
$autoupdate['mysql_root_password'] = 'howtoforge';
$autoupdate['mysql_master_hostname'] = 'master.example.com';
$autoupdate['mysql_master_root_user'] = 'root';
$autoupdate['mysql_master_root_password'] = 'howtoforge';
$autoupdate['mysql_master_database'] = 'dbispconfig'; // default: dbispconfig
$autoupdate['reconfigure_permissions_in_master_database'] = 'no'; // no (default), yes
$autoupdate['reconfigure_services'] = 'yes'; // yes (default), no
$autoupdate['ispconfig_port'] = '8080'; // default: 8080
$autoupdate['create_new_ispconfig_ssl_cert'] = 'no'; // no (default), yes
$autoupdate['reconfigure_crontab'] = 'yes'; // yes (default), no
?>