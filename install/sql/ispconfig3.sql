/*
Copyright (c) 2007-2012, Till Brehm, projektfarm Gmbh
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

-- Includes 
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
-- Table structure for table `aps_instances`
--

CREATE TABLE IF NOT EXISTS `aps_instances` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `customer_id` int(4) NOT NULL DEFAULT '0',
  `package_id` int(4) NOT NULL DEFAULT '0',
  `instance_status` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `aps_instances_settings`
--

CREATE TABLE IF NOT EXISTS `aps_instances_settings` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `instance_id` int(4) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` text,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `aps_packages`
--

CREATE TABLE IF NOT EXISTS `aps_packages` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `category` varchar(255) NOT NULL DEFAULT '',
  `version` varchar(20) NOT NULL DEFAULT '',
  `release` int(4) NOT NULL DEFAULT '0',
  `package_url` TEXT,
  `package_status` int(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `aps_settings`
--

CREATE TABLE IF NOT EXISTS `aps_settings` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `attempts_login`
--

CREATE TABLE `attempts_login` (
  `ip` varchar(39) NOT NULL DEFAULT '',
  `times` int(11) DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `client_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `company_name` varchar(64) DEFAULT NULL,
  `company_id` varchar(255) DEFAULT NULL,
  `gender` enum('','m','f') NOT NULL DEFAULT '',
  `contact_firstname` varchar( 64 ) NOT NULL DEFAULT '',
  `contact_name` varchar(64) DEFAULT NULL,
  `customer_no` varchar(64) DEFAULT NULL,
  `vat_id` varchar(64) DEFAULT NULL,
  `street` varchar(255) DEFAULT NULL,
  `zip` varchar(32) DEFAULT NULL,
  `city` varchar(64) DEFAULT NULL,
  `state` varchar(32) DEFAULT NULL,
  `country` char(2) DEFAULT NULL,
  `telephone` varchar(32) DEFAULT NULL,
  `mobile` varchar(32) DEFAULT NULL,
  `fax` varchar(32) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `internet` varchar(255) NOT NULL DEFAULT '',
  `icq` varchar(16) DEFAULT NULL,
  `notes` text,
  `bank_account_owner` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(255) DEFAULT NULL,
  `bank_code` varchar(255) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_iban` varchar(255) DEFAULT NULL,
  `bank_account_swift` varchar(255) DEFAULT NULL,
  `paypal_email` varchar(255) DEFAULT NULL,
  `default_mailserver` int(11) unsigned NOT NULL DEFAULT '1',
  `mail_servers` text,
  `limit_maildomain` int(11) NOT NULL DEFAULT '-1',
  `limit_mailbox` int(11) NOT NULL DEFAULT '-1',
  `limit_mailalias` int(11) NOT NULL DEFAULT '-1',
  `limit_mailaliasdomain` int(11) NOT NULL DEFAULT '-1',
  `limit_mailforward` int(11) NOT NULL DEFAULT '-1',
  `limit_mailcatchall` int(11) NOT NULL DEFAULT '-1',
  `limit_mailrouting` int(11) NOT NULL DEFAULT '0',
  `limit_mailfilter` int(11) NOT NULL DEFAULT '-1',
  `limit_fetchmail` int(11) NOT NULL DEFAULT '-1',
  `limit_mailquota` int(11) NOT NULL DEFAULT '-1',
  `limit_spamfilter_wblist` int(11) NOT NULL DEFAULT '0',
  `limit_spamfilter_user` int(11) NOT NULL DEFAULT '0',
  `limit_spamfilter_policy` int(11) NOT NULL DEFAULT '0',
  `default_xmppserver` int(11) unsigned NOT NULL DEFAULT '1',
  `xmpp_servers` text,
  `limit_xmpp_domain` int(11) NOT NULL DEFAULT '-1',
  `limit_xmpp_user` int(11) NOT NULL DEFAULT '-1',
  `limit_xmpp_muc` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_anon` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_auth_options` varchar(255) NOT NULL DEFAULT 'plain,hashed,isp',
  `limit_xmpp_vjud` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_proxy` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_status` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_pastebin` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_httparchive` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `default_webserver` int(11) unsigned NOT NULL DEFAULT '1',
  `web_servers` text,
  `limit_web_ip` text,
  `limit_web_domain` int(11) NOT NULL DEFAULT '-1',
  `limit_web_quota` int(11) NOT NULL DEFAULT '-1',
  `web_php_options` varchar(255) NOT NULL DEFAULT 'no,fast-cgi,cgi,mod,suphp,php-fpm,hhvm',
  `limit_cgi` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_ssi` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_perl` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_ruby` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_python` enum('n','y') NOT NULL DEFAULT 'n',
  `force_suexec` enum('n','y') NOT NULL DEFAULT 'y',
  `limit_hterror` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_wildcard` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_ssl` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_ssl_letsencrypt` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_web_subdomain` int(11) NOT NULL DEFAULT '-1',
  `limit_web_aliasdomain` int(11) NOT NULL DEFAULT '-1',
  `limit_ftp_user` int(11) NOT NULL DEFAULT '-1',
  `limit_shell_user` int(11) NOT NULL DEFAULT '0',
  `ssh_chroot` varchar(255) NOT NULL DEFAULT 'no,jailkit,ssh-chroot',
  `limit_webdav_user` int(11) NOT NULL DEFAULT '0',
  `limit_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y',
  `limit_directive_snippets` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n',
  `limit_aps` int(11) NOT NULL DEFAULT '-1',
  `default_dnsserver` int(11) unsigned NOT NULL DEFAULT '1',
  `db_servers` text,
  `limit_dns_zone` int(11) NOT NULL DEFAULT '-1',
  `default_slave_dnsserver` int(11) unsigned NOT NULL DEFAULT '1',
  `limit_dns_slave_zone` int(11) NOT NULL DEFAULT '-1',
  `limit_dns_record` int(11) NOT NULL DEFAULT '-1',
  `default_dbserver` int(11) NOT NULL DEFAULT '1',
  `dns_servers` text,
  `limit_database` int(11) NOT NULL DEFAULT '-1',
  `limit_database_user` int(11) NOT NULL DEFAULT '-1',
  `limit_database_quota` int(11) NOT NULL default '-1',
  `limit_cron` int(11) NOT NULL DEFAULT '0',
  `limit_cron_type` enum('url','chrooted','full') NOT NULL DEFAULT 'url',
  `limit_cron_frequency` int(11) NOT NULL DEFAULT '5',
  `limit_traffic_quota` int(11) NOT NULL DEFAULT '-1',
  `limit_client` int(11) NOT NULL DEFAULT '0',
  `limit_domainmodule` int(11) NOT NULL DEFAULT '0',
  `limit_mailmailinglist` int(11) NOT NULL DEFAULT '-1',
  `limit_openvz_vm` int(11) NOT NULL DEFAULT '0',
  `limit_openvz_vm_template_id` int(11) NOT NULL DEFAULT '0',
  `parent_client_id` int(11) unsigned NOT NULL DEFAULT '0',
  `username` varchar(64) DEFAULT NULL,
  `password` varchar(200) DEFAULT NULL,
  `language` char(2) NOT NULL DEFAULT 'en',
  `usertheme` varchar(32) NOT NULL DEFAULT 'default',
  `template_master` int(11) unsigned NOT NULL DEFAULT '0',
  `template_additional` text,
  `created_at` bigint(20) DEFAULT NULL,
  `locked` enum('n','y') NOT NULL DEFAULT 'n',
  `canceled` enum('n','y') NOT NULL DEFAULT 'n',
  `can_use_api` enum('n','y') NOT NULL DEFAULT 'n',
  `tmp_data` mediumblob,
  `id_rsa` varchar(2000) NOT NULL DEFAULT '',
  `ssh_rsa` varchar(600) NOT NULL DEFAULT '',
  `customer_no_template` varchar(255) DEFAULT 'R[CLIENTID]C[CUSTOMER_NO]',
  `customer_no_start` int(11) NOT NULL DEFAULT '1',
  `customer_no_counter` int(11) NOT NULL DEFAULT '0',
  `added_date` date NULL DEFAULT NULL,
  `added_by` varchar(255) DEFAULT NULL,
  `validation_status` enum('accept','review','reject') NOT NULL DEFAULT 'accept',
  `risk_score` int(10) unsigned NOT NULL DEFAULT '0',
  `activation_code` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`client_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `client_circle`
--

CREATE TABLE `client_circle` (
  `circle_id` int(11) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `circle_name` varchar(64) DEFAULT NULL,
  `client_ids` text,
  `description` text,
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY (`circle_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `template_name` varchar(64) NOT NULL DEFAULT '',
  `template_type` varchar(1) NOT NULL default 'm',
  `mail_servers` text,
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
  `default_xmppserver` int(11) unsigned NOT NULL DEFAULT '1',
  `xmpp_servers` text,
  `limit_xmpp_domain` int(11) NOT NULL DEFAULT '-1',
  `limit_xmpp_user` int(11) NOT NULL DEFAULT '-1',
  `limit_xmpp_muc` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_anon` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_vjud` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_proxy` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_status` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_pastebin` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `limit_xmpp_httparchive` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `web_servers` text,
  `limit_web_ip` text,
  `limit_web_domain` int(11) NOT NULL default '-1',
  `limit_web_quota` int(11) NOT NULL default '-1',
  `web_php_options` varchar(255) NOT NULL DEFAULT 'no',
  `limit_cgi` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_ssi` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_perl` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_ruby` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_python` enum('n','y') NOT NULL DEFAULT 'n',
  `force_suexec` enum('n','y') NOT NULL DEFAULT 'y',
  `limit_hterror` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_wildcard` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_ssl` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_ssl_letsencrypt` enum('n','y') NOT NULL DEFAULT 'n',
  `limit_web_subdomain` int(11) NOT NULL default '-1',
  `limit_web_aliasdomain` int(11) NOT NULL default '-1',
  `limit_ftp_user` int(11) NOT NULL default '-1',
  `limit_shell_user` int(11) NOT NULL default '0',
  `ssh_chroot` varchar(255) NOT NULL DEFAULT 'no',
  `limit_webdav_user` int(11) NOT NULL default '0',
  `limit_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y',
  `limit_directive_snippets` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n',
  `limit_aps` int(11) NOT NULL DEFAULT '-1',
  `dns_servers` text,
  `limit_dns_zone` int(11) NOT NULL default '-1',
  `default_slave_dnsserver` int(11) NOT NULL DEFAULT '0',
  `limit_dns_slave_zone` int(11) NOT NULL default '-1',
  `limit_dns_record` int(11) NOT NULL default '-1',
  `db_servers` text,
  `limit_database` int(11) NOT NULL default '-1',
  `limit_database_user` int(11) NOT NULL DEFAULT '-1',
  `limit_database_quota` int(11) NOT NULL default '-1',
  `limit_cron` int(11) NOT NULL default '0',
  `limit_cron_type` enum('url','chrooted','full') NOT NULL default 'url',
  `limit_cron_frequency` int(11) NOT NULL default '5',
  `limit_traffic_quota` int(11) NOT NULL default '-1',
  `limit_client` int(11) NOT NULL default '0',
  `limit_domainmodule` int(11) NOT NULL DEFAULT '0',
  `limit_mailmailinglist` int(11) NOT NULL default '-1',
  `limit_openvz_vm` int(11) NOT NULL DEFAULT '0',
  `limit_openvz_vm_template_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`template_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `client_template_assigned`
-- 

CREATE TABLE `client_template_assigned` (
  `assigned_template_id` bigint(20) NOT NULL auto_increment,
  `client_id` bigint(11) NOT NULL DEFAULT '0',
  `client_template_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`assigned_template_id`),
  KEY `client_id` (`client_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
-- --------------------------------------------------------

--
-- Table structure for table `invoice_message_template`
--

CREATE TABLE `client_message_template` (
  `client_message_template_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `template_type` varchar(255) DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`client_message_template_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `invoice_message_template`
--

-- --------------------------------------------------------

--
-- Table structure for table `country`
--

CREATE TABLE `country` (
  `iso` char(2) NOT NULL DEFAULT '',
  `name` varchar(64) NOT NULL DEFAULT '',
  `printable_name` varchar(64) NOT NULL DEFAULT '',
  `iso3` char(3) DEFAULT NULL,
  `numcode` smallint(6) DEFAULT NULL,
  `eu` enum('n','y') NOT NULL DEFAULT 'n',
  PRIMARY KEY (`iso`)
) DEFAULT CHARSET=utf8 ;

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
  `command` TEXT,
  `run_min` varchar(100) NULL,
  `run_hour` varchar(100) NULL,
  `run_mday` varchar(100) NULL,
  `run_month` varchar(100) NULL,
  `run_wday` varchar(100) NULL,
  `log` enum('n','y') NOT NULL default 'n',
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `directive_snippets`
-- 

CREATE TABLE IF NOT EXISTS `directive_snippets` (
  `directive_snippets_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `snippet` mediumtext,
  `customer_viewable` ENUM('n','y') NOT NULL DEFAULT 'n',
  `required_php_snippets` varchar(255) NOT NULL DEFAULT '',
  `active` enum('n','y') NOT NULL DEFAULT 'y',
  `master_directive_snippets_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`directive_snippets_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `dns_rr`
-- 
CREATE TABLE `dns_rr` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `server_id` int(11) NOT NULL default '1',
  `zone` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` enum('A','AAAA','ALIAS','CNAME','CAA','DS','HINFO','LOC','MX','NAPTR','NS','PTR','RP','SRV','TXT','TLSA','DNSKEY') default NULL,
  `data` TEXT NOT NULL,
  `aux` int(11) unsigned NOT NULL default '0',
  `ttl` int(11) unsigned NOT NULL default '3600',
  `active` enum('N','Y') NOT NULL default 'Y',
  `stamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `serial` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `rr` (`zone`,`type`,`name`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table  `dns_slave`
--

CREATE TABLE `dns_slave` (
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
  `xfer` TEXT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `slave` (`origin`,`server_id`),
  KEY `active` (`active`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `dns_ssl_ca`
-- 

CREATE TABLE IF NOT EXISTS `dns_ssl_ca` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `active` enum('N','Y') NOT NULL DEFAULT 'N',
  `ca_name` varchar(255) NOT NULL DEFAULT '',
  `ca_issue` varchar(255) NOT NULL DEFAULT '',
  `ca_wildcard` enum('Y','N') NOT NULL DEFAULT 'N',
  `ca_iodef` text NOT NULL,
  `ca_critical` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`ca_issue`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `dns_ssl_ca` ADD UNIQUE(`ca_issue`);

INSERT INTO `dns_ssl_ca` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `active`, `ca_name`, `ca_issue`, `ca_wildcard`, `ca_iodef`, `ca_critical`) VALUES
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'AC Camerfirma', 'camerfirma.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'ACCV', 'accv.es', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Actalis', 'actalis.it', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Amazon', 'amazon.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Asseco', 'certum.pl', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Buypass', 'buypass.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'CA Disig', 'disig.sk', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'CATCert', 'aoc.cat', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Certinomis', 'www.certinomis.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Certizen', 'hongkongpost.gov.hk', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'certSIGN', 'certsign.ro', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'CFCA', 'cfca.com.cn', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Chunghwa Telecom', 'cht.com.tw', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Sectigo / Comodo CA', 'comodoca.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'D-TRUST', 'd-trust.net', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'DigiCert', 'digicert.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'DocuSign', 'docusign.fr', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'e-tugra', 'e-tugra.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'EDICOM', 'edicomgroup.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Entrust', 'entrust.net', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Firmaprofesional', 'firmaprofesional.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'FNMT', 'fnmt.es', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GlobalSign', 'globalsign.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GoDaddy', 'godaddy.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Google Trust Services', 'pki.goog', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GRCA', 'gca.nat.gov.tw', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'HARICA', 'harica.gr', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'IdenTrust', 'identrust.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Izenpe', 'izenpe.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Kamu SM', 'kamusm.gov.tr', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Let''s Encrypt', 'letsencrypt.org', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Microsec e-Szigno', 'e-szigno.hu', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'NetLock', 'netlock.hu', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'PKIoverheid', 'www.pkioverheid.nl', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'PROCERT', 'procert.net.ve', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'QuoVadis', 'quovadisglobal.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'SECOM', 'secomtrust.net', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Sertifitseerimiskeskuse', 'sk.ee', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'StartCom', 'startcomca.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'SwissSign', 'swisssign.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Symantec / Thawte / GeoTrust', 'symantec.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'T-Systems', 'telesec.de', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Telia', 'telia.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Trustwave', 'trustwave.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Web.com', 'web.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'WISeKey', 'wisekey.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'WoSign', 'wosign.com', 'Y', '', 0);

-- --------------------------------------------------------

-- 
-- Table structure for table  `dns_soa`
-- 

CREATE TABLE `dns_soa` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `server_id` int(11) NOT NULL default '1',
  `origin` varchar(255) NOT NULL DEFAULT '',
  `ns` varchar(255) NOT NULL DEFAULT '',
  `mbox` varchar(255) NOT NULL DEFAULT '',
  `serial` int(11) unsigned NOT NULL default '1',
  `refresh` int(11) unsigned NOT NULL default '28800',
  `retry` int(11) unsigned NOT NULL default '7200',
  `expire` int(11) unsigned NOT NULL default '604800',
  `minimum` int(11) unsigned NOT NULL default '3600',
  `ttl` int(11) unsigned NOT NULL default '3600',
  `active` enum('N','Y') NOT NULL DEFAULT 'N',
  `xfer` TEXT NULL,
  `also_notify` TEXT NULL,
  `update_acl` varchar(255) default NULL,
  `dnssec_initialized` ENUM('Y','N') NOT NULL DEFAULT 'N',
  `dnssec_wanted` ENUM('Y','N') NOT NULL DEFAULT 'N',
  `dnssec_last_signed` BIGINT NOT NULL DEFAULT '0',
  `dnssec_info` TEXT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `origin` (`origin`),
  KEY `active` (`active`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Table structure for table  `domain`
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
) DEFAULT CHARSET=utf8 ;

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
  `tcp_port` text,
  `udp_port` text,
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`firewall_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `username_prefix` varchar(50) NOT NULL default '',
  `password` varchar(200) default NULL,
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
  `expires` datetime NULL DEFAULT NULL,
  `user_type` set('user','system') NOT NULL DEFAULT 'user',
  `user_config` text,
  PRIMARY KEY  (`ftp_user_id`),
  KEY `active` (`active`),
  KEY `server_id` (`server_id`),
  KEY `username` (`username`),
  KEY `quota_files` (`quota_files`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `ftp_traffic`
-- 

CREATE TABLE `ftp_traffic` (
  `hostname` varchar(255) NOT NULL,
  `traffic_date` date NOT NULL,
  `in_bytes` bigint(32) unsigned NOT NULL,
  `out_bytes` bigint(32) unsigned NOT NULL,
  UNIQUE KEY (`hostname`,`traffic_date`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `help_faq`
--

CREATE TABLE `help_faq` (
  `hf_id` int(11) NOT NULL AUTO_INCREMENT,
  `hf_section` int(11) DEFAULT NULL,
  `hf_order` int(11) DEFAULT '0',
  `hf_question` text,
  `hf_answer` text,
  `sys_userid` int(11) DEFAULT NULL,
  `sys_groupid` int(11) DEFAULT NULL,
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`hf_id`)
)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `help_faq_sections`
--

CREATE TABLE `help_faq_sections` (
  `hfs_id` int(11) NOT NULL AUTO_INCREMENT,
  `hfs_name` varchar(255) DEFAULT NULL,
  `hfs_order` int(11) DEFAULT '0',
  `sys_userid` int(11) DEFAULT NULL,
  `sys_groupid` int(11) DEFAULT NULL,
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`hfs_id`)
)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- Table structure for table `iptables`
--

DROP TABLE IF EXISTS `iptables`;
CREATE TABLE `iptables` (
  `iptables_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned NOT NULL DEFAULT '0',
  `table` varchar(10) DEFAULT NULL COMMENT 'INPUT OUTPUT FORWARD',
  `source_ip` varchar(16) DEFAULT NULL,
  `destination_ip` varchar(16) DEFAULT NULL,
  `protocol` varchar(10) DEFAULT 'TCP' COMMENT 'TCP UDP GRE',
  `singleport` varchar(10) DEFAULT NULL,
  `multiport` varchar(40) DEFAULT NULL,
  `state` varchar(20) DEFAULT NULL COMMENT 'NEW ESTABLISHED RECNET etc',
  `target` varchar(10) DEFAULT NULL COMMENT 'ACCEPT DROP REJECT LOG',
  `active` enum('n','y') NOT NULL DEFAULT 'y',
  PRIMARY KEY (`iptables_id`)
) AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

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
  `source` varchar(255) NOT NULL DEFAULT '',
  `access` varchar(255) NOT NULL DEFAULT '',
  `type` set('recipient','sender','client') NOT NULL DEFAULT 'recipient',
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`access_id`),
  KEY `server_id` (`server_id`,`source`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table  `mail_backup`
--

CREATE TABLE `mail_backup` (
  `backup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned NOT NULL DEFAULT '0',
  `parent_domain_id` int(10) unsigned NOT NULL DEFAULT '0',
  `mailuser_id` int(10) unsigned NOT NULL DEFAULT '0',
  `backup_mode` varchar(64) NOT NULL DEFAULT  '',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filesize` VARCHAR(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`backup_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `dkim` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `dkim_selector` varchar(63) NOT NULL DEFAULT 'default',
  `dkim_private` mediumtext NULL,
  `dkim_public` mediumtext NULL,
  `active` enum('n','y') NOT NULL DEFAULT 'n',
  PRIMARY KEY  (`domain_id`),
  KEY `server_id` (`server_id`,`domain`),
  KEY `domain_active` (`domain`,`active`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `source` varchar(255) NOT NULL DEFAULT '',
  `destination` text,
  `type` enum('alias','aliasdomain','forward','catchall') NOT NULL default 'alias',
  `active` enum('n','y') NOT NULL DEFAULT 'n',
  `allow_send_as` ENUM('n','y') NOT NULL DEFAULT 'n',
  `greylisting` enum('n','y' ) NOT NULL DEFAULT 'n',
  PRIMARY KEY  (`forwarding_id`),
  KEY `server_id` (`server_id`,`source`),
  KEY `type` (`type`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `source_read_all` varchar(255) NOT NULL default 'y',
  `destination` varchar(255) default NULL,
  `active` varchar(255) NOT NULL default 'y',
  PRIMARY KEY  (`mailget_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `mail_mailinglist`
--

CREATE TABLE `mail_mailinglist` (
  `mailinglist_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `server_id` int(11) unsigned NOT NULL default '0',
  `domain` varchar(255) NOT NULL DEFAULT '',
  `listname` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY  (`mailinglist_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `mail_traffic`
-- 

CREATE TABLE `mail_traffic` (
  `traffic_id` int(11) unsigned NOT NULL auto_increment,
  `mailuser_id` int(11) unsigned NOT NULL DEFAULT '0',
  `month` char(7) NOT NULL DEFAULT '',
  `traffic` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY  (`traffic_id`),
  KEY `mailuser_id` (`mailuser_id`,`month`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `transport` varchar(255) NOT NULL DEFAULT '',
  `sort_order` int(11) unsigned NOT NULL default '5',
  `active` enum('n','y') NOT NULL DEFAULT 'n',
  PRIMARY KEY  (`transport_id`),
  KEY `server_id` (`server_id`,`transport`),
  KEY `server_id_2` (`server_id`,`domain`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `login` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `name` varchar(255) NOT NULL default '',
  `uid` int(11) NOT NULL default '5000',
  `gid` int(11) NOT NULL default '5000',
  `maildir` varchar(255) NOT NULL default '',
  `maildir_format` varchar(255) NOT NULL default 'maildir',
  `quota` bigint(20) NOT NULL default '-1',
  `cc` varchar(255) NOT NULL default '',
  `sender_cc` varchar(255) NOT NULL default '',
  `homedir` varchar(255) NOT NULL default '',
  `autoresponder` enum('n','y') NOT NULL default 'n',
  `autoresponder_start_date` datetime NULL default NULL,
  `autoresponder_end_date` datetime NULL default NULL,
  `autoresponder_subject` varchar(255) NOT NULL default 'Out of office reply',
  `autoresponder_text` mediumtext NULL,
  `move_junk` enum('n','y') NOT NULL default 'n',
  `purge_trash_days` INT NOT NULL DEFAULT '0',
  `purge_junk_days` INT NOT NULL DEFAULT '0',
  `custom_mailfilter` mediumtext,
  `postfix` enum('n','y') NOT NULL default 'y',
  `greylisting` enum('n','y' ) NOT NULL DEFAULT 'n',
  `access` enum('n','y') NOT NULL default 'y',
  `disableimap` enum('n','y') NOT NULL default 'n',
  `disablepop3` enum('n','y') NOT NULL default 'n',
  `disabledeliver` enum('n','y') NOT NULL default 'n',
  `disablesmtp` enum('n','y') NOT NULL default 'n',
  `disablesieve` enum('n','y') NOT NULL default 'n',
  `disablesieve-filter` enum('n','y') NOT NULL default 'n',
  `disablelda` enum('n','y') NOT NULL default 'n',
  `disablelmtp` enum('n','y') NOT NULL default 'n',
  `disabledoveadm` enum('n','y') NOT NULL default 'n',
  `disablequota-status` enum('n','y') NOT NULL default 'n',
  `disableindexer-worker` enum('n','y') NOT NULL default 'n',
  `last_quota_notification` date NULL default NULL,
  `backup_interval` VARCHAR( 255 ) NOT NULL default 'none',
  `backup_copies` INT NOT NULL DEFAULT '1',
  PRIMARY KEY  (`mailuser_id`),
  KEY `server_id` (`server_id`,`email`),
  KEY `email_access` (`email`,`access`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `monitor_data`
--

CREATE TABLE `monitor_data` (
  `server_id` int(11) unsigned NOT NULL default '0',
  `type` varchar(255) NOT NULL default '',
  `created` int(11) unsigned NOT NULL default '0',
  `data` mediumtext,
  `state` enum('no_state','unknown','ok','info','warning','critical','error') NOT NULL DEFAULT 'unknown',
  PRIMARY KEY (`server_id`,`type`,`created`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `openvz_ip`
--

CREATE TABLE IF NOT EXISTS `openvz_ip` (
  `ip_address_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `ip_address` varchar(39) DEFAULT NULL,
  `vm_id` int(11) NOT NULL DEFAULT '0',
  `reserved` varchar(255) NOT NULL DEFAULT 'n',
  `additional` varchar(255) NOT NULL DEFAULT 'n',
  PRIMARY KEY (`ip_address_id`)
)  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `openvz_ip`
--

-- --------------------------------------------------------

--
-- Table structure for table `openvz_ostemplate`
--

CREATE TABLE IF NOT EXISTS `openvz_ostemplate` (
  `ostemplate_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `template_file` varchar(255) NOT NULL DEFAULT '',
  `server_id` int(11) NOT NULL DEFAULT '0',
  `allservers` varchar(255) NOT NULL DEFAULT 'y',
  `active` varchar(255) NOT NULL DEFAULT 'y',
  `description` text,
  PRIMARY KEY (`ostemplate_id`)
)  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `openvz_ostemplate`
--

INSERT INTO `openvz_ostemplate` (`ostemplate_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `template_name`, `template_file`, `server_id`, `allservers`, `active`, `description`) VALUES(1, 1, 1, 'riud', 'riud', '', 'Debian minimal', 'debian-minimal-x86', 1, 'y', 'y', 'Debian minimal image.');

-- --------------------------------------------------------

--
-- Table structure for table `openvz_template`
--

CREATE TABLE IF NOT EXISTS `openvz_template` (
  `template_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `diskspace` int(11) NOT NULL DEFAULT '0',
  `traffic` int(11) NOT NULL DEFAULT '-1',
  `bandwidth` int(11) NOT NULL DEFAULT '-1',
  `ram` int(11) NOT NULL DEFAULT '0',
  `ram_burst` int(11) NOT NULL DEFAULT '0',
  `cpu_units` int(11) NOT NULL DEFAULT '1000',
  `cpu_num` int(11) NOT NULL DEFAULT '4',
  `cpu_limit` int(11) NOT NULL DEFAULT '400',
  `io_priority` int(11) NOT NULL DEFAULT '4',
  `active` varchar(255) NOT NULL DEFAULT 'y',
  `description` text,
  `numproc` varchar(255) DEFAULT NULL,
  `numtcpsock` varchar(255) DEFAULT NULL,
  `numothersock` varchar(255) DEFAULT NULL,
  `vmguarpages` varchar(255) DEFAULT NULL,
  `kmemsize` varchar(255) DEFAULT NULL,
  `tcpsndbuf` varchar(255) DEFAULT NULL,
  `tcprcvbuf` varchar(255) DEFAULT NULL,
  `othersockbuf` varchar(255) DEFAULT NULL,
  `dgramrcvbuf` varchar(255) DEFAULT NULL,
  `oomguarpages` varchar(255) DEFAULT NULL,
  `privvmpages` varchar(255) DEFAULT NULL,
  `lockedpages` varchar(255) DEFAULT NULL,
  `shmpages` varchar(255) DEFAULT NULL,
  `physpages` varchar(255) DEFAULT NULL,
  `numfile` varchar(255) DEFAULT NULL,
  `avnumproc` varchar(255) DEFAULT NULL,
  `numflock` varchar(255) DEFAULT NULL,
  `numpty` varchar(255) DEFAULT NULL,
  `numsiginfo` varchar(255) DEFAULT NULL,
  `dcachesize` varchar(255) DEFAULT NULL,
  `numiptent` varchar(255) DEFAULT NULL,
  `swappages` varchar(255) DEFAULT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `nameserver` varchar(255) DEFAULT NULL,
  `create_dns` varchar(1) NOT NULL DEFAULT 'n',
  `capability` varchar(255) DEFAULT NULL,
  `features` varchar(255) DEFAULT NULL,
  `iptables` varchar(255) DEFAULT NULL,
  `custom` text,
  PRIMARY KEY (`template_id`)
)  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `openvz_template`
--

INSERT INTO `openvz_template` (`template_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `template_name`, `diskspace`, `traffic`, `bandwidth`, `ram`, `ram_burst`, `cpu_units`, `cpu_num`, `cpu_limit`, `io_priority`, `active`, `description`, `numproc`, `numtcpsock`, `numothersock`, `vmguarpages`, `kmemsize`, `tcpsndbuf`, `tcprcvbuf`, `othersockbuf`, `dgramrcvbuf`, `oomguarpages`, `privvmpages`, `lockedpages`, `shmpages`, `physpages`, `numfile`, `avnumproc`, `numflock`, `numpty`, `numsiginfo`, `dcachesize`, `numiptent`, `swappages`, `hostname`, `nameserver`, `create_dns`, `capability`, `features`, `iptables`, `custom`) VALUES(1, 1, 1, 'riud', 'riud', '', 'small', 10, -1, -1, 256, 512, 1000, 4, 400, 4, 'y', '', '999999:999999', '7999992:7999992', '7999992:7999992', '65536:unlimited', '2147483646:2147483646', '214748160:396774400', '214748160:396774400', '214748160:396774400', '214748160:396774400', '65536:65536', '131072:139264', '999999:999999', '65536:65536', '0:2147483647', '23999976:23999976', '180:180', '999999:999999', '500000:500000', '999999:999999', '2147483646:2147483646', '999999:999999', '256000:256000', 'v{VEID}.test.tld', '8.8.8.8 8.8.4.4', 'n', '', '', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `openvz_traffic`
--

CREATE TABLE IF NOT EXISTS `openvz_traffic` (
  `veid` int(11) NOT NULL DEFAULT '0',
  `traffic_date` date NULL DEFAULT NULL,
  `traffic_bytes` bigint(32) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY (`veid`,`traffic_date`)
) DEFAULT CHARSET=utf8;

--
-- Dumping data for table `openvz_traffic`
--


-- --------------------------------------------------------

--
-- Table structure for table `openvz_vm`
--

CREATE TABLE IF NOT EXISTS `openvz_vm` (
  `vm_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `veid` int(10) unsigned NOT NULL DEFAULT '0',
  `ostemplate_id` int(11) NOT NULL DEFAULT '0',
  `template_id` int(11) NOT NULL DEFAULT '0',
  `ip_address` varchar(255) NOT NULL DEFAULT '',
  `hostname` varchar(255) DEFAULT NULL,
  `vm_password` varchar(255) DEFAULT NULL,
  `start_boot` varchar(255) NOT NULL DEFAULT 'y',
  `bootorder` int(11) NOT NULL DEFAULT '1',
  `active` varchar(255) NOT NULL DEFAULT 'y',
  `active_until_date` date NULL DEFAULT NULL,
  `description` text,
  `diskspace` int(11) NOT NULL DEFAULT '0',
  `traffic` int(11) NOT NULL DEFAULT '-1',
  `bandwidth` int(11) NOT NULL DEFAULT '-1',
  `ram` int(11) NOT NULL DEFAULT '0',
  `ram_burst` int(11) NOT NULL DEFAULT '0',
  `cpu_units` int(11) NOT NULL DEFAULT '1000',
  `cpu_num` int(11) NOT NULL DEFAULT '4',
  `cpu_limit` int(11) NOT NULL DEFAULT '400',
  `io_priority` int(11) NOT NULL DEFAULT '4',
  `nameserver` varchar(255) NOT NULL DEFAULT '8.8.8.8 8.8.4.4',
  `create_dns` varchar(1) NOT NULL DEFAULT 'n',
  `capability` text,
  `features` text,
  `iptabless` text,
  `config` mediumtext,
  `custom` text,
  PRIMARY KEY (`vm_id`)
)  DEFAULT CHARSET=utf8 ;

--
-- Dumping data for table `openvz_vm`
--

-- --------------------------------------------------------

-- 
-- Table structure for table  `remote_session`
-- 

CREATE TABLE `remote_session` (
  `remote_session` varchar(64) NOT NULL DEFAULT '',
  `remote_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `remote_functions` text,
  `client_login` tinyint(1) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY  (`remote_session`)
) DEFAULT CHARSET=utf8 ;

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
  `remote_username` varchar(64) NOT NULL DEFAULT '',
  `remote_password` varchar(64) NOT NULL DEFAULT '',
  `remote_access` enum('y','n') NOT NULL DEFAULT 'y',
  `remote_ips` TEXT,
  `remote_functions` text,
  PRIMARY KEY  (`remote_userid`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `proxy_server` tinyint(1) NOT NULL default '0',
  `firewall_server` tinyint(1) NOT NULL default '0',
  `xmpp_server` tinyint(1) NOT NULL default '0',
  `config` text,
  `updated` bigint(20) unsigned NOT NULL default '0',
  `mirror_server_id` int(11) unsigned NOT NULL default '0',
  `dbversion` int(11) unsigned NOT NULL default '1',
  `active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`server_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `client_id` int(11) unsigned NOT NULL default '0',
  `ip_type` enum(  'IPv4',  'IPv6' ) NOT NULL DEFAULT  'IPv4',
  `ip_address` varchar(39) default NULL,
  `virtualhost` enum('n','y') NOT NULL default 'y',
  `virtualhost_port` varchar(255) default '80,443',
  PRIMARY KEY  (`server_ip_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `server_ip_map`
-- 

CREATE TABLE `server_ip_map` (
  `server_ip_map_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `source_ip` varchar(15) DEFAULT NULL,
  `destination_ip` varchar(35) DEFAULT '',
  `active` enum('n','y') NOT NULL DEFAULT 'y',
  PRIMARY KEY (`server_ip_map_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table  `server_php`
--

CREATE TABLE `server_php` (
  `server_php_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `client_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `php_fastcgi_binary` varchar(255) DEFAULT NULL,
  `php_fastcgi_ini_dir` varchar(255) DEFAULT NULL,
  `php_fpm_init_script` varchar(255) DEFAULT NULL,
  `php_fpm_ini_dir` varchar(255) DEFAULT NULL,
  `php_fpm_pool_dir` varchar(255) DEFAULT NULL,
  `active` enum('n','y') NOT NULL DEFAULT 'y',
  PRIMARY KEY (`server_php_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `shell_user`
--

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
  `username_prefix` varchar(50) NOT NULL default '',
  `password` varchar(200) default NULL,
  `quota_size` bigint(20) NOT NULL default '-1',
  `active` enum('n','y') NOT NULL default 'y',
  `puser` varchar(255) default NULL,
  `pgroup` varchar(255) default NULL,
  `shell` varchar(255) NOT NULL default '/bin/bash',
  `dir` varchar(255) default NULL,
  `chroot` varchar(255) NOT NULL DEFAULT '',
  `ssh_rsa` text,
  PRIMARY KEY  (`shell_user_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `software_package`
-- 

CREATE TABLE `software_package` (
  `package_id` int(11) unsigned NOT NULL auto_increment,
  `software_repo_id` int(11) unsigned NOT NULL DEFAULT '0',
  `package_name` varchar(64) NOT NULL DEFAULT '',
  `package_title` varchar(64) NOT NULL DEFAULT '',
  `package_description` text,
  `package_version` varchar(8) default NULL,
  `package_type` enum('ispconfig','app','web') NOT NULL default 'app',
  `package_installable` enum('yes','no','key') NOT NULL default 'yes',
  `package_requires_db` enum('no','mysql') NOT NULL default 'no',
  `package_remote_functions` text,
  `package_key` varchar(255) NOT NULL DEFAULT '',
  `package_config` text,
  PRIMARY KEY  (`package_id`),
  UNIQUE KEY `package_name` (`package_name`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `software_update`
-- 

CREATE TABLE `software_update` (
  `software_update_id` int(11) unsigned NOT NULL auto_increment,
  `software_repo_id` int(11) unsigned NOT NULL DEFAULT '0',
  `package_name` varchar(64) NOT NULL DEFAULT '',
  `update_url` varchar(255) NOT NULL DEFAULT '',
  `update_md5` varchar(255) NOT NULL DEFAULT '',
  `update_dependencies` varchar(255) NOT NULL DEFAULT '',
  `update_title` varchar(64) NOT NULL DEFAULT '',
  `v1` tinyint(1) NOT NULL default '0',
  `v2` tinyint(1) NOT NULL default '0',
  `v3` tinyint(1) NOT NULL default '0',
  `v4` tinyint(1) NOT NULL default '0',
  `type` enum('full','update') NOT NULL default 'full',
  PRIMARY KEY  (`software_update_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `software_update_inst`
-- 

CREATE TABLE `software_update_inst` (
  `software_update_inst_id` int(11) unsigned NOT NULL auto_increment,
  `software_update_id` int(11) unsigned NOT NULL default '0',
  `package_name` varchar(64) NOT NULL DEFAULT '',
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `status` enum('none','installing','installed','deleting','deleted','failed') NOT NULL default 'none',
  PRIMARY KEY  (`software_update_inst_id`),
  UNIQUE KEY `software_update_id` (`software_update_id`,`package_name`,`server_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `spamfilter_policy`
-- 

CREATE TABLE `spamfilter_policy` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `policy_name` varchar(64) default NULL,
  `virus_lover` enum('N','Y') default 'N',
  `spam_lover` enum('N','Y') default 'N',
  `banned_files_lover` enum('N','Y') default 'N',
  `bad_header_lover` enum('N','Y') default 'N',
  `bypass_virus_checks` enum('N','Y') default 'N',
  `bypass_spam_checks` enum('N','Y') default 'N',
  `bypass_banned_checks` enum('N','Y') default 'N',
  `bypass_header_checks` enum('N','Y') default 'N',
  `spam_modifies_subj` enum('N','Y') default 'N',
  `virus_quarantine_to` varchar(255) default NULL,
  `spam_quarantine_to` varchar(255) default NULL,
  `banned_quarantine_to` varchar(255) default NULL,
  `bad_header_quarantine_to` varchar(255) default NULL,
  `clean_quarantine_to` varchar(255) default NULL,
  `other_quarantine_to` varchar(255) default NULL,
  `spam_tag_level` DECIMAL(5,2) default NULL,
  `spam_tag2_level` DECIMAL(5,2) default NULL,
  `spam_kill_level` DECIMAL(5,2) default NULL,
  `spam_dsn_cutoff_level` DECIMAL(5,2) default NULL,
  `spam_quarantine_cutoff_level` DECIMAL(5,2) default NULL,
  `addr_extension_virus` varchar(64) default NULL,
  `addr_extension_spam` varchar(64) default NULL,
  `addr_extension_banned` varchar(64) default NULL,
  `addr_extension_bad_header` varchar(64) default NULL,
  `warnvirusrecip` enum('N','Y') default 'N',
  `warnbannedrecip` enum('N','Y') default 'N',
  `warnbadhrecip` enum('N','Y') default 'N',
  `newvirus_admin` varchar(64) default NULL,
  `virus_admin` varchar(64) default NULL,
  `banned_admin` varchar(64) default NULL,
  `bad_header_admin` varchar(64) default NULL,
  `spam_admin` varchar(64) default NULL,
  `spam_subject_tag` varchar(64) default NULL,
  `spam_subject_tag2` varchar(64) default NULL,
  `message_size_limit` int(11) unsigned default NULL,
  `banned_rulenames` varchar(64) default NULL,
  `policyd_quota_in` int(11) NOT NULL DEFAULT  '-1',
  `policyd_quota_in_period` int(11) NOT NULL DEFAULT  '24',
  `policyd_quota_out` int(11) NOT NULL DEFAULT  '-1',
  `policyd_quota_out_period` int(11) NOT NULL DEFAULT  '24',
  `policyd_greylist` ENUM(  'Y',  'N' ) NOT NULL DEFAULT  'N',
  `rspamd_greylisting` enum('n','y') NOT NULL DEFAULT 'n',
  `rspamd_spam_greylisting_level` decimal(5,2) DEFAULT NULL,
  `rspamd_spam_tag_level` decimal(5,2) DEFAULT NULL,
  `rspamd_spam_tag_method` enum('add_header','rewrite_subject') NOT NULL DEFAULT 'rewrite_subject',
  `rspamd_spam_kill_level` decimal(5,2) DEFAULT NULL,
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `spamfilter_users`
-- 

CREATE TABLE `spamfilter_users` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `priority` tinyint(3) unsigned NOT NULL default '7',
  `policy_id` int(11) unsigned NOT NULL default '1',
  `email` varchar(255) NOT NULL DEFAULT '',
  `fullname` varchar(64) default NULL,
  `local` varchar(1) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` (`email`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `spamfilter_wblist`
-- 

CREATE TABLE `spamfilter_wblist` (
  `wblist_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `wb` enum('W','B') NOT NULL default 'W',
  `rid` int(11) unsigned NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL DEFAULT '',
  `priority` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `active` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`wblist_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_config`
--

CREATE TABLE `sys_config` (
  `group` varchar(64) NOT NULL DEFAULT '',
  `name` varchar(64) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`group`, `name`)
) DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table `sys_cron`
--

CREATE TABLE IF NOT EXISTS `sys_cron` (
  `name` varchar(50) NOT NULL DEFAULT '',
  `last_run` datetime NULL DEFAULT NULL,
  `next_run` datetime NULL DEFAULT NULL,
  `running` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Table structure for table  `sys_datalog`
--

CREATE TABLE `sys_datalog` (
  `datalog_id` int(11) unsigned NOT NULL auto_increment,
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `dbtable` varchar(255) NOT NULL default '',
  `dbidx` varchar(255) NOT NULL default '',
  `action` char(1) NOT NULL default '',
  `tstamp` int(11) NOT NULL default '0',
  `user` varchar(255) NOT NULL default '',
  `data` longtext,
  `status` set('pending','ok','warning','error') NOT NULL default 'ok',
  `error` mediumtext,
  `session_id` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY  (`datalog_id`),
  KEY `server_id` (`server_id`,`status`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_group`
-- 

CREATE TABLE `sys_group` (
  `groupid` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `description` text,
  `client_id` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`groupid`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_ini`
-- 

CREATE TABLE `sys_ini` (
  `sysini_id` int(11) unsigned NOT NULL auto_increment,
  `config` longtext,
  `default_logo` text NOT NULL,
  `custom_logo` text NOT NULL,
  PRIMARY KEY  (`sysini_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_log`
-- 

CREATE TABLE `sys_log` (
  `syslog_id` int(11) unsigned NOT NULL auto_increment,
  `server_id` int(11) unsigned NOT NULL default '0',
  `datalog_id` int(11) unsigned NOT NULL default '0',
  `loglevel` tinyint(4) NOT NULL default '0',
  `tstamp` int(11) unsigned NOT NULL DEFAULT '0',
  `message` text,
  PRIMARY KEY  (`syslog_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_remoteaction`
--

CREATE TABLE `sys_remoteaction` (
  `action_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `tstamp` int(11) NOT NULL DEFAULT '0',
  `action_type` varchar(20) NOT NULL DEFAULT '',
  `action_param` mediumtext,
  `action_state` enum('pending','ok','warning','error') NOT NULL DEFAULT 'pending',
  `response` mediumtext,
  PRIMARY KEY (`action_id`),
  KEY `server_id` (`server_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_session`
--

CREATE TABLE `sys_session` (
  `session_id` varchar(64) NOT NULL DEFAULT '',
  `date_created` datetime NULL DEFAULT NULL,
  `last_updated` datetime NULL DEFAULT NULL,
  `permanent` enum('n','y') NOT NULL DEFAULT 'n',
  `session_data` longtext,
  PRIMARY KEY (`session_id`),
  KEY `last_updated` (`last_updated`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_theme`
--

CREATE TABLE IF NOT EXISTS `sys_theme` (
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `var_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tpl_name` varchar(32) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  `logo_url` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`var_id`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

-- 
-- Table structure for table  `sys_user`
-- 

CREATE TABLE `sys_user` (
  `userid` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '1' COMMENT 'Created by userid',
  `sys_groupid` int(11) unsigned NOT NULL default '1' COMMENT 'Created by groupid',
  `sys_perm_user` varchar(5) NOT NULL default 'riud',
  `sys_perm_group` varchar(5) NOT NULL default 'riud',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `username` varchar(64) NOT NULL default '',
  `passwort` varchar(200) NOT NULL default '',
  `modules` varchar(255) NOT NULL default '',
  `startmodule` varchar(255) NOT NULL default '',
  `app_theme` varchar(32) NOT NULL default 'default',
  `typ` varchar(16) NOT NULL default 'user',
  `active` tinyint(1) NOT NULL default '1',
  `language` varchar(2) NOT NULL default 'en',
  `groups` TEXT,
  `default_group` int(11) unsigned NOT NULL default '0',
  `client_id` int(11) unsigned NOT NULL default '0',
  `id_rsa` VARCHAR( 2000 ) NOT NULL default '',
  `ssh_rsa` VARCHAR( 600 ) NOT NULL default '',
  `lost_password_function` tinyint(1) NOT NULL default '1',
  `lost_password_hash` VARCHAR(50) NOT NULL default '',
  `lost_password_reqtime` DATETIME NULL default NULL,
  PRIMARY KEY  (`userid`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `webdav_user`
--

CREATE TABLE `webdav_user` (
  `webdav_user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `parent_domain_id` int(11) unsigned NOT NULL DEFAULT '0',
  `username` varchar(64) DEFAULT NULL,
  `username_prefix` varchar(50) NOT NULL default '',
  `password` varchar(200) DEFAULT NULL,
  `active` enum('n','y') NOT NULL DEFAULT 'y',
  `dir` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`webdav_user_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `web_backup`
--

CREATE TABLE `web_backup` (
  `backup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned NOT NULL DEFAULT '0',
  `parent_domain_id` int(10) unsigned NOT NULL DEFAULT '0',
  `backup_type` enum('web','mysql','mongodb') NOT NULL DEFAULT 'web',
  `backup_mode` varchar(64) NOT NULL DEFAULT  '',
  `backup_format` varchar(64) NOT NULL DEFAULT '',
  `tstamp` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filesize` VARCHAR(20) NOT NULL DEFAULT '',
  `backup_password` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`backup_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `web_database`
--

CREATE TABLE `web_database` (
  `database_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `parent_domain_id` int(11) unsigned NOT NULL DEFAULT  '0',
  `type` varchar(16) NOT NULL DEFAULT 'y',
  `database_name` varchar(64) DEFAULT NULL,
  `database_name_prefix` varchar(50) NOT NULL default '',
  `database_quota` int(11) DEFAULT NULL,
  `quota_exceeded` enum('n','y') NOT NULL DEFAULT 'n',
  `last_quota_notification` date NULL default NULL,
  `database_user_id` int(11) unsigned DEFAULT NULL,
  `database_ro_user_id` int(11) unsigned DEFAULT NULL,
  `database_charset` varchar(64) DEFAULT NULL,
  `remote_access` enum('n','y') NOT NULL DEFAULT 'y',
  `remote_ips` text,
  `backup_interval` VARCHAR( 255 ) NOT NULL DEFAULT 'none',
  `backup_copies` INT NOT NULL DEFAULT '1',
  `active` enum('n','y') NOT NULL DEFAULT 'y',
  PRIMARY KEY (`database_id`),
  KEY `database_user_id` (`database_user_id`),
  KEY `database_ro_user_id` (`database_ro_user_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `web_database_user`
--

CREATE TABLE IF NOT EXISTS `web_database_user` (
  `database_user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `database_user` varchar(64) DEFAULT NULL,
  `database_user_prefix` varchar(50) NOT NULL default '',
  `database_password` varchar(64) DEFAULT NULL,
  `database_password_mongo` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`database_user_id`)
)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
  `ipv6_address` VARCHAR( 255 ) default NULL,
  `domain` varchar(255) default NULL,
  `type` varchar(32) default NULL,
  `parent_domain_id` int(11) unsigned NOT NULL default '0',
  `vhost_type` varchar(32) default NULL,
  `document_root` varchar(255) default NULL,
  `web_folder` varchar(100) default NULL,
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
  `python` enum('n','y') NOT NULL default 'n',
  `perl` enum('n','y') NOT NULL default 'n',
  `redirect_type` varchar(255) default NULL,
  `redirect_path` varchar(255) default NULL,
  `seo_redirect` varchar(255) default NULL,
  `rewrite_to_https` ENUM('y','n') NOT NULL DEFAULT 'n',
  `ssl` enum('n','y') NOT NULL default 'n',
  `ssl_letsencrypt` enum('n','y') NOT NULL DEFAULT 'n',
  `ssl_letsencrypt_exclude` enum('n','y') NOT NULL DEFAULT 'n',
  `ssl_state` varchar(255) NULL,
  `ssl_locality` varchar(255) NULL,
  `ssl_organisation` varchar(255) NULL,
  `ssl_organisation_unit` varchar(255) NULL,
  `ssl_country` varchar(255) NULL,
  `ssl_domain` varchar(255) NULL,
  `ssl_request` mediumtext NULL,
  `ssl_cert` mediumtext NULL,
  `ssl_bundle` mediumtext NULL,
  `ssl_key` mediumtext NULL,
  `ssl_action` varchar(16) NULL,
  `stats_password` varchar(255) default NULL,
  `stats_type` varchar(255) default 'awstats',
  `allow_override` varchar(255) NOT NULL default 'All',
  `apache_directives` mediumtext,
  `nginx_directives` mediumtext,
  `php_fpm_use_socket` ENUM('n','y') NOT NULL DEFAULT 'y',
  `php_fpm_chroot` enum('n','y') NOT NULL DEFAULT 'n',
  `pm` enum('static','dynamic','ondemand') NOT NULL DEFAULT 'ondemand',
  `pm_max_children` int(11) NOT NULL DEFAULT '10',
  `pm_start_servers` int(11) NOT NULL DEFAULT '2',
  `pm_min_spare_servers` int(11) NOT NULL DEFAULT '1',
  `pm_max_spare_servers` int(11) NOT NULL DEFAULT '5',
  `pm_process_idle_timeout` int(11) NOT NULL DEFAULT '10',
  `pm_max_requests` int(11) NOT NULL DEFAULT '0',
  `php_open_basedir` mediumtext,
  `custom_php_ini` mediumtext,
  `backup_interval` VARCHAR( 255 ) NOT NULL DEFAULT 'none',
  `backup_copies` INT NOT NULL DEFAULT '1',
  `backup_format_web` VARCHAR( 255 ) NOT NULL default 'default',
  `backup_format_db` VARCHAR( 255 ) NOT NULL default 'gzip',
  `backup_encrypt` enum('n','y') NOT NULL DEFAULT 'n',
  `backup_password` VARCHAR( 255 ) NOT NULL DEFAULT '',
  `backup_excludes` mediumtext,
  `active` enum('n','y') NOT NULL default 'y',
  `traffic_quota_lock` enum('n','y') NOT NULL default 'n',
  `fastcgi_php_version` varchar(255) DEFAULT NULL,
  `proxy_directives` mediumtext,
  `enable_spdy` ENUM('y','n') NULL DEFAULT 'n',
  `last_quota_notification` date NULL default NULL,
  `rewrite_rules` mediumtext,
  `added_date` date NULL DEFAULT NULL,
  `added_by` varchar(255) DEFAULT NULL,
  `directive_snippets_id` int(11) unsigned NOT NULL default '0',
  `enable_pagespeed` ENUM('y','n') NOT NULL DEFAULT 'n',
  `http_port` int(11) unsigned NOT NULL DEFAULT '80',
  `https_port` int(11) unsigned NOT NULL DEFAULT '443',
  `folder_directive_snippets` text,
  `log_retention` int(11) NOT NULL DEFAULT '10',
  `proxy_protocol` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`domain_id`),
  UNIQUE KEY `serverdomain` (  `server_id` , `ip_address`,  `domain` )
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `web_folder`
--

CREATE TABLE IF NOT EXISTS `web_folder` (
  `web_folder_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `parent_domain_id` int(11) NOT NULL DEFAULT '0',
  `path` varchar(255) DEFAULT NULL,
  `active` varchar(255) NOT NULL DEFAULT 'y',
  PRIMARY KEY (`web_folder_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `web_folder`
--


-- --------------------------------------------------------

--
-- Table structure for table `web_folder_user`
--

CREATE TABLE IF NOT EXISTS `web_folder_user` (
  `web_folder_user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `web_folder_id` int(11) NOT NULL DEFAULT '0',
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `active` varchar(255) NOT NULL DEFAULT 'y',
  PRIMARY KEY (`web_folder_user_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `web_folder_user`
--

-- --------------------------------------------------------

--
-- Table structure for table  `web_traffic`
--

CREATE TABLE `web_traffic` (
  `hostname` varchar(255) NOT NULL DEFAULT '',
  `traffic_date` date NULL DEFAULT NULL,
  `traffic_bytes` bigint(32) unsigned NOT NULL default '0',
  UNIQUE KEY  (`hostname`,`traffic_date`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `xmpp_domain`
--

CREATE TABLE `xmpp_domain` (
  `domain_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) unsigned NOT NULL default '0',
  `domain` varchar(255) NOT NULL default '',

  `management_method` ENUM( 'normal', 'maildomain' ) NOT NULL default 'normal',
  `public_registration` ENUM( 'n', 'y' ) NOT NULL default 'n',
  `registration_url` varchar(255) NOT NULL DEFAULT '',
  `registration_message` varchar(255) NOT NULL DEFAULT '',
  `domain_admins` text,

  `use_pubsub` enum('n','y') NOT NULL DEFAULT 'n',
  `use_proxy` enum('n','y') NOT NULL DEFAULT 'n',
  `use_anon_host` enum('n','y') NOT NULL DEFAULT 'n',

  `use_vjud` enum('n','y') NOT NULL DEFAULT 'n',
  `vjud_opt_mode` enum('in', 'out') NOT NULL DEFAULT 'in',

  `use_muc_host` enum('n','y') NOT NULL DEFAULT 'n',
  `muc_name` varchar(30) NOT NULL DEFAULT '',
  `muc_restrict_room_creation` enum('n', 'y', 'm') NOT NULL DEFAULT 'm',
  `muc_admins` text,
  `use_pastebin` enum('n','y') NOT NULL DEFAULT 'n',
  `pastebin_expire_after` int(3) NOT NULL DEFAULT 48,
  `pastebin_trigger` varchar(10) NOT NULL DEFAULT '!paste',
  `use_http_archive` enum('n','y') NOT NULL DEFAULT 'n',
  `http_archive_show_join` enum('n', 'y') NOT NULL DEFAULT 'n',
  `http_archive_show_status` enum('n', 'y') NOT NULL DEFAULT 'n',
  `use_status_host` enum('n','y') NOT NULL DEFAULT 'n',

  `ssl_state` varchar(255) NULL,
  `ssl_locality` varchar(255) NULL,
  `ssl_organisation` varchar(255) NULL,
  `ssl_organisation_unit` varchar(255) NULL,
  `ssl_country` varchar(255) NULL,
  `ssl_email` varchar(255) NULL,
  `ssl_request` mediumtext NULL,
  `ssl_cert` mediumtext NULL,
  `ssl_bundle` mediumtext NULL,
  `ssl_key` mediumtext NULL,
  `ssl_action` varchar(16) NULL,

  `active` enum('n','y') NOT NULL DEFAULT 'n',
  PRIMARY KEY  (`domain_id`),
  KEY `server_id` (`server_id`,`domain`),
  KEY `domain_active` (`domain`,`active`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table  `xmpp_user`
--

CREATE TABLE `xmpp_user` (
  `xmppuser_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL default '',
  `sys_perm_group` varchar(5) NOT NULL default '',
  `sys_perm_other` varchar(5) NOT NULL default '',
  `server_id` int(11) unsigned NOT NULL default '0',
  `jid` varchar(255) NOT NULL default '',
  `password` varchar(255) NOT NULL default '',
  `active` enum('n','y') NOT NULL DEFAULT 'n',
  PRIMARY KEY  (`xmppuser_id`),
  KEY `server_id` (`server_id`,`jid`),
  KEY `jid_active` (`jid`,`active`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- --------------------------------------------------------
-- --------------------------------------------------------
-- DB-DATA
-- --------------------------------------------------------
-- --------------------------------------------------------

--
-- Dumping data for table `aps_settings`
--

INSERT INTO `aps_settings` (`id`, `name`, `value`) VALUES(1, 'ignore-php-extension', '');
INSERT INTO `aps_settings` (`id`, `name`, `value`) VALUES(2, 'ignore-php-configuration', '');
INSERT INTO `aps_settings` (`id`, `name`, `value`) VALUES(3, 'ignore-webserver-module', '');

-- --------------------------------------------------------

--
-- Dumping data for table `country`
--

INSERT INTO `country` (`iso`, `name`, `printable_name`, `iso3`, `numcode`, `eu`) VALUES
('AF', 'AFGHANISTAN', 'Afghanistan', 'AFG', 4, 'n'),
('AL', 'ALBANIA', 'Albania', 'ALB', 8, 'n'),
('DZ', 'ALGERIA', 'Algeria', 'DZA', 12, 'n'),
('AS', 'AMERICAN SAMOA', 'American Samoa', 'ASM', 16, 'n'),
('AD', 'ANDORRA', 'Andorra', 'AND', 20, 'n'),
('AO', 'ANGOLA', 'Angola', 'AGO', 24, 'n'),
('AI', 'ANGUILLA', 'Anguilla', 'AIA', 660, 'n'),
('AQ', 'ANTARCTICA', 'Antarctica', NULL, NULL, 'n'),
('AG', 'ANTIGUA AND BARBUDA', 'Antigua and Barbuda', 'ATG', 28, 'n'),
('AR', 'ARGENTINA', 'Argentina', 'ARG', 32, 'n'),
('AM', 'ARMENIA', 'Armenia', 'ARM', 51, 'n'),
('AW', 'ARUBA', 'Aruba', 'ABW', 533, 'n'),
('AU', 'AUSTRALIA', 'Australia', 'AUS', 36, 'n'),
('AT', 'AUSTRIA', 'Austria', 'AUT', 40, 'y'),
('AZ', 'AZERBAIJAN', 'Azerbaijan', 'AZE', 31, 'n'),
('BS', 'BAHAMAS', 'Bahamas', 'BHS', 44, 'n'),
('BH', 'BAHRAIN', 'Bahrain', 'BHR', 48, 'n'),
('BD', 'BANGLADESH', 'Bangladesh', 'BGD', 50, 'n'),
('BB', 'BARBADOS', 'Barbados', 'BRB', 52, 'n'),
('BY', 'BELARUS', 'Belarus', 'BLR', 112, 'n'),
('BE', 'BELGIUM', 'Belgium', 'BEL', 56, 'y'),
('BZ', 'BELIZE', 'Belize', 'BLZ', 84, 'n'),
('BJ', 'BENIN', 'Benin', 'BEN', 204, 'n'),
('BM', 'BERMUDA', 'Bermuda', 'BMU', 60, 'n'),
('BT', 'BHUTAN', 'Bhutan', 'BTN', 64, 'n'),
('BO', 'BOLIVIA', 'Bolivia', 'BOL', 68, 'n'),
('BA', 'BOSNIA AND HERZEGOVINA', 'Bosnia and Herzegovina', 'BIH', 70, 'n'),
('BW', 'BOTSWANA', 'Botswana', 'BWA', 72, 'n'),
('BV', 'BOUVET ISLAND', 'Bouvet Island', NULL, NULL, 'n'),
('BR', 'BRAZIL', 'Brazil', 'BRA', 76, 'n'),
('IO', 'BRITISH INDIAN OCEAN TERRITORY', 'British Indian Ocean Territory', NULL, NULL, 'n'),
('BN', 'BRUNEI DARUSSALAM', 'Brunei Darussalam', 'BRN', 96, 'n'),
('BG', 'BULGARIA', 'Bulgaria', 'BGR', 100, 'y'),
('BF', 'BURKINA FASO', 'Burkina Faso', 'BFA', 854, 'n'),
('BI', 'BURUNDI', 'Burundi', 'BDI', 108, 'n'),
('KH', 'CAMBODIA', 'Cambodia', 'KHM', 116, 'n'),
('CM', 'CAMEROON', 'Cameroon', 'CMR', 120, 'n'),
('CA', 'CANADA', 'Canada', 'CAN', 124, 'n'),
('CV', 'CAPE VERDE', 'Cape Verde', 'CPV', 132, 'n'),
('KY', 'CAYMAN ISLANDS', 'Cayman Islands', 'CYM', 136, 'n'),
('CF', 'CENTRAL AFRICAN REPUBLIC', 'Central African Republic', 'CAF', 140, 'n'),
('TD', 'CHAD', 'Chad', 'TCD', 148, 'n'),
('CL', 'CHILE', 'Chile', 'CHL', 152, 'n'),
('CN', 'CHINA', 'China', 'CHN', 156, 'n'),
('CX', 'CHRISTMAS ISLAND', 'Christmas Island', NULL, NULL, 'n'),
('CC', 'COCOS (KEELING) ISLANDS', 'Cocos (Keeling) Islands', NULL, NULL, 'n'),
('CO', 'COLOMBIA', 'Colombia', 'COL', 170, 'n'),
('KM', 'COMOROS', 'Comoros', 'COM', 174, 'n'),
('CG', 'CONGO', 'Congo', 'COG', 178, 'n'),
('CD', 'CONGO, THE DEMOCRATIC REPUBLIC OF THE', 'Congo, the Democratic Republic of the', 'COD', 180, 'n'),
('CK', 'COOK ISLANDS', 'Cook Islands', 'COK', 184, 'n'),
('CR', 'COSTA RICA', 'Costa Rica', 'CRI', 188, 'n'),
('CI', 'COTE D''IVOIRE', 'Cote D''Ivoire', 'CIV', 384, 'n'),
('HR', 'CROATIA', 'Croatia', 'HRV', 191, 'y'),
('CU', 'CUBA', 'Cuba', 'CUB', 192, 'n'),
('CY', 'CYPRUS', 'Cyprus', 'CYP', 196, 'y'),
('CZ', 'CZECH REPUBLIC', 'Czech Republic', 'CZE', 203, 'y'),
('DK', 'DENMARK', 'Denmark', 'DNK', 208, 'y'),
('DJ', 'DJIBOUTI', 'Djibouti', 'DJI', 262, 'n'),
('DM', 'DOMINICA', 'Dominica', 'DMA', 212, 'n'),
('DO', 'DOMINICAN REPUBLIC', 'Dominican Republic', 'DOM', 214, 'n'),
('EC', 'ECUADOR', 'Ecuador', 'ECU', 218, 'n'),
('EG', 'EGYPT', 'Egypt', 'EGY', 818, 'n'),
('SV', 'EL SALVADOR', 'El Salvador', 'SLV', 222, 'n'),
('GQ', 'EQUATORIAL GUINEA', 'Equatorial Guinea', 'GNQ', 226, 'n'),
('ER', 'ERITREA', 'Eritrea', 'ERI', 232, 'n'),
('EE', 'ESTONIA', 'Estonia', 'EST', 233, 'y'),
('ET', 'ETHIOPIA', 'Ethiopia', 'ETH', 231, 'n'),
('FK', 'FALKLAND ISLANDS (MALVINAS)', 'Falkland Islands (Malvinas)', 'FLK', 238, 'n'),
('FO', 'FAROE ISLANDS', 'Faroe Islands', 'FRO', 234, 'n'),
('FJ', 'FIJI', 'Fiji', 'FJI', 242, 'n'),
('FI', 'FINLAND', 'Finland', 'FIN', 246, 'y'),
('FR', 'FRANCE', 'France', 'FRA', 250, 'y'),
('GF', 'FRENCH GUIANA', 'French Guiana', 'GUF', 254, 'n'),
('PF', 'FRENCH POLYNESIA', 'French Polynesia', 'PYF', 258, 'n'),
('TF', 'FRENCH SOUTHERN TERRITORIES', 'French Southern Territories', NULL, NULL, 'n'),
('GA', 'GABON', 'Gabon', 'GAB', 266, 'n'),
('GM', 'GAMBIA', 'Gambia', 'GMB', 270, 'n'),
('GE', 'GEORGIA', 'Georgia', 'GEO', 268, 'n'),
('DE', 'GERMANY', 'Germany', 'DEU', 276, 'y'),
('GH', 'GHANA', 'Ghana', 'GHA', 288, 'n'),
('GI', 'GIBRALTAR', 'Gibraltar', 'GIB', 292, 'n'),
('GR', 'GREECE', 'Greece', 'GRC', 300, 'y'),
('GL', 'GREENLAND', 'Greenland', 'GRL', 304, 'n'),
('GD', 'GRENADA', 'Grenada', 'GRD', 308, 'n'),
('GP', 'GUADELOUPE', 'Guadeloupe', 'GLP', 312, 'n'),
('GU', 'GUAM', 'Guam', 'GUM', 316, 'n'),
('GT', 'GUATEMALA', 'Guatemala', 'GTM', 320, 'n'),
('GN', 'GUINEA', 'Guinea', 'GIN', 324, 'n'),
('GW', 'GUINEA-BISSAU', 'Guinea-Bissau', 'GNB', 624, 'n'),
('GY', 'GUYANA', 'Guyana', 'GUY', 328, 'n'),
('HT', 'HAITI', 'Haiti', 'HTI', 332, 'n'),
('HM', 'HEARD ISLAND AND MCDONALD ISLANDS', 'Heard Island and Mcdonald Islands', NULL, NULL, 'n'),
('VA', 'HOLY SEE (VATICAN CITY STATE)', 'Holy See (Vatican City State)', 'VAT', 336, 'n'),
('HN', 'HONDURAS', 'Honduras', 'HND', 340, 'n'),
('HK', 'HONG KONG', 'Hong Kong', 'HKG', 344, 'n'),
('HU', 'HUNGARY', 'Hungary', 'HUN', 348, 'y'),
('IS', 'ICELAND', 'Iceland', 'ISL', 352, 'n'),
('IN', 'INDIA', 'India', 'IND', 356, 'n'),
('ID', 'INDONESIA', 'Indonesia', 'IDN', 360, 'n'),
('IR', 'IRAN, ISLAMIC REPUBLIC OF', 'Iran, Islamic Republic of', 'IRN', 364, 'n'),
('IQ', 'IRAQ', 'Iraq', 'IRQ', 368, 'n'),
('IE', 'IRELAND', 'Ireland', 'IRL', 372, 'y'),
('IL', 'ISRAEL', 'Israel', 'ISR', 376, 'n'),
('IT', 'ITALY', 'Italy', 'ITA', 380, 'y'),
('JM', 'JAMAICA', 'Jamaica', 'JAM', 388, 'n'),
('JP', 'JAPAN', 'Japan', 'JPN', 392, 'n'),
('JO', 'JORDAN', 'Jordan', 'JOR', 400, 'n'),
('KZ', 'KAZAKHSTAN', 'Kazakhstan', 'KAZ', 398, 'n'),
('KE', 'KENYA', 'Kenya', 'KEN', 404, 'n'),
('KI', 'KIRIBATI', 'Kiribati', 'KIR', 296, 'n'),
('KP', 'KOREA, DEMOCRATIC PEOPLE''S REPUBLIC OF', 'Korea, Democratic People''s Republic of', 'PRK', 408, 'n'),
('KR', 'KOREA, REPUBLIC OF', 'Korea, Republic of', 'KOR', 410, 'n'),
('KW', 'KUWAIT', 'Kuwait', 'KWT', 414, 'n'),
('KG', 'KYRGYZSTAN', 'Kyrgyzstan', 'KGZ', 417, 'n'),
('LA', 'LAO PEOPLE''S DEMOCRATIC REPUBLIC', 'Lao People''s Democratic Republic', 'LAO', 418, 'n'),
('LV', 'LATVIA', 'Latvia', 'LVA', 428, 'y'),
('LB', 'LEBANON', 'Lebanon', 'LBN', 422, 'n'),
('LS', 'LESOTHO', 'Lesotho', 'LSO', 426, 'n'),
('LR', 'LIBERIA', 'Liberia', 'LBR', 430, 'n'),
('LY', 'LIBYAN ARAB JAMAHIRIYA', 'Libyan Arab Jamahiriya', 'LBY', 434, 'n'),
('LI', 'LIECHTENSTEIN', 'Liechtenstein', 'LIE', 438, 'n'),
('LT', 'LITHUANIA', 'Lithuania', 'LTU', 440, 'y'),
('LU', 'LUXEMBOURG', 'Luxembourg', 'LUX', 442, 'y'),
('MO', 'MACAO', 'Macao', 'MAC', 446, 'n'),
('MK', 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'Macedonia, the Former Yugoslav Republic of', 'MKD', 807, 'n'),
('MG', 'MADAGASCAR', 'Madagascar', 'MDG', 450, 'n'),
('MW', 'MALAWI', 'Malawi', 'MWI', 454, 'n'),
('MY', 'MALAYSIA', 'Malaysia', 'MYS', 458, 'n'),
('MV', 'MALDIVES', 'Maldives', 'MDV', 462, 'n'),
('ML', 'MALI', 'Mali', 'MLI', 466, 'n'),
('MT', 'MALTA', 'Malta', 'MLT', 470, 'y'),
('MH', 'MARSHALL ISLANDS', 'Marshall Islands', 'MHL', 584, 'n'),
('MQ', 'MARTINIQUE', 'Martinique', 'MTQ', 474, 'n'),
('MR', 'MAURITANIA', 'Mauritania', 'MRT', 478, 'n'),
('MU', 'MAURITIUS', 'Mauritius', 'MUS', 480, 'n'),
('YT', 'MAYOTTE', 'Mayotte', NULL, NULL, 'n'),
('MX', 'MEXICO', 'Mexico', 'MEX', 484, 'n'),
('FM', 'MICRONESIA, FEDERATED STATES OF', 'Micronesia, Federated States of', 'FSM', 583, 'n'),
('MD', 'MOLDOVA, REPUBLIC OF', 'Moldova, Republic of', 'MDA', 498, 'n'),
('MC', 'MONACO', 'Monaco', 'MCO', 492, 'n'),
('MN', 'MONGOLIA', 'Mongolia', 'MNG', 496, 'n'),
('MS', 'MONTSERRAT', 'Montserrat', 'MSR', 500, 'n'),
('MA', 'MOROCCO', 'Morocco', 'MAR', 504, 'n'),
('MZ', 'MOZAMBIQUE', 'Mozambique', 'MOZ', 508, 'n'),
('MM', 'MYANMAR', 'Myanmar', 'MMR', 104, 'n'),
('NA', 'NAMIBIA', 'Namibia', 'NAM', 516, 'n'),
('NR', 'NAURU', 'Nauru', 'NRU', 520, 'n'),
('NP', 'NEPAL', 'Nepal', 'NPL', 524, 'n'),
('NL', 'NETHERLANDS', 'Netherlands', 'NLD', 528, 'y'),
('AN', 'NETHERLANDS ANTILLES', 'Netherlands Antilles', 'ANT', 530, 'n'),
('NC', 'NEW CALEDONIA', 'New Caledonia', 'NCL', 540, 'n'),
('NZ', 'NEW ZEALAND', 'New Zealand', 'NZL', 554, 'n'),
('NI', 'NICARAGUA', 'Nicaragua', 'NIC', 558, 'n'),
('NE', 'NIGER', 'Niger', 'NER', 562, 'n'),
('NG', 'NIGERIA', 'Nigeria', 'NGA', 566, 'n'),
('NU', 'NIUE', 'Niue', 'NIU', 570, 'n'),
('NF', 'NORFOLK ISLAND', 'Norfolk Island', 'NFK', 574, 'n'),
('MP', 'NORTHERN MARIANA ISLANDS', 'Northern Mariana Islands', 'MNP', 580, 'n'),
('NO', 'NORWAY', 'Norway', 'NOR', 578, 'n'),
('OM', 'OMAN', 'Oman', 'OMN', 512, 'n'),
('PK', 'PAKISTAN', 'Pakistan', 'PAK', 586, 'n'),
('PW', 'PALAU', 'Palau', 'PLW', 585, 'n'),
('PS', 'PALESTINIAN TERRITORY, OCCUPIED', 'Palestinian Territory, Occupied', NULL, NULL, 'n'),
('PA', 'PANAMA', 'Panama', 'PAN', 591, 'n'),
('PG', 'PAPUA NEW GUINEA', 'Papua New Guinea', 'PNG', 598, 'n'),
('PY', 'PARAGUAY', 'Paraguay', 'PRY', 600, 'n'),
('PE', 'PERU', 'Peru', 'PER', 604, 'n'),
('PH', 'PHILIPPINES', 'Philippines', 'PHL', 608, 'n'),
('PN', 'PITCAIRN', 'Pitcairn', 'PCN', 612, 'n'),
('PL', 'POLAND', 'Poland', 'POL', 616, 'y'),
('PT', 'PORTUGAL', 'Portugal', 'PRT', 620, 'y'),
('PR', 'PUERTO RICO', 'Puerto Rico', 'PRI', 630, 'n'),
('QA', 'QATAR', 'Qatar', 'QAT', 634, 'n'),
('RE', 'REUNION', 'Reunion', 'REU', 638, 'n'),
('RO', 'ROMANIA', 'Romania', 'ROM', 642, 'y'),
('RU', 'RUSSIAN FEDERATION', 'Russian Federation', 'RUS', 643, 'n'),
('RW', 'RWANDA', 'Rwanda', 'RWA', 646, 'n'),
('SH', 'SAINT HELENA', 'Saint Helena', 'SHN', 654, 'n'),
('KN', 'SAINT KITTS AND NEVIS', 'Saint Kitts and Nevis', 'KNA', 659, 'n'),
('LC', 'SAINT LUCIA', 'Saint Lucia', 'LCA', 662, 'n'),
('PM', 'SAINT PIERRE AND MIQUELON', 'Saint Pierre and Miquelon', 'SPM', 666, 'n'),
('VC', 'SAINT VINCENT AND THE GRENADINES', 'Saint Vincent and the Grenadines', 'VCT', 670, 'n'),
('WS', 'SAMOA', 'Samoa', 'WSM', 882, 'n'),
('SM', 'SAN MARINO', 'San Marino', 'SMR', 674, 'n'),
('ST', 'SAO TOME AND PRINCIPE', 'Sao Tome and Principe', 'STP', 678, 'n'),
('SA', 'SAUDI ARABIA', 'Saudi Arabia', 'SAU', 682, 'n'),
('SN', 'SENEGAL', 'Senegal', 'SEN', 686, 'n'),
('RS', 'SERBIA', 'Serbia', 'SRB', 381, 'n'),
('SC', 'SEYCHELLES', 'Seychelles', 'SYC', 690, 'n'),
('SL', 'SIERRA LEONE', 'Sierra Leone', 'SLE', 694, 'n'),
('SG', 'SINGAPORE', 'Singapore', 'SGP', 702, 'n'),
('SK', 'SLOVAKIA', 'Slovakia', 'SVK', 703, 'y'),
('SI', 'SLOVENIA', 'Slovenia', 'SVN', 705, 'y'),
('SB', 'SOLOMON ISLANDS', 'Solomon Islands', 'SLB', 90, 'n'),
('SO', 'SOMALIA', 'Somalia', 'SOM', 706, 'n'),
('ZA', 'SOUTH AFRICA', 'South Africa', 'ZAF', 710, 'n'),
('GS', 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS', 'South Georgia and the South Sandwich Islands', NULL, NULL, 'n'),
('ES', 'SPAIN', 'Spain', 'ESP', 724, 'y'),
('LK', 'SRI LANKA', 'Sri Lanka', 'LKA', 144, 'n'),
('SD', 'SUDAN', 'Sudan', 'SDN', 736, 'n'),
('SR', 'SURINAME', 'Suriname', 'SUR', 740, 'n'),
('SJ', 'SVALBARD AND JAN MAYEN', 'Svalbard and Jan Mayen', 'SJM', 744, 'n'),
('SZ', 'SWAZILAND', 'Swaziland', 'SWZ', 748, 'n'),
('SE', 'SWEDEN', 'Sweden', 'SWE', 752, 'y'),
('CH', 'SWITZERLAND', 'Switzerland', 'CHE', 756, 'n'),
('SY', 'SYRIAN ARAB REPUBLIC', 'Syrian Arab Republic', 'SYR', 760, 'n'),
('TW', 'TAIWAN, PROVINCE OF CHINA', 'Taiwan, Province of China', 'TWN', 158, 'n'),
('TJ', 'TAJIKISTAN', 'Tajikistan', 'TJK', 762, 'n'),
('TZ', 'TANZANIA, UNITED REPUBLIC OF', 'Tanzania, United Republic of', 'TZA', 834, 'n'),
('TH', 'THAILAND', 'Thailand', 'THA', 764, 'n'),
('TL', 'TIMOR-LESTE', 'Timor-Leste', NULL, NULL, 'n'),
('TG', 'TOGO', 'Togo', 'TGO', 768, 'n'),
('TK', 'TOKELAU', 'Tokelau', 'TKL', 772, 'n'),
('TO', 'TONGA', 'Tonga', 'TON', 776, 'n'),
('TT', 'TRINIDAD AND TOBAGO', 'Trinidad and Tobago', 'TTO', 780, 'n'),
('TN', 'TUNISIA', 'Tunisia', 'TUN', 788, 'n'),
('TR', 'TURKEY', 'Turkey', 'TUR', 792, 'n'),
('TM', 'TURKMENISTAN', 'Turkmenistan', 'TKM', 795, 'n'),
('TC', 'TURKS AND CAICOS ISLANDS', 'Turks and Caicos Islands', 'TCA', 796, 'n'),
('TV', 'TUVALU', 'Tuvalu', 'TUV', 798, 'n'),
('UG', 'UGANDA', 'Uganda', 'UGA', 800, 'n'),
('UA', 'UKRAINE', 'Ukraine', 'UKR', 804, 'n'),
('AE', 'UNITED ARAB EMIRATES', 'United Arab Emirates', 'ARE', 784, 'n'),
('GB', 'UNITED KINGDOM', 'United Kingdom', 'GBR', 826, 'y'),
('US', 'UNITED STATES', 'United States', 'USA', 840, 'n'),
('UM', 'UNITED STATES MINOR OUTLYING ISLANDS', 'United States Minor Outlying Islands', NULL, NULL, 'n'),
('UY', 'URUGUAY', 'Uruguay', 'URY', 858, 'n'),
('UZ', 'UZBEKISTAN', 'Uzbekistan', 'UZB', 860, 'n'),
('VU', 'VANUATU', 'Vanuatu', 'VUT', 548, 'n'),
('VE', 'VENEZUELA', 'Venezuela', 'VEN', 862, 'n'),
('VN', 'VIET NAM', 'Viet Nam', 'VNM', 704, 'n'),
('VG', 'VIRGIN ISLANDS, BRITISH', 'Virgin Islands, British', 'VGB', 92, 'n'),
('VI', 'VIRGIN ISLANDS, U.S.', 'Virgin Islands, U.s.', 'VIR', 850, 'n'),
('WF', 'WALLIS AND FUTUNA', 'Wallis and Futuna', 'WLF', 876, 'n'),
('EH', 'WESTERN SAHARA', 'Western Sahara', 'ESH', 732, 'n'),
('YE', 'YEMEN', 'Yemen', 'YEM', 887, 'n'),
('ZM', 'ZAMBIA', 'Zambia', 'ZMB', 894, 'n'),
('ZW', 'ZIMBABWE', 'Zimbabwe', 'ZWE', 716, 'n'),
('ME', 'MONTENEGRO', 'Montenegro', 'MNE', 382, 'n');

-- --------------------------------------------------------

-- 
-- Dumping data for table `dns_template`
-- 

INSERT INTO `dns_template` (`template_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `name`, `fields`, `template`, `visible`) VALUES (1, 1, 1, 'riud', 'riud', '', 'Default', 'DOMAIN,IP,NS1,NS2,EMAIL,DKIM,DNSSEC', '[ZONE]\norigin={DOMAIN}.\nns={NS1}.\nmbox={EMAIL}.\nrefresh=7200\nretry=540\nexpire=604800\nminimum=3600\nttl=3600\n\n[DNS_RECORDS]\nA|{DOMAIN}.|{IP}|0|3600\nA|www|{IP}|0|3600\nA|mail|{IP}|0|3600\nNS|{DOMAIN}.|{NS1}.|0|3600\nNS|{DOMAIN}.|{NS2}.|0|3600\nMX|{DOMAIN}.|mail.{DOMAIN}.|10|3600\nTXT|{DOMAIN}.|v=spf1 mx a ~all|0|3600', 'y');


-- --------------------------------------------------------

-- 
-- Dumping data for table `help_faq`
-- 

INSERT INTO `help_faq` VALUES (1,1,0,'I would like to know ...','Yes, of course.',1,1,'riud','riud','r');

-- --------------------------------------------------------

-- 
-- Dumping data for table `help_faq_sections`
-- 

INSERT INTO `help_faq_sections` VALUES (1,'General',0,NULL,NULL,NULL,NULL,NULL);

-- --------------------------------------------------------

-- 
-- Dumping data for table `software_repo`
-- 

INSERT INTO `software_repo` (`software_repo_id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `repo_name`, `repo_url`, `repo_username`, `repo_password`, `active`) VALUES (1, 1, 1, 'riud', 'riud', '', 'ISPConfig Addons', 'http://repo.ispconfig.org/addons/', '', '', 'n');

-- --------------------------------------------------------

-- 
-- Dumping data for table `spamfilter_policy`
-- 

INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`, `rspamd_greylisting`, `rspamd_spam_greylisting_level`, `rspamd_spam_tag_level`, `rspamd_spam_tag_method`, `rspamd_spam_kill_level`) VALUES(1, 1, 0, 'riud', 'riud', 'r', 'Non-paying', 'N', 'N', 'N', 'N', 'Y', 'Y', 'Y', 'N', 'Y', '', '', '', '', '', '', 3, 7, 10, 0, 0, '', '', '', '', 'N', 'N', 'N', '', '', '', '', '', '', '', 0, '', 'n', 6.00, 8.00, 'rewrite_subject', 12.00);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`, `rspamd_greylisting`, `rspamd_spam_greylisting_level`, `rspamd_spam_tag_level`, `rspamd_spam_tag_method`, `rspamd_spam_kill_level`) VALUES(2, 1, 0, 'riud', 'riud', 'r', 'Uncensored', 'Y', 'Y', 'Y', 'Y', 'N', 'N', 'N', 'N', 'N', NULL, NULL, NULL, NULL, NULL, NULL, 3, 999, 999, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'n', 999.00, 999.00, 'rewrite_subject', 999.00);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`, `rspamd_greylisting`, `rspamd_spam_greylisting_level`, `rspamd_spam_tag_level`, `rspamd_spam_tag_method`, `rspamd_spam_kill_level`) VALUES(3, 1, 0, 'riud', 'riud', 'r', 'Wants all spam', 'N', 'Y', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 999, 999, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'n', 999.00, 999.00, 'rewrite_subject', 999.00);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`, `rspamd_greylisting`, `rspamd_spam_greylisting_level`, `rspamd_spam_tag_level`, `rspamd_spam_tag_method`, `rspamd_spam_kill_level`) VALUES(4, 1, 0, 'riud', 'riud', 'r', 'Wants viruses', 'Y', 'N', 'Y', 'Y', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 6.9, 6.9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'y', 4.00, 6.00, 'rewrite_subject', 10.00);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`, `rspamd_greylisting`, `rspamd_spam_greylisting_level`, `rspamd_spam_tag_level`, `rspamd_spam_tag_method`, `rspamd_spam_kill_level`) VALUES(5, 1, 0, 'riud', 'riud', 'r', 'Normal', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', '', '', '', '', '', '', 1, 4.5, 50, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '***SPAM***', NULL, NULL, 'y', 4.00, 6.00, 'rewrite_subject', 10.00);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`, `rspamd_greylisting`, `rspamd_spam_greylisting_level`, `rspamd_spam_tag_level`, `rspamd_spam_tag_method`, `rspamd_spam_kill_level`) VALUES(6, 1, 0, 'riud', 'riud', 'r', 'Trigger happy', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 5, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'y', 2.00, 4.00, 'rewrite_subject', 8.00);
INSERT INTO `spamfilter_policy` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `policy_name`, `virus_lover`, `spam_lover`, `banned_files_lover`, `bad_header_lover`, `bypass_virus_checks`, `bypass_spam_checks`, `bypass_banned_checks`, `bypass_header_checks`, `spam_modifies_subj`, `virus_quarantine_to`, `spam_quarantine_to`, `banned_quarantine_to`, `bad_header_quarantine_to`, `clean_quarantine_to`, `other_quarantine_to`, `spam_tag_level`, `spam_tag2_level`, `spam_kill_level`, `spam_dsn_cutoff_level`, `spam_quarantine_cutoff_level`, `addr_extension_virus`, `addr_extension_spam`, `addr_extension_banned`, `addr_extension_bad_header`, `warnvirusrecip`, `warnbannedrecip`, `warnbadhrecip`, `newvirus_admin`, `virus_admin`, `banned_admin`, `bad_header_admin`, `spam_admin`, `spam_subject_tag`, `spam_subject_tag2`, `message_size_limit`, `banned_rulenames`, `rspamd_greylisting`, `rspamd_spam_greylisting_level`, `rspamd_spam_tag_level`, `rspamd_spam_tag_method`, `rspamd_spam_kill_level`) VALUES(7, 1, 0, 'riud', 'riud', 'r', 'Permissive', 'N', 'N', 'N', 'Y', 'N', 'N', 'N', 'N', 'Y', NULL, NULL, NULL, NULL, NULL, NULL, 3, 10, 20, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'n', 7.00, 10.00, 'rewrite_subject', 20.00);

-- --------------------------------------------------------

-- 
-- Dumping data for table `sys_group`
-- 

INSERT INTO `sys_group` (`groupid`, `name`, `description`, `client_id`) VALUES (1, 'admin', 'Administrators group', 0);

-- --------------------------------------------------------

-- 
-- Dumping data for table `sys_ini`
-- 

INSERT INTO `sys_ini` (`sysini_id`, `config`, `default_logo`, `custom_logo`) VALUES (1, '', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAABBCAYAAACU5+uOAAAItUlEQVR42u1dCWwVVRStUJZCK6HsFNAgWpaCJkKICZKApKUFhURQpEnZF4EEUJZYEEpBIamgkQpUQBZRW7YCBqQsggsQEAgKLbIGCYsSCNqyQ8D76h18Hd/MvJk/n/bXc5KT+TNz79vPzNv+/2FhAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOAe++s0akTsRZxMnE6cGkKcxkwhPofaBPwWRzxxB/EO8UGI8xhxEGoV8EscY8qBKFRcgdoFAhXHC+VUHAbHo5aBQASyrZwL5DoxEjUNeBXI9XIuEMEE1DTgVSA3FA3qIDEtBLnTQiBDUNOAV4EUKhpURojmZQQEAjwKgSwK0bykWQgEU74ABAKBABAIBOIJffoNrkRsS0whDiMO5uNw4gBiSxvfGOJrbDtMOgr2JNa18HmZmETsopnGp4h9xdF0TcQRb8NEPkawTzv2qaWIoybnZYRUBoJD+difGAuBlCy0qsRM4mfERcTFfGygsBUF/xFxE/EQ8RixwIbi/j7il8R3iE8qwuxAXMJxuuFiTvNMYleb/E0gXiI+cOBaISTJrzLxcw2/+8Q5pjjfNNkM0RDILLadpbimw+bsc4DPkxRpuqkZ1orisoBAiguuhkUhPSvZRBA3u6gsK94g9jDFP9aHcAV3EKNNYX8i3RcNJ4M4nTiROJCYykIzbGZKvouk68vYbyS/cUbz+RrJZpzkO5Sv3eajaJhRDvUwg21nKK4VcF5WKPgFH6PZZw/7dJXC6S6lczunfbIQLpeDkZ+lJcoCAikuvChioaLBtfD4JHPiXSFKKexBPoa9Wwr3ael6skMZDGO7K3z+uOSb5OA7mu2KiOGmPH3ADVh8/sohnDS2S1NcG+uiO/kd+8RL146YRWzj359tb0Eg+gIpsHkjFNrQqiF3DZJABDtyuCP5/FuNRlHN8Ofz9nx+XLNR3jR1c4w8TSFGSmnr4FEgU7wKhI51jAeTpv+/ZQGBOAuEu1d/Ku6LV35t9rdigkUjHuMgkHPEecQsxdjjUx4zHbMI+10OdzqfZ2o0iiqSfzgPfMXnzZqN6iTbJ5jytMTU0E97FEhaAAJ5kc/PuJjQOCoIgegJpKbUl5b5vGaBT+A+vOgn5/JYIdFBIOs1wo1kIZl93+P70/h8oUZYFXkmKInPU9h3m2YeT8lvRilPyyWbi3xt4iMWSDc+P4lp3uAIRDxdryjui6dmuujXcr91IDcMmaJv31WISfTrLeJXCUT3yb1a4Ztmalyu61MaZG/XtD9tapRGnpZKNp2lNNZ3KZARAQgk3untBYEEPgbJ92FsIAax34v1AQ2B5Go2BlW60n0QyCC/BWISdJ5LgewWU8k86DdTzMyNh0BKVyAzfB5I93YQyBGeTlW9lQbwIle2Rdgzy7BAxJT6Hb6X6EIgTrznRSCiHli02cwcPor1pbkQiL5AKvOA+ZZPAtkfxFms3j4IZHAwBGJaRPxdjH00BSImJRqKOlEwjtjUo0Dm2pWla4HMzsyqQIxSMKI8C8RkL9YXuhDf5gqcw4NweaZJiGkh8UeLwi+Utkb4KZCrYszkVSDiQRDMN4hkf5DvZ2gKZJyLPJgFkmAjEDEF3EYSWzPeklO8Q8CLQGKJhQquK+eDdLFNZBJxFLEf8XUXFTbcYv2kRhAEIq+vGNO88zTTKVaRzxPrSSvPW11O8yZqCiROSnMsX0sP0ixWops1Hfbx/AaJIz5QcFc5n+ZVNcbxmoWtEsBNB4EU8Tgk32Gv1wneEybeWG1N8RoNbplmOo2neiyxE3/eoun7G9t31hGIqXuzl8/HB0kgxhvhD03/KoEIpIWFQPLK+UJhkWpgKLZP8IKhajNhJg8A7yt8/5K6QoFM8z5mc68Ph3VWM6wTbN+a+AR/vqThV13KYyMXAgmXps9FnK8GSSA17KaXFf7R3gUyd8H/TiBss9fngfQehzfMpkDLgxcS73J4k1y85WrxtTtOjZPuVZA2O55RhLfUId5XpI2UHwZDIHxtp7HtRrVL25SfhWy7z7VAMuYvipszd0FJcfxzHspdrMctGnGcZNPTZ4F0VszqyPSlPHm8JG9f2SDtgF3Nq/rnJZssyXeUdP0CN64c9l/FDfGyZNNNkaeVGmnMM+Vdtd19los8/2e7Ow/E70lxiG7pRmkn8AaeULlcoo4sBDLfKvL0nLUxablfX0hfmfuQ01avI65fUQYEkupRIJHcAMwbDWNNdmLgupV4zeMO3stcIZ1M4aYo4vZt0oO7Locd0ndGTEQofN+QxiZ22+y7W+RpgUb66vOU7232SZXupZqvaYT3Dfu8ZLrejtc47mvkJ9FoVEWKBmW7dyc7ZXD1Nb2TH3JVn5Tqa3r1repzY6/gwWeqhUCGO/XjWSTmjYYVLOzFoP0Z/qJTks033brxrtjmxCbGtK4ivEqKuH2fNuc0tDatIYgna4yGbz2eeTL8WhJbic2aDnmqqpm2KlLeK5vWn0pc0wirGvtUtBkzNdPKDzWe24oGdZX4CzGfWCD4U93GBQdqNSw4Uiny8K9h4buOhlU2scq+Q1G1i233k63hFwBPEfcS04l1FGJoynbH+fgz8ZKFQJLDAMDjk/psCPzw20XxE6mmdLd24d8KNQ14FciUEPl1xHvEhlK6W2j65aOWgUAEUpV4NEREstyDQNqjloFARVKL/xukrAvkGjGC09zGwfYKsQdqF/BTKMnEJcTtxC3EPAU3iic5cRkfjc/ZFvZuuZm4gXjOouG35LQ2Yfutkq/4pfpN/E9TDVCjQGkJqQExho+CjYlRPseRiQE3EIriaMZTw4K3mOJv23J8jme23RsEAMqqQJrb9PnnEbPEVpUAuJD4Mf/PoCqeONQCUJYFElGKf7ojpnqjUQtAWRdJaf1t2w8ofSAUBNKulATSEaUPhIpIRj9icbyFUgdCTSRTeR0i2HwfpQ0AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAQBnG392D9QU+JXhxAAAAAElFTkSuQmCC', '');

-- --------------------------------------------------------

-- 
-- Dumping data for table `sys_user`
-- 

INSERT INTO `sys_theme` (`var_id`, `tpl_name`, `username`, `logo_url`) VALUES (NULL, 'default', 'global', 'themes/default/images/header_logo.png');
INSERT INTO `sys_theme` (`var_id`, `tpl_name`, `username`, `logo_url`) VALUES (NULL, 'default-v2', 'global', 'themes/default-v2/images/header_logo.png');

-- --------------------------------------------------------

-- 
-- Dumping data for table `sys_user`
-- 

INSERT INTO `sys_user` (`userid`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `username`, `passwort`, `modules`, `startmodule`, `app_theme`, `typ`, `active`, `language`, `groups`, `default_group`, `client_id`) VALUES (1, 1, 0, 'riud', 'riud', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', 'dashboard,admin,client,mail,monitor,sites,dns,vm,tools,help', 'dashboard', 'default', 'admin', 1, 'en', '1,2', 1, 0);

-- --------------------------------------------------------

--
-- Dumping data for table `sys_config`
--

INSERT INTO sys_config VALUES ('db','db_version','3.1dev');
INSERT INTO sys_config VALUES ('interface','session_timeout','0');

SET FOREIGN_KEY_CHECKS = 1;
