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
$liste["name"]     = "cron";

// Database table
$liste["table"]    = "cron";

// Index index field of the database table
$liste["table_idx"]   = "id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "cron_list.php";

// Script file of the edit form
$liste["edit_file"]   = "cron_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "cron_del.php";

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
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} AND mirror_server_id = 0 ORDER BY server_name',
		'keyfield'=> 'server_id',
		'valuefield'=> 'server_name'
	),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array(   'field'     => "parent_domain_id",
	'datatype'  => "VARCHAR",
	'formtype'  => "SELECT",
	'op'        => "=",
	'prefix'    => "",
	'suffix'    => "",
	'datasource'    => array (  'type'  => 'SQL',
		'querystring' => "SELECT domain_id,domain FROM web_domain WHERE type = 'vhost' AND {AUTHSQL} ORDER BY domain",
		'keyfield'=> 'domain_id',
		'valuefield'=> 'domain'
	),
	'width'     => "",
	'value'     => "");

$liste["item"][] = array(   'field'     => "run_min",
	'datatype'  => "VARCHAR",
	'formtype'  => "TEXT",
	'op'        => "=",
	'prefix'    => "",
	'suffix'    => "",
	'width'     => "",
	'value'     => "");

$liste["item"][] = array(   'field'     => "run_hour",
	'datatype'  => "VARCHAR",
	'formtype'  => "TEXT",
	'op'        => "=",
	'prefix'    => "",
	'suffix'    => "",
	'width'     => "",
	'value'     => "");

$liste["item"][] = array(   'field'     => "run_mday",
	'datatype'  => "VARCHAR",
	'formtype'  => "TEXT",
	'op'        => "=",
	'prefix'    => "",
	'suffix'    => "",
	'width'     => "",
	'value'     => "");

$liste["item"][] = array(   'field'     => "run_month",
	'datatype'  => "VARCHAR",
	'formtype'  => "TEXT",
	'op'        => "=",
	'prefix'    => "",
	'suffix'    => "",
	'width'     => "",
	'value'     => "");

$liste["item"][] = array(   'field'     => "run_wday",
	'datatype'  => "VARCHAR",
	'formtype'  => "TEXT",
	'op'        => "=",
	'prefix'    => "",
	'suffix'    => "",
	'width'     => "",
	'value'     => "");


$liste["item"][] = array( 'field'  => "command",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");









?>
