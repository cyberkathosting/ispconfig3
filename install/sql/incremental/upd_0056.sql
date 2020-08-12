CREATE TABLE `client_template_assigned` (
  `assigned_template_id` bigint(20) NOT NULL auto_increment,
  `client_id` bigint(11) NOT NULL DEFAULT '0',
  `client_template_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`assigned_template_id`),
  KEY `client_id` (`client_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `client` ADD `gender` enum('','m','f') NOT NULL DEFAULT '' AFTER `company_id`,
  ADD `locked` enum('n','y') NOT NULL DEFAULT 'n' AFTER `created_at`,
  ADD `canceled` enum('n','y') NOT NULL DEFAULT 'n' AFTER `locked`,
  ADD `tmp_data` mediumblob AFTER `canceled` ;
