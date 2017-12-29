<?php

$liste["name"]     = "iptables";
$liste["table"]    = "iptables";
$liste["table_idx"]   = "iptables_id";
$liste["search_prefix"]  = "search_";
$liste["records_per_page"]  = "15";
$liste["file"]    = "iptables_list.php";
$liste["edit_file"]   = "iptables_edit.php";
$liste["delete_file"]  = "iptables_del.php";
$liste["paging_tpl"]  = "templates/paging.tpl.htm";
$liste["auth"]    = "yes";

$liste["item"][] = array( 'field'  => "active",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => array("y" => $app->lng('yes_txt'), "n" => $app->lng('no_txt')));

$liste["item"][] = array( 'field'  => "server_id",
	'datatype' => "INTEGER",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'datasource' => array (  'type' => "SQL",
		'querystring' => "SELECT server_id,server_name FROM server WHERE {AUTHSQL} AND db_server = 1 ORDER BY server_name",
		'keyfield'=> "server_id",
		'valuefield'=> "server_name"),
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "singleport",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => "");

$liste["item"][] = array( 'field'  => "multiport",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => "");

$liste["item"][] = array(   'field'     => "protocol",
	'datatype'  => "VARCHAR",
	'formtype'  => "SELECT",
	'op'        => "=",
	'prefix'    => "",
	'suffix'    => "",
	'width'     => "",
	'value'     => array('none'=>'None', 'tcp' => "TCP", 'udp' => "UDP"));

$liste["item"][] = array(   'field'     => "table",
	'datatype'  => "VARCHAR",
	'formtype'  => "SELECT",
	'op'        => "=",
	'prefix'    => "",
	'suffix'    => "",
	'width'     => "",
	'value'     => array('INPUT' => "INPUT", 'OUTPUT' => "OUTPUT", 'FORWARD' => "FORWARD"));

$liste["item"][] = array(   'field'     => "source_ip",
	'datatype'  => "VARCHAR",
	'formtype'  => "TEXT",
	'op'        => "like",
	'prefix'    => "%",
	'suffix'    => "%",
	'width'     => "16",
	'value'     => "");

$liste["item"][] = array(   'field'     => "destination_ip",
	'datatype'  => "VARCHAR",
	'formtype'  => "TEXT",
	'op'        => "like",
	'prefix'    => "%",
	'suffix'    => "%",
	'width'     => "16",
	'value'     => "");

$liste["item"][] = array( 'field'  => "target",
	'datatype' => "VARCHAR",
	'formtype' => "SELECT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'     => array('ACCEPT' => "ACCEPT", 'DROP' => "DROP", 'REJECT' => "REJECT", 'LOG' => "LOG"));

$liste["item"][] = array( 'field'  => "state",
	'datatype' => "VARCHAR",
	'formtype' => "TEXT",
	'op'  => "=",
	'prefix' => "",
	'suffix' => "",
	'width'  => "",
	'value'  => "");
?>
