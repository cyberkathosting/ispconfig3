<?php
/*
Copyright (c) 2014 Till Brehm, ISPConfig UG
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

$tform_def_file = "form/message_template.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('client');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {
	
	function onSubmit() {
		global $app, $conf;
		
		// Check for duplicates
		if($this->dataRecord['template_type'] == 'welcome') {
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$sql = "SELECT count(client_message_template_id) as number FROM client_message_template WHERE template_type = 'welcome' AND sys_groupid = ?";
			if($this->id > 0) {
				$sql .= " AND client_message_template_id != ?";
			}
			$tmp = $app->db->queryOneRecord($sql, $client_group_id, $this->id);
			if($tmp['number'] > 0) $app->tform->errorMessage .= $app->tform->lng('duplicate_welcome_error');
		}
		
		parent::onSubmit();
	}
	
	function onShowEnd() {
		global $app, $conf;
	
		//message variables
		$message_variables = '';
		$sql = "SHOW COLUMNS FROM client WHERE Field NOT IN ('client_id', 'sys_userid', 'sys_groupid', 'sys_perm_user', 'sys_perm_group', 'sys_perm_other', 'parent_client_id', 'id_rsa', 'ssh_rsa', 'created_at', 'default_mailserver', 'default_webserver', 'web_php_options', 'ssh_chroot', 'default_dnsserver', 'default_dbserver', 'template_master', 'template_additional', 'force_suexec', 'default_slave_dnsserver', 'usertheme', 'locked', 'canceled', 'can_use_api', 'tmp_data', 'customer_no_template', 'customer_no_start', 'customer_no_counter', 'added_date', 'added_by') AND Field NOT LIKE 'limit_%'";
		$field_names = $app->db->queryAllRecords($sql);
		if(!empty($field_names) && is_array($field_names)){
			foreach($field_names as $field_name){
				if($field_name['Field'] != ''){
					if($field_name['Field'] == 'gender'){
						$message_variables .= '<a href="javascript:void(0);" class="addPlaceholder">{salutation}</a> ';
					} else {
						$message_variables .= '<a href="javascript:void(0);" class="addPlaceholder">{'.$app->functions->htmlentities($field_name['Field']).'}</a> ';
					}
				}
			}
		}
		$app->tpl->setVar('message_variables', trim($message_variables));

		parent::onShowEnd();
	}
	
}

$page = new page_action;
$page->onLoad();

?>
