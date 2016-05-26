<?php

//* Name of the list
$liste['name']     = 'support_message';

//* Database table
$liste['table']    = 'support_message';

//* Index index field of the database table
$liste['table_idx']   = 'support_message_id';

//* Search Field Prefix
$liste['search_prefix']  = 'search_';

//* Records per page
$liste['records_per_page']  = 15;

//* Script File of the list
$liste['file']    = 'support_message_list.php';

//* Script file of the edit form
$liste['edit_file']   = 'support_message_edit.php';

//* Script File of the delete script
$liste['delete_file']  = 'support_message_del.php';

//* Paging Template
$liste['paging_tpl']  = 'templates/paging.tpl.htm';

//* Enable auth
$liste['auth']    = 'yes';


/*****************************************************
* Search fields
*****************************************************/

$liste['item'][] = array( 'field'  => 'sender_id',
	'datatype' => 'VARCHAR',
	'formtype' => 'SELECT',
	'op'  => '=',
	'prefix' => '',
	'suffix' => '',
	'width'  => '',
	'datasource'=> array (  'type' => 'SQL',
//		'querystring'  => 'SELECT userid,username FROM sys_user WHERE {AUTHSQL} ORDER BY username',
		'querystring'  => 'SELECT userid,username FROM sys_user ORDER BY username',
		'keyfield'  => 'userid',
		'valuefield' => 'username'
	),
	'value'  => '');

$liste['item'][] = array( 'field'  => 'subject',
	'datatype' => 'VARCHAR',
	'formtype' => 'TEXT',
	'op'  => 'like',
	'prefix' => '%',
	'suffix' => '%',
	'width'  => '',
	'value'  => '');

$liste['item'][] = array( 'field'  => 'tstamp',
	'datatype' => 'DATETIMETSTAMP',
	'formtype' => 'TEXT',
	'op'  => '=',
	'prefix' => '',
	'suffix' => '',
	'width'  => '',
	'value'  => '');


?>
