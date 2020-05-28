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
