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

$form["title"] 			= "DNS Wizard template";
$form["description"] 	= "";
$form["name"] 			= "dns_template";
$form["action"]			= "dns_template_edit.php";
$form["db_table"]		= "dns_template";
$form["db_table_idx"]	= "template_id";
$form["db_history"]		= "yes";
$form["tab_default"]	= "template";
$form["list_default"]	= "dns_template_list.php";
$form["auth"]			= 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['template'] = array (
	'title' 	=> "DNS Template",
	'width' 	=> 100,
	'template' 	=> "templates/dns_template_edit.htm",
	'fields' 	=> array (
	##################################
	# Begin Datatable fields
	##################################
		'name' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'validators'	=> array ( 	0 => array (	'type'	=> 'NOTEMPTY',
														'errmsg'=> 'name_error_empty'),
									),
			'default'	=> '',
			'value'		=> '',
			'width'		=> '40',
			'maxlength'	=> '255'
		),
		'fields' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOXARRAY',
			'default'	=> '',
			'separator' => ',',
			'value'		=> array('DOMAIN' => 'Domain','IP' => 'IP Address','NS1' => 'NS 1','NS2' => 'NS 2','EMAIL' => 'Email')
		),
		'template' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'TEXTAREA',
			'default'	=> '',
			'value'		=> '',
			'cols'		=> '40',
			'rows'		=> '15'
		),
		'visible' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'CHECKBOX',
			'default'	=> 'y',
			'value'		=> array(0 => 'n',1 => 'y')
		),
	##################################
	# ENDE Datatable fields
	##################################
	)
);



?>