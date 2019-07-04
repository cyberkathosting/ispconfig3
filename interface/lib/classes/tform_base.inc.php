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
 *        - DATE (Date format, converts from and to UNIX timestamps automatically)
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
 *        Hint: The auto increment (ID) filed of the table has not be be definied separately.
 *
 */


class tform_base {

	/**
	 * Definition of the database table (array)
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
	 * Debug Variable
	 * @var debug
	 */
	var $debug = 0;

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
	var $datetimeformat = 'd.m.Y H:i'; // is set to the correct value in loadFormDef
	var $formDef = array();
	var $wordbook;
	var $module;
	var $primary_id;
	var $diffrec = array();
	var $primary_id_override = 0;

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

	function loadFormDef($file, $module = '') {
		global $app, $conf;

		include $file;
		$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$form['name'] . ':on_before_formdef', $this);
		$this->formDef = $form;

		$this->module = $module;
		$wb = array();

		include_once ISPC_ROOT_PATH.'/lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'.lng';

		if(is_array($wb)) $wb_global = $wb;

		if($module == '') {
			$lng_file = "lib/lang/".$_SESSION["s"]["language"]."_".$this->formDef["name"].".lng";
			if(!file_exists($lng_file)) $lng_file = "lib/lang/en_".$this->formDef["name"].".lng";
			include $lng_file;
		} else {
			$lng_file = "../$module/lib/lang/".$app->functions->check_language($_SESSION["s"]["language"])."_".$this->formDef["name"].".lng";
			if(!file_exists($lng_file)) $lng_file = "../$module/lib/lang/en_".$this->formDef["name"].".lng";
			include $lng_file;
		}

		if(is_array($wb_global)) {
			$wb = $app->functions->array_merge($wb_global, $wb);
		}
		if(isset($wb_global)) unset($wb_global);
		
		$this->wordbook = $wb;
		
		$app->plugin->raiseEvent($_SESSION['s']['module']['name'].':'.$app->tform->formDef['name'] . ':on_after_formdef', $this);

		$this->dateformat = $app->lng('conf_format_dateshort');
		$this->datetimeformat = $app->lng('conf_format_datetime');

