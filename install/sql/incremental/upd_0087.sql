ALTER TABLE `sys_datalog` ADD `session_id` varchar(64) NOT NULL DEFAULT '' AFTER `error`;
ALTER TABLE `sys_user` CHANGE `sys_userid` `sys_userid` INT(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Created by userid';
ALTER TABLE `sys_user` CHANGE `sys_groupid` `sys_groupid` INT(11) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Created by groupid';
ALTER TABLE `web_domain` ADD COLUMN `php_fpm_chroot` enum('n','y') NOT NULL DEFAULT 'n' AFTER `php_fpm_use_socket`;

CREATE TABLE IF NOT EXISTS `dns_ssl_ca` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_group` varchar(5) NOT NULL DEFAULT '',
  `sys_perm_other` varchar(5) NOT NULL DEFAULT '',
  `active` enum('N','Y') NOT NULL DEFAULT 'N',
  `ca_name` varchar(255) NOT NULL DEFAULT '',
  `ca_issue` varchar(255) NOT NULL DEFAULT '',
  `ca_wildcard` enum('Y','N') NOT NULL DEFAULT 'N',
  `ca_iodef` text NOT NULL,
  `ca_critical` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`ca_issue`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `dns_ssl_ca` ADD UNIQUE(`ca_issue`);

UPDATE `dns_ssl_ca` SET `ca_issue` = 'comodo.com' WHERE `ca_issue` = 'comodoca.com';
DELETE FROM `dns_ssl_ca` WHERE `ca_issue` = 'geotrust.com';
DELETE FROM `dns_ssl_ca` WHERE `ca_issue` = 'thawte.com';
UPDATE `dns_ssl_ca` SET `ca_name` = 'Symantec / Thawte / GeoTrust' WHERE `ca_issue` = 'symantec.com';

ALTER TABLE `dns_rr` CHANGE `type` `type` ENUM('A','AAAA','ALIAS','CAA','CNAME','DS','HINFO','LOC','MX','NAPTR','NS','PTR','RP','SRV','TXT','TLSA','DNSKEY') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `dns_rr` CHANGE `data` `data` TEXT NOT NULL;
INSERT IGNORE INTO `dns_ssl_ca` (`id`, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `active`, `ca_name`, `ca_issue`, `ca_wildcard`, `ca_iodef`, `ca_critical`) VALUES
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'AC Camerfirma', 'camerfirma.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'ACCV', 'accv.es', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Actalis', 'actalis.it', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Amazon', 'amazon.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Asseco', 'certum.pl', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Buypass', 'buypass.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'CA Disig', 'disig.sk', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'CATCert', 'aoc.cat', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Certinomis', 'www.certinomis.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Certizen', 'hongkongpost.gov.hk', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'certSIGN', 'certsign.ro', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'CFCA', 'cfca.com.cn', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Chunghwa Telecom', 'cht.com.tw', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Comodo', 'comodoca.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'D-TRUST', 'd-trust.net', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'DigiCert', 'digicert.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'DocuSign', 'docusign.fr', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'e-tugra', 'e-tugra.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'EDICOM', 'edicomgroup.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Entrust', 'entrust.net', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Firmaprofesional', 'firmaprofesional.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'FNMT', 'fnmt.es', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GlobalSign', 'globalsign.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GoDaddy', 'godaddy.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Google Trust Services', 'pki.goog', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'GRCA', 'gca.nat.gov.tw', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'HARICA', 'harica.gr', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'IdenTrust', 'identrust.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Izenpe', 'izenpe.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Kamu SM', 'kamusm.gov.tr', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Let''s Encrypt', 'letsencrypt.org', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Microsec e-Szigno', 'e-szigno.hu', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'NetLock', 'netlock.hu', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'PKIoverheid', 'www.pkioverheid.nl', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'PROCERT', 'procert.net.ve', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'QuoVadis', 'quovadisglobal.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'SECOM', 'secomtrust.net', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Sertifitseerimiskeskuse', 'sk.ee', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'StartCom', 'startcomca.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'SwissSign', 'swisssign.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Symantec / Thawte / GeoTrust', 'symantec.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'T-Systems', 'telesec.de', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Telia', 'telia.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Trustwave', 'trustwave.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'Web.com', 'web.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'WISeKey', 'wisekey.com', 'Y', '', 0),
(NULL, 1, 1, 'riud', 'riud', '', 'Y', 'WoSign', 'wosign.com', 'Y', '', 0);

ALTER TABLE `dns_soa` CHANGE `xfer` `xfer` TEXT NULL;
ALTER TABLE `dns_soa` CHANGE `also_notify` `also_notify` TEXT NULL;
ALTER TABLE `dns_slave` CHANGE `xfer` `xfer` TEXT NULL;
ALTER TABLE `firewall` CHANGE `tcp_port` `tcp_port` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
ALTER TABLE `firewall` CHANGE `udp_port` `udp_port` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;