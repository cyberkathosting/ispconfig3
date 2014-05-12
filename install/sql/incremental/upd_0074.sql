ALTER TABLE `aps_instances`
	CHANGE `customer_id` `customer_id` int(4) NOT NULL DEFAULT '0',
	CHANGE `package_id` `package_id` int(4) NOT NULL DEFAULT '0',
	CHANGE `instance_status` `instance_status` int(4) NOT NULL DEFAULT '0';

ALTER TABLE `aps_instances_settings`
	CHANGE `instance_id` `instance_id` int(4) NOT NULL DEFAULT '0',
	CHANGE `name` `name` varchar(255) NOT NULL DEFAULT '',
	CHANGE `value` `value` text;

ALTER TABLE `aps_packages`
	CHANGE `path` `path` varchar(255) NOT NULL DEFAULT '',
	CHANGE `name` `name` varchar(255) NOT NULL DEFAULT '',
	CHANGE `category` `category` varchar(255) NOT NULL DEFAULT '',
	CHANGE `version` `version` varchar(20) NOT NULL DEFAULT '',
	CHANGE `release` `release` int(4) NOT NULL DEFAULT '0',
	CHANGE `package_url` `package_url` TEXT;

ALTER TABLE `aps_settings`
	CHANGE `name` `name` varchar(255) NOT NULL DEFAULT '',
	CHANGE `value` `value` text;

ALTER TABLE `attempts_login`
	CHANGE `ip` `ip` varchar(39) NOT NULL DEFAULT '';
	
ALTER TABLE `client`
	CHANGE `internet` `internet` varchar(255) NOT NULL DEFAULT '',
	CHANGE `mail_servers` `mail_servers` blob,
	CHANGE `web_servers` `web_servers` blob,
	CHANGE `db_servers` `db_servers` blob,
	CHANGE `dns_servers` `dns_servers` blob,
	CHANGE `template_additional` `template_additional` text;

ALTER TABLE `client_template`
	CHANGE `template_name` `template_name` varchar(64) NOT NULL DEFAULT '';

ALTER TABLE `country`
	CHANGE `iso` `iso` char(2) NOT NULL DEFAULT '',
	CHANGE `name` `name` varchar(64) NOT NULL DEFAULT '',
	CHANGE `printable_name` `printable_name` varchar(64) NOT NULL DEFAULT '';

ALTER TABLE `cron`
	CHANGE `command` `command` TEXT;

ALTER TABLE `dns_rr`
	CHANGE `sys_userid` `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_groupid` `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_perm_user` `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_group` `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_other` `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
	CHANGE `zone` `zone` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `name` `name` varchar(255) NOT NULL DEFAULT '',
	CHANGE `data` `data` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `dns_slave`
	CHANGE `sys_userid` `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_groupid` `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_perm_user` `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_group` `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_other` `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
	CHANGE `origin` `origin` varchar(255) NOT NULL DEFAULT '',
	CHANGE `ns` `ns` varchar(255) NOT NULL DEFAULT '',
	CHANGE `active` `active` enum('N','Y') NOT NULL DEFAULT 'N',
	CHANGE `xfer` `xfer` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `dns_soa`
	CHANGE `sys_userid` `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_groupid` `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_perm_user` `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_group` `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_other` `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
	CHANGE `origin` `origin` varchar(255) NOT NULL DEFAULT '',
	CHANGE `ns` `ns` varchar(255) NOT NULL DEFAULT '',
	CHANGE `mbox` `mbox` varchar(255) NOT NULL DEFAULT '',
	CHANGE `active` `active` enum('N','Y') NOT NULL DEFAULT 'N',
	CHANGE `xfer` `xfer` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `mail_access`
	CHANGE `source` `source` varchar(255) NOT NULL DEFAULT '',
	CHANGE `access` `access` varchar(255) NOT NULL DEFAULT '',
	CHANGE `type` `type` set('recipient','sender','client') NOT NULL DEFAULT 'recipient';

ALTER TABLE `mail_backup`
	CHANGE `server_id` `server_id` int(10) unsigned NOT NULL DEFAULT '0',
	CHANGE `parent_domain_id` `parent_domain_id` int(10) unsigned NOT NULL DEFAULT '0',
	CHANGE `mailuser_id` `mailuser_id` int(10) unsigned NOT NULL DEFAULT '0',
	CHANGE `tstamp` `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
	CHANGE `filename` `filename` varchar(255) NOT NULL DEFAULT '',
	CHANGE `filesize` `filesize` VARCHAR(10) NOT NULL DEFAULT '';

