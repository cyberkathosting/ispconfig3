CREATE TABLE `server_php` (
  `server_php_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `server_id` int(11) unsigned NOT NULL DEFAULT '0',
  `client_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `php_fastcgi_binary` varchar(255) DEFAULT NULL,
  `php_fastcgi_ini_dir` varchar(255) DEFAULT NULL,
  `php_fpm_init_script` varchar(255) DEFAULT NULL,
  `php_fpm_ini_dir` varchar(255) DEFAULT NULL,
  `php_fpm_pool_dir` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`server_php_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ALTER TABLE `web_domain` ADD `fastcgi_php_version` VARCHAR( 255 ) NULL DEFAULT NULL;