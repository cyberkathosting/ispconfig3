<?php

// Name of the list
$liste['name'] = 'remote_user';

// Database table
$liste['table'] = 'remote_user';

// Index index field of the database table
$liste['table_idx'] = 'remote_userid';

// Search Field Prefix
$liste['search_prefix'] = 'search_';

// Records per page
$liste['records_per_page']= "15";

// Script File of the list
$liste['file'] = 'remote_user_list.php';

// Script file of the edit form
$liste['edit_file'] = 'remote_user_edit.php';

// Script File of the delete script
$liste['delete_file'] = 'remote_user_del.php';

// Paging Template
$liste['paging_tpl'] = 'templates/paging.tpl.htm';

// Enable auth
$liste['auth'] = 'yes';


//****** Search fields

$liste['item'][] = array(
	'field'      => 'remote_userid',
	'datatype'   => 'VARCHAR',
	'formtype'   => 'SELECT',
	'op'         => '=',
	'prefix'     => '',
	'suffix'     => '',
	'width'      => '',
	'datasource' => array(
		'type' => 'SQL',
		'querystring' => 'SELECT remote_userid,remote_username FROM remote_user WHERE {AUTHSQL} ORDER BY remote_username',
		'keyfield'    => 'remote_userid',
		'valuefield'  => 'remote_userid'
	),
	'value'      => ''
);

$liste['item'][] = array(
	'field'      => 'remote_username',
	'datatype'   => 'VARCHAR',
	'formtype'   => 'TEXT',
	'op'         => 'like',
	'prefix'     => '%',
	'suffix'     => '%',
	'width'      => '',
	'value'      => ''
);

?>
