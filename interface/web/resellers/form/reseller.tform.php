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

$form["title"] 			= "Reseller";
$form["description"] 	= "";
$form["name"] 			= "reseller";
$form["action"]			= "reseller_edit.php";
$form["db_table"]		= "reseller";
$form["db_table_idx"]	= "reseller_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "address";
$form["list_default"]	= "reseller_list.php";
$form["auth"]			= 'yes';

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['address'] = array (
	'title' 	=> "Address",
	'width' 	=> 100,
	'template' 	=> "templates/reseller_edit_address.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'company' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'title' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'default'	=> '',
			'value'		=> array('Mrs.' => 'Mrs.','Mr.'=>'Mr.','Company'=>'Company'),
			'separator'	=> '',
			'width'		=> '',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'firstname' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'firstname_error_empty'),
									),
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'surname' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'surname_error_empty'),
										),
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'street' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'zip' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'city' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'country' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'telephone' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'mobile' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'fax' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'email' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'internet' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> 'http://',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'icq' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'notes' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'TEXTAREA',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '',
			'maxlength'	=> '',
			'rows'		=> '10',
			'cols'		=> '30'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

$form["tabs"]['limits'] = array (
	'title' 	=> "Limits",
	'width' 	=> 80,
	'template' 	=> "templates/reseller_edit_limits.htm",
	'fields' 	=> array (
	##################################
	# Beginn Datatable fields
	##################################
		'limit_client' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_client_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_domain' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_domain_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_subdomain' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_client_subdomain_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailbox' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailbox_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailalias' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailalias_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_webquota' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_webquota_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_mailquota' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_mailquota_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
		'limit_database' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'ISINT',
														'errmsg'=> 'limit_database_error_notint'),
									),
			'default'	=> '-1',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '10',
			'rows'		=> '',
			'cols'		=> ''
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);

$form["tabs"]['users'] = array (
	'title' 	=> "CP Users",
	'width' 	=> 100,
	'template' 	=> "templates/reseller_edit_users.htm",
	'fields' 	=> array (
	##################################
	# Beginn Datatable fields
	##################################
		
	##################################
	# ENDE Datatable fields
	##################################
	),
	'plugins' => array ( 
		'cpuser_list' => array (
			'class' 		=> 'plugin_listview',
			'options'		=> array('listdef' => 'list/users.list.php')
		)
	)
);

$form["tabs"]['ipaddress'] = array (
	'title' 	=> "IP Addresses",
	'width' 	=> 100,
	'template' 	=> "templates/reseller_edit_ipaddress.htm",
	'fields' 	=> array (
	##################################
	# Beginn Datatable fields
	##################################
		'ip_address' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'CHECKBOXARRAY',
			'default'	=> '',
			'value'		=> array('192.168.0.1' => '192.168.0.1', '192.168.0.2' => '192.168.0.2'),
			'separator'	=> ';'
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);



?>