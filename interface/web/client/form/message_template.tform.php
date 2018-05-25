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

$form["title"]    = "Email template";
$form["description"]  = "";
$form["name"]    = "client_message_template";
$form["action"]   = "message_template_edit.php";
$form["db_table"]  = "client_message_template";
$form["db_table_idx"] = "client_message_template_id";
$form["db_history"]  = "no";
$form["tab_default"] = "template";
$form["list_default"] = "message_template_list.php";
$form["auth"]   = 'yes';

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['template'] = array (
	'title'  => "Settings",
	'width'  => 100,
	'template'  => "templates/message_template.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'template_type' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('welcome' => 'Default welcome email', 'gdpr' => 'GDPR data send', 'other' => 'Other')
		),
		'template_name' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => '',
			'separator' => '',
			'width'  => '30',
			'maxlength' => '255',
			'rows'  => '',
			'cols'  => ''
		),
		'subject' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'NOTEMPTY',
				'errmsg'=> 'subject_error_empty'),
			),
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => '',
			'separator' => '',
			'width'  => '30',
			'maxlength' => '255',
			'rows'  => '',
			'cols'  => ''
		),
		'message' => array (
			'datatype' => 'TEXT',
			'formtype' => 'TEXTAREA',
			'validators' => array ( 0 => array ( 'type' => 'NOTEMPTY',
				'errmsg'=> 'message_error_empty'),
			),
			'default' => '',
			'value'  => '',
			'separator' => '',
			'width'  => '30',
			'maxlength' => '255',
			'rows'  => '',
			'cols'  => ''
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);



?>
