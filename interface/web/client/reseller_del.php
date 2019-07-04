<?php

/*
Copyright (c) 2005 - 2009, Till Brehm, projektfarm Gmbh
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

$list_def_file = "list/reseller.list.php";
$tform_def_file = "form/reseller.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('client');

if($_SESSION["s"]["user"]["typ"] != 'admin') die('Access only for administrators.');

$app->uses('tpl,tform');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onBeforeDelete() {
		global $app, $conf;

		if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

		$client_id = $app->functions->intval($this->dataRecord['client_id']);

		$tmp = $app->db->queryOneRecord("SELECT count(client_id) as number FROM client WHERE parent_client_id = ?", $client_id);
		if($tmp["number"] > 0) {
			$app->error($app->lng('error_has_clients'));
		} else {
			$parent_client_id = $app->functions->intval($this->dataRecord['parent_client_id']);
			$parent_user = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE client_id = ?", $parent_client_id);
			$client_group = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client_id);
			
			// Delete all records (mail, web, etc....) of this reseller.
			$tables = 'cron,dns_rr,dns_soa,dns_slave,ftp_user,mail_access,mail_content_filter,mail_domain,mail_forwarding,mail_get,mail_user,mail_user_filter,shell_user,spamfilter_users,support_message,web_database,web_database_user,web_domain,web_folder,web_folder_user,domain,mail_mailinglist,spamfilter_wblist';
			$tables_array = explode(',', $tables);
			$client_group_id = $app->functions->intval($client_group['groupid']);
			if($client_group_id > 1) {
				foreach($tables_array as $table) {
					if($table != '') {
						//* find the primary ID of the table
						$table_info = $app->db->tableInfo($table);
						$index_field = '';
						foreach($table_info as $tmp) {
							if($tmp['option'] == 'primary') $index_field = $tmp['name'];
						}
						//* Delete the records
						if($index_field != '') {
							$records = $app->db->queryAllRecords("SELECT * FROM ?? WHERE sys_groupid = ? ORDER BY ?? DESC", $table, $client_group_id, $index_field);
							if(is_array($records)) {
								foreach($records as $rec) {
									$app->db->datalogDelete($table, $index_field, $rec[$index_field]);
									//* Delete traffic records that dont have a sys_groupid column
									if($table == 'web_domain') {
										$app->db->query("DELETE FROM web_traffic WHERE hostname = ?", $rec['domain']);
									}
									//* Delete mail_traffic records that dont have a sys_groupid
									if($table == 'mail_user') {
										$app->db->query("DELETE FROM mail_traffic WHERE mailuser_id = ?", $rec['mailuser_id']);
									}
								}
							}
						}

					}
				}
			}
		}

	}

	function onAfterDelete() {
		global $app, $conf;

		$client_id = $app->functions->intval($this->dataRecord['client_id']);

		if($client_id > 0) {
			// TODO: Delete all records (sub-clients, mail, web, etc....)  of this client.

			// remove the group of the client from the resellers group
			$parent_client_id = $app->functions->intval($this->dataRecord['parent_client_id']);
			$parent_user = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE client_id = ?", $parent_client_id);
			$client_group = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client_id);
			$app->auth->remove_group_from_user($parent_user['userid'], $client_group['groupid']);

			// delete the group of the client
			$app->db->query("DELETE FROM sys_group WHERE client_id = ?", $client_id);

			// delete the sys user(s) of the client
			$app->db->query("DELETE FROM sys_user WHERE client_id = ?", $client_id);
		}

	}

}

$page = new page_action;
$page->onDelete()

?>
