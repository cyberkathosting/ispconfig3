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

$form["title"] 			= "Media profile";
$form["description"] 	= "";
$form["name"] 			= "media_profile";
$form["action"]			= "media_profile_edit.php";
$form["db_table"]		= "media_profile";
$form["db_table_idx"]	= "media_profile_id";
$form["tab_default"]	= "media_profile";
$form["list_default"]	= "media_profile_list.php";
$form["auth"]			= 'no';

$app->uses("tree");
$parents = $app->db->queryAllRecords("SELECT * FROM media_cat ORDER BY name");
$app->tree->loadFromArray($parents);
$parents = $app->tree->optionlist();

$parent[0] = "Medienkatalog";
if(is_array($parents)) {
	foreach($parents as $p) {
		$tmp_id = $p["id"];
		$parent[$tmp_id] = $p["data"];
	}
}

$form["tabs"]['media_profile'] = array (
	'title' 	=> "Media",
	'width' 	=> 80,
	'template' 	=> "templates/media_profile_edit.htm",
	'fields' 	=> array (
	##################################
	# Beginn Datenbankfelder
	##################################
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
		'profile_name' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '/^.{1,255}$/',
			'errmsg'	=> 'profile_name_err',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '30',
			'maxlength'	=> '255',
			'rows'		=> '',
			'cols'		=> ''
		),
		'profile_description' => array (
			'datatype'	=> 'TEXT',
			'formtype'	=> 'TEXTAREA',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '',
			'maxlength'	=> '',
			'rows'		=> '5',
			'cols'		=> '30'
		),
		'thumbnail' => array (
			'datatype'	=> 'INTEGER',
			'formtype'	=> 'CHECKBOX',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '1',
			'value'		=> '1',
			'separator'	=> '',
			'width'		=> '',
			'maxlength'	=> '',
			'rows'		=> '',
			'cols'		=> ''
		),
		'original' => array (
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
			'value'		=> '[ROOT]/web/media/original/[ID].[EXT]',
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
		'resize1' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
			'rows'		=> '',
			'cols'		=> ''
		),
		'options1' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
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
		'resize2' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
			'rows'		=> '',
			'cols'		=> ''
		),
		'options2' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
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
		'resize3' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
			'rows'		=> '',
			'cols'		=> ''
		),
		'options3' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
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
		'resize4' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
			'rows'		=> '',
			'cols'		=> ''
		),
		'options4' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
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
		),
		'resize5' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
			'rows'		=> '',
			'cols'		=> ''
		),
		'options5' => array (
			'datatype'	=> 'VARCHAR',
			'formtype'	=> 'TEXT',
			'regex'		=> '',
			'errmsg'	=> '',
			'default'	=> '',
			'value'		=> '',
			'separator'	=> '',
			'width'		=> '10',
			'maxlength'	=> '50',
			'rows'		=> '',
			'cols'		=> ''
		)
	##################################
	# ENDE Datenbankfelder
	##################################
	)
);


?>