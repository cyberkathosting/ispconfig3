<?php
// Name of the list
$liste["name"]     = "backup_stats";

// Database table
$liste["table"]    = "web_domain";

// Index index field of the database table
$liste["table_idx"]   = "domain_id";

// Search Field Prefix
$liste["search_prefix"]  = "search_";

// Records per page
$liste["records_per_page"]  = "15";

// Script File of the list
$liste["file"]    = "backup_stats.php";

// Script file of the edit form
$liste["edit_file"]   = "backup_stats_edit.php";

// Paging Template
$liste["paging_tpl"]  = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]    = "yes";

// mark columns for php sorting (no real mySQL columns)
$liste["phpsort"] = array('used_sort', 'files');


/*****************************************************
* Suchfelder
*****************************************************/

$liste['item'][] = array (
   	'field'    => 'server_id',
	'datatype' => 'INTEGER',
	'formtype' => 'SELECT',
	'op'       => '=',
	'prefix'   => '',
	'width'    => '',
	'value'    => '',
	'suffix'   => '',
	'datasource' => array (
	  	'type'        => 'SQL',
		'querystring' => 'SELECT a.server_id, a.server_name FROM server a, web_domain b WHERE (a.server_id = b.server_id) ORDER BY a.server_name',
		'keyfield'    => 'server_id',
		'valuefield'  => 'server_name'
	)
);
