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

	//* Returna a "3/2/1" path hash from a numeric id '123'
	function id_hash($id,$levels) {
		$hash = "" . $id % 10 ;
		$id /= 10 ;
		$levels -- ;
		while ( $levels > 0 ) {
			$hash .= "/" . $id % 10 ;
			$id /= 10 ;
			$levels-- ;
		}
		return $hash;
	}
	
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
			
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.default_webserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			$app->tpl->setVar("server_id_value", $client['default_webserver']);
		}

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;
		
		$app->uses('ini_parser,getconf');

		//* Client: If the logged in user is not admin and has no sub clients (no reseller)
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_web_domain, default_webserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_webserver]");
			$app->tpl->setVar("server_id","<option value='$client[default_webserver]'>$tmp[server_name]</option>");
			unset($tmp);

			//* Fill the IPv4 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".$client['default_webserver']." AND ip_type = 'IPv4' AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
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
			
			//* Fill the IPv6 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".$client['default_webserver']." AND ip_type = 'IPv6' AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = "<option value=''></option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ipv6_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ipv6_address",$ip_select);
			unset($tmp);
			unset($ips);
			
			//PHP Version Selection (FastCGI)
			$server_type = 'apache';
			$web_config = $app->getconf->get_server_config($client['default_webserver'], 'web');
			if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
			if($server_type == 'nginx'){
				$sql = "SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ".$client['default_webserver']." AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			} else {
				$sql = "SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ".$client['default_webserver']." AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			}
			$php_records = $app->db->queryAllRecords($sql);
			$php_select = "<option value=''>Default</option>";
			if(is_array($php_records) && !empty($php_records)) {
				foreach( $php_records as $php_record) {
					if($server_type == 'nginx'){
						$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					} else {
						$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					}
					$selected = ($php_version == $this->dataRecord["fastcgi_php_version"])?'SELECTED':'';
					$php_select .= "<option value='$php_version' $selected>".$php_record['name']."</option>\r\n";
				}
			}
			$app->tpl->setVar("fastcgi_php_version",$php_select);
			unset($php_records);

			//* Reseller: If the logged in user is not admin and has sub clients (is a reseller)
		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, limit_web_domain, default_webserver, client.contact_name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = $client[default_webserver]");
			$app->tpl->setVar("server_id","<option value='$client[default_webserver]'>$tmp[server_name]</option>");
			unset($tmp);

			// Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$client['client_id']." ORDER BY name";
			$records = $app->db->queryAllRecords($sql);
			$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$client['client_id']);
			$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contact_name'].'</option>';
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($records)) {
				foreach( $records as $rec) {
					$selected = @(is_array($this->dataRecord) && ($rec["groupid"] == $this->dataRecord['client_group_id'] || $rec["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					$client_select .= "<option value='$rec[groupid]' $selected>$rec[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);

			//* Fill the IPv4 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".$client['default_webserver']." AND ip_type = 'IPv4' AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
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
			
			//* Fill the IPv6 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".$client['default_webserver']." AND ip_type = 'IPv6' AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = "<option value=''></option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ipv6_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ipv6_address",$ip_select);
			unset($tmp);
			unset($ips);
			
			//PHP Version Selection (FastCGI)
			$server_type = 'apache';
			$web_config = $app->getconf->get_server_config($client['default_webserver'], 'web');
			if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
			if($server_type == 'nginx'){
				$sql = "SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ".$client['default_webserver']." AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			} else {
				$sql = "SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ".$client['default_webserver']." AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			}
			$php_records = $app->db->queryAllRecords($sql);
			$php_select = "<option value=''>Default</option>";
			if(is_array($php_records) && !empty($php_records)) {
				foreach( $php_records as $php_record) {
					if($server_type == 'nginx'){
						$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					} else {
						$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					}
					$selected = ($php_version == $this->dataRecord["fastcgi_php_version"])?'SELECTED':'';
					$php_select .= "<option value='$php_version' $selected>".$php_record['name']."</option>\r\n";
				}
			}
			$app->tpl->setVar("fastcgi_php_version",$php_select);
			unset($php_records);

			//* Admin: If the logged in user is admin
		} else {

			// The user is admin, so we fill in all IP addresses of the server
			if($this->id > 0) {
				if(!isset($this->dataRecord["server_id"])){
					$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ".intval($this->id));
					$this->dataRecord["server_id"] = $tmp["server_id"];
					unset($tmp);
				}
				$server_id = @$this->dataRecord["server_id"];
			} else {
				// Get the first server ID
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM server WHERE web_server = 1 ORDER BY server_name LIMIT 0,1");
				$server_id = $tmp['server_id'];
			}
		
			//* Fill the IPv4 select field
			$sql = "SELECT ip_address FROM server_ip WHERE ip_type = 'IPv4' AND server_id = $server_id";
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
			
			//* Fill the IPv6 select field
			$sql = "SELECT ip_address FROM server_ip WHERE ip_type = 'IPv6' AND server_id = $server_id";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = "<option value=''></option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ipv6_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ipv6_address",$ip_select);
			unset($tmp);
			unset($ips);
			
			//PHP Version Selection (FastCGI)
			$server_type = 'apache';
			$web_config = $app->getconf->get_server_config($server_id, 'web');
			if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
			if($server_type == 'nginx'){
				$sql = "SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = $server_id";
			} else {
				$sql = "SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = $server_id";
			}
			$php_records = $app->db->queryAllRecords($sql);
			$php_select = "<option value=''>Default</option>";
			if(is_array($php_records) && !empty($php_records)) {
				foreach( $php_records as $php_record) {
					if($server_type == 'nginx'){
						$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					} else {
						$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					}
					$selected = ($php_version == $this->dataRecord["fastcgi_php_version"])?'SELECTED':'';
					$php_select .= "<option value='$php_version' $selected>".$php_record['name']."</option>\r\n";
				}
			}
			$app->tpl->setVar("fastcgi_php_version",$php_select);
			unset($php_records);

			// Fill the client select field
			$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0 ORDER BY name";
			$clients = $app->db->queryAllRecords($sql);
			$client_select = "<option value='0'></option>";
			//$tmp_data_record = $app->tform->getDataRecord($this->id);
			if(is_array($clients)) {
				foreach( $clients as $client) {
					//$selected = @($client["groupid"] == $tmp_data_record["sys_groupid"])?'SELECTED':'';
					$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
					$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
				}
			}
			$app->tpl->setVar("client_group_id",$client_select);

		}

		$ssl_domain_select = '';
		$tmp = $app->db->queryOneRecord("SELECT domain FROM web_domain WHERE domain_id = ".$this->id);
		$ssl_domains = array($tmp["domain"],'www.'.$tmp["domain"]);
		if(is_array($ssl_domains)) {
			foreach( $ssl_domains as $ssl_domain) {
				$selected = ($ssl_domain == $this->dataRecord['ssl_domain'])?'SELECTED':'';
				$ssl_domain_select .= "<option value='$ssl_domain' $selected>$ssl_domain</option>\r\n";
			}
		}
		$app->tpl->setVar("ssl_domain",$ssl_domain_select);
		unset($ssl_domain_select);
		unset($ssl_domains);
		unset($ssl_domain);

		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"]);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
		}

		$tmp_txt = ($this->dataRecord['traffic_quota_lock'] == 'y')?'<b>('.$app->tform->lng('traffic_quota_exceeded_txt').')</b>':'';
		$app->tpl->setVar("traffic_quota_exceeded_txt", $tmp_txt);

		/*
		 * Now we have to check, if we should use the domain-module to select the domain
		 * or not
		 */
		$settings = $app->getconf->get_global_config('domains');
		if ($settings['use_domain_module'] == 'y') {
			/*
			 * The domain-module is in use.
			*/
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			/*
			 * The admin can select ALL domains, the user only the domains assigned to him
			 */
			$sql = "SELECT domain FROM domain ";
			if ($_SESSION["s"]["user"]["typ"] != 'admin') {
				$sql .= "WHERE sys_groupid =" . $client_group_id;
			}
			$sql .= " ORDER BY domain";
			$domains = $app->db->queryAllRecords($sql);
			$domain_select = '';
			if(is_array($domains) && sizeof($domains) > 0) {
				/* We have domains in the list, so create the drop-down-list */
				foreach( $domains as $domain) {
					$domain_select .= "<option value=" . $domain['domain'] ;
					if ($domain['domain'] == $this->dataRecord["domain"]) {
						$domain_select .= " selected";
					}
					$domain_select .= ">" . $domain['domain'] . "</option>\r\n";
				}
			}
			else {
				/*
				 * We have no domains in the domain-list. This means, we can not add ANY new domain.
				 * To avoid, that the variable "domain_option" is empty and so the user can
				 * free enter a domain, we have to create a empty option!
				*/
				$domain_select .= "<option value=''></option>\r\n";
			}
			$app->tpl->setVar("domain_option",$domain_select);
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
			$client = $app->db->queryOneRecord("SELECT limit_traffic_quota, limit_web_domain, default_webserver, parent_client_id, limit_web_quota FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			//* Check the website quota of the client
			if(isset($_POST["hd_quota"]) && $client["limit_web_quota"] >= 0) {
				$tmp = $app->db->queryOneRecord("SELECT sum(hd_quota) as webquota FROM web_domain WHERE domain_id != ".intval($this->id)." AND ".$app->tform->getAuthSQL('u'));
				$webquota = $tmp["webquota"];
				$new_web_quota = intval($this->dataRecord["hd_quota"]);
				if(($webquota + $new_web_quota > $client["limit_web_quota"]) || ($new_web_quota < 0 && $client["limit_web_quota"] >= 0)) {
					$max_free_quota = floor($client["limit_web_quota"] - $webquota);
					if($max_free_quota < 0) $max_free_quota = 0;
					$app->tform->errorMessage .= $app->tform->lng("limit_web_quota_free_txt").": ".$max_free_quota." MB<br>";
					// Set the quota field to the max free space
					$this->dataRecord["hd_quota"] = $max_free_quota;
				}
				unset($tmp);
				unset($tmp_quota);
			}

			//* Check the traffic quota of the client
			if(isset($_POST["traffic_quota"]) && $client["limit_traffic_quota"] > 0) {
				$tmp = $app->db->queryOneRecord("SELECT sum(traffic_quota) as trafficquota FROM web_domain WHERE domain_id != ".intval($this->id)." AND ".$app->tform->getAuthSQL('u'));
				$trafficquota = $tmp["trafficquota"];
				$new_traffic_quota = intval($this->dataRecord["traffic_quota"]);
				if(($trafficquota + $new_traffic_quota > $client["limit_traffic_quota"]) || ($new_traffic_quota < 0 && $client["limit_traffic_quota"] >= 0)) {
					$max_free_quota = floor($client["limit_traffic_quota"] - $trafficquota);
					if($max_free_quota < 0) $max_free_quota = 0;
					$app->tform->errorMessage .= $app->tform->lng("limit_traffic_quota_free_txt").": ".$max_free_quota." MB<br>";
					// Set the quota field to the max free space
					$this->dataRecord["traffic_quota"] = $max_free_quota;
				}
				unset($tmp);
				unset($tmp_quota);
			}
			
			if($client['parent_client_id'] > 0) {
				// Get the limits of the reseller
				$reseller = $app->db->queryOneRecord("SELECT limit_traffic_quota, limit_web_domain, default_webserver, limit_web_quota FROM client WHERE client_id = ".$client['parent_client_id']);

				//* Check the website quota of the client
				if(isset($_POST["hd_quota"]) && $reseller["limit_web_quota"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT sum(hd_quota) as webquota FROM web_domain WHERE domain_id != ".intval($this->id)." AND ".$app->tform->getAuthSQL('u'));
					$webquota = $tmp["webquota"];
					$new_web_quota = intval($this->dataRecord["hd_quota"]);
					if(($webquota + $new_web_quota > $reseller["limit_web_quota"]) || ($new_web_quota < 0 && $reseller["limit_web_quota"] >= 0)) {
						$max_free_quota = floor($reseller["limit_web_quota"] - $webquota);
						if($max_free_quota < 0) $max_free_quota = 0;
						$app->tform->errorMessage .= $app->tform->lng("limit_web_quota_free_txt").": ".$max_free_quota." MB<br>";
						// Set the quota field to the max free space
						$this->dataRecord["hd_quota"] = $max_free_quota;
					}
					unset($tmp);
					unset($tmp_quota);
				}

				//* Check the traffic quota of the client
				if(isset($_POST["traffic_quota"]) && $reseller["limit_traffic_quota"] > 0) {
					$tmp = $app->db->queryOneRecord("SELECT sum(traffic_quota) as trafficquota FROM web_domain WHERE domain_id != ".intval($this->id)." AND ".$app->tform->getAuthSQL('u'));
					$trafficquota = $tmp["trafficquota"];
					$new_traffic_quota = intval($this->dataRecord["traffic_quota"]);
					if(($trafficquota + $new_traffic_quota > $reseller["limit_traffic_quota"]) || ($new_traffic_quota < 0 && $reseller["limit_traffic_quota"] >= 0)) {
						$max_free_quota = floor($reseller["limit_traffic_quota"] - $trafficquota);
						if($max_free_quota < 0) $max_free_quota = 0;
						$app->tform->errorMessage .= $app->tform->lng("limit_traffic_quota_free_txt").": ".$max_free_quota." MB<br>";
						// Set the quota field to the max free space
						$this->dataRecord["traffic_quota"] = $max_free_quota;
					}
					unset($tmp);
					unset($tmp_quota);
				}
			}

			// When the record is updated
			if($this->id > 0) {
				// restore the server ID if the user is not admin and record is edited
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ".intval($this->id));
				$this->dataRecord["server_id"] = $tmp["server_id"];
				unset($tmp);
				// When the record is inserted
			} else {
				//* set the server ID to the default webserver of the client
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
		
		//* make sure that the email domain is lowercase
		if(isset($this->dataRecord["domain"])) $this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);
		
		//* get the server config for this server
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config(intval(isset($this->dataRecord["server_id"]) ? $this->dataRecord["server_id"] : 0),'web');
		//* Check for duplicate ssl certs per IP if SNI is disabled
		if(isset($this->dataRecord['ssl']) && $this->dataRecord['ssl'] == 'y' && $web_config['enable_sni'] != 'y') {
			$sql = "SELECT count(domain_id) as number FROM web_domain WHERE `ssl` = 'y' AND ip_address = '".$app->db->quote($this->dataRecord['ip_address'])."' and domain_id != ".$this->id;
			$tmp = $app->db->queryOneRecord($sql);
			if($tmp['number'] > 0) $app->tform->errorMessage .= $app->tform->lng("error_no_sni_txt");
		}
		
		// Check if pm.max_children >= pm.max_spare_servers >= pm.start_servers >= pm.min_spare_servers > 0
		if(isset($this->dataRecord['pm_max_children'])) {
			if(intval($this->dataRecord['pm_max_children']) >= intval($this->dataRecord['pm_max_spare_servers']) && intval($this->dataRecord['pm_max_spare_servers']) >= intval($this->dataRecord['pm_start_servers']) && intval($this->dataRecord['pm_start_servers']) >= intval($this->dataRecord['pm_min_spare_servers']) && intval($this->dataRecord['pm_min_spare_servers']) > 0){
		
			} else {
				$app->tform->errorMessage .= $app->tform->lng("error_php_fpm_pm_settings_txt").'<br>';
			}
		}

		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app, $conf;

		// make sure that the record belongs to the clinet group and not the admin group when admin inserts it
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
		$document_root = str_replace("[website_idhash_1]",$this->id_hash($page_form->id,1),$document_root);
		$document_root = str_replace("[website_idhash_2]",$this->id_hash($page_form->id,1),$document_root);
		$document_root = str_replace("[website_idhash_3]",$this->id_hash($page_form->id,1),$document_root);
		$document_root = str_replace("[website_idhash_4]",$this->id_hash($page_form->id,1),$document_root);

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
		$system_user = $app->db->quote('web'.$this->id);
		$system_group = $app->db->quote('client'.$client_id);
		$document_root = str_replace("[client_id]",$client_id,$document_root);
		$document_root = str_replace("[client_idhash_1]",$this->id_hash($client_id,1),$document_root);
		$document_root = str_replace("[client_idhash_2]",$this->id_hash($client_id,2),$document_root);
		$document_root = str_replace("[client_idhash_3]",$this->id_hash($client_id,3),$document_root);
		$document_root = str_replace("[client_idhash_4]",$this->id_hash($client_id,4),$document_root);
		$document_root = $app->db->quote($document_root);
		$php_open_basedir = str_replace("[website_path]",$document_root,$web_config["php_open_basedir"]);
		$php_open_basedir = $app->db->quote(str_replace("[website_domain]",$web_rec['domain'],$php_open_basedir));
		$htaccess_allow_override = $app->db->quote($web_config["htaccess_allow_override"]);

		$sql = "UPDATE web_domain SET system_user = '$system_user', system_group = '$system_group', document_root = '$document_root', allow_override = '$htaccess_allow_override', php_open_basedir = '$php_open_basedir'  WHERE domain_id = ".$this->id;
		$app->db->query($sql);
	}

	function onBeforeUpdate () {
		global $app, $conf;

		//* Check if the server has been changed
		// We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
		if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			if (isset($this->dataRecord["server_id"])) {
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
		
		if(isset($this->dataRecord['ssl_action']) && $this->dataRecord['ssl_action'] == 'save') {
			if(trim($this->dataRecord['ssl_cert']) == '') $app->tform->errorMessage .= $app->tform->lng('error_ssl_cert_empty').'<br />';
		}

	}

	function onAfterUpdate() {
		global $app, $conf;

		// make sure that the record belongs to the client group and not the admin group when a admin inserts it
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
		$page_formid = isset($page_form->id) ? $page_form->id : '';
		$document_root = str_replace("[website_idhash_1]",$this->id_hash($page_formid,1),$document_root);
		$document_root = str_replace("[website_idhash_2]",$this->id_hash($page_formid,1),$document_root);
		$document_root = str_replace("[website_idhash_3]",$this->id_hash($page_formid,1),$document_root);
		$document_root = str_replace("[website_idhash_4]",$this->id_hash($page_formid,1),$document_root);

		// get the ID of the client
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = $client_group_id");
			$client_id = intval($client["client_id"]);
		} elseif (isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $this->dataRecord["client_group_id"];
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ".intval(@$this->dataRecord["client_group_id"]));
			$client_id = intval($client["client_id"]);
		} else {
			$client_group_id = $web_rec['sys_groupid'];
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ".intval($client_group_id));
			$client_id = intval($client["client_id"]);
		}

		if(($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) &&  isset($this->dataRecord["client_group_id"]) && $this->dataRecord["client_group_id"] != $this->oldDataRecord["sys_groupid"]) {
			// Set the values for document_root, system_user and system_group
			$system_user = $app->db->quote('web'.$this->id);
			$system_group = $app->db->quote('client'.$client_id);
			$document_root = str_replace("[client_id]",$client_id,$document_root);
			$document_root = str_replace("[client_idhash_1]",$this->id_hash($client_id,1),$document_root);
			$document_root = str_replace("[client_idhash_2]",$this->id_hash($client_id,2),$document_root);
			$document_root = str_replace("[client_idhash_3]",$this->id_hash($client_id,3),$document_root);
			$document_root = str_replace("[client_idhash_4]",$this->id_hash($client_id,4),$document_root);
			$document_root = $app->db->quote($document_root);

			$sql = "UPDATE web_domain SET system_user = '$system_user', system_group = '$system_group', document_root = '$document_root' WHERE domain_id = ".$this->id;
			//$sql = "UPDATE web_domain SET system_user = '$system_user', system_group = '$system_group' WHERE domain_id = ".$this->id;
			$app->db->query($sql);

			// Update the FTP user(s) too
			$records = $app->db->queryAllRecords("SELECT ftp_user_id FROM ftp_user WHERE parent_domain_id = ".$this->id);
			foreach($records as $rec) {
				$app->db->datalogUpdate('ftp_user', "sys_userid = '".$web_rec['sys_userid']."', sys_groupid = '".$web_rec['sys_groupid']."', uid = '$system_user', gid = '$system_group', dir = '$document_root'", 'ftp_user_id', $rec['ftp_user_id']);
			}
			unset($records);
			unset($rec);

			// Update the Shell user(s) too
			$records = $app->db->queryAllRecords("SELECT shell_user_id FROM shell_user WHERE parent_domain_id = ".$this->id);
			foreach($records as $rec) {
				$app->db->datalogUpdate('shell_user', "sys_userid = '".$web_rec['sys_userid']."', sys_groupid = '".$web_rec['sys_groupid']."', puser = '$system_user', pgroup = '$system_group', dir = '$document_root'", 'shell_user_id', $rec['shell_user_id']);
			}
			unset($records);
			unset($rec);
			
			//* Update all subdomains and alias domains
			$records = $app->db->queryAllRecords("SELECT domain_id FROM web_domain WHERE parent_domain_id = ".$this->id);
			foreach($records as $rec) {
				$app->db->datalogUpdate('web_domain', "sys_userid = '".$web_rec['sys_userid']."', sys_groupid = '".$web_rec['sys_groupid']."'", 'domain_id', $rec['domain_id']);
			}
			unset($records);
			unset($rec);
			
			//* Update all databases
			$records = $app->db->queryAllRecords("SELECT database_id FROM web_database WHERE parent_domain_id = ".$this->id);
			foreach($records as $rec) {
				$app->db->datalogUpdate('web_database', "sys_userid = '".$web_rec['sys_userid']."', sys_groupid = '".$web_rec['sys_groupid']."'", 'database_id', $rec['database_id']);
			}
			unset($records);
			unset($rec);

		}

		//* If the domain name has been changed, we will have to change all subdomains
		if(!empty($this->dataRecord["domain"]) && !empty($this->oldDataRecord["domain"]) && $this->dataRecord["domain"] != $this->oldDataRecord["domain"]) {
			$records = $app->db->queryAllRecords("SELECT domain_id,domain FROM web_domain WHERE type = 'subdomain' AND domain LIKE '%.".$app->db->quote($this->oldDataRecord["domain"])."'");
			foreach($records as $rec) {
				$subdomain = $app->db->quote(str_replace($this->oldDataRecord["domain"],$this->dataRecord["domain"],$rec['domain']));
				$app->db->datalogUpdate('web_domain', "domain = '".$subdomain."'", 'domain_id', $rec['domain_id']);
			}
			unset($records);
			unset($rec);
			unset($subdomain);
		}

		//* Set allow_override if empty
		if($web_rec['allow_override'] == '') {
			$sql = "UPDATE web_domain SET allow_override = '".$app->db->quote($web_config["htaccess_allow_override"])."' WHERE domain_id = ".$this->id;
			$app->db->query($sql);
		}
		
		//* Set php_open_basedir if empty or domain or client has been changed
		if(empty($web_rec['php_open_basedir']) ||
		(!empty($this->dataRecord["domain"]) && !empty($this->oldDataRecord["domain"]) && $this->dataRecord["domain"] != $this->oldDataRecord["domain"]) ||
		(isset($this->dataRecord["client_group_id"]) && $this->dataRecord["client_group_id"] != $this->oldDataRecord["sys_groupid"])) {
			$document_root = $app->db->quote(str_replace("[client_id]",$client_id,$document_root));
			$php_open_basedir = str_replace("[website_path]",$document_root,$web_config["php_open_basedir"]);
			$php_open_basedir = $app->db->quote(str_replace("[website_domain]",$web_rec['domain'],$php_open_basedir));
			$sql = "UPDATE web_domain SET php_open_basedir = '$php_open_basedir' WHERE domain_id = ".$this->id;
			$app->db->query($sql);
		}
		
		//* Change database backup options when web backup options have been changed
		if(isset($this->dataRecord['backup_interval']) && ($this->dataRecord['backup_interval'] != $this->oldDataRecord['backup_interval'] || $this->dataRecord['backup_copies'] != $this->oldDataRecord['backup_copies'])) {
			//* Update all databases
			$backup_interval = $this->dataRecord['backup_interval'];
			$backup_copies = $this->dataRecord['backup_copies'];
			$records = $app->db->queryAllRecords("SELECT database_id FROM web_database WHERE parent_domain_id = ".$this->id);
			foreach($records as $rec) {
				$app->db->datalogUpdate('web_database', "backup_interval = '$backup_interval', backup_copies = '$backup_copies'", 'database_id', $rec['database_id']);
			}
			unset($records);
			unset($rec);
			unset($backup_copies);
			unset($backup_interval);
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