-- default spamfilter_users.policy_id to 0
ALTER TABLE `spamfilter_users` ALTER `policy_id` SET DEFAULT 0;

-- mail_forwarding.source must be unique
ALTER TABLE `mail_forwarding` DROP KEY `server_id`;
ALTER TABLE `mail_forwarding` ADD UNIQUE KEY `server_id` (`server_id`, `source`);

-- Purge apps & addons installer (#5795) - second time due to syntax error in 0093
DROP TABLE IF EXISTS `software_package`;
DROP TABLE IF EXISTS `software_repo`;
DROP TABLE IF EXISTS `software_update`;
DROP TABLE IF EXISTS `software_update_inst`;
