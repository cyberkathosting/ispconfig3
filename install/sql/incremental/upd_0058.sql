ALTER TABLE `client` ADD COLUMN `can_use_api` enum('n','y') NOT NULL DEFAULT 'n' AFTER `canceled`;

ALTER TABLE `remote_session` ADD COLUMN `client_login` tinyint(1) unsigned NOT NULL default '0' AFTER `remote_functions`;

