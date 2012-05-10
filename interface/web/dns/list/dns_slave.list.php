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
$liste["name"] 				= "dns_slave";

// Database table
$liste["table"] 			= "dns_slave";

// Index index field of the database table
$liste["table_idx"]			= "id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Records per page
$liste["records_per_page"] 	= "15";

// Script File of the list
$liste["file"]				= "dns_slave_list.php";

// Script file of the edit form
$liste["edit_file"]			= "dns_slave_edit.php";

// Script File of the delete script
$liste["delete_file"]		= "dns_slave_del.php";

// Paging Template
$liste["paging_tpl"]		= "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]				= "yes";


/*****************************************************
* Suchfelder
*****************************************************/


$liste["item"][] = array(	'field'		=> "active",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'width'		=> "",
							'value'		=> array('Y' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'N' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));


$liste["item"][] = array(	'field'		=> "server_id",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'datasource'	=> array ( 	'type'	=> 'CUSTOM',
														'class'=> 'custom_datasource',
														'function'=> 'dns_servers'
									 				  ),
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "origin",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");


$liste["item"][] = array(	'field'		=> "ns",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");





?>