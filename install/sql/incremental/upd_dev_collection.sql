ALTER TABLE `sys_datalog` ADD `session_id` varchar(64) NOT NULL DEFAULT '' AFTER `error`;
ALTER TABLE `sys_user` CHANGE `sys_userid` `sys_userid` INT(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Created by userid';
ALTER TABLE `sys_user` CHANGE `sys_groupid` `sys_groupid` INT(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Created by groupid';
ALTER TABLE `web_domain` ADD COLUMN `php_fpm_chroot` enum('n','y') NOT NULL DEFAULT 'n' AFTER `php_fpm_use_socket`;
