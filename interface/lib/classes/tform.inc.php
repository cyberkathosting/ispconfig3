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

/**
* Formularbehandlung
*
* Functions to validate, display and save form values
*
*        Database table field definitions
*
*        Datatypes:
*        - INTEGER (Converts data to int automatically)
*        - DOUBLE
*        - CURRENCY (Formats digits in currency notation)
*        - VARCHAR (No format check)
*        - DATE (Date format, converts from and to linux timestamps automatically)
*
*        Formtype:
*        - TEXT (Normal text field)
*        - PASSWORD (password field, the content will not be displayed again to the user)
*        - SELECT (Option fiield)
*        - MULTIPLE (Allows selection of multiple values)
*
*        VALUE:
*        - Value or array
*
*        SEPARATOR
*        - separator char used for fileds with multiple values
*
*        Hint: The auto increment (ID) filed of the table has not be be definied eoarately.
*
*/

class tform {

        /**
        * Table definition (array)
        * @var tableDef
        */
        var $tableDef;

        /**
        * Private
        * @var action
        */
        var $action;

        /**
        * Table name (String)
        * @var table_name
        */
        var $table_name;

        /**
        * Enable debigging
        * @var debug
        */
        var $debug = 0;

        /**
        * name of the primary field of the datbase table (string)
        * @var table_index
        */
        var $table_index;

        /**
        * contains the error message
        * @var errorMessage
        */
        var $errorMessage = '';

        var $dateformat = "d.m.Y";
    	var $formDef;
        var $wordbook;
        var $module;
        var $primary_id;
		var $diffrec = array();

        /**
        * Loading of the table definition
        *
        * @param file: path to the form definition file
        * @return true
        */
        /*
        function loadTableDef($file) {
                global $app,$conf;

                include_once($file);
                $this->tableDef = $table;
                $this->table_name = $table_name;
                $this->table_index = $table_index;
                return true;
        }
        */

    function loadFormDef($file,$module = '') {
                global $app,$conf;

                include_once($file);
                $this->formDef = $form;

                $this->module = $module;
				$wb = array();
				
                if($module == '') {
					$lng_file = "lib/lang/".$_SESSION["s"]["language"]."_".$this->formDef["name"].".lng";
					if(!file_exists($lng_file)) $lng_file = "lib/lang/en_".$this->formDef["name"].".lng";
					include($lng_file);
                } else {
					$lng_file = "../$module/lib/lang/".$_SESSION["s"]["language"]."_".$this->formDef["name"].".lng";
					if(!file_exists($lng_file)) $lng_file = "../$module/lib/lang/en_".$this->formDef["name"].".lng";
					include($lng_file);
                }
                $this->wordbook = $wb;

                return true;
        }


        /**
        * Converts the data in the array to human readable format
        * Datatype conversion e.g. to show the data in lists
        *
        * @param record
        * @return record
        */
        function decode($record,$tab) {
                if(!is_array($this->formDef['tabs'][$tab])) $app->error("Tab does not exist or the tab is empty (TAB: $tab).");
                $new_record = '';
				if(is_array($record)) {
                        foreach($this->formDef['tabs'][$tab]['fields'] as $key => $field) {
                                switch ($field['datatype']) {
                                case 'VARCHAR':
                                        $new_record[$key] = stripslashes($record[$key]);
                                break;

                                case 'TEXT':
                                        $new_record[$key] = stripslashes($record[$key]);
                                break;

                                case 'DATE':
                                        if($record[$key] > 0) {
                                                $new_record[$key] = date($this->dateformat,$record[$key]);
                                        }
                                break;

                                case 'INTEGER':
                                        $new_record[$key] = intval($record[$key]);
                                break;

                                case 'DOUBLE':
                                        $new_record[$key] = $record[$key];
                                break;

                                case 'CURRENCY':
                                        $new_record[$key] = number_format($record[$key], 2, ',', '');
                                break;

                                default:
                                        $new_record[$key] = stripslashes($record[$key]);
                                }
                        }

                }
				
        return $new_record;
        }

        /**
        * Get the key => value array of a form filed from a datasource definitiom
        *
        * @param field = array with field definition
        * @param record = Dataset as array
        * @return key => value array for the value field of a form
        */

