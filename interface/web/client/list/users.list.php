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
$liste["name"] 				= "users";

// Datenbank Tabelle
$liste["table"] 			= "sys_user";

// Index Feld der datenbank
$liste["table_idx"]			= "userid";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Einträge pro Seite
$liste["records_per_page"] 	= 15;

// Script File der Liste
$liste["file"]				= "users_list.php";

// Script File der Liste
$liste["edit_file"]			= "users_edit.php";

// Script File der Liste
$liste["delete_file"]		= "users_del.php";

// Paging Template
$liste["paging_tpl"]		= "templates/paging.tpl.htm";

// Script File der Liste
$liste["auth"]				= "no";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(	'field'		=> "username",
							'datatype'	=> "VARCHAR",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "");

$liste["item"][] = array(	'field'		=> "vorname",
							'datatype'	=> "VARCHAR",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "");

$liste["item"][] = array(	'field'		=> "name",
							'datatype'	=> "VARCHAR",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "");  

$liste["item"][] = array(	'field'		=> "ort",
							'datatype'	=> "VARCHAR",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "");

?>