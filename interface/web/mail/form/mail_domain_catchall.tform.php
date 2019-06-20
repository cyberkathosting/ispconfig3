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

$form["title"]    = "Email Catchall";
$form["description"]  = "";
$form["name"]    = "mail_domain_catchall";
$form["action"]   = "mail_domain_catchall_edit.php";
$form["db_table"]  = "mail_forwarding";
$form["db_table_idx"] = "forwarding_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "catchall";
$form["list_default"] = "mail_domain_catchall_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['catchall'] = array (
	'title'  => "Email Catchall",
	'width'  => 100,
	'template'  => "templates/mail_domain_catchall_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'source' => array (
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
					'errmsg'=> 'domain_error_empty'),
				1 => array ( 'type' => 'UNIQUE',
					'errmsg'=> 'domain_error_unique'),
				2 => array ( 'type' => 'REGEX',
					'regex' => '/^\@[\w\.\-]{2,255}\.[a-zA-Z\-]{2,30}$/',
					'errmsg'=> 'domain_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 1
		),
		'destination' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'default' => '',
			/*
			'datasource'	=> array ( 	'type'			=> 'SQL',
										'querystring' 	=> 'SELECT email FROM mail_user WHERE {AUTHSQL} ORDER BY email',
										'keyfield'		=> 'email',
										'valuefield'	=> 'email'
									 ),
			*/
			'validators' => array (  0 => array ( 'type' => 'ISEMAIL',
					'errmsg'=> 'destination_error_isemail'),
			),
			'value'  => '',
			'searchable' => 2
		),
		'type' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('alias' => 'Alias', 'forward'=>'Forward', 'catchall'=>'Catchall')
		),
		'active' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'greylisting' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(1 => 'y', 0 => 'n')
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);



?>
