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



// Name of the list
$liste["name"]    = "clients";

// Database table
$liste["table"]   = "client";

// Index index field of the database table
$liste["table_idx"]  = "client_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]   = "client_list.php";

// Script file of the edit form
$liste["edit_file"]  = "client_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "client_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable authe
$liste["auth"]   = "yes";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(   'field'     => "client_id",
	'datatype' => "INTEGER",
	'formtype' => "TEXT",
	'op' => "=",
	'prefix' => "",
	'suffix' => "",
	'width' => "",
	'value' => "");

$liste["item"][] = array(   'field'     => "company_name",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op' => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width' => "",
	'value' => "");

$liste["item"][] = array(   'field'     => "contact_name",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op' => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width' => "",
	'value' => "");

$liste["item"][] = array(   'field'     => "customer_no",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op' => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width' => "",
	'value' => "");

$liste["item"][] = array(   'field'     => "username",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op' => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width' => "",
	'value' => "");

$liste["item"][] = array(   'field'     => "city",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op' => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width' => "",
	'value' => "");

$liste["item"][] = array(   'field'     => "country",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op' => "=",
	'prefix' => "",
	'suffix' => "",
	'datasource'=> array (  'type'          => 'SQL',
		'querystring'   => 'SELECT iso,printable_name FROM country ORDER BY iso ASC',
		'keyfield'      => 'iso',
		'valuefield'    => 'printable_name'
	),
	'width' => "",
	'value' => "");
	
$liste["item"][] = array( 'field'  => "locked",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('y' => $app->lng('yes_txt'), 'n' => $app->lng('no_txt')));

if(is_file(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php')){
	$liste["item"][] = array( 'field'  => "validation_status",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('accept' => 'accept', 'review' => 'review', 'reject' => 'reject'));
}

?>