        function getDatasourceData($field, $record) {
                global $app;

                $values = array();

                if($field["datasource"]["type"] == 'SQL') {

                        // Preparing SQL string. We will replace some
                        // common placeholders
                        $querystring = $field["datasource"]["querystring"];
                        $querystring = str_replace("{USERID}",$_SESSION["s"]["user"]["userid"],$querystring);
                        $querystring = str_replace("{GROUPID}",$_SESSION["s"]["user"]["default_group"],$querystring);
                        $querystring = str_replace("{GROUPS}",$_SESSION["s"]["user"]["groups"],$querystring);
                        $table_idx = $this->formDef['db_table_idx'];
						
						$tmp_recordid = (isset($record[$table_idx]))?$record[$table_idx]:0;
                        $querystring = str_replace("{RECORDID}",$tmp_recordid,$querystring);
						unset($tmp_recordid);
						
                        $querystring = str_replace("{AUTHSQL}",$this->getAuthSQL('r'),$querystring);

                        // Getting the records
                        $tmp_records = $app->db->queryAllRecords($querystring);
                        if($app->db->errorMessage != '') die($app->db->errorMessage);
                        if(is_array($tmp_records)) {
                                $key_field = $field["datasource"]["keyfield"];
                                $value_field = $field["datasource"]["valuefield"];
                                foreach($tmp_records as $tmp_rec) {
                                        $tmp_id = $tmp_rec[$key_field];
                                        $values[$tmp_id] = $tmp_rec[$value_field];
                                }
                        }
                }

                if($field["datasource"]["type"] == 'CUSTOM') {
                        // Calls a custom class to validate this record
                        if($field["datasource"]['class'] != '' and $field["datasource"]['function'] != '') {
                                $datasource_class = $field["datasource"]['class'];
                                $datasource_function = $field["datasource"]['function'];
                                $app->uses($datasource_class);
                                $values = $app->$datasource_class->$datasource_function($field, $record);
                        } else {
                                $this->errorMessage .= "Custom datasource class or function is empty<br />\r\n";
                        }
                }

                return $values;

        }


