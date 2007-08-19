<?php

//*************************************************************************************
// Mail Related
//*************************************************************************************

$tables['mail_access'] = "
`access_id` int(11) NOT NULL AUTO PRIMARY,
`sys_userid` int(11) NOTNULL DEFAULT '0',
`sys_groupid` int(11) NOTNULL DEFAULT '0',
`sys_perm_user` C(5) NOTNULL DEFAULT '',
`sys_perm_group` C(5) NOTNULL DEFAULT '',
`sys_perm_other` C(5) NOTNULL DEFAULT '',
`server_id` I INDEX NOTNULL DEFAULT '0',
`source` C(255) INDEX NOT NULL,
`access` C(255) NOT NULL,
`type` set('recipient','sender','client') NOT NULL,
`active` enum('n','y') NOTNULL DEFAULT 'y'
";


$tables['mail_blacklist'] = "
blacklist_id I NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
server_id I NOTNULL DEFAULT '0',
address C(200) NOTNULL DEFAULT '',
recipient C(200) NOTNULL DEFAULT '',
active enum('0','1') NOTNULL DEFAULT '1'
";

$tables['mail_box'] = "
mailbox_id I NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
server_id I NOTNULL DEFAULT '0',
email C(255) NOTNULL DEFAULT '',
cryptpwd C(128) NOTNULL DEFAULT '',
clearpwd C(128) NOTNULL DEFAULT '',
name C(128) NOTNULL DEFAULT '',
uid int(10) unsigned NOTNULL DEFAULT '0',
gid int(10) unsigned NOTNULL DEFAULT '0',
maildir C(255) NOTNULL DEFAULT '',
quota C(255) NOTNULL DEFAULT '',
autoresponder enum('0','1') NOTNULL DEFAULT '0',
autoresponder_text tinytext NOTNULL,
active enum('0','1') NOTNULL DEFAULT '1'
";

$tables['mail_content_filter'] = "
`content_filter_id` bigint(20) NOTNULL AUTO PRIMARY,
`sys_userid` int(11) NOTNULL DEFAULT '0',
`sys_groupid` int(11) NOTNULL DEFAULT '0',
`sys_perm_user` C(5),
`sys_perm_group` C(5),
`sys_perm_other` C(5),
`server_id` int(11) NOTNULL DEFAULT '0',
`type` C(255),
`pattern` C(255),
`data` C(255),
`action` C(255) ,
`active` C(255) NOTNULL DEFAULT 'y'
";

$tables['mail_domain'] = "
domain_id I NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
server_id I INDEX NOTNULL DEFAULT '0',
domain C(255) INDEX NOTNULL DEFAULT '',
type enum('local','relay','alias') NOTNULL DEFAULT 'local',
destination C(255) NOTNULL DEFAULT '',
active tinyint(4) NOTNULL DEFAULT '1'
";

$tables['mail_domain_catchall'] = "
domain_catchall_id I NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
server_id I NOTNULL DEFAULT '0',
domain C(255) NOTNULL DEFAULT '',
destination C(255) NOTNULL DEFAULT '',
active enum('0','1') NOTNULL DEFAULT '1'
";

$tables['mail_forwarding'] = "
`forwarding_id` int(11) NOTNULL AUTO PRIMARY,
`sys_userid` int(11) NOTNULL DEFAULT '0',
`sys_groupid` int(11) NOTNULL DEFAULT '0',
`sys_perm_user` C(5) NOTNULL DEFAULT '',
`sys_perm_group` C(5) NOTNULL DEFAULT '',
`sys_perm_other` C(5) NOTNULL DEFAULT '',
`server_id` I INDEX NOTNULL DEFAULT '0',
`source` C(255) INDEX NOTNULL,
`destination` C(255) NOTNULL DEFAULT '',
`type` enum('alias','forward','catchall') NOTNULL DEFAULT 'alias',
`active` enum('y','n') NOTNULL
";
 
$tables['mail_get'] = "
`mailget_id` bigint(20) NOT NULL AUTO PRIMARY,
`sys_userid` int(11) NOTNULL DEFAULT '0',
`sys_groupid` int(11) NOTNULL DEFAULT '0',
`sys_perm_user` C(5) ,
`sys_perm_group` C(5) ,
`sys_perm_other` C(5) ,
`server_id` int(11) NOTNULL DEFAULT '0',
`type` C(255) ,
`source_server` C(255) ,
`source_username` C(255) ,
`source_password` C(255) ,
`source_delete` C(255) NOTNULL DEFAULT 'y',
`destination` C(255) ,
`active` C(255) NOTNULL DEFAULT 'y'
"

$tables['mail_greylist'] = "
greylist_id I NOTNULL AUTO PRIMARY,
relay_ip C(64),
from_domain C(255) DEFAULT NULL,
block_expires datetime',
record_expires datetime',
origin_type enum('MANUAL','AUTO') NOTNULL DEFAULT 'AUTO',
create_time datetime'
";

