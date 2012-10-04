
-- Add bank account owner
ALTER TABLE `client` ADD `bank_account_owner` varchar(255) DEFAULT NULL AFTER `notes`;

