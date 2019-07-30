-- --------------------------------------------------------

--
-- Table structure for table `sys_theme`
--

CREATE TABLE IF NOT EXISTS `sys_theme` (
  `var_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tpl_name` varchar(32) NOT NULL,
  `username` varchar(64) NOT NULL,
  `logo_url` varchar(255) NOT NULL,
  PRIMARY KEY (`var_id`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

INSERT INTO `sys_theme` (`var_id`, `tpl_name`, `username`, `logo_url`) VALUES (NULL, 'default', 'global', 'themes/default/images/header_logo.png');
INSERT INTO `sys_theme` (`var_id`, `tpl_name`, `username`, `logo_url`) VALUES (NULL, 'default-v2', 'global', 'themes/default-v2/images/header_logo.png');

-- --------------------------------------------------------

ALTER TABLE  `web_domain` ADD  `ssl_key` MEDIUMTEXT NOT NULL AFTER  `ssl_bundle`;
ALTER TABLE  `mail_user` ADD  `disabledoveadm` enum('n','y') NOT NULL default 'n' AFTER  `disablelda`;


