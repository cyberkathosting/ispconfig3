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
$liste['name']     = 'datalog';

//* Database table
$liste['table']    = 'sys_datalog';

//* Primary index column
$liste['table_idx']   = 'datalog_id';

//* Search Field Prefix
$liste['search_prefix']  = 'search_';

//* Records per page
$liste['records_per_page']  = "15";

//* Script file for listing
$liste['file']    = 'datalog_list.php';

//* Script file to edit
$liste['edit_file']   = 'datalog_list.php';

//* Script file to delete
$liste['delete_file']  = 'datalog_del.php';

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

$liste["item"][] = array( 'field'  => "action",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('i' => "Insert", 'u' => "Update", 'd' => "Delete"));


$liste["item"][] = array( 'field'  => "dbtable",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");


?>
