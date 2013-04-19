ALTER TABLE `client_template` CHANGE `limit_aps` `limit_aps` INT( 11 ) NOT NULL DEFAULT '-1';
ALTER TABLE `web_backup` ADD `filesize` VARCHAR(10) NOT NULL AFTER `filename`;
