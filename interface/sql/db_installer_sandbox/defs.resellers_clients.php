<?php


//*************************************************************************************
// Resellers, clients etc
//*************************************************************************************

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