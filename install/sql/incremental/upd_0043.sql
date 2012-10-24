ALTER TABLE `sys_theme` ADD `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
                     ADD `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
                     ADD `sys_perm_user` varchar(5) DEFAULT NULL,
                     ADD `sys_perm_group` varchar(5) DEFAULT NULL,
                     ADD `sys_perm_other` varchar(5) DEFAULT NULL;


