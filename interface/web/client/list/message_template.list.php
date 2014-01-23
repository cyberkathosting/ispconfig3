<?php

/*
Copyright (c) 2010, Till Brehm, projektfarm Gmbh
All rights reserved.
*/

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
$liste["name"]     = "client_message_template";

// Database table
$liste["table"]    = "client_message_template";

// Index index field of the database table
$liste["table_idx"]   = "client_message_template_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = 15;

// Script File of the list
$liste["file"]    = "message_template_list.php";

// Script file of the edit form
$liste["edit_file"]   = "message_template_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "message_template_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable authe
$liste["auth"]    = "yes";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array( 'field'  => "template_type",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('welcome' => 'Default welcome email', 'other' => 'Other'));

$liste["item"][] = array( 'field'  => "template_name",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");





?>
