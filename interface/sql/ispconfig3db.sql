-- phpMyAdmin SQL Dump
-- version 2.6.2-Debian-3sarge1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 25. November 2005 um 19:28
-- Server Version: 4.0.24
-- PHP-Version: 4.3.10-16
--
-- Datenbank: `mailserver`
--

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_blacklist`
-- 

DROP TABLE IF EXISTS `mail_blacklist`;
CREATE TABLE `mail_blacklist` (
  `blacklist_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `address` varchar(200) NOT NULL default '',
  `recipient` varchar(200) NOT NULL default '',
  `active` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`blacklist_id`),
  KEY `server_id` (`server_id`,`address`,`recipient`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

-- 
-- Daten für Tabelle `mail_blacklist`
-- 

INSERT INTO `mail_blacklist` (`blacklist_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `address`, `recipient`, `active`) VALUES (2, 1, 0, 'riud', 'riud', '', 1, 'till@test.int', 'till@test.int', '0');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_box`
-- 

DROP TABLE IF EXISTS `mail_box`;
CREATE TABLE `mail_box` (
  `mailbox_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `cryptpwd` varchar(128) NOT NULL default '',
  `clearpwd` varchar(128) NOT NULL default '',
  `name` varchar(128) NOT NULL default '',
  `uid` int(10) unsigned NOT NULL default '0',
  `gid` int(10) unsigned NOT NULL default '0',
  `maildir` varchar(255) NOT NULL default '',
  `quota` varchar(255) NOT NULL default '',
  `cc` varchar(50) NOT NULL default '',
  `forward` varchar(50) NOT NULL default '',
  `autoresponder` enum('0','1') NOT NULL default '0',
  `autoresponder_text` tinytext NOT NULL,
  `active` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`mailbox_id`),
  KEY `server_id` (`server_id`,`email`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

-- 
-- Daten für Tabelle `mail_box`
-- 

INSERT INTO `mail_box` (`mailbox_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `email`, `cryptpwd`, `clearpwd`, `name`, `uid`, `gid`, `maildir`, `quota`, `cc`, `forward`, `autoresponder`, `autoresponder_text`, `active`) VALUES (1, 1, 0, 'riud', 'riud', '', 1, 'till@test.int', '$1$tRlfKeOB$iHJgCn8mH8x/dh/XWy6v0/', '', '', 0, 0, '/var/spool/mail/till', '100', '', '', '0', '', '1');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_domain`
-- 

DROP TABLE IF EXISTS `mail_domain`;
CREATE TABLE `mail_domain` (
  `domain_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `type` enum('local','relay','alias') NOT NULL default 'local',
  `relay_host` varchar(255) NOT NULL default '',
  `destination` varchar(255) NOT NULL default '',
  `active` tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (`domain_id`),
  KEY `server_id` (`server_id`,`domain`,`type`)
) TYPE=MyISAM AUTO_INCREMENT=7 ;

-- 
-- Daten für Tabelle `mail_domain`
-- 

INSERT INTO `mail_domain` (`domain_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `domain`, `type`, `relay_host`, `destination`, `active`) VALUES (1, 1, 0, 'riud', 'riud', '', 1, 'test.int', 'local', '', '', 1);
INSERT INTO `mail_domain` (`domain_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `domain`, `type`, `relay_host`, `destination`, `active`) VALUES (2, 1, 0, 'riud', 'riud', '', 1, 'test2.int', 'alias', '', 'test.int', 1);
INSERT INTO `mail_domain` (`domain_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `domain`, `type`, `relay_host`, `destination`, `active`) VALUES (5, 1, 0, 'riud', 'riud', '', 1, 'ensign.int', 'alias', '', 'ensign.de', 1);

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_domain_catchall`
-- 

DROP TABLE IF EXISTS `mail_domain_catchall`;
CREATE TABLE `mail_domain_catchall` (
  `domain_catchall_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `destination` varchar(255) NOT NULL default '',
  `active` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`domain_catchall_id`),
  KEY `server_id` (`server_id`,`domain`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

-- 
-- Daten für Tabelle `mail_domain_catchall`
-- 

INSERT INTO `mail_domain_catchall` (`domain_catchall_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `domain`, `destination`, `active`) VALUES (1, 1, 1, 'riud', 'riud', '', 1, 'test.int', 'till@test.int', '1');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_greylist`
-- 

DROP TABLE IF EXISTS `mail_greylist`;
CREATE TABLE `mail_greylist` (
  `greylist_id` int(11) NOT NULL auto_increment,
  `relay_ip` varchar(64) default NULL,
  `from_domain` varchar(255) default NULL,
  `block_expires` datetime NOT NULL default '0000-00-00 00:00:00',
  `record_expires` datetime NOT NULL default '0000-00-00 00:00:00',
  `origin_type` enum('MANUAL','AUTO') NOT NULL default 'AUTO',
  `create_time` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`greylist_id`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_greylist`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_mailman_domain`
-- 

DROP TABLE IF EXISTS `mail_mailman_domain`;
CREATE TABLE `mail_mailman_domain` (
  `mailman_id` int(11) NOT NULL auto_increment,
  `server_id` int(11) NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `mm_home` varchar(255) NOT NULL default '',
  `mm_wrap` varchar(255) NOT NULL default '',
  `mm_user` varchar(50) NOT NULL default '',
  `mm_group` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`mailman_id`,`server_id`,`domain`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_mailman_domain`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_redirect`
-- 

DROP TABLE IF EXISTS `mail_redirect`;
CREATE TABLE `mail_redirect` (
  `redirect_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `destination` varchar(255) NOT NULL default '',
  `type` enum('alias','forward') NOT NULL default 'alias',
  `active` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`redirect_id`),
  KEY `server_id` (`server_id`,`email`)
) TYPE=MyISAM AUTO_INCREMENT=4 ;

