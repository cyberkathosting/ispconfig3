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

$form["title"] 			= "Email Alias";
$form["description"] 	= "";
$form["name"] 			= "mail_alias";
$form["action"]			= "mail_alias_edit.php";
$form["db_table"]		= "mail_redirect";
$form["db_table_idx"]	= "redirect_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "mailbox";
$form["list_default"]	= "mail_box_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['mailbox'] = array (
	'title' 	=> "Mailbox",
	'width' 	=> 100,
	'template' 	=> "templates/mail_box_mailbox_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'server_id' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'email' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISEMAIL',
														'errmsg'=> 'email_error_isemail'),
										1 => array (	'type'	=> 'UNIQUE',
														'errmsg'=> 'email_error_unique'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'cryptpwd' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'PASSWORD',
			'encryption'=> 'CRYPT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'active' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'CHECKBOX',
			'default'	=> '1',
			'value'		=> '1'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

$form["tabs"]['autoresponder'] = array (
	'title' 	=> "Autoresponder",
	'width' 	=> 100,
	'template' 	=> "templates/mail_box_autoresponder_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'autoresponder_text' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'TEXTAREA',
			'default'	=> '',
			'value'		=> '',
			'cols'		=> '30',
			'rows'		=> '15'
		),
		'autoresponder' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'CHECKBOX',
			'default'	=> '1',
			'value'		=> '1'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);


?>