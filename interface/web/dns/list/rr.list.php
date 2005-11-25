<?php

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
$liste["auth"]                                = "no";


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