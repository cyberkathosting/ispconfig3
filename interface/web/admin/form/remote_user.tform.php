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

$function_list = array();

// Getting the remote function list from other modules
$modules = explode(',', $_SESSION['s']['user']['modules']);
if(is_array($modules)) {
	foreach($modules as $mt) {
		if(is_file(ISPC_WEB_PATH.'/'.$mt.'/lib/remote.conf.php')) {
			include ISPC_WEB_PATH."/$mt/lib/remote.conf.php";
		}
	}
}

$form["title"]    = "remote_user_txt";
$form["description"]  = "";
$form["name"]    = "remote_user";
$form["action"]   = "remote_user_edit.php";
$form["db_table"]  = "remote_user";
$form["db_table_idx"] = "remote_userid";
$form["db_history"]  = "yes";
$form["tab_default"] = "remote_user";
$form["list_default"] = "remote_user_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['remote_user'] = array (
	'title'  => "remote_user_txt",
	'width'  => 100,
	'template'  => "templates/remote_user_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'remote_userid' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT remote_userid,remote_username FROM remote_user WHERE {AUTHSQL} ORDER BY remote_username',
				'keyfield'=> 'remote_userid',
				'valuefield'=> 'remote_username'
			),
			'value'  => ''
		),

		'remote_username' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'UNIQUE',
					'errmsg'=> 'username_error_unique'),
				1 => array ( 'type' => 'REGEX',
					'regex' => '/^[\w\.\-]{0,64}$/',
					'errmsg'=> 'username_error_regex'),
				2 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'username_error_empty'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'remote_password' => array (
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
			'encryption' => 'MD5',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'remote_access' => array (
 			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
        ),
		'remote_ips' => array (
			'datatype'  => 'TEXT',
			'formtype'  => 'TEXT',
			'validators'  => array (  
				0 => array (
					'type' => 'CUSTOM', 
					'class' => 'validate_remote_user', 
					'function' => 'valid_remote_ip', 
					'errmsg' => 'remote_user_error_ips'),
			),
			'default' => '',
			'value'   => '',
			'width'   => '60',
			'searchable' => 2
		),
		'remote_functions' => array (
			'datatype' => 'TEXT',
			'formtype' => 'CHECKBOXARRAY',
			'regex'  => '',
			'errmsg' => '',
			'default' => '',
			'value'  => $function_list,
			'separator' => ';',
			'width'  => '',
			'maxlength' => '',
			'rows'  => '5',
			'cols'  => '30'
		)

		//#################################
		// END Datatable fields
		//#################################
	)
);





?>
