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
$liste["name"] 				= "media";

// Datenbank Tabelle
$liste["table"] 			= "media";

// Index Feld der datenbank
$liste["table_idx"]			= "media_id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Einträge pro Seite
$liste["records_per_page"] 	= 15;

// Script File der Liste
$liste["file"]				= "media_list.php";

// Script File der Liste
$liste["edit_file"]			= "media_edit.php";

// Script File der Liste
$liste["delete_file"]		= "media_del.php";

// Paging Template
$liste["paging_tpl"]		= "templates/paging.tpl.htm";

// Script File der Liste
$liste["auth"]				= "no";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(	'field'		=> "media_name",
							'datatype'	=> "VARCHAR",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "");

$liste["item"][] = array(	'field'		=> "media_type",
							'datatype'	=> "VARCHAR",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "");


$liste["item"][] = array(	'field'		=> "media_cat_id",
							'datatype'	=> "VARCHAR",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "");
?>