ALTER TABLE `mail_domain`
	CHANGE `active` `active` enum('n','y') NOT NULL DEFAULT 'n';

ALTER TABLE `mail_forwarding`
	CHANGE `source` `source` varchar(255) NOT NULL DEFAULT '',
	CHANGE `active` `active` enum('n','y') NOT NULL DEFAULT 'n';

ALTER TABLE `mail_mailinglist`
	CHANGE `sys_perm_user` `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_group` `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_other` `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
	CHANGE `domain` `domain` varchar(255) NOT NULL DEFAULT '',
	CHANGE `listname` `listname` varchar(255) NOT NULL DEFAULT '',
	CHANGE `email` `email` varchar(255) NOT NULL DEFAULT '',
	CHANGE `password` `password` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `mail_traffic`
	CHANGE `mailuser_id` `mailuser_id` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `month` `month` char(7) NOT NULL DEFAULT '',
	CHANGE `traffic` `traffic` bigint(20) unsigned NOT NULL DEFAULT '0';

ALTER TABLE `mail_transport`
	CHANGE `transport` `transport` varchar(255) NOT NULL DEFAULT '',
	CHANGE `active` `active` enum('n','y') NOT NULL DEFAULT 'n';

ALTER TABLE `mail_user`
	CHANGE `login` `login` varchar(255) NOT NULL default '',
	CHANGE `password` `password` varchar(255) NOT NULL default '',
	CHANGE `homedir` `homedir` varchar(255) NOT NULL default '',
	CHANGE `postfix` `postfix` enum('n','y') NOT NULL default 'y',
	CHANGE `access` `access` enum('n','y') NOT NULL default 'y';

ALTER TABLE `monitor_data`
	CHANGE `server_id` `server_id` int(11) unsigned NOT NULL default '0',
	CHANGE `type` `type` varchar(255) NOT NULL default '',
	CHANGE `created` `created` int(11) unsigned NOT NULL default '0',
	CHANGE `data` `data` mediumtext;

ALTER TABLE `openvz_ostemplate`
	CHANGE `template_file` `template_file` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `openvz_traffic`
	CHANGE `veid` `veid` int(11) NOT NULL DEFAULT '0',
	CHANGE `traffic_date` `traffic_date` date NOT NULL DEFAULT '0000-00-00';

ALTER TABLE `openvz_vm`
	CHANGE `veid` `veid` int(10) unsigned NOT NULL DEFAULT '0',
	CHANGE `ip_address` `ip_address` varchar(255) NOT NULL DEFAULT '',
	CHANGE `active_until_date` `active_until_date` date NOT NULL DEFAULT '0000-00-00',
	CHANGE `capability` `capability` text,
	CHANGE `config` `config` mediumtext;

ALTER TABLE `remote_session`
	CHANGE `remote_session` `remote_session` varchar(64) NOT NULL DEFAULT '',
	CHANGE `remote_userid` `remote_userid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `remote_functions` `remote_functions` text,
	CHANGE `tstamp` `tstamp` int(10) unsigned NOT NULL DEFAULT '0';

ALTER TABLE `remote_user`
	CHANGE `remote_username` `remote_username` varchar(64) NOT NULL DEFAULT '',
	CHANGE `remote_password` `remote_password` varchar(64) NOT NULL DEFAULT '',
	CHANGE `remote_functions` `remote_functions` text;

ALTER TABLE `server`
	CHANGE `config` `config` text;

ALTER TABLE `shell_user`
	CHANGE `chroot` `chroot` varchar(255) NOT NULL DEFAULT '',
	CHANGE `ssh_rsa` `ssh_rsa` text;

