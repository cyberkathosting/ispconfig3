<?php

/*
	Form Definition

	Tabledefinition

	Datatypes:
	- INTEGER (Forces the input to Int)
	- DOUBLE
	- CURRENCY (Formats the values to currency notation)
	- VARCHAR (no format check, maxlength: 255)
	- TEXT (no format check)
	- DATE (Dateformat, automatic conversion to timestamps)

	Formtype:
	- TEXT (Textfield)
	- TEXTAREA (Textarea)
	- PASSWORD (Password textfield, input is not shown when edited)
	- SELECT (Select option field)
	- RADIO
	- CHECKBOX
	- CHECKBOXARRAY
	- FILE

	VALUE:
	- Wert oder Array

	Hint:
	The ID field of the database table is not part of the datafield definition.
	The ID field must be always auto incement (int or bigint).


*/

$form["title"] 			= "Domain";
$form["description"] 	= "";
$form["name"] 			= "domain";
$form["action"]			= "domain_edit.php";
$form["db_table"]		= "domain";
$form["db_table_idx"]	= "domain_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "domain";
$form["list_default"]	= "domain_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

// Clients may not change the website basic settings if they are not resellers
if($app->auth->has_clients($_SESSION['s']['user']['userid']) || $app->auth->is_admin()) {
	$domain_edit_readonly = false;
} else {
	$domain_edit_readonly = true;
}


$form["tabs"]['domain'] = array (
	'title' 	=> "Domain",
	'width' 	=> 100,
	'template' 	=> "templates/domain_edit.htm",
	'readonly'	=> $domain_edit_readonly,
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'domain' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'domain_error_empty'),
										1 => array (	'type'	=> 'UNIQUE',
														'errmsg'=> 'domain_error_unique'),
										2 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-]{2,64}\.[a-zA-Z]{2,10}$/',
														'errmsg'=> 'domain_error_regex'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'tld' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT tld FROM domain_tld ORDER BY tld",
										'keyfield'=> 'tld',
										'valuefield'=> 'tld'
									 ),
			'value'		=> ''
		),
		'domain_provider_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT domain_provider_id, provider FROM domain_provider ORDER BY domain_provider_id",
										'keyfield'=> 'domain_provider_id',
										'valuefield'=> 'provider'
									 ),
			'value'		=> ''
		),
		'handle_desc' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT handle FROM domain_handle ORDER BY handle",
										'keyfield'=> 'handle',
										'valuefield'=> 'handle'
									 ),
			'value'		=> ''
		),
		'handle_admin_c' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT handle FROM domain_handle ORDER BY handle",
										'keyfield'=> 'handle',
										'valuefield'=> 'handle'
									 ),
			'value'		=> ''
		),
		'handle_tech_c' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT handle FROM domain_handle ORDER BY handle",
										'keyfield'=> 'handle',
										'valuefield'=> 'handle'
									 ),
			'value'		=> ''
		),
		'handle_zone_c' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT handle FROM domain_handle ORDER BY handle",
										'keyfield'=> 'handle',
										'valuefield'=> 'handle'
									 ),
			'value'		=> ''
		),
		'status' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> 'y',
			'value'		=> array('connect' => 'connect', 'failed' => 'failed', 'free' => 'free', 'invalid' => 'invalid')
		),

	##################################
	# ENDE Datatable fields
	##################################
	)
);

?>