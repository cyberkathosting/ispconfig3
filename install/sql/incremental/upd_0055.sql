ALTER TABLE `web_backup` CHANGE `backup_type` `backup_type` enum('web','mongodb','mysql') NOT NULL DEFAULT 'web';
ALTER TABLE `web_database_user` ADD `database_password_mongo` varchar(32) DEFAULT NULL AFTER `database_password`;
ALTER TABLE `sys_datalog` ADD `error` MEDIUMTEXT NULL DEFAULT NULL;
