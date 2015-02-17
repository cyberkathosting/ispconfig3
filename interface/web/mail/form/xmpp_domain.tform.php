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

$form["title"]    = "XMPP Domain";
$form["description"]  = "";
$form["name"]    = "xmpp_domain";
$form["action"]   = "xmpp_domain_edit.php";
$form["db_table"]  = "xmpp_domain";
$form["db_table_idx"] = "domain_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "domain";
$form["list_default"] = "xmpp_domain_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['domain'] = array (
	'title'  => "Domain",
	'width'  => 100,
	'template'  => "templates/xmpp_domain_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE xmpp_server = 1 AND mirror_server_id = 0 AND {AUTHSQL} ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'
			),
			'value'  => ''
		),
		'domain' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'domain_error_empty'),
				1 => array ( 'type' => 'UNIQUE',
					'errmsg'=> 'domain_error_unique'),
				2 => array ( 'type' => 'REGEX',
					'regex' => '/^[\w\.\-]{2,255}\.[a-zA-Z0-9\-]{2,30}$/',
					'errmsg'=> 'domain_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 1
		),
		'auth_method' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'SELECT',
			'default'       => '1',
			'value'         => array(0 => 'Plain', 1 => 'Hashed', 2 => 'By Email Mailbox')
		),
        'public_registration' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'registration_url' => array (
            'datatype' => 'VARCHAR',
            'validators' => array (  0 => array ( 'type' => 'REGEX',
                'regex' => '@^(([\.]{0})|((ftp|https?)://([-\w\.]+)+(:\d+)?(/([\w/_\.\,\-\+\?\~!:%]*(\?\S+)?)?)?)|(\[scheme\]://([-\w\.]+)+(:\d+)?(/([\w/_\.\-\,\+\?\~!:%]*(\?\S+)?)?)?)|(/(?!.*\.\.)[\w/_\.\-]{1,255}/))$@',
                'errmsg'=> 'redirect_error_regex'),
            ),
            'formtype' => 'TEXT',
            'default' => '',
            'value'  => '',
            'width'  => '30',
            'maxlength' => '255'
        ),
        'registration_message' => array(
            'datatype' => 'TEXT',
            'formtype' => 'TEXT',
            'default' => "Please visit our website for information on registration.",
            'value' => ''
        ),
        'domain_admins' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'default' => 'admin@service.com, superuser@service.com',
            'value' => '',
            'width' => '15',
            'maxlength' => '3'
        ),

		'active' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		//#################################
		// ENDE Datatable fields
		//#################################
	)
);


$form["tabs"]['features'] = array (
    'title'  => "Modules",
    'width'  => 100,
    'template'  => "templates/xmpp_domain_edit_modules.htm",
    'fields'  => array (
        //#################################
        // Begin Datatable fields
        //#################################
        'use_anon_host' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'use_pubsub' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'use_vjud' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'vjud_opt_mode' => array (
            'datatype'      => 'VARCHAR',
            'formtype'      => 'SELECT',
            'default'       => '0',
            'value'         => array(0 => 'Opt-In', 1 => 'Opt-Out')
        ),
        'use_proxy' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'use_status_host' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        //#################################
        // ENDE Datatable fields
        //#################################
    )
);

$form["tabs"]['muc'] = array (
    'title'  => "MUC",
    'width'  => 100,
    'template'  => "templates/xmpp_domain_edit_muc.htm",
    'fields'  => array (
        //#################################
        // Begin Datatable fields
        //#################################
        'use_muc_host' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'muc_name' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'default' => ''
        ),
        'muc_restrict_room_creation' => array (
            'datatype'      => 'VARCHAR',
            'formtype'      => 'SELECT',
            'default'       => '1',
            'value'         => array(0 => 'Everyone', 1 => 'Members', 2 => 'Admins')
        ),
        'muc_admins' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'default' => 'admin@service.com, superuser@service.com',
            'value' => '',
            'width' => '15',
            'maxlength' => '3'
        ),
        'use_pastebin' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'pastebin_expire_after' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'default' => '48',
            'validators' => array(0 => array('type' => 'ISINT'),
                array('type'=>'RANGE', 'range'=>'1:168')
            ),
            'value' => '',
            'width' => '15'
        ),
        'pastebin_trigger' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'default' => '!paste',
            'value' => '',
            'width' => '15'
        ),
        'use_http_archive' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'http_archive_show_join' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'http_archive_show_status' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        //#################################
        // ENDE Datatable fields
        //#################################
    )
);


?>
