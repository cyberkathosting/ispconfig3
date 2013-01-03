
ALTER TABLE `web_database` ADD `database_name_prefix` VARCHAR( 50 ) NOT NULL AFTER `database_name`;
UPDATE `web_database` SET `database_name_prefix` = '#' WHERE 1;
ALTER TABLE `web_database_user` ADD `database_user_prefix` VARCHAR( 50 ) NOT NULL AFTER `database_user`;
UPDATE `web_database_user` SET `database_user_prefix` = '#' WHERE 1;
ALTER TABLE `ftp_user` ADD `username_prefix` VARCHAR( 50 ) NOT NULL AFTER `username`;
UPDATE `ftp_user` SET `username_prefix` = '#' WHERE 1;
ALTER TABLE `shell_user` ADD `username_prefix` VARCHAR( 50 ) NOT NULL AFTER `username`;
UPDATE `shell_user` SET `username_prefix` = '#' WHERE 1;
ALTER TABLE `webdav_user` ADD `username_prefix` VARCHAR( 50 ) NOT NULL AFTER `username`;
UPDATE `webdav_user` SET `username_prefix` = '#' WHERE 1;