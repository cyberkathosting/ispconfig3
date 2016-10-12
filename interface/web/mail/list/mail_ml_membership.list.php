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
$liste["name"]    = "mail_ml_membership";

// Database table
$liste["table"]   = "mail_ml_membership";

// Index index field of the database table
$liste["table_idx"]  = "subscriber_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]   = "mail_ml_membership_list.php";

// Script file of the edit form
$liste["edit_file"]  = "mail_ml_membership_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "mail_ml_membership_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]   = "yes";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array( 'field'  => "mailinglist_id",
	'datatype' => "INTEGER",
	'formtype' => "SELECT",
	'op'  => "like",
	'prefix' => "",
	'suffix' => "",
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT mailinglist_id, CONCAT_WS(\'@\', listname, domain) as listname FROM mail_mailinglist WHERE {AUTHSQL} ORDER BY listname',
		'keyfield'=> 'mailinglist_id',
		'valuefield'=> 'listname'
	),
	'width'  => "",
	'value'  => "");


$liste["item"][] = array(   'field'     => "email",
	'datatype'  => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype'  => "TEXT",
	'op'        => "like",
	'prefix'    => "%",
	'suffix'    => "%",
	'width'     => "",
	'value'     => "");

$liste["item"][] = array(   'field'     => "name",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op' => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width' => "",
	'value' => "");

$liste["item"][] = array( 'field'  => "goodbye_msg",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('n' => "<div id=\"ir-No\" class=\"swap\"><span>".$app->lng('no_txt')."</span></div>", 'y' => "<div class=\"swap\" id=\"ir-Yes\"><span>".$app->lng('yes_txt')."</span></div>"));
