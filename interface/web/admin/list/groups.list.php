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

//* Name of list
$liste['name']     = 'groups';

//* Database table
$liste['table']    = 'sys_group';

//* Primary index column
$liste['table_idx']   = 'groupid';

//* Search Field Prefix
$liste['search_prefix']  = 'search_';

//* Records per page
$liste['records_per_page']  = "15";

//* Script file for listing
$liste['file']    = 'groups_list.php';

//* Script file to edit
$liste['edit_file']   = 'groups_edit.php';

//* Script file for deleting
$liste['delete_file']  = 'groups_del.php';

//* Paging Template
$liste['paging_tpl']  = 'templates/paging.tpl.htm';

//* Enable auth
$liste['auth']    = 'no';


/*****************************************************
* Suchfelder
*****************************************************/

$liste['item'][] = array( 'field'  => 'name',
	'datatype' => 'VARCHAR',
	'op'  => 'like',
	'prefix' => '%',
	'suffix' => '%',
	'width'  => '');

$liste['item'][] = array( 'field'  => 'description',
	'datatype' => 'VARCHAR',
	'op'  => 'like',
	'prefix' => '%',
	'suffix' => '%',
	'width'  => '');

?>
