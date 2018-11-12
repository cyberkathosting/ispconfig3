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
	$liste["name"]     = "dns_soa_admin";
} else {
	$liste["name"]     = "dns_soa";
}

// Database table
$liste["table"]    = "dns_soa";

// Index index field of the database table
$liste["table_idx"]   = "id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "dns_soa_list.php";

// Script file of the edit form
$liste["edit_file"]   = "dns_soa_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "dns_soa_del.php";

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
	'value'  => array('Y' => $app->lng('yes_txt'), 'N' => $app->lng('no_txt')));


$liste["item"][] = array( 'field'  => "server_id",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'datasource' => array (  'type' => 'CUSTOM',
		'class'=> 'custom_datasource',
		'function'=> 'dns_servers'
	),
	'width'  => "",
	'value'  => "");
	
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

$liste["item"][] = array( 'field'  => "origin",
	'datatype' => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");


$liste["item"][] = array( 'field'  => "ns",
	'datatype' => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");


$liste["item"][] = array( 'field'  => "mbox",
	'datatype' => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");









?>
