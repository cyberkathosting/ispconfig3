<?php
// Name of the list
$liste["name"]     = "backup_stats";

// Database table
//$liste["table"]    = "mail_domain";
$liste["table"]    = "mail_user";

// Index index field of the database table
//$liste["table_idx"]   = "domain_id";
$liste["table_idx"]   = "mailuser_id";

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
$liste["phpsort"] = array('active', 'interval_sort', 'backup_size_sort', 'backup_copies_exists');


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
		'querystring' => 'SELECT a.server_id, a.server_name FROM server a, mail_domain b WHERE (a.server_id = b.server_id) ORDER BY a.server_name',
		'keyfield'    => 'server_id',
		'valuefield'  => 'server_name'
	)
);
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
