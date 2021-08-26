-- default spamfilter_users.policy_id to 0
ALTER TABLE `spamfilter_users` ALTER `policy_id` SET DEFAULT 0;

-- mail_forwarding.source must be unique
ALTER TABLE `mail_forwarding` DROP KEY `server_id`;
ALTER TABLE `mail_forwarding` ADD UNIQUE KEY `server_id` (`server_id`, `source`);

-- create mail_relay_domain and load with current domains from mail_transport table
CREATE TABLE IF NOT EXISTS `mail_relay_domain` (
	  `relay_domain_id` bigint(20) NOT NULL AUTO_INCREMENT,
	  `sys_userid` int(11) NOT NULL DEFAULT '0',
	  `sys_groupid` int(11) NOT NULL DEFAULT '0',
	  `sys_perm_user` varchar(5) DEFAULT NULL,
	  `sys_perm_group` varchar(5) DEFAULT NULL,
	  `sys_perm_other` varchar(5) DEFAULT NULL,
	  `server_id` int(11) NOT NULL DEFAULT '0',
	  `domain` varchar(255) DEFAULT NULL,
	  `access` varchar(255) NOT NULL DEFAULT 'OK',
	  `active` varchar(255) NOT NULL DEFAULT 'y',
	  PRIMARY KEY (`relay_domain_id`),
	  UNIQUE KEY `domain` (`domain`, `server_id`)
	) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `mail_relay_domain` SELECT NULL, `sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `domain`, 'OK', `active` FROM `mail_transport` WHERE `domain` NOT LIKE '%@%' AND `domain` LIKE '%.%' GROUP BY `domain`, `server_id`;

