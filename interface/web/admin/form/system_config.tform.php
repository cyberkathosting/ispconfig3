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

$form["title"] 			= "System Config";
$form["description"] 	= "";
$form["name"] 			= "system_config";
$form["action"]			= "system_config_edit.php";
$form["db_table"]		= "sys_ini";
$form["db_table_idx"]	= "sysini_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "sites";
$form["list_default"]	= "users_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['sites'] = array (
	'title' 	=> "Sites",
	'width' 	=> 70,
	'template' 	=> "templates/system_config_sites_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'dbname_prefix' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'REGEX',
														'regex' => '/^[a-zA-Z0-0\-\_\[\]]{0,50}$/',
														'errmsg'=> 'dbname_prefix_error_regex'),
										),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'dbuser_prefix' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'REGEX',
														'regex' => '/^[a-zA-Z0-0\-\_\[\]]{0,50}$/',
														'errmsg'=> 'dbuser_prefix_error_regex'),
										),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'ftpuser_prefix' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'REGEX',
														'regex' => '/^[a-zA-Z0-0\-\_\[\]]{0,50}$/',
														'errmsg'=> 'ftpuser_prefix_error_regex'),
										),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'shelluser_prefix' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'REGEX',
														'regex' => '/^[a-zA-Z0-0\-\_\[\]]{0,50}$/',
														'errmsg'=> 'shelluser_prefix_error_regex'),
										),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'dblist_phpmyadmin_link' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'n',
			'value'		=> array(0 => 'n',1 => 'y')
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

$form["tabs"]['mail'] = array (
	'title' 	=> "Mail",
	'width' 	=> 70,
	'template' 	=> "templates/system_config_mail_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'mailboxlist_webmail_link' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'n',
			'value'		=> array(0 => 'n',1 => 'y')
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);



?>