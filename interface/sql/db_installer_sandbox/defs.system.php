<?php

//*************************************************************************************
// System Related
//*************************************************************************************

$tables['sys_datalog'] = "
datalog_id I8 NOTNULL AUTO PRIMARY,
server_id I NOTNULL,
dbtable C(255) NOTNULL DEFAULT '',
dbidx C(255) NOTNULL DEFAULT '',
action char(1) NOTNULL DEFAULT '',
tstamp I8 NOTNULL DEFAULT '0',
user C(255) NOTNULL DEFAULT '',
data X NOTNULL
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
description text NOTNULL,
client_id int(11) NOT NULL default '0'
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
app_theme C(100) NOTNULL DEFAULT 'default',
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
default_group I NOTNULL DEFAULT '0',
client_id I INDEX
";

?>