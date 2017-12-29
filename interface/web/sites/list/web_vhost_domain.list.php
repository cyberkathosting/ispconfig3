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

$vhostdomain_type = 'domain';

if(isset($_SESSION['s']['var']['vhostdomain_type'])) {
	if($_SESSION['s']['var']['vhostdomain_type'] == 'subdomain') {
		$vhostdomain_type = 'subdomain';
	} elseif($_SESSION['s']['var']['vhostdomain_type'] == 'aliasdomain') {
		$vhostdomain_type = 'aliasdomain';
	}
}


// Name of the list
if($_SESSION['s']['user']['typ'] == 'admin') {
	$liste["name"]     = "web_vhost_domain_admin";
} else {
	$liste["name"]     = "web_vhost_domain";
}

// Database table
$liste["table"]    = "web_domain";

// Index index field of the database table
$liste["table_idx"]   = "domain_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "web_vhost_domain_list.php";

// Script file of the edit form
$liste["edit_file"]   = "web_vhost_domain_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "web_vhost_domain_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]    = "yes";


/*****************************************************
* Suchfelder
*****************************************************/

if($vhostdomain_type == 'domain') {
	$liste["item"][] = array( 'field'  => "domain_id",
		'datatype' => "INTEGER",
		'formtype' => "TEXT",
		'op'  => "=",
		'prefix' => "",
		'suffix' => "",
		'width'  => "",
		'value'  => "");
}

$liste["item"][] = array( 'field'  => "active",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('y' => $app->lng('yes_txt'), 'n' => $app->lng('no_txt')));

if($_SESSION['s']['user']['typ'] == 'admin' && $vhostdomain_type == 'domain') {
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

$liste["item"][] = array( 'field'  => "server_id",
	'datatype' => "INTEGER",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT a.server_id, a.server_name FROM server a, web_domain b WHERE (a.server_id = b.server_id) AND ({AUTHSQL-B}) ORDER BY a.server_name',
		'keyfield'=> 'server_id',
		'valuefield'=> 'server_name'
	),
	'width'  => "",
	'value'  => "");

if($vhostdomain_type != 'domain') {
	$liste["item"][] = array( 'field'  => "parent_domain_id",
		'datatype' => "VARCHAR",
		'filters'   => array( 0 => array( 'event' => 'SHOW',
				'type' => 'IDNTOUTF8')
		),
		'formtype' => "SELECT",
		'op'  => "=",
		'prefix' => "",
		'suffix' => "",
		'datasource' => array (  'type' => 'SQL',
			'querystring' => "SELECT domain_id,domain FROM web_domain WHERE type = 'vhost' AND {AUTHSQL} ORDER BY domain",
			'keyfield'=> 'domain_id',
			'valuefield'=> 'domain'
		),
		'width'  => "",
		'value'  => "");

}

$liste["item"][] = array( 'field'  => "domain",
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
