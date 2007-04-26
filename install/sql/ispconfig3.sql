-- phpMyAdmin SQL Dump
-- version 2.9.0.3
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Erstellungszeit: 19. April 2007 um 12:05
-- Server Version: 5.0.24
-- PHP-Version: 5.1.4
-- 
-- Datenbank: `ispconfig3`
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
  `zone` int(10) unsigned NOT NULL,
  `name` char(64) NOT NULL,
  `type` enum('A','AAAA','CNAME','HINFO','MX','NAPTR','NS','PTR','RP','SRV','TXT') default NULL,
  `data` char(128) NOT NULL,
  `aux` int(10) unsigned NOT NULL,
  `ttl` int(10) unsigned NOT NULL default '86400',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `rr` (`zone`,`name`,`type`,`data`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `dns_rr`
-- 


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
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `dns_soa`
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- 
-- Daten für Tabelle `mail_access`
-- 

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
  KEY `server_id` (`server_id`,`domain`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- 
-- Daten für Tabelle `mail_forwarding`
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

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
  `quota` int(11) NOT NULL,
  `homedir` varchar(255) NOT NULL,
  `autoresponder` enum('n','y') NOT NULL default 'n',
  `autoresponder_text` tinytext NOT NULL,
  `postfix` enum('y','n') NOT NULL,
  `access` enum('y','n') NOT NULL,
  PRIMARY KEY  (`mailuser_id`),
  KEY `server_id` (`server_id`,`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- 
-- Daten für Tabelle `mail_user`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `reseller`
-- 

CREATE TABLE `reseller` (
  `reseller_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `company` varchar(255) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `firstname` varchar(255) NOT NULL default '',
  `surname` varchar(255) NOT NULL default '',
  `street` varchar(255) NOT NULL default '',
  `zip` varchar(255) NOT NULL default '',
  `city` varchar(255) NOT NULL default '',
  `country` varchar(255) NOT NULL default '',
  `telephone` varchar(255) NOT NULL default '',
  `mobile` varchar(255) NOT NULL default '',
  `fax` varchar(255) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `internet` varchar(255) NOT NULL default '',
  `icq` varchar(255) NOT NULL default '',
  `notes` text NOT NULL,
  `limit_client` int(11) NOT NULL default '-1',
  `limit_domain` int(11) NOT NULL default '-1',
  `limit_subdomain` int(11) NOT NULL default '-1',
  `limit_mailbox` int(11) NOT NULL default '-1',
  `limit_mailalias` int(11) NOT NULL default '-1',
  `limit_webquota` int(11) NOT NULL default '-1',
  `limit_mailquota` int(11) NOT NULL default '-1',
  `limit_database` int(11) NOT NULL default '-1',
  `ip_address` text NOT NULL,
  PRIMARY KEY  (`reseller_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `reseller`
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- 
-- Daten für Tabelle `server`
-- 

INSERT INTO `server` (`server_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_name`, `mail_server`, `web_server`, `dns_server`, `file_server`, `db_server`, `vserver_server`, `config`, `updated`, `active`) VALUES 
(1, 1, 1, 'riud', 'riud', '', 'Server 1', 1, 1, 1, 1, 1, 1, '[global]\r\nwebserver=apache\r\nmailserver=exim\r\ndnsserver=mydns\r\n\r\n[mail]\r\nmodule=postfix_mysql\r\nmaildir_path=/home/vmail/[domain]/[localpart]/\r\nhomedir_path=/home/vmail/\r\nmailuser_uid=5000\r\nmailuser_gid=5000', 0, 1);

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

INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES 
(1, 0, 0, '', '', '', 'Non-paying', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 7, 10, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 0, 0, '', '', '', 'Uncensored', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, 3, 999, 999, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 0, 0, '', '', '', 'Wants all spam', 'N', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 999, 999, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 0, 0, '', '', '', 'Wants viruses', 'Y', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 6.9, 6.9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 0, 0, '', '', '', 'Normal', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 6.9, 6.9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 0, 0, '', '', '', 'Trigger happy', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 5, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 0, 0, '', '', '', 'Permissive', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 10, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 0, 0, '', '', '', '6.5/7.8', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, 3, 6.5, 7.8, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 0, 0, '', '', '', 'userB', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 6.3, 6.3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 0, 0, '', '', '', 'userC', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, 3, 6, 6, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 0, 0, '', '', '', 'userD', 'Y', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, 3, 7, 7, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 1, 0, 'riud', 'riud', '', 'test', 'Y', 'Y', 'N', 'N', 'N', NULL, 'N', 'N', 'N', '', '', '', '', '', '', 0.7, 0, 0, 0, 0, '', '', '', '', 'N', 'N', 'N', '', '', '', '', '', '', '', 0, '');

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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=21 ;

-- 
-- Daten für Tabelle `spamfilter_users`
-- 

INSERT INTO `spamfilter_users` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `priority`, `policy_id`, `email`, `fullname`, `local`) VALUES 
(1, 0, 0, '', '', '', 0, 9, 5, 'user1+foo@y.example.com', 'Name1 Surname1', 'Y'),
(2, 0, 0, '', '', '', 0, 7, 5, 'user1@y.example.com', 'Name1 Surname1', 'Y'),
(3, 0, 0, '', '', '', 0, 7, 2, 'user2@y.example.com', 'Name2 Surname2', 'Y'),
(4, 0, 0, '', '', '', 0, 7, 7, 'user3@z.example.com', 'Name3 Surname3', 'Y'),
(5, 0, 0, '', '', '', 0, 7, 7, 'user4@example.com', 'Name4 Surname4', 'Y'),
(6, 0, 0, '', '', '', 0, 7, 1, 'user5@example.com', 'Name5 Surname5', 'Y'),
(7, 0, 0, '', '', '', 0, 5, 0, '@sub1.example.com', NULL, 'Y'),
(8, 0, 0, '', '', '', 0, 5, 7, '@sub2.example.com', NULL, 'Y'),
(9, 0, 0, '', '', '', 0, 5, 5, '@example.com', NULL, 'Y'),
(10, 0, 0, '', '', '', 0, 3, 8, 'userA', 'NameA SurnameA anywhere', 'Y'),
(11, 0, 0, '', '', '', 0, 3, 9, 'userB', 'NameB SurnameB', 'Y'),
(12, 0, 0, '', '', '', 0, 3, 10, 'userC', 'NameC SurnameC', 'Y'),
(13, 0, 0, '', '', '', 0, 3, 11, 'userD', 'NameD SurnameD', 'Y'),
(14, 0, 0, '', '', '', 0, 3, 0, '@sub1.example.net', NULL, 'Y'),
(15, 0, 0, '', '', '', 0, 3, 7, '@sub2.example.net', NULL, 'Y'),
(16, 0, 0, '', '', '', 0, 3, 5, '@example.net', NULL, 'Y'),
(17, 0, 0, '', '', '', 0, 7, 5, 'u1@example.org', 'u1', 'Y'),
(18, 0, 0, '', '', '', 0, 7, 6, 'u2@example.org', 'u2', 'Y'),
(19, 0, 0, '', '', '', 0, 7, 3, 'u3@example.org', 'u3', 'Y'),
(20, 1, 0, 'riud', 'riud', '', 1, 5, 0, '@ensign.de', 'ensign all', 'N');

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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- 
-- Daten für Tabelle `spamfilter_wblist`
-- 

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `sys_datalog`
-- 

CREATE TABLE `sys_datalog` (
  `datalog_id` bigint(20) NOT NULL auto_increment,
  `dbtable` varchar(255) NOT NULL default '',
  `dbidx` varchar(255) NOT NULL default '',
  `action` char(1) NOT NULL default '',
  `tstamp` bigint(20) NOT NULL default '0',
  `user` varchar(255) NOT NULL default '',
  `data` text NOT NULL,
  PRIMARY KEY  (`datalog_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=35 ;

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
  PRIMARY KEY  (`groupid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

-- 
-- Daten für Tabelle `sys_group`
-- 

INSERT INTO `sys_group` (`groupid`, `name`, `description`) VALUES 
(1, 'admin', 'Administrators group'),
(2, 'user', 'Users Group');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `sys_user`
-- 

CREATE TABLE `sys_user` (
  `userid` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `username` varchar(100) NOT NULL default '',
  `passwort` varchar(100) NOT NULL default '',
  `modules` varchar(255) NOT NULL default '',
  `startmodule` varchar(255) NOT NULL default '',
  `app_theme` varchar(100) NOT NULL default 'default',
  `typ` varchar(20) NOT NULL default 'user',
  `active` tinyint(4) NOT NULL default '1',
  `name` varchar(100) NOT NULL default '',
  `vorname` varchar(100) NOT NULL default '',
  `unternehmen` varchar(100) NOT NULL default '',
  `strasse` varchar(100) NOT NULL default '',
  `ort` varchar(100) NOT NULL default '',
  `plz` varchar(10) NOT NULL default '',
  `land` varchar(50) NOT NULL default '',
  `email` varchar(100) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `telefon` varchar(100) NOT NULL default '',
  `fax` varchar(100) NOT NULL default '',
  `language` varchar(10) NOT NULL default 'de',
  `groups` varchar(255) NOT NULL default '',
  `default_group` int(11) NOT NULL default '0',
  PRIMARY KEY  (`userid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- 
-- Daten für Tabelle `sys_user`
-- 

INSERT INTO `sys_user` (`userid`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `username`, `passwort`, `modules`, `startmodule`, `app_theme`, `typ`, `active`, `name`, `vorname`, `unternehmen`, `strasse`, `ort`, `plz`, `land`, `email`, `url`, `telefon`, `fax`, `language`, `groups`, `default_group`) VALUES 
(1, 1, 0, 'riud', 'riud', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'admin,designer,mail', 'mail', 'default', 'admin', 1, '', 'Administrator', '', '', '', '', '', '', '', '', '', 'en', '1,2', 1);
