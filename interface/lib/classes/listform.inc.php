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

class listform {

	private $debug = 0;
	private $errorMessage;
	public  $listDef;
	public  $searchValues;
	public  $pagingHTML;
	private $pagingValues;
	private $searchChanged = 0;
	private $module;
	public $wordbook;

	public function loadListDef($file, $module = '')
	{
		global $app, $conf;
		if(!is_file($file)){
			die("List-Definition: $file not found.");
		}
		require_once $file;
		$this->listDef = $liste;
		$this->module = $module;

		//* Fill datasources
		if(@is_array($this->listDef['item'])) {
			foreach($this->listDef['item'] as $key => $field) {
				if(@is_array($field['datasource'])) {
					$this->listDef['item'][$key]['value'] = $this->getDatasourceData($field);
				}
			}
		}

		//* Set local Language File
		$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_'.$this->listDef['name'].'_list.lng';
		if(!file_exists($lng_file)) $lng_file = 'lib/lang/en_'.$this->listDef['name'].'_list.lng';
		include $lng_file;

		$this->wordbook = $wb;

		return true;
	}

	/**
	 * Get the key => value array of a form filed from a datasource definitiom
	 *
	 * @param field = array with field definition
	 * @param record = Dataset as array
	 * @return array key => value array for the value field of a form
	 */


	private function getDatasourceData($field)
	{
		global $app;
		$values = array();

		if($field['datasource']['type'] == 'SQL') {

			//** Preparing SQL string. We will replace some common placeholders
			$querystring = $field['datasource']['querystring'];
			$querystring = str_replace('{USERID}', $_SESSION['s']['user']['userid'], $querystring);
			$querystring = str_replace('{GROUPID}', $_SESSION['s']['user']['default_group'], $querystring);
			$querystring = str_replace('{GROUPS}', $_SESSION['s']['user']['groups'], $querystring);
			//TODO:
			//$table_idx = $this->formDef['db_table_idx'];
			//$querystring = str_replace("{RECORDID}",$record[$table_idx],$querystring);
			$app->uses('tform');
			$querystring = str_replace("{AUTHSQL}", $app->tform->getAuthSQL('r'), $querystring);
			$querystring = str_replace("{AUTHSQL-A}", $app->tform->getAuthSQL('r', 'a'), $querystring);
			$querystring = str_replace("{AUTHSQL-B}", $app->tform->getAuthSQL('r', 'b'), $querystring);
			$querystring = preg_replace_callback('@{AUTHSQL::(.+?)}@', create_function('$matches','global $app; $tmp = $app->tform->getAuthSQL("r", $matches[1]); return $tmp;'), $querystring);

			//* Getting the records
			$tmp_records = $app->db->queryAllRecords($querystring);
			if($app->db->errorMessage != '') die($app->db->errorMessage);
			if(is_array($tmp_records)) {
				$key_field = $field['datasource']['keyfield'];
				$value_field = $field['datasource']['valuefield'];
				foreach($tmp_records as $tmp_rec) {
					$tmp_id = $tmp_rec[$key_field];
					$values[$tmp_id] = $tmp_rec[$value_field];
				}
			}
		}

		if($field['datasource']['type'] == 'CUSTOM') {
			//* Calls a custom class to validate this record
			if($field['datasource']['class'] != '' and $field['datasource']['function'] != '') {
				$datasource_class = $field['datasource']['class'];
				$datasource_function = $field['datasource']['function'];
				$app->uses($datasource_class);
				$record = array();
				$values = $app->$datasource_class->$datasource_function($field, $record);
			} else {
				$this->errorMessage .= "Custom datasource class or function is empty<br />\r\n";
			}
		}
		
		if($api == false && isset($field['filters']) && is_array($field['filters'])) {
			$new_values = array();
			foreach($values as $index => $value) {
				$new_index = $app->tform->filterField($index, $index, $field['filters'], 'SHOW');
				$new_values[$new_index] = $app->tform->filterField($index, (isset($values[$index]))?$values[$index]:'', $field['filters'], 'SHOW');
			}
			$values = $new_values;
			unset($new_values);
			unset($new_index);
		}
		return $values;
	}

