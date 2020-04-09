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
$liste["name"]     = "dns_a";

// Database table
$liste["table"]    = "dns_rr";

// Index index field of the database table
$liste["table_idx"]   = "id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "dns_a_list.php";

// Script file of the edit form
$liste["edit_file"]   = "dns_a_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "dns_a_del.php";

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
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} ORDER BY server_name',
		'keyfield'=> 'server_id',
		'valuefield'=> 'server_name'
	),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "zone",
	'datatype' => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype' => "SELECT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT id,origin FROM dns_soa WHERE {AUTHSQL} ORDER BY origin',
		'keyfield'=> 'id',
		'valuefield'=> 'origin'
	),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "name",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "data",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "aux",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "ttl",
	'datatype' => "INTEGER",
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "type",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "like",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('A'=>'A', 'AAAA' => 'AAAA', 'ALIAS'=>'ALIAS', 'CAA'=>'CAA', 'CNAME'=>'CNAME', 'DS'=>'DS', 'HINFO'=>'HINFO', 'LOC'=>'LOC', 'MX'=>'MX', 'NAPTR'=>'NAPTR', 'NS'=>'NS', 'PTR'=>'PTR', 'RP'=>'RP', 'SRV'=>'SRV', 'TLSA'=>'TLSA', 'TXT'=>'TXT'));


?>
