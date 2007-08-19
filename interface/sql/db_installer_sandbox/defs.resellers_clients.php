<?php


//*************************************************************************************
// Resellers, clients etc
//*************************************************************************************
$tables['client'] = "
`client_id` bigint(20) NOT NULL AUTO PRIMARY,
`sys_userid` int(11) NOTNULL DEFAULT '0',
`sys_groupid` int(11) NOTNULL DEFAULT '0',
`sys_perm_user` C(5),
`sys_perm_group` C(5),
`sys_perm_other` C(5),
`company_name` C(255),
`contact_name` C(255),
`street` C(255),
`zip` C(255),
`city` C(255),
`state` C(255),
`country` C(255),
`telephone` C(255),
`mobile` C(255),
`fax` C(255),
`email` C(255),
`internet` C(255) NOTNULL DEFAULT 'http://',
`icq` C(255),
`notes` text,
`default_mailserver` int(11) NOTNULL DEFAULT '1',
`limit_maildomain` int(11) NOTNULL DEFAULT '-1',
`limit_mailbox` int(11) NOTNULL DEFAULT '-1',
`limit_mailalias` int(11) NOTNULL DEFAULT '-1',
`limit_mailforward` int(11) NOTNULL DEFAULT '-1',
`limit_mailcatchall` int(11) NOTNULL DEFAULT '-1',
`limit_mailrouting` int(11) NOTNULL DEFAULT '0',
`limit_mailfilter` int(11) NOTNULL DEFAULT '-1',
`limit_fetchmail` int(11) NOTNULL DEFAULT '-1',
`limit_mailquota` int(11) NOTNULL DEFAULT '-1',
`limit_spamfilter_wblist` int(11) NOTNULL DEFAULT '0',
`limit_spamfilter_user` int(11) NOTNULL DEFAULT '0',
`limit_spamfilter_policy` int(11) NOTNULL DEFAULT '0',
`default_webserver` int(11) NOT NULL,
`limit_web_ip` text NOT NULL,
`username` C(255) ,
`password` C(255) ,
`language` C(255) NOTNULL DEFAULT 'en',
`usertheme` C(255) NOTNULL DEFAULT 'default'
"; 


$tables['reseller'] = "
reseller_id bigint(20) NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
company C(255) NOTNULL DEFAULT '',
title C(255) NOTNULL DEFAULT '',
firstname C(255) NOTNULL DEFAULT '',
surname C(255) NOTNULL DEFAULT '',
street C(255) NOTNULL DEFAULT '',
zip C(255) NOTNULL DEFAULT '',
city C(255) NOTNULL DEFAULT '',
country C(255) NOTNULL DEFAULT '',
telephone C(255) NOTNULL DEFAULT '',
mobile C(255) NOTNULL DEFAULT '',
fax C(255) NOTNULL DEFAULT '',
email C(255) NOTNULL DEFAULT '',
internet C(255) NOTNULL DEFAULT '',
icq C(255) NOTNULL DEFAULT '',
notes text NOTNULL,
limit_client I NOTNULL DEFAULT '-1',
limit_domain I NOTNULL DEFAULT '-1',
limit_subdomain I NOTNULL DEFAULT '-1',
limit_mailbox I NOTNULL DEFAULT '-1',
limit_mailalias I NOTNULL DEFAULT '-1',
limit_webquota I NOTNULL DEFAULT '-1',
limit_mailquota I NOTNULL DEFAULT '-1',
limit_database I NOTNULL DEFAULT '-1',
ip_address text NOTNULL
";


?>