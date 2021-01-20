ALTER TABLE `remote_user` MODIFY `remote_password` VARCHAR(200) NOT NULL DEFAULT '';

ALTER TABLE `client` ADD COLUMN `limit_mail_wblist` INT(11) NOT NULL DEFAULT '0' AFTER `limit_mailrouting`;
ALTER TABLE `client_template` ADD COLUMN `limit_mail_wblist` INT(11) NOT NULL DEFAULT '0' AFTER `limit_mailrouting`;

ALTER TABLE mail_access DROP CONSTRAINT `server_id`;
SET SESSION old_alter_table=1;
ALTER IGNORE TABLE mail_access ADD UNIQUE KEY `unique_source` (`server_id`,`source`,`type`);
SET SESSION old_alter_table=0;
