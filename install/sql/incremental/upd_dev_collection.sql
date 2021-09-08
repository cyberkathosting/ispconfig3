-- Add column for email backup limit (#5732)
ALTER TABLE `client_template` ADD `limit_mail_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_spamfilter_policy`;
ALTER TABLE `client` ADD `limit_mail_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_spamfilter_policy`;

-- default spamfilter_users.policy_id to 0
ALTER TABLE `spamfilter_users` ALTER `policy_id` SET DEFAULT 0;

-- mail_forwarding.source must be unique
ALTER TABLE `mail_forwarding` DROP KEY `server_id`;
ALTER TABLE `mail_forwarding` ADD KEY `server_id` (`server_id`, `source`);

-- Purge apps & addons installer (#5795) - second time due to syntax error in 0093
DROP TABLE IF EXISTS `software_package`;
DROP TABLE IF EXISTS `software_repo`;
DROP TABLE IF EXISTS `software_update`;
DROP TABLE IF EXISTS `software_update_inst`;
