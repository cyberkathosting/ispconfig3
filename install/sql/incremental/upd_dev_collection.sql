ALTER TABLE `web_domain` ADD COLUMN `ssl_letsencrypt_exclude` enum('n','y') NOT NULL DEFAULT 'n' AFTER `ssl_letsencrypt`;
