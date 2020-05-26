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

$form["title"]   = "system_config_title";
$form["description"]  = "system_config_desc_txt";
$form["name"]   = "system_config";
$form["action"]  = "system_config_edit.php";
$form["db_table"] = "sys_ini";
$form["db_table_idx"] = "sysini_id";
$form["db_history"] = "yes";
$form["tab_default"] = "sites";
$form["list_default"] = "server_list.php";
$form["auth"]  = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['sites'] = array (
	'title'  => "Sites",
	'width'  => 70,
	'template'  => "templates/system_config_sites_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'dbname_prefix' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9\-\_\[\]]{0,50}$/',
					'errmsg'=> 'dbname_prefix_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'dbuser_prefix' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9\-\_\[\]]{0,50}$/',
					'errmsg'=> 'dbuser_prefix_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'ftpuser_prefix' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9\-\_\[\]]{0,50}$/',
					'errmsg'=> 'ftpuser_prefix_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'shelluser_prefix' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9\-\_\[\]]{0,50}$/',
					'errmsg'=> 'shelluser_prefix_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'webdavuser_prefix' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9\-\_\[\]]{0,50}$/',
					'errmsg'=> 'webdavuser_prefix_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'dblist_phpmyadmin_link' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'phpmyadmin_url' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					'regex' => '/^[0-9a-zA-Z\:\/\-\.\_\[\]\?\=\&]{0,255}$/',
					'errmsg'=> 'phpmyadmin_url_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'webftp_url' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					'regex' => '/^[0-9a-zA-Z\:\/\-\.]{0,255}$/',
					'errmsg'=> 'webftp_url_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'vhost_subdomains' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'vhost_aliasdomains' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'client_username_web_check_disabled' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'backups_include_into_web_quota' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'reseller_can_use_options' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'default_webserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '1',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => '',
			'name'  => 'default_webserver'
		),
		'default_dbserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '1',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => '',
			'name'  => 'default_dbserver'
		),
		'web_php_options' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOXARRAY',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'web_php_options_notempty'),
			),
			'default' => '',
			'separator' => ',',
			'value'  => array('no' => 'Disabled', 'fast-cgi' => 'Fast-CGI', 'cgi' => 'CGI', 'mod' => 'Mod-PHP', 'suphp' => 'SuPHP', 'php-fpm' => 'PHP-FPM', 'hhvm' => 'HHVM')
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);

$form["tabs"]['mail'] = array (
	'title'  => "Mail",
	'width'  => 70,
	'template'  => "templates/system_config_mail_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'enable_custom_login' => array(
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value' => array(0 => 'n', 1 => 'y')
		),
		'mailbox_show_autoresponder_tab' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default'  => 'y',
			'value'    => array(0 => 'n', 1 => 'y')
		),
		'mailbox_show_mail_filter_tab' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default'  => 'y',
			'value'    => array(0 => 'n', 1 => 'y')
		),
		'mailbox_show_custom_rules_tab' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default'  => 'y',
			'value'    => array(0 => 'n', 1 => 'y')
		),
		'mailboxlist_webmail_link' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'webmail_url' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					/*'regex' => '/^[0-9a-zA-Z\:\/\-\.]{0,255}(\?.+)?$/',*/
					'regex' => '/^[0-9a-zA-Z\:\/\-\.\[\]]{0,255}$/',
					'errmsg'=> 'webmail_url_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'mailmailinglist_link' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'mailmailinglist_url' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					'regex' => '/^[0-9a-zA-Z\:\/\-\.]{0,255}$/',
					'errmsg'=> 'mailinglist_url_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'admin_mail' => array (
			'datatype' => 'VARCHAR',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER'),
				3 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
				4 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'admin_name' => array (
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
		'smtp_enabled' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'smtp_host' => array (
			'datatype' => 'VARCHAR',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER'),
				3 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
				4 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'formtype' => 'TEXT',
			'default' => 'localhost',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'smtp_port' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '25',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'smtp_user' => array (
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
		'smtp_pass' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'smtp_crypt' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('' => 'No', 'ssl' => 'SSL', 'tls' => 'STARTTLS')
		),
		'default_mailserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '1',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => '',
			'name'  => 'default_mailserver'
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);

$form["tabs"]['dns'] = array (
	'title'  => "DNS",
	'width'  => 70,
	'template'  => "templates/system_config_dns_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'default_dnsserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '1',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => '',
			'name'  => 'default_dnsserver'
		),
		'default_slave_dnsserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '1',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => '',
			'name'  => 'default_slave_dnsserver'
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);

