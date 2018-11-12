<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

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
	- FILE

	VALUE:
	- Wert oder Array

	Hinweis:
	Das ID-Feld ist nicht bei den Table Values einzufügen.


*/

$form["title"]    = "Groups";
$form["description"]  = "groups_description";
$form["name"]    = "groups";
$form["action"]   = "groups_edit.php";
$form["db_table"]  = "sys_group";
$form["db_table_idx"] = "groupid";
$form["db_history"]  = "yes";
$form["tab_default"] = "groups";
$form["list_default"] = "groups_list.php";
$form["auth"]   = 'no';

$form["tabs"]['groups'] = array (
	'title'  => "Groups",
	'width'  => 80,
	'template'  => "templates/groups_edit.htm",
	'fields'  => array (
		//#################################
		// Beginn Datenbankfelder
		//#################################
		'name' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS'),
					1 => array( 'event' => 'SAVE',
					'type' => 'STRIPNL')
			),
			'regex'  => '/^.{1,30}$/',
			'errmsg' => 'name_err',
			'default' => '',
			'value'  => '',
			'separator' => '',
			'width'  => '30',
			'maxlength' => '255',
			'rows'  => '',
			'cols'  => ''
		),
		'description' => array (
			'datatype' => 'TEXT',
			'formtype' => 'TEXTAREA',
			'filters'   => array(
					0 => array( 'event' => 'SAVE',
					'type' => 'STRIPTAGS')
			),
			'regex'  => '',
			'errmsg' => '',
			'default' => '',
			'value'  => '',
			'separator' => '',
			'width'  => '',
			'maxlength' => '',
			'rows'  => '5',
			'cols'  => '30'
		)
		//#################################
		// ENDE Datenbankfelder
		//#################################
	)
);
?>
