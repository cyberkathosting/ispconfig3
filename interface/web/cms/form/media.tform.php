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

$form["title"] 			= "Media";
$form["description"] 	= "";
$form["name"] 			= "media";
$form["action"]			= "media_edit.php";
$form["db_table"]		= "media";
$form["db_table_idx"]	= "media_id";
$form["tab_default"]	= "media";
$form["list_default"]	= "media_list.php";
$form["auth"]			= 'no';

$app->uses("tree");
$parents = $app->db->queryAllRecords("SELECT * FROM media_cat ORDER BY name");
$app->tree->loadFromArray($parents);
// Damit einträge nicht unter sich selbst eingehängt werden können
// $app->tree->deltree($_REQUEST["id"]);
$parents = $app->tree->optionlist();

$parent[0] = "Root";
if(is_array($parents)) {
	foreach($parents as $p) {
		$tmp_id = $p["id"];
		$parent[$tmp_id] = $p["data"];
	}
}

// Hole Felder
$tmp_records = $app->db->queryAllRecords("SELECT media_profile_id, profile_name FROM media_profile ORDER BY profile_name");
$media_profiles = array();
$media_profiles[0] = "";
foreach($tmp_records as $tmp) {
	$tmp_id = $tmp["media_profile_id"];
	$media_profiles[$tmp_id] = $tmp["profile_name"];
}
unset($tmp_records);



$form["tabs"]['media'] = array (
	'title' 	=> "Media",
	'width' 	=> 80,
	'template' 	=> "templates/media_edit.htm",
	'fields' 	=> array (
	##################################
	# Beginn Datenbankfelder
	##################################
		'media_profile_id' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'SELECT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> $media_profiles,
			'separator'	=> ',',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'media_cat_id' => array (
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
		'media_name' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '/^.{1,255}$/',
			'errmsg'	=> 'media_name_err',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'media_type' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '5',
			'maxlength'	=> '30',
			'rows'		=> '',
			'cols'		=> ''
		),
		'media_size' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '15',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'media_format' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '15',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'thumbnail' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'CHECKBOX',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '0',
			'value'		=> '1',
			'separator'	=> '',
			'width'		=> '',
			'maxlength'	=> '',
			'rows'		=> '',
			'cols'		=> ''
		),
		'path0' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'path1' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'path2' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'path3' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'path4' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'path5' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
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