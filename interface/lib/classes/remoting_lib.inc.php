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
* Funktionen zur Umwandlung von Formulardaten
* sowie zum vorbereiten von HTML und SQL
* Ausgaben
*
*        Tabellendefinition
*
*        Datentypen:
*        - INTEGER (Wandelt Ausdrücke in Int um)
*        - DOUBLE
*        - CURRENCY (Formatiert Zahlen nach Währungsnotation)
*        - VARCHAR (kein weiterer Format Check)
*        - DATE (Datumsformat, Timestamp Umwandlung)
*
*        Formtype:
*        - TEXT (normales Textfeld)
*        - PASSWORD (Feldinhalt wird nicht angezeigt)
*        - SELECT (Gibt Werte als option Feld aus)
*        - MULTIPLE (Select-Feld mit nehreren Werten)
*
*        VALUE:
*        - Wert oder Array
*
*        SEPARATOR
*        - Trennzeichen für multiple Felder
*
*        Hinweis:
*        Das ID-Feld ist nicht bei den Table Values einzufügen.
*
* @package form
* @author Till Brehm
* @version 1.1
*/

class remoting_lib {

        /**
        * Definition of the database atble (array)
        * @var tableDef
        */
        private $tableDef;

        /**
        * Private
        * @var action
        */
        private $action;

        /**
        * Table name (String)
        * @var table_name
        */
        private $table_name;

        /**
        * Debug Variable
        * @var debug
        */
        private $debug = 0;

        /**
        * name of the primary field of the database table (string)
        * @var table_index
        */
        var $table_index;

        /**
        * contains the error messages
        * @var errorMessage
        */
        var $errorMessage = '';

        var $dateformat = "d.m.Y";
    	var $formDef = array();
        var $wordbook;
        var $module;
        var $primary_id;
		var $diffrec = array();
		
		var $sys_username;
		var $sys_userid;
		var $sys_default_group;
		var $sys_groups;

		
		//* Load the form definition from file.
    	function loadFormDef($file) {
			global $app,$conf;
            
			include_once($file);
				
			$this->formDef = $form;
			unset($this->formDef['tabs']);
                
			//* Copy all fields from all tabs into one form definition
			foreach($form['tabs'] as $tab) {
				foreach($tab['fields'] as $key => $value) {
					$this->formDef['fields'][$key] = $value;
				}
			}
			unset($form);
				
            return true;
        }
		
		//* Load the user profile
		function loadUserProfile($client_id = 0) {
			global $app,$conf;
			
			$client_id = intval($client_id);
			
			if($client_id == 0) {
				$this->sys_username 		= 'admin';
				$this->sys_userid			= 1;
				$this->sys_default_group 	= 1;
				$this->sys_groups			= 1;
			} else {
				//* Load the client data
				$client = $app->db->queryOneRecord("SELECT username FROM client WHERE client_id = $client_id");
				if($client["username"] == '') {
					$this->errorMessage .= 'No client with ID $client_id found.';
					return false;
				}
				//* load system user
				$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE username = '".$app->db->quote($client["username"])."'");
				if(empty($user["userid"])) {
					$this->errorMessage .= 'No user with the username '.$client['username'].' found.';
					return false;
				}
				$this->sys_username 		= $user['username'];
				$this->sys_userid			= $user['userid'];
				$this->sys_default_group 	= $user['default_group'];
				$this->sys_groups 			= $user['groups'];
			}
			
			return true;
			
		}


        /**
        * Converts data in human readable form
        *
        * @param record
        * @return record
        */
        function decode($record) {
                $new_record = '';
				if(is_array($record)) {
                        foreach($this->formDef['fields'] as $key => $field) {
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
        * Get the key => value array of a form filled from a datasource definitiom
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
                        $querystring = str_replace("{USERID}",$this->sys_userid,$querystring);
                        $querystring = str_replace("{GROUPID}",$this->sys_default_group,$querystring);
                        $querystring = str_replace("{GROUPS}",$this->sys_groups,$querystring);
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
                                $this->errorMessage .= "Custom datasource class or function is empty<br>\r\n";
                        }
                }

                return $values;

        }

