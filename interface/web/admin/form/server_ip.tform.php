<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

	Search:
	- searchable = 1 or searchable = 2 include the field in the search
	- searchable = 1: this field will be the title of the search result
	- searchable = 2: this field will be included in the description of the search result


*/

$form["title"]    = "server_ip_edit_title";
$form["description"]  = "server_ip_edit_desc";
$form["name"]    = "server_ip";
$form["action"]   = "server_ip_edit.php";
$form["db_table"]  = "server_ip";
$form["db_table_idx"] = "server_ip_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "server_ip";
$form["list_default"] = "server_ip_list.php";
$form["auth"]   = 'yes';

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['server_ip'] = array (
	'title'  => "IP Address",
	'width'  => 80,
	'template'  => "templates/server_ip_edit.htm",
	'fields'  => array (
		//#################################
		// Beginn Datenbankfelder
		//#################################
		'server_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => 'SELECT server_id,server_name FROM server WHERE {AUTHSQL} ORDER BY server_name',
				'keyfield'=> 'server_id',
				'valuefield'=> 'server_name'
			),
			'value'  => ''
		),
		'client_id' => array (
			'datatype' => 'INTEGER',
			'formtype' => 'SELECT',
			'default' => '',
			'datasource' => array (  'type' => 'SQL',
				'querystring' => "(SELECT 0 AS client_id, '' AS name) UNION ALL (SELECT client_id,CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as name FROM client WHERE {AUTHSQL} ORDER BY contact_name)",
				'keyfield'=> 'client_id',
				'valuefield'=> 'name'
			),
			'value'  => array(0 => ' ')
		),
		'ip_type' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => array('IPv4' => 'IPv4', 'IPv6' => 'IPv6'),
			'searchable' => 2
		),
		'ip_address' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'CUSTOM', 'class' => 'validate_server', 'function' => 'check_server_ip',
					'errmsg'=> 'ip_error_wrong'),
				1 => array ( 'type' => 'UNIQUE',
					'errmsg'=> 'ip_error_unique'),
			),
			'default' => '',
			'value'  => '',
			'separator' => '',
			'width'  => '15',
			'maxlength' => '15',
			'rows'  => '',
			'cols'  => '',
			'searchable' => 1
		),
		'virtualhost' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value'  => array(0 => 'n', 1 => 'y')
		),
		'virtualhost_port' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  0 => array ( 'type' => 'REGEX',
					'regex' => '/^([0-9]{1,5}\,{0,1}){1,}$/i',
					'errmsg'=> 'error_port_syntax'),
			),
			'default' => '80,443',
			'value'  => '',
			'separator' => '',
			'width'  => '15',
			'maxlength' => '15',
			'rows'  => '',
			'cols'  => ''
		),
		//#################################
		// ENDE Datenbankfelder
		//#################################
	)
);
?>
