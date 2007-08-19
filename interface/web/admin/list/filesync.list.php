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
$liste['name'] 				= 'filesync';

//* Database table
$liste['table'] 			= 'sys_filesync';

//* Primary index column
$liste['table_idx']			= 'id';

//* Search Field Prefix
$liste['search_prefix'] 	= 'search_';

//* Records per page
$liste['records_per_page'] 	= 15;

//* Script file for listing
$liste['file']				= 'filesync_list.php';

//* Script file for editing
$liste['edit_file']			= 'filesync_edit.php';

//* Script file for deleting
$liste['delete_file']		= 'filesync_del.php';

//*  Paging Template
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

$liste['item'][] = array(	'field'		=> 'ftp_host',
							'datatype'	=> 'VARCHAR',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '');

$liste['item'][] = array(	'field'		=> 'local_path',
							'datatype'	=> 'VARCHAR',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '');  

?>