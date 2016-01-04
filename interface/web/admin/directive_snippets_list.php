<?php

/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/directive_snippets.list.php";

/******************************************
* End Form configuration
******************************************/

//* Check permissions for module
$app->auth->check_module_permissions('admin');

$app->uses('listform_actions');

class list_action extends listform_actions {

	public function prepareDataRow($rec)
	{
		global $app;

		$rec = $app->listform->decode($rec);

		//* Alternating datarow colors
		$this->DataRowColor = ($this->DataRowColor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
		$rec['bgcolor'] = $this->DataRowColor;
		
		$rec['is_master'] = $rec['master_directive_snippets_id'];

		//* substitute value for select fields
		if(is_array($app->listform->listDef['item']) && count($app->listform->listDef['item']) > 0) {
			foreach($app->listform->listDef['item'] as $field) {
				$key = $field['field'];
				if(isset($field['formtype']) && $field['formtype'] == 'SELECT') {
					if(strtolower($rec[$key]) == 'y' or strtolower($rec[$key]) == 'n') {
						// Set a additional image variable for bolean fields
						$rec['_'.$key.'_'] = (strtolower($rec[$key]) == 'y')?'x16/tick_circle.png':'x16/cross_circle.png';
					}
					//* substitute value for select field
					$rec[$key] = @$field['value'][$rec[$key]];
				}
			}
		}

		//* The variable "id" contains always the index variable
		$rec['id'] = $rec[$this->idx_key];
		return $rec;
	}
	
}
$list = new list_action;
$list->SQLOrderBy = 'ORDER BY directive_snippets.name';
$list->onLoad();

//$app->listform_actions->SQLExtWhere = 'master_directive_snippets_id = 0';
/*
$app->listform_actions->SQLOrderBy = 'ORDER BY directive_snippets.name';
$app->listform_actions->onLoad();
*/
?>
