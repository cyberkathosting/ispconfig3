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

class listform_actions {

	private $id;
	public $idx_key;
	public $DataRowColor;
	public $SQLExtWhere = '';
	public $SQLOrderBy = '';
	public $SQLExtSelect = '';
	private $sortKeys;

	private function _sort($aOne, $aTwo) {
		$suffixes=array('k' => 1, 'M' => 1024, 'G' => 1048576, 'T' => 1099511627776);
		if(!is_array($aOne) || !is_array($aTwo)) return 0;

		if(!is_array($this->sortKeys)) $this->sortKeys = array($this->sortKeys);
		foreach($this->sortKeys as $sKey => $sDir) {
			if(is_numeric($sKey)) {
				$sKey = $sDir;
				$sDir = 'ASC';
			}
			$a = $aOne[$sKey];
			$b = $aTwo[$sKey];

			if(preg_match('/(\d+\.?\d*) ([kMGT])B/', $a, $match)) {
				$a = $match[1] * $suffixes[$match[2]];
			}

			if(preg_match('/(\d+\.?\d*) ([kMGT])B/', $b, $match)) {
				$b = $match[1]  * $suffixes[$match[2]];
			}

			if(is_string($a)) $a = strtolower($a);
			if(is_string($b)) $b = strtolower($b);
			if($a < $b) return $sDir == 'DESC' ? 1 : -1;
			elseif($a > $b) return $sDir == 'DESC' ? -1 : 1;
		}
		return 0;
	}

