
-- Removal of the domain module
UPDATE sys_user SET startmodule = 'dashboard' WHERE startmodule = 'domain';
UPDATE sys_user SET modules = replace(modules, ',domain', '') WHERE modules like '%domain%';

-- --------------------------------------------------------

-- 
-- Table structure for table  `directive_snippets`
-- 

CREATE TABLE IF NOT EXISTS `directive_snippets` (
  `directive_snippets_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sys_userid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_groupid` int(11) unsigned NOT NULL DEFAULT '0',
  `sys_perm_user` varchar(5) DEFAULT NULL,
  `sys_perm_group` varchar(5) DEFAULT NULL,
  `sys_perm_other` varchar(5) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `snippet` mediumtext,
  `active` enum('n','y') NOT NULL DEFAULT 'y',
  PRIMARY KEY (`directive_snippets_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
