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
global $app;
$app->uses('getconf');
$global_config = $app->getconf->get_global_config();

$form["title"]    = "Mailbox";
$form["description"]  = "";
$form["name"]    = "mail_user";
$form["action"]   = "mail_user_edit.php";
$form["db_table"]  = "mail_user";
$form["db_table_idx"] = "mailuser_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "mailuser";
$form["list_default"] = "mail_user_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['mailuser'] = array (
	'title'  => "Mailbox",
	'width'  => 100,
	'template'  => "templates/mail_user_mailbox_edit.htm",
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
		'login' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'validators'  => array (
				0 => array (  'type'  => 'UNIQUE',
					'errmsg'=> 'login_error_unique'),
				1 => array (  'type'  => 'REGEX',
					'regex' => '/^[a-z0-9][\w\.\-_\+@]{1,63}$/',
					'errmsg'=> 'login_error_regex'),
			),
			'default' => '',
			'value'   => '',
			'width'   => '30',
			'maxlength' => '255'
		),
		'password' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'PASSWORD',
			'encryption'=> 'CRYPT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
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
		'quota' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  1 => array ( 'type' => 'ISINT',
					'errmsg'=> 'quota_error_isint'),
				0 => array ( 'type' => 'REGEX',
					'regex' => '/^([0-9]{1,})$/',
					'errmsg'=> 'quota_error_value'),
			),
			'default' => '-1',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'cc' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'validators' => array (  0 => array ( 'type' => 'REGEX',
					'regex' => '/^(\w+[\w\.\-\+]*\w{0,}@\w+[\w.-]*\.[a-z\-]{2,10}){0,1}$/i',
					'errmsg'=> 'cc_error_isemail'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'maildir' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'homedir' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'uid' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '10'
		),
		'gid' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '10'
		),
		'postfix' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(1 => 'y', 0 => 'n')
		),
		/*
		'access' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'y',
			'value'		=> array(1 => 'y',0 => 'n')
		),
		*/
		'disableimap' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(1 => 'y', 0 => 'n')
		),
		'disablepop3' => array (
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

if ($global_config['mail']['mailbox_show_autoresponder_tab'] === 'y') {
	$form["tabs"]['autoresponder'] = array (
		'title'  => "Autoresponder",
		'width'  => 100,
		'template'  => "templates/mail_user_autoresponder_edit.htm",
		'fields'  => array (
			//#################################
			// Begin Datatable fields
			//#################################
			'autoresponder_subject' => array (
				'datatype'  => 'VARCHAR',
				'formtype'  => 'TEXT',
				'default'   => 'Out of office reply',
				'value'     => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'autoresponder_text' => array (
				'datatype' => 'TEXT',
				'formtype' => 'TEXTAREA',
				'default' => '',
				'value'  => '',
				'cols'  => '30',
				'rows'  => '15'
			),
			'autoresponder' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'CHECKBOX',
				'default' => 'n',
				'value'  => array(1 => 'y', 0 => 'n')
			),
			'autoresponder_start_date' => array (
				'datatype' => 'DATETIME',
				'formtype' => 'DATETIME',
				'validators'=> array ( 0 => array ( 'type' => 'CUSTOM',
						'class' => 'validate_autoresponder',
						'function' => 'start_date',
						'errmsg'=> 'autoresponder_start_date_is_required'),
				)
			),
			'autoresponder_end_date' => array (
				'datatype' => 'DATETIME',
				'formtype' => 'DATETIME',
				'validators'=> array (  0 => array ( 'type' => 'CUSTOM',
						'class' => 'validate_autoresponder',
						'function' => 'end_date',
						'errmsg'=> 'autoresponder_end_date_isgreater'),
				),
			),
			//#################################
			// END Datatable fields
			//#################################
		)
	);
}


if ($global_config['mail']['mailbox_show_mail_filter_tab'] === 'y') {
	$form["tabs"]['filter_records'] = array (
		'title'  => "Mail Filter",
		'width'  => 100,
		'template'  => "templates/mail_user_mailfilter_edit.htm",
		'fields'  => array (
			//#################################
			// Begin Datatable fields
			//#################################
			'move_junk' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'CHECKBOX',
				'default' => 'n',
				'value'  => array(0 => 'n', 1 => 'y')
			),
			//#################################
			// END Datatable fields
			//#################################
		),
		'plugins' => array (
			'filter_records' => array (
				'class'   => 'plugin_listview',
				'options' => array(
					'listdef' => 'list/mail_user_filter.list.php',
					'sqlextwhere' => "mailuser_id = ".@$app->functions->intval(@$_REQUEST['id']),
					'sql_order_by' => "ORDER BY rulename"
				)
			)
		)
	);
}


if ($_SESSION["s"]["user"]["typ"] == 'admin' && $global_config['mail']['mailbox_show_custom_rules_tab'] === 'y') {
	$form["tabs"]['mailfilter'] = array (
		'title'  => "Custom Rules",
		'width'  => 100,
		'template'  => "templates/mail_user_custom_rules_edit.htm",
		'fields'  => array (
			//#################################
			// Begin Datatable fields
			//#################################
			'custom_mailfilter' => array (
				'datatype' => 'TEXT',
				'formtype' => 'TEXTAREA',
				'default' => '',
				'value'  => '',
				'cols'  => '30',
				'rows'  => '15'
			),
			//#################################
			// END Datatable fields
			//#################################
		)
	);
}

?>
