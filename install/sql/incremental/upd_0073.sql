ALTER TABLE `client_template` ADD `limit_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_webdav_user`;
ALTER TABLE `client` ADD `limit_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_webdav_user`;
ALTER TABLE  `web_domain` CHANGE  `php_fpm_use_socket`  `php_fpm_use_socket` ENUM(  'n',  'y' ) NOT NULL DEFAULT  'y';
