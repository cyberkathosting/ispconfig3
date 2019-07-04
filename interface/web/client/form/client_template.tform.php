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

$form["title"]    = "Client-Templates";
$form["description"]  = "";
$form["name"]    = "client_template";
$form["action"]   = "client_template_edit.php";
$form["db_table"]  = "client_template";
$form["db_table_idx"] = "template_id";
$form["db_history"]  = "no";
$form["tab_default"] = "template";
$form["list_default"] = "client_template_list.php";
$form["auth"]   = 'yes';

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

//* Languages
$language_list = array();
$handle = @opendir(ISPC_ROOT_PATH.'/lib/lang');
while ($file = @readdir($handle)) {
	if ($file != '.' && $file != '..') {
		if(@is_file(ISPC_ROOT_PATH.'/lib/lang/'.$file) and substr($file, -4, 4) == '.lng') {
			$tmp = substr($file, 0, 2);
			$language_list[$tmp] = $tmp;
		}
	}
}

$form["tabs"]['template'] = array (
	'title'  => "Template",
	'width'  => 80,
	'template'  => "templates/client_template_edit_template.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'template_type' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => 'm',
			'value'  => array('m' => "Main Template", 'a' => "Additional Template"),
		),
		'template_name' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'error_template_name_empty'),
			),
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

