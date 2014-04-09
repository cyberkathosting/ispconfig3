ALTER TABLE `client_template` ADD `limit_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_webdav_user`;
ALTER TABLE `client` ADD `limit_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_webdav_user`;
