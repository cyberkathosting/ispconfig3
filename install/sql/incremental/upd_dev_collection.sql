-- add new proxy_protocol column
ALTER TABLE `web_domain`
    ADD COLUMN `proxy_protocol` ENUM('n','y') NOT NULL DEFAULT 'n' AFTER `log_retention`;

-- backup format
ALTER TABLE `web_domain` ADD  `backup_format_web` VARCHAR( 255 ) NOT NULL default 'default' AFTER `backup_copies`;
ALTER TABLE `web_domain` ADD  `backup_format_db` VARCHAR( 255 ) NOT NULL default 'gzip' AFTER `backup_format_web`;
-- end of backup format

-- backup encryption
ALTER TABLE `web_domain` ADD  `backup_encrypt` enum('n','y') NOT NULL DEFAULT 'n' AFTER `backup_format_db`;
ALTER TABLE `web_domain` ADD  `backup_password` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `backup_encrypt`;
ALTER TABLE `web_backup` ADD  `backup_format` VARCHAR( 64 ) NOT NULL DEFAULT '' AFTER `backup_mode`;
ALTER TABLE `web_backup` ADD  `backup_password` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `filesize`;
-- end of backup encryption

-- rename Comodo to "Sectigo / Comodo CA"
UPDATE `dns_ssl_ca` SET `ca_name` = 'Sectigo / Comodo CA' WHERE `ca_issue` = 'comodoca.com';

-- default php-fpm to ondemand mode
ALTER TABLE `web_domain` ALTER pm SET DEFAULT 'ondemand';

ALTER TABLE `mail_user` 
  ADD `purge_trash_days` INT NOT NULL DEFAULT '0' AFTER `move_junk`,
  ADD `purge_junk_days` INT NOT NULL DEFAULT '0' AFTER `purge_trash_days`;

-- doveadm should be enabled for all mailboxes
UPDATE `mail_user` set `disabledoveadm` = 'n';

-- add disablequota-status for quota-status policy daemon
ALTER TABLE `mail_user` ADD `disablequota-status` ENUM('n','y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'n' AFTER `disabledoveadm`;

-- add disableindexer-worker for solr search
ALTER TABLE `mail_user` ADD `disableindexer-worker` ENUM('n','y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'n' AFTER `disablequota-status`;
