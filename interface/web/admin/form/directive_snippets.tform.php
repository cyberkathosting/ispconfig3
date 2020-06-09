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

	Search:
	- searchable = 1 or searchable = 2 include the field in the search
	- searchable = 1: this field will be the title of the search result
	- searchable = 2: this field will be included in the description of the search result


*/

$form["title"]    = "Directive Snippets";
$form["description"]  = "";
$form["name"]    = "directive_snippets";
$form["action"]   = "directive_snippets_edit.php";
$form["db_table"]  = "directive_snippets";
$form["db_table_idx"] = "directive_snippets_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "directive_snippets";
$form["list_default"] = "directive_snippets_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['directive_snippets'] = array (
	'title'  => "Directive Snippets",
	'width'  => 100,
	'template'  => "templates/directive_snippets_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'name' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  
				0 => array ( 'type' => 'NOTEMPTY', 'errmsg'=> 'directive_snippets_name_empty'),
				1 => array ( 'type' => 'CUSTOM', 'class' => 'validate_server_directive_snippets', 'function' => 'validate_snippet'),
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
			'maxlength' => '255',
			'searchable' => 1
		),
		'type' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('apache' => 'Apache', 'nginx' => 'nginx', 'php' => 'PHP', 'proxy' => 'Proxy'),
			'searchable' => 2
		),
		'snippet' => array (
			'datatype' => 'TEXT',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 2
		),
		'customer_viewable' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'active' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'required_php_snippets' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOXARRAY',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => "SELECT directive_snippets_id,name FROM directive_snippets WHERE type = 'php' AND active = 'y' AND master_directive_snippets_id = 0 ORDER BY name",
				'keyfield' => 'directive_snippets_id',
				'valuefield' => 'name'
			),
			'separator' => ',',
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);


?>
