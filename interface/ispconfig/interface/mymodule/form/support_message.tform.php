<?php

// Title of the form.
$form['title'] 			= 'Support Message';

// Optional description of the form.
$form['description'] 	= '';

// Name of the form which cannot contain spaces or foreign characters.
$form['name'] 			= 'support_message';

// The file that is used to call the form in the browser.
$form['action']			= 'support_message_edit.php';

// The name of the database table used to store the data
$form['db_table']		= 'mail_user';

// The name of the database table index field.
// This field must be a numeric auto increment column.
$form['db_table_idx']	= 'support_message_id';

// Should changes to this table be stored in the database history (sys_datalog) table.
// This should be set to 'yes' for all tables that store configuration information.
$form['db_history']		= 'no'; 

// The name of the tab that is shown when the form is opened
$form['tab_default']	= 'mymodule';

// The name of the default list file of this form
$form['list_default']	= 'support_message_list.php';

// Use the internal authentication system for this table. This should
// be set to 'yes' in most cases, otherwise 'no'.
$form['auth']			= 'yes'; 

//** Authentication presets. The defaults below does not need to be changed in most cases.

// 0 = id of the user, > 0 id must match with id of current user
$form['auth_preset']['userid']  = 0;

 // 0 = default groupid of the user, > 0 id must match with groupid of current
$form['auth_preset']['groupid'] = 0; // user

// Permissions with the following codes: r = read, i = insert, u = update, d = delete
$form['auth_preset']['perm_user'] = 'riud';
$form['auth_preset']['perm_group'] = 'riud';
$form['auth_preset']['perm_other'] = ''; 

// The form definition of the first tab. The name of the tab is called 'message'. We refer
// to this name in the $form['tab_default'] setting above.
$form['tabs']['message'] = array(
    'title' 	=> 'Message', // Title of the Tab
    'width' 	=> 100,       // Tab width
    'template' 	=> 'templates/support_message_edit.htm', // Template file name
    'fields' 	=> array(

        //*** BEGIN Datatable columns **********************************

        'recipient_id' => array(
            'datatype'   => 'INTEGER',
            'formtype'   => 'SELECT',
            'default'    => '',
            'datasource' => array(
                        'type'         => 'SQL',
                        'querystring'  => 'SELECT userid,username FROM sys_user WHERE {AUTHSQL} ORDER BY username',
                        'keyfield'     => 'userid',
                        'valuefield'   => 'username'
                                 ),
            'validators' => array( 0 => array( 'type'	=> 'ISINT',
                                                'errmsg'=> 'recipient_id_is_not_integer'
                                                ),
                                 ),
            'value'      => ''
        ),

        'sender_id' => array(
            'datatype'   => 'INTEGER',
            'formtype'   => 'SELECT',
            'default'    => '',
            'datasource' => array(
                        'type'          => 'SQL',
                        'querystring'   => 'SELECT userid,username FROM sys_user WHERE {AUTHSQL} ORDER BY username',
                        'keyfield'      => 'userid',
                        'valuefield'    => 'username'
                                 ),
            'validators' => array( 0 => array( 'type'   => 'ISINT',
                                               'errmsg' => 'recipient_id_is_not_integer'
                                             ),
                                 ),
            'value'      => ''
        ),

        'subject' => array(
            'datatype'   => 'VARCHAR',
            'formtype'   => 'TEXT',
            'validators' => array( 0 => array( 'type'  => 'NOTEMPTY',
                                               'errmsg'=> 'subject_is_empty'
                                             ),
                                  ),
            'default'     => '',
            'value'      => '',
            'width'      => '30',
            'maxlength'  => '255'
        ),

        'message' => array(
            'datatype'	 => 'VARCHAR',
            'formtype'	 => 'TEXTAREA',
            'validators' => array( 0 => array( 'type'	=> 'NOTEMPTY',
                                                'errmsg'=> 'message_is_empty'
                                             ),
                                 ),
            'default'    => '',
            'value'      => '',
            'cols'       => '30',
            'rows'       => '10',
            'maxlength'  => '255'
        ),

        'tstamp' => array(
            'datatype'   => 'INTEGER',
            'formtype'   => 'TEXT',
            'default'    => time(),
            'value'      => '',
            'width'      => '30',
            'maxlength'  => '30'
        ),

        //*** END Datatable columns **********************************
	)
);
?>