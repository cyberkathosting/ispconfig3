<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
Copyright (c) 2013, Florian Schaal, info@schaal-24.de
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

$tform_def_file = "form/dns_dkim.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('dns');

// Loading classes
$app->uses('tpl,tform,tform_actions,validate_dns');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf;
		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {

			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_dns_record FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another record.
			if($client["limit_dns_record"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM dns_rr WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_dns_record"]) {
					$app->error($app->tform->wordbook["limit_dns_record_txt"]);
				}
			}
		}

		parent::onShowNew();

        $soa = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $_GET['zone']);
        $sql=$app->db->queryOneRecord("SELECT domain, dkim_public, dkim_selector, dkim FROM mail_domain WHERE domain = ? AND " . $app->tform->getAuthSQL('r'), substr_replace($soa['origin'],'',-1));
		if(isset($sql['domain']) && $sql['domain'] != '') {
			if($sql['dkim'] == 'y') {
		        $public_key=str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"),'',$sql['dkim_public']);
				$app->tpl->setVar('public_key', $public_key, true);
				$app->tpl->setVar('selector', $sql['dkim_selector'], true);
			} else {
			//TODO: show warning - use mail_domain for dkim and enabled dkim
			}
			$app->tpl->setVar('edit_disabled', 1);
		} else {
			$app->tpl->setVar('edit_disabled', 0);
		}
		$app->tpl->setVar('name', $soa['origin'], true);

	}

	function onSubmit() {
		global $app, $conf;

		// Get the parent soa record of the domain
		$soa = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $_POST["zone"]);
		// Check if Domain belongs to user
		if($soa["id"] != $_POST["zone"]) $app->tform->errorMessage .= $app->tform->wordbook["no_zone_perm"];

		// Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_dns_record FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			// Check if the user may add another record.
			if($this->id == 0 && $client["limit_dns_record"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM dns_rr WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_dns_record"]) {
					$app->error($app->tform->wordbook["limit_dns_record_txt"]);
				}
			}
		} // end if user is not admin

		// Set the server ID of the rr record to the same server ID as the parent record.
		$this->dataRecord["server_id"] = $soa["server_id"];

		// add dkim-settings to the public-key in the txt-record
		if (!empty($this->dataRecord['data'])) {
			$this->dataRecord['data']='v=DKIM1; t=s; p='.$this->dataRecord['data'];
			$this->dataRecord['name']=$this->dataRecord['selector'].'._domainkey.'.$this->dataRecord['name'];
//			$this->dataRecord['ttl']=60;
		}
		// Update the serial number  and timestamp of the RR record
		$soa = $app->db->queryOneRecord("SELECT serial FROM dns_rr WHERE id = ?", $this->id);
		$this->dataRecord["serial"] = $app->validate_dns->increase_serial($soa["serial"]);
		$this->dataRecord["stamp"] = date('Y-m-d H:i:s');

		// check for duplicate entry
		// Should NOT include data in this check?  it must be unique for zone/name (selector)/type, regardless of data
		$check=$app->db->queryOneRecord("SELECT * FROM dns_rr WHERE zone = ? AND type = ? AND data = ? AND name = ?", $this->dataRecord["zone"], $this->dataRecord["type"], $this->dataRecord["data"], $this->dataRecord['name']);
		if ($check!='') $app->tform->errorMessage .= $app->tform->wordbook["record_exists_txt"];
		if (empty($this->dataRecord['data'])) $app->tform->errorMessage .= $app->tform->wordbook["dkim_disabled_txt"];

		// validate selector and public-key
		if (empty($this->dataRecord['selector'])) $app->tform->errorMessage .= '<br/>'.$app->tform->wordbook["dkim_selector_empty_txt"].'<br/>';
		$this->dataRecord['data']=str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"),'',$this->dataRecord['data']); // if the users entered his own key
	
		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app, $conf;

		//* Set the sys_groupid of the rr record to be the same then the sys_groupid of the soa record
		$soa = $app->db->queryOneRecord("SELECT sys_groupid,serial FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $this->dataRecord["zone"]);
		$app->db->datalogUpdate('dns_rr', array("sys_groupid" => $soa['sys_groupid']), 'id', $this->id);

		//* Update the serial number of the SOA record
		$soa_id = $app->functions->intval($_POST["zone"]);
		$serial = $app->validate_dns->increase_serial($soa["serial"]);
		$app->db->datalogUpdate('dns_soa', array("serial" => $serial), 'id', $soa_id);
	}

	function onAfterUpdate() {
		global $app, $conf;

		//* Update the serial number of the SOA record
		$soa = $app->db->queryOneRecord("SELECT serial FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $this->dataRecord["zone"]);
		$soa_id = $app->functions->intval($_POST["zone"]);
		$serial = $app->validate_dns->increase_serial($soa["serial"]);
		$app->db->datalogUpdate('dns_soa', array("serial" => $serial), 'id', $soa_id);
	}

}

$page = new page_action;
$page->onLoad();

?>
