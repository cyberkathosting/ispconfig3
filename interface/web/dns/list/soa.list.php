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
$liste["name"]                                 = "soa";

// Database table
$liste["table"]                         = "soa";

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
                                                        'formtype'        => "TEXT",
                                                        'op'                => "=",
                                                        'prefix'        => "",
                                                        'suffix'        => "",
                                                        'width'                => "",
                                                        'value'                => "");

$liste["item"][] = array(        'field'                => "xfer",
                                                        'datatype'        => "VARCHAR",
                                                        'formtype'        => "TEXT",
                                                        'op'                => "LIKE",
                                                        'prefix'        => "%",
                                                        'suffix'        => "%",
                                                        'width'                => "",
                                                        'value'                => "");


?>