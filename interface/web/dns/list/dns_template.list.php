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
$liste["name"]    = "dns_template";

// Database table
$liste["table"]   = "dns_template";

// Index index field of the database table
$liste["table_idx"]  = "template_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]   = "dns_template_list.php";

// Script file of the edit form
$liste["edit_file"]  = "dns_template_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "dns_template_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]   = "yes";


/*****************************************************
* Suchfelder
*****************************************************/


$liste["item"][] = array(   'field'     => "visible",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op' => "=",
	'prefix' => "",
	'suffix' => "",
	'width' => "",
	'value' => array('Y' => $app->lng('yes_txt'), 'N' => $app->lng('no_txt')));


$liste["item"][] = array(   'field' => "name",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op' => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width' => "",
	'value' => "");

?>