	public function getSearchSQL($sql_where = '')
	{
		global $app, $db;

		//* Get config variable
		$list_name = $this->listDef['name'];
		$search_prefix = $this->listDef['search_prefix'];

		if(isset($_REQUEST['Filter']) && !isset($_SESSION['search'][$list_name])) {
			//* Jump back to page 1 of the list when a new search gets started.
			$_SESSION['search'][$list_name]['page'] = 0;
		}

		//* store retrieval query
		if(@is_array($this->listDef['item'])) {
			foreach($this->listDef['item'] as $i) {
				$field = $i['field'];

				//* The search string has been changed
				if(isset($_REQUEST[$search_prefix.$field]) && isset($_SESSION['search'][$list_name][$search_prefix.$field]) && $_REQUEST[$search_prefix.$field] != $_SESSION['search'][$list_name][$search_prefix.$field]){
					$this->searchChanged = 1;

					//* Jump back to page 1 of the list when search has changed.
					$_SESSION['search'][$list_name]['page'] = 0;
				}

				//* Store field in session
				if(isset($_REQUEST[$search_prefix.$field]) && !stristr($_REQUEST[$search_prefix.$field], "'")){
					$_SESSION['search'][$list_name][$search_prefix.$field] = $_REQUEST[$search_prefix.$field];
					if(preg_match("/['\\\\]/", $_SESSION['search'][$list_name][$search_prefix.$field])) $_SESSION['search'][$list_name][$search_prefix.$field] = '';
				}

				if(isset($i['formtype']) && $i['formtype'] == 'SELECT'){
					if(is_array($i['value'])) {
						$out = '<option value=""></option>';
						foreach($i['value'] as $k => $v) {
							// TODO: this could be more elegant
							$selected = (isset($_SESSION['search'][$list_name][$search_prefix.$field])
								&& $k == $_SESSION['search'][$list_name][$search_prefix.$field]
								&& $_SESSION['search'][$list_name][$search_prefix.$field] != '')
								? ' SELECTED' : '';
							$v = $app->functions->htmlentities($v);
							$out .= "<option value='$k'$selected>$v</option>\r\n";
						}
					}
					$this->searchValues[$search_prefix.$field] = $out;
				} else {
					if(isset($_SESSION['search'][$list_name][$search_prefix.$field])){
						$this->searchValues[$search_prefix.$field] = htmlspecialchars($_SESSION['search'][$list_name][$search_prefix.$field]);
					}
				}
			}
		}
		//* Store variables in object | $this->searchValues = $_SESSION["search"][$list_name];
		if(@is_array($this->listDef['item'])) {
			foreach($this->listDef['item'] as $i) {
				$field = $i['field'];
				$table = $i['table'];

				$searchval = $_SESSION['search'][$list_name][$search_prefix.$field];
				// IDN
				if($searchval != ''){
					if(is_array($i['filters'])) {
						foreach($i['filters'] as $searchval_filter) {
							if($searchval_filter['event'] == 'SHOW') {
								switch ($searchval_filter['type']) {
								case 'IDNTOUTF8':
									$searchval = $app->functions->idn_encode($searchval);
									//echo $searchval;
									break;
								}
							}
						}
					}
				}
		
				// format user date format to MySQL date format 0000-00-00
				if($i['datatype'] == 'DATE' && $this->lng('conf_format_dateshort') != 'Y-m-d'){
					$dateformat = preg_replace("@[^Ymd]@", "", $this->lng('conf_format_dateshort'));
					$yearpos = strpos($dateformat, 'Y') + 1;
					$monthpos = strpos($dateformat, 'm') + 1;
					$daypos = strpos($dateformat, 'd') + 1;

					$full_date_trans = array ('Y' => '((?:19|20)\d\d)',
						'm' => '(0[1-9]|1[012])',
						'd' => '(0[1-9]|[12][0-9]|3[01])'
					);
					// d.m.Y  Y/m/d
					$full_date_regex = strtr(preg_replace("@[^Ymd]@", "[^0-9]", $this->lng('conf_format_dateshort')), $full_date_trans);
					//echo $full_date_regex;

					if (preg_match("@^\d+$@", $_SESSION['search'][$list_name][$search_prefix.$field])) { // we just have digits
						$searchval = $_SESSION['search'][$list_name][$search_prefix.$field];
					} elseif(preg_match("@^[^0-9]?\d+[^0-9]?$@", $_SESSION['search'][$list_name][$search_prefix.$field])){ // 10. or .10.
						$searchval = preg_replace("@[^0-9]@", "", $_SESSION['search'][$list_name][$search_prefix.$field]);
					} elseif(preg_match("@^[^0-9]?(\d{1,2})[^0-9]((?:19|20)\d\d)$@", $_SESSION['search'][$list_name][$search_prefix.$field], $matches)){ // 10.2013
						$month = $matches[1];
						$year = $matches[2];
						$searchval = $year.'-'.$month;
					} elseif(preg_match("@^((?:19|20)\d\d)[^0-9](\d{1,2})[^0-9]?$@", $_SESSION['search'][$list_name][$search_prefix.$field], $matches)){ // 2013-10
						$month = $matches[2];
						$year = $matches[1];
						$searchval = $year.'-'.$month;
					} elseif(preg_match("@^[^0-9]?(\d{1,2})[^0-9](\d{1,2})[^0-9]?$@", $_SESSION['search'][$list_name][$search_prefix.$field], $matches)){ // 04.10.
						if($monthpos < $daypos){
							$month = $matches[1];
							$day = $matches[2];
						} else {
							$month = $matches[2];
							$day = $matches[1];
						}
						$searchval = $month.'-'.$day;
					} elseif (preg_match("@^".$full_date_regex."$@", $_SESSION['search'][$list_name][$search_prefix.$field], $matches)) {
						//print_r($matches);
						$day = $matches[$daypos];
						$month = $matches[$monthpos];
						$year = $matches[$yearpos];
						$searchval = $year.'-'.$month.'-'.$day;
					}
				}
				
				if($i['datatype'] == 'BOOLEAN' && $searchval != ''){
					if (!function_exists('boolval')) {
						$searchval = (bool) $searchval;
						if($searchval === true){
							$searchval = 'TRUE';
						} else {
							$searchval = 'FALSE';
						}
					} else {
						$searchval = boolval($searchval)? 'TRUE' : 'FALSE';
					}
				}

				// if($_REQUEST[$search_prefix.$field] != '') $sql_where .= " $field ".$i["op"]." '".$i["prefix"].$_REQUEST[$search_prefix.$field].$i["suffix"]."' and";
				if(isset($searchval) && $searchval != ''){
					$sql_where .= " ".($table != ''? $table.'.' : $this->listDef['table'].'.')."$field ".$i['op']." ".($i['datatype'] == 'BOOLEAN'? "" : "'").$app->db->quote($i['prefix'].$searchval.$i['suffix']).($i['datatype'] == 'BOOLEAN'? "" : "'")." and";
				}
			}
		}
		return ( $sql_where != '' ) ? $sql_where = substr($sql_where, 0, -3) : '1';
	}

