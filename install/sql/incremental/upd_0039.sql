-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `web_database_user`
--

CREATE TABLE IF NOT EXISTS `web_database_user` (
  `database_user_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `database_user` varchar(64) DEFAULT NULL,
  `database_password` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`database_user_id`)
)  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

ALTER TABLE `web_database` ADD `database_user_id` INT( 11 ) UNSIGNED NULL DEFAULT NULL AFTER `database_password` ,
ADD `database_ro_user_id` INT( 11 ) UNSIGNED NULL DEFAULT NULL AFTER `database_user_id`,
ADD INDEX ( `database_user_id` ),
ADD INDEX ( `database_ro_user_id` ) ;

-- --------------------------------------------------------

INSERT INTO `web_database_user` SELECT NULL, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, '0', `database_user`, `database_password` FROM `web_database` WHERE 1;

-- --------------------------------------------------------

UPDATE `web_database`, `web_database_user` SET `web_database`.`database_user_id` = `web_database_user`.`database_user_id` WHERE `web_database_user`.`database_user` = `web_database`.`database_user`;

-- --------------------------------------------------------

ALTER TABLE `web_database`
DROP `database_user`,
DROP `database_password`;

