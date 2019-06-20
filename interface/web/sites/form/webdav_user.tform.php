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

$form["title"]    = "Webdav-User";
$form["description"]  = "";
$form["name"]    = "webdav_user";
$form["action"]   = "webdav_user_edit.php";
$form["db_table"]  = "webdav_user";
$form["db_table_idx"] = "webdav_user_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "webdav";
$form["list_default"] = "webdav_user_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['webdav'] = array (
	'title'  => "Webdav User",
	'width'  => 100,
	'template'  => "templates/webdav_user_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE mirror_server_id = 0 AND {AUTHSQL} ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'
			),
			'value'  => ''
		),
		'parent_domain_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'webdav_domains'
			),
			'value'  => ''
		),
		'username' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'UNIQUE',
					'errmsg'=> 'username_error_unique'),
				1 => array ( 'type' => 'REGEX',
					'regex' => '/^[\w\.\-@]{0,64}$/',
					'errmsg'=> 'username_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'username_prefix' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '25'
		),
		'password' => array (
			'datatype' => 'VARCHAR',
			'encryption' => 'CLEARTEXT',
			'validators' => array(
				0 => array(
					'type' => 'CUSTOM',
					'class' => 'validate_password',
					'function' => 'password_check',
					'errmsg' => 'weak_password_txt'
				)
			),
			'formtype' => 'PASSWORD',
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
		'dir' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'directory_error_empty'),
			),
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
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
