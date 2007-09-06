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

$form["title"] 			= "Spamfilter Config";
$form["description"] 	= "";
$form["name"] 			= "spamfilter_config";
$form["action"]			= "spamfilter_config_edit.php";
$form["db_table"]		= "server";
$form["db_table_idx"]	= "server_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "mail";
$form["list_default"]	= "spamfilter_config_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['mail'] = array (
	'title' 	=> "Mailserver",
	'width' 	=> 100,
	'template' 	=> "templates/spamfilter_config_mail_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'module' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'value'		=> array('postfix_mysql' => 'postfix_mysql')
		),
		'maildir_path' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '/home/vmail/[domain]/[localpart]/',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'maildir_path_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'homedir_path' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '/home/vmail/',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'homedir_path_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'mailuser_uid' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '5000',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'mailuser_uid_error_empty'),
									),
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '255'
		),
		'mailuser_gid' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '5000',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'mailuser_gid_error_empty'),
									),
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '255'
		),
		'mailuser_name' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> 'vmail',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'mailuser_name_error_empty'),
									),
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '255'
		),
		'mailuser_group' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> 'vmail',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'mailuser_group_error_empty'),
									),
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '255'
		),
		'relayhost' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'relayhost_user' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'relayhost_password' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'mailbox_size_limit' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '0',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '15'
		),
		'message_size_limit' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '0',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '15'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);


?>