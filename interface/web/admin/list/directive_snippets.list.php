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
$liste["name"]     = "directive_snippets";

// Database table
$liste["table"]    = "directive_snippets";

// Index index field of the database table
$liste["table_idx"]   = "directive_snippets_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "directive_snippets_list.php";

// Script file of the edit form
$liste["edit_file"]   = "directive_snippets_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "directive_snippets_del.php";

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


$liste["item"][] = array( 'field'  => "name",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "type",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('apache' => 'Apache', 'nginx' => 'nginx', 'php' => 'PHP', 'proxy' => 'Proxy'));
	
$liste["item"][] = array( 'field'  => "customer_viewable",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('y' => $app->lng('yes_txt'), 'n' => $app->lng('no_txt')));
	
$liste["item"][] = array( 'field'  => "master_directive_snippets_id",
	'datatype' => "BOOLEAN",
	'formtype' => "SELECT",
	'op'  => "IS",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array(0 => $app->lng('select_directive_snippet_txt'), 1 => $app->lng('select_master_directive_snippet_txt')));

?>