		return true;
	}

	/*
		* Converts the data in the array to human readable format
		* Datatype conversion e.g. to show the data in lists
		*
		* @param record
		* @param tab
		* @param apply_filters
		* @return record
		*/
	protected function _decode($record, $tab = '', $api = false) {
		global $app;
		$new_record = array();
		if($api == false) {
			$table_idx = $this->formDef['db_table_idx'];
			if(isset($record[$table_idx])) $new_record[$table_idx] = $app->functions->intval($record[$table_idx ]);
			$fields = &$this->formDef['tabs'][$tab]['fields'];
		} else {
			$fields = &$this->formDef['fields'];
		}

		if(is_array($record)) {
			foreach($fields as $key => $field) {

				//* Apply filter to record value.
				if($api == false && isset($field['filters']) && is_array($field['filters'])) {
					$record[$key] = $this->filterField($key, (isset($record[$key]))?$record[$key]:'', $field['filters'], 'SHOW');
				}

				switch ($field['datatype']) {
				case 'VARCHAR':
					$new_record[$key] = ($api == true ? stripslashes($record[$key]) : $record[$key]);
					break;

				case 'TEXT':
					$new_record[$key] = ($api == true ? stripslashes($record[$key]) : $record[$key]);
					break;

				case 'DATETSTAMP':
					if($record[$key] > 0) {
						$new_record[$key] = date($this->dateformat, $record[$key]);
					}
					break;

				case 'DATE':
					if($record[$key] != '' && !is_null($record[$key]) && $record[$key] != '0000-00-00') {
						$tmp = explode('-', $record[$key]);
						$new_record[$key] = date($this->dateformat, mktime(0, 0, 0, $tmp[1]  , $tmp[2], $tmp[0]));
					}
					break;

				case 'INTEGER':
					$new_record[$key] = $app->functions->intval($record[$key]);
					break;

				case 'DOUBLE':
					$new_record[$key] = $record[$key];
					break;

				case 'CURRENCY':
					$new_record[$key] = $app->functions->currency_format($record[$key]);
					break;

				default:
					$new_record[$key] = ($api == true ? stripslashes($record[$key]) : $record[$key]);
				}
			}

		}

		return $new_record;
	}


	/**
	 * Converts the data in the array to human readable format
	 * Datatype conversion e.g. to show the data in lists
	 *
	 * @param record
	 * @return record
	 */
	function decode($record, $tab) {
		global $conf, $app;
		if(!is_array($this->formDef['tabs'][$tab])) $app->error("Tab does not exist or the tab is empty (TAB: ".$app->functions->htmlentities($tab).").");
		return $this->_decode($record, $tab, false);
	}

	/**
	 * Get the key => value array of a form filled from a datasource definitiom
	 *
	 * @param field = array with field definition
	 * @param record = Dataset as array
	 * @return key => value array for the value field of a form
	 */
	protected function _getDatasourceData($field, $record, $api = false) {
		global $app;

		$values = array();

		if($field["datasource"]["type"] == 'SQL') {

			// Preparing SQL string. We will replace some
			// common placeholders
			$querystring = $field["datasource"]["querystring"];
			$querystring = str_replace("{USERID}", $_SESSION["s"]["user"]["userid"], $querystring);
			$querystring = str_replace("{GROUPID}", $_SESSION["s"]["user"]["default_group"], $querystring);
			$querystring = str_replace("{GROUPS}", $_SESSION["s"]["user"]["groups"], $querystring);
			$table_idx = $this->formDef['db_table_idx'];

			$tmp_recordid = (isset($record[$table_idx]))?$record[$table_idx]:0;
			$querystring = str_replace("{RECORDID}", $tmp_recordid, $querystring);
			unset($tmp_recordid);

			$querystring = str_replace("{AUTHSQL}", $this->getAuthSQL('r'), $querystring);
			$querystring = preg_replace_callback('@{AUTHSQL::(.+?)}@', create_function('$matches','global $app; $tmp = $app->tform->getAuthSQL("r", $matches[1]); return $tmp;'), $querystring);

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

		if($api == false && isset($field['filters']) && is_array($field['filters'])) {
			$new_values = array();
			foreach($values as $index => $value) {
				$new_index = $this->filterField($index, $index, $field['filters'], 'SHOW');
				$new_values[$new_index] = $this->filterField($index, (isset($values[$index]))?$values[$index]:'', $field['filters'], 'SHOW');
			}
			$values = $new_values;
			unset($new_values);
			unset($new_index);
		}

		return $values;

	}

	/*
	function table_auth_sql($matches){
		return $this->getAuthSQL('r', $matches[1]);
	}
	*/
	
	/**
	 * Get the key => value array of a form filled from a datasource definitiom
	 *
	 * @param field = array with field definition
	 * @param record = Dataset as array
	 * @return key => value array for the value field of a form
	 */
	function getDatasourceData($field, $record) {
		return $this->_getDatasourceData($field, $record, false);
	}

	//* If the parameter 'valuelimit' is set
	function applyValueLimit($limit, $values, $current_value = '') {

		global $app;
		
		// we mas have multiple limits, therefore we explode by ; first
		// Example: "system:sites:web_php_options;client:web_php_options"
		$limits = explode(';',$limit);
		
		
		foreach($limits as $limit) {

			$limit_parts = explode(':', $limit);

			//* values are limited to a comma separated list
			if($limit_parts[0] == 'list') {
				$allowed = explode(',', $limit_parts[1]);
			}

			//* values are limited to a field in the client settings
			if($limit_parts[0] == 'client') {
				if($_SESSION["s"]["user"]["typ"] == 'admin') {
					return $values;
				} else {
					$client_group_id = $_SESSION["s"]["user"]["default_group"];
					$client = $app->db->queryOneRecord("SELECT ".$limit_parts[1]." as lm FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
					$allowed = explode(',', $client['lm']);
				}
			}

			//* values are limited to a field in the reseller settings
			if($limit_parts[0] == 'reseller') {
				if($_SESSION["s"]["user"]["typ"] == 'admin') {
					return $values;
				} else {
					//* Get the limits of the client that is currently logged in
					$client_group_id = $_SESSION["s"]["user"]["default_group"];
					$client = $app->db->queryOneRecord("SELECT parent_client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
					//echo "SELECT parent_client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id";
					//* If the client belongs to a reseller, we will check against the reseller Limit too
					if($client['parent_client_id'] != 0) {

						//* first we need to know the groups of this reseller
						$tmp = $app->db->queryOneRecord("SELECT userid, groups FROM sys_user WHERE client_id = ?", $client['parent_client_id']);
						$reseller_groups = $tmp["groups"];
						$reseller_userid = $tmp["userid"];

						// Get the limits of the reseller of the logged in client
						$client_group_id = $_SESSION["s"]["user"]["default_group"];
						$reseller = $app->db->queryOneRecord("SELECT ".$limit_parts[1]." as lm FROM client WHERE client_id = ?", $client['parent_client_id']);
						$allowed = explode(',', $reseller['lm']);
					} else {
						return $values;
					}
				} // end if admin
			} // end if reseller

			//* values are limited to a field in the system settings
			if($limit_parts[0] == 'system') {
				$app->uses('getconf');
				$tmp_conf = $app->getconf->get_global_config($limit_parts[1]);
				$tmp_key = $limit_parts[2];
				$allowed = $allowed = explode(',',$tmp_conf[$tmp_key]);
			}
			
			// add the current value to the allowed array
			$allowed[] = $current_value;

			// remove all values that are not allowed
			$values_new = array();
			foreach($values as $key => $val) {
				if(in_array($key, $allowed)) $values_new[$key] = $val;
			}

			$values = $values_new;

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
		if(!is_array($this->formDef['tabs'][$tab])) $app->error("The tab is empty or does not exist (TAB: ".$app->functions->htmlentities($tab).").");

		/* CSRF PROTECTION */
		// generate csrf protection id and key
		$csrf_token = $app->auth->csrf_token_get($this->formDef['name']);
		$_csrf_id = $csrf_token['csrf_id'];
		$_csrf_value = $csrf_token['csrf_key'];
		
		$this->formDef['tabs'][$tab]['fields']['_csrf_id'] = array(
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => $_csrf_id,
			'value' => $_csrf_id
		);
		$this->formDef['tabs'][$tab]['fields']['_csrf_key'] = array(
			'datatype' => 'VARCHAR',
			'formtype' => 'TEXT',
			'default' => $_csrf_value,
			'value' => $_csrf_value
		);
		$record['_csrf_id'] = $_csrf_id;
		$record['_csrf_key'] = $_csrf_value;
		/* CSRF PROTECTION */
		
		$new_record = array();
		if($action == 'EDIT') {
			$record = $this->decode($record, $tab);
			if(is_array($record)) {
				foreach($this->formDef['tabs'][$tab]['fields'] as $key => $field) {

					if(isset($record[$key])) {
						$val = $record[$key];
					} else {
						$val = '';
					}

					// If Datasource is set, get the data from there
					if(isset($field['datasource']) && is_array($field['datasource'])) {
						if(is_array($field["value"])) {
							//$field["value"] = array_merge($field["value"],$this->getDatasourceData($field, $record));
							$field["value"] = $app->functions->array_merge($field["value"], $this->getDatasourceData($field, $record));
						} else {
							$field["value"] = $this->getDatasourceData($field, $record);
						}
					}

					// If a limitation for the values is set
					if(isset($field['valuelimit']) && is_array($field["value"])) {
						$field["value"] = $this->applyValueLimit($field['valuelimit'], $field["value"], $val);
					}

					switch ($field['formtype']) {
					case 'SELECT':
						$out = '';
						if(is_array($field['value'])) {
							foreach($field['value'] as $k => $v) {
								$selected = ($k == $val)?' SELECTED':'';
								if(isset($this->wordbook[$v])) $v = $this->wordbook[$v];
								else $v = $app->functions->htmlentities($v);
								$out .= "<option value='$k'$selected>".$this->lng($v)."</option>\r\n";
							}
						}
						$new_record[$key] = $out;
						break;
					case 'MULTIPLE':
						if(is_array($field['value'])) {

							// Split
							$vals = explode($field['separator'], $val);

							// write HTML
							$out = '';
							foreach($field['value'] as $k => $v) {

								$selected = '';
								foreach($vals as $tvl) {
									if(trim($tvl) == trim($k)) $selected = ' SELECTED';
								}
								$v = $app->functions->htmlentities($v);
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
						$new_record[$key] = "<input name=\"".$key."\" id=\"".$key."\" value=\"".$field['value'][1]."\" type=\"checkbox\" $checked />\r\n";
						break;

					case 'CHECKBOXARRAY':
						if(is_array($field['value'])) {

							// aufsplitten ergebnisse
							$vals = explode($field['separator'], $val);

							// HTML schreiben
							$out = '';
							$elementNo = 0;
							foreach($field['value'] as $k => $v) {

								$checked = '';
								foreach($vals as $tvl) {
									if(trim($tvl) == trim($k)) $checked = ' CHECKED';
								}
								// $out .= "<label for=\"".$key."[]\" class=\"inlineLabel\"><input name=\"".$key."[]\" id=\"".$key."[]\" value=\"$k\" type=\"checkbox\" $checked /> $v</label>\r\n";
								$out .= "<label for=\"".$key.$elementNo."\" class=\"inlineLabel\"><input name=\"".$key."[]\" id=\"".$key.$elementNo."\" value=\"$k\" type=\"checkbox\" $checked /> $v</label><br/>\r\n";
								$elementNo++;
							}
						}
						$new_record[$key] = $out;
						break;

					case 'RADIO':
						if(is_array($field['value'])) {

							// HTML schreiben
							$out = '';
							$elementNo = 0;
							foreach($field['value'] as $k => $v) {
								$checked = ($k == $val)?' CHECKED':'';
								//$out .= "<label for=\"".$key."[]\" class=\"inlineLabel\"><input name=\"".$key."[]\" id=\"".$key."[]\" value=\"$k\" type=\"radio\" $checked/> $v</label>\r\n";
								$out .= "<label for=\"".$key.$elementNo."\" class=\"inlineLabel\"><input name=\"".$key."[]\" id=\"".$key.$elementNo."\" value=\"$k\" type=\"radio\" $checked/> $v </label>\r\n";
								$elementNo++;
							}
						}
						$new_record[$key] = $out;
						break;

					case 'DATETIME':
						if (strtotime($val) !== false) {
							$dt_value = $val;
						} elseif ( isset($field['default']) && (strtotime($field['default']) !== false) ) {
							$dt_value = $field['default'];
						} else {
							$dt_value = 0;
						}

						$display_seconds = (isset($field['display_seconds']) && $field['display_seconds'] == true) ? true : false;

						$new_record[$key] = $this->_getDateTimeHTML($key, $dt_value, $display_seconds);
						break;

					case 'DATE':
						if (strtotime($val) !== false) {
							$dt_value = $val;
						} elseif ( isset($field['default']) && (strtotime($field['default']) !== false) ) {
							$dt_value = $field['default'];
						} else {
							$dt_value = 0;
						}

						$new_record[$key] = $this->_getDateHTML($key, $dt_value);
						break;
					
					default:
						if(isset($record[$key])) {
							$new_record[$key] = $app->functions->htmlentities($record[$key]);
						} else {
							$new_record[$key] = '';
						}
					}
				}
			}
		} else {
			// Action: NEW
			foreach($this->formDef['tabs'][$tab]['fields'] as $key => $field) {

				// If Datasource is set, get the data from there
				if(@is_array($field['datasource'])) {
					if(is_array($field["value"])) {
						$field["value"] = $app->functions->array_merge($field["value"], $this->getDatasourceData($field, $record));
					} else {
						$field["value"] = $this->getDatasourceData($field, $record);
					}
				}

				// If a limitation for the values is set
				if(isset($field['valuelimit']) && is_array($field["value"])) {
					$field["value"] = $this->applyValueLimit($field['valuelimit'], $field["value"], $field['default']);
				}

				switch ($field['formtype']) {
				case 'SELECT':
					if(is_array($field['value'])) {
						$out = '';
						foreach($field['value'] as $k => $v) {
							$selected = ($k == $field["default"])?' SELECTED':'';
							$v = $app->functions->htmlentities($this->lng($v));
							$out .= "<option value='$k'$selected>".$v."</option>\r\n";
						}
					}
					if(isset($out)) $new_record[$key] = $out;
					break;
				case 'MULTIPLE':
					if(is_array($field['value'])) {

						// aufsplitten ergebnisse
						$vals = explode($field['separator'], $val);

						// HTML schreiben
						$out = '';
						foreach($field['value'] as $k => $v) {
							$v = $app->functions->htmlentities($v);
							$out .= "<option value='$k'>$v</option>\r\n";
						}
					}
					$new_record[$key] = $out;
					break;

				case 'PASSWORD':
					//$new_record[$key] = '';
					$new_record[$key] = htmlspecialchars($field['default']);
					break;

				case 'CHECKBOX':
					// $checked = (empty($field["default"]))?'':' CHECKED';
					$checked = ($field["default"] == $field['value'][1])?' CHECKED':'';
					$new_record[$key] = "<input name=\"".$key."\" id=\"".$key."\" value=\"".$field['value'][1]."\" type=\"checkbox\" $checked />\r\n";
					break;

				case 'CHECKBOXARRAY':
					if(is_array($field['value'])) {

						// aufsplitten ergebnisse
						$vals = explode($field['separator'], $field["default"]);

						// HTML schreiben
						$out = '';
						$elementNo = 0;
						foreach($field['value'] as $k => $v) {

							$checked = '';
							foreach($vals as $tvl) {
								if(trim($tvl) == trim($k)) $checked = ' CHECKED';
							}
							// $out .= "<label for=\"".$key."[]\" class=\"inlineLabel\"><input name=\"".$key."[]\" id=\"".$key."[]\" value=\"$k\" type=\"checkbox\" $checked /> $v</label>\r\n";
							$out .= "<label for=\"".$key.$elementNo."\" class=\"inlineLabel\"><input name=\"".$key."[]\" id=\"".$key.$elementNo."\" value=\"$k\" type=\"checkbox\" $checked /> $v</label> &nbsp;\r\n";
							$elementNo++;
						}
					}
					$new_record[$key] = $out;
					break;

				case 'RADIO':
					if(is_array($field['value'])) {

						// HTML schreiben
						$out = '';
						$elementNo = 0;
						foreach($field['value'] as $k => $v) {
							$checked = ($k == $field["default"])?' CHECKED':'';
							//$out .= "<label for=\"".$key."[]\" class=\"inlineLabel\"><input name=\"".$key."[]\" id=\"".$key."[]\" value=\"$k\" type=\"radio\" $checked/> $v</label>\r\n";
							$out .= "<label for=\"".$key.$elementNo."\" class=\"inlineLabel\"><input name=\"".$key."[]\" id=\"".$key.$elementNo."\" value=\"$k\" type=\"radio\" $checked/> $v</label>\r\n";
							$elementNo++;
						}
					}
					$new_record[$key] = $out;
					break;

				case 'DATETIME':
					$dt_value = (isset($field['default'])) ? $field['default'] : 0;
					$display_seconds = (isset($field['display_seconds']) && $field['display_seconds'] == true) ? true : false;

					$new_record[$key] = $this->_getDateTimeHTML($key, $dt_value, $display_seconds);
					break;
				
				case 'DATE':
					$dt_value = (isset($field['default'])) ? $field['default'] : 0;

					$new_record[$key] = $this->_getDateHTML($key, $dt_value);
					break;

				default:
					$new_record[$key] = $app->functions->htmlentities($field['default']);
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
	protected function _encode($record, $tab, $dbencode = true, $api = false) {
		global $app;
		if($api == true) {
			$fields = &$this->formDef['fields'];
		} else {
			$fields = &$this->formDef['tabs'][$tab]['fields'];
			/* CSRF PROTECTION */
			if(isset($_POST) && is_array($_POST)) {
				$_csrf_valid = false;
				if(isset($_POST['_csrf_id']) && isset($_POST['_csrf_key'])) {
					$_csrf_id = trim($_POST['_csrf_id']);
					$_csrf_key = trim($_POST['_csrf_key']);
					if(isset($_SESSION['_csrf']) && isset($_SESSION['_csrf'][$_csrf_id]) && isset($_SESSION['_csrf_timeout']) && isset($_SESSION['_csrf_timeout'][$_csrf_id])) {
						if($_SESSION['_csrf'][$_csrf_id] === $_csrf_key && $_SESSION['_csrf_timeout'] >= time()) $_csrf_valid = true;
					}
				}
				if($_csrf_valid !== true) {
					$app->log('CSRF attempt blocked. Referer: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'unknown'), LOGLEVEL_WARN);
					$errmsg = 'err_csrf_attempt_blocked';
					$this->errorMessage .= ($api == true ? $errmsg : $this->wordbook[$errmsg]."<br />") . "\r\n";
					unset($_POST);
					unset($record);
				}
				
				if(isset($_SESSION['_csrf_timeout']) && is_array($_SESSION['_csrf_timeout'])) {
					$to_unset = array();
					foreach($_SESSION['_csrf_timeout'] as $_csrf_id => $timeout) {
						if($timeout < time()) $to_unset[] = $_csrf_id;
					}
					foreach($to_unset as $_csrf_id) {
						$_SESSION['_csrf'][$_csrf_id] = null;
						$_SESSION['_csrf_timeout'][$_csrf_id] = null;
						unset($_SESSION['_csrf'][$_csrf_id]);
						unset($_SESSION['_csrf_timeout'][$_csrf_id]);
					}
					unset($to_unset);
				}
			}
			/* CSRF PROTECTION */
		}
		
		$new_record = array();
		if(is_array($record)) {
			foreach($fields as $key => $field) {

				//* Apply filter to record value
				if(isset($field['filters']) && is_array($field['filters'])) {
					$record[$key] = $this->filterField($key, (isset($record[$key]))?$record[$key]:'', $field['filters'], 'SAVE');
				}
				//* Validate record value
				if(isset($field['validators']) && is_array($field['validators'])) {
					$this->validateField($key, (isset($record[$key]))?$record[$key]:'', $field['validators']);
				}

				switch ($field['datatype']) {
				case 'VARCHAR':
					if(!@is_array($record[$key])) {
						$new_record[$key] = (isset($record[$key]))?$record[$key]:'';
					} else {
						$new_record[$key] = implode($field['separator'], $record[$key]);
					}
					break;
				case 'TEXT':
					if(!is_array($record[$key])) {
						$new_record[$key] = $record[$key];
					} else {
						$new_record[$key] = implode($field['separator'], $record[$key]);
					}
					break;
				case 'DATETSTAMP':
					if($record[$key] > 0) {
						list($tag, $monat, $jahr) = explode('.', $record[$key]);
						$new_record[$key] = mktime(0, 0, 0, $monat, $tag, $jahr);
					} else {
						$new_record[$key] = 0;
					}
					break;
				case 'DATE':
					if($record[$key] != '' && !is_null($record[$key]) && $record[$key] != '0000-00-00') {
						if(function_exists('date_parse_from_format')) {
							$date_parts = date_parse_from_format($this->dateformat, $record[$key]);
							$new_record[$key] = $date_parts['year'].'-'.str_pad($date_parts['month'], 2, "0", STR_PAD_LEFT).'-'.str_pad($date_parts['day'], 2, "0", STR_PAD_LEFT);
						} else {
							$tmp = strtotime($record[$key]);
							$new_record[$key] = date('Y-m-d', $tmp);
						}
					} else {
						$new_record[$key] = null;
					}
					break;
				case 'INTEGER':
					$new_record[$key] = (isset($record[$key]))?$app->functions->intval($record[$key]):0;
					break;
				case 'DOUBLE':
					$new_record[$key] = $record[$key];
					break;
				case 'CURRENCY':
					$new_record[$key] = $app->functions->currency_unformat($record[$key]);
					break;

				case 'DATETIME':
					/*if (is_array($record[$key]))
					{
						$filtered_values = array_map(create_function('$item', 'return (int)$item;'), $record[$key]);
						extract($filtered_values, EXTR_PREFIX_ALL, '_dt');

						if ($_dt_day != 0 && $_dt_month != 0 && $_dt_year != 0) {
							$new_record[$key] = date( 'Y-m-d H:i:s', mktime($_dt_hour, $_dt_minute, $_dt_second, $_dt_month, $_dt_day, $_dt_year) );
						}
					} else {*/
						if($record[$key] != '' && !is_null($record[$key]) && $record[$key] != '0000-00-00 00:00:00') {
							//$tmp = strtotime($record[$key]);
							//$new_record[$key] = date($this->datetimeformat, $tmp);
							$parsed_date = date_parse_from_format($this->datetimeformat,$record[$key]);
							if($parsed_date['error_count'] > 0 || ($parsed_date['year'] == 1899 && $parsed_date['month'] == 12 && $parsed_date['day'] == 31)) {
								// There was an error, set the date to 0
								$new_record[$key] = null;
							} else {
								// Date parsed successfully. Convert it to database format
								$new_record[$key] = date( 'Y-m-d H:i:s', mktime($parsed_date['hour'], $parsed_date['minute'], $parsed_date['second'], $parsed_date['month'], $parsed_date['day'], $parsed_date['year']) );
							}
						} else {
							$new_record[$key] = null;
						}
					/*}*/
					break;
				}

				// The use of the field value is deprecated, use validators instead
				if(isset($field['regex']) && $field['regex'] != '') {
					// Enable that "." matches also newlines
					$field['regex'] .= 's';
					if(!preg_match($field['regex'], $record[$key])) {
						$errmsg = $field['errmsg'];
						$this->errorMessage .= ($api == true ? $errmsg : $this->wordbook[$errmsg]."<br />") . "\r\n";
					}
				}

				//* Add slashes to all records, when we encode data which shall be inserted into mysql.
				if($dbencode == true && !is_null($new_record[$key])) $new_record[$key] = $app->db->quote($new_record[$key]);
			}
		}
		return $new_record;
	}


	/**
	 * Rewrite the record data to be stored in the database
	 * and check values with regular expressions.
	 *
	 * @param record = Datensatz als Array
	 * @return record
	 */
	function encode($record, $tab, $dbencode = true) {
		global $app;

		if(!is_array($this->formDef['tabs'][$tab])) $app->error("Tab is empty or does not exist (TAB: ".$app->functions->htmlentities($tab).").");
		return $this->_encode($record, $tab, $dbencode, false);
	}


	/**
	 * process the filters for a given field.
	 *
	 * @param field_name = Name of the field
	 * @param field_value = value of the field
	 * @param filters = Array of filters
	 * @param filter_event = 'SAVE'or 'SHOW'
	 * @return record
	 */
	function filterField($field_name, $field_value, $filters, $filter_event) {

		global $app;
		$returnval = $field_value;

		//* Loop trough all filters
		foreach($filters as $filter) {
			if($filter['event'] == $filter_event) {
				switch ($filter['type']) {
				case 'TOLOWER':
					$returnval = strtolower($returnval);
					break;
				case 'TOUPPER':
					$returnval = strtoupper($returnval);
					break;
				case 'IDNTOASCII':
					$returnval = $app->functions->idn_encode($returnval);
					break;
				case 'IDNTOUTF8':
					$returnval = $app->functions->idn_decode($returnval);
					break;
				case 'TOLATIN1':
					$returnval = mb_convert_encoding($returnval, 'ISO-8859-1', 'UTF-8');
					break;
				case 'TRIM':
					$returnval = trim($returnval);
					break;
				case 'NOWHITESPACE':
					$returnval = preg_replace('/\s+/', '', $returnval);
					break;
				case 'STRIPTAGS':
					$returnval = strip_tags(preg_replace('/<script[^>]*?>.*?<\/script>/is', '', $returnval));
					break;
				case 'STRIPNL':
					$returnval = str_replace(array("\n","\r"),'', $returnval);
					break;
				default:
					$this->errorMessage .= "Unknown Filter: ".$filter['type'];
					break;
				}
			}
		}
		return $returnval;
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
				if($validator['allowempty'] != 'y') $validator['allowempty'] = 'n';
				if($validator['allowempty'] == 'n' || ($validator['allowempty'] == 'y' && $field_value != '')){
					if($this->action == 'NEW') {
						$num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM ?? WHERE ?? = ?", $this->formDef['db_table'], $field_name, $field_value);
						if($num_rec["number"] > 0) {
							$errmsg = $validator['errmsg'];
							if(isset($this->wordbook[$errmsg])) {
								$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
							} else {
								$this->errorMessage .= $errmsg."<br />\r\n";
							}
						}
					} else {
						$num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM ?? WHERE ?? = ? AND ?? != ?", $this->formDef['db_table'], $field_name, $field_value, $this->formDef['db_table_idx'], $this->primary_id);
						if($num_rec["number"] > 0) {
							$errmsg = $validator['errmsg'];
							if(isset($this->wordbook[$errmsg])) {
								$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
							} else {
								$this->errorMessage .= $errmsg."<br />\r\n";
							}
						}
					}
				}
				break;
			case 'NOTEMPTY':
				if(!isset($field_value) || $field_value === '') {
					$errmsg = $validator['errmsg'];
					if(isset($this->wordbook[$errmsg])) {
						$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
					} else {
						$this->errorMessage .= $errmsg."<br />\r\n";
					}
				}
				break;
			case 'ISASCII':
				if(preg_match("/[^\x20-\x7F]/", $field_value)) {
					$errmsg = $validator['errmsg'];
					if(isset($this->wordbook[$errmsg])) {
						$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
					} else {
						$this->errorMessage .= $errmsg."<br />\r\n";
					}
				}
				break;
			case 'ISDOMAIN':
				$error = false;
				if($validator['allowempty'] != 'y') $validator['allowempty'] = 'n';
				if($validator['allowempty'] == 'y' && $field_value == '') {
					//* Do nothing
				} else {
					if(function_exists('filter_var')) {
						if(filter_var('check@'.$field_value, FILTER_VALIDATE_EMAIL) === false) {
							$errmsg = $validator['errmsg'];
							if(isset($this->wordbook[$errmsg])) {
								$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
							} else {
								$this->errorMessage .= $errmsg."<br />\r\n";
							}
						}

					} else $this->errorMessage .= "function filter_var missing <br />\r\n";
				}
				unset($error);
				break;
			case 'ISEMAIL':
				$error = false;
				if($validator['allowempty'] != 'y') $validator['allowempty'] = 'n';
				if($validator['allowempty'] == 'y' && $field_value == '') {
					//* Do nothing
				} else {
					if(function_exists('filter_var')) {
						if(filter_var($field_value, FILTER_VALIDATE_EMAIL) === false) {
							$error = true;
						} else {
							if (!preg_match("/^[^\\+]+$/", $field_value)) { // * disallow + in local-part
								$error = true;
							}
						}
						if ($error) {
							$errmsg = $validator['errmsg'];
							if(isset($this->wordbook[$errmsg])) {
								$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
							} else {
								$this->errorMessage .= $errmsg."<br />\r\n";
							}
						}

					} else $this->errorMessage .= "function filter_var missing <br />\r\n";
				}
				unset($error);
				break;
			case 'ISINT':
				if(function_exists('filter_var') && $field_value < PHP_INT_MAX) {
					//if($field_value != '' && filter_var($field_value, FILTER_VALIDATE_INT, array("options" => array('min_range'=>0))) === false) {
					if($field_value != '' && filter_var($field_value, FILTER_VALIDATE_INT) === false) {
						$errmsg = $validator['errmsg'];
						if(isset($this->wordbook[$errmsg])) {
							$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
						} else {
							$this->errorMessage .= $errmsg."<br />\r\n";
						}
					}
				} else {
					$tmpval = $app->functions->intval($field_value);
					if($tmpval === 0 and !empty($field_value)) {
						$errmsg = $validator['errmsg'];
						if(isset($this->wordbook[$errmsg])) {
							$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
						} else {
							$this->errorMessage .= $errmsg."<br />\r\n";
						}
					}
				}
				break;
			case 'ISPOSITIVE':
				if(function_exists('filter_var')) {
					if($field_value != '' && filter_var($field_value, FILTER_VALIDATE_INT, array("options" => array('min_range'=>1))) === false) {
						$errmsg = $validator['errmsg'];
						if(isset($this->wordbook[$errmsg])) {
							$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
						} else {
							$this->errorMessage .= $errmsg."<br />\r\n";
						}
					}
				} else $this->errorMessage .= "function filter_var missing <br />\r\n";
				break;
			case 'V6PREFIXEND':
				$explode_field_value = explode(':',$field_value);
				if (!$explode_field_value[count($explode_field_value)-1]=='' && $explode_field_value[count($explode_field_value)-2]!='' ) {
					$errmsg = $validator['errmsg'];
					if(isset($this->wordbook[$errmsg])) {
						$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
					} else {
						$this->errorMessage .= $errmsg."<br />\r\n";
					}
				}
				break;
			case 'V6PREFIXLENGTH':
				// find shortes ipv6 subnet can`t be longer
				$sql_v6 = $app->db->queryOneRecord("SELECT ip_address FROM server_ip WHERE ip_type = 'IPv6' AND virtualhost = 'y' ORDER BY CHAR_LENGTH(ip_address) ASC LIMIT 0,1");
				$sql_v6_explode=explode(':',$sql_v6['ip_address']);
				$explode_field_value = explode(':',$field_value);
				if (count($sql_v6_explode) < count($explode_field_value) && isset($sql_v6['ip_address'])) {
					$errmsg = $validator['errmsg'];
					if(isset($this->wordbook[$errmsg])) {
						$this->errorMessage .= $this->wordbook[$errmsg].$sql_v6[ip_address]."<br />\r\n";
					} else {
						$this->errorMessage .= $errmsg."<br />\r\n";
					}
				}
				break;
			case 'ISV6PREFIX':
				$v6_prefix_ok=0;
				$explode_field_value = explode(':',$field_value);
				if ($explode_field_value[count($explode_field_value)-1]=='' && $explode_field_value[count($explode_field_value)-2]=='' ){
					if ( count($explode_field_value) <= 9 ) {
						if (filter_var(substr($field_value,0,strlen($field_value)-2),FILTER_VALIDATE_IP,FILTER_FLAG_IPV6) or filter_var(substr($field_value,0,strlen($field_value)-2).'::0',FILTER_VALIDATE_IP,FILTER_FLAG_IPV6) or filter_var(substr($field_value,0,strlen($field_value)-2).':0',FILTER_VALIDATE_IP,FILTER_FLAG_IPV6) ) {
							$v6_prefix_ok = 1;
						}
					}
				}
				if($v6_prefix_ok <> 1) {
					$errmsg = $validator['errmsg'];
					if(isset($this->wordbook[$errmsg])) {
						$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
					} else {
						$this->errorMessage .= $errmsg."<br />\r\n";
					}
				}
				break;

			case 'ISIPV4':
				if(function_exists('filter_var')) {
					if(!filter_var($field_value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$errmsg = $validator['errmsg'];
						if(isset($this->wordbook[$errmsg])) {
							$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
						} else {
							$this->errorMessage .= $errmsg."<br />\r\n";
						}
					}
				} else $this->errorMessage .= "function filter_var missing <br />\r\n";
				break;

			case 'ISIPV6':
				if(function_exists('filter_var')) {
					if(!filter_var($field_value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
						$errmsg = $validator['errmsg'];
						if(isset($this->wordbook[$errmsg])) {
							$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
						} else {
							$this->errorMessage .= $errmsg."<br />\r\n";
						}
					}
				} else $this->errorMessage .= "function filter_var missing <br />\r\n";
				break;

			case 'ISIP':
				if($validator['allowempty'] != 'y') $validator['allowempty'] = 'n';
				if($validator['allowempty'] == 'y' && $field_value == '') {
					//* Do nothing
				} else {
					//* Check if its a IPv4 or IPv6 address
					if(isset($validator['separator']) && $validator['separator'] != '') {
						//* When the field may contain several IP addresses, split them by the char defined as separator
						$field_value_array = explode($validator['separator'], $field_value);
					} else {
						$field_value_array[] = $field_value;
					}
					foreach($field_value_array as $field_value) {
						$field_value = trim($field_value);
						if(function_exists('filter_var')) {
							if(!filter_var($field_value, FILTER_VALIDATE_IP)) {
								$errmsg = $validator['errmsg'];
								if(isset($this->wordbook[$errmsg])) {
									$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
								} else {
									$this->errorMessage .= $errmsg."<br />\r\n";
								}
							}
						} else $this->errorMessage .= "function filter_var missing <br />\r\n";
					}
				}
				break;
			
			case 'ISDATETIME':
				/* Checks a datetime value against the date format of the current language */
				if($validator['allowempty'] != 'y') $validator['allowempty'] = 'n';
				if($validator['allowempty'] == 'y' && $field_value == '') {
					//* Do nothing
				} else {
					$parsed_date = date_parse_from_format($this->datetimeformat,$field_value);
					if($parsed_date['error_count'] > 0 || ($parsed_date['year'] == 1899 && $parsed_date['month'] == 12 && $parsed_date['day'] == 31)) {
						$errmsg = $validator['errmsg'];
						if(isset($this->wordbook[$errmsg])) {
							$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
						} else {
							$this->errorMessage .= $errmsg."<br />\r\n";
						}
					}
				}
				break;
			
			case 'RANGE':
				//* Checks if the value is within the given range or above / below a value
				//* Range examples: < 10 = ":10", between 2 and 10 = "2:10", above 5 = "5:".
				$range_parts = explode(':', trim($validator['range']));
				$ok = true;
				if($range_parts[0] != '' && $field_value < $range_parts[0]) {
					$ok = false;
				}
				if($range_parts[1] != '' && $field_value > $range_parts[1]) {
					$ok = false;
				}
				if($ok != true) {
					$errmsg = $validator['errmsg'];
					if(isset($this->wordbook[$errmsg])) {
						$this->errorMessage .= $this->wordbook[$errmsg]."<br />\r\n";
					} else {
						$this->errorMessage .= $errmsg."<br />\r\n";
					}
				}
				unset($range_parts);
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
	 * Create SQL statement
	 *
	 * @param record = Datensatz als Array
	 * @param action = INSERT oder UPDATE
	 * @param primary_id
	 * @return record
	 */
	 /* TODO: check for double quoting */
	protected function _getSQL($record, $tab, $action = 'INSERT', $primary_id = 0, $sql_ext_where = '', $api = false) {

		global $app;

		$this->action = $action;
		$this->primary_id = $primary_id;

		$sql_insert_key = '';
		$sql_insert_val = '';
		$sql_update = '';

		$record = $this->encode($record, $tab, true);
		
		if(($this->primary_id_override > 0)) {
			$sql_insert_key .= '`'.$this->formDef["db_table_idx"].'`, ';
			$sql_insert_val .= $this->primary_id_override.", ";
			$record['_primary_id'] = $this->primary_id_override;
		}

		if($api == true) $fields = &$this->formDef['fields'];
		else $fields = &$this->formDef['tabs'][$tab]['fields'];

		// go trough all fields of the tab
		if(is_array($record)) {
			foreach($fields as $key => $field) {
				// Wenn es kein leeres Passwortfeld ist
				if (!($field['formtype'] == 'PASSWORD' and $record[$key] == '')) {
					// Erzeuge Insert oder Update Quelltext
					if($action == "INSERT") {
						if($field['formtype'] == 'PASSWORD') {
							$sql_insert_key .= "`$key`, ";
							if ((isset($field['encryption']) && $field['encryption'] == 'CLEARTEXT') || (isset($record['_ispconfig_pw_crypted']) && $record['_ispconfig_pw_crypted'] == 1)) {
								$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
							} elseif(isset($field['encryption']) && $field['encryption'] == 'CRYPT') {
								$record[$key] = $app->auth->crypt_password(stripslashes($record[$key]));
								$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
							} elseif(isset($field['encryption']) && $field['encryption'] == 'CRYPTMAIL') {
								// The password for the mail system needs to be converted to latin1 before it is hashed.
								$record[$key] = $app->auth->crypt_password(stripslashes($record[$key]),'ISO-8859-1');
								$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
							} elseif (isset($field['encryption']) && $field['encryption'] == 'MYSQL') {
								$tmp = $app->db->queryOneRecord("SELECT PASSWORD(?) as `crypted`", stripslashes($record[$key]));
								$record[$key] = $tmp['crypted'];
								$sql_insert_val .= "'".$app->db->quote($record[$key])."', ";
							} else {
								$record[$key] = md5(stripslashes($record[$key]));
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
							$sql_insert_val .= (is_null($record[$key]) ? 'NULL' : "'".$record[$key]."'") . ", ";
						}
					} else {
						if($field['formtype'] == 'PASSWORD') {
							if ((isset($field['encryption']) && $field['encryption'] == 'CLEARTEXT') || (isset($record['_ispconfig_pw_crypted']) && $record['_ispconfig_pw_crypted'] == 1)) {
								$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
							} elseif(isset($field['encryption']) && $field['encryption'] == 'CRYPT') {
								$record[$key] = $app->auth->crypt_password(stripslashes($record[$key]));
								$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
							} elseif(isset($field['encryption']) && $field['encryption'] == 'CRYPTMAIL') {
								// The password for the mail system needs to be converted to latin1 before it is hashed.
								$record[$key] = $app->auth->crypt_password(stripslashes($record[$key]),'ISO-8859-1');
								$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
							} elseif (isset($field['encryption']) && $field['encryption'] == 'MYSQL') {
								$tmp = $app->db->queryOneRecord("SELECT PASSWORD(?) as `crypted`", stripslashes($record[$key]));
								$record[$key] = $tmp['crypted'];
								$sql_update .= "`$key` = '".$app->db->quote($record[$key])."', ";
							} else {
								$record[$key] = md5(stripslashes($record[$key]));
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
							$sql_update .= "`$key` = " . (is_null($record[$key]) ? 'NULL' : "'".$record[$key]."'") . ", ";
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
		if(stristr($this->formDef['db_table'], '.')) {
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
			$sql_insert_key = substr($sql_insert_key, 0, -2);
			$sql_insert_val = substr($sql_insert_val, 0, -2);
			$sql = "INSERT INTO ".$escape.$this->formDef['db_table'].$escape." ($sql_insert_key) VALUES ($sql_insert_val)";
		} else {
			if($this->formDef['auth'] == 'yes') {
				if($primary_id != 0) {
					if($api == true && $_SESSION["s"]["user"]["client_id"] > 0 && $_SESSION["s"]["user"]["userid"] > 0 && $_SESSION["s"]["user"]["default_group"] > 0) {
						$sql_update .= '`sys_userid` = '.$this->sys_userid.', ';
						$sql_update .= '`sys_groupid` = '.$this->sys_default_group.', ';
					}

					$sql_update = substr($sql_update, 0, -2);
					$sql = "UPDATE ".$escape.$this->formDef['db_table'].$escape." SET ".$sql_update." WHERE ".$this->getAuthSQL('u')." AND ".$this->formDef['db_table_idx']." = ".$primary_id;
					if($sql_ext_where != '') $sql .= " and ".$sql_ext_where;
				} else {
					$app->error("Primary ID fehlt!");
				}
			} else {
				if($primary_id != 0) {
					$sql_update = substr($sql_update, 0, -2);
					$sql = "UPDATE ".$escape.$this->formDef['db_table'].$escape." SET ".$sql_update." WHERE ".$this->formDef['db_table_idx']." = ".$primary_id;
					if($sql_ext_where != '') $sql .= " and ".$sql_ext_where;
				} else {
					$app->error("Primary ID fehlt!");
				}
			}
			//* return a empty string if there is nothing to update
			if(trim($sql_update) == '') $sql = '';
		}

		return $sql;
	}


	/**
	 * Create SQL statement
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
				if(!$this->checkPerm($primary_id, 'i')) $this->errorMessage .= "Insert denied.<br />\r\n";
			} else {
				if(!$this->checkPerm($primary_id, 'u')) $this->errorMessage .= "Update denied.<br />\r\n";
			}
		}

		if(!is_array($this->formDef)) $app->error("Form definition not found.");
		if(!is_array($this->formDef['tabs'][$tab])) $app->error("The tab is empty or does not exist (TAB: ".$app->functions->htmlentities($tab).").");

		return $this->_getSQL($record, $tab, $action, $primary_id, $sql_ext_where, false);
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
		global $app, $conf;

		if(!is_array($this->formDef)) die("Form Definition wurde nicht geladen.");

		$active_tab = $this->getNextTab();

		// go trough the tabs
		foreach( $this->formDef["tabs"] as $key => $tab) {

			$tab['name'] = $key;
			// Translate the title of the tab
			$tab['title'] = $this->lng($tab['title']);

			if($tab['name'] == $active_tab) {

				// If module is set, then set the template path relative to the module..
				if($this->module != '') $tab["template"] = "../".$this->module."/".$tab["template"];

				// Generate the template if it does not exist yet.



				if(!is_file($tab["template"])) {
					$app->uses('tform_tpl_generator');
					$app->tform_tpl_generator->buildHTML($this->formDef, $tab['name']);
				}
				$app->tpl->setVar('readonly_tab', (isset($tab['readonly']) && $tab['readonly'] == true));
				$app->tpl->setInclude('content_tpl', $tab["template"]);
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
		$app->tpl->setVar('form_action', $this->formDef["action"]);
		$app->tpl->setVar('form_active_tab', $active_tab);

		// Set form title
		$form_hint = $this->lng($this->formDef["title"]);
		if($this->formDef["description"] != '') $form_hint .= '<div class="pageForm_description">'.$this->lng($this->formDef["description"]).'</div>';
		$app->tpl->setVar('form_hint', $form_hint);

		// Set Wordbook for this form
		foreach($this->wordbook as $key => $val) {
			if(strstr($val,'\'')) $val = stripslashes($val);
			$app->tpl->setVar($key,$val);
		}
	}

	function getDataRecord($primary_id) {
		global $app;
		$escape = '`';
		$sql = "SELECT * FROM ?? WHERE ?? = ? AND ".$this->getAuthSQL('r', $this->formDef['db_table']);
		return $app->db->queryOneRecord($sql, $this->formDef['db_table'], $this->formDef['db_table_idx'], $primary_id);
	}


	function datalogSave($action, $primary_id, $record_old, $record_new) {
		global $app, $conf;

		$app->db->datalogSave($this->formDef['db_table'], $action, $this->formDef['db_table_idx'], $primary_id, $record_old, $record_new);
		return true;
	}

	function getAuthSQL($perm, $table = '') {
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $_SESSION['s']['user']['mailuser_id'] > 0) {
			return '1';
		} else {
			if ($table != ''){
				$table = ' ' . $table . '.';
			}
			$groups = ( $_SESSION["s"]["user"]["groups"] ) ? $_SESSION["s"]["user"]["groups"] : 0;
			$sql = '(';
			$sql .= "(" . $table . "sys_userid = ".$_SESSION["s"]["user"]["userid"]." AND " . $table . "sys_perm_user like '%$perm%') OR  ";
			$sql .= "(" . $table . "sys_groupid IN (".$groups.") AND " . $table ."sys_perm_group like '%$perm%') OR ";
			$sql .= $table . "sys_perm_other like '%$perm%'";
			$sql .= ')';

			return $sql;
		}
	}

}

?>
