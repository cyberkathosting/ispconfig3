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
$liste["name"] 				= "domain";

// Database table
$liste["table"] 			= "domain";

// Index index field of the database table
$liste["table_idx"]			= "domain_id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Records per page
$liste["records_per_page"] 	= 30;

// Script File of the list
$liste["file"]				= "domain_list.php";

// Script file of the edit form
$liste["edit_file"]			= "domain_edit.php";

// Script File of the delete script
$liste["delete_file"]		= "domain_del.php";

// Paging Template
$liste["paging_tpl"]		= "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]				= "yes";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(	'field'		=> "domain",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");


?>