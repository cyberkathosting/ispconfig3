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

$muc_available = $muc_pastebin_available = $muc_httparchive_available = $anon_available = $vjud_available = $proxy_available = $status_available = true;
if(!$app->auth->is_admin()) {
    $client_group_id = $_SESSION["s"]["user"]["default_group"];
    $client = $app->db->queryOneRecord("SELECT limit_xmpp_muc, limit_xmpp_anon, limit_xmpp_vjud, limit_xmpp_proxy, limit_xmpp_status, limit_xmpp_pastebin, limit_xmpp_httparchive FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

    if($client['limit_xmpp_muc'] != 'y') $muc_available = false;
    if($client['limit_xmpp_pastebin'] != 'y' || $client['limit_xmpp_muc'] != 'y') $muc_pastebin_available = false;
    if($client['limit_xmpp_httparchive'] != 'y' || $client['limit_xmpp_muc'] != 'y') $muc_httparchive_available = false;
    if($client['limit_xmpp_anon'] != 'y') $anon_available = false;
    if($client['limit_xmpp_vjud'] != 'y') $vjud_available = false;
    if($client['limit_xmpp_proxy'] != 'y') $proxy_available= false;
    if($client['limit_xmpp_status'] != 'y') $status_available = false;
}

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
		'management_method' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'SELECT',
			'default'       => '0',
			'value'         => array(0 => 'Normal', 1 => 'By Mail Domain')
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
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS')
			),
            'default' => "",
            'value' => ''
        ),
        'domain_admins' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
            'default' => '',
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
        'use_pubsub' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'y',
            'value'  => array(0 => 'n', 1 => 'y')
        )
        //#################################
        // ENDE Datatable fields
        //#################################
    )
);
if($anon_available)
    $form['tabs']['features']['fields']['use_anon_host'] = array (
        'datatype' => 'VARCHAR',
        'formtype' => 'CHECKBOX',
        'default' => 'y',
        'value'  => array(0 => 'n', 1 => 'y')
    );
if($vjud_available){
    $form['tabs']['features']['fields']['use_vjud'] = array (
        'datatype' => 'VARCHAR',
        'formtype' => 'CHECKBOX',
        'default' => 'y',
        'value'  => array(0 => 'n', 1 => 'y')
    );
    $form['tabs']['features']['fields']['vjud_opt_mode'] = array (
        'datatype'      => 'VARCHAR',
        'formtype'      => 'SELECT',
        'default'       => '0',
        'value'         => array(0 => 'Opt-In', 1 => 'Opt-Out')
    );
}

if($proxy_available)
    $form['tabs']['features']['fields']['use_proxy'] = array (
        'datatype' => 'VARCHAR',
        'formtype' => 'CHECKBOX',
        'default' => 'y',
        'value'  => array(0 => 'n', 1 => 'y')
    );
if($status_available)
    $form['tabs']['features']['fields']['use_status_host'] = array (
        'datatype' => 'VARCHAR',
        'formtype' => 'CHECKBOX',
        'default' => 'y',
        'value'  => array(0 => 'n', 1 => 'y')
    );


if($muc_available)
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
            //#################################
            // ENDE Datatable fields
            //#################################
        )
    );
if($muc_available && $muc_pastebin_available){
    $form['tabs']['muc']['fields']['use_pastebin'] = array (
        'datatype' => 'VARCHAR',
        'formtype' => 'CHECKBOX',
        'default' => 'y',
        'value'  => array(0 => 'n', 1 => 'y')
    );
    $form['tabs']['muc']['fields']['pastebin_expire_after'] = array(
        'datatype' => 'VARCHAR',
        'formtype' => 'TEXT',
        'default' => '48',
        'validators' => array(0 => array('type' => 'ISINT'),
            array('type'=>'RANGE', 'range'=>'1:168')
        ),
        'value' => '',
        'width' => '15'
    );
    $form['tabs']['muc']['fields']['pastebin_trigger'] = array(
        'datatype' => 'VARCHAR',
        'formtype' => 'TEXT',
        'default' => '!paste',
        'value' => '',
        'width' => '15'
    );
}
if($muc_available && $muc_httparchive_available){
    $form['tabs']['muc']['fields']['use_http_archive'] = array (
        'datatype' => 'VARCHAR',
        'formtype' => 'CHECKBOX',
        'default' => 'y',
        'value'  => array(0 => 'n', 1 => 'y')
    );
    $form['tabs']['muc']['fields']['http_archive_show_join'] = array (
        'datatype' => 'VARCHAR',
        'formtype' => 'CHECKBOX',
        'default' => 'y',
        'value'  => array(0 => 'n', 1 => 'y')
    );
    $form['tabs']['muc']['fields']['http_archive_show_status'] = array (
        'datatype' => 'VARCHAR',
        'formtype' => 'CHECKBOX',
        'default' => 'y',
        'value'  => array(0 => 'n', 1 => 'y')
    );
}

