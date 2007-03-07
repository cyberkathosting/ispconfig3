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
$liste["name"]                                 = "soa";

// Database table
$liste["table"]                         = "dns_soa";

// Index index field of the database table
$liste["table_idx"]                        = "id";

// Search Field Prefix
$liste["search_prefix"]         = "search_";

// Records per page
$liste["records_per_page"]         = 15;

// Script File of the list
$liste["file"]                                = "soa_list.php";

// Script file of the edit form
$liste["edit_file"]                        = "soa_edit.php";

// Script File of the delete script
$liste["delete_file"]                = "soa_del.php";

// Paging Template
$liste["paging_tpl"]                = "templates/paging.tpl.htm";

// Enable auth
$liste["auth"]                                = "yes";

/*****************************************************
* Suchfelder
*****************************************************/

$liste["item"][] = array(        'field'                => "origin",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "like",
                                                        'prefix'        => "%",
                                                        'suffix'        => "%",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "ns",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "like",
                                                        'prefix'        => "%",
                                                        'suffix'        => "%",
                                                        'width'                => "",
                                                        'value'                => "");

/*
$liste["item"][] = array(        'field'                => "mbox",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "like",
                                                        'prefix'        => "%",
                                                        'suffix'        => "%",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "serial",
                                                        'datatype'        => "INTEGER",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "refresh",
                                                        'datatype'        => "INTEGER",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "retry",
                                                        'datatype'        => "INTEGER",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "expire",
                                                        'datatype'        => "INTEGER",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "minimum",
                                                        'datatype'        => "INTEGER",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => "");
*/

$liste["item"][] = array(        'field'                => "ttl",
                                                        'datatype'        => "INTEGER",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "active",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "SELECT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => array('Y' => $app->lng('Yes'), 'N' => $app->lng('No')));

/*
$liste["item"][] = array(        'field'                => "xfer",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "LIKE",
                                                        'prefix'        => "%",
                                                        'suffix'        => "%",
                                                        'width'                => "",
                                                        'value'                => "");
*/
?>