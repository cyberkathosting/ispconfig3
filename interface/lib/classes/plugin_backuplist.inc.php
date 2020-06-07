<?php

/*
Copyright (c) 2012, Till Brehm, ISPConfig UG
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

class plugin_backuplist extends plugin_base {

	var $module;
	var $form;
	var $tab;
	var $record_id;
	var $formdef;
	var $options;

	/**
	 * Process request to make a backup. This request is triggered manually by the user in the ISPConfig interface.
	 * @param string $message
	 * @param string $error
	 * @param string[] $wb language text
	 * @author Ramil Valitov <ramilvalitov@gmail.com>
	 * @uses backup_plugin::make_backup_callback() this method is called later in the plugin to run the backup
	 */
	protected function makeBackup(&$message, &$error, $wb)
	{
		global $app;

		$mode = $_GET['make_backup'];
		$action_type = ($mode == 'web') ? 'backup_web_files' : 'backup_database';
		$domain_id = intval($this->form->id);

		$sql = "SELECT count(action_id) as number FROM sys_remoteaction WHERE action_state = 'pending' AND action_type = ? AND action_param = ?";
		$tmp = $app->db->queryOneRecord($sql, $action_type, $domain_id);
		if ($tmp['number'] == 0) {
			$server_id = $this->form->dataRecord['server_id'];
			$message .= $wb['backup_info_txt'];
			$sql = "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) VALUES (?, UNIX_TIMESTAMP(), ?, ?, 'pending', '')";
			$app->db->query($sql, $server_id, $action_type, $domain_id);
		} else {
			$error .= $wb['backup_pending_txt'];
		}
	}

	function onShow() {

		global $app;

		$listTpl = new tpl;
		$listTpl->newTemplate('templates/web_backup_list.htm');

		//* Loading language file
		$lng_file = "lib/lang/".$app->functions->check_language($_SESSION["s"]["language"])."_web_backup_list.lng";
		include $lng_file;
		$listTpl->setVar($wb);

		$message = '';
		$error = '';

		if (isset($_GET['make_backup'])) {
			$this->makeBackup($message, $error, $wb);
		}

		if(isset($_GET['backup_action'])) {
			$backup_id = $app->functions->intval($_GET['backup_id']);

			//* check if the user is  owner of the parent domain
			$domain_backup = $app->db->queryOneRecord("SELECT parent_domain_id FROM web_backup WHERE backup_id = ?", $backup_id);

			$check_perm = 'u';
			if($_GET['backup_action'] == 'download') $check_perm = 'r'; // only check read permissions on download, not update permissions

			$get_domain = $app->db->queryOneRecord("SELECT domain_id FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL($check_perm), $domain_backup["parent_domain_id"]);
			if(empty($get_domain) || !$get_domain) {
				$app->error($app->tform->lng('no_domain_perm'));
			}

			if($_GET['backup_action'] == 'download' && $backup_id > 0) {
				$server_id = $this->form->dataRecord['server_id'];
				$backup = $app->db->queryOneRecord("SELECT * FROM web_backup WHERE backup_id = ?", $backup_id);
				if($backup['server_id'] > 0) $server_id = $backup['server_id'];
				$sql = "SELECT count(action_id) as number FROM sys_remoteaction WHERE action_state = 'pending' AND action_type = 'backup_download' AND action_param = ?";
				$tmp = $app->db->queryOneRecord($sql, $backup_id);
				if($tmp['number'] == 0) {
					$message .= $wb['download_info_txt'];
					$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
						"VALUES (?, UNIX_TIMESTAMP(), 'backup_download', ?, 'pending', '')";
					$app->db->query($sql, $server_id, $backup_id);
				} else {
					$error .= $wb['download_pending_txt'];
				}
			}
			if($_GET['backup_action'] == 'restore' && $backup_id > 0) {
				$server_id = $this->form->dataRecord['server_id'];
				$backup = $app->db->queryOneRecord("SELECT * FROM web_backup WHERE backup_id = ?", $backup_id);
				if($backup['server_id'] > 0) $server_id = $backup['server_id'];
				$sql = "SELECT count(action_id) as number FROM sys_remoteaction WHERE action_state = 'pending' AND action_type = 'backup_restore' AND action_param = ?";
				$tmp = $app->db->queryOneRecord($sql, $backup_id);
				if($tmp['number'] == 0) {
					$message .= $wb['restore_info_txt'];
					$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
						"VALUES (?, UNIX_TIMESTAMP(), 'backup_restore', ?, 'pending', '')";
					$app->db->query($sql, $server_id, $backup_id);
				} else {
					$error .= $wb['restore_pending_txt'];
				}
			}
			if($_GET['backup_action'] == 'delete' && $backup_id > 0) {
				$server_id = $this->form->dataRecord['server_id'];
				$backup = $app->db->queryOneRecord("SELECT * FROM web_backup WHERE backup_id = ?", $backup_id);
				if($backup['server_id'] > 0) $server_id = $backup['server_id'];
				$sql = "SELECT count(action_id) as number FROM sys_remoteaction WHERE action_state = 'pending' AND action_type = 'backup_delete' AND action_param = ?";
				$tmp = $app->db->queryOneRecord($sql, $backup_id);
				if($tmp['number'] == 0) {
					$message .= $wb['delete_info_txt'];
					$sql =  "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
						"VALUES (?, UNIX_TIMESTAMP(), 'backup_delete', ?, 'pending', '')";
					$app->db->query($sql, $server_id, $backup_id);
				} else {
					$error .= $wb['delete_pending_txt'];
				}
			}

		}

		//* Get the data
		$server_ids = array();
		$web = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ?", $this->form->id);
		$databases = $app->db->queryAllRecords("SELECT server_id FROM web_database WHERE parent_domain_id = ?", $this->form->id);
		if($app->functions->intval($web['server_id']) > 0) $server_ids[] = $app->functions->intval($web['server_id']);
		if(is_array($databases) && !empty($databases)){
			foreach($databases as $database){
				if($app->functions->intval($database['server_id']) > 0) $server_ids[] = $app->functions->intval($database['server_id']);
			}
		}
		$server_ids = array_unique($server_ids);
		$sql = "SELECT * FROM web_backup WHERE parent_domain_id = ? AND server_id IN ? ORDER BY tstamp DESC, backup_type ASC";
		$records = $app->db->queryAllRecords($sql, $this->form->id, $server_ids);

		$bgcolor = "#FFFFFF";
		if(is_array($records)) {
			foreach($records as $rec) {

				// Change of color
				$bgcolor = ($bgcolor == "#FFFFFF")?"#EEEEEE":"#FFFFFF";
				$rec["bgcolor"] = $bgcolor;

				$rec['date'] = date($app->lng('conf_format_datetime'), $rec['tstamp']);
				$backup_format = $rec['backup_format'];
				if (empty($backup_format)) {
					//We have a backup from old version of ISPConfig
					switch ($rec['backup_type']) {
						case 'mysql':
							$backup_format = 'gzip';
							break;
						case 'web':
							$backup_format = ($rec['backup_mode'] == 'userzip') ? 'zip' : 'tar_gzip';
							break;
						default:
							$app->log('Unsupported backup type "' . $rec['backup_type'] . '" for backup id ' . $rec['backup_id'], LOGLEVEL_ERROR);
							break;
					}
				}
				$rec['backup_type'] = $wb[('backup_type_' . $rec['backup_type'])];
				$backup_format = (!empty($backup_format)) ? $wb[('backup_format_' . $backup_format . '_txt')] : $wb["backup_format_unknown_txt"];
				if (empty($backup_format))
					$backup_format = $wb["backup_format_unknown_txt"];

				$rec['backup_format'] = $backup_format;
				$rec['backup_encrypted'] = empty($rec['backup_password']) ? $wb["no_txt"] : $wb["yes_txt"];
				$backup_manual_prefix = 'manual-';
				$rec['backup_job'] = (substr($rec['filename'], 0, strlen($backup_manual_prefix)) == $backup_manual_prefix) ? $wb["backup_job_manual_txt"] : $wb["backup_job_auto_txt"];
				
				$rec['download_available'] = true;
				if($rec['server_id'] != $web['server_id']) $rec['download_available'] = false;
				
				if($rec['filesize'] > 0){
					$rec['filesize'] = $app->functions->currency_format($rec['filesize']/(1024*1024), 'client').' MB';
				}

				$records_new[] = $rec;
			}
		}

		$listTpl->setLoop('records', @$records_new);

		$listTpl->setVar('parent_id', $this->form->id);
		$listTpl->setVar('msg', $message);
		$listTpl->setVar('error', $error);

		// Setting Returnto information in the session
		$list_name = 'backup_list';
		// $_SESSION["s"]["list"][$list_name]["parent_id"] = $app->tform_actions->id;
		$_SESSION["s"]["list"][$list_name]["parent_id"] = $this->form->id;
		$_SESSION["s"]["list"][$list_name]["parent_name"] = $app->tform->formDef["name"];
		$_SESSION["s"]["list"][$list_name]["parent_tab"] = $_SESSION["s"]["form"]["tab"];
		$_SESSION["s"]["list"][$list_name]["parent_script"] = $app->tform->formDef["action"];
		$_SESSION["s"]["form"]["return_to"] = $list_name;

		return $listTpl->grab();
	}

}

?>
