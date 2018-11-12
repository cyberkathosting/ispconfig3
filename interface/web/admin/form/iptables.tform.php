<?php

$form["title"]    = "IPTables";
$form["description"]  = "IPTables based firewall";
$form["name"]    = "iptables";
$form["action"]   = "iptables_edit.php";
$form["db_table"]  = "iptables";
$form["db_table_idx"] = "iptables_id";
$form["db_history"]  = "no";
$form["tab_default"] = "iptables";
$form["list_default"] = "iptables_list.php";
//$form["auth"]   = 'yes'; // yes / no

//$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
//$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
//$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
//$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
//$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['iptables'] = array (
	'title'  => "Rules",
	'width'  => "100",
	'template'  => "templates/iptables_edit.htm",
	'fields'  => array (
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'),
			'value'  => ''
		),
		'protocol' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('none'=>'None', 'tcp'=>'TCP', 'udp'=>'UDP'),
			'width'  => '',
			'maxlength' => ''
		),
		'table' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'SELECT',
			'validators'    => array (      0  => array ( 'type' => 'NOTEMPTY', 'errmsg' => 'table_error_empty')),
			'default'       => 'INPUT',
			'value'         => array('INPUT'=>'INPUT', 'OUTPUT'=>'OUTPUT', 'FORWARD'=>'FORWARD'),
			'width'         => '',
			'maxlength'     => ''
		),
		'source_ip' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default'       => '',
			'value'         => '',
			'width'         => '',
			'maxlength'     => ''
		),
		'destination_ip' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default'       => '',
			'value'         => '',
			'width'         => '',
			'maxlength'     => ''
		),
		'singleport' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default'       => '',
			'value'         => '',
			'width'         => '',
			'maxlength'     => ''
		),
		'multiport' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default'       => '',
			'value'         => '',
			'width'         => '',
			'maxlength'     => ''
		),
		'state' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'default'       => '',
			'value'         => '',
			'width'         => '',
			'maxlength'     => ''
		),
		'target' => array (
			'datatype'      => 'VARCHAR',
			'formtype'      => 'SELECT',
			'validators'    => array (      0  => array ( 'type' => 'NOTEMPTY', 'errmsg' => 'target_error_empty')),
			'default'       => '',
			'value'         => array('ACCEPT'=>'ACCEPT', 'DROP'=>'DROP', 'REJECT'=>'REJECT'),
			'width'         => '',
			'maxlength'     => ''
		),
		'active' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
	)
);
?>