$form["tabs"]['limits'] = array (
	'title'  => "Limits",
	'width'  => 80,
	'template'  => "templates/client_template_edit_limits.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'limit_client' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_client_error_notint'),
			),
			'default' => '1',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),/*
		'default_mailserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '0',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => array(''),
			'name'  => 'default_mailserver'
		),*/
		'mail_servers' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'MULTIPLE',
			'separator' => ',',
			'default' => '1',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => '',
			'name'  => 'mail_servers'
		),
		'limit_maildomain' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_maildomain_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_mailbox' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailbox_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_mailalias' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailalias_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_mailaliasdomain' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailaliasdomain_error_notint'),
			),
			'default' => '-1',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_mailmailinglist' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailmailinglist_error_notint'),
			),
			'default' => '-1',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_mailforward' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailforward_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_mailcatchall' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailcatchall_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_mailrouting' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailrouting_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_mailfilter' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailfilter_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_fetchmail' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailfetchmail_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_mailquota' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_mailquota_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_spamfilter_wblist' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_spamfilter_wblist_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_spamfilter_user' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_spamfilter_user_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_spamfilter_policy' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_spamfilter_policy_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),/*
        'default_xmppserver' => array (
            'datatype' => 'INTEGER',
            'formtype' => 'SELECT',
            'default' => '1',
            'datasource' => array (  'type' => 'CUSTOM',
                'class'=> 'custom_datasource',
                'function'=> 'client_servers'
            ),
            'value'  => '',
            'name'  => 'default_xmppserver'
        ),*/
        'xmpp_servers' => array (
            'datatype' => 'VARCHAR',
            'formtype' => 'MULTIPLE',
            'separator' => ',',
            'default' => '1',
            'datasource' => array (  'type' => 'CUSTOM',
                'class'=> 'custom_datasource',
                'function'=> 'client_servers'
            ),
            'validators'    => array (
                0 => array ( 'type' => 'CUSTOM',
                    'class' => 'validate_client',
                    'function' => 'check_used_servers',
                    'errmsg'=> 'xmpp_servers_used'),
            ),
            'value'  => '',
            'name'  => 'xmpp_servers'
        ),
        'limit_xmpp_domain' => array(
            'datatype' => 'INTEGER',
            'formtype' => 'TEXT',
            'validators' => array (  0 => array ( 'type' => 'ISINT',
                'errmsg'=> 'limit_xmpp_domain_error_notint'),
            ),
            'default' => '-1',
            'value'  => '',
            'separator' => '',
            'width'  => '10',
            'maxlength' => '10',
            'rows'  => '',
            'cols'  => ''
        ),
        'limit_xmpp_user' => array(
            'datatype' => 'INTEGER',
            'formtype' => 'TEXT',
            'validators' => array (  0 => array ( 'type' => 'ISINT',
                'errmsg'=> 'limit_xmpp_user_error_notint'),
            ),
            'default' => '-1',
            'value'  => '',
            'separator' => '',
            'width'  => '10',
            'maxlength' => '10',
            'rows'  => '',
            'cols'  => ''
        ),
        'limit_xmpp_muc' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'n',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'limit_xmpp_anon' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'n',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'limit_xmpp_vjud' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'n',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'limit_xmpp_proxy' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'n',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'limit_xmpp_status' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'n',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'limit_xmpp_pastebin' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'n',
            'value'  => array(0 => 'n', 1 => 'y')
        ),
        'limit_xmpp_httparchive' => array(
            'datatype' => 'VARCHAR',
            'formtype' => 'CHECKBOX',
            'default' => 'n',
            'value'  => array(0 => 'n', 1 => 'y')
        ),/*
		'default_webserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '0',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => array(''),
			'name'  => 'default_webserver'
		),*/
		'web_servers' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'MULTIPLE',
			'separator' => ',',
			'default' => '1',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => '',
			'name'  => 'web_servers'
		),
		'limit_web_domain' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_web_domain_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_web_quota' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_web_quota_error_notint'),
			),
			'default' => '-1',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'web_php_options' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOXARRAY',
			'default' => '',
			'separator' => ',',
			'valuelimit' => 'system:sites:web_php_options',
			'value'  => array('no' => 'Disabled', 'fast-cgi' => 'Fast-CGI', 'cgi' => 'CGI', 'mod' => 'Mod-PHP', 'suphp' => 'SuPHP', 'php-fpm' => 'PHP-FPM', 'hhvm' => 'HHVM')
		),
		'limit_cgi' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_ssi' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_perl' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_ruby' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_python' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'force_suexec' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_hterror' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_wildcard' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_ssl' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_ssl_letsencrypt' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_web_aliasdomain' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_web_aliasdomain_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_web_subdomain' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_web_subdomain_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_ftp_user' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_ftp_user_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_shell_user' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_shell_user_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'ssh_chroot' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOXARRAY',
			'validators' => array (
				0 => array (
					'type'=> 'NOTEMPTY',
					'errmsg'=> 'ssh_chroot_notempty'
				),
			),
			'default' => '',
			'separator' => ',',
			'valuelimit' => 'client:ssh_chroot',
			'value'  => array('no' => 'None', 'jailkit' => 'Jailkit')
		),
		'limit_webdav_user' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_webdav_user_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_backup' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'limit_directive_snippets' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'n',
			'value'  => array(0 => 'n', 1 => 'y')
		),/*
		'default_dnsserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '0',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => array(''),
			'name'  => 'default_dnsserver'
		),*/
		'dns_servers' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'MULTIPLE',
			'separator' => ',',
			'default' => '1',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => '',
			'name'  => 'dns_servers'
		),
		'limit_dns_zone' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_dns_zone_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'default_slave_dnsserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '0',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => array(''),
			'name'  => 'default_slave_dnsserver'
		),
		'limit_dns_slave_zone' => array (
			'datatype'      => 'INTEGER',
			'formtype'      => 'TEXT',
			'validators'    => array (      0 => array (    'type'  => 'ISINT',
					'errmsg'=> 'limit_dns_slave_zone_error_notint'),
			),
			'default'       => '0',
			'value'         => '',
			'separator'     => '',
			'width'         => '10',
			'maxlength'     => '10',
			'rows'          => '',
			'cols'          => ''
		),
		'limit_dns_record' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_dns_record_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		/*
		'limit_client' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_client_error_notint'),
									),
			'default'	=> '0',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		*/
		/*
		'default_dbserver' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '0',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => array(''),
			'name'  => 'default_dbserver'
		),*/
		'db_servers' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'MULTIPLE',
			'separator' => ',',
			'default' => '1',
			'datasource' => array (  'type' => 'CUSTOM',
				'class'=> 'custom_datasource',
				'function'=> 'client_servers'
			),
			'value'  => '',
			'name'  => 'db_servers'
		),
		'limit_database' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_database_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_database_user' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_database_user_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_database_quota' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_database_quota_error_notint'),
			),
			'default' => '-1',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_cron' => array (
			'datatype'  => 'INTEGER',
			'formtype'  => 'TEXT',
			'validators'    => array (  0 => array (    'type'  => 'ISINT',
					'errmsg'=> 'limit_cron_error_notint'),
			),
			'default'   => '0',
			'value'     => '',
			'separator' => '',
			'width'     => '10',
			'maxlength' => '10',
			'rows'      => '',
			'cols'      => ''
		),
		'limit_cron_type' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'SELECT',
			'default'   => '',
			'value'     => array('full' => 'Full Cron', 'chrooted' => 'Chrooted Cron', 'url' => 'URL Cron')
		),
		'limit_cron_frequency' => array (
			'datatype'  => 'INTEGER',
			'formtype'  => 'TEXT',
			'validators'    => array (  0 => array (    'type'  => 'ISINT',
					'errmsg'=> 'limit_cron_error_frequency'),
			),
			'default'   => '-1',
			'value'     => '',
			'separator' => '',
			'width'     => '10',
			'maxlength' => '10',
			'rows'      => '',
			'cols'      => ''
		),
		'limit_traffic_quota' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_traffic_quota_error_notint'),
			),
			'default' => '-1',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_openvz_vm' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_openvz_vm_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_openvz_vm_template_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT template_id,template_name FROM openvz_template WHERE 1 ORDER BY template_name',
				'keyfield'=> 'template_id',
				'valuefield'=> 'template_name'
			),
			'value'  => array(0 => ' ')
		),
		'limit_aps' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_aps_error_notint'),
			),
			'default' => '-1',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		'limit_domainmodule' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'ISINT',
					'errmsg'=> 'limit_domainmodule_error_notint'),
			),
			'default' => '0',
			'value'  => '',
			'separator' => '',
			'width'  => '10',
			'maxlength' => '10',
			'rows'  => '',
			'cols'  => ''
		),
		//#################################
		// END Datatable fields
		//#################################
	)
);

?>
