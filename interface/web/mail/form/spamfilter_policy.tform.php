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

$form["title"]    = "Spamfilter policy";
$form["description"]  = "";
$form["name"]    = "spamfilter_policy";
$form["action"]   = "spamfilter_policy_edit.php";
$form["db_table"]  = "spamfilter_policy";
$form["db_table_idx"] = "id";
$form["db_history"]  = "yes";
$form["tab_default"] = "policy";
$form["list_default"] = "spamfilter_policy_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = 'r'; //r = read, i = insert, u = update, d = delete

$form["tabs"]['policy'] = array (
	'title'  => "Policy",
	'width'  => 100,
	'template'  => "templates/spamfilter_policy_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'policy_name' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'policyname_error_notempty'),
			),
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'virus_lover' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'spam_lover' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);


$form["tabs"]['amavis'] = array (
	'title'  => "Amavis",
	'width'  => 100,
	'template'  => "templates/spamfilter_amavis_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'banned_files_lover' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'bad_header_lover' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'bypass_virus_checks' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'bypass_banned_checks' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'bypass_header_checks' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'virus_quarantine_to' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'spam_quarantine_to' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'banned_quarantine_to' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'bad_header_quarantine_to' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'clean_quarantine_to' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'other_quarantine_to' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'spam_tag_level' => array (
			'datatype' => 'DOUBLE',
			'formtype' => 'TEXT',
			'default' => '0',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '255'
		),
		'spam_tag2_level' => array (
			'datatype' => 'DOUBLE',
			'formtype' => 'TEXT',
			'default' => '0',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '255'
		),
		'spam_kill_level' => array (
			'datatype' => 'DOUBLE',
			'formtype' => 'TEXT',
			'default' => '0',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '255'
		),
		'spam_dsn_cutoff_level' => array (
			'datatype' => 'DOUBLE',
			'formtype' => 'TEXT',
			'default' => '0',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '255'
		),
		'spam_quarantine_cutoff_level' => array (
			'datatype' => 'DOUBLE',
			'formtype' => 'TEXT',
			'default' => '0',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '255'
		),
		'spam_modifies_subj' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'spam_subject_tag' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'spam_subject_tag2' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'addr_extension_virus' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'addr_extension_spam' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'addr_extension_banned' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'addr_extension_bad_header' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'warnvirusrecip' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'warnbannedrecip' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'warnbadhrecip' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'N',
			'value'  => array('N' => 'No', 'Y' => 'Yes')
		),
		'newvirus_admin' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'virus_admin' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'banned_admin' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'bad_header_admin' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		'spam_admin' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),

		'message_size_limit' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '255'
		),
		'banned_rulenames' => array (
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
			'width'  => '30',
			'maxlength' => '255'
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);

$form["tabs"]['rspamd'] = array (
	'title'  => "Rspamd",
	'width'  => 100,
	'template'  => "templates/spamfilter_rspamd_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'rspamd_spam_greylisting_level' => array (
			'datatype' => 'DOUBLE',
			'formtype' => 'TEXT',
			'default' => '0',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '255'
		),
		'rspamd_spam_tag_level' => array (
			'datatype' => 'DOUBLE',
			'formtype' => 'TEXT',
			'default' => '0',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '255'
		),
		'rspamd_spam_tag_method' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'rewrite_subject',
			'value'  => array('add_header' => $app->lng('add_header_txt'), 'rewrite_subject' => $app->lng('rewrite_subject_txt'))
		),
		'rspamd_spam_kill_level' => array (
			'datatype' => 'DOUBLE',
			'formtype' => 'TEXT',
			'default' => '0',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '255'
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);
