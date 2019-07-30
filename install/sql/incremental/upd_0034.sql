-- --------------------------------------------------------

--
-- Table structure for table `aps_instances`
--

CREATE TABLE IF NOT EXISTS `aps_instances` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `customer_id` int(4) NOT NULL,
  `package_id` int(4) NOT NULL,
  `instance_status` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `aps_instances_settings`
--

CREATE TABLE IF NOT EXISTS `aps_instances_settings` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `server_id` int(11) NOT NULL DEFAULT '0',
  `instance_id` int(4) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `aps_packages`
--

CREATE TABLE IF NOT EXISTS `aps_packages` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `version` varchar(20) NOT NULL,
  `release` int(4) NOT NULL,
  `package_url` TEXT NOT NULL,
  `package_status` int(1) NOT NULL DEFAULT '2',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Table structure for table `aps_settings`
--

CREATE TABLE IF NOT EXISTS `aps_settings` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) DEFAULT CHARSET=utf8 ;

-- --------------------------------------------------------

--
-- Dumping data for table `aps_settings`
--

INSERT INTO `aps_settings` (`id`, `name`, `value`) VALUES(1, 'ignore-php-extension', '');
INSERT INTO `aps_settings` (`id`, `name`, `value`) VALUES(2, 'ignore-php-configuration', '');
INSERT INTO `aps_settings` (`id`, `name`, `value`) VALUES(3, 'ignore-webserver-module', '');

ALTER TABLE  `client` ADD  `limit_aps` int(11) NOT NULL DEFAULT '0' AFTER  `limit_webdav_user`;
ALTER TABLE  `client_template` ADD  `limit_aps` int(11) NOT NULL DEFAULT '0' AFTER  `limit_webdav_user`;