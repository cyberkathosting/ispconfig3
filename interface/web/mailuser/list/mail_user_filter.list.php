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
$liste["name"]     = "mail_user_filter";

// Database table
$liste["table"]    = "mail_user_filter";

// Index index field of the database table
$liste["table_idx"]   = "filter_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "mail_user_filter_list.php";

// Script file of the edit form
$liste["edit_file"]   = "mail_user_filter_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "mail_user_filter_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]    = "no";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array( 'field'  => "rulename",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");


?>