$tables['mail_mailman_domain'] = "
mailman_id I NOTNULL AUTO PRIMARY,
server_id I NOTNULL DEFAULT '0',
domain C(255) NOTNULL DEFAULT '',
mm_home C(255) NOTNULL DEFAULT '',
mm_wrap C(255) NOTNULL DEFAULT '',
mm_user C(50) NOTNULL DEFAULT '',
mm_group C(50) NOTNULL DEFAULT ''
";

$tables['mail_redirect'] = "
redirect_id I NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
server_id I NOTNULL DEFAULT '0',
email C(255) NOTNULL DEFAULT '',
destination C(255) NOTNULL DEFAULT '',
type enum('alias','forward') NOTNULL DEFAULT 'alias',
active enum('0','1') NOTNULL DEFAULT '1'
";

$tables['mail_spamfilter'] = "
spamfilter_id I NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
server_id I NOTNULL DEFAULT '0',
email C(255) NOTNULL DEFAULT '',
spam_rewrite_score_int I NOTNULL DEFAULT '0',
spam_delete_score_int I NOTNULL DEFAULT '0',
spam_redirect_score_int I NOTNULL DEFAULT '0',
spam_rewrite_subject C(50) NOTNULL DEFAULT '***SPAM***',
spam_redirect_maildir C(255) NOTNULL DEFAULT '',
spam_redirect_maildir_purge I NOTNULL DEFAULT '7',
active enum('0','1') NOTNULL DEFAULT '1'
";

$tables['mail_traffic'] = "
`traffic_id` I NOTNULL AUTO PRIMARY,
`mailuser_id` I NOTNULL,
`month` C(7) INDEX NOTNULL,
`traffic` bigint(20) unsigned NOT NULL
";

$tables['mail_transport'] = "
transport_id I NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
server_id I NOTNULL DEFAULT '0',
domain C(255) NOTNULL DEFAULT '',
transport C(255) NOTNULL DEFAULT '',
sort_order I NOT NULL default '5',
destination C(255) NOTNULL DEFAULT '',
active enum('0','1') NOTNULL DEFAULT '1'
";

$tables['mail_user'] = "
`mailuser_id` I NOTNULL AUTO PRIMARY,
`sys_userid` I NOTNULL DEFAULT '0',
`sys_groupid` I NOTNULL DEFAULT '0',
`sys_perm_user` C(5) NOTNULL DEFAULT '',
`sys_perm_group` C(5) NOTNULL DEFAULT '',
`sys_perm_other` C(5) NOTNULL DEFAULT '',
`server_id` I INDEX NOTNULL DEFAULT '0',
`email` C(255) INDEX NOTNULL DEFAULT '',
`password` C(255) NOTNULL,
`name` C(128) NOTNULL DEFAULT '',
`uid` int(10) unsigned NOTNULL DEFAULT '5000',
`gid` int(10) unsigned NOTNULL DEFAULT '5000',
`maildir` C(255) NOTNULL DEFAULT '',
`quota` I NOTNULL,
`homedir` C(255) NOTNULL,
`autoresponder` enum('n','y') NOTNULL DEFAULT 'n',
`autoresponder_text` tinytext NOTNULL,
`custom_mailfilter` text,
`postfix` enum('y','n') NOTNULL,
`access` enum('y','n') NOTNULL
";

$tables['mail_whitelist'] = "
whitelist_id I NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
server_id I NOTNULL DEFAULT '0',
address C(255) NOTNULL DEFAULT '',
recipient C(255) NOTNULL DEFAULT '',
active enum('0','1') NOTNULL DEFAULT '1'
";


//*************************************************************************************
// Spam spam spam spam, sing along..
//*************************************************************************************

$tables['spamfilter_policy'] = "
`id` I NOTNULL AUTO PRIMARY,
`sys_userid` I NOTNULL,
`sys_groupid` I NOTNULL,
`sys_perm_user` C(5) NOTNULL,
`sys_perm_group` C(5) NOTNULL,
`sys_perm_other` C(5) NOTNULL,
`policy_name` C(32) ,
`virus_lover` C(1) ,
`spam_lover` C(1) ,
`banned_files_lover` C(1) ,
`bad_header_lover` C(1) ,
`bypass_virus_checks` C(1) ,
`bypass_spam_checks` C(1) ,
`bypass_banned_checks` C(1) ,
`bypass_header_checks` C(1) ,
`spam_modifies_subj` C(1) ,
`virus_quarantine_to` C(64) ,
`spam_quarantine_to` C(64) ,
`banned_quarantine_to` C(64) ,
`bad_header_quarantine_to` C(64) ,
`clean_quarantine_to` C(64) ,
`other_quarantine_to` C(64) ,
`spam_tag_level` F,
`spam_tag2_level` F,
`spam_kill_level` F,
`spam_dsn_cutoff_level` F,
`spam_quarantine_cutoff_level` F,
`addr_extension_virus` C(64) ,
`addr_extension_spam` C(64) ,
`addr_extension_banned` C(64) ,
`addr_extension_bad_header` C(64) ,
`warnvirusrecip` C(1) ,
`warnbannedrecip` C(1) ,
`warnbadhrecip` C(1) ,
`newvirus_admin` C(64) ,
`virus_admin` C(64) ,
`banned_admin` C(64) ,
`bad_header_admin` C(64) ,
`spam_admin` C(64) ,
`spam_subject_tag` C(64) ,
`spam_subject_tag2` C(64) ,
`message_size_limit` I ,
`banned_rulenames` C(64)
";

