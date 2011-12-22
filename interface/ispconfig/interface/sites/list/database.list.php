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
$liste["name"] 				= "database";

// Database table
$liste["table"] 			= "web_database";

// Index index field of the database table
$liste["table_idx"]			= "database_id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Records per page
$liste["records_per_page"] 	= 15;

// Script File of the list
$liste["file"]				= "database_list.php";

// Script file of the edit form
$liste["edit_file"]			= "database_edit.php";

// Script File of the delete script
$liste["delete_file"]		= "database_del.php";

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

$liste["item"][] = array(	'field'		=> "remote_access",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'width'		=> "",
							'value'		=> array('y' => "Yes",'n' => "No"));

$liste["item"][] = array(	'field'		=> "server_id",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} AND db_server = 1 ORDER BY server_name',
														'keyfield'=> 'server_id',
														'valuefield'=> 'server_name'
									 				  ),
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "database_user",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "TEXT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(    'field'        => "database_name",
                            'datatype'    => "VARCHAR",
                            'formtype'    => "TEXT",
                            'op'        => "like",
                            'prefix'    => "%",
                            'suffix'    => "%",
                            'width'        => "",
                            'value'        => "");

?>