        /**
        * Prepare the data record to show the data in a form.
        *
        * @param record = Datensatz als Array
        * @param action = NEW oder EDIT
        * @return record
        */
        function getHTML($record, $tab, $action = 'NEW') {

                global $app;

                $this->action = $action;

                if(!is_array($this->formDef)) $app->error("No form definition found.");
                if(!is_array($this->formDef['tabs'][$tab])) $app->error("The tab is empty or does not exist (TAB: $tab).");

                $new_record = array();
                if($action == 'EDIT') {
                        $record = $this->decode($record,$tab);
                        if(is_array($record)) {
                                foreach($this->formDef['tabs'][$tab]['fields'] as $key => $field) {
                                        $val = $record[$key];

                                        // If Datasource is set, get the data from there
                                        if(isset($field['datasource']) && is_array($field['datasource'])) {
                                                $field["value"] = $this->getDatasourceData($field, $record);
                                        }

                                        switch ($field['formtype']) {
                                        case 'SELECT':
												$out = '';
                                                if(is_array($field['value'])) {
                                                        foreach($field['value'] as $k => $v) {
                                                                $selected = ($k == $val)?' SELECTED':'';
                                                                $out .= "<option value='$k'$selected>$v</option>\r\n";
                                                        }
                                                }
                                                $new_record[$key] = $out;
                                        break;
                                        case 'MULTIPLE':
                                                if(is_array($field['value'])) {

                                                        // Split
                                                        $vals = explode($field['separator'],$val);

                                                        // write HTML
                                                        $out = '';
                                                        foreach($field['value'] as $k => $v) {

                                                                $selected = '';
                                                                foreach($vals as $tvl) {
                                                                        if(trim($tvl) == trim($k)) $selected = ' SELECTED';
                                                                }

                                                                $out .= "<option value='$k'$selected>$v</option>\r\n";
                                                        }
                                                }
                                                $new_record[$key] = $out;
                                        break;

                                        case 'PASSWORD':
                                                $new_record[$key] = '';
                                        break;

                                        case 'CHECKBOX':
                                                $checked = ($val == $field['value'][1])?' CHECKED':'';
                                                $new_record[$key] = "<input name=\"".$key."\" type=\"checkbox\" value=\"".$field['value'][1]."\" $checked>\r\n";
                                        break;

                                        case 'CHECKBOXARRAY':
                                                if(is_array($field['value'])) {

                                                        // aufsplitten ergebnisse
                                                        $vals = explode($field['separator'],$val);

                                                        // HTML schreiben
                                                        $out = '';
                                                        foreach($field['value'] as $k => $v) {

                                                                $checked = '';
                                                                foreach($vals as $tvl) {
                                                                        if(trim($tvl) == trim($k)) $checked = ' CHECKED';
                                                                }
                                                                $out .= "<span class=\"wf_oneChoice\">\r\n
                                                                <input type=\"checkbox\" value=\"$k\" id=\"".$key."[]\" name=\"".$key."[]\" $checked>\r\n
                                                                <label for=\"".$key."[]\" id=\"".$key."[]-L\" class=\"wf_postField\">$v</label>\r\n
                                                                </span><br />\r\n";
                                                        }
                                                }
                                                $new_record[$key] = $out;
                                        break;

                                        case 'RADIO':
                                                if(is_array($field['value'])) {

                                                        // HTML schreiben
                                                        $out = '';
                                                        foreach($field['value'] as $k => $v) {
                                                                $checked = ($k == $val)?' CHECKED':'';
                                                                $out .= "<span class=\"wf_oneChoice\">\r\n
                                                                <input type=\"radio\" value=\"$k\" id=\"".$key."[]\" name=\"".$key."[]\" $checked>\r\n
                                                                <label for=\"".$key."[]\" id=\"".$key."[]-L\" class=\"wf_postField\">$v</label>\r\n
                                                                </span><br />\r\n";
                                                        }
                                                }
                                                $new_record[$key] = $out;
                                        break;

                                        default:
                                                $new_record[$key] = htmlspecialchars($record[$key]);
                                        }
                                }
                        }
                } else {
                        // Action: NEW
                        foreach($this->formDef['tabs'][$tab]['fields'] as $key => $field) {

                                // If Datasource is set, get the data from there
                                if(@is_array($field['datasource'])) {
                                        $field["value"] = $this->getDatasourceData($field, $record);
                                }

                                switch ($field['formtype']) {
                                case 'SELECT':
                                        if(is_array($field['value'])) {
                                                $out = '';
                                                foreach($field['value'] as $k => $v) {
                                                    //$selected = ($k == $val)?' SELECTED':'';
													$selected = '';
                                                    $out .= "<option value='$k'$selected>$v</option>\r\n";
                                                }
                                        }
                                        if(isset($out)) $new_record[$key] = $out;
                                break;
                                case 'MULTIPLE':
                                                if(is_array($field['value'])) {

                                                        // aufsplitten ergebnisse
                                                        $vals = explode($field['separator'],$val);

                                                        // HTML schreiben
                                                        $out = '';
                                                        foreach($field['value'] as $k => $v) {

                                                                $out .= "<option value='$k'>$v</option>\r\n";
                                                        }
                                                }
                                                $new_record[$key] = $out;
                                        break;

                                case 'PASSWORD':
                                        $new_record[$key] = '';
                                break;

                                case 'CHECKBOX':
                                        // $checked = (empty($field["default"]))?'':' CHECKED';
										                    $checked = ($field["default"] == $field['value'][1])?' CHECKED':'';
                                        $new_record[$key] = "<input name=\"".$key."\" type=\"checkbox\" value=\"".$field['value'][1]."\" $checked>\r\n";
                                break;

                                case 'CHECKBOXARRAY':
                                        if(is_array($field['value'])) {

                                                // aufsplitten ergebnisse
                                                $vals = explode($field['separator'],$field["default"]);

                                                // HTML schreiben
                                                $out = '';
                                                foreach($field['value'] as $k => $v) {

                                                        $checked = '';
                                                        foreach($vals as $tvl) {
                                                                if(trim($tvl) == trim($k)) $checked = ' CHECKED';
                                                        }
                                                        $out .= "<span class=\"wf_oneChoice\">\r\n
                                                        <input type=\"checkbox\" value=\"$k\" id=\"".$key."[]\" name=\"".$key."[]\" $checked>\r\n
                                                        <label for=\"".$key."[]\" id=\"".$key."[]-L\" class=\"wf_postField\">$v</label>\r\n
                                                        </span><br />\r\n";
                                                }
                                        }
                                        $new_record[$key] = $out;
                                break;

                                case 'RADIO':
                                        if(is_array($field['value'])) {

                                                // HTML schreiben
                                                $out = '';
                                                foreach($field['value'] as $k => $v) {
                                                        $checked = ($k == $field["default"])?' CHECKED':'';
                                                        $out .= "<span class=\"wf_oneChoice\">\r\n
                                                        <input type=\"radio\" value=\"$k\" id=\"".$key."[]\" name=\"".$key."[]\" $checked>\r\n
                                                        <label for=\"".$key."[]\" id=\"".$key."[]-L\" class=\"wf_postField\">$v</label>\r\n
                                                        </span><br />\r\n";
                                                }
                                        }
                                        $new_record[$key] = $out;
                                break;

                                default:
                                        $new_record[$key] = htmlspecialchars($field['default']);
                                }
                        }

                }

                if($this->debug == 1) $this->dbg($new_record);

                return $new_record;
        }