$form["tabs"]['ssl'] = array (
    'title'  => "SSL",
    'width'  => 100,
    'template'  => "templates/xmpp_domain_edit_ssl.htm",
    'readonly' => false,
    'fields'  => array (
        //#################################
        // Begin Datatable fields
        //#################################
        'ssl_state' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'validators' => array (  0 => array ( 'type' => 'REGEX',
                'regex' => '/^(([\.]{0})|([-a-zA-Z0-9._,&äöüÄÖÜ ]{1,255}))$/',
                'errmsg'=> 'ssl_state_error_regex'),
            ),
            'default' => '',
            'value'  => '',
            'width'  => '30',
            'maxlength' => '255'
        ),
        'ssl_locality' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'validators' => array (  0 => array ( 'type' => 'REGEX',
                'regex' => '/^(([\.]{0})|([-a-zA-Z0-9._,&äöüÄÖÜ ]{1,255}))$/',
                'errmsg'=> 'ssl_locality_error_regex'),
            ),
            'default' => '',
            'value'  => '',
            'width'  => '30',
            'maxlength' => '255'
        ),
        'ssl_organisation' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'validators' => array (  0 => array ( 'type' => 'REGEX',
                'regex' => '/^(([\.]{0})|([-a-zA-Z0-9._,&äöüÄÖÜ ]{1,255}))$/',
                'errmsg'=> 'ssl_organisation_error_regex'),
            ),
            'default' => '',
            'value'  => '',
            'width'  => '30',
            'maxlength' => '255'
        ),
        'ssl_organisation_unit' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'validators' => array (  0 => array ( 'type' => 'REGEX',
                'regex' => '/^(([\.]{0})|([-a-zA-Z0-9._,&äöüÄÖÜ ]{1,255}))$/',
                'errmsg'=> 'ssl_organistaion_unit_error_regex'),
            ),
            'default' => '',
            'value'  => '',
            'width'  => '30',
            'maxlength' => '255'
        ),
        'ssl_country' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'SELECT',
            'default' => '',
            'datasource' => array (  'type' => 'SQL',
                'querystring' => 'SELECT iso,printable_name FROM country ORDER BY printable_name',
                'keyfield'=> 'iso',
                'valuefield'=> 'printable_name'
            ),
            'value'  => ''
        ),
        'ssl_email' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'TEXT',
            'default' => '',
            'value'  => '',
            'width'  => '30',
            'maxlength' => '255',
            'validators' => array (  0 => array ( 'type' => 'ISEMAIL',
                'errmsg'=> 'ssl_error_isemail')
            ),
        ),
        'ssl_key' => array (
            'datatype' => 'TEXT',
            'formtype' => 'TEXTAREA',
            'default' => '',
            'value'  => '',
            'cols'  => '30',
            'rows'  => '10'
        ),
        'ssl_request' => array (
            'datatype' => 'TEXT',
            'formtype' => 'TEXTAREA',
            'default' => '',
            'value'  => '',
            'cols'  => '30',
            'rows'  => '10'
        ),
        'ssl_cert' => array (
            'datatype' => 'TEXT',
            'formtype' => 'TEXTAREA',
            'default' => '',
            'value'  => '',
            'cols'  => '30',
            'rows'  => '10'
        ),
        'ssl_bundle' => array (
            'datatype' => 'TEXT',
            'formtype' => 'TEXTAREA',
            'default' => '',
            'value'  => '',
            'cols'  => '30',
            'rows'  => '10'
        ),
        'ssl_action' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'SELECT',
            'default' => '',
            'value'  => array('' => 'none_txt', 'save' => 'save_certificate_txt', 'create' => 'create_certificate_txt', 'del' => 'delete_certificate_txt')
        ),
        //#################################
        // ENDE Datatable fields
        //#################################
    )
);


?>
