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
$liste["name"]    = "xmpp_user";

// Database table
$liste["table"]   = "xmpp_user";

// Index index field of the database table
$liste["table_idx"]  = "xmppuser_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]   = "xmpp_user_list.php";

// Script file of the edit form
$liste["edit_file"]  = "xmpp_user_edit.php";

// Script File of the delete script
$liste["delete_file"]  = "xmpp_user_del.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]   = "yes";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(   'field'     => "JID",
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

?>
