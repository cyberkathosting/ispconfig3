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

$form["title"]    = "Openvz virtual server";
$form["description"]  = "";
$form["name"]    = "openvz_vm";
$form["action"]   = "openvz_vm_edit.php";
$form["db_table"]  = "openvz_vm";
$form["db_table_idx"] = "vm_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "main";
$form["list_default"] = "openvz_vm_list.php";
$form["auth"]   = 'yes'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['main'] = array (
	'title'  => "Virtual server",
	'width'  => 100,
	'template'  => "templates/openvz_vm_edit.htm",
	'fields'  => array (
		//#################################
		// Begin Datatable fields
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE vserver_server = 1 AND mirror_server_id = 0 ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'
			),
			'value'  => ''
		),
		'ostemplate_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT ostemplate_id,template_name FROM openvz_ostemplate WHERE 1 ORDER BY template_name',
				'keyfield'=> 'ostemplate_id',
				'valuefield'=> 'template_name'
			),
			'value'  => ''
		),
		'template_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			/*
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => 'SELECT template_id,template_name FROM openvz_template WHERE 1 ORDER BY template_name',
										'keyfield'=> 'template_id',
										'valuefield'=> 'template_name'
									 ),
			*/
			'value'  => ''
		),
		'ip_address' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'ip_address_error_empty'),
			),
			'default' => '',
			/*
			'datasource'	=> array ( 	'type'	=> 'SQL',
										'querystring' => "SELECT ip_address FROM openvz_ip WHERE reserved = 'n' AND (vm_id = 0 or vm_id = '{RECORDID}') ORDER BY ip_address",
										'keyfield'=> 'ip_address',
										'valuefield'=> 'ip_address'
									 ),
			*/
			'value'  => ''
		),
		'hostname' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
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
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'hostname_error_empty'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255',
			'searchable' => 1
		),
		'vm_password' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
					'errmsg'=> 'vm_password_error_empty'),
			),
			'default' => $app->auth->get_random_password(10),
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'start_boot' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'bootorder' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '0',
			'value'  => '',
			'width'  => '3',
			'maxlength' => '3',
			'validators' => array (  0 => array ( 'type' => 'ISPOSITIVE',
				'errmsg'=> 'bootorder_error_notpositive'),
            ),
		),
		'active' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'active_until_date' => array (
			'datatype' => 'DATE',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'description' => array (
			'datatype' => 'TEXT',
			'formtype' => 'TEXTAREA',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS')
			),
			'default' => '',
			'value'  => '',
			'separator' => '',
			'width'  => '',
			'maxlength' => '',
			'rows'  => '10',
			'cols'  => '30',
			'searchable' => 2
		),
		//#################################
		// ENDE Datatable fields
		//#################################
	)
);

if($_SESSION["s"]["user"]["typ"] == 'admin') {
	$form["tabs"]['advanced'] = array (
		'title'  => "Advanced",
		'width'  => 100,
		'template'  => "templates/openvz_vm_advanced_edit.htm",
		'fields'  => array (
			//#################################
			// Begin Datatable fields
			//#################################
			'veid' => array (
				'datatype' => 'INTEGER',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'veid_error_empty'),
					1 => array ( 'type' => 'UNIQUE',
						'errmsg'=> 'veid_error_unique'),
				),
				'default' => '',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'create_dns' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'CHECKBOX',
				'default' => 'y',
				'value'  => array(0 => 'n', 1 => 'y')
			),
			'diskspace' => array (
				'datatype' => 'INTEGER',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'diskspace_error_empty'),
				),
				'default' => '',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'ram' => array (
				'datatype' => 'INTEGER',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'ram_error_empty'),
				),
				'default' => '',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'ram_burst' => array (
				'datatype' => 'INTEGER',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'ram_burst_error_empty'),
				),
				'default' => '',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'cpu_units' => array (
				'datatype' => 'INTEGER',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'cpu_units_error_empty'),
				),
				'default' => '1000',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'cpu_num' => array (
				'datatype' => 'INTEGER',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'cpu_num_error_empty'),
				),
				'default' => '4',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'cpu_limit' => array (
				'datatype' => 'INTEGER',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'cpu_limit_error_empty'),
				),
				'default' => '400',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'io_priority' => array (
				'datatype' => 'INTEGER',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'io_priority_error_empty'),
				),
				'default' => '4',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'nameserver' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'TEXT',
				'validators' => array (  0 => array ( 'type' => 'NOTEMPTY',
						'errmsg'=> 'template_nameserver_error_empty'),
				),
				'default' => '8.8.8.8 8.8.4.4',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'capability' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'TEXT',
				'default' => '',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'features' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'TEXT',
				'default' => '',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'iptables' => array (
				'datatype' => 'VARCHAR',
				'formtype' => 'TEXT',
				'default' => '',
				'value'  => '',
				'width'  => '30',
				'maxlength' => '255'
			),
			'custom' => array (
				'datatype' => 'TEXT',
				'formtype' => 'TEXTAREA',
				'default' => '',
				'value'  => '',
				'separator' => '',
				'width'  => '',
				'maxlength' => '',
				'rows'  => '10',
				'cols'  => '30',
				'searchable' => 2,
				'validators'    => array (  0 => array ('type'  => 'CUSTOM',
					'class' => 'validate_openvz',
					'function' => 'check_custom',
					'errmsg'=> 'custom_error'),
				),
			),
			//#################################
			// ENDE Datatable fields
			//#################################
		)
	);

	$form["tabs"]['additional_ip'] = array (
		'title'  => "Additional IP",
		'width'  => 100,
		'template'  => "templates/openvz_vm_additional_ip_edit.htm",
	);
}


?>
