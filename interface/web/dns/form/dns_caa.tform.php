<?php

global $app;

$form['title'] = 'DNS CAA Record';
$form['description'] = '';
$form['name'] = 'dns_caa';
$form['action'] = 'dns_caa_edit.php';
$form['db_table'] = 'dns_rr';
$form['db_table_idx'] = 'id';
$form['db_history'] = 'yes';
$form['tab_default'] = 'dns';
$form['list_default'] = 'dns_a_list.php';
$form['auth'] = 'yes';

$form['auth_preset']['userid']  = 0;
$form['auth_preset']['groupid'] = 0;
$form['auth_preset']['perm_user'] = 'riud';
$form['auth_preset']['perm_group'] = 'riud';
$form['auth_preset']['perm_other'] = '';

$form['tabs']['dns'] = array (
	'title'  => 'DNS CAA',
	'width'  => 100,
	'template'  => 'templates/dns_caa_edit.htm',
	'fields'  => array (
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'zone' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => @$app->functions->intval($_REQUEST['zone']),
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'name' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array( 0 => array( 'event' => 'SAVE',
					'type' => 'IDNTOASCII'),
				1 => array( 'event' => 'SHOW',
					'type' => 'IDNTOUTF8'),
				2 => array( 'event' => 'SAVE',
					'type' => 'TOLOWER')
			),
			'validators' => array (  0 => array ( 'type' => 'REGEX',
					'regex' => '/^[a-zA-Z0-9\.\-\_]{0,255}$/',
					'errmsg'=> 'name_error_regex'),
			),
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'type' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => 'CAA',
			'value'  => '',
			'width'  => '5',
			'maxlength' => '5'
		),
		'data' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'ttl' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'validators' => array ( 0 => array ( 'type' => 'RANGE',
							'range' => '60:',
							'errmsg'=> 'ttl_range_error'),
			),
			'default' => '3600',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '10'
		),
		'active' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'Y',
			'value'  => array(0 => 'N', 1 => 'Y')
		),
		'stamp' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '30',
			'maxlength' => '255'
		),
		'serial' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'TEXT',
			'default' => '',
			'value'  => '',
			'width'  => '10',
			'maxlength' => '10'
		),
	)
);



?>
