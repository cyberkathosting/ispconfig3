ALTER TABLE  `dbispconfig`.`web_domain` ADD UNIQUE  `serverdomain` (  `server_id` ,  `domain` );
DROP INDEX rr ON dns_rr;
ALTER TABLE  `dns_rr` CHANGE  `name`  `name` VARCHAR( 128 ) NOT NULL ;
CREATE INDEX `rr` ON dns_rr (`zone`,`type`,`name`);