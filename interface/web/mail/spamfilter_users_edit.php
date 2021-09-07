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

$tform_def_file = "form/spamfilter_users.tform.php";

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
	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_spamfilter_user')) {
				$app->error($app->tform->wordbook["limit_spamfilter_user_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_spamfilter_user')) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_spamfilter_user_txt"]);
			}
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;

		// Get the spamfilter policies for the user
		$tmp_user = $app->db->queryOneRecord("SELECT policy_id FROM spamfilter_users WHERE email = ?", $this->dataRecord["email"]);
		if (isset($_POST['policy_id'])) $tmp_user['policy_id'] = intval($_POST['policy_id']);
		$sql = "SELECT id, policy_name FROM spamfilter_policy WHERE ".$app->tform->getAuthSQL('r') . " ORDER BY policy_name";
		$policies = $app->db->queryAllRecords($sql);
		$policy_select = "<option value='0'".(($tmp_user['policy_id'] == 0) ? " SELECTED>":">").$app->tform->lng("inherit_policy")."</option>";
		if(is_array($policies)) {
			foreach( $policies as $p) {
				$selected = ($p["id"] == $tmp_user["policy_id"])?'SELECTED':'';
				$policy_select .= "<option value='$p[id]' $selected>" . $app->functions->htmlentities($p['policy_name']) . "</option>\r\n";
			}
		}
		$app->tpl->setVar("policy_id", $policy_select);
		unset($policies);
		unset($policy_select);
		unset($tmp_user);

		parent::onShowEnd();
	}

	function onBeforeUpdate() {
		global $app, $conf;

		//* Check if the server has been changed
		// We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$rec = $app->db->queryOneRecord("SELECT server_id from spamfilter_users WHERE id = ?", $this->id);
			if($rec['server_id'] != $this->dataRecord["server_id"]) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Server can not be changed.');
				$this->dataRecord["server_id"] = $rec['server_id'];
			}
			unset($rec);
		}
	}

	function onSubmit() {
		global $app, $conf;

		// Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_spamfilter_user FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another spamfilter user.
			if($this->id == 0 && $client["limit_spamfilter_user"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM spamfilter_users WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_spamfilter_user"]) {
					$app->tform->errorMessage .= $app->tform->wordbook["limit_spamfilter_user_txt"]."<br>";
				}
				unset($tmp);
			}
		} // end if user is not admin

		parent::onSubmit();
	}


	function onAfterUpdate() {
		global $app, $conf;

		// If email changes fire spamfilter_wblist_update events so rspamd files are rewritten
		if(isset($this->dataRecord['email']) && $this->oldDataRecord['email'] != $this->dataRecord['email']) {
			$tmp_wblist = $app->db->queryAllRecords("SELECT wblist_id FROM spamfilter_wblist WHERE rid = ?", $this->dataRecord['id']);
			foreach ($tmp_wblist as $tmp) {
				$app->db->datalogUpdate('spamfilter_wblist', array('rid' => $this->dataRecord['id']), 'wblist_id', $tmp['wblist_id']);
			}
		}
	}

}

$app->tform_actions = new page_action;
$app->tform_actions->onLoad();

