<?php

/*
Copyright (c) 2014, Florian Schaal, info@schaal-24.de
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

$tform_def_file = "form/dns_spf.tform.php";

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
			$client = $app->db->queryOneRecord("SELECT limit_dns_record FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another mailbox.
			if($client["limit_dns_record"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM dns_rr WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_dns_record"]) {
					$app->error($app->tform->wordbook["limit_dns_record_txt"]);
				}
			}
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app;

		$id = $app->functions->intval($_GET['id']);

		// if there is no existing SPF record, assume we want a new active record
		$app->tpl->setVar('active', 'CHECKED');

		//* check for an existing spf-record
		$sql = "SELECT data, active FROM dns_rr WHERE id = ? AND " . $app->tform->getAuthSQL('r');
		$rec = $app->db->queryOneRecord($sql, $id);
		if ( isset($rec) && !empty($rec) ) {
			$this->id = 1;
			$old_data = strtolower($rec['data']);

			$app->tpl->setVar("data", $old_data, true);
			if ($rec['active'] == 'Y') $app->tpl->setVar("active", "CHECKED"); else $app->tpl->setVar("active", "UNCHECKED");

			$spf_hostname = '';
			$spf_ip = '';
			$spf_domain = '';
			$spf_mechanism = '';

			// browse through data
			$temp = explode(' ', $old_data);
			foreach ($temp as $part) {
				if ($part == 'a') $app->tpl->setVar("spf_a_active", "CHECKED");
				if ($part == 'mx') $app->tpl->setVar("spf_mx_active", "CHECKED");
	    		if (preg_match("/^ip(4|6):/", $part)) $spf_ip .= str_replace(array('ip4:','ip6:'), '', $part) . ' ';
    			if (preg_match("/^a:/", $part)) $spf_hostname .= str_replace('a:', '', $part) . ' ';
    			if (preg_match("/^\\??include/", $part)) $spf_domain .= str_replace(array('include:', '?'), '', $part) . ' ';
			}
			unset($temp);
			$spf_ip = rtrim($spf_ip);
			$spf_hostname = rtrim($spf_hostname);
			$spf_domain = rtrim($spf_domain);
			$spf_mechanism = substr($rec['data'], -4, 1);
		}

		//set html-values
		$app->tpl->setVar("spf_ip", $spf_ip, true);
		$app->tpl->setVar("spf_hostname", $spf_hostname, true);
		$app->tpl->setVar("spf_domain", $spf_domain, true);
		//create spf-mechanism-list
		$spf_mechanism_value = array( 
			'+' => 'spf_mechanism_pass_txt',
			'-' => 'spf_mechanism_fail_txt',
			'~' => 'spf_mechanism_softfail_txt',
			'?' => 'spf_mechanism_neutral_txt'
		);
		$spf_mechanism_list='';
		foreach($spf_mechanism_value as $value => $txt) {
			$selected = @($spf_mechanism == $value)?' selected':'';
			$spf_mechanism_list .= "<option value='$value'$selected>".$app->tform->wordbook[$txt]."</option>\r\n";
		}
		$app->tpl->setVar('spf_mechanism', $spf_mechanism_list);

		parent::onShowEnd();

	}

	function onSubmit() {
		global $app, $conf;

		// Get the parent soa record of the domain
		$soa = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $app->functions->intval($_POST["zone"]));

		// Check if Domain belongs to user
		if($soa["id"] != $_POST["zone"]) $app->tform->errorMessage .= $app->tform->wordbook["no_zone_perm"];

		// Check the client limits, if user is not the admin
		if($_SESSION["s"]["user"]["typ"] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_dns_record FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another mailbox.
			if($this->id == 0 && $client["limit_dns_record"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM dns_rr WHERE sys_groupid = ?", $client_group_id);
				if($tmp["number"] >= $client["limit_dns_record"]) {
					$app->error($app->tform->wordbook["limit_dns_record_txt"]);
				}
			}
		} // end if user is not admin
		
		// Check that the record does not yet exist
		$existing_records = $app->db->queryAllRecords("SELECT id FROM dns_rr WHERE zone = ? AND name = ? AND type = 'TXT' AND data LIKE 'v=spf1%'", $_POST['zone'], $_POST['name']);
		if (!empty($existing_records)) {
			if (count($existing_records) > 1) {
				$multiple_existing_records_error_txt = $app->tform->wordbook['spf_record_exists_multiple_txt'];
				$multiple_existing_records_error_txt = str_replace('{hostname}', $_POST['name'], $multiple_existing_records_error_txt);

				$app->error($multiple_existing_records_error_txt);
			}

			// If there is just one existing record, three things can be going on:
			// - if we are adding a new record, show a warning that it already exists and offer to edit it
			// - if we are editing an existing record and changing its 'name' field to one that is already existing, also show the warning
			// - otherwise we are just editing the existing the record, so there is no need for a warning
			$existing_record = array_pop($existing_records);
			if (empty($this->dataRecord['id']) || ($this->dataRecord['id'] !== $existing_record['id'])) {
				$existing_record_error_txt = $app->tform->wordbook['spf_record_exists_txt'];
				$existing_record_error_txt = str_replace('{hostname}', $_POST['name'], $existing_record_error_txt);
				$existing_record_error_txt = str_replace('{existing_record_id}', $existing_record['id'], $existing_record_error_txt);

				$app->error($existing_record_error_txt);
			}
		}

		// Create spf-record
		$spf_record = array();

		if (!empty($this->dataRecord['spf_mx'])) {
			$spf_record[] = 'mx';
		}
		if (!empty($this->dataRecord['spf_a'])) {
			$spf_record[] = 'a';
		}
		$spf_ip = trim($this->dataRecord['spf_ip']);
		if (!empty($spf_ip)) {
			$rec = explode(' ', $spf_ip);
			foreach ($rec as $ip) {
				$temp_ip = explode('/', $ip);
				if (filter_var($temp_ip[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
					$temp = 'ip4:' . $temp_ip[0];
					if (isset($temp_ip[1])) $temp .= '/' . $temp_ip[1];
					$spf_record[] = $temp;
					unset($temp);
				}
				elseif (filter_var($temp_ip[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
					$temp = 'ip6:' . $temp_ip[0];
					if (isset($temp_ip[1])) $temp .= '/' . $temp_ip[1];
					$spf_record[] = $temp;
					unset($temp);
				}
				else { 
					if (isset($app->tform->errorMessage )) $app->tform->errorMessage = '<br/>' . $app->tform->errorMessage;
					$app->tform->errorMessage .= $app->tform->wordbook["spf_invalid_ip_txt"]. $temp_ip[0];
					if (isset( $temp_ip[1])) $app->tform->errorMessage .= "/".$temp_ip[1];
				}
			}
		}
		$spf_hostname = trim($this->dataRecord['spf_hostname']);
		if (!empty($spf_hostname)) {
			$rec = explode(' ', $spf_hostname);
			foreach ($rec as $hostname) { 
				if (preg_match('/^[a-zA-Z0-9\\.\\-\\*]{0,64}$/', $hostname)) 
					$spf_record[] = 'a:' . $hostname;
				else {
					if (isset($app->tform->errorMessage )) $app->tform->errorMessage .= '<br/>' . $app->tform->wordbook["spf_invalid_hostname_txt"]. $hostname;
					$app->tform->errorMessage .= $app->tform->wordbook["spf_invalid_hostname_txt"]. $hostname;
				}
			}
			unset($rec);
		}
		$spf_domain = trim($this->dataRecord['spf_domain']);
		if (!empty($spf_domain)) {
			$rec = explode(' ', $spf_domain);
			foreach ($rec as $domain) {
				if (preg_match('/^[_a-zA-Z0-9\\.\\-\\*]{0,64}$/', $domain))
					$spf_record[] = 'include:' . $domain;
				else {
					if (isset($app->tform->errorMessage )) $app->tform->errorMessage .= '<br/>' . $app->tform->wordbook["spf_invalid_domain_txt"]. $domain;
					$app->tform->errorMessage .= $app->tform->wordbook["spf_invalid_domain_txt"]. $domain;
				}
			}
		}

		$temp = implode(' ', $spf_record);unset($spf_record);
		if (!empty($temp)) 
			$this->dataRecord['data'] = 'v=spf1 ' . $temp . ' ' . $this->dataRecord['spf_mechanism'] . 'all';
		else $this->dataRecord['data'] = 'v=spf1 ' . $this->dataRecord['spf_mechanism'] . 'all';
		unset($temp);

		if (isset($this->dataRecord['active'])) $this->dataRecord['active'] = 'Y';
		
		// Set the server ID of the rr record to the same server ID as the parent record.
		$this->dataRecord["server_id"] = $soa["server_id"];

		// Update the serial number  and timestamp of the RR record
		$soa = $app->db->queryOneRecord("SELECT serial FROM dns_rr WHERE id = ?", $this->id);
		$this->dataRecord["serial"] = $app->validate_dns->increase_serial($soa["serial"]);
		$this->dataRecord["stamp"] = date('Y-m-d H:i:s');

		if (!isset($this->dataRecord['active'])) $this->dataRecord['active'] = 'N';

		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app, $conf;

		//* Set the sys_groupid of the rr record to be the same then the sys_groupid of the soa record
		$soa = $app->db->queryOneRecord("SELECT sys_groupid,serial FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $app->functions->intval($this->dataRecord["zone"]));
		$app->db->datalogUpdate('dns_rr', array("sys_groupid" => $soa['sys_groupid']), 'id', $this->id);

		//* Update the serial number of the SOA record
		$soa_id = $app->functions->intval($_POST["zone"]);
		$serial = $app->validate_dns->increase_serial($soa["serial"]);
		$app->db->datalogUpdate('dns_soa', array("serial" => $serial), 'id', $soa_id);

	}

	function onAfterUpdate() {
		global $app, $conf;

		//* Update the serial number of the SOA record
		$soa = $app->db->queryOneRecord("SELECT serial FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $app->functions->intval($this->dataRecord["zone"]));
		$soa_id = $app->functions->intval($_POST["zone"]);
		$serial = $app->validate_dns->increase_serial($soa["serial"]);
		$app->db->datalogUpdate('dns_soa', array("serial" => $serial), 'id', $soa_id);
	}

}

$page = new page_action;
$page->onLoad();

?>