        /**
        * Rewrite the record data to be stored in the database
        * and check values with regular expressions.
        *
        * @param record = Datensatz als Array
        * @return record
        */
        function encode($record,$tab) {
			global $app;
			
                if(!is_array($this->formDef['tabs'][$tab])) $app->error("Tab is empty or does not exist (TAB: $tab).");
                //$this->errorMessage = '';

                if(is_array($record)) {
                        foreach($this->formDef['tabs'][$tab]['fields'] as $key => $field) {

                                if(isset($field['validators']) && is_array($field['validators'])) $this->validateField($key, (isset($record[$key]))?$record[$key]:'', $field['validators']);

                                switch ($field['datatype']) {
                                case 'VARCHAR':
                                        if(!@is_array($record[$key])) {
                                                $new_record[$key] = (isset($record[$key]))?$app->db->quote($record[$key]):'';
                                        } else {
                                                $new_record[$key] = implode($field['separator'],$record[$key]);
                                        }
                                break;
                                case 'TEXT':
                                        if(!is_array($record[$key])) {
                                                $new_record[$key] = $app->db->quote($record[$key]);
                                        } else {
                                                $new_record[$key] = implode($field['separator'],$record[$key]);
                                        }
                                break;
                                case 'DATE':
                                        if($record[$key] > 0) {
                                                list($tag,$monat,$jahr) = explode('.',$record[$key]);
                                                $new_record[$key] = mktime(0,0,0,$monat,$tag,$jahr);
                                        } else {
											$new_record[$key] = 0;
										}
                                break;
                                case 'INTEGER':
                                        $new_record[$key] = (isset($record[$key]))?$record[$key]:0;
                                        //if($new_record[$key] != $record[$key]) $new_record[$key] = $field['default'];
                                        //if($key == 'refresh') die($record[$key]);
                                break;
                                case 'DOUBLE':
                                        $new_record[$key] = $app->db->quote($record[$key]);
                                break;
                                case 'CURRENCY':
                                        $new_record[$key] = str_replace(",",".",$record[$key]);
                                break;
                                }

                                // The use of the field value is deprecated, use validators instead
                                if(isset($field['regex']) && $field['regex'] != '') {
                                        // Enable that "." matches also newlines
                                        $field['regex'] .= 's';
                                        if(!preg_match($field['regex'], $record[$key])) {
                                                $errmsg = $field['errmsg'];
                                                $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
                                        }
                                }


                        }
                }
                return $new_record;
        }

        /**
        * process the validators for a given field.
        *
        * @param field_name = Name of the field
        * @param field_value = value of the field
        * @param validatoors = Array of validators
        * @return record
        */

        function validateField($field_name, $field_value, $validators) {

                global $app;
				
				$escape = '`';
				
                // loop trough the validators
                foreach($validators as $validator) {

                        switch ($validator['type']) {
                                case 'REGEX':
                                        $validator['regex'] .= 's';
                                        if(!preg_match($validator['regex'], $field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                	$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br />\r\n";
												}
                                        }
                                break;
                                case 'UNIQUE':
                                        if($this->action == 'NEW') {
                                                $num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM ".$escape.$this->formDef['db_table'].$escape. " WHERE $field_name = '".$app->db->quote($field_value)."'");
                                                if($num_rec["number"] > 0) {
                                                        $errmsg = $validator['errmsg'];
														if(isset($this->wordbook[$errmsg])) {
                                                        	$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
														} else {
															$this->errorMessage .= $errmsg."<br />\r\n";
														}
                                                }
                                        } else {
                                                $num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM ".$escape.$this->formDef['db_table'].$escape. " WHERE $field_name = '".$app->db->quote($field_value)."' AND ".$this->formDef['db_table_idx']." != ".$this->primary_id);
                                                if($num_rec["number"] > 0) {
                                                        $errmsg = $validator['errmsg'];
                                                        if(isset($this->wordbook[$errmsg])) {
                                                        	$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
														} else {
															$this->errorMessage .= $errmsg."<br />\r\n";
														}
                                                }
                                        }
                                break;
                                case 'NOTEMPTY':
                                        if(empty($field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                    $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br />\r\n";
												}
                                        }
                                break;
                                case 'ISEMAIL':
                                        if(!preg_match("/^\w+[\w.-]*\w+@\w+[\w.-]*\w+\.[a-z]{2,10}$/i", $field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                    $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br />\r\n";
												}
                                        }
                                break;
                                case 'ISINT':
                                        $tmpval = intval($field_value);
                                        if($tmpval === 0 and !empty($field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                    $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br />\r\n";
												}
                                        }
                                break;
                                case 'ISPOSITIVE':
                                        if(!is_numeric($field_value) || $field_value <= 0){
                                          $errmsg = $validator['errmsg'];
                                          if(isset($this->wordbook[$errmsg])) {
                                             $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
										  } else {
											 $this->errorMessage .= $errmsg."<br />\r\n";
										  }
                                        }
                                break;
								case 'ISIPV4':
								$vip=1;
								if(preg_match("/^[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}$/", $field_value)){
								$groups=explode(".",$field_value);
								foreach($groups as $group){
									if($group<0 OR $group>255)
									$vip=0;
								}
								}else{$vip=0;}
                                        if($vip==0) {
										$errmsg = $validator['errmsg'];
                                          if(isset($this->wordbook[$errmsg])) {
                                             $this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
										  } else {
											 $this->errorMessage .= $errmsg."<br />\r\n";
										  }
										}
                                break;
                                case 'CUSTOM':
                                        // Calls a custom class to validate this record
                                        if($validator['class'] != '' and $validator['function'] != '') {
                                                $validator_class = $validator['class'];
                                                $validator_function = $validator['function'];
                                                $app->uses($validator_class);
                                                $this->errorMessage .= $app->$validator_class->$validator_function($field_name, $field_value, $validator);
                                        } else {
                                                $this->errorMessage .= "Custom validator class or function is empty<br />\r\n";
                                        }
                                break;
								default:
									$this->errorMessage .= "Unknown Validator: ".$validator['type'];
								break;
                        }


                }

                return true;
        }

