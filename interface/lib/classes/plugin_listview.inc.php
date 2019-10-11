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

class plugin_listview extends plugin_base {

	var $module;
	var $form;
	var $tab;
	var $record_id;
	var $formdef;
	var $options;

	function onShow() {

		global $app;

		$app->uses('listform');
		$app->listform->loadListDef($this->options["listdef"]);

		//$app->listform->SQLExtWhere = "type = 'alias'";

		$listTpl = new tpl;
		$listTpl->newTemplate('templates/'.$app->listform->listDef["name"].'_list.htm');

		//die(print_r($app->tform_actions));

		// Changing some of the list values to reflect that the list is called within a tform page
		$app->listform->listDef["file"] = $app->tform->formDef["action"];
		// $app->listform->listDef["page_params"] = "&id=".$app->tform_actions->id."&next_tab=".$_SESSION["s"]["form"]["tab"];
		$app->listform->listDef["page_params"] = "&id=".$this->form->id."&next_tab=".$_SESSION["s"]["form"]["tab"];
		$listTpl->setVar('parent_id', $this->form->id);
		$listTpl->setVar('theme', $_SESSION['s']['theme'], true);

		// Generate the SQL for searching
		$sql_where = "";
		if($app->listform->listDef["auth"] != 'no') {
			if($_SESSION["s"]["user"]["typ"] != "admin") {
				$sql_where = $app->tform->getAuthSQL('r')." and";
			}
		}

		if($this->options["sqlextwhere"] != '') {
			$sql_where .= " ".$this->options["sqlextwhere"]." and";
		}

		$sql_where = $app->listform->getSearchSQL($sql_where);
		$listTpl->setVar($app->listform->searchValues);

		// Generate SQL for paging
		$limit_sql = $app->listform->getPagingSQL($sql_where);
		$listTpl->setVar("paging", $app->listform->pagingHTML);

		$sql_order_by = '';
		if(isset($this->options["sql_order_by"])) {
			$sql_order_by = $this->options["sql_order_by"];
		}

		//* Limit each page
		$limits = array('5'=>'5', '15'=>'15', '25'=>'25', '50'=>'50', '100'=>'100', '999999999' => 'all');

		//* create options and set selected, if default -> 15 is selected
		$options='';
		foreach($limits as $key => $val){
			$options .= '<option value="'.$key.'" '.(isset($_SESSION['search']['limit']) &&  $_SESSION['search']['limit'] == $key ? 'selected="selected"':'' ).(!isset($_SESSION['search']['limit']) && $key == '15' ? 'selected="selected"':'').'>'.$val.'</option>';
		}
		$listTpl->setVar('search_limit', '<select name="search_limit" style="width:50px">'.$options.'</select>');


		//Sorting
		if(!isset($_SESSION['search'][$app->listform->listDef["name"]]['order'])){
			$_SESSION['search'][$app->listform->listDef["name"]]['order'] = '';
		}

		if(!empty($_GET['orderby'])){
			$order = str_replace('tbl_col_', '', $_GET['orderby']);
			//* Check the css class submited value
			if (preg_match("/^[a-z\_]{1,}$/", $order)) {
				if($_SESSION['search'][$app->listform->listDef["name"]]['order'] == $order){
					$_SESSION['search'][$app->listform->listDef["name"]]['order'] = $order.' DESC';
				} else {
					$_SESSION['search'][$app->listform->listDef["name"]]['order'] = $order;
				}
			}
		}

		// If a manuel oder by like customers isset the sorting will be infront
		if(!empty($_SESSION['search'][$app->listform->listDef["name"]]['order'])){
			if(empty($sql_order_by)){
				$sql_order_by = "ORDER BY ".$_SESSION['search'][$app->listform->listDef["name"]]['order'];
			} else {
				$sql_order_by = str_replace("ORDER BY ", "ORDER BY ".$_SESSION['search'][$app->listform->listDef["name"]]['order'].', ', $sql_order_by);
			}
		}

		// Loading language field
		$lng_file = "lib/lang/".$app->functions->check_language($_SESSION["s"]["language"])."_".$app->listform->listDef['name']."_list.lng";
		include $lng_file;
		$listTpl->setVar($wb);
		
		$csrf_token = $app->auth->csrf_token_get($app->listform->listDef['name']);
		$_csrf_id = $csrf_token['csrf_id'];
		$_csrf_key = $csrf_token['csrf_key'];


		// Get the data
		$records = $app->db->queryAllRecords("SELECT * FROM ?? WHERE $sql_where $sql_order_by $limit_sql", $app->listform->listDef["table"]);

		$bgcolor = "#FFFFFF";
		if(is_array($records)) {
			$idx_key = $app->listform->listDef["table_idx"];
			foreach($records as $rec) {

				$rec = $app->listform->decode($rec);

				// Change of color
				$bgcolor = ($bgcolor == "#FFFFFF")?"#EEEEEE":"#FFFFFF";
				$rec["bgcolor"] = $bgcolor;

				// substitute value for select fields
				foreach($app->listform->listDef["item"] as $field) {
					$key = $field["field"];
					if($field['formtype'] == "SELECT") {
						if(strtolower($rec[$key]) == 'y' or strtolower($rec[$key]) == 'n') {
							// Set a additional image variable for bolean fields
							$rec['_'.$key.'_'] = (strtolower($rec[$key]) == 'y')?'x16/tick_circle.png':'x16/cross_circle.png';
						}
						//* substitute value for select field
						@$rec[$key] = $field['value'][$rec[$key]];
					}
					// Create a lowercase version of every item
					$rec[$key.'_lowercase'] = strtolower($rec[$key]);
				}

				// The variable "id" contains always the index field
				$rec["id"] = $rec[$idx_key];
				$rec["delete_confirmation"] = $wb['delete_confirmation'];
				
				// CSRF Token
				$rec["csrf_id"] = $_csrf_id;
				$rec["csrf_key"] = $_csrf_key;

				$records_new[] = $rec;
			}
		}

		$listTpl->setLoop('records', @$records_new);

		// Setting Returnto information in the session
		$list_name = $app->listform->listDef["name"];
		// $_SESSION["s"]["list"][$list_name]["parent_id"] = $app->tform_actions->id;
		$_SESSION["s"]["list"][$list_name]["parent_id"] = $this->form->id;
		$_SESSION["s"]["list"][$list_name]["parent_name"] = $app->tform->formDef["name"];
		$_SESSION["s"]["list"][$list_name]["parent_tab"] = $_SESSION["s"]["form"]["tab"];
		$_SESSION["s"]["list"][$list_name]["parent_script"] = $app->tform->formDef["action"];
		$_SESSION["s"]["form"]["return_to"] = $list_name;
		//die(print_r($_SESSION["s"]["list"][$list_name]));

		// defaults
		$listTpl->setVar('app_title', $app->_conf['app_title']);
		if(isset($_SESSION['s']['user'])) {
			$listTpl->setVar('app_version', $app->_conf['app_version']);
			// get pending datalog changes
			$datalog = $app->db->datalogStatus();
			$listTpl->setVar('datalog_changes_txt', $app->lng('datalog_changes_txt'));
			$listTpl->setVar('datalog_changes_end_txt', $app->lng('datalog_changes_end_txt'));
			$listTpl->setVar('datalog_changes_count', $datalog['count']);
			$listTpl->setLoop('datalog_changes', $datalog['entries']);
		} else {
			$listTpl->setVar('app_version', '');
		}
		$listTpl->setVar('app_link', $app->_conf['app_link']);

		$listTpl->setVar('app_logo', $app->_conf['logo']);

		$listTpl->setVar('phpsessid', session_id());

		$listTpl->setVar('theme', $_SESSION['s']['theme'], true);
		$listTpl->setVar('html_content_encoding', $app->_conf['html_content_encoding']);

		$listTpl->setVar('delete_confirmation', $app->lng('delete_confirmation'));
		//print_r($_SESSION);
		if(isset($_SESSION['s']['module']['name'])) {
			$listTpl->setVar('app_module', $_SESSION['s']['module']['name'], true);
		}
		if(isset($_SESSION['s']['user']) && $_SESSION['s']['user']['typ'] == 'admin') {
			$listTpl->setVar('is_admin', 1);
		}
		if(isset($_SESSION['s']['user']) && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$listTpl->setVar('is_reseller', 1);
		}
		/* Show username */
		if(isset($_SESSION['s']['user'])) {
			$listTpl->setVar('cpuser', $_SESSION['s']['user']['username'], true);
			$listTpl->setVar('logout_txt', $app->lng('logout_txt'));
			/* Show search field only for normal users, not mail users */
			if(stristr($_SESSION['s']['user']['username'], '@')){
				$listTpl->setVar('usertype', 'mailuser');
			} else {
				$listTpl->setVar('usertype', 'normaluser');
			}
		}

		/* Global Search */
		$listTpl->setVar('globalsearch_resultslimit_of_txt', $app->lng('globalsearch_resultslimit_of_txt'));
		$listTpl->setVar('globalsearch_resultslimit_results_txt', $app->lng('globalsearch_resultslimit_results_txt'));
		$listTpl->setVar('globalsearch_noresults_text_txt', $app->lng('globalsearch_noresults_text_txt'));
		$listTpl->setVar('globalsearch_noresults_limit_txt', $app->lng('globalsearch_noresults_limit_txt'));
		$listTpl->setVar('globalsearch_searchfield_watermark_txt', $app->lng('globalsearch_searchfield_watermark_txt'));
		
		return $listTpl->grab();

	}

}

?>
