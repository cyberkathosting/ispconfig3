<?php

/*
	Datentypen:
	- INTEGER (Wandelt Ausdrücke in Int um)
	- DOUBLE
	- CURRENCY (Formatiert Zahlen nach Währungsnotation)
	- VARCHAR (kein weiterer Format Check)
	- TEXT (kein weiterer Format Check)
	- DATE (Datumsformat, Timestamp Umwandlung)
*/



// Name der Liste
$liste["name"] 				= "server_ip";

// Datenbank Tabelle
$liste["table"] 			= "server_ip";

// Index Feld der datenbank
$liste["table_idx"]			= "server_ip_id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Einträge pro Seite
$liste["records_per_page"] 	= 15;

// Script File der Liste
$liste["file"]				= "server_ip_list.php";

// Script File der Liste
$liste["edit_file"]			= "server_ip_edit.php";

// Script File der Liste
$liste["delete_file"]		= "server_ip_del.php";

// Paging Template
$liste["paging_tpl"]		= "templates/paging.tpl.htm";

// Script File der Liste
$liste["auth"]				= "no";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(	'field'		=> "server_id",
							'datatype'	=> "VARCHAR",
							'formtype'	=> "SELECT",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'datasource'	=> array ( 	'type'	=> 'SQL',
														'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} ORDER BY server_name',
														'keyfield'=> 'server_id',
														'valuefield'=> 'server_name'
									 				  ),
							'width'		=> "",
							'value'		=> "");

$liste["item"][] = array(	'field'		=> "ip_address",
							'datatype'	=> "VARCHAR",
							'op'		=> "=",
							'prefix'	=> "",
							'suffix'	=> "",
							'width'		=> "");  



?>