        /**
        * Create the SQL staement.
        *
        * @param record = Datensatz als Array
        * @param action = INSERT oder UPDATE
        * @param primary_id
        * @return record
        */
        function getSQL($record, $tab, $action = 'INSERT', $primary_id = 0, $sql_ext_where = '') {

                global $app;

                // If there are no data records on the tab, return empty sql string
                if(count($this->formDef['tabs'][$tab]['fields']) == 0) return '';

                // checking permissions
                if($this->formDef['auth'] == 'yes' && $_SESSION["s"]["user"]["typ"] != 'admin') {
                        if($action == "INSERT") {
                                if(!$this->checkPerm($primary_id,'i')) $this->errorMessage .= "Insert denied.<br />\r\n";
                        } else {
                                if(!$this->checkPerm($primary_id,'u')) $this->errorMessage .= "Update denied.<br />\r\n";
                        }
                }

                $this->action = $action;
                $this->primary_id = $primary_id;

                $record = $this->encode($record,$tab);
                $sql_insert_key = '';
                $sql_insert_val = '';
                $sql_update = '';

                if(!is_array($this->formDef)) $app->error("Keine Formulardefinition vorhanden.");
                if(!is_array($this->formDef['tabs'][$tab])) $app->error("Tab ist leer oder existiert nicht (TAB: $tab).");

                // gehe durch alle Felder des Tabs
                if(is_array($record)) {
                foreach($this->formDef['tabs'][$tab]['fields'] as $key => $field) {
                                // Wenn es kein leeres Passwortfeld ist
                                if (!($field['formtype'] == 'PASSWORD' and $record[$key] == '')) {
                                        // Erzeuge Insert oder Update Quelltext
                                        if($action == "INSERT") {
                                                if($field['formtype'] == 'PASSWORD') {
                                                        $sql_insert_key .= "`$key`, ";
                                                        if($field['encryption'] == 'CRYPT') {
                                                                $salt="$1$";
																for ($n=0;$n<11;$n++) {
																	$salt.=chr(mt_rand(64,126));
																}
																$salt.="$";
																// $salt = substr(md5(time()),0,2);
																$record[$key] = crypt($record[$key],$salt);
																$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
														} elseif ($field['encryption'] == 'MYSQL') {
																$sql_insert_val .= "PASSWORD('".$app->db->quote($record[$key])."'), ";
														} elseif ($field['encryption'] == 'CLEARTEXT') {
																$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
                                                        } else {
                                                                $record[$key] = md5($record[$key]);
																$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
                                                        }
														
                                                } elseif ($field['formtype'] == 'CHECKBOX') {
                                                        $sql_insert_key .= "`$key`, ";
														if($record[$key] == '') {
															// if a checkbox is not set, we set it to the unchecked value
															$sql_insert_val .= "'".$field['value'][0]."', ";
															$record[$key] = $field['value'][0];
														} else {
															$sql_insert_val .= "'".$record[$key]."', ";
														}
                                                } else {
                                                        $sql_insert_key .= "`$key`, ";
                                                        $sql_insert_val .= "'".$record[$key]."', ";
                                                }
                                        } else {
                                                if($field['formtype'] == 'PASSWORD') {
														if(isset($field['encryption']) && $field['encryption'] == 'CRYPT') {
                                                                $salt="$1$";
																for ($n=0;$n<11;$n++) {
																	$salt.=chr(mt_rand(64,126));
																}
																$salt.="$";
																// $salt = substr(md5(time()),0,2);
																$record[$key] = crypt($record[$key],$salt);
																$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
														} elseif (isset($field['encryption']) && $field['encryption'] == 'MYSQL') {
																$sql_update .= "`$key` = PASSWORD('".$app->db->quote($record[$key])."'), ";
														} elseif (isset($field['encryption']) && $field['encryption'] == 'CLEARTEXT') {
																$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
                                                        } else {
                                                                $record[$key] = md5($record[$key]);
																$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
                                                        }
                                                        
                                                } elseif ($field['formtype'] == 'CHECKBOX') {
														if($record[$key] == '') {
															// if a checkbox is not set, we set it to the unchecked value
															$sql_update .= "`$key` = '".$field['value'][0]."', ";
															$record[$key] = $field['value'][0];
														} else {
															$sql_update .= "`$key` = '".$record[$key]."', ";
														}
                                                } else {
                                                        $sql_update .= "`$key` = '".$record[$key]."', ";
                                                }
                                        }
                                } else {
									// we unset the password filed, if empty to tell the datalog function 
									// that the password has not been changed
								    unset($record[$key]);
								}
                        }
        }


                // Add backticks for incomplete table names
                if(stristr($this->formDef['db_table'],'.')) {
                        $escape = '';
                } else {
                        $escape = '`';
                }


                if($action == "INSERT") {
                        if($this->formDef['auth'] == 'yes') {
                                // Set user and group
                                $sql_insert_key .= "`sys_userid`, ";
                                $sql_insert_val .= ($this->formDef["auth_preset"]["userid"] > 0)?"'".$this->formDef["auth_preset"]["userid"]."', ":"'".$_SESSION["s"]["user"]["userid"]."', ";
                                $sql_insert_key .= "`sys_groupid`, ";
                                $sql_insert_val .= ($this->formDef["auth_preset"]["groupid"] > 0)?"'".$this->formDef["auth_preset"]["groupid"]."', ":"'".$_SESSION["s"]["user"]["default_group"]."', ";
                                $sql_insert_key .= "`sys_perm_user`, ";
                                $sql_insert_val .= "'".$this->formDef["auth_preset"]["perm_user"]."', ";
                                $sql_insert_key .= "`sys_perm_group`, ";
                                $sql_insert_val .= "'".$this->formDef["auth_preset"]["perm_group"]."', ";
                                $sql_insert_key .= "`sys_perm_other`, ";
                                $sql_insert_val .= "'".$this->formDef["auth_preset"]["perm_other"]."', ";
                        }
                        $sql_insert_key = substr($sql_insert_key,0,-2);
                        $sql_insert_val = substr($sql_insert_val,0,-2);
                        $sql = "INSERT INTO ".$escape.$this->formDef['db_table'].$escape." ($sql_insert_key) VALUES ($sql_insert_val)";
                } else {
					if($this->formDef['auth'] == 'yes') {
                        if($primary_id != 0) {
                                $sql_update = substr($sql_update,0,-2);
                                $sql = "UPDATE ".$escape.$this->formDef['db_table'].$escape." SET ".$sql_update." WHERE ".$this->getAuthSQL('u')." AND ".$this->formDef['db_table_idx']." = ".$primary_id;
                                if($sql_ext_where != '') $sql .= " and ".$sql_ext_where;
                        } else {
                                $app->error("Primary ID fehlt!");
                        }
					} else {
						if($primary_id != 0) {
                                $sql_update = substr($sql_update,0,-2);
                                $sql = "UPDATE ".$escape.$this->formDef['db_table'].$escape." SET ".$sql_update." WHERE ".$this->formDef['db_table_idx']." = ".$primary_id;
                                if($sql_ext_where != '') $sql .= " and ".$sql_ext_where;
                        } else {
                                $app->error("Primary ID fehlt!");
                        }
					}
                }
                
                return $sql;
        }

