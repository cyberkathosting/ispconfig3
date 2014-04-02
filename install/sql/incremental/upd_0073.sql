ALTER TABLE `mail_domain` ADD `dkim_selector` VARCHAR(63) NOT NULL DEFAULT 'default' AFTER `dkim`;
