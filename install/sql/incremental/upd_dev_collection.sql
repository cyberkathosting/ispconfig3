-- drop old php column because new installations don't have them (fails in multi-server)
ALTER TABLE `web_domain` DROP COLUMN `fastcgi_php_version`;

-- add php_fpm_socket_dir column to server_php
ALTER TABLE `server_php` ADD `php_fpm_socket_dir` varchar(255) DEFAULT NULL AFTER `php_fpm_pool_dir`;

-- fix #5939
UPDATE `ftp_user` SET `expires` = NULL WHERE `expires` = '0000-00-00 00:00:00';