        /**
        * Debugging arrays.
        *
        * @param array_data
        */
        function dbg($array_data) {

                echo "<pre>";
                print_r($array_data);
                echo "</pre>";

        }


    function showForm() {
            global $app,$conf;

        if(!is_array($this->formDef)) die("Form Definition wurde nicht geladen.");

                $active_tab = $this->getNextTab();

        // go trough the tabs
        foreach( $this->formDef["tabs"] as $key => $tab) {

            $tab['name'] = $key;
            if($tab['name'] == $active_tab) {

                // If module is set, then set the template path relative to the module..
                if($this->module != '') $tab["template"] = "../".$this->module."/".$tab["template"];

                // Generate the template if it does not exist yet.
				
				// Translate the title of the tab
				$tab['title'] = $this->lng($tab['title']);
								
                if(!is_file($tab["template"])) {
                     $app->uses('tform_tpl_generator');
                     $app->tform_tpl_generator->buildHTML($this->formDef,$tab['name']);
                }

                $app->tpl->setInclude('content_tpl',$tab["template"]);
                $tab["active"] = 1;
                $_SESSION["s"]["form"]["tab"] = $tab['name'];
            } else {
                    $tab["active"] = 0;
            }

                        // Unset unused variables.
                        unset($tab["fields"]);
                        unset($tab["plugins"]);

            $frmTab[] = $tab;
        }

        // setting form tabs
        $app->tpl->setLoop("formTab", $frmTab);

                // Set form action
                $app->tpl->setVar('form_action',$this->formDef["action"]);
                $app->tpl->setVar('form_active_tab',$active_tab);

                // Set form title
                $form_hint = $this->lng($this->formDef["title"]);
                if($this->formDef["description"] != '') $form_hint .= '<div class="pageForm_description">'.$this->lng($this->formDef["description"]).'</div>';
                $app->tpl->setVar('form_hint',$form_hint);

                // Set Wordbook for this form

                $app->tpl->setVar($this->wordbook);
    	}

