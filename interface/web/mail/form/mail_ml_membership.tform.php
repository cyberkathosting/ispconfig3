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

$app->uses('getconf');
$mlManager = $app->getconf->get_server_config($conf['server_id'], 'mail')['mailinglist_manager'];

$form["title"]    = "Mailing List";
$form["description"]  = "";
$form["name"]    = "mail_ml_membership";
$form["action"]   = "mail_ml_membership_edit.php";
$form["db_table"]  = "mail_ml_membership";
$form["db_table_idx"] = "subscriber_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "membership";
$form["list_default"] = "mail_ml_membership_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['membership'] = array (
	'title'  => "Membership",
	'width'  => 100,
	'template'  => "templates/mail_ml_membership_edit.htm",
	'fields'  => array (
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE mail_server = 1 AND mirror_server_id = 0 AND {AUTHSQL} ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'
			),
			'value' => ''
		),
		'mailinglist_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT mailinglist_id, CONCAT_WS(\'@\', listname, domain) as listname FROM mail_mailinglist WHERE {AUTHSQL} ORDER BY listname',
				'keyfield'=> 'mailinglist_id',
				'valuefield'=> 'listname'
			),
			'value' => ''
		),
		'email' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'validators' => array (  0 => array ( 'type' => 'ISEMAIL',
					'errmsg'=> 'email_error_isemail'),
				1 => array ( 'type' => 'UNIQUE',
					'errmsg'=> 'email_error_unique'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 1
		),
		'name' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 2
		),
		'welcome_msg' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'goodbye_msg' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
	),
);