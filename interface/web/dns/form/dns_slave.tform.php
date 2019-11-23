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

$form["title"]    = "Secondary DNS Zone";
$form["description"]  = "";
$form["name"]    = "dns_slave";
$form["action"]   = "dns_slave_edit.php";
$form["db_table"]  = "dns_slave";
$form["db_table_idx"] = "id";
$form["db_history"]  = "yes";
$form["tab_default"] = "dns_slave";
$form["list_default"] = "dns_slave_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['dns_slave'] = array (
	'title'  => "Secondary DNS Zone",
	'width'  => 100,
	'template'  => "templates/dns_slave_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE mirror_server_id = 0 AND dns_server = 1 AND {AUTHSQL} ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'
			),
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'server_id_error_empty'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'origin' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'origin_error_empty'),
				/*
										1 => array (	'type'	=> 'UNIQUE',
														'errmsg'=> 'origin_error_unique'),
										*/
				1 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9\.\-\/]{2,255}\.[a-zA-Z0-9\-]{2,30}[\.]{0,1}$/',
					'errmsg'=> 'origin_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 1
		),
		'ns' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
/*
			'validators' => array (  0 => array ( 'type' => 'REGEX',
					'regex' => '/^[0-9\.]{1,255}$/',
					'errmsg'=> 'ns_error_regex'),
			),
*/
			'validators' => array (  0 => array ( 'type' => 'ISIP',
					'separator' => ',',
					'errmsg'=> 'ns_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 2
		),
		'xfer' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISIP',
					'allowempty' => 'y',
					'separator' => ',',
					'errmsg'=> 'xfer_error_regex'),
			),
			'default'       => '',
			'value'         => '',
			'width'         => '30',
			'maxlength'     => '255'
		),
		'active' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'Y',
			'value'  => array(0 => 'N', 1 => 'Y')
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);


?>
