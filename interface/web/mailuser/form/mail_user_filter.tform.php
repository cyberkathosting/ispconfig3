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

global $app;

$form["title"]    = "mailbox_filter_txt";
$form["description"]            = "";
$form["name"]    = "mail_user_filter";
$form["action"]   = "mail_user_filter_edit.php";
$form["db_table"]  = "mail_user_filter";
$form["db_table_idx"]           = "filter_id";
$form["db_history"]  = "no";
$form["tab_default"]            = "filter";
$form["list_default"]           = "mail_user_filter_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['filter'] = array (
	'title'  => "Filter",
	'width'  => 100,
	'template'  => "templates/mail_user_filter_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'mailuser_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => @$app->functions->intval($_REQUEST["mailuser_id"]),
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'rulename' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'rulename_error_empty'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'source' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('Subject' => 'subject_txt', 'From'=>'from_txt', 'To'=>'to_txt', 'List-Id'=>'list_id_txt')
		),
		'op' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			//'value'  => array('contains'=>'contains_txt','is' => 'Is','begins'=>'Begins with','ends'=>'Ends with')
			'value'  => array('contains'=>'contains_txt', 'is' => 'is_txt', 'begins'=>'begins_with_txt', 'ends'=>'ends_with_txt')
		),
		'searchterm' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'searchterm_is_empty'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'action' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('move' => 'move_to_txt', 'delete'=>'delete_txt')
		),
		'target' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9\.\-\_\ ]{0,100}$/',
					'errmsg'=> 'target_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'active' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);



?>