		function getDataRecord($primary_id) {
			global $app;
			$escape = '`';
			$sql = "SELECT * FROM ".$escape.$this->formDef['db_table'].$escape." WHERE ".$this->formDef['db_table_idx']." = ".$primary_id;
            return $app->db->queryOneRecord($sql);
		}
		

        function datalogSave($action,$primary_id, $record_old, $record_new) {
                global $app,$conf;

                // Add backticks for incomplete table names.
                if(stristr($this->formDef['db_table'],'.')) {
                        $escape = '';
                } else {
                        $escape = '`';
                }

                $this->diffrec = array();
				/*
                if(is_array($record_new) && count($record_new) > 0) {
                        foreach($record_new as $key => $val) {
                                if(@$record_old[$key] != $val) {
										// Record has changed
                                        $diffrec[$key] = array('old' => @$record_old[$key],
                                                               'new' => $val);
                                }
                        }
                } elseif(is_array($record_old)) {
                        foreach($record_old as $key => $val) {
                                if($record_new[$key] != $val) {
										// Record has changed
                                        $diffrec[$key] = array('new' => $record_new[$key],
                                                               'old' => $val);
                                }
                        }
                }
				$this->diffrec = $diffrec;
				*/
				
				// Full diff records for ISPConfig, they have a different format then the simple diffrec
				$diffrec_full = array();

                if(is_array($record_old) && count($record_old) > 0) {
                        foreach($record_old as $key => $val) {
                                //if(isset($record_new[$key]) && $record_new[$key] != $val) {
								if(!isset($record_new[$key]) || $record_new[$key] != $val) {
                                    // Record has changed
									$diffrec_full['old'][$key] = $val;
									$diffrec_full['new'][$key] = $record_new[$key];
									$this->diffrec[$key] = array(	'new' => $record_new[$key],
                                                               		'old' => $val);
                                } else {
									$diffrec_full['old'][$key] = $val;
									$diffrec_full['new'][$key] = $val;
								}
                        }
                } elseif(is_array($record_new)) {
                        foreach($record_new as $key => $val) {
                                if(isset($record_new[$key]) && $record_old[$key] != $val) {
                                    // Record has changed
									$diffrec_full['new'][$key] = $val;
									$diffrec_full['old'][$key] = $record_old[$key];
									$this->diffrec[$key] = array(	'old' => @$record_old[$key],
                                                               		'new' => $val);
                                } else {
									$diffrec_full['new'][$key] = $val;
									$diffrec_full['old'][$key] = $val;
								}
                        }
                }
				
				//$this->diffrec = $diffrec;
				// Insert the server_id, if the record has a server_id
				$server_id = (isset($record_old["server_id"]) && $record_old["server_id"] > 0)?$record_old["server_id"]:0;
				if(isset($record_new["server_id"])) $server_id = $record_new["server_id"];

                if(count($this->diffrec) > 0) {
						$diffstr = addslashes(serialize($diffrec_full));
                        $username = $app->db->quote($_SESSION["s"]["user"]["username"]);
                        $dbidx = $this->formDef['db_table_idx'].":".$primary_id;
                        // $action = ($action == 'INSERT')?'i':'u';
						
						if($action == 'INSERT') $action = 'i';
						if($action == 'UPDATE') $action = 'u';
						if($action == 'DELETE') $action = 'd';
                        $sql = "INSERT INTO sys_datalog (dbtable,dbidx,server_id,action,tstamp,user,data) VALUES ('".$this->formDef['db_table']."','$dbidx','$server_id','$action','".time()."','$username','$diffstr')";
						$app->db->query($sql);
                }

                return true;

        }

