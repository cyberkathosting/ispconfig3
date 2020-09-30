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

$tform_def_file = "form/directive_snippets.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');

// Loading classes
$app->uses('tpl,tform,tform_actions');

class page_action extends tform_actions {

	private function getAffectedSites() {
		global $app;

		if($this->dataRecord['type'] === 'php') {
			$rlike = $this->dataRecord['id'].'|,'.$this->dataRecord['id'].'|'.$this->dataRecord['id'].',';
			$affected_snippets = $app->db->queryAllRecords('SELECT directive_snippets_id FROM directive_snippets WHERE required_php_snippets REGEXP ?', $rlike);
			if(is_array($affected_snippets) && !empty($affected_snippets)) {
				foreach($affected_snippets as $snippet) {
					$sql_in[] = $snippet['directive_snippets_id'];
				}
				$affected_sites = $app->db->queryAllRecords('SELECT domain_id FROM web_domain WHERE directive_snippets_id IN ?', $sql_in);
			}
		} elseif($this->dataRecord['type'] === 'apache' || $this->dataRecord['type'] === 'nginx') {
			$affected_sites = $app->db->queryAllRecords('SELECT domain_id FROM web_domain WHERE directive_snippets_id = ?', $this->dataRecord['id']);
		}

		return $affected_sites;
	}

	public function onBeforeUpdate() {
		global $app;

		$oldRecord = $app->tform->getDataRecord($this->id);

		if($this->dataRecord['active'] !== 'y' && $oldRecord['active'] === 'y') {
			$affected_sites = $this->getAffectedSites();
			if(!empty($affected_sites)) {
				$app->tform->errorMessage .= $app->tform->lng('error_disable_snippet_active_sites');
			}
		} elseif($this->dataRecord['customer_viewable'] !== 'y' && $oldRecord['customer_viewable'] === 'y') {
			$affected_sites = $this->getAffectedSites();
			if(!empty($affected_sites)) {
				$app->tform->errorMessage .= $app->tform->lng('error_hide_snippet_active_sites');
			}
		}
	}

	public function onAfterUpdate() {
		global $app;

		if(isset($this->dataRecord['update_sites']) && $this->dataRecord['update_sites'] === 'y' && $this->dataRecord['active'] === 'y') {
			$affected_sites = $this->getAffectedSites();

			if(is_array($affected_sites) && !empty($affected_sites)) {
				foreach($affected_sites as $site) {
					$website = $app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id = ?', $site['domain_id']);
					$app->db->datalogUpdate('web_domain', $website, 'domain_id', $site['domain_id'], true);
				}
			}
		}
	}
}

$page = new page_action;
$page->onLoad();