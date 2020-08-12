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
  KEY `origin` (`origin`),
  KEY `active` (`active`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `dns_slave` DROP INDEX `origin`;
ALTER TABLE `dns_slave` ADD CONSTRAINT `slave` UNIQUE (`origin`,`server_id`);