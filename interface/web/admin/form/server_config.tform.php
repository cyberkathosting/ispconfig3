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

$form["title"] 			= "Server Config";
$form["description"] 	= "";
$form["name"] 			= "server_config";
$form["action"]			= "server_config_edit.php";
$form["db_table"]		= "server";
$form["db_table_idx"]	= "server_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "server";
$form["list_default"]	= "server_config_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['server'] = array (
	'title' 	=> "Server",
	'width' 	=> 70,
	'template' 	=> "templates/server_config_server_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'auto_network_configuration' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'n',
			'value'		=> array(0 => 'n',1 => 'y')
		),
		'ip_address' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '192.168.0.105',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISIPV4',
														'errmsg'=> 'ip_address_error_wrong'),
									),
			'value'		=> '',
			'width'		=> '15',
			'maxlength'	=> '255'
		),
		'netmask' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '255.255.255.0',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISIPV4',
														'errmsg'=> 'netmask_error_wrong'),
									),
			'value'		=> '',
			'width'		=> '15',
			'maxlength'	=> '255'
		),
		'gateway' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '192.168.0.1',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISIPV4',
														'errmsg'=> 'gateway_error_wrong'),
									),
			'value'		=> '',
			'width'		=> '15',
			'maxlength'	=> '255'
		),
		'hostname' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> 'server1.domain.tld',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'hostname_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'nameservers' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '192.168.0.1,192.168.0.2',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'nameservers_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

$form["tabs"]['mail'] = array (
	'title' 	=> "Mail",
	'width' 	=> 60,
	'template' 	=> "templates/server_config_mail_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'module' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'value'		=> array('postfix_mysql' => 'postfix_mysql')
		),
		'maildir_path' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '/home/vmail/[domain]/[localpart]/',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'maildir_path_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'homedir_path' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '/home/vmail/',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'homedir_path_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'mailuser_uid' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '5000',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'mailuser_uid_error_empty'),
									),
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '255'
		),
		'mailuser_gid' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '5000',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'mailuser_gid_error_empty'),
									),
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '255'
		),
		'mailuser_name' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> 'vmail',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'mailuser_name_error_empty'),
									),
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '255'
		),
		'mailuser_group' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> 'vmail',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'mailuser_group_error_empty'),
									),
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '255'
		),
		'relayhost' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'relayhost_user' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'relayhost_password' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'mailbox_size_limit' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '0',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '15'
		),
		'message_size_limit' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'default'	=> '0',
			'value'		=> '',
			'width'		=> '10',
			'maxlength'	=> '15'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

$form["tabs"]['getmail'] = array (
	'title' 	=> "Getmail",
	'width' 	=> 80,
	'template' 	=> "templates/server_config_getmail_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'getmail_config_dir' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'getmail_config_dir_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

$form["tabs"]['web'] = array (
	'title' 	=> "Web",
	'width' 	=> 60,
	'template' 	=> "templates/server_config_web_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'website_basedir' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'website_basedir_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'website_path' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'website_path_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'website_symlinks' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'website_symlinks_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'vhost_conf_dir' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'vhost_conf_dir_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'vhost_conf_enabled_dir' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'vhost_conf_enabled_dir_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'security_level' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '20',
			'value'		=> array('10' => 'Medium', '20' => 'High')
		),
		'user' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'apache_user_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'group' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'apache_group_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

$form["tabs"]['fastcgi'] = array (
	'title' 	=> "FastCGI",
	'width' 	=> 80,
	'template' 	=> "templates/server_config_fastcgi_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'fastcgi_starter_path' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'fastcgi_starter_path_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'fastcgi_starter_script' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'fastcgi_starter_script_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'fastcgi_alias' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'fastcgi_alias_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'fastcgi_phpini_path' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'fastcgi_phpini_path_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'fastcgi_children' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'fastcgi_children_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'fastcgi_max_requests' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'fastcgi_max_requests_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'fastcgi_bin' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'fastcgi_bin_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);


$form["tabs"]['jailkit'] = array (
	'title' 	=> "Jailkit",
	'width' 	=> 80,
	'template' 	=> "templates/server_config_jailkit_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'jailkit_chroot_home' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'jailkit_chroot_home_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'jailkit_chroot_app_sections' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'jailkit_chroot_app_sections_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '1000'
		),
		'jailkit_chroot_app_programs' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'jailkit_chroot_app_programs_error_empty'),
									),
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '1000'
		),
        'jailkit_chroot_cron_programs' => array (
            'datatype'  => 'VARCHAR',
            'formtype'  => 'TEXT',
            'default'   => '',
            'validators'    => array (  0 => array (    'type'  => 'NOTEMPTY',
                                                        'errmsg'=> 'jailkit_chroot_cron_programs_error_empty'),
                                    ),
            'value'     => '',
            'width'     => '40',
            'maxlength' => '1000'
        ),
	##################################
	# ENDE Datatable fields
	##################################
	)
);


$form["tabs"]['vlogger'] = array (
    'title'     => "vlogger",
    'width'     => 80,
    'template'  => "templates/server_config_vlogger_edit.htm",
    'fields'    => array (
    ##################################
    # Begin Datatable fields
    ##################################
        'config_dir' => array (
            'datatype'  => 'VARCHAR',
            'formtype'  => 'TEXT',
            'default'   => '',
            'validators'    => array (  0 => array (    'type'  => 'NOTEMPTY',
                                                        'errmsg'=> 'vlogger_config_dir_error_empty'),
                                    ),
            'value'     => '',
            'width'     => '40',
            'maxlength' => '255'
        ),
    ##################################
    # ENDE Datatable fields
    ##################################
    )
);



$form["tabs"]['cron'] = array (
    'title'     => "Cron",
    'width'     => 80,
    'template'  => "templates/server_config_cron_edit.htm",
    'fields'    => array (
    ##################################
    # Begin Datatable fields
    ##################################
        'init_script' => array (
            'datatype'  => 'VARCHAR',
            'formtype'  => 'TEXT',
            'default'   => '',
            'validators'    => array (  0 => array (    'type'  => 'NOTEMPTY',
                                                        'errmsg'=> 'cron_init_script_error_empty'),
                                    ),
            'value'     => '',
            'width'     => '40',
            'maxlength' => '255'
        ),
        'crontab_dir' => array (
            'datatype'  => 'VARCHAR',
            'formtype'  => 'TEXT',
            'default'   => '',
            'validators'    => array (  0 => array (    'type'  => 'NOTEMPTY',
                                                        'errmsg'=> 'crontab_dir_error_empty'),
                                    ),
            'value'     => '',
            'width'     => '40',
            'maxlength' => '255'
        ),
        'wget' => array (
            'datatype'  => 'VARCHAR',
            'formtype'  => 'TEXT',
            'default'   => '',
            'validators'    => array (  0 => array (    'type'  => 'NOTEMPTY',
                                                        'errmsg'=> 'cron_wget_error_empty'),
                                    ),
            'value'     => '',
            'width'     => '40',
            'maxlength' => '255'
        ),
    ##################################
    # ENDE Datatable fields
    ##################################
    )
);









?>