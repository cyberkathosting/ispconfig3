<?php
/*
Copyright (c) 2005, Till Brehm, Falko Timme, projektfarm Gmbh
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

        Tabledefinition

        Datatypes:
        - INTEGER (Forces the input to Int)
        - DOUBLE
        - CURRENCY (Formats the values to currency notation)
        - VARCHAR (no format check, maxlength: 255)
        - TEXT (no format check)
        - DATE (Dateformat, automatic conversion to timestamps)

        Formtype:
        - TEXT (Textfield)
        - TEXTAREA (Textarea)
        - PASSWORD (Password textfield, input is not shown when edited)
        - SELECT (Select option field)
        - RADIO
        - CHECKBOX
        - CHECKBOXARRAY
        - FILE

        VALUE:
        - Wert oder Array

        Hint:
        The ID field of the database table is not part of the datafield definition.
        The ID field must be always auto incement (int or bigint).


*/

$form["title"]          = "Record";
$form["description"]    = "";
$form["name"]           = "rr";
$form["action"]         = "rr_edit.php";
$form["db_table"]       = "dns_rr";
$form["db_table_idx"]   = "id";
$form["db_history"]     = "yes";
$form["tab_default"]    = "rr";
$form["list_default"]   = "rr_list.php";
$form["auth"]           = 'yes';  // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['rr'] = array (
        'title'         => "Record",
        'width'         => 100,
        'template'         => "templates/rr_edit.htm",
        'fields'         => array (
        ##################################
        # Begin Datatable fields
        ##################################
/*
                'server_id' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'SELECT',
                        'default'        => '',
                        'datasource'        => array (         'type'        => 'SQL',
                                                                                'querystring' => 'SELECT server_id,server_name FROM server WHERE 1 ORDER BY server_name',
                                                                                'keyfield'=> 'server_id',
                                                                                'valuefield'=> 'server_name'
                                                                         ),
                        'value'                => ''
                ),
*/
                'zone' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'TEXT',
                        'validators'      => array (0 => array ('type' => 'NOTEMPTY',
                                                                'errmsg'=> 'rr_zone_error_empty'),
                                                   ),
                        'default'        => '',
                        'value'                => '',
                        'width'                => '30',
                        'maxlength'        => '255'
                ),
                'name' => array (
                        'datatype'        => 'VARCHAR',
                        'formtype'        => 'TEXT',
                        'default'        => '',
                        'value'                => '',
                        'width'                => '30',
                        'maxlength'        => '255'
                ),
                'type' => array (
                        'datatype'        => 'VARCHAR',
                        'formtype'        => 'SELECT',
                        'default'        => '',
                        'value'                => array('A' => 'A', 'AAAA' => 'AAAA', 'ALIAS' => 'ALIAS', 'CNAME' => 'CNAME', 'HINFO' => 'HINFO', 'MX' => 'MX', 'NS' => 'NS', 'PTR' => 'PTR', 'RP' => 'RP', 'SRV' => 'SRV', 'TXT' => 'TXT')
                ),
                'data' => array (
                        'datatype'        => 'VARCHAR',
                        'formtype'        => 'TEXT',
                        'validators'      => array (0 => array ('type' => 'NOTEMPTY',
                                                                'errmsg'=> 'rr_data_error_empty'),
                                                   ),
                        'default'        => '',
                        'value'                => '',
                        'width'                => '30',
                        'maxlength'        => '255'
                ),
                'aux' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ('type' => 'ISINT',
                                                                'errmsg'=> 'rr_aux_error_noint'),
                                                   ),
                        */
                        'default'        => '',
                        'value'                => '',
                        'width'                => '30',
                        'maxlength'        => '255'
                ),
                'ttl' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ('type' => 'ISPOSITIVE',
                                                                'errmsg'=> 'rr_ttl_error_notpositive'),
                                                   ),
                        */
                        'default'        => '86400',
                        'value'                => '86400',
                        'width'                => '30',
                        'maxlength'        => '255'
                ),
        ##################################
        # ENDE Datatable fields
        ##################################
        )
);


?>