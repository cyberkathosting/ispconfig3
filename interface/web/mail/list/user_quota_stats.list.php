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
$liste["name"]     = "user_quota_stats";

// Database table
$liste["table"]    = "mail_user";

// Index index field of the database table
$liste["table_idx"]   = "mailuser_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "user_quota_stats.php";

// Script file of the edit form
$liste["edit_file"]   = "mail_user_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "mail_user_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]    = "yes";

// mark columns for php sorting (no real mySQL columns)
$liste["phpsort"] = array('used', 'percentage');

/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(   'field'     => "email",
	'datatype'  => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype'  => "TEXT",
	'op'        => "like",
	'prefix'    => "%",
	'suffix'    => "%",
	'width'     => "",
	'value'     => "");

$liste["item"][] = array(   'field'     => "name",
	'datatype'  => "VARCHAR",
	'formtype'  => "TEXT",
	'op'        => "like",
	'prefix'    => "%",
	'suffix'    => "%",
	'width'     => "",
	'value'     => "");

$liste["item"][] = array(   'field'     => "quota",
	'datatype'  => "VARCHAR",
	'formtype'  => "TEXT",
	'op'        => "like",
	'prefix'    => "%",
	'suffix'    => "%",
	'width'     => "",
	'value'     => "");


?>
