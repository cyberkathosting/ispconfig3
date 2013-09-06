ALTER TABLE `client` ADD COLUMN `can_use_api` enum('n','y') NOT NULL DEFAULT 'n' AFTER `canceled`;
