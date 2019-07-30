CREATE TABLE IF NOT EXISTS `client_circle` (
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