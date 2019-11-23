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

$tform_def_file = "form/server_config.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');
$app->auth->check_security_permissions('admin_allow_server_config');


// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShow() {
		global $app, $conf;
		
		// get the config
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		
		if($web_config['server_type'] == 'nginx'){
			unset($app->tform->formDef["tabs"]["fastcgi"]);
			unset($app->tform->formDef["tabs"]["vlogger"]);
		}
		
		parent::onShow();
	}

	function onSubmit() {
		global $app, $conf;
		
		if(isset($this->dataRecord['mailbox_size_limit']) && $this->dataRecord['mailbox_size_limit'] != 0 && $this->dataRecord['mailbox_size_limit'] < $this->dataRecord['message_size_limit']) {
			$app->tform->errorMessage .= $app->tform->lng("error_mailbox_message_size_txt").'<br>';
		}
		parent::onSubmit();
	}
	
	function onShowEdit() {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] != 'admin') die('This function needs admin priveliges');

		if($app->tform->errorMessage == '') {
			$app->uses('ini_parser,getconf');

			$section = $this->active_tab;
			$server_id = $this->id;

			$this->dataRecord = $app->getconf->get_server_config($server_id, $section);

			if($section == 'mail'){
				$server_config = $app->getconf->get_server_config($server_id, 'server');
				$rspamd_url = 'https://'.$server_config['hostname'].':8081/rspamd/';
			}
		}

		$record = $app->tform->getHTML($this->dataRecord, $this->active_tab, 'EDIT');

		$record['id'] = $this->id;
		if(isset($rspamd_url)) $record['rspamd_url'] = $rspamd_url;
		$app->tpl->setVar($record);
	}

	function onShowEnd() {
		global $app;
		
		$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ? AND ((SELECT COUNT(*) FROM server) > 1)", $this->id);
		$app->tpl->setVar('server_name', $app->functions->htmlentities($tmp['server_name']));
		unset($tmp);

		parent::onShowEnd();
	}

	function onUpdateSave($sql) {
		global $app, $conf;

		if($_SESSION["s"]["user"]["typ"] != 'admin') die('This function needs admin priveliges');
		$app->uses('ini_parser,getconf');

		if($conf['demo_mode'] != true) {
			$section = $app->tform->getCurrentTab();
			$server_id = $this->id;

			$server_config_array = $app->getconf->get_server_config($server_id);

			foreach($app->tform->formDef['tabs'][$section]['fields'] as $key => $field) {
				if ($field['formtype'] == 'CHECKBOX') {
					if($this->dataRecord[$key] == '') {
						// if a checkbox is not set, we set it to the unchecked value
						$this->dataRecord[$key] = $field['value'][0];
					}
				}
			}

			if($section === 'mail') {
				if(isset($server_config_array['mail']['rspamd_available']) && $server_config_array['mail']['rspamd_available'] === 'y') {
					$this->dataRecord['rspamd_available'] = 'y';
				} else {
					$this->dataRecord['rspamd_available'] = 'n';
				}
			}
			
			if($app->tform->errorMessage == '') {
				$server_config_array[$section] = $app->tform->encode($this->dataRecord, $section);
				$server_config_str = $app->ini_parser->get_ini_string($server_config_array);

				$app->db->datalogUpdate('server', array("config" => $server_config_str), 'server_id', $server_id);
			} else {
				$app->error('Security breach!');
			}
		}
	}

	function onAfterUpdate() {
		global $app;
		
		if(isset($this->dataRecord['content_filter'])){
			$app->uses('ini_parser');
			$old_config = $app->ini_parser->parse_ini_string(stripslashes($this->oldDataRecord['config']));
			if($this->dataRecord['content_filter'] == 'rspamd' && $old_config['mail']['content_filter'] != $this->dataRecord['content_filter']){
			
				$spamfilter_users = $app->db->queryAllRecords("SELECT * FROM spamfilter_users WHERE server_id = ?", intval($this->id));
				if(is_array($spamfilter_users) && !empty($spamfilter_users)){
					foreach($spamfilter_users as $spamfilter_user){
						$app->db->datalogUpdate('spamfilter_users', $spamfilter_user, 'id', $spamfilter_user["id"], true);
					}
				}
				
				$spamfilter_wblists = $app->db->queryAllRecords("SELECT * FROM spamfilter_wblist WHERE server_id = ?", intval($this->id));
				if(is_array($spamfilter_wblists) && !empty($spamfilter_wblists)){
					foreach($spamfilter_wblists as $spamfilter_wblist){
						$app->db->datalogUpdate('spamfilter_wblist', $spamfilter_wblist, 'wblist_id', $spamfilter_wblist["wblist_id"], true);
					}
				}
				
				$mail_users = $app->db->queryAllRecords("SELECT * FROM mail_user WHERE server_id = ?", intval($this->id));
				if(is_array($mail_users) && !empty($mail_users)){
					foreach($mail_users as $mail_user){
						if($mail_user['autoresponder'] == 'y'){
							$mail_user['autoresponder'] = 'n';
							$app->db->datalogUpdate('mail_user', $mail_user, 'mailuser_id', $mail_user["mailuser_id"], true);
							$mail_user['autoresponder'] = 'y';
							$app->db->datalogUpdate('mail_user', $mail_user, 'mailuser_id', $mail_user["mailuser_id"], true);
						} elseif($mail_user['move_junk'] == 'y') {
							$mail_user['move_junk'] = 'n';
							$app->db->datalogUpdate('mail_user', $mail_user, 'mailuser_id', $mail_user["mailuser_id"], true);
							$mail_user['move_junk'] = 'y';
							$app->db->datalogUpdate('mail_user', $mail_user, 'mailuser_id', $mail_user["mailuser_id"], true);
						} else {
							$app->db->datalogUpdate('mail_user', $mail_user, 'mailuser_id', $mail_user["mailuser_id"], true);
						}
					}
				}
				
				$mail_forwards = $app->db->queryAllRecords("SELECT * FROM mail_forwarding WHERE server_id = ?", intval($this->id));
				if(is_array($mail_forwards) && !empty($mail_forwards)){
					foreach($mail_forwards as $mail_forward){
						$app->db->datalogUpdate('mail_forwarding', $mail_forward, 'forwarding_id', $mail_forward["forwarding_id"], true);
					}
				}
			}
		}
	}
}

$app->tform_actions = new page_action;
$app->tform_actions->onLoad();


?>
