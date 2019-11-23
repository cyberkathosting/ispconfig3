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

$form["title"]    = "Spamfilter users";
$form["description"]  = "";
$form["name"]    = "spamfilter_users";
$form["action"]   = "spamfilter_users_edit.php";
$form["db_table"]  = "spamfilter_users";
$form["db_table_idx"] = "id";
$form["db_history"]  = "yes";
$form["tab_default"] = "users";
$form["list_default"] = "spamfilter_users_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['users'] = array (
	'title'  => "Users",
	'width'  => 100,
	'template'  => "templates/spamfilter_users_edit.htm",
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
		'priority' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => 5,
			'value'  => array(1 => '1 - lowest', 2 => 2, 3 => 3, 4 => 4, 5 => '5 - medium', 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => '10 - highest')
		),
		'policy_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '5',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT id,policy_name FROM spamfilter_policy WHERE {AUTHSQL} ORDER BY policy_name',
				'keyfield'=> 'id',
				'valuefield'=> 'policy_name'
			),
			'value'  => ''
		),
		'email' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					2 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL'),
					3 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
					4 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8')
			),
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'email_error_notempty'),
			),
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'fullname' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'fullname_error_notempty'),
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
		'local' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'Y',
			'value'  => array('Y' => 'Yes', 'N' => 'No')
		),

		//#################################
		// END Datatable fields
		//#################################
	)
);


?>
