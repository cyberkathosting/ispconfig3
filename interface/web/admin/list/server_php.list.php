<?php
/*
    Datatypes:
    - INTEGER
    - DOUBLE
    - CURRENCY
    - VARCHAR
    - TEXT
    - DATE
*/

//* Name of list
$liste['name']     = 'server_php';

//* Database table
$liste['table']    = 'server_php';

//* Primary index column
$liste['table_idx']   = 'server_php_id';

//* Search Field Prefix
$liste['search_prefix']  = 'search_';

//* Records per page
$liste['records_per_page']  = "15";

//* Script file for listing
$liste['file']    = 'server_php_list.php';

//* Script file to edit
$liste['edit_file']   = 'server_php_edit.php';

//* Script file to delete
$liste['delete_file']  = 'server_php_del.php';

//* Paging template
$liste['paging_tpl']  = 'templates/paging.tpl.htm';

//* Enable auth
$liste['auth']    = 'no';


/*****************************************************
* Suchfelder
*****************************************************/

$liste['item'][] = array( 'field'  => 'active',
	'datatype' => 'VARCHAR',
	'formtype' => 'SELECT',
	'op'  => '=',
	'prefix' => '',
	'suffix' => '',
	'width'  => '',
	'value'  => array('y' => $app->lng('yes_txt'), 'n' => $app->lng('no_txt')));

$liste['item'][] = array( 'field'  => 'server_id',
	'datatype' => 'INTEGER',
	'formtype' => 'SELECT',
	'op'  => '=',
	'prefix' => '',
	'suffix' => '',
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} AND mirror_server_id = 0 ORDER BY server_name',
		'keyfield'=> 'server_id',
		'valuefield'=> 'server_name'
	),
	'width'  => '',
	'value'  => '');

$liste['item'][] = array( 'field'  => 'client_id',
	'datatype' => 'INTEGER',
	'formtype' => 'SELECT',
	'op'  => '=',
	'prefix' => '',
	'suffix' => '',
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT client_id,contact_name FROM client WHERE {AUTHSQL} ORDER BY contact_name',
		'keyfield'=> 'client_id',
		'valuefield'=> 'contact_name'
	),
	'width'  => '',
	'value'  => '');

$liste['item'][] = array( 'field'  => 'name',
	'datatype' => 'VARCHAR',
	'op'  => 'like',
	'prefix' => '%',
	'suffix' => '%',
	'width'  => '');

?>
