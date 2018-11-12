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


global $app;
$app->load('tform_base');
class tform extends tform_base {
	/*
		This function checks if a user has the parmissions $perm for the data record with the ID $record_id
		If record_id = 0, the the permissions are tested against the defaults of the form file.
		*/

	function checkPerm($record_id, $perm) {
		global $app;

		$record_id = $app->functions->intval($record_id);
		if($record_id > 0) {
			// Add backticks for incomplete table names.
			if(stristr($this->formDef['db_table'], '.')) {
				$escape = '';
			} else {
				$escape = '`';
			}

			$sql = "SELECT ?? FROM ?? WHERE ?? = ? AND ".$this->getAuthSQL($perm);
			if($record = $app->db->queryOneRecord($sql, $this->formDef['db_table_idx'], $this->formDef['db_table'], $this->formDef['db_table_idx'], $record_id)) {
				return true;
			} else {
				return false;
			}
		} else {
			$result = false;
			if(@$this->formDef["auth_preset"]["userid"] == $_SESSION["s"]["user"]["userid"] && stristr($perm, $this->formDef["auth_preset"]["perm_user"])) $result = true;
			if(@$this->formDef["auth_preset"]["groupid"] == $_SESSION["s"]["user"]["groupid"] && stristr($perm, $this->formDef["auth_preset"]["perm_group"])) $result = true;
			if(@stristr($this->formDef["auth_preset"]["perm_other"], $perm)) $result = true;

			// if preset == 0, everyone can insert a record of this type
			if($this->formDef["auth_preset"]["userid"] == 0 and $this->formDef["auth_preset"]["groupid"] == 0 and (@stristr($this->formDef["auth_preset"]["perm_user"], $perm) or @stristr($this->formDef["auth_preset"]["perm_group"], $perm))) $result = true;

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
		
		if(!preg_match('/^[a-zA-Z0-9_]{0,50}$/',$active_tab)) {
			die('Invalid next tab name.');
		}

		return $active_tab;
	}

	function getCurrentTab() {
		if(!preg_match('/^[a-zA-Z0-9_]{0,50}$/',$_SESSION["s"]["form"]["tab"])) {
			die('Invalid current tab name.');
		}
		return $_SESSION["s"]["form"]["tab"];
	}

	function isReadonlyTab($tab, $primary_id) {
		global $app, $conf;
		
		if(isset($this->formDef['tabs'][$tab]['readonly']) && $this->formDef['tabs'][$tab]['readonly'] == true) {

			// Add backticks for incomplete table names.
			if(stristr($this->formDef['db_table'], '.')) {
				$escape = '';
			} else {
				$escape = '`';
			}

			$sql = "SELECT sys_userid FROM ?? WHERE ?? = ?";
			$record = $app->db->queryOneRecord($sql, $this->formDef['db_table'], $this->formDef['db_table_idx'], $primary_id);

			// return true if the readonly flag of the form is set and the current loggedin user is not the owner of the record.
			if($record['sys_userid'] != $_SESSION["s"]["user"]["userid"]) {
				return true;
			} else {
				return false;	
			}
		} else {
			return false;
		}
	}


	// translation function for forms, tries the form wordbook first and if this fails, it tries the global wordbook
	function lng($msg) {
		global $app, $conf;

		if(isset($this->wordbook[$msg])) {
			return $this->wordbook[$msg];
		} else {
			return $app->lng($msg);
		}

	}

	function checkClientLimit($limit_name, $sql_where = '') {
		global $app;

		$check_passed = true;
		if($limit_name == '') $app->error('Limit name missing in function checkClientLimit.');

		// Get the limits of the client that is currently logged in
		$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
		$client = $app->db->queryOneRecord("SELECT ?? as number, parent_client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $limit_name, $client_group_id);

		// Check if the user may add another item
		if($client["number"] >= 0) {
			$sql = "SELECT count(??) as number FROM ?? WHERE ".$this->getAuthSQL('u');
			if($sql_where != '') $sql .= ' and '.$sql_where;
			$tmp = $app->db->queryOneRecord($sql, $this->formDef['db_table_idx'], $this->formDef['db_table']);
			if($tmp["number"] >= $client["number"]) $check_passed = false;
		}

		return $check_passed;
	}

	function checkResellerLimit($limit_name, $sql_where = '') {
		global $app;

		$check_passed = true;
		if($limit_name == '') $app->error('Limit name missing in function checkClientLimit.');

		// Get the limits of the client that is currently logged in
		$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
		$client = $app->db->queryOneRecord("SELECT parent_client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

		//* If the client belongs to a reseller, we will check against the reseller Limit too
		if($client['parent_client_id'] != 0) {

			//* first we need to know the groups of this reseller
			$tmp = $app->db->queryOneRecord("SELECT userid, groups FROM sys_user WHERE client_id = ?", $client['parent_client_id']);
			$reseller_groups = $tmp["groups"];
			$reseller_userid = $tmp["userid"];

			// Get the limits of the reseller of the logged in client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$reseller = $app->db->queryOneRecord("SELECT $limit_name as number FROM client WHERE client_id = ?", $client['parent_client_id']);

			// Check if the user may add another item
			if($reseller["number"] >= 0) {
				$sql = "SELECT count(??) as number FROM ?? WHERE (sys_groupid IN ? or sys_userid = ?)";
				if($sql_where != '') $sql .= ' and '.$sql_where;
				$tmp = $app->db->queryOneRecord($sql, $this->formDef['db_table_idx'], $this->formDef['db_table'], explode(',', $reseller_groups), $reseller_userid);
				if($tmp["number"] >= $reseller["number"]) $check_passed = false;
			}
		}

		return $check_passed;
	}

	//* get the difference record of two arrays
	function getDiffRecord($record_old, $record_new) {

		if(is_array($record_new) && count($record_new) > 0) {
			foreach($record_new as $key => $val) {
				if(@$record_old[$key] != $val) {
					// Record has changed
					$diffrec[$key] = array( 'old' => @$record_old[$key],
						'new' => $val);
				}
			}
		} elseif(is_array($record_old)) {
			foreach($record_old as $key => $val) {
				if($record_new[$key] != $val) {
					// Record has changed
					$diffrec[$key] = array( 'new' => $record_new[$key],
						'old' => $val);
				}
			}
		}
		return $diffrec;

	}
	
	/**
	 * Generate HTML for DATE fields.
	 *
	 * @access private
	 * @param string $form_element Name of the form element.
	 * @param string $default_value Selected value for fields.
	 * @return string HTML
	 */
	function _getDateHTML($form_element, $default_value)
	{
		$_date = ($default_value && $default_value != '0000-00-00' ? strtotime($default_value) : false);
		$_showdate = ($_date === false) ? false : true;
		
		$tmp_dt = strtr($this->dateformat,array('d' => 'dd', 'm' => 'mm', 'Y' => 'yyyy', 'y' => 'yy'));
		
		return '<input type="text" class="form-control" name="' . $form_element . '" value="' . ($_showdate ? date($this->dateformat, $_date) : '') . '"  data-input-element="date" data-date-format="' . $tmp_dt . '" />'; 
	}


	/**
	 * Generate HTML for DATETIME fields.
	 *
	 * @access private
	 * @param string $form_element Name of the form element.
	 * @param string $default_value Selected value for fields.
	 * @param bool $display_secons Include seconds selection.
	 * @return string HTML
	 */
	function _getDateTimeHTML($form_element, $default_value, $display_seconds=false)
	{
		$_datetime = ($default_value && $default_value != '0000-00-00 00:00:00' ? strtotime($default_value) : false);
		$_showdate = ($_datetime === false) ? false : true;

		$dselect = array('day', 'month', 'year', 'hour', 'minute');
		if ($display_seconds === true) {
			$dselect[] = 'second';
		}
		
		$tmp_dt = strtr($this->datetimeformat,array('d' => 'dd', 'm' => 'mm', 'Y' => 'yyyy', 'y' => 'yy', 'H' => 'hh', 'h' => 'HH', 'i' => 'ii')) . ($display_seconds ? ':ss' : '');

		$out = '';
		
		return '<input type="text" class="form-control" name="' . $form_element . '" value="' . ($_showdate ? date($this->datetimeformat . ($display_seconds ? ':s' : ''), $_datetime) : '') . '"  data-input-element="datetime" data-date-format="' . $tmp_dt . '" />'; 
/*
		foreach ($dselect as $dt_element)
		{
			$dt_options = array();
			$dt_space = 1;

			switch ($dt_element) {
			case 'day':
				for ($i = 1; $i <= 31; $i++) {
					$dt_options[] = array('name' =>  sprintf('%02d', $i),
						'value' => sprintf('%d', $i));
				}
				$selected_value = date('d', $_datetime);
				break;

			case 'month':
				for ($i = 1; $i <= 12; $i++) {
					$dt_options[] = array('name' => strftime('%b', mktime(0, 0, 0, $i, 1, 2000)),
						'value' => strftime('%m', mktime(0, 0, 0, $i, 1, 2000)));
				}
				$selected_value = date('n', $_datetime);
				break;

			case 'year':
				$start_year = strftime("%Y");
				$years = range((int)$start_year, (int)($start_year+3));

				foreach ($years as $year) {
					$dt_options[] = array('name' => $year,
						'value' => $year);
				}
				$selected_value = date('Y', $_datetime);
				$dt_space = 2;
				break;

			case 'hour':
				foreach(range(0, 23) as $hour) {
					$dt_options[] = array('name' =>  sprintf('%02d', $hour),
						'value' => sprintf('%d', $hour));
				}
				$selected_value = date('G', $_datetime);
				break;

			case 'minute':
				foreach(range(0, 59) as $minute) {
					if (($minute % 5) == 0) {
						$dt_options[] = array('name' =>  sprintf('%02d', $minute),
							'value' => sprintf('%d', $minute));
					}
				}
				$selected_value = (int)floor(date('i', $_datetime));
				break;

			case 'second':
				foreach(range(0, 59) as $second) {
					$dt_options[] = array('name' =>  sprintf('%02d', $second),
						'value' => sprintf('%d', $second));
				}
				$selected_value = (int)floor(date('s', $_datetime));
				break;
			}
	
			$out .= "<select name=\"".$form_element."[$dt_element]\" id=\"".$form_element."_$dt_element\" class=\"selectInput\" style=\"width: auto; float: none;\">";
			if (!$_showdate) {
				$out .= "<option value=\"-\" selected=\"selected\">--</option>" . PHP_EOL;
			} else {
				$out .= "<option value=\"-\">--</option>" . PHP_EOL;
			}

			foreach ($dt_options as $dt_opt) {
				if ( $_showdate && ($selected_value == $dt_opt['value']) ) {
					$out .= "<option value=\"{$dt_opt['value']}\" selected=\"selected\">{$dt_opt['name']}</option>" . PHP_EOL;
				} else {
					$out .= "<option value=\"{$dt_opt['value']}\">{$dt_opt['name']}</option>" . PHP_EOL;
				}
			}

			$out .= '</select>' . str_repeat('&nbsp;', $dt_space);
		}

		return $out;*/
	}

}

?>
