ALTER TABLE `client` ADD `limit_domainmodule` INT NOT NULL DEFAULT '0';
ALTER TABLE `client_template` ADD `limit_domainmodule` INT NOT NULL DEFAULT '0';
CREATE TABLE `client_message_template` (
  `client_message_template_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) NOT NULL DEFAULT '0',
  `sys_groupid` int(11) NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `template_type` varchar(255) DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`client_message_template_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
ALTER TABLE  `spamfilter_policy` ADD  `policyd_quota_in` int(11) NOT NULL DEFAULT  '-1',
ADD  `policyd_quota_in_period` int(11) NOT NULL DEFAULT  '24',
ADD  `policyd_quota_out` int(11) NOT NULL DEFAULT  '-1',
ADD  `policyd_quota_out_period` int(11) NOT NULL DEFAULT  '24',
ADD  `policyd_greylist` ENUM(  'Y',  'N' ) NOT NULL DEFAULT  'N';
