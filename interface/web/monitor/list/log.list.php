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
$liste['name']     = 'syslog';

//* Database table
$liste['table']    = 'sys_log';

//* Primary index column
$liste['table_idx']   = 'syslog_id';

//* Search Field Prefix
$liste['search_prefix']  = 'search_';

//* Records per page
$liste['records_per_page']  = "15";

//* Script file for listing
$liste['file']    = 'log_list.php';

//* Script file to edit
$liste['edit_file']   = 'log_list.php';

//* Script file to delete
$liste['delete_file']  = 'log_del.php';

//* Paging template
$liste['paging_tpl']  = 'templates/paging.tpl.htm';

//* Enable auth
$liste['auth']    = 'no';

/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array( 'field'  => "tstamp",
	'datatype' => "DATETIME",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => "");


$liste['item'][] = array( 'field'  => 'server_id',
	'datatype' => 'INTEGER',
	'formtype' => 'SELECT',
	'op'  => '=',
	'prefix' => '',
	'suffix' => '',
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} ORDER BY server_name',
		'keyfield'=> 'server_id',
		'valuefield'=> 'server_name'
	),
	'width'  => '',
	'value'  => '');

$liste["item"][] = array( 'field'  => "loglevel",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('0' => "Debug", '1' => "Warning", '2' => "Error"));


$liste["item"][] = array( 'field'  => "message",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");


?>
