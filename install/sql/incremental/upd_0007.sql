ALTER TABLE client ADD COLUMN limit_mailmailinglist int(11) NOT NULL default '-1';
ALTER TABLE client_template ADD COLUMN limit_mailmailinglist int(11) NOT NULL default '-1';

CREATE TABLE IF NOT EXISTS `mail_mailinglist` (
  `mailinglist_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) NOT NULL,
  `sys_perm_group` varchar(5) character set ucs2 NOT NULL,
  `sys_perm_other` varchar(5) NOT NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `domain` varchar(255) NOT NULL,
  `listname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY  (`mailinglist_id`)
) AUTO_INCREMENT=1;

DROP TABLE `mail_mailman_domain`;