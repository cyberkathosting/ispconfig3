ALTER TABLE  `web_domain` ADD UNIQUE  `serverdomain` (  `server_id` ,  `domain` );

ALTER TABLE  `dns_rr` DROP KEY rr,
	CHANGE  `name`  `name` VARCHAR( 128 ) NOT NULL,
	ADD KEY `rr` (`zone`,`type`,`name`);
