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

$function_list = array();
$function_list['mail_domain_get,mail_domain_add,mail_domain_update,mail_domain_delete'] = 'Mail domain functions<br>';
$function_list['mail_user_get,mail_user_add,mail_user_update,mail_user_delete'] = 'Mail user functions<br>';
$function_list['mail_alias_get,mail_alias_add,mail_alias_update,mail_alias_delete'] = 'Mail alias functions<br>';
$function_list['mail_forward_get,mail_forward_add,mail_forward_update,mail_forward_delete'] = 'Mail forward functions<br>';
$function_list['mail_catchall_get,mail_catchall_add,mail_catchall_update,mail_catchall_delete'] = 'Mail catchall functions<br>';
$function_list['mail_transport_get,mail_transport_add,mail_transport_update,mail_transport_delete'] = 'Mail transport functions<br>';
$function_list['mail_whitelist_get,mail_whitelist_add,mail_whitelist_update,mail_whitelist_delete'] = 'Mail whitelist functions<br>';
$function_list['mail_blacklist_get,mail_blacklist_add,mail_blacklist_update,mail_blacklist_delete'] = 'Mail blacklist functions<br>';
$function_list['mail_spamfilter_user_get,mail_spamfilter_user_add,mail_spamfilter_user_update,mail_spamfilter_user_delete'] = 'Mail spamfilter user functions<br>';
$function_list['mail_policy_get,mail_policy_add,mail_policy_update,mail_policy_delete'] = 'Mail spamfilter policy functions<br>';
$function_list['mail_fetchmail_get,mail_fetchmail_add,mail_fetchmail_update,mail_fetchmail_delete'] = 'Mail fetchmail functions<br>';
$function_list['mail_whitelist_get,mail_whitelist_add,mail_whitelist_update,mail_whitelist_delete'] = 'Mail whitelist functions<br>';
$function_list['mail_blacklist_get,mail_blacklist_add,mail_blacklist_update,mail_blacklist_delete'] = 'Mail blacklist functions<br>';
$function_list['mail_filter_get,mail_filter_add,mail_filter_update,mail_filter_delete'] = 'Mail filter functions<br>';

$function_list['client_get,client_add,client_update,client_delete'] = 'Client functions<br>';
$function_list['sites_cron_get,sites_cron_add,sites_cron_update,sites_cron_delete'] = 'Sites cron functions<br>';
$function_list['sites_database_get,sites_database_add,sites_database_update,sites_database_delete'] = 'Sites database functions<br>';
$function_list['sites_ftp_user_get,sites_ftp_user_add,sites_ftp_user_update,sites_ftp_user_delete'] = 'Sites FTP-User functions<br>';
$function_list['sites_shell_user_get,sites_shell_user_add,sites_shell_user_update,sites_shell_user_delete'] = 'Sites Shell-User functions<br>';
$function_list['sites_web_domain_get,sites_web_domain_add,sites_web_domain_update,sites_web_domain_delete'] = 'Sites Domain functions<br>';
$function_list['sites_web_aliasdomain_get,sites_web_aliasdomain_add,sites_web_aliasdomain_update,sites_web_aliasdomain_delete'] = 'Sites Aliasdomain functions<br>';
$function_list['sites_web_subdomain_get,sites_web_subdomain_add,sites_web_subdomain_update,sites_web_subdomain_delete'] = 'Sites Subdomain functions<br>';
$function_list['dns_zone_get,dns_zone_add,dns_zone_update,dns_zone_delete'] = 'DNS zone functions<br>';
$function_list['dns_a_get,dns_a_add,dns_a_update,dns_a_delete'] = 'DNS a functions<br>';
$function_list['dns_aaaa_get,dns_aaaa_add,dns_aaaa_update,dns_aaaa_delete'] = 'DNS aaaa functions<br>';
$function_list['dns_alias_get,dns_alias_add,dns_alias_update,dns_alias_delete'] = 'DNS alias functions<br>';
$function_list['dns_cname_get,dns_cname_add,dns_cname_update,dns_cname_delete'] = 'DNS cname functions<br>';
$function_list['dns_hinfo_get,dns_hinfo_add,dns_hinfo_update,dns_hinfo_delete'] = 'DNS hinfo functions<br>';
$function_list['dns_mx_get,dns_mx_add,dns_mx_update,dns_mx_delete'] = 'DNS mx functions<br>';
$function_list['dns_ns_get,dns_ns_add,dns_ns_update,dns_ns_delete'] = 'DNS ns functions<br>';
$function_list['dns_ptr_get,dns_ptr_add,dns_ptr_update,dns_ptr_delete'] = 'DNS ptr functions<br>';
$function_list['dns_rp_get,dns_rp_add,dns_rp_update,dns_rp_delete'] = 'DNS rp functions<br>';
$function_list['dns_srv_get,dns_srv_add,dns_srv_update,dns_srv_delete'] = 'DNS srv functions<br>';
$function_list['dns_txt_get,dns_txt_add,dns_txt_update,dns_txt_delete'] = 'DNS txt functions<br>';


$form["title"] 			= "Remote user";
$form["description"] 	= "";
$form["name"] 			= "remote_user";
$form["action"]			= "remote_user_edit.php";
$form["db_table"]		= "remote_user";
$form["db_table_idx"]	= "remote_userid";
$form["db_history"]		= "yes";
$form["tab_default"]	= "remote_user";
$form["list_default"]	= "remote_user_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['remote_user'] = array (
	'title' 	=> "Remote User",
	'width' 	=> 100,
	'template' 	=> "templates/remote_user_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'remote_userid' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT remote_userid,remote_username FROM remote_user WHERE {AUTHSQL} ORDER BY remote_username',
										'keyfield'=> 'remote_userid',
										'valuefield'=> 'remote_username'
									 ),
			'value'		=> ''
		),
		
		'remote_username' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'UNIQUE',
														'errmsg'=> 'username_error_unique'),
										1 => array (	'type'	=> 'REGEX',
														'regex' => '/^[\w\.\-]{0,64}$/',
														'errmsg'=> 'username_error_regex'),
2 => array (	'type' => 'NOTEMPTY',
		'errmsg'=> 'username_error_empty'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'remote_password' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'PASSWORD',
			'encryption' => 'MD5',
			'default'	=> '',
			'value'		=> '',
			'width'		=> '30',
			'maxlength'	=> '255'
		),
		'remote_functions' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'CHECKBOXARRAY',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> $function_list,
			'separator'	=> ';',
			'width'		=> '',
			'maxlength'	=> '',
			'rows'		=> '5',
			'cols'		=> '30'
		)
		
	##################################
	# ENDE Datatable fields
	##################################
	)
);





?>
