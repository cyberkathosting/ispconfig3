ALTER TABLE `dns_slave` DROP INDEX `origin`;
ALTER TABLE `dns_slave` ADD CONSTRAINT `slave` UNIQUE (`origin`,`server_id`);