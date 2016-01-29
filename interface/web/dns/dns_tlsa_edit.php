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

$tform_def_file = "form/dns_tlsa.tform.php";

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
			$client_group_id = intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_dns_record FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// Check if the user may add another mailbox.
			if($client["limit_dns_record"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM dns_rr WHERE sys_groupid = $client_group_id");
				if($tmp["number"] >= $client["limit_dns_record"]) {
					$app->error($app->tform->wordbook["limit_dns_record_txt"]);
				}
			}
		}

		parent::onShowNew();
	}

	function onSubmit() {
		global $app, $conf;

		// Get the parent soa record of the domain
		$soa = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = '".$app->functions->intval($_POST["zone"])."' AND ".$app->tform->getAuthSQL('r'));

		// Check if Domain belongs to user
		if($soa["id"] != $_POST["zone"]) $app->tform->errorMessage .= $app->tform->wordbook["no_zone_perm"];

		// Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_dns_record FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// Check if the user may add another mailbox.
			if($this->id == 0 && $client["limit_dns_record"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM dns_rr WHERE sys_groupid = $client_group_id");
				if($tmp["number"] >= $client["limit_dns_record"]) {
					$app->error($app->tform->wordbook["limit_dns_record_txt"]);
				}
			}
		} // end if user is not admin


		// Set the server ID of the rr record to the same server ID as the parent record.
		$this->dataRecord["server_id"] = $soa["server_id"];

		// Update the serial number  and timestamp of the RR record
		$soa = $app->db->queryOneRecord("SELECT serial FROM dns_rr WHERE id = ".$this->id);
		$this->dataRecord["serial"] = $app->validate_dns->increase_serial($soa["serial"]);
		$this->dataRecord["stamp"] = date('Y-m-d H:i:s');

		parent::onSubmit();
	}

	function onInsert() {
		global $app, $conf;

		// Check if record is existing already
		$duplicate_tlsa = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE zone = ".$app->functions->intval($this->dataRecord["zone"])." AND name = '".$app->db->quote($this->dataRecord["name"])."' AND type = '".$app->db->quote($this->dataRecord["type"])."' AND data = '".$app->db->quote($this->dataRecord["data"])."' AND ".$app->tform->getAuthSQL('r'));

		if(is_array($duplicate_tlsa) && !empty($duplicate_tlsa)) $app->error($app->tform->wordbook["duplicate_tlsa_record_txt"]);

		parent::onInsert();
	}

	function onUpdate() {
		global $app, $conf;

		// Check if record is existing already
		$duplicate_tlsa = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE zone = ".$app->functions->intval($this->dataRecord["zone"])." AND name = '".$app->db->quote($this->dataRecord["name"])."' AND type = '".$app->db->quote($this->dataRecord["type"])."' AND data = '".$app->db->quote($this->dataRecord["data"])."' AND id != ".$app->functions->intval($this->dataRecord["id"])." AND ".$app->tform->getAuthSQL('r'));

		if(is_array($duplicate_tlsa) && !empty($duplicate_tlsa)) $app->error($app->tform->wordbook["duplicate_tlsa_record_txt"]);

		parent::onUpdate();
	}

	function onAfterInsert() {
		global $app, $conf;

		//* Set the sys_groupid of the rr record to be the same then the sys_groupid of the soa record
		$soa = $app->db->queryOneRecord("SELECT sys_groupid,serial FROM dns_soa WHERE id = '".$app->functions->intval($this->dataRecord["zone"])."' AND ".$app->tform->getAuthSQL('r'));
		$app->db->datalogUpdate('dns_rr', "sys_groupid = ".$soa['sys_groupid'], 'id', $this->id);

		//* Update the serial number of the SOA record
		$soa_id = $app->functions->intval($_POST["zone"]);
		$serial = $app->validate_dns->increase_serial($soa["serial"]);
		$app->db->datalogUpdate('dns_soa', "serial = $serial", 'id', $soa_id);
	}

	function onAfterUpdate() {
		global $app, $conf;

		//* Update the serial number of the SOA record
		$soa = $app->db->queryOneRecord("SELECT serial FROM dns_soa WHERE id = '".$app->functions->intval($this->dataRecord["zone"])."' AND ".$app->tform->getAuthSQL('r'));
		$soa_id = $app->functions->intval($_POST["zone"]);
		$serial = $app->validate_dns->increase_serial($soa["serial"]);
		$app->db->datalogUpdate('dns_soa', "serial = $serial", 'id', $soa_id);
	}

}

$page = new page_action;
$page->onLoad();

?>