        function getAuthSQL($perm) {
				if($_SESSION["s"]["user"]["typ"] == 'admin') {
					return '1';
				} else {
                	$groups = ( $_SESSION["s"]["user"]["groups"] ) ? $_SESSION["s"]["user"]["groups"] : 0;
					$sql = '(';
                	$sql .= "(sys_userid = ".$_SESSION["s"]["user"]["userid"]." AND sys_perm_user like '%$perm%') OR  ";
                	$sql .= "(sys_groupid IN (".$groups.") AND sys_perm_group like '%$perm%') OR ";
                	$sql .= "sys_perm_other like '%$perm%'";
                	$sql .= ')';

                	return $sql;
				}
        }

        /*
        This function checks if a user has the parmissions $perm for the data record with the ID $record_id
        If record_id = 0, the the permissions are tested against the defaults of the form file.
        */
        function checkPerm($record_id,$perm) {
                global $app;

                if($record_id > 0) {
                        // Add backticks for incomplete table names.
                        if(stristr($this->formDef['db_table'],'.')) {
                                $escape = '';
                        } else {
                                $escape = '`';
                        }

                        $sql = "SELECT ".$this->formDef['db_table_idx']." FROM ".$escape.$this->formDef['db_table'].$escape." WHERE ".$this->formDef['db_table_idx']." = ".$record_id." AND ".$this->getAuthSQL($perm);
                        if($record = $app->db->queryOneRecord($sql)) {
                                return true;
                        } else {
                                return false;
                        }
                } else {
                        $result = false;
                        if(@$this->formDef["auth_preset"]["userid"] == $_SESSION["s"]["user"]["userid"] && stristr($perm,$this->formDef["auth_preset"]["perm_user"])) $result = true;
                        if(@$this->formDef["auth_preset"]["groupid"] == $_SESSION["s"]["user"]["groupid"] && stristr($perm,$this->formDef["auth_preset"]["perm_group"])) $result = true;
                        if(@stristr($this->formDef["auth_preset"]["perm_other"],$perm)) $result = true;

                        // if preset == 0, everyone can insert a record of this type
                        if($this->formDef["auth_preset"]["userid"] == 0 AND $this->formDef["auth_preset"]["groupid"] == 0 AND (@stristr($this->formDef["auth_preset"]["perm_user"],$perm) OR @stristr($this->formDef["auth_preset"]["perm_group"],$perm))) $result = true;

                        return $result;

                }

        }

        function getNextTab() {
                // Which tab is shown
                if($this->errorMessage == '') {
                    // If there is no error
                    if(isset($_REQUEST["next_tab"]) && $_REQUEST["next_tab"] != '') {
                                // If the next tab is known
                                $active_tab = $_REQUEST["next_tab"];
                    } else {
                        // else use the default tab
                        $active_tab = $this->formDef['tab_default'];
                    }
                } else {
                    // Show the same tab again in case of an error
                    $active_tab = $_SESSION["s"]["form"]["tab"];
                }

                return $active_tab;
        }

        function getCurrentTab() {
                return $_SESSION["s"]["form"]["tab"];
        }
		
		function isReadonlyTab($tab) {
			if(isset($this->formDef['tabs'][$tab]['readonly']) && $this->formDef['tabs'][$tab]['readonly'] == true) {
				return true;
			} else {
				return false;
			}
        }
		
		
		// translation function for forms, tries the form wordbook first and if this fails, it tries the global wordbook
		function lng($msg) {
			global $app;
			
			if(isset($this->wordbook[$msg])) {
				return $this->wordbook[$msg];
			} else {
				return $app->lng($msg);
			}
			
		}

}

?>