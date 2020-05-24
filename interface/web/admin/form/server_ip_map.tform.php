<?php

/*
Copyright (c) 2015, Florian Schaal, schaal @it
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

$form["title"]    = "server_ip_map_title";
$form["description"]  = "server_ip_map_desc";
$form["name"]    = "server_ip_map";
$form["action"]   = "server_ip_map_edit.php";
$form["db_table"]  = "server_ip_map";
$form["db_table_idx"] = "server_ip_map_id";
$form["db_history"]  = "yes";
$form["tab_default"] = "server_ip_map";
$form["list_default"] = "server_ip_map_list.php";
$form["auth"]   = 'yes';

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['server_ip_map'] = array (
	'title'  => "server_ip_map_title",
	'width'  => 80,
	'template'  => "templates/server_ip_map_edit.htm",
	'fields'  => array (
		'server_id' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'default' => '',
			'value'  => ''
		),
		'source_ip' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'SELECT',
			'validators' => array (  
				0 => array ( 'type' => 'NOTEMPTY', 'errmsg'=> 'source_ip_empty'),
			),
			'default' => '',
			'value'  => ''
		),
		'destination_ip' => array (
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'validators' => array (  
				0 => array ( 'type' => 'ISIPV4', 'errmsg'=> 'ip_error_wrong'),
				1 => array ( 'type' => 'NOTEMPTY', 'errmsg'=> 'destination_ip_empty'),
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
		'active' => array(
			'datatype' => 'VARCHAR',
			'formtype' => 'CHECKBOX',
			'default' => 'y',
			'value' => array(0 => 'n', 1 => 'y')
		),
	)
);
?>
