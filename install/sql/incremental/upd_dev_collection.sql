ALTER TABLE `mail_user`
	CHANGE `uid` `uid` int(11) NOT NULL DEFAULT '5000',
	CHANGE `gid` `gid` int(11) NOT NULL DEFAULT '5000';

ALTER TABLE `mail_user`
	ADD COLUMN `sender_cc` varchar(255) NOT NULL DEFAULT '' AFTER `cc`;

ALTER TABLE `client_template` ADD `default_mailserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `client_template` ADD `default_webserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `client_template` ADD `default_dnsserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `client_template` ADD `default_slave_dnsserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `client_template` ADD `default_dbserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE  `client` ADD  `contact_firstname` VARCHAR( 64 ) NOT NULL DEFAULT '' AFTER  `gender`;

UPDATE `dns_template` SET `fields` = 'DOMAIN,IP,NS1,NS2,EMAIL,DKIM' WHERE `dns_template`.`template_id` =1;
UPDATE `dns_template` SET `template` = '[ZONE]
origin={DOMAIN}.
ns={NS1}.
mbox={EMAIL}.
refresh=7200
retry=540
expire=604800
minimum=86400
ttl=3600

[DNS_RECORDS]
A|{DOMAIN}.|{IP}|0|3600
A|www|{IP}|0|3600
A|mail|{IP}|0|3600
NS|{DOMAIN}.|{NS1}.|0|3600
NS|{DOMAIN}.|{NS2}.|0|3600
MX|{DOMAIN}.|mail.{DOMAIN}.|10|3600
TXT|{DOMAIN}.|v=spf1 mx a ~all|0|3600' WHERE `dns_template`.`template_id` = 1;

ALTER TABLE `mail_backup` CHANGE `filesize` `filesize` VARCHAR(20) NOT NULL DEFAULT '';
ALTER TABLE `web_backup` CHANGE `filesize` `filesize` VARCHAR(20) NOT NULL DEFAULT '';

ALTER TABLE `sys_datalog` ADD INDEX `dbtable` (`dbtable` (25), `dbidx` (25)), ADD INDEX (`action`);
ALTER TABLE `mail_user` ADD `greylisting` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `postfix`;
ALTER TABLE `mail_user` ADD `maildir_format` varchar(255) NOT NULL default 'maildir' AFTER `maildir`;
ALTER TABLE `mail_forwarding` ADD `greylisting` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `active`;

ALTER TABLE `openvz_ip` CHANGE `ip_address` `ip_address` VARCHAR(39) DEFAULT NULL;

-- XMPP Support

ALTER TABLE `server` ADD COLUMN `xmpp_server` tinyint(1) NOT NULL default '0' AFTER `firewall_server`;

ALTER TABLE `client`
  ADD COLUMN `default_xmppserver` int(11) unsigned NOT NULL DEFAULT '1',
  ADD COLUMN `xmpp_servers` blob,
  ADD COLUMN `limit_xmpp_domain` int(11) NOT NULL DEFAULT '-1',
  ADD COLUMN `limit_xmpp_user` int(11) NOT NULL DEFAULT '-1',
  ADD COLUMN `limit_xmpp_muc` ENUM( 'n', 'y' ) NOT NULL default 'n',
  ADD COLUMN `limit_xmpp_anon` ENUM( 'n', 'y' ) NOT NULL default 'n',
  ADD COLUMN `limit_xmpp_auth_options` varchar(255) NOT NULL DEFAULT 'plain,hashed,isp',
  ADD COLUMN `limit_xmpp_vjud` ENUM( 'n', 'y' ) NOT NULL default 'n',
  ADD COLUMN `limit_xmpp_proxy` ENUM( 'n', 'y' ) NOT NULL default 'n',
  ADD COLUMN `limit_xmpp_status` ENUM( 'n', 'y' ) NOT NULL default 'n',
  ADD COLUMN `limit_xmpp_pastebin` ENUM( 'n', 'y' ) NOT NULL default 'n',
  ADD COLUMN `limit_xmpp_httparchive` ENUM( 'n', 'y' ) NOT NULL default 'n';


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
  `muc_name` varchar(30) NOT NULL DEFAULT ''
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------
