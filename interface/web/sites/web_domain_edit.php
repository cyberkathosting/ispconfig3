<?php
/*
Copyright (c) 2007 - 2009, Till Brehm, projektfarm Gmbh
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

$tform_def_file = "form/web_domain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onShowNew() {
		global $app, $conf;
		
		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_web_domain',"type = 'vhost'")) {
				$app->error($app->tform->wordbook["limit_web_domain_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_web_domain',"type = 'vhost'")) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_web_domain_txt"]);
			}
		}
		
		parent::onShowNew();
	}
	
	function onShowEnd() {
		global $app, $conf;
		
		//* Client: If the logged in user is not admin and has no sub clients (no rseller)
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
		
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_web_domain, default_webserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_webserver]");
			$app->tpl->setVar("server_id","<option value='$client[default_webserver]'>$tmp[server_name]</option>");
			unset($tmp);
			
			// Fill the IP select field with the IP addresses that are allowed for this client
			// $ip_select = "<option value='*'>*</option>";
			// $app->tpl->setVar("ip_address",$ip_select);
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".$client['default_webserver'];
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = "<option value='*'>*</option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address",$ip_select);
			unset($tmp);
			unset($ips);
		
		//* Reseller: If the logged in user is not admin and has sub clients (is a rseller)
		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, limit_web_domain, default_webserver, client.contact_name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_webserver]");
			$app->tpl->setVar("server_id","<option value='$client[default_webserver]'>$tmp[server_name]</option>");
			unset($tmp);
			
			// Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$client['client_id'];
			$clients = $app->db->queryAllRecords($sql);
			$client_select = '<option value="'.$client['client_id'].'">'.$client['contact_name'].'</option>';
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @($client["groupid"] == $this->dataRecord["sys_groupid"])?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);
			
			// Fill the IP select field with the IP addresses that are allowed for this client
			//$ip_select = "<option value='*'>*</option>";
			//$app->tpl->setVar("ip_address",$ip_select);
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".$client['default_webserver'];
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = "<option value='*'>*</option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address",$ip_select);
			unset($tmp);
			unset($ips);
		
		//* Admin: If the logged in user is admin
		} else {
			
			// The user is admin, so we fill in all IP addresses of the server
			if($this->id > 0) {
				$server_id = @$this->dataRecord["server_id"];
			} else {
				// Get the first server ID
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM server WHERE web_server = 1 ORDER BY server_name LIMIT 0,1");
				$server_id = $tmp['server_id'];
			}
			
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = $server_id";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = "<option value='*'>*</option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address",$ip_select);
			unset($tmp);
			unset($ips);
			
			// Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0";
			$clients = $app->db->queryAllRecords($sql);
			$client_select = "<option value='0'></option>";
			if(is_array($clients)) {
				foreach( $clients as $client) {
					$selected = @($client["groupid"] == $this->dataRecord["sys_groupid"])?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);
			
		}
		
		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}
		
		parent::onShowEnd();
	}
	
	function onSubmit() {
		global $app, $conf;
		
		// Set a few fixed values
		$this->dataRecord["parent_domain_id"] = 0;
		$this->dataRecord["type"] = 'vhost';
		$this->dataRecord["vhost_type"] = 'name';
		
		if($_SESSION["s"]["user"]["typ"] != 'admin') {
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_web_domain, default_webserver, parent_client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			
			// When the record is updated
			if($this->id > 0) {
				// restore the server ID if the user is not admin and record is edited
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ".intval($this->id));
				$this->dataRecord["server_id"] = $tmp["server_id"];
				unset($tmp);
			// When the record is inserted
			} else {
				// set the server ID to the default mailserver of the client
				$this->dataRecord["server_id"] = $client["default_webserver"];
				
				
				// Check if the user may add another web_domain
				if($client["limit_web_domain"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM web_domain WHERE sys_groupid = $client_group_id and type = 'vhost'");
					if($tmp["number"] >= $client["limit_web_domain"]) {
						$app->error($app->tform->wordbook["limit_web_domain_txt"]);
					}
				}
				
			}
			
			// Clients may not set the client_group_id, so we unset them if user is not a admin and the client is not a reseller
			if(!$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);
		}
		
		
		parent::onSubmit();
	}
	
	function onAfterInsert() {
		global $app, $conf;
		
		// make sure that the record belongs to the clinet group and not the admin group when a dmin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE domain_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE domain_id = ".$this->id);
		}
		
		// Get configuration for the web system
		$app->uses("getconf");
		$web_rec = $app->tform->getDataRecord($this->id);
		$web_config = $app->getconf->get_server_config(intval($web_rec["server_id"]),'web');
		$document_root = str_replace("[website_id]",$this->id,$web_config["website_path"]);
		
		// get the ID of the client
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = $client_group_id");
			$client_id = intval($client["client_id"]);
		} else {
			//$client_id = intval($this->dataRecord["client_group_id"]);
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ".intval($this->dataRecord["client_group_id"]));
			$client_id = intval($client["client_id"]);
		}
		
		// Set the values for document_root, system_user and system_group
		$system_user = 'web'.$this->id;
		$system_group = 'client'.$client_id;
		$document_root = str_replace("[client_id]",$client_id,$document_root);
		
		$sql = "UPDATE web_domain SET system_user = '$system_user', system_group = '$system_group', document_root = '$document_root' WHERE domain_id = ".$this->id;
		$app->db->query($sql);
	}
	
	function onBeforeUpdate () {
		global $app, $conf;

		//* Check if the server has been changed
		// We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			if (isset($this->dataRecord["server_id"])){
				$rec = $app->db->queryOneRecord("SELECT server_id from web_domain WHERE domain_id = ".$this->id);
				if($rec['server_id'] != $this->dataRecord["server_id"]) {
					//* Add a error message and switch back to old server
					$app->tform->errorMessage .= $app->lng('The Server can not be changed.');
					$this->dataRecord["server_id"] = $rec['server_id'];
				}
				unset($rec);
			}
		//* If the user is neither admin nor reseller
		} else {
			//* We do not allow users to change a domain which has been created by the admin
			$rec = $app->db->queryOneRecord("SELECT domain from web_domain WHERE domain_id = ".$this->id);
			if(isset($this->dataRecord["domain"]) && $rec['domain'] != $this->dataRecord["domain"] && $app->tform->checkPerm($this->id,'u')) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Domain can not be changed. Please ask your Administrator if you want to change the domain name.');
				$this->dataRecord["domain"] = $rec['domain'];
			}
			unset($rec);
		}
		
		//* Check that all fields for the SSL cert creation are filled
		if(isset($this->dataRecord['ssl_action']) && $this->dataRecord['ssl_action'] == 'create') {
			if($this->dataRecord['ssl_state'] == '') $app->tform->errorMessage .= $app->tform->lng('error_ssl_state_empty').'<br />';
			if($this->dataRecord['ssl_locality'] == '') $app->tform->errorMessage .= $app->tform->lng('error_ssl_locality_empty').'<br />';
			if($this->dataRecord['ssl_organisation'] == '') $app->tform->errorMessage .= $app->tform->lng('error_ssl_organisation_empty').'<br />';
			if($this->dataRecord['ssl_organisation_unit'] == '') $app->tform->errorMessage .= $app->tform->lng('error_ssl_organisation_unit_empty').'<br />';
			if($this->dataRecord['ssl_country'] == '') $app->tform->errorMessage .= $app->tform->lng('error_ssl_country_empty').'<br />';
		}
		
	}
	
	function onAfterUpdate() {
		global $app, $conf;
		
		// make sure that the record belongs to the clinet group and not the admin group when a admin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE domain_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE domain_id = ".$this->id);
		}
		
		// Get configuration for the web system
		$app->uses("getconf");
		$web_rec = $app->tform->getDataRecord($this->id);
		$web_config = $app->getconf->get_server_config(intval($web_rec["server_id"]),'web');
		$document_root = str_replace("[website_id]",$this->id,$web_config["website_path"]);
		
		// get the ID of the client
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = $client_group_id");
			$client_id = intval($client["client_id"]);
		} else {
			//$client_id = intval(@$web_rec["client_group_id"]);
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ".intval(@$this->dataRecord["client_group_id"]));
			$client_id = intval($client["client_id"]);
		}
		
		if(($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) &&  isset($this->dataRecord["client_group_id"]) && $this->dataRecord["client_group_id"] != $this->oldDataRecord["client_group_id"]) {
			// Set the values for document_root, system_user and system_group
			$system_user = 'web'.$this->id;
			$system_group = 'client'.$client_id;
			$document_root = str_replace("[client_id]",$client_id,$document_root);
		
			$sql = "UPDATE web_domain SET system_user = '$system_user', system_group = '$system_group', document_root = '$document_root' WHERE domain_id = ".$this->id;
			//$sql = "UPDATE web_domain SET system_user = '$system_user', system_group = '$system_group' WHERE domain_id = ".$this->id;
			$app->db->query($sql);
		}
		
		//* If the domain name has been changed, we will have to change all subdomains
		if($this->dataRecord["domain"] != '' && $this->oldDataRecord["domain"] != '' && $this->dataRecord["domain"] != $this->oldDataRecord["domain"]) {
			$records = $app->db->queryAllRecords("SELECT domain_id,domain FROM web_domain WHERE type = 'subdomain' AND domain LIKE '%.".$app->db->quote($this->oldDataRecord["domain"])."'");
			foreach($records as $rec) {
				$subdomain = $app->db->quote(str_replace($this->oldDataRecord["domain"],$this->dataRecord["domain"],$rec['domain']));
				$app->db->datalogUpdate('web_domain', "domain = '".$subdomain."'", 'domain_id', $rec['domain_id']);
			}
			unset($records);
			unset($rec);
			unset($subdomain);
		}
		
	}
	
	function onAfterDelete() {
		global $app, $conf;
		
		// Delete the sub and alias domains
		$child_domains = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE parent_domain_id = ".$this->id);
		foreach($child_domains as $d) {
			// Saving record to datalog when db_history enabled
            if($app->tform->formDef["db_history"] == 'yes') {
				$app->tform->datalogSave('DELETE',$d["domain_id"],$d,array());
            }

            $app->db->query("DELETE FROM web_domain WHERE domain_id = ".$d["domain_id"]." LIMIT 0,1");
		}
		unset($child_domains);
		unset($d);
		
	}
	
}

$page = new page_action;
$page->onLoad();

?>