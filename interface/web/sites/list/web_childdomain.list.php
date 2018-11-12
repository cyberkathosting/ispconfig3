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
$liste["name"]     = "web_childdomain";

// Database table
$liste["table"]    = "web_domain";

// Index index field of the database table
$liste["table_idx"]   = "domain_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "web_childdomain_list.php";

// Script file of the edit form
$liste["edit_file"]   = "web_childdomain_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "web_childdomain_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
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


$liste["item"][] = array( 'field'  => "server_id",
	'datatype' => "INTEGER",
	'formtype' => "SELECT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT a.server_id, a.server_name FROM server a, web_domain b WHERE (a.server_id = b.server_id) AND ({AUTHSQL-B}) ORDER BY a.server_name',
		'keyfield'=> 'server_id',
		'valuefield'=> 'server_name'
	),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "parent_domain_id",
	'datatype' => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => "SELECT domain_id,domain FROM web_domain WHERE type = 'vhost' AND {AUTHSQL} ORDER BY domain",
		'keyfield'=> 'domain_id',
		'valuefield'=> 'domain'
	),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "domain",
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


?>
