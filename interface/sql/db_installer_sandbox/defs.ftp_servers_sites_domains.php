<?php

//*************************************************************************************
// DNS
//*************************************************************************************

//TODO - Enum
$tables['dns_rr'] = "
`id` I unsigned NOTNULL AUTO PRIMARY,
`sys_userid` I NOTNULL,
`sys_groupid` I NOTNULL,
`sys_perm_user` C(5) NOTNULL,
`sys_perm_group` C(5) NOTNULL,
`sys_perm_other` C(5) NOTNULL,
`server_id` I NOTNULL DEFAULT '1',
`zone` I unsigned NOTNULL,
`name` C(64) NOTNULL,
`type` enum('A','AAAA','ALIAS','CNAME','HINFO','MX','NAPTR','NS','PTR','RP','SRV','TXT') ,
`data` char(128) NOTNULL,
`aux` I unsigned NOTNULL DEFAULT '0',
`ttl` I unsigned NOTNULL DEFAULT '86400',
`active` enum('N','Y') NOTNULL DEFAULT 'Y'
";
 
//TODO char(255)
$tables['dns_soa'] = "
`id` I unsigned NOTNULL AUTO PRIMARY,
`sys_userid` I NOTNULL,
`sys_groupid` I NOTNULL,
`sys_perm_user` C(5) NOTNULL,
`sys_perm_group` C(5) NOTNULL,
`sys_perm_other` C(5) NOTNULL,
`server_id` I NOTNULL DEFAULT '1',
`origin` char(255) NOTNULL,
`ns` char(255) NOTNULL,
`mbox` char(255) NOTNULL,
`serial` I NOTNULL DEFAULT '1',
`refresh` I NOTNULL DEFAULT '28800',
`retry` I NOTNULL DEFAULT '7200',
`expire` I NOTNULL DEFAULT '604800',
`minimum` I NOTNULL DEFAULT '86400',
`ttl` I unsigned NOTNULL DEFAULT '86400',
`active` enum('Y','N') NOTNULL,
`xfer` char(255) NOTNULL
";


//*************************************************************************************
// Ftp Related
//*************************************************************************************

$tables['ftp_user'] = "
`ftp_user_id` I8 NOTNULL AUTO PRIMARY,
`sys_userid` I NOTNULL DEFAULT '0',
`sys_groupid` I NOTNULL DEFAULT '0',
`sys_perm_user` C(5),
`sys_perm_group` C(5),
`sys_perm_other` C(5),
`server_id` I NOTNULL DEFAULT '0',
`parent_domain_id` I NOTNULL DEFAULT '0',
`username` C(255),
`password` C(255),
`quota_size` I NOTNULL DEFAULT '-1',
`active` C(255) NOTNULL DEFAULT 'y',
`uid` C(255),
`gid` C(255),
`dir` C(255),
`quota_files` I NOTNULL DEFAULT '-1',
`ul_ratio` I NOTNULL DEFAULT '-1',
`dl_ratio` I NOTNULL DEFAULT '-1',
`ul_bandwidth` I NOTNULL DEFAULT '-1',
`dl_bandwidth` I NOTNULL DEFAULT '-1'
";

//*************************************************************************************
// Web Domain
//*************************************************************************************

$tables['web_domain'] = "
`domain_id` I8 NOTNULL AUTO PRIMARY,
`sys_userid` I NOTNULL DEFAULT '0',
`sys_groupid` I NOTNULL DEFAULT '0',
`sys_perm_user` C(5),
`sys_perm_group` C(5),
`sys_perm_other` C(5),
`server_id` I NOTNULL DEFAULT '0',
`ip_address` C(15) ,
`domain` C(255) ,
`type` C(255) NOTNULL DEFAULT 'y',
`parent_domain_id` I NOTNULL DEFAULT '0',
`vhost_type` C(255),
`document_root` C(255),
`system_user` C(255),
`system_group` C(255),
`hd_quota` I NOTNULL DEFAULT '0',
`traffic_quota` I NOTNULL DEFAULT '0',
`cgi` C(255) NOTNULL DEFAULT 'y',
`ssi` C(255) NOTNULL DEFAULT 'y',
`suexec` C(255) NOTNULL DEFAULT 'y',
`php` C(255) NOTNULL DEFAULT 'y',
`redirect_type` C(255),
`redirect_path` C(255),
`active` C(255) NOTNULL DEFAULT 'y'
";
?>