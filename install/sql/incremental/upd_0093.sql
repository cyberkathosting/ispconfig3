ALTER TABLE `remote_user` MODIFY `remote_password` VARCHAR(200) NOT NULL DEFAULT '';

ALTER TABLE `client` ADD COLUMN `limit_mail_wblist` INT(11) NOT NULL DEFAULT '0' AFTER `limit_mailrouting`;
ALTER TABLE `client_template` ADD COLUMN `limit_mail_wblist` INT(11) NOT NULL DEFAULT '0' AFTER `limit_mailrouting`;

ALTER TABLE mail_access DROP CONSTRAINT `server_id`;
SET SESSION old_alter_table=1;
ALTER IGNORE TABLE mail_access ADD UNIQUE KEY `unique_source` (`server_id`,`source`,`type`);
SET SESSION old_alter_table=0;

ALTER TABLE mail_domain ADD COLUMN `relay_host` varchar(255) NOT NULL default '' AFTER `dkim_public`,
  ADD COLUMN `relay_user` varchar(255) NOT NULL default '' AFTER `relay_host`,
  ADD COLUMN `relay_pass` varchar(255) NOT NULL default '' AFTER `relay_user`;
-- Purge apps & addons installer (#5795)
DROP TABLE `software_package`;
DROP TABLE `software_repo`;
DROP TABLE `software_update`;
DROP TABLE `software_update_inst`;

-- Brexit
UPDATE `country` SET `eu` = 'n' WHERE `iso` = 'GB';

-- Add limit for per domain relaying
ALTER TABLE `client` ADD `limit_relayhost` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_spamfilter_policy`;
ALTER TABLE `client_template` ADD `limit_relayhost` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n' AFTER `limit_spamfilter_policy`;
