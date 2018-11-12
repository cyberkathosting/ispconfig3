<?php

// Title of the form.
$form['title']    = 'Frequently Asked Questions';

// Optional description of the form.
$form['description']  = '';

// Name of the form which cannot contain spaces or foreign characters.
$form['name']    = 'faq_form';

// The file that is used to call the form in the browser.
$form['action']   = 'faq_edit.php';

// The name of the database table used to store the data
$form['db_table']  = 'help_faq';

// The name of the database table index field.
// This field must be a numeric auto increment column.
$form['db_table_idx'] = 'hf_id';

// Should changes to this table be stored in the database history (sys_datalog) table.
// This should be set to 'yes' for all tables that store configuration information.
$form['db_history']  = 'no';

// The name of the tab that is shown when the form is opened
$form['tab_default'] = 'message';

// The name of the default list file of this form
$form['list_default'] = 'faq_manage_questions_list.php';

// Use the internal authentication system for this table. This should
// be set to 'yes' in most cases, otherwise 'no'.
$form['auth']   = 'yes';

//** Authentication presets. The defaults below does not need to be changed in most cases.

// 0 = id of the user, > 0 id must match with id of current user
$form['auth_preset']['userid']  = 0;

// 0 = default groupid of the user, > 0 id must match with groupid of current
$form['auth_preset']['groupid'] = 0;

// Permissions with the following codes: r = read, i = insert, u = update, d = delete
$form['auth_preset']['perm_user'] = 'riud';
$form['auth_preset']['perm_group'] = 'riud';
$form['auth_preset']['perm_other'] = 'r';

// The form definition of the first tab. The name of the tab is called 'message'. We refer
// to this name in the $form['tab_default'] setting above.
$form['tabs']['message'] = array(
	'title'  => 'FAQ', // Title of the Tab
	'width'  => 100,       // Tab width
	'template'  => 'templates/faq_edit.htm', // Template file name
	'fields'  => array(

		//*** BEGIN Datatable columns **********************************

		'hf_section' => array (
			'datatype'      => 'INTEGER',
			'formtype'      => 'SELECT',
			'default'       => '',
			'datasource'    => array ( 'type'  => 'SQL',
				'querystring' => 'SELECT hfs_id,hfs_name FROM help_faq_sections',
				'keyfield' => 'hfs_id',
				'valuefield'    => 'hfs_name'
			),
			'validators' => array ( 0 => array (
					'type'  => 'ISINT',
					'errmsg'=> 'recipient_id_is_not_integer'),
			),
			'value'  => ($_SESSION['s']['user']['typ'] != 'admin')?array(1 => 'Administrator'):''
		),

		'hf_question' => array(
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array( 0 => array( 'type'  => 'NOTEMPTY',
					'errmsg'=> 'subject_is_empty'
				),
			),
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default'     => '',
			'value'      => '',
			'width'      => '30',
			'maxlength'  => '255'
		),

		'hf_answer' => array(
			'datatype' => 'TEXT',
			'formtype' => 'TEXTAREA',
			'validators' => array( 0 => array(  'type'  => 'NOTEMPTY',
					'errmsg' => 'message_is_empty'
				),
			),
			'default' => '',
			'value'  => '',
			'cols'  => '30',
			'rows'  => '10',
			'maxlength' => '255'
		),

		//*** END Datatable columns **********************************
	)
);
?>
