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
$liste["name"]     = "web_folder_user";

// Database table
$liste["table"]    = "web_folder_user";

// Index index field of the database table
$liste["table_idx"]   = "web_folder_user_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "web_folder_user_list.php";

// Script file of the edit form
$liste["edit_file"]   = "web_folder_user_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "web_folder_user_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]    = "yes";

$auth_sql = $app->tform->getAuthSQL('r', 'web_domain');


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


$liste["item"][] = array( 'field'  => "web_folder_id",
	'datatype' => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => "Select concat(web_domain.domain,' ',web_folder.path) as name, web_folder.web_folder_id from web_domain, web_folder WHERE web_domain.domain_id = web_folder.parent_domain_id AND ".$auth_sql." ORDER BY web_domain.domain",
		'keyfield'=> 'web_folder_id',
		'valuefield'=> 'name'
	),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "username",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");


?>
