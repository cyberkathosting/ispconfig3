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

$form["title"]    = "Shell User";
$form["description"]  = "";
$form["name"]    = "shell_user";
$form["action"]   = "shell_user_edit.php";
$form["db_table"]  = "shell_user";
$form["db_table_idx"] = "shell_user_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "shell";
$form["list_default"] = "shell_user_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['shell'] = array (
	'title'  => "Shell User",
	'width'  => 100,
	'template'  => "templates/shell_user_edit.htm",
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
			'datasource' => array (  'type' => 'SQL',
				'querystring' => "SELECT web_domain.domain_id, CONCAT(web_domain.domain, ' :: ', server.server_name) AS parent_domain FROM web_domain, server WHERE web_domain.type = 'vhost' AND web_domain.server_id = server.server_id AND {AUTHSQL::web_domain} ORDER BY web_domain.domain",
				'keyfield'=> 'domain_id',
				'valuefield'=> 'parent_domain'
			),
			'value'  => ''
		),
		'username' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'UNIQUE',
					'errmsg'=> 'username_error_unique'),
				1 => array ( 'type' => 'REGEX',
					'regex' => '/^[\w\.\-]{0,32}$/',
					'errmsg'=> 'username_error_regex'),
				2 => array(
							'type' => 'CUSTOM',
							'class' => 'validate_systemuser',
							'function' => 'check_sysuser',
							'check_names' => false,
							'errmsg' => 'invalid_username_txt'
						),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 1
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
			'formtype' => 'PASSWORD',
			'validators' => array(
				0 => array(
					'type' => 'CUSTOM',
					'class' => 'validate_password',
					'function' => 'password_check',
					'errmsg' => 'weak_password_txt'
				)
			),
			'encryption' => 'CRYPT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'chroot' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'valuelimit' => 'client:ssh_chroot',
			'value'  => array('no' => 'None', 'jailkit' => 'Jailkit')
		),
		'quota_size' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'quota_size_error_empty'),
			),
			'default' => '-1',
			'value'  => '',
			'width'  => '7',
			'maxlength' => '7'
		),
		'active' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'ssh_rsa' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'maxlength' => '600'
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);

if($_SESSION["s"]["user"]["typ"] == 'admin') {

	$form["tabs"]['advanced'] = array (
		'title'  => "Options",
		'width'  => 100,
		'template'  => "templates/shell_user_advanced.htm",
		'fields'  => array (
			//#################################
			// Begin Datatable fields
			//#################################
			'puser' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'uid_error_empty'),
						1 => array(
							'type' => 'CUSTOM',
							'class' => 'validate_systemuser',
							'function' => 'check_sysuser',
							'check_names' => true,
							'errmsg' => 'invalid_system_user_or_group_txt'
						),
				),
				'default' => '0',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'pgroup' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'uid_error_empty'),
						1 => array(
							'type' => 'CUSTOM',
							'class' => 'validate_systemuser',
							'function' => 'check_sysgroup',
							'check_names' => true,
							'errmsg' => 'invalid_system_user_or_group_txt'
						),
				),
				'default' => '0',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'shell' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'TEXT',
				'validators' => array ( 0 => array ( 	'type' => 'NOTEMPTY',
														'errmsg'=> 'shell_error_empty'),
										1 => array ( 	'type' => 'REGEX',
															'regex' => '/^\/[a-zA-Z0-9\/]{5,20}$/',
															'errmsg'=> 'shell_error_regex'),
				),
				'default' => '/bin/bash',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'dir' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'TEXT',
				'validators' => array ( 0 => array ( 	'type' => 'NOTEMPTY',
														'errmsg'=> 'directory_error_empty'),
										1 => array ( 	'type' => 'REGEX',
															'regex' => '/^\/[a-zA-Z0-9\ \.\-\_\/]{10,128}$/',
															'errmsg'=> 'directory_error_regex'),
										2 => array (    'type'  => 'CUSTOM',
														'class' => 'validate_systemuser',
														'function' => 'shelluser_dir',
														'errmsg' => 'directory_error_notinweb'),
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

}


?>
