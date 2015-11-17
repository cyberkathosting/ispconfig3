<?php
/*
Copyright (c) 2007-2010, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

$tform_def_file = "form/client_template.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('client');
if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) die('Client-Templates are for Admins and Resellers only.');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	
	function onSubmit() {
		global $app;
		
		//* Resellers shall not be able to create another reseller or set reseller specific settings
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			$this->dataRecord['limit_client'] = 0;
			$this->dataRecord['limit_domainmodule'] = 0;
		}
		
		parent::onSubmit();
	}
	
	function onBeforeUpdate() {
		global $app;

		if(isset($this->dataRecord['template_type'])) {
			//* Check if the template_type has been changed
			$rec = $app->db->queryOneRecord("SELECT template_type from client_template WHERE template_id = ?", $this->id);
			if($rec['template_type'] != $this->dataRecord['template_type']) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The template type can not be changed.');
				$this->dataRecord['template_type'] = $rec['template_type'];
			}
			unset($rec);
		}
	}


	/*
	 This function is called automatically right after
	 the data was successful updated in the database.
	*/
	function onAfterUpdate() {
		global $app;

		$app->uses('client_templates');
		if (isset($this->dataRecord["template_type"])) {
			$template_type = $this->dataRecord["template_type"];
		} else {
			$tmp = $app->tform->getDataRecord($this->id);
			$template_type = $tmp['template_type'];
		}

		/*
		 * the template has changed. apply the new data to all clients
		 */
		if ($template_type == 'm'){
			$sql = "SELECT client_id FROM client WHERE template_master = ?";
			$clients = $app->db->queryAllRecords($sql, $this->id);
		} else {
			$sql = "SELECT client_id FROM client WHERE template_additional LIKE ? OR template_additional LIKE ? OR template_additional LIKE ? UNION SELECT client_id FROM client_template_assigned WHERE client_template_id = ?";
			$clients = $app->db->queryAllRecords($sql, '%/' . $this->id . '/%', $this->id . '/%', '%/' . $this->id, $this->id);
		}
		if (is_array($clients)){
			foreach ($clients as $client){
				$app->client_templates->apply_client_templates($client['client_id']);
			}
		}
	}

}

$page = new page_action;
$page->onLoad();
?>
