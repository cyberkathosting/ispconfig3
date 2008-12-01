
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

-- ISPConfig 3
-- DB-Version: 3.0.0.8

-- --------------------------------------------------------

-- 
-- Datenbank: `ispconfig_v3`
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
  `internet` varchar(255) NOT NULL,
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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Tabellenstruktur für Tabelle `dns_template`
-- 

CREATE TABLE `dns_template` (
  `template_id` bigint(20) NOT NULL auto_increment,
  `sys_userid` int(11) NOT NULL default '0',
  `sys_groupid` int(11) NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `name` varchar(255) default NULL,
  `fields` varchar(255) default NULL,
  `template` text,
  `visible` varchar(255) NOT NULL default 'Y',
  PRIMARY KEY  (`template_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 ;

-- 
-- Daten für Tabelle `dns_template`
-- 

INSERT INTO `dns_template` (`template_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `name`, `fields`, `template`, `visible`) VALUES (1, 1, 1, 'riud', 'riud', '', 'Default', 'DOMAIN,IP,NS1,NS2,EMAIL', '[ZONE]\norigin={DOMAIN}.\nns={NS1}.\nmbox={EMAIL}.\nrefresh=28800\nretry=7200\nexpire=604800\nminimum=86400\nttl=86400\n\n[DNS_RECORDS]\nA|{DOMAIN}.|{IP}|0|86400\nA|www|{IP}|0|86400\nA|mail|{IP}|0|86400\nNS|{DOMAIN}.|{NS1}.|0|86400\nNS|{DOMAIN}.|{NS2}.|0|86400\nMX|{DOMAIN}.|mail.{DOMAIN}.|10|86400', 'y');




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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=2 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
  `autoresponder_text` tinytext NULL,
  `custom_mailfilter` text,
  `postfix` enum('y','n') NOT NULL,
  `access` enum('y','n') NOT NULL,
  `disableimap` enum('0','1') NOT NULL default '0',
  `disablepop3` enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (`mailuser_id`),
  KEY `server_id` (`server_id`,`email`),
  KEY `email_access` (`email`,`access`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1;


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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=13 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=4 ;

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
) ENGINE=MyISAM AUTO_INCREMENT=3 ;

-- 
-- Daten für Tabelle `sys_user`
-- 

INSERT INTO `sys_user` (`userid`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `username`, `passwort`, `modules`, `startmodule`, `app_theme`, `typ`, `active`, `language`, `groups`, `default_group`, `client_id`) VALUES (1, 1, 0, 'riud', 'riud', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'admin,client,mail,monitor,sites,dns,tools,help', 'mail', 'default', 'admin', 1, 'en', '1,2', 1, 0);

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
  `errordocs` tinyint(1) NOT NULL default '1',
  `is_subdomainwww` tinyint(1) NOT NULL default '1',
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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;


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
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM;


-- 
-- Tabellenstruktur für Tabelle `monitor_data`
-- 

CREATE TABLE `monitor_data` (
  `server_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `created` int(11) NOT NULL,
  `data` mediumtext NOT NULL,
  `state` enum('no_state', 'unknown', 'ok', 'info', 'warning', 'critical', 'error') NOT NULL default 'unknown',
  PRIMARY KEY  (`server_id`,`type`,`created`)
) ENGINE=MyISAM;


-- 
-- Tabellenstruktur für Tabelle `sys_config`
-- 

CREATE TABLE `sys_config` (
  `config_id` int(11) NOT NULL,
  `group` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=MyISAM;

INSERT INTO sys_config VALUES ('1','db','db_version','3.0.0.8');


-- --------------------------------------------------------

-- 
-- iso_country_list.sql
-- 
-- This will create and then populate a MySQL table with a list of the names and
-- ISO 3166 codes for countries in existence as of the date below.
-- 
-- For updates to this file, see http://27.org/isocountrylist/
-- For more about ISO 3166, see http://www.iso.ch/iso/en/prods-services/iso3166ma/02iso-3166-code-lists/list-en1.html
-- 
-- Created by getisocountrylist.pl on Sun Nov  2 14:59:20 2003.
-- Wm. Rhodes <iso_country_list@27.org>
-- 

CREATE TABLE `country` (
  `iso` CHAR(2) NOT NULL PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL,
  `printable_name` VARCHAR(80) NOT NULL,
  `iso3` CHAR(3),
  `numcode` SMALLINT
) ENGINE=MyISAM;

INSERT INTO country VALUES ('AF','AFGHANISTAN','Afghanistan','AFG','004');
INSERT INTO country VALUES ('AL','ALBANIA','Albania','ALB','008');
INSERT INTO country VALUES ('DZ','ALGERIA','Algeria','DZA','012');
INSERT INTO country VALUES ('AS','AMERICAN SAMOA','American Samoa','ASM','016');
INSERT INTO country VALUES ('AD','ANDORRA','Andorra','AND','020');
INSERT INTO country VALUES ('AO','ANGOLA','Angola','AGO','024');
INSERT INTO country VALUES ('AI','ANGUILLA','Anguilla','AIA','660');
INSERT INTO country VALUES ('AQ','ANTARCTICA','Antarctica',NULL,NULL);
INSERT INTO country VALUES ('AG','ANTIGUA AND BARBUDA','Antigua and Barbuda','ATG','028');
INSERT INTO country VALUES ('AR','ARGENTINA','Argentina','ARG','032');
INSERT INTO country VALUES ('AM','ARMENIA','Armenia','ARM','051');
INSERT INTO country VALUES ('AW','ARUBA','Aruba','ABW','533');
INSERT INTO country VALUES ('AU','AUSTRALIA','Australia','AUS','036');
INSERT INTO country VALUES ('AT','AUSTRIA','Austria','AUT','040');
INSERT INTO country VALUES ('AZ','AZERBAIJAN','Azerbaijan','AZE','031');
INSERT INTO country VALUES ('BS','BAHAMAS','Bahamas','BHS','044');
INSERT INTO country VALUES ('BH','BAHRAIN','Bahrain','BHR','048');
INSERT INTO country VALUES ('BD','BANGLADESH','Bangladesh','BGD','050');
INSERT INTO country VALUES ('BB','BARBADOS','Barbados','BRB','052');
INSERT INTO country VALUES ('BY','BELARUS','Belarus','BLR','112');
INSERT INTO country VALUES ('BE','BELGIUM','Belgium','BEL','056');
INSERT INTO country VALUES ('BZ','BELIZE','Belize','BLZ','084');
INSERT INTO country VALUES ('BJ','BENIN','Benin','BEN','204');
INSERT INTO country VALUES ('BM','BERMUDA','Bermuda','BMU','060');
INSERT INTO country VALUES ('BT','BHUTAN','Bhutan','BTN','064');
INSERT INTO country VALUES ('BO','BOLIVIA','Bolivia','BOL','068');
INSERT INTO country VALUES ('BA','BOSNIA AND HERZEGOVINA','Bosnia and Herzegovina','BIH','070');
INSERT INTO country VALUES ('BW','BOTSWANA','Botswana','BWA','072');
INSERT INTO country VALUES ('BV','BOUVET ISLAND','Bouvet Island',NULL,NULL);
INSERT INTO country VALUES ('BR','BRAZIL','Brazil','BRA','076');
INSERT INTO country VALUES ('IO','BRITISH INDIAN OCEAN TERRITORY','British Indian Ocean Territory',NULL,NULL);
INSERT INTO country VALUES ('BN','BRUNEI DARUSSALAM','Brunei Darussalam','BRN','096');
INSERT INTO country VALUES ('BG','BULGARIA','Bulgaria','BGR','100');
INSERT INTO country VALUES ('BF','BURKINA FASO','Burkina Faso','BFA','854');
INSERT INTO country VALUES ('BI','BURUNDI','Burundi','BDI','108');
INSERT INTO country VALUES ('KH','CAMBODIA','Cambodia','KHM','116');
INSERT INTO country VALUES ('CM','CAMEROON','Cameroon','CMR','120');
INSERT INTO country VALUES ('CA','CANADA','Canada','CAN','124');
INSERT INTO country VALUES ('CV','CAPE VERDE','Cape Verde','CPV','132');
INSERT INTO country VALUES ('KY','CAYMAN ISLANDS','Cayman Islands','CYM','136');
INSERT INTO country VALUES ('CF','CENTRAL AFRICAN REPUBLIC','Central African Republic','CAF','140');
INSERT INTO country VALUES ('TD','CHAD','Chad','TCD','148');
INSERT INTO country VALUES ('CL','CHILE','Chile','CHL','152');
INSERT INTO country VALUES ('CN','CHINA','China','CHN','156');
INSERT INTO country VALUES ('CX','CHRISTMAS ISLAND','Christmas Island',NULL,NULL);
INSERT INTO country VALUES ('CC','COCOS (KEELING) ISLANDS','Cocos (Keeling) Islands',NULL,NULL);
INSERT INTO country VALUES ('CO','COLOMBIA','Colombia','COL','170');
INSERT INTO country VALUES ('KM','COMOROS','Comoros','COM','174');
INSERT INTO country VALUES ('CG','CONGO','Congo','COG','178');
INSERT INTO country VALUES ('CD','CONGO, THE DEMOCRATIC REPUBLIC OF THE','Congo, the Democratic Republic of the','COD','180');
INSERT INTO country VALUES ('CK','COOK ISLANDS','Cook Islands','COK','184');
INSERT INTO country VALUES ('CR','COSTA RICA','Costa Rica','CRI','188');
INSERT INTO country VALUES ('CI','COTE D\'IVOIRE','Cote D\'Ivoire','CIV','384');
INSERT INTO country VALUES ('HR','CROATIA','Croatia','HRV','191');
INSERT INTO country VALUES ('CU','CUBA','Cuba','CUB','192');
INSERT INTO country VALUES ('CY','CYPRUS','Cyprus','CYP','196');
INSERT INTO country VALUES ('CZ','CZECH REPUBLIC','Czech Republic','CZE','203');
INSERT INTO country VALUES ('DK','DENMARK','Denmark','DNK','208');
INSERT INTO country VALUES ('DJ','DJIBOUTI','Djibouti','DJI','262');
INSERT INTO country VALUES ('DM','DOMINICA','Dominica','DMA','212');
INSERT INTO country VALUES ('DO','DOMINICAN REPUBLIC','Dominican Republic','DOM','214');
INSERT INTO country VALUES ('EC','ECUADOR','Ecuador','ECU','218');
INSERT INTO country VALUES ('EG','EGYPT','Egypt','EGY','818');
INSERT INTO country VALUES ('SV','EL SALVADOR','El Salvador','SLV','222');
INSERT INTO country VALUES ('GQ','EQUATORIAL GUINEA','Equatorial Guinea','GNQ','226');
INSERT INTO country VALUES ('ER','ERITREA','Eritrea','ERI','232');
INSERT INTO country VALUES ('EE','ESTONIA','Estonia','EST','233');
INSERT INTO country VALUES ('ET','ETHIOPIA','Ethiopia','ETH','231');
INSERT INTO country VALUES ('FK','FALKLAND ISLANDS (MALVINAS)','Falkland Islands (Malvinas)','FLK','238');
INSERT INTO country VALUES ('FO','FAROE ISLANDS','Faroe Islands','FRO','234');
INSERT INTO country VALUES ('FJ','FIJI','Fiji','FJI','242');
INSERT INTO country VALUES ('FI','FINLAND','Finland','FIN','246');
INSERT INTO country VALUES ('FR','FRANCE','France','FRA','250');
INSERT INTO country VALUES ('GF','FRENCH GUIANA','French Guiana','GUF','254');
INSERT INTO country VALUES ('PF','FRENCH POLYNESIA','French Polynesia','PYF','258');
INSERT INTO country VALUES ('TF','FRENCH SOUTHERN TERRITORIES','French Southern Territories',NULL,NULL);
INSERT INTO country VALUES ('GA','GABON','Gabon','GAB','266');
INSERT INTO country VALUES ('GM','GAMBIA','Gambia','GMB','270');
INSERT INTO country VALUES ('GE','GEORGIA','Georgia','GEO','268');
INSERT INTO country VALUES ('DE','GERMANY','Germany','DEU','276');
INSERT INTO country VALUES ('GH','GHANA','Ghana','GHA','288');
INSERT INTO country VALUES ('GI','GIBRALTAR','Gibraltar','GIB','292');
INSERT INTO country VALUES ('GR','GREECE','Greece','GRC','300');
INSERT INTO country VALUES ('GL','GREENLAND','Greenland','GRL','304');
INSERT INTO country VALUES ('GD','GRENADA','Grenada','GRD','308');
INSERT INTO country VALUES ('GP','GUADELOUPE','Guadeloupe','GLP','312');
INSERT INTO country VALUES ('GU','GUAM','Guam','GUM','316');
INSERT INTO country VALUES ('GT','GUATEMALA','Guatemala','GTM','320');
INSERT INTO country VALUES ('GN','GUINEA','Guinea','GIN','324');
INSERT INTO country VALUES ('GW','GUINEA-BISSAU','Guinea-Bissau','GNB','624');
INSERT INTO country VALUES ('GY','GUYANA','Guyana','GUY','328');
INSERT INTO country VALUES ('HT','HAITI','Haiti','HTI','332');
INSERT INTO country VALUES ('HM','HEARD ISLAND AND MCDONALD ISLANDS','Heard Island and Mcdonald Islands',NULL,NULL);
INSERT INTO country VALUES ('VA','HOLY SEE (VATICAN CITY STATE)','Holy See (Vatican City State)','VAT','336');
INSERT INTO country VALUES ('HN','HONDURAS','Honduras','HND','340');
INSERT INTO country VALUES ('HK','HONG KONG','Hong Kong','HKG','344');
INSERT INTO country VALUES ('HU','HUNGARY','Hungary','HUN','348');
INSERT INTO country VALUES ('IS','ICELAND','Iceland','ISL','352');
INSERT INTO country VALUES ('IN','INDIA','India','IND','356');
INSERT INTO country VALUES ('ID','INDONESIA','Indonesia','IDN','360');
INSERT INTO country VALUES ('IR','IRAN, ISLAMIC REPUBLIC OF','Iran, Islamic Republic of','IRN','364');
INSERT INTO country VALUES ('IQ','IRAQ','Iraq','IRQ','368');
INSERT INTO country VALUES ('IE','IRELAND','Ireland','IRL','372');
INSERT INTO country VALUES ('IL','ISRAEL','Israel','ISR','376');
INSERT INTO country VALUES ('IT','ITALY','Italy','ITA','380');
INSERT INTO country VALUES ('JM','JAMAICA','Jamaica','JAM','388');
INSERT INTO country VALUES ('JP','JAPAN','Japan','JPN','392');
INSERT INTO country VALUES ('JO','JORDAN','Jordan','JOR','400');
INSERT INTO country VALUES ('KZ','KAZAKHSTAN','Kazakhstan','KAZ','398');
INSERT INTO country VALUES ('KE','KENYA','Kenya','KEN','404');
INSERT INTO country VALUES ('KI','KIRIBATI','Kiribati','KIR','296');
INSERT INTO country VALUES ('KP','KOREA, DEMOCRATIC PEOPLE\'S REPUBLIC OF','Korea, Democratic People\'s Republic of','PRK','408');
INSERT INTO country VALUES ('KR','KOREA, REPUBLIC OF','Korea, Republic of','KOR','410');
INSERT INTO country VALUES ('KW','KUWAIT','Kuwait','KWT','414');
INSERT INTO country VALUES ('KG','KYRGYZSTAN','Kyrgyzstan','KGZ','417');
INSERT INTO country VALUES ('LA','LAO PEOPLE\'S DEMOCRATIC REPUBLIC','Lao People\'s Democratic Republic','LAO','418');
INSERT INTO country VALUES ('LV','LATVIA','Latvia','LVA','428');
INSERT INTO country VALUES ('LB','LEBANON','Lebanon','LBN','422');
INSERT INTO country VALUES ('LS','LESOTHO','Lesotho','LSO','426');
INSERT INTO country VALUES ('LR','LIBERIA','Liberia','LBR','430');
INSERT INTO country VALUES ('LY','LIBYAN ARAB JAMAHIRIYA','Libyan Arab Jamahiriya','LBY','434');
INSERT INTO country VALUES ('LI','LIECHTENSTEIN','Liechtenstein','LIE','438');
INSERT INTO country VALUES ('LT','LITHUANIA','Lithuania','LTU','440');
INSERT INTO country VALUES ('LU','LUXEMBOURG','Luxembourg','LUX','442');
INSERT INTO country VALUES ('MO','MACAO','Macao','MAC','446');
INSERT INTO country VALUES ('MK','MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF','Macedonia, the Former Yugoslav Republic of','MKD','807');
INSERT INTO country VALUES ('MG','MADAGASCAR','Madagascar','MDG','450');
INSERT INTO country VALUES ('MW','MALAWI','Malawi','MWI','454');
INSERT INTO country VALUES ('MY','MALAYSIA','Malaysia','MYS','458');
INSERT INTO country VALUES ('MV','MALDIVES','Maldives','MDV','462');
INSERT INTO country VALUES ('ML','MALI','Mali','MLI','466');
INSERT INTO country VALUES ('MT','MALTA','Malta','MLT','470');
INSERT INTO country VALUES ('MH','MARSHALL ISLANDS','Marshall Islands','MHL','584');
INSERT INTO country VALUES ('MQ','MARTINIQUE','Martinique','MTQ','474');
INSERT INTO country VALUES ('MR','MAURITANIA','Mauritania','MRT','478');
INSERT INTO country VALUES ('MU','MAURITIUS','Mauritius','MUS','480');
INSERT INTO country VALUES ('YT','MAYOTTE','Mayotte',NULL,NULL);
INSERT INTO country VALUES ('MX','MEXICO','Mexico','MEX','484');
INSERT INTO country VALUES ('FM','MICRONESIA, FEDERATED STATES OF','Micronesia, Federated States of','FSM','583');
INSERT INTO country VALUES ('MD','MOLDOVA, REPUBLIC OF','Moldova, Republic of','MDA','498');
INSERT INTO country VALUES ('MC','MONACO','Monaco','MCO','492');
INSERT INTO country VALUES ('MN','MONGOLIA','Mongolia','MNG','496');
INSERT INTO country VALUES ('MS','MONTSERRAT','Montserrat','MSR','500');
INSERT INTO country VALUES ('MA','MOROCCO','Morocco','MAR','504');
INSERT INTO country VALUES ('MZ','MOZAMBIQUE','Mozambique','MOZ','508');
INSERT INTO country VALUES ('MM','MYANMAR','Myanmar','MMR','104');
INSERT INTO country VALUES ('NA','NAMIBIA','Namibia','NAM','516');
INSERT INTO country VALUES ('NR','NAURU','Nauru','NRU','520');
INSERT INTO country VALUES ('NP','NEPAL','Nepal','NPL','524');
INSERT INTO country VALUES ('NL','NETHERLANDS','Netherlands','NLD','528');
INSERT INTO country VALUES ('AN','NETHERLANDS ANTILLES','Netherlands Antilles','ANT','530');
INSERT INTO country VALUES ('NC','NEW CALEDONIA','New Caledonia','NCL','540');
INSERT INTO country VALUES ('NZ','NEW ZEALAND','New Zealand','NZL','554');
INSERT INTO country VALUES ('NI','NICARAGUA','Nicaragua','NIC','558');
INSERT INTO country VALUES ('NE','NIGER','Niger','NER','562');
INSERT INTO country VALUES ('NG','NIGERIA','Nigeria','NGA','566');
INSERT INTO country VALUES ('NU','NIUE','Niue','NIU','570');
INSERT INTO country VALUES ('NF','NORFOLK ISLAND','Norfolk Island','NFK','574');
INSERT INTO country VALUES ('MP','NORTHERN MARIANA ISLANDS','Northern Mariana Islands','MNP','580');
INSERT INTO country VALUES ('NO','NORWAY','Norway','NOR','578');
INSERT INTO country VALUES ('OM','OMAN','Oman','OMN','512');
INSERT INTO country VALUES ('PK','PAKISTAN','Pakistan','PAK','586');
INSERT INTO country VALUES ('PW','PALAU','Palau','PLW','585');
INSERT INTO country VALUES ('PS','PALESTINIAN TERRITORY, OCCUPIED','Palestinian Territory, Occupied',NULL,NULL);
INSERT INTO country VALUES ('PA','PANAMA','Panama','PAN','591');
INSERT INTO country VALUES ('PG','PAPUA NEW GUINEA','Papua New Guinea','PNG','598');
INSERT INTO country VALUES ('PY','PARAGUAY','Paraguay','PRY','600');
INSERT INTO country VALUES ('PE','PERU','Peru','PER','604');
INSERT INTO country VALUES ('PH','PHILIPPINES','Philippines','PHL','608');
INSERT INTO country VALUES ('PN','PITCAIRN','Pitcairn','PCN','612');
INSERT INTO country VALUES ('PL','POLAND','Poland','POL','616');
INSERT INTO country VALUES ('PT','PORTUGAL','Portugal','PRT','620');
INSERT INTO country VALUES ('PR','PUERTO RICO','Puerto Rico','PRI','630');
INSERT INTO country VALUES ('QA','QATAR','Qatar','QAT','634');
INSERT INTO country VALUES ('RE','REUNION','Reunion','REU','638');
INSERT INTO country VALUES ('RO','ROMANIA','Romania','ROM','642');
INSERT INTO country VALUES ('RU','RUSSIAN FEDERATION','Russian Federation','RUS','643');
INSERT INTO country VALUES ('RW','RWANDA','Rwanda','RWA','646');
INSERT INTO country VALUES ('SH','SAINT HELENA','Saint Helena','SHN','654');
INSERT INTO country VALUES ('KN','SAINT KITTS AND NEVIS','Saint Kitts and Nevis','KNA','659');
INSERT INTO country VALUES ('LC','SAINT LUCIA','Saint Lucia','LCA','662');
INSERT INTO country VALUES ('PM','SAINT PIERRE AND MIQUELON','Saint Pierre and Miquelon','SPM','666');
INSERT INTO country VALUES ('VC','SAINT VINCENT AND THE GRENADINES','Saint Vincent and the Grenadines','VCT','670');
INSERT INTO country VALUES ('WS','SAMOA','Samoa','WSM','882');
INSERT INTO country VALUES ('SM','SAN MARINO','San Marino','SMR','674');
INSERT INTO country VALUES ('ST','SAO TOME AND PRINCIPE','Sao Tome and Principe','STP','678');
INSERT INTO country VALUES ('SA','SAUDI ARABIA','Saudi Arabia','SAU','682');
INSERT INTO country VALUES ('SN','SENEGAL','Senegal','SEN','686');
INSERT INTO country VALUES ('CS','SERBIA AND MONTENEGRO','Serbia and Montenegro',NULL,NULL);
INSERT INTO country VALUES ('SC','SEYCHELLES','Seychelles','SYC','690');
INSERT INTO country VALUES ('SL','SIERRA LEONE','Sierra Leone','SLE','694');
INSERT INTO country VALUES ('SG','SINGAPORE','Singapore','SGP','702');
INSERT INTO country VALUES ('SK','SLOVAKIA','Slovakia','SVK','703');
INSERT INTO country VALUES ('SI','SLOVENIA','Slovenia','SVN','705');
INSERT INTO country VALUES ('SB','SOLOMON ISLANDS','Solomon Islands','SLB','090');
INSERT INTO country VALUES ('SO','SOMALIA','Somalia','SOM','706');
INSERT INTO country VALUES ('ZA','SOUTH AFRICA','South Africa','ZAF','710');
INSERT INTO country VALUES ('GS','SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS','South Georgia and the South Sandwich Islands',NULL,NULL);
INSERT INTO country VALUES ('ES','SPAIN','Spain','ESP','724');
INSERT INTO country VALUES ('LK','SRI LANKA','Sri Lanka','LKA','144');
INSERT INTO country VALUES ('SD','SUDAN','Sudan','SDN','736');
INSERT INTO country VALUES ('SR','SURINAME','Suriname','SUR','740');
INSERT INTO country VALUES ('SJ','SVALBARD AND JAN MAYEN','Svalbard and Jan Mayen','SJM','744');
INSERT INTO country VALUES ('SZ','SWAZILAND','Swaziland','SWZ','748');
INSERT INTO country VALUES ('SE','SWEDEN','Sweden','SWE','752');
INSERT INTO country VALUES ('CH','SWITZERLAND','Switzerland','CHE','756');
INSERT INTO country VALUES ('SY','SYRIAN ARAB REPUBLIC','Syrian Arab Republic','SYR','760');
INSERT INTO country VALUES ('TW','TAIWAN, PROVINCE OF CHINA','Taiwan, Province of China','TWN','158');
INSERT INTO country VALUES ('TJ','TAJIKISTAN','Tajikistan','TJK','762');
INSERT INTO country VALUES ('TZ','TANZANIA, UNITED REPUBLIC OF','Tanzania, United Republic of','TZA','834');
INSERT INTO country VALUES ('TH','THAILAND','Thailand','THA','764');
INSERT INTO country VALUES ('TL','TIMOR-LESTE','Timor-Leste',NULL,NULL);
INSERT INTO country VALUES ('TG','TOGO','Togo','TGO','768');
INSERT INTO country VALUES ('TK','TOKELAU','Tokelau','TKL','772');
INSERT INTO country VALUES ('TO','TONGA','Tonga','TON','776');
INSERT INTO country VALUES ('TT','TRINIDAD AND TOBAGO','Trinidad and Tobago','TTO','780');
INSERT INTO country VALUES ('TN','TUNISIA','Tunisia','TUN','788');
INSERT INTO country VALUES ('TR','TURKEY','Turkey','TUR','792');
INSERT INTO country VALUES ('TM','TURKMENISTAN','Turkmenistan','TKM','795');
INSERT INTO country VALUES ('TC','TURKS AND CAICOS ISLANDS','Turks and Caicos Islands','TCA','796');
INSERT INTO country VALUES ('TV','TUVALU','Tuvalu','TUV','798');
INSERT INTO country VALUES ('UG','UGANDA','Uganda','UGA','800');
INSERT INTO country VALUES ('UA','UKRAINE','Ukraine','UKR','804');
INSERT INTO country VALUES ('AE','UNITED ARAB EMIRATES','United Arab Emirates','ARE','784');
INSERT INTO country VALUES ('GB','UNITED KINGDOM','United Kingdom','GBR','826');
INSERT INTO country VALUES ('US','UNITED STATES','United States','USA','840');
INSERT INTO country VALUES ('UM','UNITED STATES MINOR OUTLYING ISLANDS','United States Minor Outlying Islands',NULL,NULL);
INSERT INTO country VALUES ('UY','URUGUAY','Uruguay','URY','858');
INSERT INTO country VALUES ('UZ','UZBEKISTAN','Uzbekistan','UZB','860');
INSERT INTO country VALUES ('VU','VANUATU','Vanuatu','VUT','548');
INSERT INTO country VALUES ('VE','VENEZUELA','Venezuela','VEN','862');
INSERT INTO country VALUES ('VN','VIET NAM','Viet Nam','VNM','704');
INSERT INTO country VALUES ('VG','VIRGIN ISLANDS, BRITISH','Virgin Islands, British','VGB','092');
INSERT INTO country VALUES ('VI','VIRGIN ISLANDS, U.S.','Virgin Islands, U.s.','VIR','850');
INSERT INTO country VALUES ('WF','WALLIS AND FUTUNA','Wallis and Futuna','WLF','876');
INSERT INTO country VALUES ('EH','WESTERN SAHARA','Western Sahara','ESH','732');
INSERT INTO country VALUES ('YE','YEMEN','Yemen','YEM','887');
INSERT INTO country VALUES ('ZM','ZAMBIA','Zambia','ZMB','894');
INSERT INTO country VALUES ('ZW','ZIMBABWE','Zimbabwe','ZWE','716');

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;
