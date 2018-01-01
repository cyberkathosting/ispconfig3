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


/******************************************
* Begin Form configuration
******************************************/

$tform_def_file = "form/mail_user_spamfilter.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('mailuser');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShow() {
		global $app;
		
		$this->id = $app->functions->intval($_SESSION['s']['user']['mailuser_id']);

		parent::onShow();

	}

	function onSubmit() {
		global $app;

		$this->id = $app->functions->intval($_SESSION['s']['user']['mailuser_id']);

		parent::onSubmit();

	}

	function onAfterUpdate() {
		global $app, $conf;

		$rec = $app->tform->getDataRecord($this->id);
		$email_parts = explode('@', $rec['email']);
		$email_domain = $email_parts[1];
		$domain = $app->db->queryOneRecord("SELECT sys_userid, sys_groupid, server_id FROM mail_domain WHERE domain = ?", $email_domain);

		// Spamfilter policy
		$policy_id = $app->functions->intval($this->dataRecord["policy"]);
		$tmp_user = $app->db->queryOneRecord("SELECT id FROM spamfilter_users WHERE email = ?", $rec["email"]);
		if($policy_id > 0) {
			if($tmp_user["id"] > 0) {
				// There is already a record that we will update
				$app->db->datalogUpdate('spamfilter_users', array("policy_id" => $policy_id), 'id', $tmp_user["id"]);
			} else {
				// We create a new record
				$insert_data = array(
					"sys_userid" => $domain["sys_userid"],
					"sys_groupid" => $domain["sys_groupid"],
					"sys_perm_user" => 'riud',
					"sys_perm_group" => 'riud',
					"sys_perm_other" => '',
					"server_id" => $domain["server_id"],
					"priority" => 10,
					"policy_id" => $policy_id,
					"email" => $rec["email"],
					"fullname" => $rec["email"],
					"local" => 'Y'
				);
				$app->db->datalogInsert('spamfilter_users', $insert_data, 'id');
			}
		}else {
			if($tmp_user["id"] > 0) {
				// There is already a record but the user shall have no policy, so we delete it
				$app->db->datalogDelete('spamfilter_users', 'id', $tmp_user["id"]);
			}
		} // endif spamfilter policy
	}

	function onShowEnd() {
		global $app, $conf;

		$rec = $app->tform->getDataRecord($this->id);
		$app->tpl->setVar("email", $rec['email'], true);

		// Get the spamfilter policys for the user
		$tmp_user = $app->db->queryOneRecord("SELECT policy_id FROM spamfilter_users WHERE email = ?", $rec['email']);
		$sql = "SELECT id, policy_name FROM spamfilter_policy WHERE ".$app->tform->getAuthSQL('r');
		$policys = $app->db->queryAllRecords($sql);
		$policy_select = "<option value='0'>".$app->tform->lng("no_policy")."</option>";
		if(is_array($policys)) {
			foreach( $policys as $p) {
				$selected = ($p["id"] == $tmp_user["policy_id"])?'SELECTED':'';
				$policy_select .= "<option value='$p[id]' $selected>" . $app->functions->htmlentities($p['policy_name']) . "</option>\r\n";
			}
		}
		$app->tpl->setVar("policy", $policy_select);
		unset($policys);
		unset($policy_select);
		unset($tmp_user);

		parent::onShowEnd();
	}


}

$app->tform_actions = new page_action;
$app->tform_actions->onLoad();

?>
