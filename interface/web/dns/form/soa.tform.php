<?php

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

$form["title"]                         = "SOA";
$form["description"]         = "";
$form["name"]                         = "soa";
$form["action"]                        = "soa_edit.php";
$form["db_table"]                = "soa";
$form["db_table_idx"]        = "id";
$form["db_history"]                = "yes";
$form["tab_default"]        = "soa";
$form["list_default"]        = "soa_list.php";
$form["auth"]                        = 'no'; // yes / no

$form["auth_preset"]["userid"]  = 0; // 0 = id of the user, > 0 id must match with id of current user
$form["auth_preset"]["groupid"] = 0; // 0 = default groupid of the user, > 0 id must match with groupid of current user
$form["auth_preset"]["perm_user"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_group"] = 'riud'; //r = read, i = insert, u = update, d = delete
$form["auth_preset"]["perm_other"] = ''; //r = read, i = insert, u = update, d = delete

$form["tabs"]['soa'] = array (
        'title'         => "SOA",
        'width'         => 100,
        'template'         => "templates/soa_edit.htm",
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
                'origin' => array (
                        'datatype'        => 'VARCHAR',
                        'formtype'        => 'TEXT',
                        'validators'      => array (0 => array ( 'type'        => 'UNIQUE',
                                                                   'errmsg'=> 'soa_error_unique'),
                                                   ),
                        'default'         => '',
                        'value'           => '',
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
                'ns' => array (
                        'datatype'        => 'VARCHAR',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ( 'type'        => 'NOTEMPTY',
                                                                   'errmsg'=> 'ns_error_empty'),
                                                    ),
                        */
                        'default'         => $conf['default_ns'],
                        'value'           => $conf['default_ns'],
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
                'mbox' => array (
                        'datatype'        => 'VARCHAR',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ( 'type'        => 'NOTEMPTY',
                                                                   'errmsg'=> 'mbox_error_empty'),
                                                    ),
                        */
                        'default'         => $conf['default_mbox'],
                        'value'           => $conf['default_mbox'],
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
                'serial' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ( 'type'        => 'ISPOSITIVE',
                                                                   'errmsg'=> 'serial_error_notpositive'),
                                                    ),
                        */
                        'default'         => date("Ymd").'01',
                        'value'           => date("Ymd").'01',
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
                'refresh' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ( 'type'        => 'ISPOSITIVE',
                                                                   'errmsg'=> 'refresh_error_notpositive'),
                                                    ),
                        */
                        'default'         => $conf['default_refresh'],
                        'value'           => $conf['default_refresh'],
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
                'retry' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ( 'type'        => 'ISPOSITIVE',
                                                                   'errmsg'=> 'retry_error_notpositive'),
                                                   ),
                        */
                        'default'         => $conf['default_retry'],
                        'value'           => $conf['default_retry'],
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
                'expire' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ( 'type'        => 'ISPOSITIVE',
                                                                   'errmsg'=> 'expire_error_notpositive'),
                                                   ),
                        */
                        'default'         => $conf['default_expire'],
                        'value'           => $conf['default_expire'],
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
                'minimum' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ( 'type'        => 'ISPOSITIVE',
                                                                   'errmsg'=> 'minimum_error_notpositive'),
                                                   ),
                        */
                        'default'         => $conf['default_minimum_ttl'],
                        'value'           => $conf['default_minimum_ttl'],
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
                'ttl' => array (
                        'datatype'        => 'INTEGER',
                        'formtype'        => 'TEXT',
                        /*
                        'validators'      => array (0 => array ( 'type'        => 'ISPOSITIVE',
                                                                   'errmsg'=> 'ttl_error_notpositive'),
                                                   ),
                        */
                        'default'         => $conf['default_ttl'],
                        'value'           => $conf['default_ttl'],
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
                'active' => array (
                        'datatype'        => 'VARCHAR',
                        'formtype'        => 'RADIO',
                        'default'         => 'Y',
                        'value'           => array('Y' => 'Yes','N'=>'No')
                ),
                'xfer' => array (
                        'datatype'        => 'VARCHAR',
                        'formtype'        => 'TEXT',
                        'default'         => '',
                        'value'           => '',
                        'width'           => '30',
                        'maxlength'       => '255'
                ),
        ##################################
        # ENDE Datatable fields
        ##################################
        )
);

$form["tabs"]['rr'] = array (
        'title'         => "Records",
        'width'         => 100,
        'template'         => "templates/soa_edit_rr.htm",
        'fields'         => array (
        ##################################
        # Beginn Datatable fields
        ##################################

        ##################################
        # ENDE Datatable fields
        ##################################
        ),
        'plugins' => array (
                'rr_list' => array (
                        'class'                 => 'plugin_listview',
                        'options'                => array('listdef' => 'list/rr.list.php', 'sqlextwhere' => "zone = ".intval($_REQUEST['id']))
                )
        )
);


?>