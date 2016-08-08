<?php

// Name of the list
$liste["name"]     = "database_quota_stats";

// Database table
$liste["table"]    = "web_database";

// Index index field of the database table
$liste["table_idx"]   = "database_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "database_quota_stats.php";

// Script file of the edit form
$liste["edit_file"]   = "database_edit.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]    = "yes";

// mark columns for php sorting (no real mySQL columns)
$liste["phpsort"] = array('server_name', 'client', 'used', 'quota', 'percentage');


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array( 'field'  => "database_name",
	'datatype' => "VARCHAR",
	'filters'   => array( 0 => array( 'event' => 'SHOW',
			'type' => 'IDNTOUTF8')
	),
	'formtype' => "TEXT",
	'op'  => "like",
	'prefix' => "%",
	'suffix' => "%",
	'width'  => "",
	'value'  => ""
);

?>
