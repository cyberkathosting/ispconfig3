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

$form["title"]    = "Email relay recipient";
$form["description"]  = "";
$form["name"]    = "mail_relay_recipient";
$form["action"]   = "mail_relay_recipient_edit.php";
$form["db_table"]  = "mail_relay_recipient";
$form["db_table_idx"] = "relay_recipient_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "relay_recipient";
$form["list_default"] = "mail_relay_recipient_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['relay_recipient'] = array (
	'title'  => "Relay recipient",
	'width'  => 100,
	'template'  => "templates/mail_relay_recipient_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE mail_server = 1 AND mirror_server_id = 0 AND {AUTHSQL} ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'
			),
			'value'  => ''
		),
		'source' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'source_error_notempty'),
			),
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'access' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => 'OK',
			'value'  => 'OK',
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
