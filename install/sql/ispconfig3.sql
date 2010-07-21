
/*
Copyright (c) 2007-2010, Till Brehm, projektfarm Gmbh
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

-- 
-- ISPConfig 3
-- DB-Version: 3.0.0.9
-- 

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- --------------------------------------------------------
-- DB-STRUCTURE
-- --------------------------------------------------------
-- --------------------------------------------------------

-- 
-- Table structure for table  `client`
-- 

CREATE TABLE `client` (
  `client_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `company_name` varchar(64) default NULL,
  `contact_name` varchar(64) default NULL,
  `street` varchar(255) default NULL,
  `zip` varchar(32) default NULL,
  `city` varchar(64) default NULL,
  `state` varchar(32) default NULL,
  `country` char(2) default NULL,
  `telephone` varchar(32) default NULL,
  `mobile` varchar(32) default NULL,
  `fax` varchar(32) default NULL,
  `email` varchar(255) default NULL,
  `internet` varchar(255) NOT NULL,
  `icq` varchar(16) default NULL,
  `notes` text,
  `default_mailserver` int(11) unsigned NOT NULL default '1',
  `limit_maildomain` int(11) NOT NULL default '-1',
  `limit_mailbox` int(11) NOT NULL default '-1',
  `limit_mailalias` int(11) NOT NULL default '-1',
  `limit_mailaliasdomain` int(11) NOT NULL default '-1',
  `limit_mailforward` int(11) NOT NULL default '-1',
  `limit_mailcatchall` int(11) NOT NULL default '-1',
  `limit_mailrouting` int(11) NOT NULL default '0',
  `limit_mailfilter` int(11) NOT NULL default '-1',
  `limit_fetchmail` int(11) NOT NULL default '-1',
  `limit_mailquota` int(11) NOT NULL default '-1',
  `limit_spamfilter_wblist` int(11) NOT NULL default '0',
  `limit_spamfilter_user` int(11) NOT NULL default '0',
  `limit_spamfilter_policy` int(11) NOT NULL default '0',
  `default_webserver` int(11) unsigned NOT NULL default '1',
  `limit_web_ip` text,
  `limit_web_domain` int(11) NOT NULL default '-1',
  `limit_web_quota` int(11) NOT NULL default '-1',
  `web_php_options` varchar(255) NOT NULL default 'no,fast-cgi,cgi,mod,suphp',
  `limit_web_subdomain` int(11) NOT NULL default '-1',
  `limit_web_aliasdomain` int(11) NOT NULL default '-1',
  `limit_ftp_user` int(11) NOT NULL default '-1',
  `limit_shell_user` int(11) NOT NULL default '0',
  `ssh_chroot` varchar(255) NOT NULL DEFAULT 'no,jailkit,ssh-chroot',
  `limit_webdav_user` int(11) NOT NULL default '0',
  `default_dnsserver` int(11) unsigned NOT NULL default '1',
  `limit_dns_zone` int(11) NOT NULL default '-1',
  `limit_dns_slave_zone` int(11) NOT NULL default '-1',
  `limit_dns_record` int(11) NOT NULL default '-1',
  `default_dbserver` int(11) NOT NULL default '1',
  `limit_database` int(11) NOT NULL default '-1',
  `limit_cron` int(11) NOT NULL default '0',
  `limit_cron_type` enum('url','chrooted','full') NOT NULL default 'url',
  `limit_cron_frequency` int(11) NOT NULL default '5',
  `limit_traffic_quota` int(11) NOT NULL default '-1',
  `limit_client` int(11) NOT NULL default '0',
  `parent_client_id` int(11) unsigned NOT NULL default '0',
  `username` varchar(64) default NULL,
  `password` varchar(64) default NULL,
  `language` char(2) NOT NULL default 'en',
  `usertheme` varchar(32) NOT NULL default 'default',
  `template_master` int(11) unsigned NOT NULL default '0',
  `template_additional` varchar(255) NOT NULL default '',
  `created_at` bigint(20) DEFAULT NULL,
  PRIMARY KEY  (`client_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `client_template`
-- 

CREATE TABLE `client_template` (
  `template_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,  
  `template_name` varchar(64) NOT NULL,
  `template_type` varchar(1) NOT NULL default 'm',
  `limit_maildomain` int(11) NOT NULL default '-1',
  `limit_mailbox` int(11) NOT NULL default '-1',
  `limit_mailalias` int(11) NOT NULL default '-1',
  `limit_mailaliasdomain` int(11) NOT NULL default '-1',
  `limit_mailforward` int(11) NOT NULL default '-1',
  `limit_mailcatchall` int(11) NOT NULL default '-1',
  `limit_mailrouting` int(11) NOT NULL default '0',
  `limit_mailfilter` int(11) NOT NULL default '-1',
  `limit_fetchmail` int(11) NOT NULL default '-1',
  `limit_mailquota` int(11) NOT NULL default '-1',
  `limit_spamfilter_wblist` int(11) NOT NULL default '0',
  `limit_spamfilter_user` int(11) NOT NULL default '0',
  `limit_spamfilter_policy` int(11) NOT NULL default '0',
  `limit_web_ip` text,
  `limit_web_domain` int(11) NOT NULL default '-1',
  `limit_web_quota` int(11) NOT NULL default '-1',
  `limit_web_subdomain` int(11) NOT NULL default '-1',
  `limit_web_aliasdomain` int(11) NOT NULL default '-1',
  `limit_ftp_user` int(11) NOT NULL default '-1',
  `limit_shell_user` int(11) NOT NULL default '0',
  `limit_webdav_user` int(11) NOT NULL default '0',
  `limit_dns_zone` int(11) NOT NULL default '-1',
  `limit_dns_slave_zone` int(11) NOT NULL default '-1',
  `limit_dns_record` int(11) NOT NULL default '-1',
  `limit_database` int(11) NOT NULL default '-1',
  `limit_cron` int(11) NOT NULL default '0',
  `limit_cron_type` enum('url','chrooted','full') NOT NULL default 'url',
  `limit_cron_frequency` int(11) NOT NULL default '5',
  `limit_traffic_quota` int(11) NOT NULL default '-1',
  `limit_client` int(11) NOT NULL default '0',
  PRIMARY KEY  (`template_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;


-- --------------------------------------------------------

-- 
-- Table structure for table  `cron`
-- 
CREATE TABLE `cron` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NULL default NULL,
  `sys_perm_group` varchar(5) NULL default NULL,
  `sys_perm_other` varchar(5) NULL default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `parent_domain_id` int(11) unsigned NOT NULL default '0',
  `type` enum('url','chrooted','full') NOT NULL default 'url',
  `command` varchar(255) NOT NULL,
  `run_min` varchar(100) NULL,
  `run_hour` varchar(100) NULL,
  `run_mday` varchar(100) NULL,
  `run_month` varchar(100) NULL,
  `run_wday` varchar(100) NULL,
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  AUTO_INCREMENT=1;


-- --------------------------------------------------------

-- 
-- Table structure for table  `dns_rr`
-- 

CREATE TABLE `dns_rr` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL,
  `sys_groupid` int(11) unsigned NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(11) NOT NULL default '1',
  `zone` int(11) unsigned NOT NULL,
  `name` varchar(64) NOT NULL,
  `type` enum('A','AAAA','ALIAS','CNAME','HINFO','MX','NAPTR','NS','PTR','RP','SRV','TXT') default NULL,
  `data` varchar(255) NOT NULL,
  `aux` int(11) unsigned NOT NULL default '0',
  `ttl` int(11) unsigned NOT NULL default '86400',
  `active` enum('N','Y') NOT NULL default 'Y',
  `stamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `serial` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `rr` (`zone`,`name`,`type`,`data`)
) ENGINE=MyISAM  AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `dns_soa`
-- 

CREATE TABLE `dns_soa` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL,
  `sys_groupid` int(11) unsigned NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(11) NOT NULL default '1',
  `origin` varchar(255) NOT NULL,
  `ns` varchar(255) NOT NULL,
  `mbox` varchar(255) NOT NULL,
  `serial` int(11) unsigned NOT NULL default '1',
  `refresh` int(11) unsigned NOT NULL default '28800',
  `retry` int(11) unsigned NOT NULL default '7200',
  `expire` int(11) unsigned NOT NULL default '604800',
  `minimum` int(11) unsigned NOT NULL default '86400',
  `ttl` int(11) unsigned NOT NULL default '86400',
  `active` enum('N','Y') NOT NULL,
  `xfer` varchar(255) NOT NULL,
  `also_notify` varchar(255) default NULL,
  `update_acl` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `origin` (`origin`),
  KEY `active` (`active`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for table  `dns_slave`
--

CREATE TABLE `dns_slave` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL,
  `sys_groupid` int(11) unsigned NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(11) NOT NULL default '1',
  `origin` varchar(255) NOT NULL,
  `ns` varchar(255) NOT NULL,
  `active` enum('N','Y') NOT NULL,
  `xfer` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `origin` (`origin`),
  KEY `active` (`active`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `dns_template`
-- 

CREATE TABLE `dns_template` (
  `template_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `name` varchar(64) default NULL,
  `fields` varchar(255) default NULL,
  `template` text,
  `visible` enum('N','Y') NOT NULL default 'Y',
  PRIMARY KEY  (`template_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

--
-- Tabellenstruktur für Tabelle `domain`
--

CREATE TABLE `domain` (
  `domain_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `domain` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`domain_id`),
  UNIQUE KEY `domain` (`domain`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table  `firewall`
-- 

CREATE TABLE `firewall` (
  `firewall_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `tcp_port` varchar(255) default NULL,
  `udp_port` varchar(255) default NULL,
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`firewall_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `ftp_user`
-- 

CREATE TABLE `ftp_user` (
  `ftp_user_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `parent_domain_id` int(11) unsigned NOT NULL default '0',
  `username` varchar(64) default NULL,
  `password` varchar(64) default NULL,
  `quota_size` bigint(20) NOT NULL default '-1',
  `active` enum('n','y') NOT NULL default 'y',
  `uid` varchar(64) default NULL,
  `gid` varchar(64) default NULL,
  `dir` varchar(255) default NULL,
  `quota_files` bigint(20) NOT NULL default '-1',
  `ul_ratio` int(11) NOT NULL default '-1',
  `dl_ratio` int(11) NOT NULL default '-1',
  `ul_bandwidth` int(11) NOT NULL default '-1',
  `dl_bandwidth` int(11) NOT NULL default '-1',
  PRIMARY KEY  (`ftp_user_id`),
  KEY `active` (`active`),
  KEY `server_id` (`server_id`),
  KEY `username` (`username`),
  KEY `quota_files` (`quota_files`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_access`
-- 

CREATE TABLE `mail_access` (
  `access_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
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
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_content_filter`
-- 

CREATE TABLE `mail_content_filter` (
  `content_filter_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
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
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_domain`
-- 

CREATE TABLE `mail_domain` (
  `domain_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) unsigned NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `active` enum('n','y') NOT NULL,
  PRIMARY KEY  (`domain_id`),
  KEY `server_id` (`server_id`,`domain`),
  KEY `domain_active` (`domain`,`active`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_forwarding`
-- 

CREATE TABLE `mail_forwarding` (
  `forwarding_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) unsigned NOT NULL default '0',
  `source` varchar(255) NOT NULL,
  `destination` text,
  `type` enum('alias','aliasdomain','forward','catchall') NOT NULL default 'alias',
  `active` enum('n','y') NOT NULL,
  PRIMARY KEY  (`forwarding_id`),
  KEY `server_id` (`server_id`,`source`),
  KEY `type` (`type`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_get`
-- 

CREATE TABLE `mail_get` (
  `mailget_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `type` varchar(255) default NULL,
  `source_server` varchar(255) default NULL,
  `source_username` varchar(255) default NULL,
  `source_password` varchar(64) default NULL,
  `source_delete` varchar(255) NOT NULL default 'y',
  `destination` varchar(255) default NULL,
  `active` varchar(255) NOT NULL default 'y',
  PRIMARY KEY  (`mailget_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_greylist`
-- 

CREATE TABLE `mail_greylist` (
  `greylist_id` int(11) unsigned NOT NULL auto_increment,
  `relay_ip` varchar(39) default NULL,
  `from_domain` varchar(255) default NULL,
  `block_expires` datetime NOT NULL default '0000-00-00 00:00:00',
  `record_expires` datetime NOT NULL default '0000-00-00 00:00:00',
  `origin_type` enum('MANUAL','AUTO') NOT NULL default 'AUTO',
  `create_time` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`greylist_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_mailman_domain`
-- 

CREATE TABLE `mail_mailman_domain` (
  `mailman_id` int(11) unsigned NOT NULL auto_increment,
  `server_id` int(11) unsigned NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `mm_home` varchar(255) NOT NULL default '',
  `mm_wrap` varchar(255) NOT NULL default '',
  `mm_user` varchar(50) NOT NULL default '',
  `mm_group` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`mailman_id`,`server_id`,`domain`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Table structure for Table `mail_relay_recipient`
--

CREATE TABLE IF NOT EXISTS `mail_relay_recipient` (
  `relay_recipient_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `source` varchar(255) DEFAULT NULL,
  `access` varchar(255) NOT NULL DEFAULT 'OK',
  `active` varchar(255) NOT NULL DEFAULT 'y',
  PRIMARY KEY (`relay_recipient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_traffic`
-- 

CREATE TABLE `mail_traffic` (
  `traffic_id` int(11) unsigned NOT NULL auto_increment,
  `mailuser_id` int(11) unsigned NOT NULL,
  `month` char(7) NOT NULL,
  `traffic` bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (`traffic_id`),
  KEY `mailuser_id` (`mailuser_id`,`month`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_transport`
-- 

CREATE TABLE `mail_transport` (
  `transport_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) unsigned NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',
  `transport` varchar(255) NOT NULL,
  `sort_order` int(11) unsigned NOT NULL default '5',
  `active` enum('n','y') NOT NULL,
  PRIMARY KEY  (`transport_id`),
  KEY `server_id` (`server_id`,`transport`),
  KEY `server_id_2` (`server_id`,`domain`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_user`
-- 

CREATE TABLE `mail_user` (
  `mailuser_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) unsigned NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL default '',
  `uid` int(11) unsigned NOT NULL default '5000',
  `gid` int(11) unsigned NOT NULL default '5000',
  `maildir` varchar(255) NOT NULL default '',
  `quota` bigint(20) NOT NULL default '-1',
  `cc` varchar(255) NOT NULL default '',
  `homedir` varchar(255) NOT NULL,
  `autoresponder` enum('n','y') NOT NULL default 'n',
  `autoresponder_start_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `autoresponder_end_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `autoresponder_text` mediumtext NULL,
  `move_junk` enum('n','y') NOT NULL default 'n',
  `custom_mailfilter` mediumtext,
  `postfix` enum('n','y') NOT NULL,
  `access` enum('n','y') NOT NULL,
  `disableimap` enum('n','y') NOT NULL default 'n',
  `disablepop3` enum('n','y') NOT NULL default 'n',
  `disabledeliver` enum('n','y') NOT NULL default 'n',
  `disablesmtp` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`mailuser_id`),
  KEY `server_id` (`server_id`,`email`),
  KEY `email_access` (`email`,`access`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_user_filter`
-- 

CREATE TABLE `mail_user_filter` (
  `filter_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `mailuser_id` int(11) unsigned NOT NULL default '0',
  `rulename` varchar(64) default NULL,
  `source` varchar(255) default NULL,
  `searchterm` varchar(255) default NULL,
  `op` varchar(255) default NULL,
  `action` varchar(255) default NULL,
  `target` varchar(255) default NULL,
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`filter_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `remote_session`
-- 

CREATE TABLE `remote_session` (
  `remote_session` varchar(64) NOT NULL,
  `remote_userid` int(11) unsigned NOT NULL,
  `remote_functions` text NOT NULL,
  `tstamp` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`remote_session`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table  `remote_user`
-- 

CREATE TABLE `remote_user` (
  `remote_userid` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `remote_username` varchar(64) NOT NULL,
  `remote_password` varchar(64) NOT NULL,
  `remote_functions` text NOT NULL,
  PRIMARY KEY  (`remote_userid`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `server`
-- 

CREATE TABLE `server` (
  `server_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_name` varchar(255) NOT NULL default '',
  `mail_server` tinyint(1) NOT NULL default '0',
  `web_server` tinyint(1) NOT NULL default '0',
  `dns_server` tinyint(1) NOT NULL default '0',
  `file_server` tinyint(1) NOT NULL default '0',
  `db_server` tinyint(1) NOT NULL default '0',
  `vserver_server` tinyint(1) NOT NULL default '0',
  `config` text NOT NULL,
  `updated` bigint(20) unsigned NOT NULL default '0',
  `mirror_server_id` int(11) unsigned NOT NULL default '0',
  `dbversion` int(11) unsigned NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`server_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `server_ip`
-- 

CREATE TABLE `server_ip` (
  `server_ip_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `ip_address` varchar(39) default NULL,
  `virtualhost` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`server_ip_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

CREATE TABLE `shell_user` (
  `shell_user_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `parent_domain_id` int(11) unsigned NOT NULL default '0',
  `username` varchar(64) default NULL,
  `password` varchar(64) default NULL,
  `quota_size` bigint(20) NOT NULL default '-1',
  `active` enum('n','y') NOT NULL default 'y',
  `puser` varchar(255) default NULL,
  `pgroup` varchar(255) default NULL,
  `shell` varchar(255) NOT NULL default '/bin/bash',
  `dir` varchar(255) default NULL,
  `chroot` varchar(255) NOT NULL,
  PRIMARY KEY  (`shell_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `software_package`
-- 

CREATE TABLE `software_package` (
  `package_id` int(11) unsigned NOT NULL auto_increment,
  `software_repo_id` int(11) unsigned NOT NULL,
  `package_name` varchar(64) NOT NULL,
  `package_title` varchar(64) NOT NULL,
  `package_description` text,
  `package_version` varchar(8) default NULL,
  `package_type` enum('ispconfig','app','web') NOT NULL default 'app',
  `package_installable` enum('yes','no','key') NOT NULL default 'yes',
  `package_requires_db` enum('no','mysql') NOT NULL default 'no',
  `package_key` varchar(255) NOT NULL,
  `package_config` text,
  PRIMARY KEY  (`package_id`),
  UNIQUE KEY `package_name` (`package_name`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `software_repo`
-- 

CREATE TABLE `software_repo` (
  `software_repo_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `repo_name` varchar(64) default NULL,
  `repo_url` varchar(255) default NULL,
  `repo_username` varchar(64) default NULL,
  `repo_password` varchar(64) default NULL,
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`software_repo_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `software_update`
-- 

CREATE TABLE `software_update` (
  `software_update_id` int(11) unsigned NOT NULL auto_increment,
  `software_repo_id` int(11) unsigned NOT NULL,
  `package_name` varchar(64) NOT NULL,
  `update_url` varchar(255) NOT NULL,
  `update_md5` varchar(255) NOT NULL,
  `update_dependencies` varchar(255) NOT NULL,
  `update_title` varchar(64) NOT NULL,
  `v1` tinyint(1) NOT NULL default '0',
  `v2` tinyint(1) NOT NULL default '0',
  `v3` tinyint(1) NOT NULL default '0',
  `v4` tinyint(1) NOT NULL default '0',
  `type` enum('full','update') NOT NULL default 'full',
  PRIMARY KEY  (`software_update_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `software_update_inst`
-- 

CREATE TABLE `software_update_inst` (
  `software_update_inst_id` int(11) unsigned NOT NULL auto_increment,
  `software_update_id` int(11) unsigned NOT NULL default '0',
  `package_name` varchar(64) NOT NULL,
  `server_id` int(11) unsigned NOT NULL,
  `status` enum('none','installing','installed','deleting','deleted','failed') NOT NULL default 'none',
  PRIMARY KEY  (`software_update_inst_id`),
  UNIQUE KEY `software_update_id` (`software_update_id`,`package_name`,`server_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `spamfilter_policy`
-- 

CREATE TABLE `spamfilter_policy` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL,
  `sys_groupid` int(11) unsigned NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `policy_name` varchar(64) default NULL,
  `virus_lover` enum('N','Y') default NULL,
  `spam_lover` enum('N','Y') default NULL,
  `banned_files_lover` enum('N','Y') default NULL,
  `bad_header_lover` enum('N','Y') default NULL,
  `bypass_virus_checks` enum('N','Y') default NULL,
  `bypass_spam_checks` enum('N','Y') default NULL,
  `bypass_banned_checks` enum('N','Y') default NULL,
  `bypass_header_checks` enum('N','Y') default NULL,
  `spam_modifies_subj` enum('N','Y') default NULL,
  `virus_quarantine_to` varchar(255) default NULL,
  `spam_quarantine_to` varchar(255) default NULL,
  `banned_quarantine_to` varchar(255) default NULL,
  `bad_header_quarantine_to` varchar(255) default NULL,
  `clean_quarantine_to` varchar(255) default NULL,
  `other_quarantine_to` varchar(255) default NULL,
  `spam_tag_level` float default NULL,
  `spam_tag2_level` float default NULL,
  `spam_kill_level` float default NULL,
  `spam_dsn_cutoff_level` float default NULL,
  `spam_quarantine_cutoff_level` float default NULL,
  `addr_extension_virus` varchar(64) default NULL,
  `addr_extension_spam` varchar(64) default NULL,
  `addr_extension_banned` varchar(64) default NULL,
  `addr_extension_bad_header` varchar(64) default NULL,
  `warnvirusrecip` enum('N','Y') default NULL,
  `warnbannedrecip` enum('N','Y') default NULL,
  `warnbadhrecip` enum('N','Y') default NULL,
  `newvirus_admin` varchar(64) default NULL,
  `virus_admin` varchar(64) default NULL,
  `banned_admin` varchar(64) default NULL,
  `bad_header_admin` varchar(64) default NULL,
  `spam_admin` varchar(64) default NULL,
  `spam_subject_tag` varchar(64) default NULL,
  `spam_subject_tag2` varchar(64) default NULL,
  `message_size_limit` int(11) unsigned default NULL,
  `banned_rulenames` varchar(64) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `spamfilter_users`
-- 

CREATE TABLE `spamfilter_users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL,
  `sys_groupid` int(11) unsigned NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(11) unsigned NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL default '7',
  `policy_id` int(11) unsigned NOT NULL default '1',
  `email` varchar(255) NOT NULL,
  `fullname` varchar(64) default NULL,
  `local` varchar(1) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `spamfilter_wblist`
-- 

CREATE TABLE `spamfilter_wblist` (
  `wblist_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL,
  `sys_groupid` int(11) unsigned NOT NULL,
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(11) unsigned NOT NULL,
  `wb` enum('W','B') NOT NULL default 'W',
  `rid` int(11) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL,
  `active` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`wblist_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `support_message`
-- 

CREATE TABLE `support_message` (
  `support_message_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `recipient_id` int(11) unsigned NOT NULL default '0',
  `sender_id` int(11) unsigned NOT NULL default '0',
  `subject` varchar(255) default NULL,
  `message` text default NULL,
  `tstamp` int(11) NOT NULL default '0',
  PRIMARY KEY  (`support_message_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_datalog`
-- 

CREATE TABLE `sys_datalog` (
  `datalog_id` int(11) unsigned NOT NULL auto_increment,
  `server_id` int(11) unsigned NOT NULL,
  `dbtable` varchar(255) NOT NULL default '',
  `dbidx` varchar(255) NOT NULL default '',
  `action` char(1) NOT NULL default '',
  `tstamp` int(11) NOT NULL default '0',
  `user` varchar(255) NOT NULL default '',
  `data` text NOT NULL,
  `status` set('pending','ok','warning','error') NOT NULL default 'pending',
  PRIMARY KEY  (`datalog_id`),
  KEY `server_id` (`server_id`,`status`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_dbsync`
-- 

CREATE TABLE `sys_dbsync` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `jobname` varchar(64) NOT NULL default '',
  `sync_interval_minutes` int(11) unsigned NOT NULL default '0',
  `db_type` varchar(16) NOT NULL default '',
  `db_host` varchar(255) NOT NULL default '',
  `db_name` varchar(64) NOT NULL default '',
  `db_username` varchar(64) NOT NULL default '',
  `db_password` varchar(64) NOT NULL default '',
  `db_tables` varchar(255) NOT NULL default 'admin,forms',
  `empty_datalog` int(11) unsigned NOT NULL default '0',
  `sync_datalog_external` int(11) unsigned NOT NULL default '0',
  `active` tinyint(1) NOT NULL default '1',
  `last_datalog_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `last_datalog_id` (`last_datalog_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;
-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_filesync`
-- 

CREATE TABLE `sys_filesync` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `jobname` varchar(64) NOT NULL default '',
  `sync_interval_minutes` int(11) unsigned NOT NULL default '0',
  `ftp_host` varchar(255) NOT NULL default '',
  `ftp_path` varchar(255) NOT NULL default '',
  `ftp_username` varchar(64) NOT NULL default '',
  `ftp_password` varchar(64) NOT NULL default '',
  `local_path` varchar(255) NOT NULL default '',
  `wput_options` varchar(255) NOT NULL default '--timestamping --reupload --dont-continue',
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_group`
-- 

CREATE TABLE `sys_group` (
  `groupid` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `description` text NOT NULL,
  `client_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`groupid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_ini`
-- 

CREATE TABLE `sys_ini` (
  `sysini_id` int(11) unsigned NOT NULL auto_increment,
  `config` longtext NOT NULL,
  PRIMARY KEY  (`sysini_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_log`
-- 

CREATE TABLE `sys_log` (
  `syslog_id` int(11) unsigned NOT NULL auto_increment,
  `server_id` int(11) unsigned NOT NULL default '0',
  `datalog_id` int(11) unsigned NOT NULL default '0',
  `loglevel` tinyint(4) NOT NULL default '0',
  `tstamp` int(11) unsigned NOT NULL,
  `message` text,
  PRIMARY KEY  (`syslog_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;


-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_user`
-- 

CREATE TABLE `sys_user` (
  `userid` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '1',
  `sys_groupid` int(11) unsigned NOT NULL default '1',
  `sys_perm_user` varchar(5) NOT NULL default 'riud',
  `sys_perm_group` varchar(5) NOT NULL default 'riud',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `username` varchar(64) NOT NULL default '',
  `passwort` varchar(64) NOT NULL default '',
  `modules` varchar(255) NOT NULL default '',
  `startmodule` varchar(255) NOT NULL default '',
  `app_theme` varchar(32) NOT NULL default 'default',
  `typ` varchar(16) NOT NULL default 'user',
  `active` tinyint(1) NOT NULL default '1',
  `language` varchar(2) NOT NULL default 'de',
  `groups` varchar(255) NOT NULL default '',
  `default_group` int(11) unsigned NOT NULL default '0',
  `client_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `web_domain`
-- 

CREATE TABLE `web_domain` (
  `domain_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `ip_address` varchar(39) default NULL,
  `domain` varchar(255) default NULL,
  `type` varchar(32) default NULL,
  `parent_domain_id` int(11) unsigned NOT NULL default '0',
  `vhost_type` varchar(32) default NULL,
  `document_root` varchar(255) default NULL,
  `system_user` varchar(255) default NULL,
  `system_group` varchar(255) default NULL,
  `hd_quota` bigint(20) NOT NULL default '0',
  `traffic_quota` bigint(20) NOT NULL default '-1',
  `cgi` enum('n','y') NOT NULL default 'y',
  `ssi` enum('n','y') NOT NULL default 'y',
  `suexec` enum('n','y') NOT NULL default 'y',
  `errordocs` tinyint(1) NOT NULL default '1',
  `is_subdomainwww` tinyint(1) NOT NULL default '1',
  `subdomain` enum('none','www','*') NOT NULL default 'none',
  `php` varchar(32) NOT NULL default 'y',
  `ruby` enum('n','y') NOT NULL default 'n',
  `redirect_type` varchar(255) default NULL,
  `redirect_path` varchar(255) default NULL,
  `ssl` enum('n','y') NOT NULL default 'n',
  `ssl_state` varchar(255) NULL,
  `ssl_locality` varchar(255) NULL,
  `ssl_organisation` varchar(255) NULL,
  `ssl_organisation_unit` varchar(255) NULL,
  `ssl_country` varchar(255) NULL,
  `ssl_domain` varchar(255) NULL,
  `ssl_request` mediumtext NULL,
  `ssl_cert` mediumtext NULL,
  `ssl_bundle` mediumtext NULL,
  `ssl_action` varchar(16) NULL,
  `stats_password` varchar(255) default NULL,
  `stats_type` varchar(255) default 'webalizer',
  `allow_override` varchar(255) NOT NULL default 'All',
  `apache_directives` text,
  `php_open_basedir` text,
  `custom_php_ini` text,
  `backup_interval` VARCHAR( 255 ) NOT NULL DEFAULT 'none',
  `backup_copies` INT NOT NULL DEFAULT '1',
  `active` enum('n','y') NOT NULL default 'y',
  `traffic_quota_lock` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`domain_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;

-- --------------------------------------------------------

-- 
-- Table structure for table  `web_database`
-- 

CREATE TABLE `web_database` (
  `database_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `type` varchar(16) NOT NULL default 'y',
  `database_name` varchar(64) default NULL,
  `database_user` varchar(64) default NULL,
  `database_password` varchar(64) default NULL,
  `database_charset` varchar(64) default NULL,
  `remote_access` enum('n','y') NOT NULL default 'y',
  `remote_ips` text NOT NULL,
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`database_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;


-- --------------------------------------------------------

--
-- Table structure for table  `web_traffic`
--

CREATE TABLE `web_traffic` (
  `hostname` varchar(255) NOT NULL,
  `traffic_date` date NOT NULL,
  `traffic_bytes` bigint(32) unsigned NOT NULL default '0',
  PRIMARY KEY  (`hostname`,`traffic_date`)
) ENGINE=MyISAM;


-- --------------------------------------------------------

--
-- Table structure for table  `webdav_user`
--

CREATE TABLE `webdav_user` (
  `webdav_user_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `parent_domain_id` int(11) unsigned NOT NULL default '0',
  `username` varchar(64) default NULL,
  `password` varchar(64) default NULL,
  `active` enum('n','y') NOT NULL default 'y',
  `dir` varchar(255) default NULL,
  PRIMARY KEY  (`webdav_user_id`)
) ENGINE=MyISAM;


-- --------------------------------------------------------

--
-- Table structure for table  `attempts_login`
--

CREATE TABLE `attempts_login` (
  `ip` varchar(39) NOT NULL,
  `times` int(11) default NULL,
  `login_time` timestamp
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table  `monitor_data`
-- 

CREATE TABLE `monitor_data` (
  `server_id` int(11) unsigned NOT NULL,
  `type` varchar(255) NOT NULL,
  `created` int(11) unsigned NOT NULL,
  `data` mediumtext NOT NULL,
  `state` enum('no_state', 'unknown', 'ok', 'info', 'warning', 'critical', 'error') NOT NULL default 'unknown',
  PRIMARY KEY  (`server_id`,`type`,`created`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_config`
-- 

CREATE TABLE `sys_config` (
  `config_id` int(11) unsigned NOT NULL,
  `group` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=MyISAM;


--
-- Tabellenstruktur für Tabelle `sys_remoteaction`
--

CREATE TABLE `sys_remoteaction` (
  `action_id` int(11) unsigned NOT NULL auto_increment,
  `server_id` int(11) unsigned NOT NULL,
  `tstamp` int(11) NOT NULL,
  `action_type` varchar(20) NOT NULL,
  `action_param` mediumtext NOT NULL,
  `action_state` enum('pending','ok','warning','error') NOT NULL,
  `response` mediumtext NOT NULL,
  PRIMARY KEY  (`action_id`),
  KEY `server_id` (`server_id`)
) ENGINE=MyISAM;

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

--
-- Table structure for table `country`
--

CREATE TABLE `country` (
  `iso` char(2) NOT NULL PRIMARY KEY,
  `name` varchar(64) NOT NULL,
  `printable_name` varchar(64) NOT NULL,
  `iso3` char(3),
  `numcode` SMALLINT
) ENGINE=MyISAM;



-- --------------------------------------------------------
-- --------------------------------------------------------
-- DB-DATA
-- --------------------------------------------------------
-- --------------------------------------------------------

-- 
-- Dumping data for table `dns_template`
-- 

INSERT INTO `dns_template` (`template_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `name`, `fields`, `template`, `visible`) VALUES (1, 1, 1, 'riud', 'riud', '', 'Default', 'DOMAIN,IP,NS1,NS2,EMAIL', '[ZONE]\norigin={DOMAIN}.\nns={NS1}.\nmbox={EMAIL}.\nrefresh=28800\nretry=7200\nexpire=604800\nminimum=86400\nttl=86400\n\n[DNS_RECORDS]\nA|{DOMAIN}.|{IP}|0|86400\nA|www|{IP}|0|86400\nA|mail|{IP}|0|86400\nNS|{DOMAIN}.|{NS1}.|0|86400\nNS|{DOMAIN}.|{NS2}.|0|86400\nMX|{DOMAIN}.|mail.{DOMAIN}.|10|86400', 'y');

-- --------------------------------------------------------

-- 
-- Dumping data for table `software_repo`
-- 

INSERT INTO `software_repo` (`software_repo_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `repo_name`, `repo_url`, `repo_username`, `repo_password`, `active`) VALUES (1, 1, 1, 'riud', 'riud', '', 'ISPConfig Addons', 'http://repo.ispconfig.org/addons/', '', '', 'n');

-- --------------------------------------------------------

-- 
-- Dumping data for table `spamfilter_policy`
-- 

INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES(1, 1, 0, 'riud', 'riud', 'r', 'Non-paying', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N', 'Y', '', '', '', '', '', '', 3, 7, 10, 0, 0, '', '', '', '', 'N', 'N', 'N', '', '', '', '', '', '', '', 0, '');
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES(2, 1, 0, 'riud', 'riud', 'r', 'Uncensored', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, 3, 999, 999, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES(3, 1, 0, 'riud', 'riud', 'r', 'Wants all spam', 'N', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 999, 999, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES(4, 1, 0, 'riud', 'riud', 'r', 'Wants viruses', 'Y', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 6.9, 6.9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES(5, 1, 0, 'riud', 'riud', 'r', 'Normal', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', '', '', '', '', '', '', 1, 4.5, 50, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '***SPAM***', NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES(6, 1, 0, 'riud', 'riud', 'r', 'Trigger happy', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 5, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`) VALUES(7, 1, 0, 'riud', 'riud', 'r', 'Permissive', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 10, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

-- 
-- Dumping data for table `sys_group`
-- 

INSERT INTO `sys_group` (`groupid`, `name`, `description`, `client_id`) VALUES (1, 'admin', 'Administrators group', 0);

-- --------------------------------------------------------

-- 
-- Dumping data for table `sys_ini`
-- 

INSERT INTO `sys_ini` (`sysini_id`, `config`) VALUES (1, '');

-- --------------------------------------------------------

-- 
-- Dumping data for table `sys_user`
-- 

INSERT INTO `sys_user` (`userid`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `username`, `passwort`, `modules`, `startmodule`, `app_theme`, `typ`, `active`, `language`, `groups`, `default_group`, `client_id`) VALUES (1, 1, 0, 'riud', 'riud', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'admin,client,mail,monitor,sites,dns,tools,help', 'mail', 'default', 'admin', 1, 'en', '1,2', 1, 0);

-- --------------------------------------------------------

--
-- Dumping data for table `sys_config`
--

INSERT INTO sys_config VALUES ('1','db','db_version','3.0.2.1');

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

--
-- Dumping data for table `country`
--

INSERT INTO `country` (`iso`, `name`, `printable_name`, `iso3`, `numcode`) VALUES
('AF', 'AFGHANISTAN', 'Afghanistan', 'AFG', 4),
('AL', 'ALBANIA', 'Albania', 'ALB', 8),
('DZ', 'ALGERIA', 'Algeria', 'DZA', 12),
('AS', 'AMERICAN SAMOA', 'American Samoa', 'ASM', 16),
('AD', 'ANDORRA', 'Andorra', 'AND', 20),
('AO', 'ANGOLA', 'Angola', 'AGO', 24),
('AI', 'ANGUILLA', 'Anguilla', 'AIA', 660),
('AQ', 'ANTARCTICA', 'Antarctica', NULL, NULL),
('AG', 'ANTIGUA AND BARBUDA', 'Antigua and Barbuda', 'ATG', 28),
('AR', 'ARGENTINA', 'Argentina', 'ARG', 32),
('AM', 'ARMENIA', 'Armenia', 'ARM', 51),
('AW', 'ARUBA', 'Aruba', 'ABW', 533),
('AU', 'AUSTRALIA', 'Australia', 'AUS', 36),
('AT', 'AUSTRIA', 'Austria', 'AUT', 40),
('AZ', 'AZERBAIJAN', 'Azerbaijan', 'AZE', 31),
('BS', 'BAHAMAS', 'Bahamas', 'BHS', 44),
('BH', 'BAHRAIN', 'Bahrain', 'BHR', 48),
('BD', 'BANGLADESH', 'Bangladesh', 'BGD', 50),
('BB', 'BARBADOS', 'Barbados', 'BRB', 52),
('BY', 'BELARUS', 'Belarus', 'BLR', 112),
('BE', 'BELGIUM', 'Belgium', 'BEL', 56),
('BZ', 'BELIZE', 'Belize', 'BLZ', 84),
('BJ', 'BENIN', 'Benin', 'BEN', 204),
('BM', 'BERMUDA', 'Bermuda', 'BMU', 60),
('BT', 'BHUTAN', 'Bhutan', 'BTN', 64),
('BO', 'BOLIVIA', 'Bolivia', 'BOL', 68),
('BA', 'BOSNIA AND HERZEGOVINA', 'Bosnia and Herzegovina', 'BIH', 70),
('BW', 'BOTSWANA', 'Botswana', 'BWA', 72),
('BV', 'BOUVET ISLAND', 'Bouvet Island', NULL, NULL),
('BR', 'BRAZIL', 'Brazil', 'BRA', 76),
('IO', 'BRITISH INDIAN OCEAN TERRITORY', 'British Indian Ocean Territory', NULL, NULL),
('BN', 'BRUNEI DARUSSALAM', 'Brunei Darussalam', 'BRN', 96),
('BG', 'BULGARIA', 'Bulgaria', 'BGR', 100),
('BF', 'BURKINA FASO', 'Burkina Faso', 'BFA', 854),
('BI', 'BURUNDI', 'Burundi', 'BDI', 108),
('KH', 'CAMBODIA', 'Cambodia', 'KHM', 116),
('CM', 'CAMEROON', 'Cameroon', 'CMR', 120),
('CA', 'CANADA', 'Canada', 'CAN', 124),
('CV', 'CAPE VERDE', 'Cape Verde', 'CPV', 132),
('KY', 'CAYMAN ISLANDS', 'Cayman Islands', 'CYM', 136),
('CF', 'CENTRAL AFRICAN REPUBLIC', 'Central African Republic', 'CAF', 140),
('TD', 'CHAD', 'Chad', 'TCD', 148),
('CL', 'CHILE', 'Chile', 'CHL', 152),
('CN', 'CHINA', 'China', 'CHN', 156),
('CX', 'CHRISTMAS ISLAND', 'Christmas Island', NULL, NULL),
('CC', 'COCOS (KEELING) ISLANDS', 'Cocos (Keeling) Islands', NULL, NULL),
('CO', 'COLOMBIA', 'Colombia', 'COL', 170),
('KM', 'COMOROS', 'Comoros', 'COM', 174),
('CG', 'CONGO', 'Congo', 'COG', 178),
('CD', 'CONGO, THE DEMOCRATIC REPUBLIC OF THE', 'Congo, the Democratic Republic of the', 'COD', 180),
('CK', 'COOK ISLANDS', 'Cook Islands', 'COK', 184),
('CR', 'COSTA RICA', 'Costa Rica', 'CRI', 188),
('CI', 'COTE D''IVOIRE', 'Cote D''Ivoire', 'CIV', 384),
('HR', 'CROATIA', 'Croatia', 'HRV', 191),
('CU', 'CUBA', 'Cuba', 'CUB', 192),
('CY', 'CYPRUS', 'Cyprus', 'CYP', 196),
('CZ', 'CZECH REPUBLIC', 'Czech Republic', 'CZE', 203),
('DK', 'DENMARK', 'Denmark', 'DNK', 208),
('DJ', 'DJIBOUTI', 'Djibouti', 'DJI', 262),
('DM', 'DOMINICA', 'Dominica', 'DMA', 212),
('DO', 'DOMINICAN REPUBLIC', 'Dominican Republic', 'DOM', 214),
('EC', 'ECUADOR', 'Ecuador', 'ECU', 218),
('EG', 'EGYPT', 'Egypt', 'EGY', 818),
('SV', 'EL SALVADOR', 'El Salvador', 'SLV', 222),
('GQ', 'EQUATORIAL GUINEA', 'Equatorial Guinea', 'GNQ', 226),
('ER', 'ERITREA', 'Eritrea', 'ERI', 232),
('EE', 'ESTONIA', 'Estonia', 'EST', 233),
('ET', 'ETHIOPIA', 'Ethiopia', 'ETH', 231),
('FK', 'FALKLAND ISLANDS (MALVINAS)', 'Falkland Islands (Malvinas)', 'FLK', 238),
('FO', 'FAROE ISLANDS', 'Faroe Islands', 'FRO', 234),
('FJ', 'FIJI', 'Fiji', 'FJI', 242),
('FI', 'FINLAND', 'Finland', 'FIN', 246),
('FR', 'FRANCE', 'France', 'FRA', 250),
('GF', 'FRENCH GUIANA', 'French Guiana', 'GUF', 254),
('PF', 'FRENCH POLYNESIA', 'French Polynesia', 'PYF', 258),
('TF', 'FRENCH SOUTHERN TERRITORIES', 'French Southern Territories', NULL, NULL),
('GA', 'GABON', 'Gabon', 'GAB', 266),
('GM', 'GAMBIA', 'Gambia', 'GMB', 270),
('GE', 'GEORGIA', 'Georgia', 'GEO', 268),
('DE', 'GERMANY', 'Germany', 'DEU', 276),
('GH', 'GHANA', 'Ghana', 'GHA', 288),
('GI', 'GIBRALTAR', 'Gibraltar', 'GIB', 292),
('GR', 'GREECE', 'Greece', 'GRC', 300),
('GL', 'GREENLAND', 'Greenland', 'GRL', 304),
('GD', 'GRENADA', 'Grenada', 'GRD', 308),
('GP', 'GUADELOUPE', 'Guadeloupe', 'GLP', 312),
('GU', 'GUAM', 'Guam', 'GUM', 316),
('GT', 'GUATEMALA', 'Guatemala', 'GTM', 320),
('GN', 'GUINEA', 'Guinea', 'GIN', 324),
('GW', 'GUINEA-BISSAU', 'Guinea-Bissau', 'GNB', 624),
('GY', 'GUYANA', 'Guyana', 'GUY', 328),
('HT', 'HAITI', 'Haiti', 'HTI', 332),
('HM', 'HEARD ISLAND AND MCDONALD ISLANDS', 'Heard Island and Mcdonald Islands', NULL, NULL),
('VA', 'HOLY SEE (VATICAN CITY STATE)', 'Holy See (Vatican City State)', 'VAT', 336),
('HN', 'HONDURAS', 'Honduras', 'HND', 340),
('HK', 'HONG KONG', 'Hong Kong', 'HKG', 344),
('HU', 'HUNGARY', 'Hungary', 'HUN', 348),
('IS', 'ICELAND', 'Iceland', 'ISL', 352),
('IN', 'INDIA', 'India', 'IND', 356),
('ID', 'INDONESIA', 'Indonesia', 'IDN', 360),
('IR', 'IRAN, ISLAMIC REPUBLIC OF', 'Iran, Islamic Republic of', 'IRN', 364),
('IQ', 'IRAQ', 'Iraq', 'IRQ', 368),
('IE', 'IRELAND', 'Ireland', 'IRL', 372),
('IL', 'ISRAEL', 'Israel', 'ISR', 376),
('IT', 'ITALY', 'Italy', 'ITA', 380),
('JM', 'JAMAICA', 'Jamaica', 'JAM', 388),
('JP', 'JAPAN', 'Japan', 'JPN', 392),
('JO', 'JORDAN', 'Jordan', 'JOR', 400),
('KZ', 'KAZAKHSTAN', 'Kazakhstan', 'KAZ', 398),
('KE', 'KENYA', 'Kenya', 'KEN', 404),
('KI', 'KIRIBATI', 'Kiribati', 'KIR', 296),
('KP', 'KOREA, DEMOCRATIC PEOPLE''S REPUBLIC OF', 'Korea, Democratic People''s Republic of', 'PRK', 408),
('KR', 'KOREA, REPUBLIC OF', 'Korea, Republic of', 'KOR', 410),
('KW', 'KUWAIT', 'Kuwait', 'KWT', 414),
('KG', 'KYRGYZSTAN', 'Kyrgyzstan', 'KGZ', 417),
('LA', 'LAO PEOPLE''S DEMOCRATIC REPUBLIC', 'Lao People''s Democratic Republic', 'LAO', 418),
('LV', 'LATVIA', 'Latvia', 'LVA', 428),
('LB', 'LEBANON', 'Lebanon', 'LBN', 422),
('LS', 'LESOTHO', 'Lesotho', 'LSO', 426),
('LR', 'LIBERIA', 'Liberia', 'LBR', 430),
('LY', 'LIBYAN ARAB JAMAHIRIYA', 'Libyan Arab Jamahiriya', 'LBY', 434),
('LI', 'LIECHTENSTEIN', 'Liechtenstein', 'LIE', 438),
('LT', 'LITHUANIA', 'Lithuania', 'LTU', 440),
('LU', 'LUXEMBOURG', 'Luxembourg', 'LUX', 442),
('MO', 'MACAO', 'Macao', 'MAC', 446),
('MK', 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'Macedonia, the Former Yugoslav Republic of', 'MKD', 807),
('MG', 'MADAGASCAR', 'Madagascar', 'MDG', 450),
('MW', 'MALAWI', 'Malawi', 'MWI', 454),
('MY', 'MALAYSIA', 'Malaysia', 'MYS', 458),
('MV', 'MALDIVES', 'Maldives', 'MDV', 462),
('ML', 'MALI', 'Mali', 'MLI', 466),
('MT', 'MALTA', 'Malta', 'MLT', 470),
('MH', 'MARSHALL ISLANDS', 'Marshall Islands', 'MHL', 584),
('MQ', 'MARTINIQUE', 'Martinique', 'MTQ', 474),
('MR', 'MAURITANIA', 'Mauritania', 'MRT', 478),
('MU', 'MAURITIUS', 'Mauritius', 'MUS', 480),
('YT', 'MAYOTTE', 'Mayotte', NULL, NULL),
('MX', 'MEXICO', 'Mexico', 'MEX', 484),
('FM', 'MICRONESIA, FEDERATED STATES OF', 'Micronesia, Federated States of', 'FSM', 583),
('MD', 'MOLDOVA, REPUBLIC OF', 'Moldova, Republic of', 'MDA', 498),
('MC', 'MONACO', 'Monaco', 'MCO', 492),
('MN', 'MONGOLIA', 'Mongolia', 'MNG', 496),
('MS', 'MONTSERRAT', 'Montserrat', 'MSR', 500),
('MA', 'MOROCCO', 'Morocco', 'MAR', 504),
('MZ', 'MOZAMBIQUE', 'Mozambique', 'MOZ', 508),
('MM', 'MYANMAR', 'Myanmar', 'MMR', 104),
('NA', 'NAMIBIA', 'Namibia', 'NAM', 516),
('NR', 'NAURU', 'Nauru', 'NRU', 520),
('NP', 'NEPAL', 'Nepal', 'NPL', 524),
('NL', 'NETHERLANDS', 'Netherlands', 'NLD', 528),
('AN', 'NETHERLANDS ANTILLES', 'Netherlands Antilles', 'ANT', 530),
('NC', 'NEW CALEDONIA', 'New Caledonia', 'NCL', 540),
('NZ', 'NEW ZEALAND', 'New Zealand', 'NZL', 554),
('NI', 'NICARAGUA', 'Nicaragua', 'NIC', 558),
('NE', 'NIGER', 'Niger', 'NER', 562),
('NG', 'NIGERIA', 'Nigeria', 'NGA', 566),
('NU', 'NIUE', 'Niue', 'NIU', 570),
('NF', 'NORFOLK ISLAND', 'Norfolk Island', 'NFK', 574),
('MP', 'NORTHERN MARIANA ISLANDS', 'Northern Mariana Islands', 'MNP', 580),
('NO', 'NORWAY', 'Norway', 'NOR', 578),
('OM', 'OMAN', 'Oman', 'OMN', 512),
('PK', 'PAKISTAN', 'Pakistan', 'PAK', 586),
('PW', 'PALAU', 'Palau', 'PLW', 585),
('PS', 'PALESTINIAN TERRITORY, OCCUPIED', 'Palestinian Territory, Occupied', NULL, NULL),
('PA', 'PANAMA', 'Panama', 'PAN', 591),
('PG', 'PAPUA NEW GUINEA', 'Papua New Guinea', 'PNG', 598),
('PY', 'PARAGUAY', 'Paraguay', 'PRY', 600),
('PE', 'PERU', 'Peru', 'PER', 604),
('PH', 'PHILIPPINES', 'Philippines', 'PHL', 608),
('PN', 'PITCAIRN', 'Pitcairn', 'PCN', 612),
('PL', 'POLAND', 'Poland', 'POL', 616),
('PT', 'PORTUGAL', 'Portugal', 'PRT', 620),
('PR', 'PUERTO RICO', 'Puerto Rico', 'PRI', 630),
('QA', 'QATAR', 'Qatar', 'QAT', 634),
('RE', 'REUNION', 'Reunion', 'REU', 638),
('RO', 'ROMANIA', 'Romania', 'ROM', 642),
('RU', 'RUSSIAN FEDERATION', 'Russian Federation', 'RUS', 643),
('RW', 'RWANDA', 'Rwanda', 'RWA', 646),
('SH', 'SAINT HELENA', 'Saint Helena', 'SHN', 654),
('KN', 'SAINT KITTS AND NEVIS', 'Saint Kitts and Nevis', 'KNA', 659),
('LC', 'SAINT LUCIA', 'Saint Lucia', 'LCA', 662),
('PM', 'SAINT PIERRE AND MIQUELON', 'Saint Pierre and Miquelon', 'SPM', 666),
('VC', 'SAINT VINCENT AND THE GRENADINES', 'Saint Vincent and the Grenadines', 'VCT', 670),
('WS', 'SAMOA', 'Samoa', 'WSM', 882),
('SM', 'SAN MARINO', 'San Marino', 'SMR', 674),
('ST', 'SAO TOME AND PRINCIPE', 'Sao Tome and Principe', 'STP', 678),
('SA', 'SAUDI ARABIA', 'Saudi Arabia', 'SAU', 682),
('SN', 'SENEGAL', 'Senegal', 'SEN', 686),
('CS', 'SERBIA AND MONTENEGRO', 'Serbia and Montenegro', NULL, NULL),
('SC', 'SEYCHELLES', 'Seychelles', 'SYC', 690),
('SL', 'SIERRA LEONE', 'Sierra Leone', 'SLE', 694),
('SG', 'SINGAPORE', 'Singapore', 'SGP', 702),
('SK', 'SLOVAKIA', 'Slovakia', 'SVK', 703),
('SI', 'SLOVENIA', 'Slovenia', 'SVN', 705),
('SB', 'SOLOMON ISLANDS', 'Solomon Islands', 'SLB', 90),
('SO', 'SOMALIA', 'Somalia', 'SOM', 706),
('ZA', 'SOUTH AFRICA', 'South Africa', 'ZAF', 710),
('GS', 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS', 'South Georgia and the South Sandwich Islands', NULL, NULL),
('ES', 'SPAIN', 'Spain', 'ESP', 724),
('LK', 'SRI LANKA', 'Sri Lanka', 'LKA', 144),
('SD', 'SUDAN', 'Sudan', 'SDN', 736),
('SR', 'SURINAME', 'Suriname', 'SUR', 740),
('SJ', 'SVALBARD AND JAN MAYEN', 'Svalbard and Jan Mayen', 'SJM', 744),
('SZ', 'SWAZILAND', 'Swaziland', 'SWZ', 748),
('SE', 'SWEDEN', 'Sweden', 'SWE', 752),
('CH', 'SWITZERLAND', 'Switzerland', 'CHE', 756),
('SY', 'SYRIAN ARAB REPUBLIC', 'Syrian Arab Republic', 'SYR', 760),
('TW', 'TAIWAN, PROVINCE OF CHINA', 'Taiwan, Province of China', 'TWN', 158),
('TJ', 'TAJIKISTAN', 'Tajikistan', 'TJK', 762),
('TZ', 'TANZANIA, UNITED REPUBLIC OF', 'Tanzania, United Republic of', 'TZA', 834),
('TH', 'THAILAND', 'Thailand', 'THA', 764),
('TL', 'TIMOR-LESTE', 'Timor-Leste', NULL, NULL),
('TG', 'TOGO', 'Togo', 'TGO', 768),
('TK', 'TOKELAU', 'Tokelau', 'TKL', 772),
('TO', 'TONGA', 'Tonga', 'TON', 776),
('TT', 'TRINIDAD AND TOBAGO', 'Trinidad and Tobago', 'TTO', 780),
('TN', 'TUNISIA', 'Tunisia', 'TUN', 788),
('TR', 'TURKEY', 'Turkey', 'TUR', 792),
('TM', 'TURKMENISTAN', 'Turkmenistan', 'TKM', 795),
('TC', 'TURKS AND CAICOS ISLANDS', 'Turks and Caicos Islands', 'TCA', 796),
('TV', 'TUVALU', 'Tuvalu', 'TUV', 798),
('UG', 'UGANDA', 'Uganda', 'UGA', 800),
('UA', 'UKRAINE', 'Ukraine', 'UKR', 804),
('AE', 'UNITED ARAB EMIRATES', 'United Arab Emirates', 'ARE', 784),
('GB', 'UNITED KINGDOM', 'United Kingdom', 'GBR', 826),
('US', 'UNITED STATES', 'United States', 'USA', 840),
('UM', 'UNITED STATES MINOR OUTLYING ISLANDS', 'United States Minor Outlying Islands', NULL, NULL),
('UY', 'URUGUAY', 'Uruguay', 'URY', 858),
('UZ', 'UZBEKISTAN', 'Uzbekistan', 'UZB', 860),
('VU', 'VANUATU', 'Vanuatu', 'VUT', 548),
('VE', 'VENEZUELA', 'Venezuela', 'VEN', 862),
('VN', 'VIET NAM', 'Viet Nam', 'VNM', 704),
('VG', 'VIRGIN ISLANDS, BRITISH', 'Virgin Islands, British', 'VGB', 92),
('VI', 'VIRGIN ISLANDS, U.S.', 'Virgin Islands, U.s.', 'VIR', 850),
('WF', 'WALLIS AND FUTUNA', 'Wallis and Futuna', 'WLF', 876),
('EH', 'WESTERN SAHARA', 'Western Sahara', 'ESH', 732),
('YE', 'YEMEN', 'Yemen', 'YEM', 887),
('ZM', 'ZAMBIA', 'Zambia', 'ZMB', 894),
('ZW', 'ZIMBABWE', 'Zimbabwe', 'ZWE', 716);

-- --------------------------------------------------------
-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;
