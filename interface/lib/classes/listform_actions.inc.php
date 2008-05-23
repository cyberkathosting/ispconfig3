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
* Action framework for the listform library.
*
* @author Till Brehm <t.brehm@scrigo.org>
* @copyright Copyright &copy; 2005, Till Brehm
*/

class listform_actions {
	
	private $id;
	private $idx_key;
	private $DataRowColor;
	public  $SQLExtWhere = '';
	public  $SQLOrderBy = '';
	
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
		$app->tpl->setInclude('content_tpl','templates/'.$app->listform->listDef["name"].'_list.htm');

		// Getting Datasets from DB
		$records = $app->db->queryAllRecords($this->getQueryString());

		$this->DataRowColor = "#FFFFFF";
		$records_new = '';
		if(is_array($records)) {
			$this->idx_key = $app->listform->listDef["table_idx"]; 
			foreach($records as $rec) {
				$records_new[] = $this->prepareDataRow($rec);
			}
		}

		$app->tpl->setLoop('records',$records_new);

		$this->onShow();
		
		
	}
	
	private function prepareDataRow($rec)
    {
		global $app;
		
		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;
		
		//* substitute value for select fields
		foreach($app->listform->listDef['item'] as $field) {
			$key = $field['field'];
			if(isset($field['formtype']) && $field['formtype'] == 'SELECT') {
				$rec[$key] = @$field['value'][$rec[$key]];
			}
		}
		
		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}
	
	private function getQueryString() {
		global $app;
		$sql_where = '';

		//* Generate the search sql
		if($app->listform->listDef['auth'] != 'no') {
			if($_SESSION['s']['user']['typ'] == "admin") {
				$sql_where = '';
			} else {
				$sql_where = $app->tform->getAuthSQL('r').' and';
			}
		}		
		if($this->SQLExtWhere != '') {
			$sql_where .= ' '.$this->SQLExtWhere.' and';
		}

		$sql_where = $app->listform->getSearchSQL($sql_where);
		$app->tpl->setVar($app->listform->searchValues);
		
		$order_by_sql = $this->SQLOrderBy;

		//* Generate SQL for paging
		$limit_sql = $app->listform->getPagingSQL($sql_where);
		$app->tpl->setVar('paging',$app->listform->pagingHTML);

		return 'SELECT * FROM '.$app->listform->listDef['table']." WHERE $sql_where $order_by_sql $limit_sql";
	}
	
	
	private function onShow()
    {
		global $app;
		
		//* Set global Language File
		$lng_file = ISPC_LIB_PATH.'/lang/'.$_SESSION['s']['language'].'.lng';
		include($lng_file);
		$app->tpl->setVar($wb);
		
		//* Set local Language File
		$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_'.$app->listform->listDef['name'].'_list.lng';
		include($lng_file);
		$app->tpl->setVar($wb);
		$app->tpl->setVar('form_action', $app->listform->listDef['file']);
		
		//* Parse the templates and send output to the browser
		$this->onShowEnd();
	}
	
	private function onShowEnd()
    {
		global $app;
		$app->tpl_defaults();
		$app->tpl->pparse();
	}
}

?>