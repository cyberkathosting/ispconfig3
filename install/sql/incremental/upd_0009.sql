CREATE TABLE IF NOT EXISTS `proxy_reverse` (
  `rewrite_id` int(11) NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `rewrite_url_src` varchar(100) NOT NULL,
  `rewrite_url_dst` varchar(100) NOT NULL,
  `active` enum('n','y') NOT NULL default 'y',
  PRIMARY KEY  (`rewrite_id`)
) AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `firewall_filter` (
  `firewall_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `domain_id` int(11) NOT NULL,
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `rule_name` varchar(100) default NULL,
  `rule_id` int(11) default 1,
  `src_ip` varchar(20) NOT NULL,
  `src_netmask` varchar(20) NOT NULL,
  `dst_ip` varchar(20) NOT NULL,
  `dst_netmask` varchar(20) NOT NULL,
  `src_from_port` varchar(10) NOT NULL,
  `src_to_port` varchar(10) NOT NULL,
  `dst_to_port` varchar(10) NOT NULL,
  `dst_from_port` varchar(10) NOT NULL,
  `protocol` varchar(10) default 'tcp',
  `inbound_policy` enum('allow','deny','reject','limit') default 'allow',
  `outbound_policy` enum('allow','deny','reject','limit') default 'allow',
  `active` enum('n','y') NOT NULL default 'y',
  `client_id` int(11) NOT NULL,
  PRIMARY KEY  (`firewall_id`)
) AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `firewall_forward` (
  `firewall_id` int(11) unsigned NOT NULL auto_increment,
  `sys_userid` int(11) unsigned NOT NULL default '0',
  `domain_id` int(11) NOT NULL,
  `sys_groupid` int(11) unsigned NOT NULL default '0',
  `sys_perm_user` varchar(5) default NULL,
  `sys_perm_group` varchar(5) default NULL,
  `sys_perm_other` varchar(5) default NULL,
  `server_id` int(11) unsigned NOT NULL default '0',
  `application_name` varchar(100) default NULL,
  `dst_ip` varchar(20) NOT NULL,
  `src_from_port` varchar(10) NOT NULL,
  `src_to_port` varchar(10) NOT NULL,
  `dst_to_port` varchar(10) NOT NULL,
  `dst_from_port` varchar(10) NOT NULL,
  `protocol` int(3) default 0,
  `active` enum('n','y') NOT NULL default 'y',
  `client_id` int(11) NOT NULL,
  PRIMARY KEY  (`firewall_id`)
) AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

alter table `server` add column `proxy_server` tinyint(1) not null after `vserver_server`;
alter table `server` add column `firewall_server` tinyint(1) not null after `proxy_server`;
alter table `web_domain` add column `nginx_directives` mediumtext null after `apache_directives`;
