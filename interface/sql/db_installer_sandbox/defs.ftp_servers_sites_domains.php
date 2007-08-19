<?php



$tables['web_domain'] = "
  `domain_id` bigint(20) NOTNULL AUTO PRIMARY,
  `sys_userid` I NOTNULL DEFAULT '0',
  `sys_groupid` I NOTNULL DEFAULT '0',
  `sys_perm_user` C(5) ,
  `sys_perm_group` C(5) ,
  `sys_perm_other` C(5) ,
  `server_id` I NOTNULL DEFAULT '0',
  `ip_address` C(15) ,
  `domain` C(255) ,
  `type` C(255) NOTNULL DEFAULT 'y',
  `parent_domain_id` I NOTNULL DEFAULT '0',
  `vhost_type` C(255) ,
  `document_root` C(255) ,
  `system_user` C(255) ,
  `system_group` C(255) ,
  `hd_quota` I NOTNULL DEFAULT '0',
  `traffic_quota` I NOTNULL DEFAULT '0',
  `cgi` C(255) NOTNULL DEFAULT 'y',
  `ssi` C(255) NOTNULL DEFAULT 'y',
  `suexec` C(255) NOTNULL DEFAULT 'y',
  `php` C(255) NOTNULL DEFAULT 'y',
  `redirect_type` C(255) ,
  `redirect_path` C(255) ,
  `active` C(255) NOTNULL DEFAULT 'y',
  PRIMARY KEY  (`domain_id`)
)  ;
?>