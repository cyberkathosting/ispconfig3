<?php
/*
Copyright (c) 2017, Florian Schaal, schaal @it UG
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

$tform_def_file = 'form/dns_caa.tform.php';

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
		global $app;
		// we will check only users, not admins
		if($_SESSION['s']['user']['typ'] == 'user') {
			// Get the limits of the client
			$client_group_id = intval($_SESSION['s']['user']['default_group']);
			$client = $app->db->queryOneRecord("SELECT limit_dns_record FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another record.
			if($client['limit_dns_record'] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM dns_rr WHERE sys_groupid = ?", $client_group_id);
				if($tmp['number'] >= $client['limit_dns_record']) {
					$app->error($app->tform->wordbook['limit_dns_record_txt']);
				}
			}
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app;

		$zone = @(!isset($this->dataRecord['zone']))?$app->functions->intval($_GET['zone']):$this->dataRecord['zone'];

		// get domain-name
		$sql = "SELECT * FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r');
		$soa = $app->db->queryOneRecord($sql, $zone);
		$domain_name = rtrim($soa['origin'], '.');
		$app->tpl->setVar('name', $domain_name);
		if($this->id > 0) {
			$temp = $this->dataRecord['name'];
			$temp = str_replace($soa['origin'], '', $this->dataRecord['name']);
			$temp = trim($temp,'.');
			if(trim($temp != '')) $app->tpl->setVar('additional', $temp);
			unset($temp);
		}

		//create ca-list
		$rec = $app->db->QueryAllRecords("SELECT * FROM dns_ssl_ca WHERE active = 'Y' AND ca_issue != '' ORDER by ca_name ASC");
		$ca_select = "<option value='0' >".$app->tform->wordbook['select_txt']."</option>";
		if(count($rec) > 0) {
			foreach($rec as $ca) {
				if(strpos($this->dataRecord['data'], $ca['ca_issue']) !== FALSE) $selected = ' selected'; else $selected='';
				$ca_select .= "<option value='$ca[id]'$selected>$ca[ca_name]</option>\r\n";
			}
		}
		$app->tpl->setVar('ca_list', $ca_select);
		$app->tpl->setVar('type', 'CAA');
		if($this->id > 0) {
			if(stristr($this->dataRecord['data'], 'issuewild') !== FALSE) $app->tpl->setVar('allow_wildcard', 'CHECKED'); else $app->tpl->setVar('allow_wildcard', 'UNCHECKED');
			if(strpos($this->dataRecord['data'], '128') === 0) $app->tpl->setVar('allow_critical', 'CHECKED'); else $app->tpl->setVar('allow_critical', 'UNCHECKED');
			$app->tpl->setVar('edit_disabled', 1);
		} else {
			$app->tpl->setVar('ttl', $soa['ttl']);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app;

		// Check the client limits, if user is not the admin
		if($_SESSION['s']['user']['typ'] != 'admin') { // if user is not admin
			// Get the limits of the client
			$client_group_id = intval($_SESSION['s']['user']['default_group']);
			$client = $app->db->queryOneRecord("SELECT limit_dns_record FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			// Check if the user may add another record.
			if($this->id == 0 && $client['limit_dns_record'] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM dns_rr WHERE sys_groupid = ?", $client_group_id);
				if($tmp['number'] >= $client['limit_dns_record']) {
					$app->error($app->tform->wordbook['limit_dns_record_txt']);
				}
			}
		} // end if user is not admin

		// Check CA
		if($this->dataRecord['ca_issue'] == '') $this->error('ca_error_txt');

		// Get the parent soa record of the domain
		$soa = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $_POST['zone']);
		$this->dataRecord['name'] = $soa['origin'];

		// Check if Domain belongs to user
		if($soa['id'] != $_POST['zone']) $this->error('no_zone_perm');

		// Set the server ID of the rr record to the same server ID as the parent record.
        $this->dataRecord['server_id'] = $soa['server_id'];
		
		// Set issue
		$critical = 0; //* To use critical > 0, uncommented "<div class="critical form-group">" in the template
		if(isset($this->dataRecord['allow_critical']) && $this->dataRecord['allow_critical'] == 'on' && isset($this->dataRecord['ca_critical']) && $this->dataRecord['ca_critical'] == 1) $critical = 128;
		if(isset($this->dataRecord['allow_wildcard']) && $this->dataRecord['allow_wildcard'] == "on") {
			$this->dataRecord['data'] = $critical.' issuewild "'.$this->dataRecord['ca_issue'];
		} else {
			$this->dataRecord['data'] = $critical.' issue "'.$this->dataRecord['ca_issue'];
		}
		unset($critical);
		if(isset($this->dataRecord['options']) && $this->dataRecord['options'] != '') {
			$options=explode(',', $this->dataRecord['options']);
			foreach($options as $option) {
				if(trim($option) != '') {
					if(preg_match('/^(\w+|d\+)=(\w+|d\+)/', $option)) {
						$this->dataRecord['data'] = $this->dataRecord['data'] . '; '.$option;
					} else {
						$this->error('ca_option_error');
					}
				}
			}
		}
		$this->dataRecord['data'] = $this->dataRecord['data'].'"';

		// Set name
		if($this->dataRecord['additional'] != '') {
			$temp = explode(',', $this->dataRecord['additional']);
			$temp = $temp[0]; // if we have more hostnames the interface-plugin will be used
			$temp = trim($temp,'.');
			if(trim($temp != '')) $this->dataRecord['name'] = $temp.'.'.$this->dataRecord['name'];
			unset($temp);
		}

		// Check for duplicate
		$temp = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE type = 'CAA' AND name = ? AND data = ? AND active = ?", $this->dataRecord['name'], $this->dataRecord['data'], $POST['active']);
		if(is_array($temp)) $this->error('caa_exists_error');
		unset($temp);
		
		// Update the serial number  and timestamp of the RR record
		$dns_rr = $app->db->queryOneRecord("SELECT serial FROM dns_rr WHERE id = ?", $this->id);
		$this->dataRecord['serial'] = $app->validate_dns->increase_serial($dns_rr['serial']);
		$this->dataRecord['stamp'] = date('Y-m-d H:i:s');

		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app;

		//* Set the sys_groupid of the rr record to be the same then the sys_groupid of the soa record
		$soa = $app->db->queryOneRecord("SELECT sys_groupid,serial FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $this->dataRecord['zone']);
		$app->db->datalogUpdate('dns_rr', array('sys_groupid' => $soa['sys_groupid']), 'id', $this->id);

		//* Update the serial number of the SOA record
		$soa_id = $app->functions->intval($_POST["zone"]);
		$serial = $app->validate_dns->increase_serial($soa['serial']);
		$app->db->datalogUpdate('dns_soa', array('serial' => $serial), 'id', $soa_id);

	}

	function onAfterUpdate() {
		global $app;

		//* Update the serial number of the SOA record
		$soa = $app->db->queryOneRecord("SELECT serial FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $this->dataRecord['zone']);
		$soa_id = $app->functions->intval($_POST['zone']);
		$serial = $app->validate_dns->increase_serial($soa['serial']);
		$app->db->datalogUpdate('dns_soa', array('serial' => $serial), 'id', $soa_id);
	}

	private function error($errmsg) {
		global $app;
		if (isset($app->tform->errorMessage)) $app->tform->errorMessage = '<br/>' . $app->tform->errorMessage;
		$app->tform->errorMessage .= $app->tform->wordbook[$errmsg];
	}

}

$page = new page_action;
$page->onLoad();

?>
