ALTER TABLE `mail_domain` ADD `dkim_selector` VARCHAR(63) DEFAULT 'default' AFTER `dkim`;