	public function getPagingValue($key) {
		if(!is_array($this->pagingValues)) return null;
		if(!array_key_exists($key, $this->pagingValues)) return null;
		return $this->pagingValues[$key];
	}

	/* TODO: maybe rewrite sql */
	public function getPagingSQL($sql_where = '1')
	{
		global $app, $conf;
		
		$old_search_limit = intval($_SESSION['search']['limit']);

		//* Add Global Limit from selectbox
		if(!empty($_POST['search_limit']) and $app->functions->intval($_POST['search_limit']) > 0){
			$_SESSION['search']['limit'] = $app->functions->intval($_POST['search_limit']);
		}

		//if(preg_match('{^[0-9]$}',$_SESSION['search']['limit'])){
		// $_SESSION['search']['limit'] = 15;
		//}
		if(intval($_SESSION['search']['limit']) < 1) $_SESSION['search']['limit'] = 15;

		//* Get Config variables
		$list_name          = $this->listDef['name'];
		$search_prefix      = $this->listDef['search_prefix'];
		$records_per_page   = (empty($_SESSION['search']['limit']) ? $app->functions->intval($this->listDef['records_per_page']) : $app->functions->intval($_SESSION['search']['limit'])) ;
		$table              = $this->listDef['table'];

		//* set PAGE to zero, if in session not set
		if(!isset($_SESSION['search'][$list_name]['page']) || $_SESSION['search'][$list_name]['page'] == ''){
			$_SESSION['search'][$list_name]['page'] = 0;
		}

		//* set PAGE to worth request variable "PAGE" - ? setze page auf wert der request variablen "page"
		if(isset($_REQUEST["page"])) $_SESSION["search"][$list_name]["page"] = $app->functions->intval($_REQUEST["page"]);
		
		//* Set search to changed when search limit has been changed.
		if(intval($_SESSION['search']['limit']) != $old_search_limit) $this->searchChanged = 1;

		//* PAGE to 0 set, if look for themselves ?  page auf 0 setzen, wenn suche sich ge�ndert hat.
		if($this->searchChanged == 1) $_SESSION['search'][$list_name]['page'] = 0;

		$sql_von = $app->functions->intval($_SESSION['search'][$list_name]['page'] * $records_per_page);
		$record_count = $app->db->queryOneRecord("SELECT count(*) AS anzahl FROM ??".($app->listform->listDef['additional_tables'] != ''? ','.$app->listform->listDef['additional_tables'] : '')." WHERE $sql_where", $table);
		$pages = $app->functions->intval(($record_count['anzahl'] - 1) / $records_per_page);


		$vars['list_file']      = $_SESSION['s']['module']['name'].'/'.$this->listDef['file'];
		$vars['page']           = $_SESSION['search'][$list_name]['page'];
		$vars['last_page']      = $_SESSION['search'][$list_name]['page'] - 1;
		$vars['next_page']      = $_SESSION['search'][$list_name]['page'] + 1;
		$vars['pages']          = $pages;
		$vars['max_pages']      = $pages + 1;
		$vars['records_gesamt'] = $record_count['anzahl'];
		$vars['page_params']    = (isset($this->listDef['page_params'])) ? $this->listDef['page_params'] : '';
		$vars['offset']   = $sql_von;
		$vars['records_per_page'] = $records_per_page;
		//$vars['module'] = $_SESSION['s']['module']['name'];

		if($_SESSION['search'][$list_name]['page'] > 0) $vars['show_page_back'] = 1;
		if($_SESSION['search'][$list_name]['page'] <= $vars['pages'] - 1) $vars['show_page_next'] = 1;

		$this->pagingValues = $vars;
		$this->pagingHTML = $this->getPagingHTML($vars);

		//* Return limit sql
		return "LIMIT $sql_von, $records_per_page";
	}