        /**
        * Converts the data in a format to store it in the database table
        *
        * @param record = Datensatz als Array
        * @return record
        */
        function encode($record) {

                if(is_array($record)) {
                        foreach($this->formDef['fields'] as $key => $field) {

                                if(isset($field['validators']) && is_array($field['validators'])) $this->validateField($key, (isset($record[$key]))?$record[$key]:'', $field['validators']);

                                switch ($field['datatype']) {
                                case 'VARCHAR':
                                        if(!@is_array($record[$key])) {
                                                $new_record[$key] = (isset($record[$key]))?addslashes($record[$key]):'';
                                        } else {
                                                $new_record[$key] = implode($field['separator'],$record[$key]);
                                        }
                                break;
                                case 'TEXT':
                                        if(!is_array($record[$key])) {
                                                $new_record[$key] = addslashes($record[$key]);
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
                                        $new_record[$key] = (isset($record[$key]))?intval($record[$key]):0;
                                        //if($new_record[$key] != $record[$key]) $new_record[$key] = $field['default'];
                                        //if($key == 'refresh') die($record[$key]);
                                break;
                                case 'DOUBLE':
                                        $new_record[$key] = addslashes($record[$key]);
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
                                                $this->errorMessage .= $errmsg."\r\n";
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
                                                	$this->errorMessage .= $this->wordbook[$errmsg]."<br>\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br>\r\n";
												}
                                        }
                                break;
                                case 'UNIQUE':
                                        if($this->action == 'NEW') {
                                                $num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM ".$escape.$this->formDef['db_table'].$escape. " WHERE $field_name = '".$app->db->quote($field_value)."'");
                                                if($num_rec["number"] > 0) {
                                                        $errmsg = $validator['errmsg'];
														if(isset($this->wordbook[$errmsg])) {
                                                        	$this->errorMessage .= $this->wordbook[$errmsg]."<br>\r\n";
														} else {
															$this->errorMessage .= $errmsg."<br>\r\n";
														}
                                                }
                                        } else {
                                                $num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM ".$escape.$this->formDef['db_table'].$escape. " WHERE $field_name = '".$app->db->quote($field_value)."' AND ".$this->formDef['db_table_idx']." != ".$this->primary_id);
                                                if($num_rec["number"] > 0) {
                                                        $errmsg = $validator['errmsg'];
                                                        if(isset($this->wordbook[$errmsg])) {
                                                        	$this->errorMessage .= $this->wordbook[$errmsg]."<br>\r\n";
														} else {
															$this->errorMessage .= $errmsg."<br>\r\n";
														}
                                                }
                                        }
                                break;
                                case 'NOTEMPTY':
                                        if(empty($field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                    $this->errorMessage .= $this->wordbook[$errmsg]."<br>\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br>\r\n";
												}
                                        }
                                break;
                                case 'ISEMAIL':
                                        if(!preg_match("/^\w+[\w.-]*\w+@\w+[\w.-]*\w+\.[a-z]{2,10}$/i", $field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                    $this->errorMessage .= $this->wordbook[$errmsg]."<br>\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br>\r\n";
												}
                                        }
                                break;
                                case 'ISINT':
                                        $tmpval = intval($field_value);
                                        if($tmpval === 0 and !empty($field_value)) {
                                                $errmsg = $validator['errmsg'];
                                                if(isset($this->wordbook[$errmsg])) {
                                                    $this->errorMessage .= $this->wordbook[$errmsg]."<br>\r\n";
												} else {
													$this->errorMessage .= $errmsg."<br>\r\n";
												}
                                        }
                                break;
                                case 'ISPOSITIVE':
                                        if(!is_numeric($field_value) || $field_value <= 0){
                                          $errmsg = $validator['errmsg'];
                                          if(isset($this->wordbook[$errmsg])) {
                                             $this->errorMessage .= $this->wordbook[$errmsg]."<br>\r\n";
										  } else {
											 $this->errorMessage .= $errmsg."<br>\r\n";
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
                                                $this->errorMessage .= "Custom validator class or function is empty<br>\r\n";
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
        * Create SQL statement
        *
        * @param record = Datensatz als Array
        * @param action = INSERT oder UPDATE
        * @param primary_id
        * @return record
        */
        function getSQL($record, $action = 'INSERT', $primary_id = 0, $sql_ext_where = '') {

                global $app;

                $this->action = $action;
                $this->primary_id = $primary_id;

                $record = $this->encode($record,$tab);
                $sql_insert_key = '';
                $sql_insert_val = '';
                $sql_update = '';

                if(!is_array($this->formDef)) $app->error("No form definition found.");

                // gehe durch alle Felder des Tabs
                if(is_array($record)) {
                foreach($this->formDef['fields'] as $key => $field) {
                                // Wenn es kein leeres Passwortfeld ist
                                if (!($field['formtype'] == 'PASSWORD' and $record[$key] == '')) {
                                        // Erzeuge Insert oder Update Quelltext
                                        if($action == "INSERT") {
                                                if($field['formtype'] == 'PASSWORD') {
                                                        $sql_insert_key .= "`$key`, ";
                                                        if($field['encryption'] == 'CRYPT') {
                                                                $salt="$1$";
																for ($n=0;$n<8;$n++) {
																	$salt.=chr(mt_rand(64,126));
																}
																$salt.="$";
																// $salt = substr(md5(time()),0,2);
																$record[$key] = crypt($record[$key],$salt);
                                                        } else {
                                                                $record[$key] = md5($record[$key]);
                                                        }
														$sql_insert_val .= "'".$record[$key]."', ";
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
														if($field['encryption'] == 'CRYPT') {
                                                                $salt="$1$";
																for ($n=0;$n<8;$n++) {
																	$salt.=chr(mt_rand(64,126));
																}
																$salt.="$";
																// $salt = substr(md5(time()),0,2);
																$record[$key] = crypt($record[$key],$salt);
                                                        } else {
                                                                $record[$key] = md5($record[$key]);
                                                        }
                                                        $sql_update .= "`$key` = '".$record[$key]."', ";
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



                if(stristr($this->formDef['db_table'],'.')) {
                        $escape = '';
                } else {
                        $escape = '`';
                }


                if($action == "INSERT") {
                        if($this->formDef['auth'] == 'yes') {
                                // Setze User und Gruppe
                                $sql_insert_key .= "`sys_userid`, ";
                                $sql_insert_val .= ($this->formDef["auth_preset"]["userid"] > 0)?"'".$this->formDef["auth_preset"]["userid"]."', ":"'".$this->sys_userid."', ";
                                $sql_insert_key .= "`sys_groupid`, ";
                                $sql_insert_val .= ($this->formDef["auth_preset"]["groupid"] > 0)?"'".$this->formDef["auth_preset"]["groupid"]."', ":"'".$this->sys_default_group."', ";
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
                        if($primary_id != 0) {
                                $sql_update = substr($sql_update,0,-2);
                                $sql = "UPDATE ".$escape.$this->formDef['db_table'].$escape." SET ".$sql_update." WHERE ".$this->formDef['db_table_idx']." = ".$primary_id;
                                if($sql_ext_where != '') $sql .= " and ".$sql_ext_where;
                        } else {
                                $app->error("Primary ID fehlt!");
                        }
                }
                
                return $sql;
        }


		function getDataRecord($primary_id) {
			global $app;
			$escape = '`';
			$sql = "SELECT * FROM ".$escape.$this->formDef['db_table'].$escape." WHERE ".$this->formDef['db_table_idx']." = ".$primary_id;
            return $app->db->queryOneRecord($sql);
		}
		

        function datalogSave($action,$primary_id, $record_old, $record_new) {
                global $app,$conf;

                if(stristr($this->formDef['db_table'],'.')) {
                        $escape = '';
                } else {
                        $escape = '`';
                }

                $diffrec = array();
				
                if(is_array($record_new) && count($record_new) > 0) {
                        foreach($record_new as $key => $val) {
                                if($record_old[$key] != $val) {
										// Record has changed
                                        $diffrec[$key] = array('old' => $record_old[$key],
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
				
				
				// Full diff records for ISPConfig, they have a different format then the simple diffrec
				$diffrec_full = array();

                if(is_array($record_old) && count($record_old) > 0) {
                        foreach($record_old as $key => $val) {
                                if(isset($record_new[$key]) && $record_new[$key] != $val) {
                                    // Record has changed
									$diffrec_full['old'][$key] = $val;
									$diffrec_full['new'][$key] = $record_new[$key];
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
                                } else {
									$diffrec_full['new'][$key] = $val;
									$diffrec_full['old'][$key] = $val;
								}
                        }
                }
				
				/*
				echo "<pre>";
				print_r($diffrec_full);
				echo "</pre>";
				*/
				
				// Insert the server_id, if the record has a server_id
				$server_id = (isset($record_old["server_id"]) && $record_old["server_id"] > 0)?$record_old["server_id"]:0;
				if(isset($record_new["server_id"])) $server_id = $record_new["server_id"];

                if(count($this->diffrec) > 0) {
						$diffstr = $app->db->quote(serialize($diffrec_full));
                        $username = $app->db->quote($this->sys_username);
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

}

?>