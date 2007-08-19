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
$liste['name'] 				= 'dbsync';

//* Database table
$liste['table'] 			= 'sys_dbsync';

//* Primary index column
$liste['table_idx']			= 'id';

//* Search Field Prefix
$liste['search_prefix'] 	= 'search_';

//* Records per page
$liste['records_per_page'] 	= 15;

//* Script file for listing
$liste['file']				= 'dbsync_list.php';

//* Script file for editing
$liste['edit_file']			= 'dbsync_edit.php';

//* Script file for deleting
$liste['delete_file']		= 'dbsync_del.php';

//* Paging Template
$liste['paging_tpl']		= 'templates/paging.tpl.htm';

//* Enable auth
$liste['auth']				= 'no';


/*****************************************************
* Suchfelder
*****************************************************/

$liste['item'][] = array(	'field'		=> 'jobname',
							'datatype'	=> 'VARCHAR',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '');

$liste['item'][] = array(	'field'		=> 'db_host',
							'datatype'	=> 'VARCHAR',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '');

$liste['item'][] = array(	'field'		=> 'db_name',
							'datatype'	=> 'VARCHAR',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '');  

?>