ALTER TABLE `mail_user`
	CHANGE `uid` `uid` int(11) NOT NULL DEFAULT '5000',
	CHANGE `gid` `gid` int(11) NOT NULL DEFAULT '5000';

ALTER TABLE `mail_user`
	ADD COLUMN `sender_cc` varchar(255) NOT NULL DEFAULT '' AFTER `cc`;

ALTER TABLE `client_template` ADD `default_mailserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `client_template` ADD `default_webserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `client_template` ADD `default_dnsserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `client_template` ADD `default_slave_dnsserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE `client_template` ADD `default_dbserver` INT(11) NOT NULL DEFAULT 1;
ALTER TABLE  `client` ADD  `contact_firstname` VARCHAR( 64 ) NOT NULL DEFAULT '' AFTER  `gender`;

UPDATE `dns_template` SET `fields` = 'DOMAIN,IP,NS1,NS2,EMAIL,DKIM' WHERE `dns_template`.`template_id` =1;
UPDATE `dns_template` SET `template` = '[ZONE]
origin={DOMAIN}.
ns={NS1}.
mbox={EMAIL}.
refresh=7200
retry=540
expire=604800
minimum=86400
ttl=3600

[DNS_RECORDS]
A|{DOMAIN}.|{IP}|0|3600
A|www|{IP}|0|3600
A|mail|{IP}|0|3600
NS|{DOMAIN}.|{NS1}.|0|3600
NS|{DOMAIN}.|{NS2}.|0|3600
MX|{DOMAIN}.|mail.{DOMAIN}.|10|3600
TXT|{DOMAIN}.|v=spf1 mx a ~all|0|3600' WHERE `dns_template`.`template_id` = 1;

ALTER TABLE `mail_backup` CHANGE `filesize` `filesize` VARCHAR(20) NOT NULL DEFAULT '';
ALTER TABLE `web_backup` CHANGE `filesize` `filesize` VARCHAR(20) NOT NULL DEFAULT '';
