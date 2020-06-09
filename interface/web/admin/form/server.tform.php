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

$form["title"]    = "Server";
$form["description"]  = "";
$form["name"]    = "server";
$form["action"]   = "server_edit.php";
$form["db_table"]  = "server";
$form["db_table_idx"] = "server_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "services";
$form["list_default"] = "server_list.php";
$form["auth"]   = 'yes';

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 1; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['services'] = array (
	'title'  => "Services",
	'width'  => 100,
	'template'  => "templates/server_edit_services.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_name' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'mail_server' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'CHECKBOX',
			'default' => '0',
			'value'  => array(0 => 0, 1 => 1)
		),
		'web_server' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'CHECKBOX',
			'default' => '0',
			'value'  => array(0 => 0, 1 => 1)
		),
		'dns_server' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'CHECKBOX',
			'default' => '0',
			'value'  => array(0 => 0, 1 => 1)
		),
		'file_server' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'CHECKBOX',
			'default' => '0',
			'value'  => array(0 => 0, 1 => 1)
		),
		'db_server' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'CHECKBOX',
			'default' => '0',
			'value'  => array(0 => 0, 1 => 1)
		),
		'vserver_server' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'CHECKBOX',
			'default' => '0',
			'value'  => array(0 => 0, 1 => 1)
		),
		'xmpp_server' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'CHECKBOX',
			'default' => '0',
			'value'  => array(0 => 0, 1 => 1)
		),
		'mirror_server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => ''
		),
		/*
		'update' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '1',
			'value'		=> array(0 => 'No', 1 => 'Yes')
		),
		*/
		'active' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '1',
			'value'  => array(0 => 'No', 1 => 'Yes')
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);

/*
$form["tabs"]['config'] = array (
	'title' 	=> "Config",
	'width' 	=> 100,
	'template' 	=> "templates/server_edit_config.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'config' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'TEXTAREA',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '',
			'cols'		=> '40',
			'rows'		=> '20',
			'maxlength'	=> ''
		),
	##################################
	# END Datatable fields
	##################################
	)
);
*/

?>
