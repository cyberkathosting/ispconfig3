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

$tform_def_file = "form/spamfilter_policy.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('mail');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShow() {
		global $app, $conf;
		
		// get the config
		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		$content_filter = 'amavisd';
		if($mail_config['content_filter'] == 'rspamd'){
			$content_filter = 'rspamd';
			unset($app->tform->formDef["tabs"]["policy"]['fields']['banned_files_lover']);
			unset($app->tform->formDef["tabs"]["policy"]['fields']['bad_header_lover']);
			unset($app->tform->formDef["tabs"]["policy"]['fields']['bypass_virus_checks']);
			unset($app->tform->formDef["tabs"]["policy"]['fields']['bypass_banned_checks']);
			unset($app->tform->formDef["tabs"]["policy"]['fields']['bypass_header_checks']);
			
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['spam_tag_level']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['spam_tag2_level']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['spam_kill_level']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['spam_dsn_cutoff_level']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['spam_quarantine_cutoff_level']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['spam_modifies_subj']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['spam_subject_tag']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['spam_subject_tag2']);
			
			unset($app->tform->formDef["tabs"]["quarantine"]);
			unset($app->tform->formDef["tabs"]["other"]);
		} else {
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['rspamd_greylisting']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['rspamd_spam_greylisting_level']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['rspamd_spam_tag_level']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['rspamd_spam_tag_method']);
			unset($app->tform->formDef["tabs"]["taglevel"]['fields']['rspamd_spam_kill_level']);
		}
		$app->tpl->setVar("content_filter", $content_filter);
		
		parent::onShow();
	}
	
	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_spamfilter_policy')) {
				$app->error($app->tform->wordbook["limit_spamfilter_policy_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_spamfilter_policy')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_spamfilter_policy_txt"]);
			}
		}

		parent::onShowNew();
	}

	function onSubmit() {
		global $app, $conf;

		// Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_spamfilter_policy FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another mailbox.
			if($this->id == 0 && $client["limit_spamfilter_policy"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM spamfilter_policy WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_spamfilter_policy"]) {
					$app->tform->errorMessage .= $app->tform->wordbook["limit_spamfilter_policy_txt"]."<br>";
				}
				unset($tmp);
			}
		} // end if user is not admin

		parent::onSubmit();
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		if($mail_config['content_filter'] == 'rspamd'){
			$record_has_changed = false;
			if(isset($this->dataRecord['rspamd_spam_greylisting_level']) && !isset($this->dataRecord['rspamd_greylisting'])) $this->dataRecord['rspamd_greylisting'] = 'n';
			foreach($this->dataRecord as $key => $val) {
				if(isset($this->oldDataRecord[$key]) && @$this->oldDataRecord[$key] != $val) {
					// Record has changed
					$record_has_changed = true;
				}
			}
		
			if($record_has_changed){
				$spamfilter_users = $app->db->queryAllRecords("SELECT * FROM spamfilter_users WHERE policy_id = ?", intval($this->id));

				if(is_array($spamfilter_users) && !empty($spamfilter_users)){
					foreach($spamfilter_users as $spamfilter_user){
						$app->db->datalogUpdate('spamfilter_users', $spamfilter_user, 'id', $spamfilter_user["id"], true);
					}
				}
			}
		}
	}

}

$app->tform_actions = new page_action;
$app->tform_actions->onLoad();
?>