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

$lista_funkcji = array();
$lista_funkcji['mail_domain_get,mail_domain_add,mail_domain_update,mail_domain_delete'] = 'Mail domain functions<br>';

$lista_funkcji['mail_user_add,mail_user_update,mail_user_delete'] = 'Mail user functions<br>';

$lista_funkcji['mail_alias_add,mail_alias_update,mail_alias_delete'] = 'Mail alias functions<br>';

$lista_funkcji['mail_forward_add,mail_forward_update,mail_forward_delete'] = 'Mail forward functions<br>';

$lista_funkcji['mail_catchall_add,mail_catchall_update,mail_catchall_delete'] = 'Mail catchall functions<br>';

$lista_funkcji['mail_transport_add,mail_transport_update,mail_transport_delete'] = 'Mail transport functions<br>';

$lista_funkcji['mail_whitelist_add,mail_whitelist_update,mail_whitelist_delete'] = 'Mail whitelist functions<br>';

$lista_funkcji['mail_blacklist_add,mail_blacklist_update,mail_blacklist_delete'] = 'Mail blacklist functions<br>';

$lista_funkcji['mail_spamfilter_user_add,mail_spamfilter_user_update,mail_spamfilter_user_delete'] = 'Mail spamfilter user functions<br>';

$lista_funkcji['mail_policy_add,mail_policy_update,mail_policy_delete'] = 'Mail spamfilter policy functions<br>';

$lista_funkcji['mail_fetchmail_add,mail_fetchmail_update,mail_fetchmail_delete'] = 'Mail fetchmail functions<br>';

$lista_funkcji['mail_whitelist_add,mail_whitelist_update,mail_whitelist_delete'] = 'Mail whitelist functions<br>';

$lista_funkcji['mail_blacklist_add,mail_blacklist_update,mail_blacklist_delete'] = 'Mail blacklist functions<br>';

$lista_funkcji['mail_filter_add,mail_filter_update,mail_filter_delete'] = 'Mail filter functions<br>';



$form["title"] 			= "Remote user";
$form["description"] 	= "";
$form["name"] 			= "remote_user";
$form["action"]			= "remote_user_edit.php";
$form["db_table"]		= "remote_user";
$form["db_table_idx"]	= "remote_userid";
$form["db_history"]		= "yes";
$form["tab_default"]	= "remote_user";
$form["list_default"]	= "remote_user_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['remote_user'] = array (
	'title' 	=> "Remote User",
	'width' 	=> 100,
	'template' 	=> "templates/remote_user_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'remote_userid' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT remote_userid,remote_username FROM remote_user WHERE {AUTHSQL} ORDER BY remote_username',
										'keyfield'=> 'remote_userid',
										'valuefield'=> 'remote_username'
									 ),
			'value'		=> ''
		),
		
		'remote_username' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'UNIQUE',
														'errmsg'=> 'username_error_unique'),
										1 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-]{0,64}$/',
														'errmsg'=> 'username_error_regex'),
2 => array (	'type' => 'NOTEMPTY',
		'errmsg'=> 'username_error_empty'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'remote_password' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'PASSWORD',
			'encryption' => 'MD5',
			'validators'    => array ( 0 => array (    'type' => 'NOTEMPTY',
                'errmsg'=> 'password_error_empty'),
                                                                        ),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'remote_functions' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'CHECKBOXARRAY',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> $lista_funkcji,
			'separator'	=> ';',
			'width'		=> '',
			'maxlength'	=> '',
			'rows'		=> '5',
			'cols'		=> '30'
		)
		
	##################################
	# ENDE Datatable fields
	##################################
	)
);





?>
