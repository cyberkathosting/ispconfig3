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

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/directive_snippets.list.php";
$tform_def_file = "form/directive_snippets.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');

$app->load("tform_actions");

class page_action extends tform_actions {
	function onBeforeDelete() {
		global $app;

		if($this->dataRecord['type'] === 'php') {
			$rlike = $this->dataRecord['directive_snippets_id'].'|,'.$this->dataRecord['directive_snippets_id'].'|'.$this->dataRecord['directive_snippets_id'].',';
			$affected_snippets = $app->db->queryAllRecords('SELECT directive_snippets_id FROM directive_snippets WHERE required_php_snippets REGEXP ?', $rlike);
			if(is_array($affected_snippets) && !empty($affected_snippets)) {
				foreach($affected_snippets as $snippet) {
					$sql_in[] = $snippet['directive_snippets_id'];
				}
				$affected_sites = $app->db->queryAllRecords('SELECT domain_id FROM web_domain WHERE directive_snippets_id IN ?', $sql_in);
			}
		} elseif($this->dataRecord['type'] === 'apache' || $this->dataRecord['type'] === 'nginx') {
			$affected_sites = $app->db->queryAllRecords('SELECT domain_id FROM web_domain WHERE directive_snippets_id = ?', $this->dataRecord['directive_snippets_id']);
		}

		if(!empty($affected_sites)) {
			$app->error($app->tform->lng('error_delete_snippet_active_sites'));
		}
	}
}

$page = new page_action();
$page->onDelete();

