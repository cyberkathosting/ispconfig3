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
        Datatypes:
        - INTEGER
        - DOUBLE
        - CURRENCY
        - VARCHAR
        - TEXT
        - DATE
*/



// Name of the list
$liste["name"]                                 = "rr";

// Database table
$liste["table"]                         = "rr";

// Index index field of the database table
$liste["table_idx"]                        = "id";

// Search Field Prefix
$liste["search_prefix"]         = "search_";

// Records per page
$liste["records_per_page"]         = 15;

// Script File of the list
$liste["file"]                                = "rr_list.php";

// Script file of the edit form
$liste["edit_file"]                        = "rr_edit.php";

// Script File of the delete script
$liste["delete_file"]                = "rr_del.php";

// Paging Template
$liste["paging_tpl"]                = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]                                = "yes";


/*****************************************************
* Suchfelder
*****************************************************/
/*
$liste["item"][] = array(        'field'                => "server_id",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "like",
                                                        'prefix'        => "%",
                                                        'suffix'        => "%",
                                                        'width'                => "",
                                                        'value'                => "");
*/
$liste["item"][] = array(        'field'                => "name",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "like",
                                                        'prefix'        => "%",
                                                        'suffix'        => "%",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "type",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "SELECT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => array('A' => 'A', 'AAAA' => 'AAAA', 'ALIAS' => 'ALIAS', 'CNAME' => 'CNAME', 'HINFO' => 'HINFO', 'MX' => 'MX', 'NS' => 'NS', 'PTR' => 'PTR', 'RP' => 'RP', 'SRV' => 'SRV', 'TXT' => 'TXT'));

$liste["item"][] = array(        'field'                => "data",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "like",
                                                        'prefix'        => "%",
                                                        'suffix'        => "%",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "aux",
                                                        'datatype'        => "INTEGER",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "ttl",
                                                        'datatype'        => "INTEGER",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => "");

?>