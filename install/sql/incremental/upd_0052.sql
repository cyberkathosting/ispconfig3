ALTER TABLE `client` ADD `default_slave_dnsserver` INT( 11 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `limit_dns_zone`;