ALTER TABLE `software_update`
	CHANGE `software_repo_id` `software_repo_id` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `package_name` `package_name` varchar(64) NOT NULL DEFAULT '',
	CHANGE `update_url` `update_url` varchar(255) NOT NULL DEFAULT '',
	CHANGE `update_md5` `update_md5` varchar(255) NOT NULL DEFAULT '',
	CHANGE `update_dependencies` `update_dependencies` varchar(255) NOT NULL DEFAULT '',
	CHANGE `update_title` `update_title` varchar(64) NOT NULL DEFAULT '';

ALTER TABLE `software_update_inst`
	CHANGE `package_name` `package_name` varchar(64) NOT NULL DEFAULT '',
	CHANGE `server_id` `server_id` int(11) unsigned NOT NULL DEFAULT '0';

ALTER TABLE `spamfilter_policy`
	CHANGE `sys_userid` `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_groupid` `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_perm_user` `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_group` `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_other` `sys_perm_other` varchar(5) NOT NULL DEFAULT '';


ALTER TABLE `spamfilter_users`
	CHANGE `sys_userid` `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_groupid` `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_perm_user` `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_group` `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_other` `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
	CHANGE `server_id` `server_id` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `email` `email` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `spamfilter_wblist`
	CHANGE `sys_userid` `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_groupid` `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `sys_perm_user` `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_group` `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
	CHANGE `sys_perm_other` `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
	CHANGE `server_id` `server_id` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `rid` `rid` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `email` `email` varchar(255) NOT NULL DEFAULT '',
	CHANGE `priority` `priority` tinyint(3) unsigned NOT NULL DEFAULT '0';

ALTER TABLE `sys_config`
	CHANGE `group` `group` varchar(64) NOT NULL DEFAULT '',
	CHANGE `name` `name` varchar(64) NOT NULL DEFAULT '',
	CHANGE `value` `value` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `sys_cron`
	CHANGE `name` `name` varchar(50) NOT NULL DEFAULT '';

ALTER TABLE `sys_datalog`
	CHANGE `server_id` `server_id` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `data` `data` longtext;

ALTER TABLE `sys_group`
	CHANGE `description` `description` text;

ALTER TABLE `sys_ini`
	CHANGE `config` `config` longtext;

ALTER TABLE `sys_log`
	CHANGE `tstamp` `tstamp` int(11) unsigned NOT NULL DEFAULT '0';

ALTER TABLE `sys_remoteaction`
	CHANGE `server_id` `server_id` int(11) unsigned NOT NULL DEFAULT '0',
	CHANGE `tstamp` `tstamp` int(11) NOT NULL DEFAULT '0',
	CHANGE `action_type` `action_type` varchar(20) NOT NULL DEFAULT '',
	CHANGE `action_param` `action_param` mediumtext,
	CHANGE `action_state` `action_state` enum('pending','ok','warning','error') NOT NULL DEFAULT 'pending',
	CHANGE `response` `response` mediumtext;

ALTER TABLE `sys_theme`
	CHANGE `tpl_name` `tpl_name` varchar(32) NOT NULL DEFAULT '',
	CHANGE `username` `username` varchar(64) NOT NULL DEFAULT '',
	CHANGE `logo_url` `logo_url` varchar(255) NOT NULL DEFAULT '';

ALTER TABLE `sys_user`
	CHANGE `groups` `groups` TEXT;

ALTER TABLE `web_backup`
	CHANGE `server_id` `server_id` int(10) unsigned NOT NULL DEFAULT '0',
	CHANGE `parent_domain_id` `parent_domain_id` int(10) unsigned NOT NULL DEFAULT '0',
	CHANGE `tstamp` `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
	CHANGE `filename` `filename` varchar(255) NOT NULL DEFAULT '',
	CHANGE `filesize` `filesize` VARCHAR(10) NOT NULL DEFAULT '';

ALTER TABLE `web_database`
	CHANGE `remote_ips` `remote_ips` text;

ALTER TABLE `web_traffic`
	CHANGE `hostname` `hostname` varchar(255) NOT NULL DEFAULT '',
	CHANGE `traffic_date` `traffic_date` date NOT NULL DEFAULT '0000-00-00';
