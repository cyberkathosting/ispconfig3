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

$form["title"] 			= "Get Email";
$form["description"] 	= "";
$form["name"] 			= "mail_get";
$form["action"]			= "mail_get_edit.php";
$form["db_table"]		= "mail_get";
$form["db_table_idx"]	= "mailget_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "mailget";
$form["list_default"]	= "mail_get_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['mailget'] = array (
	'title' 	=> "Get Email",
	'width' 	=> 100,
	'template' 	=> "templates/mail_get_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'server_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT server_id,server_name FROM server WHERE mirror_server_id = 0 AND {AUTHSQL} ORDER BY server_name',
										'keyfield'=> 'server_id',
										'valuefield'=> 'server_name'
									 ),
			'value'		=> ''
		),
		'type' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'value' => array('pop3' => 'POP3','imap'=>'IMAP','pop3ssl' => 'POP3SSL','imapssl'=>'IMAPSSL')
		),
		'source_server' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'source_server_error_isempty'),
										1 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-]{2,64}\.[a-zA-Z\-]{2,10}$/',
														'errmsg'=> 'source_server_error_regex'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'source_username' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'source_username_error_isempty'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'source_password' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'source_password_error_isempty'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'source_delete' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'y',
			'value'		=> array(0 => 'n',1 => 'y')
		),
		'source_read_all' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'y',
			'value'		=> array(0 => 'n',1 => 'y')
		),
		'destination' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'			=> 'SQL',
										'querystring' 	=> 'SELECT email FROM mail_user WHERE {AUTHSQL} ORDER BY email',
										'keyfield'		=> 'email',
										'valuefield'	=> 'email'
									 ),
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISEMAIL',
														'errmsg'=> 'destination_error_isemail'),
									),
			'value'		=> ''
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