CREATE TABLE IF NOT EXISTS `domains` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `master` varchar(128) default NULL,
  `last_check` int(11) default NULL,
  `type` varchar(6) NOT NULL,
  `notified_serial` int(11) default NULL,
  `account` varchar(40) default NULL,
  `ispconfig_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name_index` (`name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `records` (
  `id` int(11) NOT NULL auto_increment,
  `domain_id` int(11) default NULL,
  `name` varchar(255) default NULL,
  `type` varchar(6) default NULL,
  `content` TEXT default NULL,
  `ttl` int(11) default NULL,
  `prio` int(11) default NULL,
  `change_date` int(11) default NULL,
  `disabled` tinyint(1) default 0,
  `auth` tinyint(1) default 1,
  `ispconfig_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `rec_name_index` (`name`),
  KEY `nametype_index` (`name`,`type`),
  KEY `domain_id` (`domain_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `supermasters` (
  `ip` varchar(25) NOT NULL,
  `nameserver` varchar(255) NOT NULL,
  `account` varchar(40) default NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `domainmetadata` (
  `id` int auto_increment,
  `domain_id` int NOT NULL,
  `kind` varchar(32),
  `content` TEXT,
  PRIMARY KEY (`id`)
) Engine=InnoDB;


-- add new columns if not existing
SET @dbname = DATABASE();

SELECT count(*) INTO @exist FROM `information_schema`.`columns` WHERE `table_schema` = @dbname AND `column_name` = 'auth' AND `table_name` = 'records' LIMIT 1;
SET @query = IF(@exist <= 0, 'ALTER TABLE `records` ADD COLUMN `auth` tinyint(1) default 1 AFTER `change_date`', 'SELECT \'Column Exists\' STATUS');
PREPARE stmt FROM @query;
EXECUTE stmt;

SELECT count(*) INTO @exist FROM `information_schema`.`columns` WHERE `table_schema` = @dbname AND `column_name` = 'disabled' AND `table_name` = 'records' LIMIT 1;
SET @query = IF(@exist <= 0, 'ALTER TABLE `records` ADD COLUMN `disabled` tinyint(1) default 0 AFTER `change_date`', 'SELECT \'Column Exists\' STATUS');
PREPARE stmt FROM @query;
EXECUTE stmt;

