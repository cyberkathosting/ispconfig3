<?php

die('deprecated');

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

$form["title"]    = "Spamfilter";
$form["description"]  = "";
$form["name"]    = "mail_spamfilter";
$form["action"]   = "mail_spamfilter_edit.php";
$form["db_table"]  = "mail_spamfilter";
$form["db_table_idx"] = "spamfilter_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "spamfilter";
$form["list_default"] = "mail_spamfilter_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['spamfilter'] = array (
	'title'  => "Spamfilter",
	'width'  => 100,
	'template'  => "templates/mail_spamfilter_edit.htm",
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
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'email_error_notempty'),
				1 => array ( 'type' => 'UNIQUE',
					'errmsg'=> 'email_error_unique'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'spam_rewrite_score_int' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '5.00',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '10'
		),
		'spam_redirect_score_int' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '7.00',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '10'
		),
		'spam_delete_score_int' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '15.00',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '10'
		),
		'spam_rewrite_subject' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '***SPAM*** ',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'spam_redirect_maildir' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT mailbox_id,email FROM mail_box WHERE {AUTHSQL} ORDER BY email',
				'keyfield'=> 'mailbox_id',
				'valuefield'=> 'email'
			),
			'default' => '',
			'value'  => ''
		),
		'spam_redirect_maildir_purge' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '7',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '10'
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
