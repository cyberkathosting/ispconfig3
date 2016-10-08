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
if($_SESSION['s']['user']['typ'] == 'admin') {
	$liste["name"]     = "database_user_admin";
} else {
	$liste["name"]     = "database_user";
}

// Database table
$liste["table"]    = "web_database_user";

// Index index field of the database table
$liste["table_idx"]   = "database_user_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "database_user_list.php";

// Script file of the edit form
$liste["edit_file"]   = "database_user_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "database_user_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]    = "yes";


/*****************************************************
* Suchfelder
*****************************************************/


if($_SESSION['s']['user']['typ'] == 'admin') {
	$liste["item"][] = array( 'field'  => "sys_groupid",
		'datatype' => "INTEGER",
		'formtype' => "SELECT",
		'op'  => "=",
		'prefix' => "",
		'suffix' => "",
		'datasource' => array (  'type' => 'SQL',
			//'querystring' => 'SELECT groupid, name FROM sys_group WHERE groupid != 1 ORDER BY name',
			'querystring' => "SELECT sys_group.groupid,CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), IF(client.contact_firstname != '', CONCAT(client.contact_firstname, ' '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as name FROM sys_group, client WHERE sys_group.groupid != 1 AND sys_group.client_id = client.client_id ORDER BY client.company_name, client.contact_name",
			'keyfield'=> 'groupid',
			'valuefield'=> 'name'
		),
		'width'  => "",
		'value'  => "");
}

$liste["item"][] = array( 'field'  => "database_user",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");


?>