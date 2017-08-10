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

$list_def_file = "list/web_vhost_domain.list.php";
$tform_def_file = "form/web_vhost_domain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('sites');

$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

//* Get and set the vhost domain type - store in session
$show_type = 'domain';
if(isset($_GET['type']) && $_GET['type'] == 'subdomain') {
	$show_type = 'subdomain';
} elseif(isset($_GET['type']) && $_GET['type'] == 'aliasdomain') {
	$show_type = 'aliasdomain';
} elseif(!isset($_GET['type']) && isset($_SESSION['s']['var']['vhostdomain_type']) && $_SESSION['s']['var']['vhostdomain_type'] == 'subdomain') {
	$show_type = 'subdomain';
} elseif(!isset($_GET['type']) && isset($_SESSION['s']['var']['vhostdomain_type']) && $_SESSION['s']['var']['vhostdomain_type'] == 'aliasdomain') {
	$show_type = 'aliasdomain';
}

$_SESSION['s']['var']['vhostdomain_type'] = $show_type;


class page_action extends tform_actions {

	function onBeforeDelete() {
		global $app; $conf;

		if($_SESSION['s']['var']['vhostdomain_type'] == 'domain') {
			if($app->tform->checkPerm($this->id, 'd') == false) $app->error($app->lng('error_no_delete_permission'));

			//* Delete all records that belong to this web.
			$records = $app->db->queryAllRecords("SELECT domain_id FROM web_domain WHERE parent_domain_id = ? AND type != 'vhost'", $this->id);
			foreach($records as $rec) {
				$app->db->datalogDelete('web_domain', 'domain_id', $rec['domain_id']);
			}

			//* Delete all records that belong to this web.
			$records = $app->db->queryAllRecords("SELECT ftp_user_id FROM ftp_user WHERE parent_domain_id = ?", $this->id);
			foreach($records as $rec) {
				$app->db->datalogDelete('ftp_user', 'ftp_user_id', $rec['ftp_user_id']);
			}

			//* Delete all records that belong to this web.
			$records = $app->db->queryAllRecords("SELECT shell_user_id FROM shell_user WHERE parent_domain_id = ?", $this->id);
			foreach($records as $rec) {
				$app->db->datalogDelete('shell_user', 'shell_user_id', $rec['shell_user_id']);
			}

			//* Delete all records that belong to this web.
			$records = $app->db->queryAllRecords("SELECT id FROM cron WHERE parent_domain_id = ?", $this->id);
			foreach($records as $rec) {
				$app->db->datalogDelete('cron', 'id', $rec['id']);
			}

			//* Delete all records that belong to this web
			$records = $app->db->queryAllRecords("SELECT webdav_user_id FROM webdav_user WHERE parent_domain_id = ?", $this->id);
			foreach($records as $rec) {
				$app->db->datalogDelete('webdav_user', 'webdav_user_id', $rec['webdav_user_id']);
			}

			//* Delete all records that belong to this web
			$records = $app->db->queryAllRecords("SELECT backup_id FROM web_backup WHERE parent_domain_id = ?", $this->id);
			foreach($records as $rec) {
				$app->db->datalogDelete('web_backup', 'backup_id', $rec['backup_id']);
			}

			//* Delete all records that belog to this web.
			$web_domain = $app->db->queryOneRecord("SELECT domain FROM web_domain WHERE domain_id = ?", $this->id);
			if($web_domain['domain'] != ''){
				$aps_instances = $app->db->queryAllRecords("SELECT instance_id FROM aps_instances_settings WHERE name = 'main_domain' AND value = ?", $web_domain['domain']);
				if(is_array($aps_instances) && !empty($aps_instances)){
					foreach($aps_instances as $aps_instance){
						if($aps_instance['instance_id'] > 0){
							$app->db->datalogDelete('aps_instances_settings', 'instance_id', $aps_instance['instance_id']);
							$app->db->datalogDelete('aps_instances', 'id', $aps_instance['instance_id']);
						}
					}
				}
			}
			
			//* Remove parent_domain_id from databases
			$records = $app->db->queryAllRecords("SELECT database_id FROM web_database WHERE parent_domain_id = ?", $this->id);
			foreach($records as $rec) {
				$app->db->datalogUpdate('web_database', array('parent_domain_id' => 0), 'database_id', $rec['database_id']);
			}
		}
		
		//* Delete all web folders
		$records = $app->db->queryAllRecords("SELECT web_folder_id FROM web_folder WHERE parent_domain_id = ?", $this->id);
		foreach($records as $rec) {
			//* Delete all web folder users
			$records2 = $app->db->queryAllRecords("SELECT web_folder_user_id FROM web_folder_user WHERE web_folder_id = ?", $rec['web_folder_id']);
			foreach($records2 as $rec2) {
				$app->db->datalogDelete('web_folder_user', 'web_folder_user_id', $rec2['web_folder_user_id']);
			}
			$app->db->datalogDelete('web_folder', 'web_folder_id', $rec['web_folder_id']);
		}
	}

}

$page = new page_action;
$page->onDelete();

?>
