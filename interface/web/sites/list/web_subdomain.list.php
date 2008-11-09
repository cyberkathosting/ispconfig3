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
$liste["name"] 				= "web_subdomain";

// Database table
$liste["table"] 			= "web_domain";

// Index index field of the database table
$liste["table_idx"]			= "domain_id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Records per page
$liste["records_per_page"] 	= 15;

// Script File of the list
$liste["file"]				= "web_subdomain_list.php";

// Script file of the edit form
$liste["edit_file"]			= "web_subdomain_edit.php";

// Script File of the delete script
$liste["delete_file"]		= "web_subdomain_del.php";

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
							'value'		=> array('y' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'n' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));


$liste["item"][] = array(	'field'		=> "server_id",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} ORDER BY server_name',
														'keyfield'=> 'server_id',
														'valuefield'=> 'server_name'
									 				  ),
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "parent_domain_id",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT domain_id,domain FROM web_domain WHERE type = 'vhost' AND {AUTHSQL} ORDER BY domain",
										'keyfield'=> 'domain_id',
										'valuefield'=> 'domain'
									 ),
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "domain",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");


?>