//TODO Unique index on email
$tables['spamfilter_users'] = "
`id` int(10) NOTNULL AUTO PRIMARY,
`sys_userid` I NOTNULL,
`sys_groupid` I NOTNULL,
`sys_perm_user` C(5) NOTNULL,
`sys_perm_group` C(5) NOTNULL,
`sys_perm_other` C(5) NOTNULL,
`server_id` int(10) unsigned NOTNULL,
`priority` I NOTNULL DEFAULT '7',
`policy_id` int(10) unsigned NOTNULL DEFAULT '1',
`email` C(255) INDEX NOTNULL,
`fullname` C(255) ,
`local` char(1)
";

//TODO Enum
$tables['spamfilter_wblist'] = "
`wblist_id` I NOTNULL AUTO PRIMARY,
`sys_userid` I NOTNULL,
`sys_groupid` I NOTNULL,
`sys_perm_user` C(5) NOTNULL,
`sys_perm_group` C(5) NOTNULL,
`sys_perm_other` C(5) NOTNULL,
`server_id` int(10) unsigned NOTNULL,
`wb` enum('W','B') NOTNULL DEFAULT 'W',
`rid` int(10) unsigned NOTNULL,
`email` C(255) NOTNULL,
`priority` I NOTNULL,
`active` enum('y','n') NOTNULL DEFAULT 'y'
";

$tables['rr'] = "
id int(10) unsigned NOTNULL AUTO PRIMARY,
zone int(10) unsigned NOTNULL DEFAULT '0',
name C(64) NOTNULL DEFAULT '',
type enum('A','AAAA','ALIAS','CNAME','HINFO','MX','NS','PTR','RP','SRV','TXT') DEFAULT NULL,
data C(128) NOTNULL DEFAULT '',
aux int(10) unsigned NOTNULL DEFAULT '0',
ttl int(10) unsigned NOTNULL DEFAULT '86400',
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT ''
";

$tables['server'] = "
server_id bigint(20) NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
server_name C(255) NOTNULL DEFAULT '',
mail_server tinyint(4) NOTNULL DEFAULT '0',
web_server tinyint(4) NOTNULL DEFAULT '0',
dns_server tinyint(4) NOTNULL DEFAULT '0',
file_server tinyint(4) NOTNULL DEFAULT '0',
db_server tinyint(4) NOTNULL DEFAULT '0',
vserver_server tinyint(4) NOTNULL DEFAULT '0',
config text NOTNULL,
`update` tinyint(4) NOTNULL DEFAULT '0',
active tinyint(4) NOTNULL DEFAULT '1',
";


$tables['server_ip'] = "
`server_ip_id` bigint(20) NOTNULL AUTO PRIMARY,
`sys_userid` I NOTNULL DEFAULT '0',
`sys_groupid` I NOTNULL DEFAULT '0',
`sys_perm_user` C(5) ,
`sys_perm_group` C(5) ,
`sys_perm_other` C(5) ,
`server_id` int(10) unsigned NOTNULL DEFAULT '0',
`ip_address` C(15) ,
`virtualhost` C(1) NOTNULL DEFAULT 'y'
";


$tables['soa'] = "
id int(10) unsigned NOTNULL AUTO PRIMARY,
origin C(255) NOTNULL DEFAULT '',
ns C(255) NOTNULL DEFAULT '',
mbox C(255) NOTNULL DEFAULT '',
serial int(10) unsigned NOTNULL DEFAULT '1',
refresh int(10) unsigned NOTNULL DEFAULT '28800',
retry int(10) unsigned NOTNULL DEFAULT '7200',
expire int(10) unsigned NOTNULL DEFAULT '604800',
minimum int(10) unsigned NOTNULL DEFAULT '86400',
ttl int(10) unsigned NOTNULL DEFAULT '86400',
active enum('Y','N') NOTNULL DEFAULT 'Y',
xfer C(255) NOTNULL DEFAULT '',
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT ''
";



?>