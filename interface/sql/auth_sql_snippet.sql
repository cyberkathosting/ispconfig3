ALTER TABLE `mail_domain` ADD `sys_userid` INT NOT NULL AFTER `domain_id` ,
ADD `sys_groupid` INT NOT NULL AFTER `sys_userid` ,
ADD `sys_perm_user` VARCHAR( 5 ) NOT NULL AFTER `sys_groupid` ,
ADD `sys_perm_group` VARCHAR( 5 ) NOT NULL AFTER `sys_perm_user` ,
ADD `sys_perm_other` VARCHAR( 5 ) NOT NULL AFTER `sys_perm_group` ;