ALTER TABLE `web_backup` ADD `filesize` VARCHAR(10) NOT NULL AFTER `filename`;
ALTER TABLE `client_template` CHANGE `limit_aps` `limit_aps` INT( 11 ) NOT NULL DEFAULT '-1';
ALTER TABLE `mail_domain` ADD `dkim_public` MEDIUMTEXT NULL AFTER `domain`;
ALTER TABLE `mail_domain` ADD `dkim_private` MEDIUMTEXT NULL AFTER `domain`;
ALTER TABLE `mail_domain` ADD `dkim` ENUM( 'n', 'y' ) NOT NULL AFTER `domain`;
ALTER TABLE `web_backup` CHANGE `backup_type` `backup_type` enum('web','mysql','mongodb') NOT NULL DEFAULT 'web';
ALTER TABLE `web_database_user` ADD `database_password_mongo` varchar(32) DEFAULT NULL AFTER `database_password`;
