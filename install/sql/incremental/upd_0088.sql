-- rspamd
ALTER TABLE `spamfilter_policy` ADD `rspamd_greylisting` ENUM('n','y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'n' AFTER `policyd_greylist`;
ALTER TABLE `spamfilter_policy` ADD `rspamd_spam_greylisting_level` DECIMAL(5,2) NULL DEFAULT NULL AFTER `rspamd_greylisting`;
ALTER TABLE `spamfilter_policy` ADD `rspamd_spam_tag_level` DECIMAL(5,2) NULL DEFAULT NULL AFTER `rspamd_spam_greylisting_level`;
ALTER TABLE `spamfilter_policy` ADD `rspamd_spam_tag_method` ENUM('add_header','rewrite_subject') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'rewrite_subject' AFTER `rspamd_spam_tag_level`;
ALTER TABLE `spamfilter_policy` ADD `rspamd_spam_kill_level` DECIMAL(5,2) NULL DEFAULT NULL AFTER `rspamd_spam_tag_method`;

UPDATE `spamfilter_policy` SET `rspamd_greylisting` = 'y' WHERE id = 4;
UPDATE `spamfilter_policy` SET `rspamd_greylisting` = 'y' WHERE id = 5;
UPDATE `spamfilter_policy` SET `rspamd_greylisting` = 'y' WHERE id = 6;

UPDATE `spamfilter_policy` SET `rspamd_spam_greylisting_level` = '4.00';
UPDATE `spamfilter_policy` SET `rspamd_spam_greylisting_level` = '6.00' WHERE id = 1;
UPDATE `spamfilter_policy` SET `rspamd_spam_greylisting_level` = '999.00' WHERE id = 2;
UPDATE `spamfilter_policy` SET `rspamd_spam_greylisting_level` = '999.00' WHERE id = 3;
UPDATE `spamfilter_policy` SET `rspamd_spam_greylisting_level` = '2.00' WHERE id = 6;
UPDATE `spamfilter_policy` SET `rspamd_spam_greylisting_level` = '7.00' WHERE id = 7;

UPDATE `spamfilter_policy` SET `rspamd_spam_tag_level` = '6.00';
UPDATE `spamfilter_policy` SET `rspamd_spam_tag_level` = '8.00' WHERE id = 1;
UPDATE `spamfilter_policy` SET `rspamd_spam_tag_level` = '999.00' WHERE id = 2;
UPDATE `spamfilter_policy` SET `rspamd_spam_tag_level` = '999.00' WHERE id = 3;
UPDATE `spamfilter_policy` SET `rspamd_spam_tag_level` = '4.00' WHERE id = 6;
UPDATE `spamfilter_policy` SET `rspamd_spam_tag_level` = '10.00' WHERE id = 7;

UPDATE `spamfilter_policy` SET `rspamd_spam_kill_level` = '10.00';
UPDATE `spamfilter_policy` SET `rspamd_spam_kill_level` = '12.00' WHERE id = 1;
UPDATE `spamfilter_policy` SET `rspamd_spam_kill_level` = '999.00' WHERE id = 2;
UPDATE `spamfilter_policy` SET `rspamd_spam_kill_level` = '999.00' WHERE id = 3;
UPDATE `spamfilter_policy` SET `rspamd_spam_kill_level` = '8.00' WHERE id = 6;
UPDATE `spamfilter_policy` SET `rspamd_spam_kill_level` = '20.00' WHERE id = 7;
-- end of rspamd
ALTER TABLE `client` CHANGE COLUMN `password` `password` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `ftp_user` CHANGE COLUMN `password` `password` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `shell_user` CHANGE COLUMN `password` `password` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `sys_user` CHANGE COLUMN `passwort` `passwort` VARCHAR(200) DEFAULT NULL;
ALTER TABLE `webdav_user` CHANGE COLUMN `password` `password` VARCHAR(200) DEFAULT NULL;

DELETE FROM sys_cron WHERE `next_run` IS NOT NULL AND `next_run` >= DATE_ADD(`last_run`, INTERVAL 30 DAY) AND `next_run` BETWEEN '2020-01-01' AND '2020-01-02';