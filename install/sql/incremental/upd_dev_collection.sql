-- Add column for email backup limit (#5732)
ALTER TABLE `client_template` ADD `limit_mail_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_spamfilter_policy`;
ALTER TABLE `client` ADD `limit_mail_backup` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y' AFTER `limit_spamfilter_policy`;
