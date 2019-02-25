<?php

/*
Copyright (c) 2017, Florian Schaal, schaal @it UG
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

class plugin_system_config_dns_ca_list extends plugin_base {

	var $module;
	var $form;
	var $tab;
	var $record_id;
	var $formdef;
	var $options;

	function onShow() {
		global $app;

		$listTpl = new tpl;
		$listTpl->newTemplate('templates/system_config_dns_ca_list.htm');

		//* Loading language file
		$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_system_config.lng';
		include $lng_file;
		$listTpl->setVar($wb);
		if($_SESSION['s']['user']['typ'] == 'admin') {
			if(isset($_GET['action'])) { 
				$ca_id = $app->functions->intval($_GET['id']);
				if($_GET['action'] == 'delete' && $ca_id > 0) {
					$app->db->query("DELETE FROM dns_ssl_ca WHERE id = ?",  $ca_id);
				}
			}
		}

		if(isset($_GET['action']) && $_GET['action'] == 'edit' && $_GET['id'] > 0) $listTpl->setVar('edit_record', 1);

		// Getting Datasets from DB
		$ca_records = $app->db->queryAllRecords("SELECT * FROM dns_ssl_ca ORDER BY ca_name ASC");
		$records=array();
		if(is_array($ca_records) && count($ca_records) > 0) {
			foreach($ca_records as $ca) {
				$rec['ca_id'] = $ca['id'];
				$rec['name'] = $ca['ca_name'];
				$rec['active'] = $ca['active'];
				$records[] = $rec;
				unset($rec);
			}
			$listTpl->setLoop('ca_records', @$records);
		} 
		$listTpl->setVar('parent_id', $this->form->id);

		return $listTpl->grab();
	}

}

?>
