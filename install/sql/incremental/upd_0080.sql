ALTER TABLE `web_domain` ADD COLUMN `enable_spdy` ENUM('y','n') NULL DEFAULT 'n' AFTER `proxy_directives`;
