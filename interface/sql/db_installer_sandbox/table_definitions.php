<?php

/* Down the page
   * Mail related
   * System related
*/

//*************************************************************************************
// Mail Related
//*************************************************************************************

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

//*************************************************************************************
// System Related
//*************************************************************************************
$tables['sys_datalog'] = "
datalog_id bigint(20) NOTNULL AUTO PRIMARY,
dbtable C(255) NOTNULL DEFAULT '',
dbidx C(255) NOTNULL DEFAULT '',
server_id I NOTNULL DEFAULT '0',
action char(1) NOTNULL DEFAULT '',
tstamp bigint(20) NOTNULL DEFAULT '0',
user C(255) NOTNULL DEFAULT '',
data text NOTNULL
";

$tables['sys_dbsync'] = "
id bigint(20) NOTNULL AUTO PRIMARY,
jobname C(255) NOTNULL DEFAULT '',
sync_interval_minutes I NOTNULL DEFAULT '0',
db_type C(255) NOTNULL DEFAULT '',
db_host C(255) NOTNULL DEFAULT '',
db_name C(255) NOTNULL DEFAULT '',
db_username C(255) NOTNULL DEFAULT '',
db_password C(255) NOTNULL DEFAULT '',
db_tables C(255) NOTNULL DEFAULT 'admin,forms',
empty_datalog I NOTNULL DEFAULT '0',
sync_datalog_external I NOTNULL DEFAULT '0',
active I NOTNULL DEFAULT '1',
last_datalog_id bigint(20) NOTNULL DEFAULT '0'
";


$tables['sys_filesync'] = "
id bigint(20) NOTNULL AUTO PRIMARY,
jobname C(255) NOTNULL DEFAULT '',
sync_interval_minutes I NOTNULL DEFAULT '0',
ftp_host C(255) NOTNULL DEFAULT '',
ftp_path C(255) NOTNULL DEFAULT '',
ftp_username C(255) NOTNULL DEFAULT '',
ftp_password C(255) NOTNULL DEFAULT '',
local_path C(255) NOTNULL DEFAULT '',
wput_options C(255) NOTNULL DEFAULT '--timestamping --reupload --dont-continue',
active I NOTNULL DEFAULT '1'
";

$tables['sys_group'] = "
groupid I NOTNULL AUTO PRIMARY,
name C(255) NOTNULL DEFAULT '',
description text NOTNULL
";

$tables['sys_user'] = "
userid I NOTNULL AUTO PRIMARY,
sys_userid I NOTNULL DEFAULT '0',
sys_groupid I NOTNULL DEFAULT '0',
sys_perm_user C(5) NOTNULL DEFAULT '',
sys_perm_group C(5) NOTNULL DEFAULT '',
sys_perm_other C(5) NOTNULL DEFAULT '',
username C(100) NOTNULL DEFAULT '',
passwort C(100) NOTNULL DEFAULT '',
modules C(255) NOTNULL DEFAULT '',
startmodule C(255) NOTNULL DEFAULT '',
app_theme C(100) NOTNULL DEFAULT 'DEFAULT',
typ C(20) NOTNULL DEFAULT 'user',
active tinyint(4) NOTNULL DEFAULT '1',
name C(100) NOTNULL DEFAULT '',
vorname C(100) NOTNULL DEFAULT '',
unternehmen C(100) NOTNULL DEFAULT '',
strasse C(100) NOTNULL DEFAULT '',
ort C(100) NOTNULL DEFAULT '',
plz C(10) NOTNULL DEFAULT '',
land C(50) NOTNULL DEFAULT '',
email C(100) NOTNULL DEFAULT '',
url C(255) NOTNULL DEFAULT '',
telefon C(100) NOTNULL DEFAULT '',
fax C(100) NOTNULL DEFAULT '',
language C(10) NOTNULL DEFAULT 'de',
groups C(255) NOTNULL DEFAULT '',
DEFAULT_group I NOTNULL DEFAULT '0'
";

?>