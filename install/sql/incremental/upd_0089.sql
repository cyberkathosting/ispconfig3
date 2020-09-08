-- add new proxy_protocol column
ALTER TABLE `web_domain`
    ADD COLUMN `proxy_protocol` ENUM('n','y') NOT NULL DEFAULT 'n' AFTER `log_retention`;

-- backup format
ALTER TABLE `web_domain` ADD  `backup_format_web` VARCHAR( 255 ) NOT NULL default 'default' AFTER `backup_copies`;
ALTER TABLE `web_domain` ADD  `backup_format_db` VARCHAR( 255 ) NOT NULL default 'gzip' AFTER `backup_format_web`;
-- end of backup format

-- backup encryption
ALTER TABLE `web_domain` ADD  `backup_encrypt` enum('n','y') NOT NULL DEFAULT 'n' AFTER `backup_format_db`;
ALTER TABLE `web_domain` ADD  `backup_password` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `backup_encrypt`;
ALTER TABLE `web_backup` ADD  `backup_format` VARCHAR( 64 ) NOT NULL DEFAULT '' AFTER `backup_mode`;
ALTER TABLE `web_backup` ADD  `backup_password` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `filesize`;
-- end of backup encryption

-- rename Comodo to "Sectigo / Comodo CA"
UPDATE `dns_ssl_ca` SET `ca_name` = 'Sectigo / Comodo CA' WHERE `ca_issue` = 'comodoca.com';

-- default php-fpm to ondemand mode
ALTER TABLE `web_domain` ALTER pm SET DEFAULT 'ondemand';

ALTER TABLE `mail_user`
  ADD `purge_trash_days` INT NOT NULL DEFAULT '0' AFTER `move_junk`,
  ADD `purge_junk_days` INT NOT NULL DEFAULT '0' AFTER `purge_trash_days`;

-- doveadm should be enabled for all mailboxes
UPDATE `mail_user` set `disabledoveadm` = 'n';

-- add disablequota-status for quota-status policy daemon
ALTER TABLE `mail_user` ADD `disablequota-status` ENUM('n','y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'n' AFTER `disabledoveadm`;

-- add disableindexer-worker for solr search
ALTER TABLE `mail_user` ADD `disableindexer-worker` ENUM('n','y') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'n' AFTER `disablequota-status`;

-- add SSHFP and DNAME record
ALTER TABLE `dns_rr` CHANGE `type` `type` ENUM('A','AAAA','ALIAS','CNAME','DNAME','CAA','DS','HINFO','LOC','MX','NAPTR','NS','PTR','RP','SRV','SSHFP','TXT','TLSA','DNSKEY') NULL DEFAULT NULL AFTER `name`;

-- change cc and sender_cc column type
ALTER TABLE `mail_user` CHANGE `cc` `cc` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci;

-- remove SPDY option
ALTER TABLE `web_domain` DROP COLUMN `enable_spdy`;

-- was missing in incremental, inserted for fixing older installations
ALTER TABLE `web_domain` ADD `folder_directive_snippets` TEXT NULL AFTER `https_port`;

ALTER TABLE `web_domain` ADD `server_php_id` INT(11) UNSIGNED NOT NULL DEFAULT 0;

UPDATE `web_domain` as w LEFT JOIN sys_group as g ON (g.groupid = w.sys_groupid) INNER JOIN `server_php` as p ON (w.fastcgi_php_version = CONCAT(p.name, ':', p.php_fastcgi_binary, ':', p.php_fastcgi_ini_dir) AND p.server_id IN (0, w.server_id) AND p.client_id IN (0, g.client_id)) SET w.server_php_id = p.server_php_id, w.fastcgi_php_version = '' WHERE 1;

UPDATE `web_domain` as w LEFT JOIN sys_group as g ON (g.groupid = w.sys_groupid) INNER JOIN `server_php` as p ON (w.fastcgi_php_version = CONCAT(p.name, ':', p.php_fpm_init_script, ':', p.php_fpm_ini_dir, ':', p.php_fpm_pool_dir) AND p.server_id IN (0, w.server_id) AND p.client_id IN (0, g.client_id)) SET w.server_php_id = p.server_php_id, w.fastcgi_php_version = '' WHERE 1;

ALTER TABLE `web_domain` CHANGE `apache_directives` `apache_directives` mediumtext NULL DEFAULT NULL;
ALTER TABLE `web_domain` CHANGE `nginx_directives` `nginx_directives` mediumtext NULL DEFAULT NULL;

-- add move to junk before/after option, default to after
ALTER TABLE `mail_user` MODIFY `move_junk` enum('y','a','n') NOT NULL DEFAULT 'y';

-- Change id_rsa column to TEXT format
ALTER TABLE `client` CHANGE `id_rsa` `id_rsa` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE `directive_snippets` ADD `update_sites` ENUM('y','n') NOT NULL DEFAULT 'n' ;

-- Add DNSSEC Algorithm setting
ALTER TABLE `dns_soa` ADD `dnssec_algo` SET('NSEC3RSASHA1','ECDSAP256SHA256') NULL DEFAULT NULL AFTER `dnssec_wanted`;
UPDATE `dns_soa` SET `dnssec_algo` = 'NSEC3RSASHA1' WHERE `dnssec_algo` IS NULL AND dnssec_initialized = 'Y';
UPDATE `dns_soa` SET `dnssec_algo` = 'ECDSAP256SHA256' WHERE `dnssec_algo` IS NULL AND dnssec_initialized = 'N';
ALTER TABLE `dns_soa` CHANGE `dnssec_algo` `dnssec_algo` SET('NSEC3RSASHA1','ECDSAP256SHA256') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'ECDSAP256SHA256';

-- Fix issue #5635
ALTER TABLE `client_template` CHANGE `ssh_chroot` `ssh_chroot` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `client_template` CHANGE `web_php_options` `web_php_options` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';

-- add option to forward in lda, default to forward in mta except for existing forwards
ALTER TABLE `mail_user` ADD `forward_in_lda` enum('n','y') NOT NULL default 'n' AFTER `cc`;
UPDATE `mail_user` set `forward_in_lda` = 'y' where `cc` != '';