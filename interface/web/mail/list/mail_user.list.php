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
$liste["name"]    = "mail_user";

// Database table
$liste["table"]   = "mail_user";

// Index index field of the database table
$liste["table_idx"]  = "mailuser_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]   = "mail_user_list.php";

// Script file of the edit form
$liste["edit_file"]  = "mail_user_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "mail_user_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]   = "yes";


/*****************************************************
* Suchfelder
*****************************************************/

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

$liste["item"][] = array(   'field'     => "login",
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

$liste["item"][] = array(   'field'     => "autoresponder",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op' => "=",
	'prefix' => "",
	'suffix' => "",
	'width' => "",
	'value' => array('y' => $app->lng('yes_txt'), 'n' => $app->lng('no_txt')));

$liste["item"][] = array( 'field'  => "postfix",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('y' => $app->lng('yes_txt'), 'n' => $app->lng('no_txt')));

$liste["item"][] = array( 'field'  => "disablesmtp",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('n' => $app->lng('yes_txt'), 'y' => $app->lng('no_txt')));

$liste["item"][] = array( 'field'  => "disableimap",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('n' => $app->lng('yes_txt'), 'y' => $app->lng('no_txt')));

$liste["item"][] = array( 'field'  => "disablepop3",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('n' => $app->lng('yes_txt'), 'y' => $app->lng('no_txt')));

?>