	public function getPagingHTML($vars)
	{
		global $app;

		// we want to show at max 17 page numbers (8 left, current, 8 right)
		$show_pages_count = 17;

		$show_pages = array(0); // first page
		if($vars['pages'] > 0) $show_pages[] = $vars['pages']; // last page
		for($p = $vars['page'] - 2; $p <= $vars['page'] + 2; $p++) { // surrounding pages
			if($p > 0 && $p < $vars['pages']) $show_pages[] = $p;
		}

		$l_start = $vars['page'] - 13;
		$l_start -= ($l_start % 10) + 1;
		$h_end = $vars['page'] + 23;
		$h_end -= ($h_end % 10) + 1;
		for($p = $l_start; $p <= $h_end; $p += 10) { // surrounding pages
			if($p > 0 && $p < $vars['pages'] && !in_array($p, $show_pages, true) && count($show_pages) < $show_pages_count) $show_pages[] = $p;
		}

		$l_start = $vars['page'] - 503;
		$l_start -= ($l_start % 100) + 1;
		$h_end = $vars['page'] + 603;
		$h_end -= ($h_end % 100) + 1;
		for($p = $l_start; $p <= $h_end; $p += 100) { // surrounding pages
			if($p > 0 && $p < $vars['pages'] && !in_array($p, $show_pages, true) && count($show_pages) < $show_pages_count) $show_pages[] = $p;
		}

		$l_start = $vars['page'] - 203;
		$l_start -= ($l_start % 25) + 1;
		$h_end = $vars['page'] + 228;
		$h_end -= ($h_end % 25) + 1;
		for($p = $l_start; $p <= $h_end; $p += 25) { // surrounding pages
			if($p > 0 && $p < $vars['pages'] && abs($p - $vars['page']) > 30 && !in_array($p, $show_pages, true) && count($show_pages) < $show_pages_count) $show_pages[] = $p;
		}

		sort($show_pages);
		$show_pages = array_unique($show_pages);
		
		$content = '<nav>
		<ul class="pagination">';
		
		//* Show Back
		if(isset($vars['show_page_back']) && $vars['show_page_back'] == 1){
			$content .= '<li><a href="#" data-load-content="'.$vars['list_file'].'?page=0'.$vars['page_params'].'" aria-label="First">
			<span aria-hidden="true">&laquo;</span></a></li>';
			$content .= '<li><a href="#" data-load-content="'.$vars['list_file'].'?page='.$vars['last_page'].$vars['page_params'].'" aria-label="Previous">
			<span aria-hidden="true">&lsaquo;</span></a></li>';
		}
		$prev = -1;
		foreach($show_pages as $p) {
			if($prev != -1 && $p > $prev + 1) $content .= '<li class="disabled"><a href="#">…</a></li>';
			$content .= '<li' . ($p == $vars['page'] ? ' class="active"' : '') . '><a href="#" data-load-content="'.$vars['list_file'].'?page='.$p.$vars['page_params'].'">'. ($p+1) .'</a></li>';
			$prev = $p;
		}
		//.$vars['next_page'].' '.$this->lng('page_of_txt').' '.$vars['max_pages'].' &nbsp; ';
		//* Show Next
		if(isset($vars['show_page_next']) && $vars['show_page_next'] == 1){
			$content .= '<li><a href="#" data-load-content="'.$vars['list_file'].'?page='.$vars['next_page'].$vars['page_params'].'" aria-label="Next">
			<span aria-hidden="true">&rsaquo;</span></a></li>';
			$content .= '<li><a href="#" data-load-content="'.$vars['list_file'].'?page='.$vars['pages'].$vars['page_params'].'" aria-label="Last">
			<span aria-hidden="true">&raquo;</span></a></li>';
		}
		$content .= '</ul></nav>';
		
		return $content;
	}

