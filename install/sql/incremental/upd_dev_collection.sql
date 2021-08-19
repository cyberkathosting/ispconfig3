-- default spamfilter_users.policy_id to 0
ALTER TABLE `spamfilter_users` ALTER `policy_id` SET DEFAULT 0;

-- mail_forwarding.source must be unique
ALTER TABLE `mail_forwarding` DROP KEY `server_id`;
ALTER TABLE `mail_forwarding` ADD UNIQUE KEY `server_id` (`server_id`, `source`);
