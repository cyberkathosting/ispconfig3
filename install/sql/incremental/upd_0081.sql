ALTER TABLE `server` ADD COLUMN `xmpp_server` tinyint(1) NOT NULL default '0' AFTER `firewall_server`;

ALTER TABLE `client`
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