-- 
-- Daten für Tabelle `mail_redirect`
-- 

INSERT INTO `mail_redirect` (`redirect_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `email`, `destination`, `type`, `active`) VALUES (1, 1, 0, 'riud', 'riud', '', 1, 'tom@test.int', 'till@test.int', 'alias', '1');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_spamfilter`
-- 

DROP TABLE IF EXISTS `mail_spamfilter`;
CREATE TABLE `mail_spamfilter` (
  `spamfilter_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `spam_rewrite_score_int` int(11) NOT NULL default '0',
  `spam_delete_score_int` int(11) NOT NULL default '0',
  `spam_redirect_score_int` int(11) NOT NULL default '0',
  `spam_rewrite_subject` varchar(50) NOT NULL default '***SPAM***',
  `spam_redirect_maildir` varchar(255) NOT NULL default '',
  `active` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`spamfilter_id`),
  KEY `server_id` (`server_id`,`email`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

-- 
-- Daten für Tabelle `mail_spamfilter`
-- 

INSERT INTO `mail_spamfilter` (`spamfilter_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `email`, `spam_rewrite_score_int`, `spam_delete_score_int`, `spam_redirect_score_int`, `spam_rewrite_subject`, `spam_redirect_maildir`, `active`) VALUES (1, 0, 0, '', '', '', 1, 'till@test.int', 100, 100, 1, '***SPAM mag ich nicht***', '/var/spool/mail/spam', '1');

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_transport`
-- 

DROP TABLE IF EXISTS `mail_transport`;
CREATE TABLE `mail_transport` (
  `whitelist_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `destination` varchar(255) NOT NULL default '',
  `active` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`whitelist_id`),
  KEY `server_id` (`server_id`,`destination`),
  KEY `server_id_2` (`server_id`,`domain`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_transport`
-- 


-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `mail_whitelist`
-- 

DROP TABLE IF EXISTS `mail_whitelist`;
CREATE TABLE `mail_whitelist` (
  `whitelist_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) NOT NULL default '0',
  `address` varchar(255) NOT NULL default '',
  `active` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`whitelist_id`),
  KEY `server_id` (`server_id`,`address`)
) TYPE=MyISAM AUTO_INCREMENT=1 ;

-- 
-- Daten für Tabelle `mail_whitelist`
-- 

        


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `reseller`
--

DROP TABLE IF EXISTS `reseller`;
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
) TYPE=MyISAM AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `reseller`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `server`
--

DROP TABLE IF EXISTS `server`;
CREATE TABLE `server` (
  `server_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_name` varchar(255) NOT NULL default '',
  `mail_server` int(11) NOT NULL default '0',
  `web_server` int(11) NOT NULL default '0',
  `dns_server` int(11) NOT NULL default '0',
  `file_server` int(11) NOT NULL default '0',
  `mysql_server` int(11) NOT NULL default '0',
  `postgresql_server` int(11) NOT NULL default '0',
  `firebird_server` int(11) NOT NULL default '0',
  `active` int(11) NOT NULL default '1',
  PRIMARY KEY  (`server_id`)
) TYPE=MyISAM AUTO_INCREMENT=2 ;