$form["tabs"]['domains'] = array (
	'title'  => "Domains",
	'width'  => 70,
	'template'  => "templates/system_config_domains_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'use_domain_module' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'new_domain_html' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS')
			),
			'default' => '',
			'value'  => ''
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);

/* TODO_ BEGIN: Branding

$form["tabs"]['domains'] = array (
	'title' 	=> "Branding",
	'width' 	=> 70,
	'template' 	=> "templates/system_config_branding_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
                'allow_themechange' => array (
                        'datatype'	=> 'VARCHAR',
                        'formtype'	=> 'CHECKBOX',
                        'default'	=> 'N',
                        'value'         => array(0 => 'n',1 => 'y')
                ),
	##################################
	# ENDE Datatable fields
	##################################
	)
);


 END: Branding */
$form["tabs"]['misc'] = array (
	'title'  => "Misc",
	'width'  => 70,
	'template'  => "templates/system_config_misc_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'company_name' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => ''
		),
		'custom_login_text' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => ''
		),
		'custom_login_link' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
				'regex' => '/^(http|https):\\/\\/.*|^$/',
				'errmsg'=> 'login_link_error_regex'),
			)
		),
		'dashboard_atom_url_admin' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => 'http://www.ispconfig.org/atom',
			'value'  => ''
		),
		'dashboard_atom_url_reseller' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => 'http://www.ispconfig.org/atom',
			'value'  => ''
		),
		'dashboard_atom_url_client' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => 'http://www.ispconfig.org/atom',
			'value'  => ''
		),
		'monitor_key' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => ''
		),
		'tab_change_discard' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'tab_change_warning' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'use_loadindicator' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'use_combobox' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'maintenance_mode' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'maintenance_mode_exclude_ips' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array(
				 0 => array (
					'type' => 'ISIP',
					'allowempty' => true,
					'separator' => ',',
					'errmsg'=> 'maintenance_mode_exclude_ips_error_isip'
				),
			),
			'default' => '',
			'value'  => ''
		),
		'admin_dashlets_left' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => ''
		),
		'admin_dashlets_right' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => ''
		),
		'reseller_dashlets_left' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => ''
		),
		'reseller_dashlets_right' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => ''
		),
		'client_dashlets_left' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => ''
		),
		'client_dashlets_right' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default' => '',
			'value'  => ''
		),
		'customer_no_template' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9\-\_\[\]]{0,50}$/',
					'errmsg'=> 'customer_no_template_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'customer_no_start' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'customer_no_counter' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'session_timeout' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'session_allow_endless' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'min_password_length' => array(
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '5',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'min_password_strength' => array(
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('' => 'None', '1' => 'strength_1', '2' => 'strength_2', '3' => 'strength_3', '4' => 'strength_4', '5' => 'strength_5')
		),
		'ssh_authentication' => array(
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('' => 'ssh_authentication_password_key', 'password' => 'ssh_authentication_password', 'key' => 'ssh_authentication_key')
		)
		//#################################
		// END Datatable fields
		//#################################
	)
);

$form['tabs']['dns_ca'] = array (
	'title'  => 'DNS CAs',
	'width'  => 100,
	'template'  => 'templates/system_config_dns_ca.htm',
	'fields'  => array (),
	'plugins' => array (
		'dns_ca' => array (
			'class'   => 'plugin_system_config_dns_ca',
			'options' => array()
		),
		'dns_ca_list' => array (
			'class'   => 'plugin_system_config_dns_ca_list',
			'options' => array()
		)
	)
);

?>
