<?php
/*
Copyright (c) 2005 - 2010, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/openvz_vm.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('vm');

// Loading classes
$app->uses('tpl,tform');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowEnd() {
		global $app, $conf;

		//* Client: If the logged in user is not admin and has no sub clients (no rseller)
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			//* Reseller: If the logged in user is not admin and has sub clients (is a rseller)
		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			
			// Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$client['client_id']." ORDER BY name";
			$records = $app->db->queryAllRecords($sql);
			$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$client['client_id']);
			$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contact_name'].'</option>';
			$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($records)) {
				foreach( $records as $rec) {
					$selected = @($rec["groupid"] == $tmp_data_record["sys_groupid"])?'SELECTED':'';
					$client_select .= "<option value='$rec[groupid]' $selected>$rec[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);

			//* Admin: If the logged in user is admin
		} else {

			// Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0 ORDER BY name";
			$clients = $app->db->queryAllRecords($sql);
			$client_select = "<option value='0'></option>";
			$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @($client["groupid"] == $tmp_data_record["sys_groupid"])?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);

		}
		
		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"]);
			$app->tpl->setVar("ostemplate_id_value", $this->dataRecord["ostemplate_id"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		parent::onShowEnd();
	}

	function onSubmit() {
		global $app, $conf;

		// Clients may not set the client_group_id, so we unset them if user is not a admin and the client is not a reseller
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);

		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app, $conf;

		// make sure that the record belongs to the clinet group and not the admin group when admin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE openvz_vm SET sys_groupid = $client_group_id WHERE vm_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE openvz_vm SET sys_groupid = $client_group_id WHERE vm_id = ".$this->id);
		}
		
		// Set the VEID
		$tmp = $app->db->queryOneRecord('SELECT MAX(veid) + 1 as newveid FROM openvz_vm');
		$veid = ($tmp['newveid'] > 100)?$tmp['newveid']:101;
		$app->db->query("UPDATE openvz_vm SET veid = ".$veid." WHERE vm_id = ".$this->id);
		unset($tmp);
		
		// Apply template values to the advanced tab settings
		$this->applyTemplate();
		
		// Set the IP address
		$app->db->query("UPDATE openvz_ip SET vm_id = ".$this->id." WHERE ip_address = '".$this->dataRecord['ip_address']."'");
		
		// Create the OpenVZ config file and store it in config field
		$this->makeOpenVZConfig();
		
		// Create the DNS record
		$this->createDNS();
		
	}

	function onAfterUpdate() {
		global $app, $conf;

		// make sure that the record belongs to the clinet group and not the admin group when a admin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE openvz_vm SET sys_groupid = $client_group_id WHERE vm_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE openvz_vm SET sys_groupid = $client_group_id WHERE vm_id = ".$this->id);
		}
		
		if(isset($this->dataRecord["ostemplate_id"]) && $this->oldDataRecord["ostemplate_id"] != $this->dataRecord["ostemplate_id"]) {
			$this->applyTemplate();
		}
		
		// Set the IP address
		if(isset($this->dataRecord['ip_address'])) $app->db->query("UPDATE openvz_ip SET vm_id = ".$this->id." WHERE ip_address = '".$this->dataRecord['ip_address']."'");
		
		// Create the OpenVZ config file and store it in config field
		$this->makeOpenVZConfig();
		
		// Create the DNS record
		if((isset($this->dataRecord['hostname']) && $this->dataRecord['hostname'] != $this->oldDataRecord['hostname']) 
		or (isset($this->dataRecord['create_dns']) && $this->dataRecord['create_dns'] != $this->oldDataRecord['create_dns'])) {
			$this->createDNS();
		}
		
	}
	
	function applyTemplate() {
		global $app, $conf;
		
		$tpl = $app->db->queryOneRecord("SELECT * FROM openvz_template WHERE template_id = ".$this->dataRecord["template_id"]);
		
		$sql = "UPDATE openvz_vm SET ";
		$sql .= "diskspace = '".$tpl['diskspace']."', ";
		$sql .= "ram = '".$tpl['ram']."', ";
		$sql .= "ram_burst = '".$tpl['ram_burst']."', ";
		$sql .= "cpu_units = '".$tpl['cpu_units']."', ";
		$sql .= "cpu_num = '".$tpl['cpu_num']."', ";
		$sql .= "cpu_limit = '".$tpl['cpu_limit']."', ";
		$sql .= "io_priority = '".$tpl['io_priority']."', ";
		$sql .= "nameserver = '".$tpl['nameserver']."', ";
		$sql .= "create_dns = '".$tpl['create_dns']."', ";
		$sql .= "capability = '".$tpl['capability']."' ";
		$sql .= "WHERE vm_id = ".$this->id;
		$app->db->query($sql);
		
	}
	
	function makeOpenVZConfig() {
		global $app, $conf;
		
		$vm = $app->tform->getDataRecord($this->id);
		$vm_template = $app->db->queryOneRecord("SELECT * FROM openvz_template WHERE template_id = ".$vm['template_id']);
		$burst_ram = $vm['ram_burst']*256;
		$guar_ram = $vm['ram']*256;
		
		$tpl = new tpl();
		$tpl->newTemplate('templates/openvz.conf.tpl');
		
		$onboot = ($vm['start_boot'] == 'y')?'yes':'no';
		$tpl->setVar('onboot',$onboot);
		
		$tpl->setVar('kmemsize',$vm_template['kmemsize']);
		$tpl->setVar('lockedpages',$vm_template['lockedpages']);
		$tpl->setVar('privvmpages',$burst_ram.':'.$burst_ram);
		$tpl->setVar('shmpages',$guar_ram.':'.$guar_ram);
		$tpl->setVar('numproc',$vm_template['numproc']);
		$tpl->setVar('physpages',$vm_template['physpages']);
		$tpl->setVar('vmguarpages',$guar_ram.':'.$guar_ram);
		$tpl->setVar('oomguarpages',$guar_ram.':'.$guar_ram);
		$tpl->setVar('numtcpsock',$vm_template['numtcpsock']);
		$tpl->setVar('numflock',$vm_template['numflock']);
		$tpl->setVar('numpty',$vm_template['numpty']);
		$tpl->setVar('numsiginfo',$vm_template['numsiginfo']);
		$tpl->setVar('tcpsndbuf',$vm_template['tcpsndbuf']);
		$tpl->setVar('tcprcvbuf',$vm_template['tcprcvbuf']);
		$tpl->setVar('othersockbuf',$vm_template['othersockbuf']);
		$tpl->setVar('dgramrcvbuf',$vm_template['dgramrcvbuf']);
		$tpl->setVar('numothersock',$vm_template['numothersock']);
		$tpl->setVar('dcachesize',$vm_template['dcachesize']);
		$tpl->setVar('numfile',$vm_template['numfile']);
		$tpl->setVar('avnumproc',$vm_template['avnumproc']);
		$tpl->setVar('numiptent',$vm_template['numiptent']);
		
		$diskspace = $vm['diskspace']*1048576;
		$diskinodes = $vm['diskspace']*524288;
		
		$tpl->setVar('diskspace',$diskspace.":".$diskspace);
		$tpl->setVar('diskinodes',$diskinodes.":".$diskinodes);
		$tpl->setVar('io_priority',$vm['io_priority']);
		
		$tpl->setVar('cpu_num',$vm['cpu_num']);
		$tpl->setVar('cpu_units',$vm['cpu_units']);
		$tpl->setVar('cpu_limit',$vm['cpu_limit']);
		
		$hostname = str_replace('{VEID}',$vm['veid'],$vm['hostname']);
		
		$tpl->setVar('hostname',$hostname);
		$tpl->setVar('ip_address',$vm['ip_address']);
		$tpl->setVar('nameserver',$vm['nameserver']);
		$tpl->setVar('capability',$vm['capability']);
		
		$tmp = $app->db->queryOneRecord("SELECT template_file FROM openvz_ostemplate WHERE ostemplate_id = ".$vm['ostemplate_id']);
		$tpl->setVar('ostemplate',$tmp['template_file']);
		unset($tmp);
		
		$openvz_config = $app->db->quote($tpl->grab());
		$app->db->query("UPDATE openvz_vm SET config = '".$openvz_config."' WHERE vm_id = ".$this->id);
		
		unset($tpl);
		
	}
	
	function createDNS() {
		global $app, $conf;
		
		$vm = $app->tform->getDataRecord($this->id);
		
		if($vm['create_dns'] != 'y') return;
		
		$full_hostname = str_replace('{VEID}',$vm['veid'],$vm['hostname']);
		$hostname_parts = explode('.',$full_hostname);
		$hostname = $hostname_parts[0];
		unset($hostname_parts[0]);
		$zone = implode('.',$hostname_parts);
		unset($hostname_parts);
		
		// Find the dns zone
		$zone_rec = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE origin = '$zone.'");
		$rr_rec = $app->db->queryOneRecord("SELECT * FROM dns_rr WHERE zone = '".$zone_rec['id']."' AND name = '$hostname'");
		
		if($zone_rec['id'] > 0) {
			$ip_address = $vm['ip_address'];
			$sys_userid = $zone_rec['sys_userid'];
			$sys_groupid = $zone_rec['sys_groupid'];
			$server_id = $zone_rec['server_id'];
			$dns_soa_id = $zone_rec['id'];
			
			if($rr_rec['id'] > 0) {
				$app->uses('validate_dns');
				$app->db->datalogUpdate('dns_rr', "data = '$ip_address'", 'id', $rr_rec['id']);
				$serial = $app->validate_dns->increase_serial($zone_rec['serial']);
				$app->db->datalogUpdate('dns_soa', "serial = '$serial'", 'id', $zone_rec['id']);
			} else {
				$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `zone`, `name`, `type`, `data`, `aux`, `ttl`, `active`) VALUES 
				('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$dns_soa_id', '$hostname', 'A', '$ip_address', '0', '3600', 'Y')";
				$dns_rr_id = $app->db->datalogInsert('dns_rr', $insert_data, 'id');
			}
			
		}
		
		
		
		
	}

}

$page = new page_action;
$page->onLoad();

?>