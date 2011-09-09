ALTER TABLE  `server_ip` ADD  `ip_type` enum(  'IPv4',  'IPv6' ) NOT NULL DEFAULT  'IPv4' AFTER  `server_id`;
ALTER TABLE  `server_ip` ADD  `virtualhost_port` VARCHAR( 255 ) NOT NULL DEFAULT  '80,443';
ALTER TABLE  `server_ip` ADD  `client_id` int(11) unsigned NOT NULL default '0' AFTER  `server_id`;
ALTER TABLE  `mail_user` ADD  `disablesieve` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n';
ALTER TABLE  `mail_user` ADD  `disablelda` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'n';