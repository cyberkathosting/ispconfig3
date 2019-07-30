CREATE TABLE `mail_backup` (
  `backup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned NOT NULL,
  `parent_domain_id` int(10) unsigned NOT NULL,
  `mailuser_id` int(10) unsigned NOT NULL,
  `backup_mode` varchar(64) NOT NULL DEFAULT  '',
  `tstamp` int(10) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filesize` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`backup_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `mail_user` ADD `backup_interval` VARCHAR( 255 ) NOT NULL DEFAULT 'none';
ALTER TABLE `mail_user` ADD `backup_copies` INT NOT NULL DEFAULT '1';
