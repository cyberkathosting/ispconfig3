ALTER TABLE `dns_rr` CHANGE `data` `data` TEXT NOT NULL;
ALTER TABLE `web_domain` DROP INDEX `serverdomain`, ADD UNIQUE  `serverdomain` (  `server_id` , `ip_address`, `domain` );
