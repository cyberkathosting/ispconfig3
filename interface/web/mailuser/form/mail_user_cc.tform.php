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

$form["title"]    = "mailbox_cc_txt";
$form["description"]            = "";
$form["name"]    = "mail_user_cc";
$form["action"]   = "mail_user_cc_edit.php";
$form["db_table"]  = "mail_user";
$form["db_table_idx"]           = "mailuser_id";
$form["db_history"]  = "yes";
$form["tab_default"]            = "mailuser";
$form["list_default"]           = "index.php";
$form["auth"]   = 'no'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['mailuser'] = array (
	'title'  => "cc_txt",
	'width'  => 100,
	'template'  => "templates/mail_user_cc_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'cc' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'validators' => array (
				0 => array ( 'type' => 'REGEX', 
					'regex' => '/^(\w+[\w\.\-\+]*\w{0,}@\w+[\w.-]*\.[a-z\-]{2,10}){0,1}(,\s*\w+[\w\.\-\+]*\w{0,}@\w+[\w.-]*\.[a-z\-]{2,10}){0,}$/i',
					'errmsg'=> 'cc_error_isemail'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);


?>
