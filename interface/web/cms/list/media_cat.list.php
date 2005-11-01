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
$liste["name"] 				= "media_cat";

// Datenbank Tabelle
$liste["table"] 			= "media_cat";

// Index Feld der datenbank
$liste["table_idx"]			= "media_cat_id";

// Search Field Prefix
$liste["search_prefix"] 	= "search_";

// Einträge pro Seite
$liste["records_per_page"] 	= 15;

// Script File der Liste
$liste["file"]				= "media_cat_list.php";

// Script File der Liste
$liste["edit_file"]			= "media_cat_edit.php";

// Script File der Liste
$liste["delete_file"]		= "media_cat_del.php";

// Paging Template
$liste["paging_tpl"]		= "templates/paging.tpl.htm";

// Script File der Liste
$liste["auth"]				= "no";


/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(	'field'		=> "name",
							'datatype'	=> "VARCHAR",
							'op'		=> "like",
							'prefix'	=> "%",
							'suffix'	=> "%",
							'width'		=> "");

?>