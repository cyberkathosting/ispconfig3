ALTER TABLE `client`
	ADD `web_servers` blob NOT NULL AFTER `default_webserver`,
	ADD `mail_servers` blob NOT NULL AFTER `default_mailserver`,
	ADD `db_servers` blob NOT NULL AFTER `default_dbserver`,
	ADD `dns_servers` blob NOT NULL AFTER `default_dnsserver`;

UPDATE `client` SET `web_servers` = `default_webserver`, `mail_servers` = `default_mailserver`, `db_servers` = `default_dbserver`, `dns_servers` = `default_dnsserver` WHERE 1;
