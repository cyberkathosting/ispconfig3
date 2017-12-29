<?php

$liste['name']     = 'server_ip_map';
$liste['table']    = 'server_ip_map';
$liste['table_idx']   = 'server_ip_map_id';
$liste['search_prefix']  = 'search_';
$liste['records_per_page']  = "15";
$liste['file']    = 'server_ip_map_list.php';
$liste['edit_file']   = 'server_ip_map_edit.php';
$liste['delete_file']  = 'server_ip_del.php';
$liste['paging_tpl']  = 'templates/paging.tpl.htm';
$liste['auth']    = 'no';

$liste["item"][] = array( 'field'  => "active",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array('y' => $app->lng('yes_txt'), 'n' => $app->lng('no_txt')));

$liste['item'][] = array( 'field'  => 'server_id',
	'datatype' => 'INTEGER',
	'formtype' => 'SELECT',
	'op'  => '=',
	'prefix' => '',
	'suffix' => '',
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} AND mirror_server_id <> 0 ORDER BY server_name',
		'keyfield'=> 'server_id',
		'valuefield'=> 'server_name'
	),
	'width'  => '',
	'value'  => '');

$liste['item'][] = array( 'field'  => 'source_ip',
	'datatype' => 'VARCHAR',
	'op'  => '=',
	'prefix' => '',
	'suffix' => '',
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT server_ip_map_id,source_ip FROM server_ip_map WHERE {AUTHSQL}',
		'keyfield'=> 'server_ip_map_id',
		'valuefield'=> 'source_ip'
	),
	'width'  => '',
	'value'  => '');

$liste['item'][] = array( 'field'  => 'destination_ip',
	'datatype' => 'VARCHAR',
	'op'  => '=',
	'prefix' => '',
	'suffix' => '',
	'datasource' => array (  'type' => 'SQL',
		'querystring' => 'SELECT server_ip_map_id,destination_ip FROM server_ip_map WHERE {AUTHSQL}',
		'keyfield'=> 'server_ip_map_id',
		'valuefield'=> 'destination_ip'
	),
	'width'  => '',
	'value'  => '');
?>
