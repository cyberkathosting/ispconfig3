ALTER TABLE `client` CHANGE COLUMN `password` `password` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `ftp_user` CHANGE COLUMN `password` `password` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `shell_user` CHANGE COLUMN `password` `password` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `sys_user` CHANGE COLUMN `passwort` `passwort` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `webdav_user` CHANGE COLUMN `password` `password` VARCHAR(200) DEFAULT NULL;