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

//* Name of the list
$liste['name']                  = 'users';

//* Database table
$liste['table']   = 'sys_user';

//* Primary index column
$liste['table_idx']  = 'userid';

//* Search column prefix
$liste['search_prefix']  = 'search_';

//* Records per page
$liste['records_per_page']  = "15";

//* Script file for list
$liste['file']   = 'users_list.php';

//* Script file to edit
$liste['edit_file']  = 'users_edit.php';

//* Script file to delete
$liste['delete_file']  = 'users_del.php';

//* Paging template
$liste['paging_tpl']  = 'templates/paging.tpl.htm';

//* Enable auth
$liste['auth']   = 'no';


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(   'field'     => "client_id",
	'datatype' => "INTEGER",
	'formtype' => "TEXT",
	'op' => "=",
	'prefix' => "",
	'suffix' => "",
	'width' => "",
	'value' => "");

$liste["item"][] = array(   'field' => "active",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op' => "=",
	'prefix' => "",
	'suffix' => "",
	'width' => "",
	'value' => array('1' => $app->lng('yes_txt'), '0' => $app->lng('no_txt')));

$liste['item'][] = array(   'field' => 'username',
	'datatype' => 'VARCHAR',
	'op' => 'like',
	'prefix' => '%',
	'suffix' => '%',
	'width' => '');

$liste['item'][] = array(   'field' => 'groups',
	'datatype' => 'VARCHAR',
	'op' => 'like',
	'prefix' => '%',
	'suffix' => '%',
	'width' => '');

?>
