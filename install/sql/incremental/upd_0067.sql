ALTER TABLE `client`
	ADD `web_servers` blob NOT NULL DEFAULT '' AFTER `default_webserver`,
	ADD `mail_servers` blob NOT NULL DEFAULT '' AFTER `default_mailserver`,
	ADD `db_servers` blob NOT NULL DEFAULT '' AFTER `default_dbserver`,
	ADD `dns_servers` blob NOT NULL DEFAULT '' AFTER `default_dnsserver`;

