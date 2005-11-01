<?php

/*
	Form Definition

	Tabellendefinition

	Datentypen:
	- INTEGER (Wandelt Ausdrücke in Int um)
	- DOUBLE
	- CURRENCY (Formatiert Zahlen nach Währungsnotation)
	- VARCHAR (kein weiterer Format Check)
	- TEXT (kein weiterer Format Check)
	- DATE (Datumsformat, Timestamp Umwandlung)

	Formtype:
	- TEXT (normales Textfeld)
	- TEXTAREA (normales Textfeld)
	- PASSWORD (Feldinhalt wird nicht angezeigt)
	- SELECT (Gibt Werte als option Feld aus)
	- RADIO
	- CHECKBOX
	- CHECKBOXARRAY
	- FILE

	VALUE:
	- Wert oder Array

	Hinweis:
	Das ID-Feld ist nicht bei den Table Values einzufügen.


*/

$form["title"] 			= "Media Categories";
$form["description"] 	= "Form to edit Media Categories.";
$form["name"] 			= "media_cat";
$form["action"]			= "media_cat_edit.php";
$form["db_table"]		= "media_cat";
$form["db_table_idx"]	= "media_cat_id";
$form["tab_default"]	= "media_cat";
$form["list_default"]	= "media_cat_list.php";
$form["auth"]			= 'no';

$app->uses("tree");
$parents = $app->db->queryAllRecords("SELECT * FROM media_cat ORDER BY name");
$app->tree->loadFromArray($parents);
// Damit einträge nicht unter sich selbst eingehängt werden können
$app->tree->deltree($_REQUEST["id"]);
$parents = $app->tree->optionlist();

$parent[0] = "Root";
if(is_array($parents)) {
	foreach($parents as $p) {
		$tmp_id = $p["id"];
		$parent[$tmp_id] = $p["data"];
	}
}



$form["tabs"]['media_cat'] = array (
	'title' 	=> "Categorie",
	'width' 	=> 80,
	'template' 	=> "templates/media_cat_edit.htm",
	'fields' 	=> array (
	##################################
	# Beginn Datenbankfelder
	##################################
		'name' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '/^.{1,30}$/',
			'errmsg'	=> 'name_err',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'parent' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> $parent,
			'separator'	=> ',',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'sort' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '15',
			'maxlength'	=> '30',
			'rows'		=> '',
			'cols'		=> ''
		),
		'active' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'CHECKBOX',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '1',
			'value'		=> '1',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		)
	##################################
	# ENDE Datenbankfelder
	##################################
	)
);


?>