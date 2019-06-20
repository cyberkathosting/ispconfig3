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

$form["title"]    = "Database";
$form["description"]  = "";
$form["name"]    = "database";
$form["action"]   = "database_edit.php";
$form["db_table"]  = "web_database";
$form["db_table_idx"] = "database_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "database";
$form["list_default"] = "database_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['database'] = array (
	'title'  => "Database",
	'width'  => 100,
	'template'  => "templates/database_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE mirror_server_id = 0 AND {AUTHSQL} AND db_server = 1 ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'
			),
			'value'  => ''
		),
		'parent_domain_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => "SELECT web_domain.domain_id, CONCAT(web_domain.domain, ' :: ', server.server_name) AS parent_domain FROM web_domain, server WHERE web_domain.type = 'vhost' AND web_domain.server_id = server.server_id AND {AUTHSQL::web_domain} ORDER BY web_domain.domain",
				'keyfield'=> 'domain_id',
				'valuefield'=> 'parent_domain'
			),
			'value'  => array('0' => 'select_site_txt')
		),
		'type' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'mysql',
			'value'  => array(
				/*'mongo' => 'MongoDB',*/
				'mysql' => 'MySQL'
			)
		),
		'database_name' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'database_name_error_empty'),
				1 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9_]{2,64}$/',
					'errmsg'=> 'database_name_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 1
		),
		'database_name_prefix' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '25'
		),
		'database_quota' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
				'errmsg'=> 'limit_database_quota_error_notint'),
			),
			'default' => '-1',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'database_user_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => "SELECT database_user_id,database_user FROM web_database_user WHERE {AUTHSQL} ORDER BY database_user",
				'keyfield'=> 'database_user_id',
				'valuefield'=> 'database_user'
			),
			'value'  => array('0' => 'select_dbuser_txt')
		),
		'database_ro_user_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => "SELECT database_user_id,database_user FROM web_database_user WHERE {AUTHSQL} ORDER BY database_user",
				'keyfield'=> 'database_user_id',
				'valuefield'=> 'database_user'
			),
			'value'  => array('0' => 'no_dbuser_txt')
		),
		'database_charset' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('' => 'DB-Default', 'latin1' => 'Latin 1', 'utf8' => 'UTF-8', 'utf8mb4' => 'UTF8MB4')
		),
		'remote_access' => array (
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
		'remote_ips' => array (
			'datatype'  => 'TEXT',
			'formtype'  => 'TEXT',
			'validators'  => array (  0 => array (  'type' => 'CUSTOM',
					'class' => 'validate_database',
					'function' => 'valid_ip_list',
					'errmsg' => 'database_remote_error_ips'),
			),
			'default' => '',
			'value'   => '',
			'width'   => '60',
			'searchable' => 2
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);


?>
