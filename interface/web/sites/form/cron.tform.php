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

$form["title"]    = "Cron Job";
$form["description"]  = "";
$form["name"]    = "cron";
$form["action"]   = "cron_edit.php";
$form["db_table"]  = "cron";
$form["db_table_idx"] = "id";
$form["db_history"]  = "yes";
$form["tab_default"] = "cron";
$form["list_default"] = "cron_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['cron'] = array (
	'title'  => "Cron Job",
	'width'  => 100,
	'template'  => "templates/cron_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE mirror_server_id = 0 AND web_server = 1 AND {AUTHSQL} ORDER BY server_name',
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
		'parent_domain_id' => array (
			'datatype'  => 'INTEGER',
			'formtype'  => 'SELECT',
			'default'   => '',
			'datasource'    => array (  'type'  => 'SQL',
				'querystring' => "SELECT web_domain.domain_id, CONCAT(web_domain.domain, ' :: ', server.server_name) AS parent_domain FROM web_domain, server WHERE web_domain.type = 'vhost' AND web_domain.server_id = server.server_id AND {AUTHSQL::web_domain} ORDER BY web_domain.domain",
				'keyfield'=> 'domain_id',
				'valuefield'=> 'parent_domain'
			),
			'value'     => ''
		),
		'run_min' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'validators'    => array (  0 => array (    'type'  => 'CUSTOM',
					'class' => 'validate_cron',
					'function' => 'run_time_format',
					'errmsg'=> 'run_min_error_format'),
			),
			'default'   => '',
			'value'     => '',
			'width'     => '30',
			'maxlength' => '255'
		),
		'run_hour' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'validators'    => array (  0 => array (    'type'  => 'CUSTOM',
					'class' => 'validate_cron',
					'function' => 'run_time_format',
					'errmsg'=> 'run_hour_error_format'),
			),
			'default'   => '',
			'value'     => '',
			'width'     => '30',
			'maxlength' => '255'
		),
		'run_mday' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'validators'    => array (  0 => array (    'type'  => 'CUSTOM',
					'class' => 'validate_cron',
					'function' => 'run_time_format',
					'errmsg'=> 'run_mday_error_format'),
			),
			'default'   => '',
			'value'     => '',
			'width'     => '30',
			'maxlength' => '255'
		),
		'run_month' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'validators'    => array (  0 => array (    'type'  => 'CUSTOM',
					'class' => 'validate_cron',
					'function' => 'run_month_format',
					'errmsg'=> 'run_month_error_format'),
			),
			'default'   => '',
			'value'     => '',
			'width'     => '30',
			'maxlength' => '255'
		),
		'run_wday' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'validators'    => array (  0 => array (    'type'  => 'CUSTOM',
					'class' => 'validate_cron',
					'function' => 'run_time_format',
					'errmsg'=> 'run_wday_error_format'),
			),
			'default'   => '',
			'value'     => '',
			'width'     => '30',
			'maxlength' => '255'
		),
		'command' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'command_error_empty'),
				1 => array (    'type'  => 'CUSTOM',
					'class' => 'validate_cron',
					'function' => 'command_format',
					'errmsg'=> 'command_error_format'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'type' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'SELECT',
			'default'   => 'url',
			'valuelimit' => 'list:url,full,chrooted',
			'value'     => array('url' => 'Url', 'full' => 'Full', 'chrooted' => 'Chrooted')
		),
		'log' => array (
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
		//#################################
		// END Datatable fields
		//#################################
	)
);



?>
