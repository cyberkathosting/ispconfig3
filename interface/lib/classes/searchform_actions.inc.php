<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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
* Action framework for the searchform library.
*
* @author Till Brehm <t.brehm@scrigo.org>
* @copyright Copyright &copy; 2005, Till Brehm
*/

class searchform_actions {
	
	var $id;
	var $idx_key;
	var $DataRowColor;
	var $SQLExtWhere = '';
	var $SQLOrderBy = '';
	
	function onLoad() {
		global $app, $conf, $list_def_file;
		
		if(!is_object($app->tpl)) $app->uses('tpl');
		if(!is_object($app->searchform)) $app->uses('searchform');
		if(!is_object($app->tform)) $app->uses('tform');
		
		// Load list definition
		$app->searchform->loadListDef($list_def_file);
		
		// Delete the search form contents, if requested
		if($_REQUEST["empty_searchfields"] == 'yes') {
			$list_name = $app->searchform->listDef["name"];
			unset($_SESSION["search"][$list_name]);
		}
		
		// Save the search for later usage
		if($_REQUEST["btn_submit_search_save"] && $_REQUEST["search_save_as"] != '') {
			$app->searchform->saveSearchSettings($_REQUEST["search_save_as"]);
		}
		
		// Set th returnto value for forms
		$_SESSION["s"]["form"]["return_to_url"] = $app->searchform->listDef["file"];
		
		if(!is_file('templates/'.$app->searchform->listDef["name"].'_search.htm')) {
			$app->uses('searchform_tpl_generator');
			$app->searchform_tpl_generator->buildHTML($app->searchform->listDef);
		}
		
		$app->tpl->newTemplate("searchpage.tpl.htm");
		$app->tpl->setInclude('content_tpl','templates/'.$app->searchform->listDef["name"].'_search.htm');

		// Getting Datasets from DB
		$records = $app->db->queryAllRecords($this->getQueryString());


		$this->DataRowColor = "#FFFFFF";
		if(is_array($records)) {
			$this->idx_key = $app->searchform->listDef["table_idx"]; 
			foreach($records as $rec) {
				$records_new[] = $this->prepareDataRow($rec);
			}
		}

		$app->tpl->setLoop('records',$records_new);
		
		//print_r($records_new);

		$this->onShow();
		
		
	}
	
	function prepareDataRow($rec) {
		global $app;
		
		$rec = $app->searchform->decode($rec);

		// Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == "#FFFFFF")?"#EEEEEE":"#FFFFFF";
		$rec["bgcolor"] = $this->DataRowColor;
		
		// substitute value for select fields
		foreach($app->searchform->listDef["item"] as $field) {
			$key = $field["field"];
			if($field['formtype'] == "SELECT") {
				if($rec[$key] == 'y' or $rec[$key] == 'n') {
					// Set a additional image variable for bolean fields
					$rec['_'.$key.'_'] = ($rec[$key] == 'y')?'list_icon_true.png':'list_icon_false.png';
				}
				// substitute value for select field
				$rec[$key] = $field['value'][$rec[$key]];
			}
		}
		
		// The variable "id" contains always the index variable
		$rec["id"] = $rec[$this->idx_key];
		
		return $rec;
	}
	
	function getQueryString() {
		global $app;
		
		// Generate the search sql
		if($app->searchform->listDef["auth"] != 'no') {
			if($_SESSION["s"]["user"]["typ"] == "admin") {
				$sql_where = "";
			} else {
				$sql_where = $app->tform->getAuthSQL('r')." and";
			}
		}
		
		if($this->SQLExtWhere != '') {
			$sql_where .= " ".$this->SQLExtWhere." and";
		}

		$sql_where = $app->searchform->getSearchSQL($sql_where);
		$app->tpl->setVar($app->searchform->searchValues);
		
		$order_by_sql = $this->SQLOrderBy;

		// Generate SQL for paging
		$limit_sql = $app->searchform->getPagingSQL($sql_where);
		$app->tpl->setVar("paging",$app->searchform->pagingHTML);

		return "SELECT * FROM ".$app->searchform->listDef["table"]." WHERE $sql_where $order_by_sql $limit_sql";
		
	}
	
	
	function onShow() {
		global $app;
		
		// Language File setzen
		$lng_file = "lib/lang/".$_SESSION["s"]["language"]."_".$app->searchform->listDef['name']."_search.lng";
		include($lng_file);
		$app->tpl->setVar($wb);
		$app->tpl->setVar("form_action",$app->searchform->listDef["file"]);
		
		// Parse the templates and send output to the browser
		$this->onShowEnd();
	}
	
	function onShowEnd() {
		global $app;

		if(count($_REQUEST) > 0) {
			$app->tpl->setVar('searchresult_visible',1);
			if($_REQUEST['searchresult_visible'] == 'no') $app->tpl->setVar('searchresult_visible',0);
			
			if($_REQUEST['searchform_visible'] == 'yes') {
				$app->tpl->setVar('searchform_visible',1);
			} else {
				$app->tpl->setVar('searchform_visible',0);
			}
		} else {
			$app->tpl->setVar('searchform_visible',1);
			if($_REQUEST['searchform_visible'] == 'no') $app->tpl->setVar('searchform_visible',0);
			
			if($_REQUEST['searchresult_visible'] == 'yes') {
				$app->tpl->setVar('searchresult_visible',1);
			} else {
				$app->tpl->setVar('searchresult_visible',0);
			}
		}
		
		// make columns visible
		$visible_columns = explode(",",$app->searchform->listDef['default_columns']);
		foreach($visible_columns as $col) {
			$app->tpl->setVar($col.'_visible',1);
		}
		
		$app->tpl_defaults();
		$app->tpl->pparse();
	}
}

?>