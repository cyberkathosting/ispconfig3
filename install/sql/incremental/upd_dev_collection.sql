ALTER TABLE `web_domain` ADD COLUMN `ssl_letsencrypt_exclude` enum('n','y') NOT NULL DEFAULT 'n' AFTER `ssl_letsencrypt`;
ALTER TABLE `remote_user` ADD `remote_access` ENUM('y','n') NOT NULL DEFAULT 'y' AFTER `remote_password`;
ALTER TABLE `remote_user` ADD `remote_ips` TEXT AFTER `remote_access`;
