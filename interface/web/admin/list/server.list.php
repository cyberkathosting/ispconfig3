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
$liste['name'] 				= 'server';

//* Database table
$liste['table'] 			= 'server';

//* Index index field of the database table
$liste['table_idx']			= 'server_id';

//* Search Field Prefix
$liste['search_prefix'] 	= 'search_';

//* Records per page
$liste['records_per_page'] 	= "15";

//* Script File of the list
$liste['file']				= 'server_list.php';

//* Script file of the edit form
$liste['edit_file']			= 'server_edit.php';

//* Script File of the delete script
$liste['delete_file']		= 'server_del.php';

//* Paging Template
$liste['paging_tpl']		= 'templates/paging.tpl.htm';

//* Enable auth
$liste['auth']				= 'yes';


/*****************************************************
* Suchfelder
*****************************************************/

$liste['item'][] = array(	'field'		=> 'server_name',
							'datatype'	=> 'VARCHAR',
                            'filters'   => array( 0 => array( 'event' => 'SHOW',
                                                              'type' => 'IDNTOUTF8')
                                                ),
							'formtype'	=> 'TEXT',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '',
							'value'		=> '');

$liste['item'][] = array(	'field'		=> 'mail_server',
							'datatype'	=> 'VARCHAR',
							'formtype'	=> 'TEXT',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '',
							'value'		=> array('1' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'0' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));

$liste['item'][] = array(	'field'		=> 'web_server',
							'datatype'	=> 'VARCHAR',
							'formtype'	=> 'TEXT',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '',
							'value'		=> array('1' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'0' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));

$liste['item'][] = array(	'field'		=> 'dns_server',
							'datatype'	=> 'VARCHAR',
							'formtype'	=> 'TEXT',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '',
							'value'		=> array('1' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'0' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));

$liste['item'][] = array(	'field'		=> 'file_server',
							'datatype'	=> 'VARCHAR',
							'formtype'	=> 'TEXT',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '',
							'value'		=> array('1' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'0' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));

$liste['item'][] = array(	'field'		=> 'db_server',
							'datatype'	=> 'VARCHAR',
							'formtype'	=> 'TEXT',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '',
							'value'		=> array('1' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'0' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));

$liste['item'][] = array(	'field'		=> 'vserver_server',
							'datatype'	=> 'VARCHAR',
							'formtype'	=> 'TEXT',
							'op'		=> 'like',
							'prefix'	=> '%',
							'suffix'	=> '%',
							'width'		=> '',
							'value'		=> array('1' => "<div id=\"ir-Yes\" class=\"swap\"><span>Yes</span></div>",'0' => "<div class=\"swap\" id=\"ir-No\"><span>No</span></div>"));

?>