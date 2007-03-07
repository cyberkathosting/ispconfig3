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

$form["title"] 			= "Email Catchall";
$form["description"] 	= "";
$form["name"] 			= "mail_domain_catchall";
$form["action"]			= "mail_domain_catchall_edit.php";
$form["db_table"]		= "mail_domain_catchall";
$form["db_table_idx"]	= "domain_catchall_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "catchall";
$form["list_default"]	= "mail_domain_catchall_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['catchall'] = array (
	'title' 	=> "Domain Catchall",
	'width' 	=> 150,
	'template' 	=> "templates/mail_domain_catchall_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'server_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'VARCHAR',
			'default'	=> '',
			'value'		=> ''
		),
		'domain' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'UNIQUE',
														'errmsg'=> 'domain_error_unique'),
										1 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-]{2,64}\.[a-zA-Z]{2,10}$/',
														'errmsg'=> 'domain_error_regex'),
									),
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT domain FROM mail_domain WHERE type = 'local' AND {AUTHSQL} ORDER BY domain",
										'keyfield'=> 'domain',
										'valuefield'=> 'domain'
									 ),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'destination' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'active' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'y',
			'value'		=> array(0 => 'n',1 => 'y')
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);


?>