	public function onLoad()
	{
		global $app, $conf, $list_def_file;

		$app->uses('tpl,listform,tform');

		//* Clear session variable that is used when lists are embedded with the listview plugin
		$_SESSION['s']['form']['return_to'] = '';

		// Load list definition
		$app->listform->loadListDef($list_def_file);

		if(!is_file('templates/'.$app->listform->listDef["name"].'_list.htm')) {
			$app->uses('listform_tpl_generator');
			$app->listform_tpl_generator->buildHTML($app->listform->listDef);
		}

		$app->tpl->newTemplate("listpage.tpl.htm");
		$app->tpl->setInclude('content_tpl', 'templates/'.$app->listform->listDef["name"].'_list.htm');

		//* Manipulate order by for sorting / Every list has a stored value
		//* Against notice error
		if(!isset($_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order'])){
			$_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order'] = '';
		}

		$php_sort = false;

		if(!empty($_GET['orderby'])){
			$order = str_replace('tbl_col_', '', $_GET['orderby']);

			//* Check the css class submited value
			if (preg_match("/^[a-z\_]{1,}$/", $order)) {

				if(isset($app->listform->listDef['phpsort']) && is_array($app->listform->listDef['phpsort']) && in_array($order, $app->listform->listDef['phpsort'])) {
					$php_sort = true;
				} else {
					// prepend correct table
					$prepend_table = $app->listform->listDef['table'];
					if(trim($app->listform->listDef['additional_tables']) != '' && is_array($app->listform->listDef['item']) && count($app->listform->listDef['item']) > 0) {
						foreach($app->listform->listDef['item'] as $field) {
							if($field['field'] == $order && $field['table'] != ''){
								$prepend_table = $field['table'];
								break;
							}
						}
					}
					$order = $prepend_table.'.'.$order;
				}

				if($_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order'] == $order){
					$_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order'] = $order.' DESC';
				} else {
					$_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order'] = $order;
				}
				$_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order_in_php'] = $php_sort;
			}
		}

		// If a manuel oder by like customers isset the sorting will be infront
		if(!empty($_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order']) && !$_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order_in_php']){
			if(empty($this->SQLOrderBy)){
				$this->SQLOrderBy = "ORDER BY ".$_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order'];
			} else {
				$this->SQLOrderBy = str_replace("ORDER BY ", "ORDER BY ".$_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order'].', ', $this->SQLOrderBy);
			}
		}

		if($_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order_in_php']) $php_sort = true;

		// Getting Datasets from DB
		$records = $app->db->queryAllRecords($this->getQueryString($php_sort));

		$csrf_token = $app->auth->csrf_token_get($app->listform->listDef['name']);
		$_csrf_id = $csrf_token['csrf_id'];
		$_csrf_key = $csrf_token['csrf_key'];

		$this->DataRowColor = "#FFFFFF";
		$records_new = array();
		if(is_array($records)) {
			$this->idx_key = $app->listform->listDef["table_idx"];
			foreach($records as $key => $rec) {
				$records_new[$key] = $this->prepareDataRow($rec);
				$records_new[$key]['csrf_id'] = $_csrf_id;
				$records_new[$key]['csrf_key'] = $_csrf_key;
			}
		}

		if(!empty($_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order']) && $_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order_in_php']) {
			$order_by = $_SESSION['search'][$_SESSION['s']['module']['name'].$app->listform->listDef["name"].$app->listform->listDef['table']]['order'];
			$order_dir = 'ASC';
			if(substr($order_by, -5) === ' DESC') {
				$order_by = substr($order_by, 0, -5);
				$order_dir = 'DESC';
			}
			$this->sortKeys = array($order_by => $order_dir);
			uasort($records_new, array($this, '_sort'));
		}
		if($php_sort) {
			$records_new = array_slice($records_new, $app->listform->getPagingValue('offset'), $app->listform->getPagingValue('records_per_page'));
		}

		if(is_array($records_new) && count($records_new) > 0) $app->tpl->setLoop('records', $records_new);

		$this->onShow();


	}

	public function prepareDataRow($rec)
	{
		global $app;

		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;

		//* substitute value for select fields
		if(is_array($app->listform->listDef['item']) && count($app->listform->listDef['item']) > 0) {
			foreach($app->listform->listDef['item'] as $field) {
				if($rec['active'] == 'n') $rec['warn_inactive'] = 'y';
				$key = $field['field'];
				if(isset($field['formtype']) && $field['formtype'] == 'SELECT') {
					if(strtolower($rec[$key]) == 'y' or strtolower($rec[$key]) == 'n') {
						// Set a additional image variable for boolean fields
						$rec['_'.$key.'_'] = (strtolower($rec[$key]) == 'y')?'x16/tick_circle.png':'x16/cross_circle.png';
					}
					//* substitute value for select field
					$rec[$key] = $app->functions->htmlentities(@$field['value'][$rec[$key]]);
				}
			}
		}

		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}

	/* TODO: maybe rewrite SQL */
	public function getQueryString($no_limit = false) {
		global $app;
		$sql_where = '';

		//* Generate the search sql
		if($app->listform->listDef['auth'] != 'no') {
			if($_SESSION['s']['user']['typ'] == "admin") {
				$sql_where = '';
			} else {
				$sql_where = $app->tform->getAuthSQL('r', $app->listform->listDef['table']).' and';
				//$sql_where = $app->tform->getAuthSQL('r').' and';
			}
		}
		if($this->SQLExtWhere != '') {
			$sql_where .= ' '.$this->SQLExtWhere.' and';
		}

		$sql_where = $app->listform->getSearchSQL($sql_where);
		if($app->listform->listDef['join_sql']) $sql_where .= ' AND '.$app->listform->listDef['join_sql'];
		$app->tpl->setVar($app->listform->searchValues);

		$order_by_sql = $this->SQLOrderBy;

		//* Generate SQL for paging
		$limit_sql = $app->listform->getPagingSQL($sql_where);
		$app->tpl->setVar('paging', $app->listform->pagingHTML);

		$extselect = '';
		$join = '';

		if($this->SQLExtSelect != '') {
			if(substr($this->SQLExtSelect, 0, 1) != ',') $this->SQLExtSelect = ','.$this->SQLExtSelect;
			$extselect .= $this->SQLExtSelect;
		}

		$table_selects = array();
		$table_selects[] = trim($app->listform->listDef['table']).'.*';
		$app->listform->listDef['additional_tables'] = trim($app->listform->listDef['additional_tables']);
		if($app->listform->listDef['additional_tables'] != ''){
			$additional_tables = explode(',', $app->listform->listDef['additional_tables']);
			foreach($additional_tables as $additional_table){
				$table_selects[] = trim($additional_table).'.*';
			}
		}
		$select = implode(', ', $table_selects);

		$sql = 'SELECT '.$select.$extselect.' FROM '.$app->listform->listDef['table'].($app->listform->listDef['additional_tables'] != ''? ','.$app->listform->listDef['additional_tables'] : '')."$join WHERE $sql_where $order_by_sql";
		if($no_limit == false) $sql .= " $limit_sql";
		//echo $sql;
		return $sql;
	}


	public function onShow()
	{
		global $app;

		//* Set global Language File
		$lng_file = ISPC_LIB_PATH.'/lang/'.$app->functions->check_language($_SESSION['s']['language']).'.lng';
		if(!file_exists($lng_file))
			$lng_file = ISPC_LIB_PATH.'/lang/en.lng';
		include $lng_file;
		$app->tpl->setVar($wb);

		//* Limit each page
		$limits = array('5'=>'5', '15'=>'15', '25'=>'25', '50'=>'50', '100'=>'100', '999999999' => 'all');

		//* create options and set selected, if default -> 15 is selected

		$options = '';
		foreach($limits as $key => $val){
			$options .= '<option value="'.$key.'" '.(isset($_SESSION['search']['limit']) &&  $_SESSION['search']['limit'] == $key ? 'selected="selected"':'' ).(!isset($_SESSION['search']['limit']) && $key == '15' ? 'selected="selected"':'').'>'.$val.'</option>';
		}
		$app->tpl->setVar('search_limit', '<select name="search_limit" class="search_limit" style="width: 60px;">'.$options.'</select>');

		$app->tpl->setVar('toolsarea_head_txt', $app->lng('toolsarea_head_txt'));
		$app->tpl->setVar($app->listform->wordbook);
		$app->tpl->setVar('form_action', $app->listform->listDef['file']);

		if(isset($_SESSION['show_info_msg'])) {
			$app->tpl->setVar('show_info_msg', $_SESSION['show_info_msg']);
			unset($_SESSION['show_info_msg']);
		}
		if(isset($_SESSION['show_error_msg'])) {
			$app->tpl->setVar('show_error_msg', $_SESSION['show_error_msg']);
			unset($_SESSION['show_error_msg']);
		}

		//* Parse the templates and send output to the browser
		$this->onShowEnd();
	}

	public function onShowEnd()
	{
		global $app;
		$app->tpl_defaults();
		$app->tpl->pparse();
	}

}

?>
