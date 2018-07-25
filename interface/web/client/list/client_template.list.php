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
$liste["name"]     = "client_template";

// Database table
$liste["table"]    = "client_template";

// Index index field of the database table
$liste["table_idx"]   = "template_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "client_template_list.php";

// Script file of the edit form
$liste["edit_file"]   = "client_template_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "client_template_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]    = "yes";


/*****************************************************
* Suchfelder
*****************************************************/
if($_SESSION['s']['user']['typ'] == 'admin') {
	$liste["item"][] = array( 'field'  => 'sys_groupid',
		'datatype' => 'INTEGER',
		'formtype' => 'SELECT',
		'op'  => '=',
		'prefix' => '',
		'suffix' => '',
		'datasource' => array (  'type' => 'SQL',
			'querystring' => "SELECT sys_group.groupid,CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), IF(client.contact_firstname != '', CONCAT(client.contact_firstname, ' '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as name FROM sys_group, client WHERE sys_group.groupid != 1 AND sys_group.client_id = client.client_id ORDER BY client.company_name, client.contact_name",
			'keyfield'=> 'groupid',
			'valuefield'=> 'name'
		),
		'width'  => '',
		'value'  => ''
	);
}

$liste["item"][] = array( 'field'  => "template_id",
	'datatype' => "INTEGER",
	'formtype' => "TEXT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => "");


$liste["item"][] = array( 'field'  => "template_type",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('m' => "Main Template", 'a' => "Additional Template"));

$liste["item"][] = array( 'field'  => "template_name",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");
?>
