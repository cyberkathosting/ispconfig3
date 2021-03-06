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
$liste['name'] 				= 'server_ip';

//* Database table
$liste['table'] 			= 'server_ip';

//* Primary index column
$liste['table_idx']			= 'server_ip_id';

//* Search Field Prefix
$liste['search_prefix'] 	= 'search_';

//* Records per page
$liste['records_per_page'] 	= "15";

//* Script file for listing
$liste['file']				= 'server_ip_list.php';

//* Script file to edit
$liste['edit_file']			= 'server_ip_edit.php';

//* Script file to delete
$liste['delete_file']		= 'server_ip_del.php';

//* Paging template
$liste['paging_tpl']		= 'templates/paging.tpl.htm';

//* Enable auth
$liste['auth']				= 'no';


/*****************************************************
* Suchfelder
*****************************************************/

$liste['item'][] = array(	'field'		=> 'server_id',
							'datatype'	=> 'VARCHAR',
							'formtype'	=> 'SELECT',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} ORDER BY server_name',
														'keyfield'=> 'server_id',
														'valuefield'=> 'server_name'
									 				  ),
							'width'		=> '',
							'value'		=> '');

$liste['item'][] = array(	'field'		=> 'client_id',
							'datatype'	=> 'VARCHAR',
							'formtype'	=> 'SELECT',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT client_id,contact_name FROM client WHERE {AUTHSQL} ORDER BY contact_name',
														'keyfield'=> 'client_id',
														'valuefield'=> 'contact_name'
									 				  ),
							'width'		=> '',
							'value'		=> '');

$liste['item'][] = array(	'field'		=> 'ip_type',
							'datatype'	=> 'VARCHAR',
							'formtype'	=> 'SELECT',
							'op'		=> '=',
							'prefix'	=> '',
							'suffix'	=> '',
							'width'		=> '',
							'value'		=> array('IPv4' => 'IPv4', 'IPv6' => 'IPv6'));

$liste['item'][] = array(	'field'		=> 'ip_address',
							'datatype'	=> 'VARCHAR',
							'op'		=> '=',
							'prefix'	=> '',
							'suffix'	=> '',
							'width'		=> '');  

$liste["item"][] = array(	'field'		=> "virtualhost",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'width'		=> "",
							'value'		=> array('y' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'n' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));


$liste['item'][] = array(	'field'		=> 'virtualhost_port',
							'datatype'	=> 'VARCHAR',
							'op'		=> '=',
							'prefix'	=> '',
							'suffix'	=> '',
							'width'		=> ''); 

?>