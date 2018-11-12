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
$liste["name"]     = "spamfilter_policy";

// Database table
$liste["table"]    = "spamfilter_policy";

// Index index field of the database table
$liste["table_idx"]   = "id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "spamfilter_policy_list.php";

// Script file of the edit form
$liste["edit_file"]   = "spamfilter_policy_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "spamfilter_policy_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]    = "yes";


/*****************************************************
* Suchfelder
*****************************************************/


$liste["item"][] = array( 'field'  => "policy_name",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");


$liste["item"][] = array( 'field'  => "virus_lover",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('Y' => $app->lng('yes_txt'), 'N' => $app->lng('no_txt')));


$liste["item"][] = array( 'field'  => "spam_lover",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('Y' => $app->lng('yes_txt'), 'N' => $app->lng('no_txt')));


$liste["item"][] = array( 'field'  => "banned_files_lover",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('Y' => $app->lng('yes_txt'), 'N' => $app->lng('no_txt')));


$liste["item"][] = array( 'field'  => "bad_header_lover",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('Y' => $app->lng('yes_txt'), 'N' => $app->lng('no_txt')));

















?>
