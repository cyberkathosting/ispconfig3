ALTER TABLE `web_database` ADD `last_quota_notification` date NULL default NULL AFTER `database_quota`;

ALTER TABLE `ftp_user` CHANGE `username_prefix` `username_prefix` varchar(50) NOT NULL DEFAULT '';

ALTER TABLE `mail_domain` CHANGE `dkim` `dkim` enum('n','y') NOT NULL DEFAULT 'n';
ALTER TABLE `mail_forwarding` CHANGE  `destination` `destination` text;
ALTER TABLE `mail_user`
	CHANGE `uid` `uid` int(11) unsigned NOT NULL DEFAULT '5000',
	CHANGE `gid` `gid` int(11) unsigned NOT NULL DEFAULT '5000';

ALTER TABLE `server`
	CHANGE `proxy_server` `proxy_server` tinyint(1) NOT NULL DEFAULT '0',
	CHANGE `firewall_server` `firewall_server` tinyint(1) NOT NULL DEFAULT '0',
	CHANGE `dbversion` `dbversion` int(11) unsigned NOT NULL DEFAULT '1';

ALTER TABLE `server_ip`
	CHANGE `virtualhost_port` `virtualhost_port` varchar(255) DEFAULT '80,443';

ALTER TABLE `shell_user`
	CHANGE `username_prefix` `username_prefix` varchar(50) NOT NULL DEFAULT '';

ALTER TABLE `client` ADD `limit_dns_slave_zone` int(11) NOT NULL DEFAULT '-1' AFTER `default_slave_dnsserver`;
ALTER TABLE `client_template` ADD `limit_dns_slave_zone` int(11) NOT NULL DEFAULT '-1' AFTER `limit_dns_zone`;

ALTER TABLE `client`
	CHANGE `id_rsa` `id_rsa` varchar(2000) NOT NULL DEFAULT '',
	CHANGE `ssh_rsa` `ssh_rsa` varchar(600) NOT NULL DEFAULT '';

ALTER TABLE `software_package` CHANGE `software_repo_id` `software_repo_id` int(11) unsigned NOT NULL DEFAULT '0';
ALTER TABLE `software_package` ADD `package_installable` enum('yes','no','key') NOT NULL default 'yes' AFTER `package_type`;
ALTER TABLE `software_package` ADD `package_requires_db` enum('no','mysql') NOT NULL default 'no' AFTER `package_installable`;
ALTER TABLE `software_package` ADD `package_remote_functions` TEXT NULL AFTER `package_requires_db`;
ALTER TABLE `software_package` ADD `package_key` varchar(255) NOT NULL DEFAULT '' AFTER `package_remote_functions`;
ALTER TABLE `software_package` ADD `package_config` text AFTER `package_key`;

ALTER TABLE `software_package`
	CHANGE `package_name` `package_name` varchar(64) NOT NULL DEFAULT '',
	CHANGE `package_title` `package_title` varchar(64) NOT NULL DEFAULT '',
	CHANGE `package_key` `package_key` varchar(255) NOT NULL DEFAULT '';

INSERT IGNORE INTO `sys_config` (`group`, `name`, `value`) VALUES ('interface', 'session_timeout', '0');

ALTER TABLE `sys_datalog` CHANGE `status` `status` set('pending','ok','warning','error') NOT NULL DEFAULT 'ok';

ALTER TABLE `sys_session` CHANGE `session_id` `session_id` varchar(64) NOT NULL DEFAULT '';

ALTER TABLE `sys_user` CHANGE `language` `language` varchar(2) NOT NULL DEFAULT 'en';
ALTER TABLE `sys_user`
	ADD `id_rsa` varchar(2000) NOT NULL DEFAULT '' AFTER `client_id`,
	ADD `ssh_rsa` varchar(600) NOT NULL DEFAULT '' AFTER `id_rsa`;

ALTER TABLE `webdav_user` CHANGE `username_prefix` `username_prefix` varchar(50) NOT NULL DEFAULT '';

ALTER TABLE `web_database` CHANGE `database_name_prefix` `database_name_prefix` varchar(50) NOT NULL DEFAULT '';

ALTER TABLE `web_database_user` CHANGE `database_user_prefix` `database_user_prefix` varchar(50) NOT NULL DEFAULT '';

ALTER TABLE `web_domain` DROP COLUMN `document_root_www`;
ALTER TABLE `web_domain`
	CHANGE `ssl_key` `ssl_key` mediumtext,
	CHANGE `apache_directives` `apache_directives` mediumtext,
	CHANGE `php_open_basedir` `php_open_basedir` mediumtext,
	CHANGE `custom_php_ini` `custom_php_ini` mediumtext;

CREATE TABLE IF NOT EXISTS `dns_slave` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `server_id` int(11) NOT NULL default '1',
  `origin` varchar(255) NOT NULL DEFAULT '',
  `ns` varchar(255) NOT NULL DEFAULT '',
  `active` enum('N','Y') NOT NULL DEFAULT 'N',
  `xfer` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `slave` (`origin`,`server_id`),
  KEY `active` (`active`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
