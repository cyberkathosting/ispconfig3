ALTER TABLE `client_template` CHANGE `limit_aps` `limit_aps` INT( 11 ) NOT NULL DEFAULT '-1';
ALTER TABLE `mail_domain` ADD `dkim_public` MEDIUMTEXT NOT NULL AFTER `domain`;
ALTER TABLE `mail_domain` ADD `dkim_private` MEDIUMTEXT NOT NULL AFTER `domain`;
ALTER TABLE `mail_domain` ADD `dkim` ENUM( 'n', 'y' ) NOT NULL AFTER `domain`;
ALTER TABLE `client` ADD `default_slave_dnsserver` INT( 11 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `limit_dns_zone`;
