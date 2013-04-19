ALTER TABLE `mail_domain` ADD `dkim_public` MEDIUMTEXT NOT NULL AFTER `domain`;
ALTER TABLE `mail_domain` ADD `dkim_private` MEDIUMTEXT NOT NULL AFTER `domain`;
ALTER TABLE `mail_domain` ADD `dkim` ENUM( 'n', 'y' ) NOT NULL AFTER `domain`;
