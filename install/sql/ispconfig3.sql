-- phpMyAdmin SQL Dump
-- version 2.9.0.3
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Erstellungszeit: 29. Juni 2007 um 16:37
-- Server Version: 5.0.24
-- PHP-Version: 5.1.4
-- 
-- Datenbank: `ispconfig3`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `client`
-- 

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `client` (
  `client_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `company_name` varchar(255) default NULL,
  `contact_name` varchar(255) default NULL,
  `street` varchar(255) default NULL,
  `zip` varchar(255) default NULL,
  `city` varchar(255) default NULL,
  `state` varchar(255) default NULL,
  `country` varchar(255) default NULL,
  `telephone` varchar(255) default NULL,
  `mobile` varchar(255) default NULL,
  `fax` varchar(255) default NULL,
  `email` varchar(255) default NULL,
  `internet` varchar(255) NOT NULL default 'http://',
  `icq` varchar(255) default NULL,
  `notes` text,
  `default_mailserver` int(11) NOT NULL default '1',
  `limit_maildomain` int(11) NOT NULL default '-1',
  `limit_mailbox` int(11) NOT NULL default '-1',
  `limit_mailalias` int(11) NOT NULL default '-1',
  `limit_mailforward` int(11) NOT NULL default '-1',
  `limit_mailcatchall` int(11) NOT NULL default '-1',
  `limit_mailrouting` int(11) NOT NULL default '0',
  `limit_mailfilter` int(11) NOT NULL default '-1',
  `limit_fetchmail` int(11) NOT NULL default '-1',
  `limit_mailquota` int(11) NOT NULL default '-1',
  `limit_spamfilter_wblist` int(11) NOT NULL default '0',
  `limit_spamfilter_user` int(11) NOT NULL default '0',
  `limit_spamfilter_policy` int(11) NOT NULL default '0',
  `default_webserver` int(11) NOT NULL default '1',
  `limit_web_ip` text,
  `limit_web_domain` int(11) NOT NULL default '-1',
  `limit_web_subdomain` int(11) NOT NULL default '-1',
  `limit_web_aliasdomain` int(11) NOT NULL default '-1',
  `limit_ftp_user` int(11) NOT NULL default '-1',
  `limit_shell_user` int(11) NOT NULL default '0',
  `default_dnsserver` int(10) unsigned NOT NULL default '1',
  `limit_dns_zone` int(11) NOT NULL default '-1',
  `limit_dns_record` int(11) NOT NULL default '-1',
  `default_dbserver` int(10) unsigned NOT NULL default '1',
  `limit_database` int(11) NOT NULL default '-1',
  `limit_client` int(11) NOT NULL default '0',
  `parent_client_id` int(10) unsigned NOT NULL default '0',
  `username` varchar(255) default NULL,
  `password` varchar(255) default NULL,
  `language` varchar(255) NOT NULL default 'en',
  `usertheme` varchar(255) NOT NULL default 'default',
  PRIMARY KEY  (`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `client`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `dns_rr`
-- 

CREATE TABLE `dns_rr` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL,
  `sys_groupid` int(11) NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(11) NOT NULL default '1',
  `zone` int(10) unsigned NOT NULL,
  `name` char(64) NOT NULL,
  `type` enum('A','AAAA','ALIAS','CNAME','HINFO','MX','NAPTR','NS','PTR','RP','SRV','TXT') default NULL,
  `data` char(128) NOT NULL,
  `aux` int(10) unsigned NOT NULL default '0',
  `ttl` int(10) unsigned NOT NULL default '86400',
  `active` enum('N','Y') NOT NULL default 'Y',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `rr` (`zone`,`name`,`type`,`data`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `dns_soa`
-- 

CREATE TABLE `dns_soa` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL,
  `sys_groupid` int(11) NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(11) NOT NULL default '1',
  `origin` char(255) NOT NULL,
  `ns` char(255) NOT NULL,
  `mbox` char(255) NOT NULL,
  `serial` int(10) unsigned NOT NULL default '1',
  `refresh` int(10) unsigned NOT NULL default '28800',
  `retry` int(10) unsigned NOT NULL default '7200',
  `expire` int(10) unsigned NOT NULL default '604800',
  `minimum` int(10) unsigned NOT NULL default '86400',
  `ttl` int(10) unsigned NOT NULL default '86400',
  `active` enum('Y','N') NOT NULL,
  `xfer` char(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `origin` (`origin`),
  KEY `active` (`active`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;



-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `firewall`
-- 

CREATE TABLE `firewall` (
  `firewall_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) NOT NULL default '0',
  `tcp_port` varchar(255) default NULL,
  `udp_port` varchar(255) default NULL,
  `active` varchar(255) NOT NULL default 'y',
  PRIMARY KEY  (`firewall_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `firewall`



-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `ftp_user`
-- 

CREATE TABLE `ftp_user` (
  `ftp_user_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) NOT NULL default '0',
  `parent_domain_id` int(11) NOT NULL default '0',
  `username` varchar(255) default NULL,
  `password` varchar(255) default NULL,
  `quota_size` int(11) NOT NULL default '-1',
  `active` varchar(255) NOT NULL default 'y',
  `uid` varchar(255) default NULL,
  `gid` varchar(255) default NULL,
  `dir` varchar(255) default NULL,
  `quota_files` int(11) NOT NULL default '-1',
  `ul_ratio` int(11) NOT NULL default '-1',
  `dl_ratio` int(11) NOT NULL default '-1',
  `ul_bandwidth` int(11) NOT NULL default '-1',
  `dl_bandwidth` int(11) NOT NULL default '-1',
  PRIMARY KEY  (`ftp_user_id`),
  KEY `active` (`active`),
  KEY `server_id` (`server_id`),
  KEY `username` (`username`),
  KEY `quota_files` (`quota_files`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `ftp_user`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_access`
-- 

CREATE TABLE `mail_access` (
  `access_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `source` varchar(255) NOT NULL,
  `access` varchar(255) NOT NULL,
  `type` set('recipient','sender','client') NOT NULL,
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`access_id`),
  KEY `server_id` (`server_id`,`source`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_access`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_content_filter`
-- 

CREATE TABLE `mail_content_filter` (
  `content_filter_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) NOT NULL default '0',
  `type` varchar(255) default NULL,
  `pattern` varchar(255) default NULL,
  `data` varchar(255) default NULL,
  `action` varchar(255) default NULL,
  `active` varchar(255) NOT NULL default 'y',
  PRIMARY KEY  (`content_filter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_domain`
-- 

CREATE TABLE `mail_domain` (
  `domain_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `active` enum('y','n') NOT NULL,
  PRIMARY KEY  (`domain_id`),
  KEY `server_id` (`server_id`,`domain`),
  KEY `domain_active` (`domain`,`active`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_domain`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_forwarding`
-- 

CREATE TABLE `mail_forwarding` (
  `forwarding_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `source` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL default '',
  `type` enum('alias','forward','catchall') NOT NULL default 'alias',
  `active` enum('y','n') NOT NULL,
  PRIMARY KEY  (`forwarding_id`),
  KEY `server_id` (`server_id`,`source`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_forwarding`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_get`
-- 

CREATE TABLE `mail_get` (
  `mailget_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) NOT NULL default '0',
  `type` varchar(255) default NULL,
  `source_server` varchar(255) default NULL,
  `source_username` varchar(255) default NULL,
  `source_password` varchar(255) default NULL,
  `source_delete` varchar(255) NOT NULL default 'y',
  `destination` varchar(255) default NULL,
  `active` varchar(255) NOT NULL default 'y',
  PRIMARY KEY  (`mailget_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_get`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_greylist`
-- 

CREATE TABLE `mail_greylist` (
  `greylist_id` int(11) NOT NULL auto_increment,
  `relay_ip` varchar(64) default NULL,
  `from_domain` varchar(255) default NULL,
  `block_expires` datetime NOT NULL default '0000-00-00 00:00:00',
  `record_expires` datetime NOT NULL default '0000-00-00 00:00:00',
  `origin_type` enum('MANUAL','AUTO') NOT NULL default 'AUTO',
  `create_time` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`greylist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_greylist`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_mailman_domain`
-- 

CREATE TABLE `mail_mailman_domain` (
  `mailman_id` int(11) NOT NULL auto_increment,
  `server_id` int(11) NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `mm_home` varchar(255) NOT NULL default '',
  `mm_wrap` varchar(255) NOT NULL default '',
  `mm_user` varchar(50) NOT NULL default '',
  `mm_group` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`mailman_id`,`server_id`,`domain`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_mailman_domain`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_traffic`
-- 

CREATE TABLE `mail_traffic` (
  `traffic_id` int(10) unsigned NOT NULL auto_increment,
  `mailuser_id` int(10) unsigned NOT NULL,
  `month` char(7) NOT NULL,
  `traffic` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`traffic_id`),
  KEY `mailuser_id` (`mailuser_id`,`month`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- 
-- Daten für Tabelle `mail_traffic`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_transport`
-- 

CREATE TABLE `mail_transport` (
  `transport_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `transport` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL default '5',
  `active` enum('y','n') NOT NULL,
  PRIMARY KEY  (`transport_id`),
  KEY `server_id` (`server_id`,`transport`),
  KEY `server_id_2` (`server_id`,`domain`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_transport`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_user`
-- 

CREATE TABLE `mail_user` (
  `mailuser_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL,
  `name` varchar(128) NOT NULL default '',
  `uid` int(10) unsigned NOT NULL default '5000',
  `gid` int(10) unsigned NOT NULL default '5000',
  `maildir` varchar(255) NOT NULL default '',
  `quota` int(11) NOT NULL default '0',
  `homedir` varchar(255) NOT NULL,
  `autoresponder` enum('n','y') NOT NULL default 'n',
  `autoresponder_text` tinytext NOT NULL,
  `custom_mailfilter` text,
  `postfix` enum('y','n') NOT NULL,
  `access` enum('y','n') NOT NULL,
  `disableimap` enum('0','1') NOT NULL default '0',
  `disablepop3` enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (`mailuser_id`),
  KEY `server_id` (`server_id`,`email`),
  KEY `email_access` (`email`,`access`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_user`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_user_filter`
-- 

CREATE TABLE `mail_user_filter` (
  `filter_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `mailuser_id` int(11) NOT NULL default '0',
  `rulename` varchar(255) default NULL,
  `source` varchar(255) default NULL,
  `searchterm` varchar(255) default NULL,
  `op` varchar(255) default NULL,
  `action` varchar(255) default NULL,
  `target` varchar(255) default NULL,
  `active` varchar(255) NOT NULL default 'y',
  PRIMARY KEY  (`filter_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `remote_session`
-- 

CREATE TABLE `remote_session` (
  `remote_session` varchar(50) NOT NULL,
  `remote_userid` int(11) NOT NULL,
  `remote_functions` text NOT NULL,
  `tstamp` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`remote_session`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Daten für Tabelle `remote_session`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `remote_user`
-- 

CREATE TABLE `remote_user` (
  `remote_userid` int(11) NOT NULL auto_increment,
  `remote_username` varchar(255) NOT NULL,
  `remote_password` varchar(255) NOT NULL,
  `remote_functions` text NOT NULL,
  PRIMARY KEY  (`remote_userid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `remote_user`
-- 




-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `server`
-- 

CREATE TABLE `server` (
  `server_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_name` varchar(255) NOT NULL default '',
  `mail_server` tinyint(4) NOT NULL default '0',
  `web_server` tinyint(4) NOT NULL default '0',
  `dns_server` tinyint(4) NOT NULL default '0',
  `file_server` tinyint(4) NOT NULL default '0',
  `db_server` tinyint(4) NOT NULL default '0',
  `vserver_server` tinyint(4) NOT NULL default '0',
  `config` text NOT NULL,
  `updated` tinyint(4) NOT NULL default '0',
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`server_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `server`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `server_ip`
-- 

CREATE TABLE `server_ip` (
  `server_ip_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(10) unsigned NOT NULL default '0',
  `ip_address` varchar(15) default NULL,
  `virtualhost` char(1) NOT NULL default 'y',
  PRIMARY KEY  (`server_ip_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `server_ip`
-- 

-- --------------------------------------------------------

CREATE TABLE `shell_user` (
  `shell_user_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) NOT NULL default '0',
  `parent_domain_id` int(11) NOT NULL default '0',
  `username` varchar(255) default NULL,
  `password` varchar(255) default NULL,
  `quota_size` int(11) NOT NULL default '-1',
  `active` varchar(255) NOT NULL default 'y',
  `puser` varchar(255) default NULL,
  `pgroup` varchar(255) default NULL,
  `shell` varchar(255) NOT NULL default '/bin/bash',
  `dir` varchar(255) default NULL,
  `chroot` varchar(255) NOT NULL,
  PRIMARY KEY  (`shell_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;


-- 
-- Daten für Tabelle `shell_user`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `software_package`
-- 

CREATE TABLE `software_package` (
  `package_id` int(11) NOT NULL auto_increment,
  `software_repo_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `package_title` varchar(255) NOT NULL,
  `package_description` text,
  `package_version` varchar(255) default NULL,
  PRIMARY KEY  (`package_id`),
  UNIQUE KEY `package_name` (`package_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `software_package`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `software_repo`
-- 

CREATE TABLE `software_repo` (
  `software_repo_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `repo_name` varchar(40) default NULL,
  `repo_url` varchar(40) default NULL,
  `repo_username` varchar(30) default NULL,
  `repo_password` varchar(30) default NULL,
  `active` varchar(255) NOT NULL default 'y',
  PRIMARY KEY  (`software_repo_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `software_repo`
-- 

INSERT INTO `software_repo` (`software_repo_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `repo_name`, `repo_url`, `repo_username`, `repo_password`, `active`) VALUES (1, 1, 1, 'riud', 'riud', '', 'ISPConfig Addons', 'http://repo.ispconfig.org/addons/', '', '', 'n');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `software_update`
-- 

CREATE TABLE `software_update` (
  `software_update_id` int(11) NOT NULL auto_increment,
  `software_repo_id` int(11) NOT NULL,
  `package_name` varchar(255) NOT NULL,
  `update_url` varchar(255) NOT NULL,
  `update_md5` varchar(255) NOT NULL,
  `update_dependencies` varchar(255) NOT NULL,
  `update_title` varchar(255) NOT NULL,
  `v1` tinyint(4) NOT NULL default '0',
  `v2` tinyint(4) NOT NULL default '0',
  `v3` tinyint(4) NOT NULL default '0',
  `v4` tinyint(4) NOT NULL default '0',
  `type` enum('full','update') NOT NULL default 'full',
  PRIMARY KEY  (`software_update_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `software_update`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `software_update_inst`
-- 

CREATE TABLE `software_update_inst` (
  `software_update_inst_id` int(10) unsigned NOT NULL auto_increment,
  `software_update_id` int(11) NOT NULL default '0',
  `package_name` varchar(255) NOT NULL,
  `server_id` int(11) NOT NULL,
  `status` enum('none','installing','installed','deleting') NOT NULL default 'none',
  PRIMARY KEY  (`software_update_inst_id`),
  UNIQUE KEY `software_update_id` (`software_update_id`,`package_name`,`server_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `software_update_inst`
-- 


-- --------------------------------------------------------


-- 
-- Tabellenstruktur für Tabelle `spamfilter_policy`
-- 

CREATE TABLE `spamfilter_policy` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL,
  `sys_groupid` int(11) NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `policy_name` varchar(32) default NULL,
  `virus_lover` char(1) default NULL,
  `spam_lover` char(1) default NULL,
  `banned_files_lover` char(1) default NULL,
  `bad_header_lover` char(1) default NULL,
  `bypass_virus_checks` char(1) default NULL,
  `bypass_spam_checks` char(1) default NULL,
  `bypass_banned_checks` char(1) default NULL,
  `bypass_header_checks` char(1) default NULL,
  `spam_modifies_subj` char(1) default NULL,
  `virus_quarantine_to` varchar(64) default NULL,
  `spam_quarantine_to` varchar(64) default NULL,
  `banned_quarantine_to` varchar(64) default NULL,
  `bad_header_quarantine_to` varchar(64) default NULL,
  `clean_quarantine_to` varchar(64) default NULL,
  `other_quarantine_to` varchar(64) default NULL,
  `spam_tag_level` float default NULL,
  `spam_tag2_level` float default NULL,
  `spam_kill_level` float default NULL,
  `spam_dsn_cutoff_level` float default NULL,
  `spam_quarantine_cutoff_level` float default NULL,
  `addr_extension_virus` varchar(64) default NULL,
  `addr_extension_spam` varchar(64) default NULL,
  `addr_extension_banned` varchar(64) default NULL,
  `addr_extension_bad_header` varchar(64) default NULL,
  `warnvirusrecip` char(1) default NULL,
  `warnbannedrecip` char(1) default NULL,
  `warnbadhrecip` char(1) default NULL,
  `newvirus_admin` varchar(64) default NULL,
  `virus_admin` varchar(64) default NULL,
  `banned_admin` varchar(64) default NULL,
  `bad_header_admin` varchar(64) default NULL,
  `spam_admin` varchar(64) default NULL,
  `spam_subject_tag` varchar(64) default NULL,
  `spam_subject_tag2` varchar(64) default NULL,
  `message_size_limit` int(11) default NULL,
  `banned_rulenames` varchar(64) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

-- 
-- Daten für Tabelle `spamfilter_policy`
-- 

INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES (1, 1, 0, 'riud', 'riud', 'r', 'Non-paying', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N', 'Y', '', '', '', '', '', '', 3, 7, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES (2, 1, 0, 'riud', 'riud', 'r', 'Uncensored', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, 3, 999, 999, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES (3, 1, 0, 'riud', 'riud', 'r', 'Wants all spam', 'N', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 999, 999, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES (4, 1, 0, 'riud', 'riud', 'r', 'Wants viruses', 'Y', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 6.9, 6.9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES (5, 1, 0, 'riud', 'riud', 'r', 'Normal', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 6.9, 6.9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES (6, 1, 0, 'riud', 'riud', 'r', 'Trigger happy', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 5, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES (7, 1, 0, 'riud', 'riud', 'r', 'Permissive', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 10, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `spamfilter_users`
-- 

CREATE TABLE `spamfilter_users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL,
  `sys_groupid` int(11) NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(10) unsigned NOT NULL,
  `priority` int(11) NOT NULL default '7',
  `policy_id` int(10) unsigned NOT NULL default '1',
  `email` varchar(255) NOT NULL,
  `fullname` varchar(255) default NULL,
  `local` char(1) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `spamfilter_users`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `spamfilter_wblist`
-- 

CREATE TABLE `spamfilter_wblist` (
  `wblist_id` int(10) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL,
  `sys_groupid` int(11) NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(10) unsigned NOT NULL,
  `wb` enum('W','B') NOT NULL default 'W',
  `rid` int(10) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL,
  `active` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`wblist_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `spamfilter_wblist`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `support_message`
-- 

CREATE TABLE `support_message` (
  `support_message_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `recipient_id` int(11) NOT NULL default '0',
  `sender_id` int(11) NOT NULL default '0',
  `subject` varchar(255) default NULL,
  `message` varchar(255) default NULL,
  `tstamp` int(11) NOT NULL default '1187707778',
  PRIMARY KEY  (`support_message_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `sys_datalog`
-- 

CREATE TABLE `sys_datalog` (
  `datalog_id` bigint(20) NOT NULL auto_increment,
  `server_id` int(11) NOT NULL,
  `dbtable` varchar(255) NOT NULL default '',
  `dbidx` varchar(255) NOT NULL default '',
  `action` char(1) NOT NULL default '',
  `tstamp` bigint(20) NOT NULL default '0',
  `user` varchar(255) NOT NULL default '',
  `data` text NOT NULL,
  PRIMARY KEY  (`datalog_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `sys_datalog`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `sys_dbsync`
-- 

CREATE TABLE `sys_dbsync` (
  `id` bigint(20) NOT NULL auto_increment,
  `jobname` varchar(255) NOT NULL default '',
  `sync_interval_minutes` int(11) NOT NULL default '0',
  `db_type` varchar(255) NOT NULL default '',
  `db_host` varchar(255) NOT NULL default '',
  `db_name` varchar(255) NOT NULL default '',
  `db_username` varchar(255) NOT NULL default '',
  `db_password` varchar(255) NOT NULL default '',
  `db_tables` varchar(255) NOT NULL default 'admin,forms',
  `empty_datalog` int(11) NOT NULL default '0',
  `sync_datalog_external` int(11) NOT NULL default '0',
  `active` int(11) NOT NULL default '1',
  `last_datalog_id` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `last_datalog_id` (`last_datalog_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `sys_dbsync`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `sys_filesync`
-- 

CREATE TABLE `sys_filesync` (
  `id` bigint(20) NOT NULL auto_increment,
  `jobname` varchar(255) NOT NULL default '',
  `sync_interval_minutes` int(11) NOT NULL default '0',
  `ftp_host` varchar(255) NOT NULL default '',
  `ftp_path` varchar(255) NOT NULL default '',
  `ftp_username` varchar(255) NOT NULL default '',
  `ftp_password` varchar(255) NOT NULL default '',
  `local_path` varchar(255) NOT NULL default '',
  `wput_options` varchar(255) NOT NULL default '--timestamping --reupload --dont-continue',
  `active` int(11) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `sys_filesync`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `sys_group`
-- 

CREATE TABLE `sys_group` (
  `groupid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  `client_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`groupid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- 
-- Daten für Tabelle `sys_group`
-- 

INSERT INTO `sys_group` (`groupid`, `name`, `description`, `client_id`) VALUES (1, 'admin', 'Administrators group', 0);

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `sys_user`
-- 

CREATE TABLE `sys_user` (
  `userid` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '1',
  `sys_groupid` int(11) NOT NULL default '1',
  `sys_perm_user` varchar(5) NOT NULL default 'riud',
  `sys_perm_group` varchar(5) NOT NULL default 'riud',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `username` varchar(100) NOT NULL default '',
  `passwort` varchar(100) NOT NULL default '',
  `modules` varchar(255) NOT NULL default '',
  `startmodule` varchar(255) NOT NULL default '',
  `app_theme` varchar(100) NOT NULL default 'default',
  `typ` varchar(20) NOT NULL default 'user',
  `active` tinyint(4) NOT NULL default '1',
  `language` varchar(10) NOT NULL default 'de',
  `groups` varchar(255) NOT NULL default '',
  `default_group` int(11) NOT NULL default '0',
  `client_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`userid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- 
-- Daten für Tabelle `sys_user`
-- 

INSERT INTO `sys_user` (`userid`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `username`, `passwort`, `modules`, `startmodule`, `app_theme`, `typ`, `active`, `language`, `groups`, `default_group`, `client_id`) VALUES (1, 1, 0, 'riud', 'riud', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'admin,client,mail,monitor,sites,dns,tools', 'mail', 'default', 'admin', 1, 'en', '1,2', 1, 0);

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `web_domain`
-- 

CREATE TABLE `web_domain` (
  `domain_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) NOT NULL default '0',
  `ip_address` varchar(15) default NULL,
  `domain` varchar(255) default NULL,
  `type` varchar(255) NOT NULL default 'y',
  `parent_domain_id` int(11) NOT NULL default '0',
  `vhost_type` varchar(255) default NULL,
  `document_root` varchar(255) default NULL,
  `system_user` varchar(255) default NULL,
  `system_group` varchar(255) default NULL,
  `hd_quota` int(11) NOT NULL default '0',
  `traffic_quota` int(11) NOT NULL default '0',
  `cgi` char(1) NOT NULL default 'y',
  `ssi` char(1) NOT NULL default 'y',
  `suexec` char(1) NOT NULL default 'y',
  `errordocs` char(1) NOT NULL default 'y',
  `php` varchar(255) NOT NULL default 'y',
  `redirect_type` varchar(255) default NULL,
  `redirect_path` varchar(255) default NULL,
  `ssl` enum('n','y') NOT NULL default 'n',
  `ssl_state` varchar(255) NULL,
  `ssl_locality` varchar(255) NULL,
  `ssl_organisation` varchar(255) NULL,
  `ssl_organisation_unit` varchar(255) NULL,
  `ssl_country` varchar(255) NULL,
  `ssl_request` mediumtext NULL,
  `ssl_cert` mediumtext NULL,
  `ssl_bundle` mediumtext NULL,
  `ssl_action` varchar(10) NULL,
  `apache_directives` text,
  `active` varchar(255) NOT NULL default 'y',
  PRIMARY KEY  (`domain_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


-- 
-- Daten für Tabelle `web_domain`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `web_database`
-- 

CREATE TABLE `web_database` (
  `database_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) NOT NULL default '0',
  `type` varchar(255) NOT NULL default 'y',
  `database_name` varchar(255) default NULL,
  `database_user` varchar(255) default NULL,
  `database_password` varchar(255) default NULL,
  `database_charset` varchar(64) default NULL,
  `remote_access` varchar(255) NOT NULL default 'y',
  `active` varchar(255) NOT NULL default 'y',
  PRIMARY KEY  (`database_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `web_database`
-- 

-- --------------------------------------------------------

--
-- Table for attempts login 
--

CREATE TABLE `attempts_login` (
  `ip` varchar(12) NOT NULL,
  `times` tinyint(1) NOT NULL default '1',
  `login_time` timestamp NOT NULL default '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- 
-- Tabellenstruktur für Tabelle `monitor_data`
-- 

CREATE TABLE `monitor_data` (
  `server_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created` int(11) NOT NULL,
  `data` mediumtext NOT NULL,
  `state` enum('unknown','ok','warning','error') NOT NULL default 'unknown',
  PRIMARY KEY  (`server_id`,`type`,`created`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


SET FOREIGN_KEY_CHECKS = 1;
