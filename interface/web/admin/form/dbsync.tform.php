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
	- INTEGER (Wandelt Ausdr�cke in Int um)
	- DOUBLE
	- CURRENCY (Formatiert Zahlen nach W�hrungsnotation)
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
	Das ID-Feld ist nicht bei den Table Values einzuf�gen.


*/

//* Load modules
$modules_list = array();
$handle = @opendir(ISPC_WEB_PATH);
while ($file = @readdir($handle)) {
	if ($file != "." && $file != "..") {
		if(@is_dir(ISPC_WEB_PATH."/$file")) {
			if(is_file(ISPC_WEB_PATH."/$file/lib/module.conf.php") and $file != 'login') {
				$modules_list[$file] = $file;
			}
		}
	}
}
closedir($handle);

//* read data bases in with more activated db_history.
$db_tables = array();
foreach($modules_list as $md) {
	$handle = @opendir(ISPC_WEB_PATH."/$md/form");
	while ($file = @readdir($handle)) {
		if ($file != '.' && $file != '..' && substr($file, 0, 1) != '.') {
			include_once ISPC_WEB_PATH."/$md/form/$file";
			if(isset($form['db_history']) && $form['db_history'] == 'yes') {
				$tmp_id = $form['db_table'];
				$db_tables[$tmp_id] = $form['db_table'];
			}
			unset($form);
		}
	}
	closedir($handle);
}
unset($form);


$form['title']          = 'DB sync';
$form['description']    = 'ISPConfig database synchronisation tool.';
$form['name']           = 'dbsync';
$form['action']         = 'dbsync_edit.php';
$form['db_table']       = 'sys_dbsync';
$form['db_table_idx']   = 'id';
$form['tab_default']    = 'dbsync';
$form['list_default']   = 'dbsync_list.php';
$form['auth']           = 'no';


$form['tabs']['dbsync'] = array (
	'title'     => 'DB sync',
	'width'     => 80,
	'template'  => 'templates/dbsync_edit.htm',
	'fields'    => array (
		//#################################
		// Beginn Datenbankfelder
		//#################################
		'jobname' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'regex'     => '/^.{1,30}$/',
			'errmsg'    => 'jobname_err',
			'default'   => '',
			'value'     => '',
			'separator' => '',
			'width'     => '15',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'sync_interval_minutes' => array (
			'datatype'  => 'INTEGER',
			'formtype'  => 'TEXT',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => '',
			'value'     => '',
			'separator' => '',
			'width'     => '15',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'db_type' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'SELECT',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => '',
			'value'     => array('mysql' => 'mysql'),
			'separator' => '',
			'width'     => '30',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'db_host' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => '',
			'value'     => '',
			'separator' => '',
			'width'     => '30',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'db_name' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => '',
			'value'     => '',
			'separator' => '',
			'width'     => '30',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'db_username' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => '',
			'value'     => '',
			'separator' => '',
			'width'     => '30',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'db_password' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'TEXT',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => '',
			'value'     => '',
			'separator' => '',
			'width'     => '30',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'db_tables' => array (
			'datatype'  => 'VARCHAR',
			'formtype'  => 'CHECKBOXARRAY',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => 'admin,forms',
			'value'     => $db_tables,
			'separator' => ',',
			'width'     => '30',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'empty_datalog' => array (
			'datatype'  => 'INTEGER',
			'formtype'  => 'CHECKBOX',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => '',
			'value'     => array(0 => 0, 1 => 1),
			'separator' => '',
			'width'     => '30',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'sync_datalog_external' => array (
			'datatype'  => 'INTEGER',
			'formtype'  => 'CHECKBOX',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => '',
			'value'     => array(0 => 0, 1 => 1),
			'separator' => '',
			'width'     => '30',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		),
		'active' => array (
			'datatype'  => 'INTEGER',
			'formtype'  => 'CHECKBOX',
			'regex'     => '',
			'errmsg'    => '',
			'default'   => '1',
			'value'     => array(0 => 0, 1 => 1),
			'separator' => '',
			'width'     => '30',
			'maxlength' => '255',
			'rows'      => '',
			'cols'      => ''
		)
		//#################################
		// ENDE Datenbankfelder
		//#################################
	)
);

?>
