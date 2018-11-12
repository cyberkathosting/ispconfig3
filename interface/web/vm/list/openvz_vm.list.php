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
$liste["name"]     = "openvz_vm";

// Database table
$liste["table"]    = "openvz_vm";

// Index index field of the database table
$liste["table_idx"]   = "vm_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = 15;

// Script File of the list
$liste["file"]    = "openvz_vm_list.php";

// Script file of the edit form
$liste["edit_file"]   = "openvz_vm_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "openvz_vm_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable authe
$liste["auth"]    = "yes";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array( 'field'  => "active",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('y' => $app->lng('yes_txt'), 'n' => $app->lng('no_txt')));

$liste["item"][] = array( 'field'  => "veid",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "server_id",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "like",
	'prefix' => "",
	'suffix' => "",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT server_id, server_name FROM server WHERE vserver_server = 1 AND mirror_server_id = 0 ORDER BY server_name',
		'keyfield'=> 'server_id',
		'valuefield'=> 'server_name'
	),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "ostemplate_id",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "like",
	'prefix' => "",
	'suffix' => "",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT ostemplate_id,template_name FROM openvz_ostemplate WHERE {AUTHSQL} ORDER BY template_name',
		'keyfield'=> 'ostemplate_id',
		'valuefield'=> 'template_name'
	),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "template_id",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "like",
	'prefix' => "",
	'suffix' => "",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT template_id,template_name FROM openvz_template WHERE {AUTHSQL} ORDER BY template_name',
		'keyfield'=> 'template_id',
		'valuefield'=> 'template_name'
	),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "hostname",
	'datatype' => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "ip_address",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");


?>
