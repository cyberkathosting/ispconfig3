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

$form["title"]    = "Client Circle";
$form["description"]  = "";
$form["name"]    = "client_circle";
$form["action"]   = "client_circle_edit.php";
$form["db_table"]  = "client_circle";
$form["db_table_idx"] = "circle_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "circle";
$form["list_default"] = "client_circle_list.php";
$form["auth"]   = 'yes';

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

//* Languages
$language_list = array();
$handle = @opendir(ISPC_ROOT_PATH.'/lib/lang');
while ($file = @readdir($handle)) {
	if ($file != '.' && $file != '..') {
		if(@is_file(ISPC_ROOT_PATH.'/lib/lang/'.$file) and substr($file, -4, 4) == '.lng') {
			$tmp = substr($file, 0, 2);
			$language_list[$tmp] = $tmp;
		}
	}
}

//* Load themes
$themes_list = array();
$handle = @opendir(ISPC_THEMES_PATH);
while ($file = @readdir($handle)) {
	if (substr($file, 0, 1) != '.') {
		if(@is_dir(ISPC_THEMES_PATH."/$file")) {
			if($file == 'default' || (@file_exists(ISPC_THEMES_PATH."/$file/ISPC_VERSION") && trim(@file_get_contents(ISPC_THEMES_PATH."/$file/ISPC_VERSION")) == ISPC_APP_VERSION)) {
				$themes_list[$file] = $file;
			}
		}
	}
}

$form["tabs"]['circle'] = array (
	'title'  => "Circle",
	'width'  => 100,
	'template'  => "templates/client_circle_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'circle_name' => array (
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
			'cols'  => '',
			'searchable' => 2
		),
		'client_ids' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOXARRAY',
			'default' => '',
			'separator' => ',',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT client_id,contact_name FROM client WHERE {AUTHSQL} ORDER BY contact_name',
				'keyfield'=> 'client_id',
				'valuefield'=> 'contact_name'
			),
			'value'  => ''
		),
		'description' => array (
			'datatype' => 'TEXT',
			'formtype' => 'TEXTAREA',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS')
			),
			'default' => '',
			'value'  => '',
			'separator' => '',
			'width'  => '',
			'maxlength' => '',
			'rows'  => '10',
			'cols'  => '30'
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
