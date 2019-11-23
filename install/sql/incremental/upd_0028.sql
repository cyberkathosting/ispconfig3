ALTER TABLE  `web_database` ADD  `parent_domain_id` int(11) unsigned NOT NULL DEFAULT  '0' AFTER  `server_id`;
ALTER TABLE  `web_database` ADD  `backup_interval` VARCHAR( 255 ) NOT NULL DEFAULT 'none', ADD `backup_copies` INT NOT NULL DEFAULT '1' AFTER  `remote_ips`;
CREATE TABLE `web_backup` (
  `backup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned NOT NULL,
  `parent_domain_id` int(10) unsigned NOT NULL,
  `backup_type` enum('web','mysql') NOT NULL DEFAULT 'web',
  `backup_mode` varchar(64) NOT NULL DEFAULT  '',
  `tstamp` int(10) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`backup_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
