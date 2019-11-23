CREATE TABLE IF NOT EXISTS `sys_cron` (
  `name` varchar(50) NOT NULL,
  `last_run` datetime NULL DEFAULT NULL,
  `next_run` datetime NULL DEFAULT NULL,
  `running` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`)
) DEFAULT CHARSET=utf8;
