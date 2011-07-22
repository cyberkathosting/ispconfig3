<?php
/**
 * sites_web_domain_plugin plugin
 * 
 * @author Till Brehm, projektfarm GmbH
 */
 
class vm_openvz_plugin {

	var $plugin_name        = 'vm_openvz_plugin';
	var $class_name         = 'vm_openvz_plugin';
	var $id = 0;
	var $dataRecord = array();
	var $oldDataRecord = array();


    /*
            This function is called when the plugin is loaded
    */
    function onLoad() {
        global $app;
        
		//* Register for events        
        $app->plugin->registerEvent('vm:openvz_vm:on_after_insert','vm_openvz_plugin','openvz_vm_insert');
		$app->plugin->registerEvent('vm:openvz_vm:on_after_update','vm_openvz_plugin','openvz_vm_update');
		$app->plugin->registerEvent('vm:openvz_vm:on_after_delete','vm_openvz_plugin','openvz_vm_delete');
    }

    /*
		Function that gets called after a new vm was inserted           
    */
    function openvz_vm_insert($event_name, $page_form) {
        global $app, $conf;  

		$this->id = $page_form->id;
		$this->dataRecord = $page_form->dataRecord;	
		$this->oldDataRecord = $page_form->oldDataRecord;	
        
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
	
	/*
		Function that gets called after a vm was updated          
    */
    function openvz_vm_update($event_name, $page_form) {
        global $app, $conf;
		
		$this->id = $page_form->id;
		$this->dataRecord = $page_form->dataRecord;
		$this->oldDataRecord = $page_form->oldDataRecord;	
		
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
	
	function openvz_vm_delete($event_name, $page_form) {
        global $app, $conf;
		
		//* Free the IP address
		$tmp = $app->db->queryOneRecord("SELECT ip_address_id FROM openvz_ip WHERE vm_id = ".$page_form->id);
		$app->db->datalogUpdate('openvz_ip', 'vm_id = 0', 'ip_address_id', $tmp['ip_address_id']);
		unset($tmp);
		
	}
	
	private function applyTemplate() {
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
	
	private function makeOpenVZConfig() {
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
	
	private function createDNS() {
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