	public function getPagingHTMLasTXT($vars)
	{
		global $app;
		$content = '[<a href="'.$vars['list_file'].'?page=0'.$vars['page_params'].'">|&lt;&lt; </a>]';
		if($vars['show_page_back'] == 1){
			$content .= '[<< <a href="'.$vars['list_file'].'?page='.$vars['last_page'].$vars['page_params'].'">'.$app->lng('page_back_txt').'</a>] ';
		}
		$content .= ' '.$this->lng('page_txt').' '.$vars['next_page'].' '.$this->lng('page_of_txt').' '.$vars['max_pages'].' ';
		if($vars['show_page_next'] == 1){
			$content .= '[<a href="'.$vars['list_file'].'?page='.$vars['next_page'].$vars['page_params'].'">'.$app->lng('page_next_txt').' >></a>] ';
		}
		$content .= '[<a href="'.$vars['list_file'].'?page='.$vars['pages'].$vars['page_params'].'"> &gt;&gt;|</a>]';
		return $content;
	}

	public function getSortSQL()
	{
		global $app, $conf;
		//* Get config vars
		$sort_field = $this->listDef['sort_field'];
		$sort_direction = $this->listDef['sort_direction'];
		return ($sort_field != '' && $sort_direction != '') ? "ORDER BY $sort_field $sort_direction" : '';
	}

