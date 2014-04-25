ALTER TABLE `client`
	ADD `limit_database_quota` int(11) NOT NULL default '-1' AFTER	`limit_database`;
ALTER TABLE `client_template`
	ADD `limit_database_quota` int(11) NOT NULL default '-1' AFTER	`limit_database`;
ALTER TABLE `web_database`
	ADD `database_quota` int(11) unsigned DEFAULT NULL AFTER `database_name_prefix`;