--
-- Daten für Tabelle `server`
--

INSERT INTO `server` (`server_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_name`, `mail_server`, `web_server`, `dns_server`, `file_server`, `mysql_server`, `postgresql_server`, `firebird_server`, `active`) VALUES (1, 1, 1, 'riud', 'riud', '', 'Server 1', 1, 0, 0, 0, 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sys_datalog`
--

DROP TABLE IF EXISTS `sys_datalog`;
CREATE TABLE `sys_datalog` (
  `datalog_id` bigint(20) NOT NULL auto_increment,
  `dbtable` varchar(255) NOT NULL default '',
  `dbidx` varchar(255) NOT NULL default '',
  `action` char(1) NOT NULL default '',
  `tstamp` bigint(20) NOT NULL default '0',
  `user` varchar(255) NOT NULL default '',
  `data` text NOT NULL,
  PRIMARY KEY  (`datalog_id`)
) TYPE=MyISAM AUTO_INCREMENT=48 ;

--
-- Daten für Tabelle `sys_datalog`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sys_dbsync`
--

DROP TABLE IF EXISTS `sys_dbsync`;
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
) TYPE=MyISAM AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `sys_dbsync`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sys_filesync`
--

DROP TABLE IF EXISTS `sys_filesync`;
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
) TYPE=MyISAM AUTO_INCREMENT=1 ;

--
-- Daten für Tabelle `sys_filesync`
--


-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sys_group`
--

DROP TABLE IF EXISTS `sys_group`;
CREATE TABLE `sys_group` (
  `groupid` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`groupid`)
) TYPE=MyISAM AUTO_INCREMENT=3 ;

--
-- Daten für Tabelle `sys_group`
--

INSERT INTO `sys_group` (`groupid`, `name`, `description`) VALUES (1, 'admin', 'Administrators group');
INSERT INTO `sys_group` (`groupid`, `name`, `description`) VALUES (2, 'user', 'Users Group');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `sys_user`
--

DROP TABLE IF EXISTS `sys_user`;
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
) TYPE=MyISAM AUTO_INCREMENT=2 ;

--
-- Daten für Tabelle `sys_user`
--

INSERT INTO `sys_user` (`userid`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `username`, `passwort`, `modules`, `startmodule`, `app_theme`, `typ`, `active`, `name`, `vorname`, `unternehmen`, `strasse`, `ort`, `plz`, `land`, `email`, `url`, `telefon`, `fax`, `language`, `groups`, `default_group`) VALUES (1, 1, 0, 'riud', 'riud', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'admin,designer,sites,dns', 'admin', 'default', 'admin', 1, '', 'Administrator', '', '', '', '', '', '', '', '', '', 'en', '1,2', 0);

--
-- Table structure for table `rr`
--

DROP TABLE IF EXISTS `rr`;
CREATE TABLE `rr` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `zone` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL default '',
  `type` enum('A','AAAA','ALIAS','CNAME','HINFO','MX','NS','PTR','RP','SRV','TXT') default NULL,
  `data` varchar(128) NOT NULL default '',
  `aux` int(10) unsigned NOT NULL default '0',
  `ttl` int(10) unsigned NOT NULL default '86400',
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `rr` (`zone`,`name`,`type`,`data`)
) TYPE=MyISAM;

--
-- Dumping data for table `rr`
--


-- --------------------------------------------------------

--
-- Table structure for table `soa`
--

DROP TABLE IF EXISTS `soa`;
CREATE TABLE `soa` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `origin` varchar(255) NOT NULL default '',
  `ns` varchar(255) NOT NULL default '',
  `mbox` varchar(255) NOT NULL default '',
  `serial` int(10) unsigned NOT NULL default '1',
  `refresh` int(10) unsigned NOT NULL default '28800',
  `retry` int(10) unsigned NOT NULL default '7200',
  `expire` int(10) unsigned NOT NULL default '604800',
  `minimum` int(10) unsigned NOT NULL default '86400',
  `ttl` int(10) unsigned NOT NULL default '86400',
  `active` enum('Y','N') NOT NULL default 'Y',
  `xfer` varchar(255) NOT NULL default '',
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `origin` (`origin`),
  KEY `active` (`active`)
) TYPE=MyISAM;

--
-- Dumping data for table `soa`
--

-- --------------------------------------------------------