	public function decode($record)
	{
		global $conf, $app;
		if(is_array($record) && count($record) > 0 && is_array($this->listDef['item'])) {
			foreach($this->listDef['item'] as $field){
				$key = $field['field'];
				//* Apply filter to record value.
				if(isset($field['filters']) && is_array($field['filters'])) {
					$app->uses('tform');
					$record[$key] = $app->tform->filterField($key, (isset($record[$key]))?$record[$key]:'', $field['filters'], 'SHOW');
				}
				if(isset($record[$key])) {
					switch ($field['datatype']){
					case 'VARCHAR':
						case 'TEXT':
						$record[$key] = htmlentities(stripslashes($record[$key]), ENT_QUOTES, $conf["html_content_encoding"]);
						break;

					case 'DATETSTAMP':
						if ($record[$key] > 0) {
							// is value int?
							if (preg_match("/^[0-9]+[\.]?[0-9]*$/", $record[$key], $p)) {
								$record[$key] = date($this->lng('conf_format_dateshort'), $record[$key]);
							} else {
								$record[$key] = date($this->lng('conf_format_dateshort'), strtotime($record[$key]));
							}
						}
						break;
					case 'DATETIMETSTAMP':
						if ($record[$key] > 0) {
							// is value int?
							if (preg_match("/^[0-9]+[\.]?[0-9]*$/", $record[$key], $p)) {
								$record[$key] = date($this->lng('conf_format_datetime'), $record[$key]);
							} else {
								$record[$key] = date($this->lng('conf_format_datetime'), strtotime($record[$key]));
							}
						}
						break;
					case 'DATE':
						if ($record[$key] > 0) {
							// is value int?
							if (preg_match("/^[0-9]+[\.]?[0-9]*$/", $record[$key], $p)) {
								$record[$key] = date($this->lng('conf_format_dateshort'), $record[$key]);
							} else {
								$record[$key] = date($this->lng('conf_format_dateshort'), strtotime($record[$key]));
							}
						}
						break;

					case 'DATETIME':
						if ($record[$key] > 0) {
							// is value int?
							if (preg_match("/^[0-9]+[\.]?[0-9]*$/", $record[$key], $p)) {
								$record[$key] = date($this->lng('conf_format_datetime'), $record[$key]);
							} else {
								$record[$key] = date($this->lng('conf_format_datetime'), strtotime($record[$key]));
							}
						}
						break;

					case 'INTEGER':
						$record[$key] = $app->functions->intval($record[$key]);
						break;

					case 'DOUBLE':
						$record[$key] = htmlentities($record[$key], ENT_QUOTES, $conf["html_content_encoding"]);
						break;

					case 'CURRENCY':
						$record[$key] = $app->functions->currency_format($record[$key]);
						break;
						
					case 'BOOLEAN':
						if (!function_exists('boolval')) {
							$record[$key] = (bool) $record[$key];
						} else {
							$record[$key] = boolval($record[$key]);
						}
						break;

					default:
						$record[$key] = htmlentities(stripslashes($record[$key]), ENT_QUOTES, $conf["html_content_encoding"]);
					}
				}
			}
		}
		return $record;
	}
	
	/* TODO: check double quoting of SQL */
	public function encode($record)
	{
		global $app;
		if(is_array($record)) {
			foreach($this->listDef['item'] as $field){
				$key = $field['field'];
				switch($field['datatype']){

				case 'VARCHAR':
				case 'TEXT':
					if(!is_array($record[$key])) {
						$record[$key] = $app->db->quote($record[$key]);
					} else {
						$record[$key] = implode($this->tableDef[$key]['separator'], $record[$key]);
					}
					break;

				case 'DATETSTAMP':
					if($record[$key] > 0) {
						$record[$key] = date('Y-m-d', strtotime($record[$key]));
					}
					break;

				case 'DATETIMETSTAMP':
					if($record[$key] > 0) {
						$record[$key] = date('Y-m-d H:i:s', strtotime($record[$key]));
					}
					break;

				case 'DATE':
					if($record[$key] != '' && !is_null($record[$key]) && $record[$key] != '0000-00-00') {
						$record[$key] = $record[$key];
					}
					break;

				case 'DATETIME':
					if($record[$key] > 0) {
						$record[$key] = date('Y-m-d H:i:s', strtotime($record[$key]));
					}
					break;

				case 'INTEGER':
					$record[$key] = $app->functions->intval($record[$key]);
					break;

				case 'DOUBLE':
					$record[$key] = $app->db->quote($record[$key]);
					break;

				case 'CURRENCY':
					$record[$key] = $app->functions->currency_unformat($record[$key]);
					break;
				
				case 'BOOLEAN':
					if (!function_exists('boolval')) {
						$record[$key] = (bool) $record[$key];
					} else {
						$record[$key] = boolval($record[$key]);
					}
					break;
				}
			}
		}
		return $record;
	}

	function lng($msg) {
		global $app;

		if(isset($this->wordbook[$msg])) {
			return $this->wordbook[$msg];
		} else {
			return $app->lng($msg);
		}
	}

	function escapeArrayValues($search_values) {
		global $app;
		return $app->functions->htmlentities($search_values);
	}

}

?>
