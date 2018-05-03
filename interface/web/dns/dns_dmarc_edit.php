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

$tform_def_file = "form/dns_dmarc.tform.php";

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
		global $app, $conf;

		$zone = $app->functions->intval($_GET['zone']);
		// get domain-name
		$sql = "SELECT * FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r');
		$rec = $app->db->queryOneRecord($sql, $zone);
		$domain_name = rtrim($rec['origin'], '.');

		// set defaults
		$dmarc_policy = 'none';
		$dmarc_adkim = 'r';
		$dmarc_aspf = 'r';
		$dmarc_rf = 'afrf';
		$dmarc_pct = 100;
		$dmarc_ri = 86400;
		$dmarc_sp = 'same';

		//* check for an existing dmarc-record
		$sql = "SELECT data, active FROM dns_rr WHERE data LIKE 'v=DMARC1%' AND zone = ? AND name = ? AND " . $app->tform->getAuthSQL('r');
		$rec = $app->db->queryOneRecord($sql, $zone, '_dmarc.'.$domain_name.'.');
		if ( isset($rec) && !empty($rec) ) {
			$this->id = 1;
			$old_data = strtolower($rec['data']);
			$app->tpl->setVar("data", $old_data, true);
            if ($rec['active'] == 'Y') $app->tpl->setVar("active", "CHECKED"); else $app->tpl->setVar("active", "UNCHECKED");
			$dmarc_rua = '';
			$dmarc_ruf = '';
			$dmac_rf = '';
			$dmac_rua = '';
			$dmac_ruf = '';
			// browse through data
			$temp = explode('; ', $old_data);
			foreach ($temp as $part) {
				if (preg_match("/^p=/", $part)) $dmarc_policy = str_replace('p=', '', $part);
				if (preg_match("/^rua=/", $part)) {
					$dmarc_rua = str_replace(array('rua=','mailto:'), '', $part).' ';
					$dmarc_rua = str_replace(',', ' ', $dmarc_rua);
				}
				if (preg_match("/^ruf=/", $part)) {
					$dmarc_ruf = str_replace(array('ruf=','mailto:'), '', $part).' ';
					$dmarc_ruf = str_replace(',', ' ', $dmarc_ruf);
				}
				if (preg_match("/^fo=/", $part)) $dmarc_fo = str_replace('fo=', '', $part);
				if (preg_match("/^adkim=/", $part)) $dmarc_adkim = str_replace('adkim=', '', $part);
				if (preg_match("/^aspf=/", $part)) $dmarc_aspf = str_replace('aspf=', '', $part);
				if (preg_match("/^rf=/", $part)) $dmarc_rf = str_replace('rf=', '', $part);
				if (preg_match("/^(afrf:iodef|iodef:afrf)$/s", $dmarc_rf)) $dmarc_rf = str_replace(':', ' ', $dmarc_rf);
				if (preg_match("/^pct=/", $part)) $dmarc_pct = str_replace('pct=', '', $part);
				if (preg_match("/^ri=/", $part)) $dmarc_ri = str_replace('ri=', '', $part);
			}
		} 

		//set html-values
		$app->tpl->setVar('domain', $domain_name, true);

		//create dmarc-policy-list
		$dmarc_policy_value = array( 
			'none' => 'dmarc_policy_none_txt',
			'quarantine' => 'dmarc_policy_quarantine_txt',
			'reject' => 'dmarc_policy_reject_txt',
		);
		$dmarc_policy_list='';
		foreach($dmarc_policy_value as $value => $txt) {
			$selected = @($dmarc_policy == $value)?' selected':'';
			$dmarc_policy_list .= "<option value='$value'$selected>".$app->tform->wordbook[$txt]."</option>\r\n";
		}
		$app->tpl->setVar('dmarc_policy', $dmarc_policy_list);

		if (!empty($dmarc_rua)) $app->tpl->setVar("dmarc_rua", $dmarc_rua, true);

		if (!empty($dmarc_ruf)) $app->tpl->setVar("dmarc_ruf", $dmarc_ruf, true);

		//set dmarc-fo-options
		if (isset($dmarc_fo)) {
			$temp = explode(':', $dmarc_fo);
			foreach ($temp as $fo => $value) $app->tpl->setVar("dmarc_fo".$value, 'CHECKED');
		} else
			$app->tpl->setVar("dmarc_fo0", 'CHECKED');

		unset($temp);

		//create dmarc-adkim-list
		$dmarc_adkim_value = array( 
			'r' => 'dmarc_adkim_r_txt',
			's' => 'dmarc_adkim_s_txt',
		);
		$dmarc_adkim_list='';
		foreach($dmarc_adkim_value as $value => $txt) {
			$selected = @($dmarc_adkim == $value)?' selected':'';
			$dmarc_adkim_list .= "<option value='$value'$selected>".$app->tform->wordbook[$txt]."</option>\r\n";
		}
		$app->tpl->setVar('dmarc_adkim', $dmarc_adkim_list);

		//create dmarc-aspf-list
		$dmarc_aspf_value = array( 
			'r' => 'dmarc_aspf_r_txt',
			's' => 'dmarc_aspf_s_txt',
		);
		$dmarc_aspf_list='';
		foreach($dmarc_aspf_value as $value => $txt) {
			$selected = @($dmarc_aspf == $value)?' selected':'';
			$dmarc_aspf_list .= "<option value='$value'$selected>".$app->tform->wordbook[$txt]."</option>\r\n";
		}
		$app->tpl->setVar('dmarc_aspf', $dmarc_aspf_list);

		if ( strpos($dmarc_rf, 'afrf') !== false ) $app->tpl->setVar("dmarc_rf_afrf", 'CHECKED');
		if ( strpos($dmarc_rf, 'iodef') !== false ) $app->tpl->setVar("dmarc_rf_iodef", 'CHECKED');

		$app->tpl->setVar("dmarc_pct", $dmarc_pct, true);

		$app->tpl->setVar("dmarc_ri", $dmarc_ri, true);

		//create dmarc-sp-list
		$dmarc_sp_value = array( 
			'same' => 'dmarc_sp_same_txt',
			'none' => 'dmarc_sp_none_txt',
			'quarantine' => 'dmarc_sp_quarantine_txt',
			'reject' => 'dmarc_sp_reject_txt',
		);
		$dmarc_sp_list='';
		foreach($dmarc_sp_value as $value => $txt) {
			$selected = @($dmarc_sp == $value)?' selected':'';
			$dmarc_sp_list .= "<option value='$value'$selected>".$app->tform->wordbook[$txt]."</option>\r\n";
		}
		$app->tpl->setVar('dmarc_sp', $dmarc_sp_list);

		parent::onShowEnd();

	}

	function onSubmit() {
		global $app, $conf;

		// Get the parent soa record of the domain
		$soa = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = ? AND " . $app->tform->getAuthSQL('r'), $_POST['zone']);

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

		$domain_name = rtrim($soa['origin'], '.');
		// DMARC requieres at least one active dkim-record...
		$sql = "SELECT * FROM dns_rr WHERE name LIKE ? AND type='TXT' AND data like 'v=DKIM1;%' AND active='Y'";
		$temp = $app->db->queryAllRecords($sql, '%._domainkey.'.$domain_name.'.');
		if (empty($temp)) {
			if (isset($app->tform->errorMessage )) $app->tform->errorMessage = '<br/>' . $app->tform->errorMessage;
			$app->tform->errorMessage .= $app->tform->wordbook['dmarc_no_dkim_txt'].$email;
		}

		// ... and an active spf-record (this breaks the current draft but DMARC is useless if you use DKIM or SPF
		$sql = "SELECT * FROM dns_rr WHERE name LIKE ? AND type='TXT' AND (data LIKE 'v=spf1%' AND active = 'y')";
		$temp = $app->db->queryAllRecords($sql, $domain_name.'.');
		// abort if more than 1 active spf-records (backward-compatibility)
		if (is_array($temp[1])) {
			if (isset($app->tform->errorMessage )) $app->tform->errorMessage = '<br/>' . $app->tform->errorMessage;
			$app->tform->errorMessage .= $app->tform->wordbook['dmarc_more_spf_txt'];
		}
		if (empty($temp)) {
			if (isset($app->tform->errorMessage )) $app->tform->errorMessage = '<br/>' . $app->tform->errorMessage;
			$app->tform->errorMessage .= $app->tform->wordbook['dmarc_no_spf_txt'];
		}
		unset($temp);

		//validate dmarc_pct
		$this->dataRecord['dmarc_pct'] = $app->functions->intval($this->dataRecord['dmarc_pct']);
		if ($this->dataRecord['dmarc_pct'] < 0) $this->dataRecord['dmarc_pct'] = 0;
		if ($this->dataRecord['dmarc_pct'] > 100) $this->dataRecord['dmarc_pct'] = 100;
		
		//create dmarc-record
		$dmarc_record[] = 'p='.$this->dataRecord['dmarc_policy'];

		if (!empty($this->dataRecord['dmarc_rua'])) {
			$dmarc_rua = explode(' ', $this->dataRecord['dmarc_rua']);
			$dmarc_rua = array_filter($dmarc_rua);
			foreach ($dmarc_rua as $rec) {
				if (!filter_var($rec, FILTER_VALIDATE_EMAIL)) {
					if (isset($app->tform->errorMessage )) $app->tform->errorMessage = '<br/>' . $app->tform->errorMessage;
					$app->tform->errorMessage .= $app->tform->wordbook['dmarc_invalid_email_txt'].': '.$dmarc_rua;
				} else {
					$temp .= 'mailto:'.$rec.',';
				}
			}
			$dmarc_record[] = 'rua='.rtrim($temp, ',');
			unset ($dmarc_rua);
			unset($temp);
		}
		
		if (!empty($this->dataRecord['dmarc_ruf'])) {
			$dmarc_ruf = explode(' ', $this->dataRecord['dmarc_ruf']);
			$dmarc_ruf = array_filter($dmarc_ruf);
			foreach ($dmarc_ruf as $rec) {
				if (!filter_var($rec, FILTER_VALIDATE_EMAIL)) {
					if (isset($app->tform->errorMessage )) $app->tform->errorMessage = '<br/>' . $app->tform->errorMessage;
					$app->tform->errorMessage .= $app->tform->wordbook['dmarc_invalid_email_txt'].': '.$dmarc_rua;
				} else {
					$temp .= 'mailto:'.$rec.',';
				}
			}
			$dmarc_record[] = 'ruf='.rtrim($temp, ',');
			unset ($dmarc_ruf);
			unset($temp);
		}
		
		$fo_rec = array();
		if (isset($this->dataRecord['dmarc_fo0'])) $fo_rec[] = '0';
		if (isset($this->dataRecord['dmarc_fo1'])) $fo_rec[] = '1';
		if (isset($this->dataRecord['dmarc_fod'])) $fo_rec[] = 'd';
		if (isset($this->dataRecord['dmarc_fos'])) $fo_rec[] = 's';
		if (is_array($fo_rec) && !empty($fo_rec)) {
			$rec = 'fo='.implode(':', $fo_rec);
			if ($rec != 'fo=0') $dmarc_record[] = 'fo='.implode(':', $fo_rec);
			unset($rec);
		}

		if ($this->dataRecord['dmarc_adkim'] != 'r' )
			$dmarc_record[] = 'adkim='.$this->dataRecord['dmarc_adkim'];

		if ($this->dataRecord['dmarc_aspf'] != 'r' )
			$dmarc_record[] = 'aspf='.$this->dataRecord['dmarc_aspf'];

		if (isset($this->dataRecord['dmarc_rf_afrf']) && isset($this->dataRecord['dmarc_rf_iodef']))
			$dmarc_record[] = 'rf=afrf:iodef';
		else {
			 if (isset($this->dataRecord['dmarc_rf_iodef']))
				$dmarc_record[] = 'rf=iodef';
		}
		unset($fo_rec);

		if (!empty($this->dataRecord['dmarc_pct']) && $this->dataRecord['dmarc_pct'] != 100)
			$dmarc_record[] = 'pct='.$this->dataRecord['dmarc_pct'];

		if (!empty($this->dataRecord['dmarc_ri']) && $this->dataRecord['dmarc_ri'] != '86400')
			$dmarc_record[] = 'ri='.$this->dataRecord['dmarc_ri'];

		if (!empty($this->dataRecord['dmarc_sp']) && $this->dataRecord['dmarc_sp'] != 'same')
			$dmarc_record[] = 'sp='.$this->dataRecord['dmarc_sp'];

		$temp = implode('; ', $dmarc_record);
		if (!empty($temp))
			$this->dataRecord['data'] = 'v=DMARC1; ' . $temp;
		else $app->tform->errorMessage .= $app->tform->wordbook["dmarc_empty_txt"];

		$this->dataRecord['name'] = '_dmarc.' . $soa['origin'];
		if (isset($this->dataRecord['active'])) $this->dataRecord['active'] = 'Y';
		
		// Set the server ID of the rr record to the same server ID as the parent record.
		$this->dataRecord["server_id"] = $soa["server_id"];

		// Update the serial number  and timestamp of the RR record
		$soa = $app->db->queryOneRecord("SELECT serial FROM dns_rr WHERE id = ?", $this->id);
		$this->dataRecord["serial"] = $app->validate_dns->increase_serial($soa["serial"]);
		$this->dataRecord["stamp"] = date('Y-m-d H:i:s');

		// always update an existing entry
		$check=$app->db->queryOneRecord("SELECT * FROM dns_rr WHERE zone = ? AND type = ? AND data LIKE 'v=DMARC1%' AND name = ?", $this->dataRecord['zone'], $this->dataRecord['type'], $this->dataRecord['name']);
		$this->id = $check['id'];
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
