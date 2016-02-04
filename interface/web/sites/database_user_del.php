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

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/database_user.list.php";
$tform_def_file = "form/database_user.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('sites');

$app->uses("tform_actions");

class page_action extends tform_actions {
	function onBeforeDelete() {
		global $app; $conf;
		if($app->tform->checkPerm($this->id, 'd') == false) $app->error($app->lng('error_no_delete_permission'));

		$old_record = $app->tform->getDataRecord($this->id);

		/* we cannot use datalogDelete here, as we need to set server_id to 0 */
		$app->db->query("DELETE FROM `web_database_user` WHERE `database_user_id` = ?", $this->id);
		$new_rec = array();
		$old_record['server_id'] = 0;
		$app->db->datalogSave('web_database_user', 'DELETE', 'database_user_id', $this->id, $old_record, $new_rec);
	}

	function onAfterDelete() { // this has to be done on AFTER delete, because we need the db user still in the database when the server plugin processes the datalog
		global $app; $conf;

		//* Update all records that belog to this user
		$records = $app->db->queryAllRecords("SELECT database_id FROM web_database WHERE database_user_id = ?", $this->id);
		foreach($records as $rec) {
			$app->db->datalogUpdate('web_database', array('database_user_id' => null), 'database_id', $rec['database_id']);

		}
		$records = $app->db->queryAllRecords("SELECT database_id FROM web_database WHERE database_ro_user_id = ?", $this->id);
		foreach($records as $rec) {
			$app->db->datalogUpdate('web_database', array('database_ro_user_id' => null), 'database_id', $rec['database_id']);
		}
	}

}

$page = new page_action;
$page->onDelete();

?>
