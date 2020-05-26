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

$tform_def_file = "form/web_vhost_domain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions,tools_sites');
$app->load('tform_actions');

class page_action extends tform_actions {
	var $_vhostdomain_type = 'domain';
	var $_letsencrypt_on_insert = false;

	//* Returna a "3/2/1" path hash from a numeric id '123'
	function id_hash($id, $levels) {
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

	function onLoad() {
		$show_type = 'domain';
		if(isset($_GET['type']) && $_GET['type'] == 'subdomain') {
			$show_type = 'subdomain';
		} elseif(isset($_GET['type']) && $_GET['type'] == 'aliasdomain') {
			$show_type = 'aliasdomain';
		} elseif(!isset($_GET['type']) && isset($_SESSION['s']['var']['vhostdomain_type']) && $_SESSION['s']['var']['vhostdomain_type'] == 'subdomain') {
			$show_type = 'subdomain';
		} elseif(!isset($_GET['type']) && isset($_SESSION['s']['var']['vhostdomain_type']) && $_SESSION['s']['var']['vhostdomain_type'] == 'aliasdomain') {
			$show_type = 'aliasdomain';
		}

		$_SESSION['s']['var']['vhostdomain_type'] = $show_type;
		$this->_vhostdomain_type = $show_type;
		
		parent::onLoad();
	}

	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if($this->_vhostdomain_type == 'domain') {
				if(!$app->tform->checkClientLimit('limit_web_domain', "type = 'vhost'")) {
					$app->error($app->tform->wordbook["limit_web_domain_txt"]);
				}
				if(!$app->tform->checkResellerLimit('limit_web_domain', "type = 'vhost'")) {
					$app->error('Reseller: '.$app->tform->wordbook["limit_web_domain_txt"]);
				}
			} elseif($this->_vhostdomain_type == 'subdomain') {
				if(!$app->tform->checkClientLimit('limit_web_subdomain', "(type = 'subdomain' OR type = 'vhostsubdomain')")) {
					$app->error($app->tform->wordbook["limit_web_subdomain_txt"]);
				}
				if(!$app->tform->checkResellerLimit('limit_web_subdomain', "(type = 'subdomain' OR type = 'vhostsubdomain')")) {
					$app->error('Reseller: '.$app->tform->wordbook["limit_web_subdomain_txt"]);
				}
			} elseif($this->_vhostdomain_type == 'aliasdomain') {
				if(!$app->tform->checkClientLimit('limit_web_aliasdomain', "(type = 'alias' OR type = 'vhostalias')")) {
					$app->error($app->tform->wordbook["limit_web_aliasdomain_txt"]);
				}
				if(!$app->tform->checkResellerLimit('limit_web_aliasdomain', "(type = 'alias' OR type = 'vhostalias')")) {
					$app->error('Reseller: '.$app->tform->wordbook["limit_web_aliasdomain_txt"]);
				}
			}
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.web_servers FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			$web_servers = explode(',', $client['web_servers']);
			$server_id = $web_servers[0];
			$app->tpl->setVar("server_id_value", $server_id, true);
			unset($web_servers);
		} else {
			$settings = $app->getconf->get_global_config('sites');
			$server_id = intval($settings['default_webserver']);
			$app->tform->formDef['tabs']['domain']['fields']['server_id']['default'] = $server_id;
		}
		if(!$server_id){
			$default_web_server = $app->db->queryOneRecord("SELECT server_id FROM server WHERE web_server = ? ORDER BY server_id LIMIT 0,1", 1);
			$server_id = $default_web_server['server_id'];
		}
		$web_config = $app->getconf->get_server_config($server_id, 'web');
		$app->tform->formDef['tabs']['domain']['fields']['php']['default'] = $web_config['php_handler'];
		$app->tform->formDef['tabs']['domain']['readonly'] = false;

		$app->tpl->setVar('vhostdomain_type', $this->_vhostdomain_type, true);
		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;

		$app->uses('ini_parser,getconf');
		$settings = $app->getconf->get_global_config('domains');

		$read_limits = array('limit_cgi', 'limit_ssi', 'limit_perl', 'limit_ruby', 'limit_python', 'force_suexec', 'limit_hterror', 'limit_wildcard', 'limit_ssl', 'limit_ssl_letsencrypt', 'limit_directive_snippets');

		if($this->_vhostdomain_type != 'domain') $parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ?", @$this->dataRecord["parent_domain_id"]);
		
		$is_admin = false;

		//* Client: If the logged in user is not admin and has no sub clients (no reseller)
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			if($this->_vhostdomain_type == 'domain') {
				$client = $app->db->queryOneRecord("SELECT client.limit_web_domain, client.web_servers, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			} elseif($this->_vhostdomain_type == 'subdomain') {
				$client = $app->db->queryOneRecord("SELECT client.limit_web_subdomain, client.web_servers, client.default_webserver, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			} elseif($this->_vhostdomain_type == 'aliasdomain') {
				$client = $app->db->queryOneRecord("SELECT client.limit_web_aliasdomain, client.web_servers, client.default_webserver, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			}

			$client['web_servers_ids'] = explode(',', $client['web_servers']);
			if($this->id > 0) {
				$client['web_servers_ids'][] = $this->dataRecord['server_id'];
				$client['web_servers_ids'] = array_unique($client['web_servers_ids']);
			}
			
			$only_one_server = count($client['web_servers_ids']) === 1;
			$app->tpl->setVar('only_one_server', $only_one_server);

			//* Get global web config
			foreach ($client['web_servers_ids'] as $web_server_id) {
				$web_config[$web_server_id] = $app->getconf->get_server_config($web_server_id, 'web');
			}

			$sql = "SELECT server_id, server_name FROM server WHERE server_id IN ?";
			$web_servers = $app->db->queryAllRecords($sql, $client['web_servers_ids']);

			$options_web_servers = "";

			foreach ($web_servers as $web_server) {
				$options_web_servers .= '<option value="'.$web_server['server_id'].'"'.($this->id > 0 && $this->dataRecord["server_id"] == $web_server['server_id'] ? ' selected="selected"' : '').'>'.$app->functions->htmlentities($web_server['server_name']).'</option>';
			}

			$app->tpl->setVar("server_id", $options_web_servers);
			unset($options_web_servers);

			if($this->id > 0) {
				if(!isset($this->dataRecord["server_id"])){
					$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ?", $this->id);
					$this->dataRecord["server_id"] = $tmp["server_id"];
					unset($tmp);
				}
				$server_id = intval(@$this->dataRecord["server_id"]);
			} else {
				$server_id = (isset($web_servers[0])) ? intval($web_servers[0]['server_id']) : 0;
			}
			
			if($app->functions->intval($this->dataRecord["server_id"]) > 0) {
				// check if server is in client's servers or add it.
				$chk_sid = explode(',', $client['web_servers']);
				if(in_array($this->dataRecord["server_id"], explode(',', $client['web_servers'])) == false) {
					if($client['web_servers'] != '') $client['web_servers'] .= ',';
					$client['web_servers'] .= $app->functions->intval($this->dataRecord["server_id"]);
				}
			}
			
			//* Fill the IPv4 select field with the IP addresses that are allowed for this client on the current server
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ? AND ip_type = 'IPv4' AND virtualhost = 'y' AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			$ips = $app->db->queryAllRecords($sql, $server_id);
			$ip_select = ($web_config[$server_id]['enable_ip_wildcard'] == 'y')?"<option value='*'>*</option>":"";
			//if(!in_array($this->dataRecord["ip_address"], $ips)) $ip_select .= "<option value='".$this->dataRecord["ip_address"]."' SELECTED>".$this->dataRecord["ip_address"]."</option>\r\n";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='" . $app->functions->htmlentities($ip['ip_address']) . "' $selected>" . $app->functions->htmlentities($ip['ip_address']) . "</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address", $ip_select);
			unset($tmp);
			unset($ips);

			//* Fill the IPv6 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ? AND ip_type = 'IPv6' AND virtualhost = 'y' AND (client_id = 0 OR client_id=?)";
			$ips = $app->db->queryAllRecords($sql, $server_id, $_SESSION['s']['user']['client_id']);
			//$ip_select = ($web_config[$server_id]['enable_ip_wildcard'] == 'y')?"<option value='*'>*</option>":"";
			//$ip_select = "";
			$ip_select = "<option value=''></option>";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ipv6_address"])?'SELECTED':'';
					$ip_select .= "<option value='" . $app->functions->htmlentities($ip['ip_address']) . "' $selected>" . $app->functions->htmlentities($ip['ip_address']) . "</option>\r\n";
				}
			}
			$app->tpl->setVar("ipv6_address", $ip_select);
			unset($tmp);
			unset($ips);

			//PHP Version Selection (FastCGI)
			$server_type = 'apache';
			if(!empty($web_config[$server_id]['server_type'])) $server_type = $web_config[$server_id]['server_type'];
			if($server_type == 'nginx' && $this->dataRecord['php'] == 'fast-cgi') $this->dataRecord['php'] = 'php-fpm';

			if($this->_vhostdomain_type == 'domain') {
				if($this->dataRecord['php'] == 'php-fpm' || ($this->dataRecord['php'] == 'hhvm' && $server_type == 'nginx')){
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ? AND (client_id = 0 OR client_id=?) AND active = 'y'", ($this->id > 0 ? $this->dataRecord['server_id'] : $client['default_webserver']), $_SESSION['s']['user']['client_id']);
				}
				if($this->dataRecord['php'] == 'fast-cgi'){
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ? AND (client_id = 0 OR client_id=?) AND active = 'y'", ($this->id > 0 ? $this->dataRecord['server_id'] : $client['default_webserver']), $_SESSION['s']['user']['client_id']);
				}
			} else {
				if($this->dataRecord['php'] == 'php-fpm' || ($this->dataRecord['php'] == 'hhvm' && $server_type == 'nginx')){
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ? AND (client_id = 0 OR client_id=?) AND active = 'y'", $parent_domain['server_id'], $_SESSION['s']['user']['client_id']);
				}
				if($this->dataRecord['php'] == 'fast-cgi'){
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ? AND (client_id = 0 OR client_id=?) AND active = 'y'", $parent_domain['server_id'], $_SESSION['s']['user']['client_id']);
				}
			}
			if (empty($web_config['php_default_hide']) || 'n' === $web_config['php_default_hide']) {
				$php_select = "<option value=''>".$app->functions->htmlentities($web_config['php_default_name'])."</option>";
			}
			if(is_array($php_records) && !empty($php_records)) {
				foreach( $php_records as $php_record) {
					if($this->dataRecord['php'] == 'php-fpm' || ($this->dataRecord['php'] == 'hhvm' && $server_type == 'nginx')){
						$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					} else {
						$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					}
					$selected = ($php_version == $this->dataRecord["fastcgi_php_version"])?'SELECTED':'';
					$php_select .= "<option value='" . $app->functions->htmlentities($php_version) . "' $selected>".$app->functions->htmlentities($php_record['name'])."</option>\r\n";
				}
			}
			$app->tpl->setVar("fastcgi_php_version", $php_select);
			unset($php_records);

			// add limits to template to be able to hide settings
			foreach($read_limits as $limit) $app->tpl->setVar($limit, $client[$limit]);

			//* Reseller: If the logged in user is not admin and has sub clients (is a reseller)
		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);

			if($this->_vhostdomain_type == 'domain') {
				$client = $app->db->queryOneRecord("SELECT client.client_id, client.limit_web_domain, client.web_servers, client.default_webserver, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
				$app->tpl->setVar('only_one_server', $only_one_server);
			} elseif($this->_vhostdomain_type == 'subdomain') {
				$client = $app->db->queryOneRecord("SELECT client.client_id, client.limit_web_subdomain, client.web_servers, client.default_webserver, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			} elseif($this->_vhostdomain_type == 'aliasdomain') {
				$client = $app->db->queryOneRecord("SELECT client.client_id, client.limit_web_aliasdomain, client.web_servers, client.default_webserver, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
			}
			$client = $app->functions->htmlentities($client);

			$client['web_servers_ids'] = explode(',', $client['web_servers']);
			$only_one_server = count($client['web_servers_ids']) === 1;

			//* Get global web config
			foreach ($client['web_servers_ids'] as $web_server_id) {
				$web_config[$web_server_id] = $app->getconf->get_server_config($web_server_id, 'web');
			}

			$sql = "SELECT server_id, server_name FROM server WHERE server_id IN ?";
			$web_servers = $app->db->queryAllRecords($sql, $client['web_servers_ids']);

			$options_web_servers = "";

			foreach ($web_servers as $web_server) {
				$options_web_servers .= '<option value="'.$web_server['server_id'].'"'.($this->id > 0 && $this->dataRecord["server_id"] == $web_server['server_id'] ? ' selected="selected"' : '').'>'.$app->functions->htmlentities($web_server['server_name']).'</option>';
			}

			$app->tpl->setVar("server_id", $options_web_servers);
			unset($options_web_servers);
			
			if($this->id > 0) {
				if(!isset($this->dataRecord["server_id"])){
					$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ?", $this->id);
					$this->dataRecord["server_id"] = $tmp["server_id"];
					unset($tmp);
				}
				$server_id = intval(@$this->dataRecord["server_id"]);
			} else {
				$server_id = (isset($web_servers[0])) ? intval($web_servers[0]['server_id']) : 0;
			}

			if ($settings['use_domain_module'] != 'y') {
				// Fill the client select field
				$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ? ORDER BY client.company_name, client.contact_name, sys_group.name";
				$records = $app->db->queryAllRecords($sql, $client['client_id']);
				$records = $app->functions->htmlentities($records);
				$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client['client_id']);
				$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contactname'].'</option>';
				//$tmp_data_record = $app->tform->getDataRecord($this->id);
				if(is_array($records)) {
					$selected_client_group_id = 0; // needed to get list of PHP versions
					foreach( $records as $rec) {
						if(is_array($this->dataRecord) && ($rec["groupid"] == $this->dataRecord['client_group_id'] || $rec["groupid"] == $this->dataRecord['sys_groupid']) && !$selected_client_group_id) $selected_client_group_id = $rec["groupid"];
						$selected = @(is_array($this->dataRecord) && ($rec["groupid"] == $this->dataRecord['client_group_id'] || $rec["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
						if($selected == 'SELECTED') $selected_client_group_id = $rec["groupid"];
						$client_select .= "<option value='$rec[groupid]' $selected>$rec[contactname]</option>\r\n";
					}
				}
				$app->tpl->setVar("client_group_id", $client_select);
			}

			if($app->functions->intval($this->dataRecord["server_id"]) > 0) {
				// check if server is in client's servers or add it.
				$chk_sid = explode(',', $client['web_servers']);
				if(in_array($this->dataRecord["server_id"], $chk_sid) == false) {
					if($client['web_servers'] != '') $client['web_servers'] .= ',';
					$client['web_servers'] .= $app->functions->intval($this->dataRecord["server_id"]);
				}
			}
			
			//* Fill the IPv4 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ? AND ip_type = 'IPv4' AND virtualhost = 'y' AND (client_id = 0 OR client_id=?)";
			$ips = $app->db->queryAllRecords($sql, $server_id, $_SESSION['s']['user']['client_id']);
			$ip_select = ($web_config[$server_id]['enable_ip_wildcard'] == 'y')?"<option value='*'>*</option>":"";
			//if(!in_array($this->dataRecord["ip_address"], $ips)) $ip_select .= "<option value='".$this->dataRecord["ip_address"]."' SELECTED>".$this->dataRecord["ip_address"]."</option>\r\n";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='" . $app->functions->htmlentities($ip['ip_address']) . "' $selected>" . $app->functions->htmlentities($ip['ip_address']) . "</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address", $ip_select);
			unset($tmp);
			unset($ips);

			//* Fill the IPv6 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ? AND ip_type = 'IPv6' AND virtualhost = 'y' AND (client_id = 0 OR client_id=?)";
			$ips = $app->db->queryAllRecords($sql, $server_id, $_SESSION['s']['user']['client_id']);
			$ip_select = "<option value=''></option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ipv6_address"])?'SELECTED':'';
					$ip_select .= "<option value='" . $app->functions->htmlentities($ip['ip_address']) . "' $selected>" . $app->functions->htmlentities($ip['ip_address']) . "</option>\r\n";
				}
			}
			$app->tpl->setVar("ipv6_address", $ip_select);
			unset($tmp);
			unset($ips);

			//PHP Version Selection (FastCGI)
			$server_type = 'apache';
			if(!empty($web_config[$server_id]['server_type'])) $server_type = $web_config[$server_id]['server_type'];
			if($server_type == 'nginx' && $this->dataRecord['php'] == 'fast-cgi') $this->dataRecord['php'] = 'php-fpm';
			$selected_client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE groupid = ?", $selected_client_group_id);
			$sql_where = " AND (client_id = 0 OR client_id = ?) AND active = 'y'";
			if($this->_vhostdomain_type == 'domain') {
				if($this->dataRecord['php'] == 'php-fpm' || ($this->dataRecord['php'] == 'hhvm' && $server_type == 'nginx')){
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ?".$sql_where, ($this->id > 0 ? $this->dataRecord['server_id'] : $client['default_webserver']), $selected_client['client_id']);
				}
				if($this->dataRecord['php'] == 'fast-cgi') {
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ?".$sql_where, ($this->id > 0 ? $this->dataRecord['server_id'] : $client['default_webserver']), $selected_client['client_id']);
				}
			} else {
				if($this->dataRecord['php'] == 'php-fpm' || ($this->dataRecord['php'] == 'hhvm' && $server_type == 'nginx')){
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ? AND (client_id = 0 OR client_id=?) AND active = 'y'", $parent_domain['server_id'], $_SESSION['s']['user']['client_id']);
				}
				if($this->dataRecord['php'] == 'fast-cgi') {
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ? AND (client_id = 0 OR client_id=?) AND active = 'y'", $parent_domain['server_id'], $_SESSION['s']['user']['client_id']);
				}
			}
			if (empty($web_config['php_default_hide']) || 'n' === $web_config['php_default_hide']) {
				$php_select = "<option value=''>".$app->functions->htmlentities($web_config['php_default_name'])."</option>";
			}
			if(is_array($php_records) && !empty($php_records)) {
				foreach( $php_records as $php_record) {
					if($this->dataRecord['php'] == 'php-fpm' || ($this->dataRecord['php'] == 'hhvm' && $server_type == 'nginx')){
						$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					} else {
						$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					}
					$selected = ($php_version == $this->dataRecord["fastcgi_php_version"])?'SELECTED':'';
					$php_select .= "<option value='" . $app->functions->htmlentities($php_version) . "' $selected>".$app->functions->htmlentities($php_record['name'])."</option>\r\n";
				}
			}
			$app->tpl->setVar("fastcgi_php_version", $php_select);
			unset($php_records);

			// add limits to template to be able to hide settings
			foreach($read_limits as $limit) $app->tpl->setVar($limit, $client[$limit]);

			$sites_config = $app->getconf->get_global_config('sites');
			if($sites_config['reseller_can_use_options']) {
				// Directive Snippets
				$php_directive_snippets_txt = '';
				$php_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'php' AND active = 'y' AND master_directive_snippets_id > 0 ORDER BY name");
				if(is_array($php_directive_snippets) && !empty($php_directive_snippets)){
					$php_directive_snippets_txt .= $app->tform->wordbook["select_master_directive_snippet_txt"].'<br>';
					foreach($php_directive_snippets as $php_directive_snippet){
						$php_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $php_directive_snippet['snippet'] . PHP_EOL;
						$php_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$php_directive_snippet['name'].']<pre class="addPlaceholderContent" style="display:none;">'.htmlentities($php_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
					$php_directive_snippets_txt .= '<br><br>';
				}
				
				$php_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'php' AND active = 'y' AND master_directive_snippets_id = 0 ORDER BY name");
				if(is_array($php_directive_snippets) && !empty($php_directive_snippets)){
					$php_directive_snippets_txt .= $app->tform->wordbook["select_directive_snippet_txt"].'<br>';
					foreach($php_directive_snippets as $php_directive_snippet){
						$php_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $php_directive_snippet['snippet'] . PHP_EOL;
						$php_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($php_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($php_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
				}
				if($php_directive_snippets_txt == '') $php_directive_snippets_txt = '------';
				$app->tpl->setVar("php_directive_snippets_txt", $php_directive_snippets_txt);

				if($server_type == 'apache'){
					$apache_directive_snippets_txt = '';
					$apache_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'apache' AND active = 'y' AND master_directive_snippets_id > 0 ORDER BY name");
					if(is_array($apache_directive_snippets) && !empty($apache_directive_snippets)){
						$apache_directive_snippets_txt .= $app->tform->wordbook["select_master_directive_snippet_txt"].'<br>';
						foreach($apache_directive_snippets as $apache_directive_snippet){
							$apache_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $apache_directive_snippet['snippet'] . PHP_EOL;
							$apache_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$apache_directive_snippet['name'].']<pre class="addPlaceholderContent" style="display:none;">'.htmlentities($apache_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						}
						$apache_directive_snippets_txt .= '<br><br>';
					}
					
					$apache_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'apache' AND active = 'y' AND master_directive_snippets_id = 0 ORDER BY name");
					if(is_array($apache_directive_snippets) && !empty($apache_directive_snippets)){
						$apache_directive_snippets_txt .= $app->tform->wordbook["select_directive_snippet_txt"].'<br>';
						foreach($apache_directive_snippets as $apache_directive_snippet){
							$apache_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $apache_directive_snippet['snippet'] . PHP_EOL;
							$apache_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($apache_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($apache_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						}
					}
					if($apache_directive_snippets_txt == '') $apache_directive_snippets_txt = '------';
					$app->tpl->setVar("apache_directive_snippets_txt", $apache_directive_snippets_txt);
				}

				if($server_type == 'nginx'){
					$nginx_directive_snippets_txt = '';
					$nginx_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'nginx' AND active = 'y' AND master_directive_snippets_id > 0 ORDER BY name");
					if(is_array($nginx_directive_snippets) && !empty($nginx_directive_snippets)){
						$nginx_directive_snippets_txt .= $app->tform->wordbook["select_master_directive_snippet_txt"].'<br>';
						foreach($nginx_directive_snippets as $nginx_directive_snippet){
							$nginx_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $nginx_directive_snippet['snippet'] . PHP_EOL;
							$nginx_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($nginx_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($nginx_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						}
						$nginx_directive_snippets_txt .= '<br><br>';
					}
					
					$nginx_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'nginx' AND active = 'y' AND master_directive_snippets_id = 0 ORDER BY name");
					if(is_array($nginx_directive_snippets) && !empty($nginx_directive_snippets)){
						$nginx_directive_snippets_txt .= $app->tform->wordbook["select_directive_snippet_txt"].'<br>';
						foreach($nginx_directive_snippets as $nginx_directive_snippet){
							$nginx_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $nginx_directive_snippet['snippet'] . PHP_EOL;
							$nginx_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($nginx_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($nginx_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
						}
					}
					if($nginx_directive_snippets_txt == '') $nginx_directive_snippets_txt = '------';
					$app->tpl->setVar("nginx_directive_snippets_txt", $nginx_directive_snippets_txt);
				}

				$proxy_directive_snippets_txt = '';
				$proxy_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'proxy' AND active = 'y' AND master_directive_snippets_id > 0 ORDER BY name");
				if(is_array($proxy_directive_snippets) && !empty($proxy_directive_snippets)){
					$proxy_directive_snippets_txt .= $app->tform->wordbook["select_master_directive_snippet_txt"].'<br>';
					foreach($proxy_directive_snippets as $proxy_directive_snippet){
						$proxy_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $proxy_directive_snippet['snippet'] . PHP_EOL;
						$proxy_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($proxy_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($proxy_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
					$proxy_directive_snippets_txt .= '<br><br>';
				}
				
				$proxy_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'proxy' AND active = 'y' AND master_directive_snippets_id = 0 ORDER BY name");
				if(is_array($proxy_directive_snippets) && !empty($proxy_directive_snippets)){
					$proxy_directive_snippets_txt .= $app->tform->wordbook["select_directive_snippet_txt"].'<br>';
					foreach($proxy_directive_snippets as $proxy_directive_snippet){
						$proxy_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $proxy_directive_snippet['snippet'] . PHP_EOL;
						$proxy_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($proxy_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($proxy_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
				}
				if($proxy_directive_snippets_txt == '') $proxy_directive_snippets_txt = '------';
				$app->tpl->setVar("proxy_directive_snippets_txt", $proxy_directive_snippets_txt);
			}

			//* Admin: If the logged in user is admin
		} else {
		
			$is_admin = true;

			if($this->_vhostdomain_type == 'domain') {
				// The user is admin, so we fill in all IP addresses of the server
				if($this->id > 0) {
					if(!isset($this->dataRecord["server_id"])){
						$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ?", $this->id);
						$this->dataRecord["server_id"] = $tmp["server_id"];
						unset($tmp);
					}
					$server_id = intval(@$this->dataRecord["server_id"]);
				} else {
					$settings = $app->getconf->get_global_config('sites');
					$server_id = intval($settings['default_webserver']);
					if (!$server_id) {
						// Get the first server ID
						$tmp = $app->db->queryOneRecord("SELECT server_id FROM server WHERE web_server = 1 ORDER BY server_name LIMIT 0,1");
						$server_id = intval($tmp['server_id']);
					}
				}

				//* get global web config
				$web_config = $app->getconf->get_server_config($server_id, 'web');
			} else {
				//* get global web config
				$web_config = $app->getconf->get_server_config($parent_domain['server_id'], 'web');
			}

			//* Fill the IPv4 select field
			$sql = "SELECT ip_address FROM server_ip WHERE ip_type = 'IPv4' AND virtualhost = 'y' AND server_id = ?";
			$ips = $app->db->queryAllRecords($sql, $server_id);
			$ip_select = ($web_config['enable_ip_wildcard'] == 'y')?"<option value='*'>*</option>":"";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='" . $app->functions->htmlentities($ip['ip_address']) . "' $selected>" . $app->functions->htmlentities($ip['ip_address']) . "</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address", $ip_select);
			unset($tmp);
			unset($ips);

			//* Fill the IPv6 select field
			$sql = "SELECT ip_address FROM server_ip WHERE ip_type = 'IPv6' AND virtualhost = 'y' AND server_id = ?";
			$ips = $app->db->queryAllRecords($sql, $server_id);
			$ip_select = "<option value=''></option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ipv6_address"])?'SELECTED':'';
					$ip_select .= "<option value='" . $app->functions->htmlentities($ip['ip_address']) . "' $selected>" . $app->functions->htmlentities($ip['ip_address']) . "</option>\r\n";
				}
			}
			$app->tpl->setVar("ipv6_address", $ip_select);
			unset($tmp);
			unset($ips);

			if ($settings['use_domain_module'] != 'y') {
				if(!isset($this->dataRecord["sys_groupid"])){
					$tmp = $app->db->queryOneRecord("SELECT sys_groupid FROM web_domain WHERE domain_id = ".$app->functions->intval($this->id));
					$this->dataRecord["sys_groupid"] = $tmp["sys_groupid"];
				}
				// Fill the client select field
				$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY client.company_name, client.contact_name, sys_group.name";
				$clients = $app->db->queryAllRecords($sql);
				$clients = $app->functions->htmlentities($clients);
				$client_select = "<option value='0'></option>";
				//$tmp_data_record = $app->tform->getDataRecord($this->id);
				if(is_array($clients)) {
					$selected_client_group_id = 0; // needed to get list of PHP versions
					foreach($clients as $client) {
						if(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']) && !$selected_client_group_id) $selected_client_group_id = $client["groupid"];
						//$selected = @($client["groupid"] == $tmp_data_record["sys_groupid"])?'SELECTED':'';
						$selected = @(is_array($this->dataRecord) && ($client["groupid"] == $this->dataRecord['client_group_id'] || $client["groupid"] == $this->dataRecord['sys_groupid']))?'SELECTED':'';
						if($selected == 'SELECTED') $selected_client_group_id = $client["groupid"];
						$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
					}
				}
				$app->tpl->setVar("client_group_id", $client_select);
			}

			//PHP Version Selection (FastCGI)
			$server_type = 'apache';
			if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
			if($server_type == 'nginx' && $this->dataRecord['php'] == 'fast-cgi') $this->dataRecord['php'] = 'php-fpm';
			$selected_client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE groupid = ?", $selected_client_group_id);
			$sql_where = " AND (client_id = 0 OR client_id = ?) AND active = 'y'";
			if($this->_vhostdomain_type == 'domain') {
				if($this->dataRecord['php'] == 'php-fpm' || ($this->dataRecord['php'] == 'hhvm' && $server_type == 'nginx')){
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ?".$sql_where, $server_id, $selected_client['client_id']);
				}
				if($this->dataRecord['php'] == 'fast-cgi') {
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ?".$sql_where, $server_id, $selected_client['client_id']);
				}
			} else {
				if($this->dataRecord['php'] == 'php-fpm' || ($this->dataRecord['php'] == 'hhvm' && $server_type == 'nginx')){
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ? AND active = 'y'", $parent_domain['server_id']);
				}
				if($this->dataRecord['php'] == 'fast-cgi') {
					$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ? AND active = 'y'", $parent_domain['server_id']);
				}
			}
			if (empty($web_config['php_default_hide']) || 'n' === $web_config['php_default_hide']) {
				$php_select = "<option value=''>".$app->functions->htmlentities($web_config['php_default_name'])."</option>";
			}
			if(is_array($php_records) && !empty($php_records)) {
				foreach( $php_records as $php_record) {
					if($this->dataRecord['php'] == 'php-fpm' || ($this->dataRecord['php'] == 'hhvm' && $server_type == 'nginx')){
						$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					} else {
						$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					}
					$selected = ($php_version == $this->dataRecord["fastcgi_php_version"])?'SELECTED':'';
					$php_select .= "<option value='" . $app->functions->htmlentities($php_version) . "' $selected>".$app->functions->htmlentities($php_record['name'])."</option>\r\n";
				}
			}
			$app->tpl->setVar("fastcgi_php_version", $php_select);
			unset($php_records);

			foreach($read_limits as $limit) $app->tpl->setVar($limit, ($limit == 'force_suexec' ? 'n' : 'y'));

			// Directive Snippets
			$php_directive_snippets_txt = '';
			$php_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'php' AND active = 'y' AND master_directive_snippets_id > 0 ORDER BY name");
			if(is_array($php_directive_snippets) && !empty($php_directive_snippets)){
				$php_directive_snippets_txt .= $app->tform->wordbook["select_master_directive_snippet_txt"].'<br>';
				foreach($php_directive_snippets as $php_directive_snippet){
					$php_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $php_directive_snippet['snippet'] . PHP_EOL;
					$php_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($php_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($php_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}
				$php_directive_snippets_txt .= '<br><br>';
			}
			
			$php_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'php' AND active = 'y' AND master_directive_snippets_id = 0 ORDER BY name");
			if(is_array($php_directive_snippets) && !empty($php_directive_snippets)){
				$php_directive_snippets_txt .= $app->tform->wordbook["select_directive_snippet_txt"].'<br>';
				foreach($php_directive_snippets as $php_directive_snippet){
					$php_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $php_directive_snippet['snippet'] . PHP_EOL;
					$php_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($php_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($php_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}
			}
			if($php_directive_snippets_txt == '') $php_directive_snippets_txt = '------';
			$app->tpl->setVar("php_directive_snippets_txt", $php_directive_snippets_txt);

			if($server_type == 'apache'){
				$apache_directive_snippets_txt = '';
				$apache_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'apache' AND active = 'y' AND master_directive_snippets_id > 0 ORDER BY name");
				if(is_array($apache_directive_snippets) && !empty($apache_directive_snippets)){
					$apache_directive_snippets_txt .= $app->tform->wordbook["select_master_directive_snippet_txt"].'<br>';
					foreach($apache_directive_snippets as $apache_directive_snippet){
						$apache_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $apache_directive_snippet['snippet'] . PHP_EOL;
						$apache_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($apache_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($apache_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
					$apache_directive_snippets_txt .= '<br><br>';
				}
				
				$apache_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'apache' AND active = 'y' AND master_directive_snippets_id = 0 ORDER BY name");
				if(is_array($apache_directive_snippets) && !empty($apache_directive_snippets)){
					$apache_directive_snippets_txt .= $app->tform->wordbook["select_directive_snippet_txt"].'<br>';
					foreach($apache_directive_snippets as $apache_directive_snippet){
						$apache_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $apache_directive_snippet['snippet'] . PHP_EOL;
						$apache_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($apache_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($apache_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
				}
				if($apache_directive_snippets_txt == '') $apache_directive_snippets_txt = '------';
				$app->tpl->setVar("apache_directive_snippets_txt", $apache_directive_snippets_txt);
			}

			if($server_type == 'nginx'){
				$nginx_directive_snippets_txt = '';
				$nginx_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'nginx' AND active = 'y' AND master_directive_snippets_id > 0 ORDER BY name");
				if(is_array($nginx_directive_snippets) && !empty($nginx_directive_snippets)){
					$nginx_directive_snippets_txt .= $app->tform->wordbook["select_master_directive_snippet_txt"].'<br>';
					foreach($nginx_directive_snippets as $nginx_directive_snippet){
						$nginx_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $nginx_directive_snippet['snippet'] . PHP_EOL;
						$nginx_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($nginx_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($nginx_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
					$nginx_directive_snippets_txt .= '<br><br>';
				}
				
				$nginx_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'nginx' AND active = 'y' AND master_directive_snippets_id = 0 ORDER BY name");
				if(is_array($nginx_directive_snippets) && !empty($nginx_directive_snippets)){
					$nginx_directive_snippets_txt .= $app->tform->wordbook["select_directive_snippet_txt"].'<br>';
					foreach($nginx_directive_snippets as $nginx_directive_snippet){
						$nginx_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $nginx_directive_snippet['snippet'] . PHP_EOL;
						$nginx_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($nginx_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($nginx_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
					}
				}
				if($nginx_directive_snippets_txt == '') $nginx_directive_snippets_txt = '------';
				$app->tpl->setVar("nginx_directive_snippets_txt", $nginx_directive_snippets_txt);
			}

			$proxy_directive_snippets_txt = '';
			$proxy_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'proxy' AND active = 'y' AND master_directive_snippets_id > 0 ORDER BY name");
			if(is_array($proxy_directive_snippets) && !empty($proxy_directive_snippets)){
				$proxy_directive_snippets_txt .= $app->tform->wordbook["select_master_directive_snippet_txt"].'<br>';
				foreach($proxy_directive_snippets as $proxy_directive_snippet){
					$proxy_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $proxy_directive_snippet['snippet'] . PHP_EOL;
					$proxy_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($proxy_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($proxy_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}
				$proxy_directive_snippets_txt .= '<br><br>';
			}
			
			$proxy_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'proxy' AND active = 'y' AND master_directive_snippets_id = 0 ORDER BY name");
			if(is_array($proxy_directive_snippets) && !empty($proxy_directive_snippets)){
				$proxy_directive_snippets_txt .= $app->tform->wordbook["select_directive_snippet_txt"].'<br>';
				foreach($proxy_directive_snippets as $proxy_directive_snippet){
					$proxy_directive_snippet['snippet'] = PHP_EOL . PHP_EOL . $proxy_directive_snippet['snippet'] . PHP_EOL;
					$proxy_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$app->functions->htmlentities($proxy_directive_snippet['name']).']<pre class="addPlaceholderContent" style="display:none;">'.$app->functions->htmlentities($proxy_directive_snippet['snippet']).'</pre></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				}
			}
			if($proxy_directive_snippets_txt == '') $proxy_directive_snippets_txt = '------';
			$app->tpl->setVar("proxy_directive_snippets_txt", $proxy_directive_snippets_txt);
		}

		$ssl_domain_select = '';
		$ssl_domains = array();
		$tmpd = $app->db->queryAllRecords("SELECT domain, type FROM web_domain WHERE domain_id = ? OR parent_domain_id = ?", $this->id, $this->id);
		foreach($tmpd as $tmp) {
			if($tmp['type'] == 'subdomain' || $tmp['type'] == 'vhostsubdomain') {
				$ssl_domains[] = $tmp["domain"];
			} else {
				$ssl_domains = array_merge($ssl_domains, array($tmp["domain"],'www.'.$tmp["domain"],'*.'.$tmp["domain"]));
			}
		}
		if(is_array($ssl_domains)) {
			foreach( $ssl_domains as $ssl_domain) {
				$selected = ($ssl_domain == $this->dataRecord['ssl_domain'])?'SELECTED':'';
				$ssl_domain_select .= "<option value='" . $app->functions->htmlentities($ssl_domain) . "' $selected>".$app->functions->htmlentities($app->functions->idn_decode($ssl_domain))."</option>\r\n";
			}
		}
		$app->tpl->setVar("ssl_domain", $ssl_domain_select);
		unset($ssl_domain_select);
		unset($ssl_domains);
		unset($ssl_domain);

		if($this->id > 0) {
			//* we are editing a existing record
			$app->tpl->setVar("edit_disabled", 1);
			$app->tpl->setVar('fixed_folder', 'y');
			if($this->_vhostdomain_type == 'domain') {
				$app->tpl->setVar("server_id_value", $this->dataRecord["server_id"], true);
				$app->tpl->setVar("document_root", $this->dataRecord["document_root"], true);
			}
			else $app->tpl->setVar('server_id_value', $parent_domain['server_id']);
		} else {
			$app->tpl->setVar("edit_disabled", 0);
			$app->tpl->setVar('fixed_folder', 'n');
			if($this->_vhostdomain_type != 'domain') $app->tpl->setVar('server_id_value', $parent_domain['server_id']);
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
			$domains = $app->tools_sites->getDomainModuleDomains($this->_vhostdomain_type == 'subdomain' ? null : "web_domain");
			$domain_select = '';
			$selected_domain = '';
			if(is_array($domains) && sizeof($domains) > 0) {
				/* We have domains in the list, so create the drop-down-list */
				foreach( $domains as $domain) {
					$domain_select .= "<option value=" . $domain['domain_id'] ;
					if ($this->_vhostdomain_type == 'subdomain' && '.' . $domain['domain'] == substr($this->dataRecord["domain"], -strlen($domain['domain']) - 1)) {
						$domain_select .= " selected";
						$selected_domain = $domain['domain'];
					} elseif($this->_vhostdomain_type == 'aliasdomain' && $domain['domain'] == $this->dataRecord["domain"]) {
						$domain_select .= " selected";
					} elseif($this->_vhostdomain_type == 'domain' && $domain['domain'] == $this->dataRecord["domain"]) {
						$domain_select .= " selected";
					}
					$domain_select .= ">" . $app->functions->htmlentities($app->functions->idn_decode($domain['domain'])) . "</option>\r\n";
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
			$app->tpl->setVar("domain_option", $domain_select);
			
			// remove the parent domain part of the domain name before we show it in the text field.
			if($this->dataRecord["type"] == 'vhostsubdomain') $this->dataRecord["domain"] = str_replace('.'.$selected_domain, '', $this->dataRecord["domain"]);

		
		} else {
			// remove the parent domain part of the domain name before we show it in the text field.
			if($this->dataRecord["type"] == 'vhostsubdomain') $this->dataRecord["domain"] = str_replace('.'.$parent_domain["domain"], '', $this->dataRecord["domain"]);
		}
		
		if($this->_vhostdomain_type != 'domain') $app->tpl->setVar("domain", $this->dataRecord["domain"], true);

		// check for configuration errors in sys_datalog
		if($this->id > 0) {
			$datalog = $app->db->queryOneRecord("SELECT sys_datalog.error, sys_log.tstamp FROM sys_datalog, sys_log WHERE sys_datalog.dbtable = 'web_domain' AND sys_datalog.dbidx = ? AND sys_datalog.datalog_id = sys_log.datalog_id AND sys_log.message = CONCAT('Processed datalog_id ',sys_log.datalog_id) ORDER BY sys_datalog.tstamp DESC", 'domain_id:' . $this->id);
			if(is_array($datalog) && !empty($datalog)){
				if(trim($datalog['error']) != ''){
					$app->tpl->setVar("config_error_msg", nl2br($app->functions->htmlentities($datalog['error'])));
					$app->tpl->setVar("config_error_tstamp", date($app->lng('conf_format_datetime'), $datalog['tstamp']));
				}
			}
		}
		
		$app->tpl->setVar('vhostdomain_type', $this->_vhostdomain_type, true);

		$app->tpl->setVar('is_spdy_enabled', ($web_config['enable_spdy'] === 'y'));
		$app->tpl->setVar('is_pagespeed_enabled', ($web_config['nginx_enable_pagespeed'] === 'y'));
		$app->tpl->setVar("is_admin", $is_admin);
		
		if($this->id > 0) {
			$tmp_web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", intval($this->id));
			$tmp_sys_group = $app->db->queryOneRecord("SELECT * FROM sys_group WHERE groupid = ?", intval($tmp_web['sys_groupid']));
			if(intval($tmp_sys_group['client_id']) > 0) $tmp_client = $app->db->queryOneRecord("SELECT * FROM client WHERE client_id = ?", intval($tmp_sys_group['client_id']));
			if(is_array($tmp_client) && !empty($tmp_client) && trim($this->dataRecord['ssl_organisation']) == '' && trim($this->dataRecord['ssl_locality']) == '' && trim($this->dataRecord['ssl_state']) == '' && trim($this->dataRecord['ssl_organisation_unit']) == '') $app->tpl->setVar("show_helper_links", true);
			$app->tpl->setVar('is_ssl_enabled', $tmp_web['ssl']);
		}

		$sys_config = $app->getconf->get_global_config('misc');
		if($sys_config['use_combobox'] == 'y') {
			$app->tpl->setVar('use_combobox', 'y');
		}
		
		$directive_snippets_id_select = '<option value="0"'.($this->dataRecord['directive_snippets_id'] == 0? ' selected="selected"' : '').'>-</option>';
		$server_type = $app->getconf->get_server_config($server_id, 'web');
		$server_type = $server_type['server_type'];
		
		$m_directive_snippets = $app->db->queryAllRecords("SELECT directive_snippets_id, name FROM directive_snippets WHERE customer_viewable = 'y' AND active = 'y' AND master_directive_snippets_id > 0 AND type = ? ORDER BY name ASC", $server_type);
		if(is_array($m_directive_snippets) && !empty($m_directive_snippets)){
			$directive_snippets_id_select .= '<optgroup label="'.$app->tform->wordbook["select_master_directive_snippet_txt"].'">';
			foreach($m_directive_snippets as $m_directive_snippet){
				$directive_snippets_id_select .= '<option value="'.$m_directive_snippet['directive_snippets_id'].'"'.($this->dataRecord['directive_snippets_id'] == $m_directive_snippet['directive_snippets_id']? ' selected="selected"' : '').'>'.$app->functions->htmlentities($m_directive_snippet['name']).'</option>';
			}
			$directive_snippets_id_select .= '</optgroup>';
		}

		if($is_admin) {
			$directive_snippets = $app->db->queryAllRecords("SELECT directive_snippets_id, name FROM directive_snippets WHERE active = 'y' AND master_directive_snippets_id = 0 AND type = ? ORDER BY name ASC", $server_type);
		} else {
			$directive_snippets = $app->db->queryAllRecords("SELECT directive_snippets_id, name FROM directive_snippets WHERE customer_viewable = 'y' AND active = 'y' AND master_directive_snippets_id = 0 AND type = ? ORDER BY name ASC", $server_type);
		}
		if(is_array($directive_snippets) && !empty($directive_snippets)){
			$directive_snippets_id_select .= '<optgroup label="'.$app->tform->wordbook["select_directive_snippet_txt"].'">';
			foreach($directive_snippets as $directive_snippet){
				$directive_snippets_id_select .= '<option value="'.$directive_snippet['directive_snippets_id'].'"'.($this->dataRecord['directive_snippets_id'] == $directive_snippet['directive_snippets_id']? ' selected="selected"' : '').'>'.$app->functions->htmlentities($directive_snippet['name']).'</option>';
			}
			$directive_snippets_id_select .= '</optgroup>';
		}
		$app->tpl->setVar("directive_snippets_id", $directive_snippets_id_select);
		
		// folder_directive_snippets
		if(isset($_POST['folder_directive_snippets']) && !isset($this->dataRecord['folder_directive_snippets'])){
			$this->dataRecord['folder_directive_snippets'] = '';
			if(is_array($_POST['folder_directive_snippets']) && !empty($_POST['folder_directive_snippets'])){
				foreach($_POST['folder_directive_snippets'] as $folder_directive_snippet){
					if(trim($folder_directive_snippet['folder']) != '' && intval($folder_directive_snippet['snippets_id']) > 0) $this->dataRecord['folder_directive_snippets'] .= trim($folder_directive_snippet['folder']).':'.intval($folder_directive_snippet['snippets_id'])."\n";
				}
			}
			$this->dataRecord['folder_directive_snippets'] = trim($this->dataRecord['folder_directive_snippets']);
		}
		
		$master_directive_snippets = $app->db->queryAllRecords("SELECT directive_snippets_id, name FROM directive_snippets WHERE customer_viewable = 'y' AND active = 'y' AND snippet LIKE '%{FOLDER}%' AND master_directive_snippets_id > 0 AND type = ? ORDER BY name ASC", $server_type);
		$c_directive_snippets = $app->db->queryAllRecords("SELECT directive_snippets_id, name FROM directive_snippets WHERE customer_viewable = 'y' AND active = 'y' AND snippet LIKE '%{FOLDER}%' AND master_directive_snippets_id = 0 AND type = ? ORDER BY name ASC", $server_type);
		
		$folder_directive_snippets = array();
		$this->dataRecord['folder_directive_snippets'] = str_replace("\r\n", "\n", $this->dataRecord['folder_directive_snippets']);
		$this->dataRecord['folder_directive_snippets'] = str_replace("\r", "\n", $this->dataRecord['folder_directive_snippets']);
		$folder_directive_snippets_lines = explode("\n", trim($this->dataRecord['folder_directive_snippets']));
		for($i=0;$i<sizeof($folder_directive_snippets_lines)+50;$i++){
			$folder_directive_snippets[$i]['folder_directive_snippets_index'] = $i;
			$folder_directive_snippets[$i]['folder_directive_snippets_index_plus_1'] = $i + 1;
			if($i > sizeof($folder_directive_snippets_lines)){
				$folder_directive_snippets[$i]['folder_directive_snippets_css'] = 'hidden';
			} else {
				$folder_directive_snippets[$i]['folder_directive_snippets_css'] = '';
			}
			if(trim($folder_directive_snippets_lines[$i]) != ''){
				list($folder_directive_snippets[$i]['folder_directive_snippets_folder'], $selected_snippet) = explode(':', trim($folder_directive_snippets_lines[$i]));
				$folder_directive_snippets[$i]['folder_directive_snippets_id'] = '<option value="0">-</option>';
				if(is_array($master_directive_snippets) && !empty($master_directive_snippets)){
					$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '<optgroup label="'.$app->tform->wordbook["select_master_directive_snippet_txt"].'">';
					foreach($master_directive_snippets as $master_directive_snippet){
						$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '<option value="'.$master_directive_snippet['directive_snippets_id'].'"'.($master_directive_snippet['directive_snippets_id'] == $selected_snippet ? ' selected="selected"' : '').'>'.$master_directive_snippet['name'].'</option>';
					}
					$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '</optgroup>';
				}
				
				if(is_array($c_directive_snippets) && !empty($c_directive_snippets)){
					$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '<optgroup label="'.$app->tform->wordbook["select_directive_snippet_txt"].'">';
					foreach($c_directive_snippets as $c_directive_snippet){
						$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '<option value="'.$c_directive_snippet['directive_snippets_id'].'"'.($c_directive_snippet['directive_snippets_id'] == $selected_snippet? ' selected="selected"' : '').'>'.$c_directive_snippet['name'].'</option>';
					}
					$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '</optgroup>';
				}
			} else {
				$folder_directive_snippets[$i]['folder_directive_snippets_folder'] = '';
				$folder_directive_snippets[$i]['folder_directive_snippets_id'] = '<option value="0">-</option>';
				if(is_array($master_directive_snippets) && !empty($master_directive_snippets)){
					$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '<optgroup label="'.$app->tform->wordbook["select_master_directive_snippet_txt"].'">';
					foreach($master_directive_snippets as $master_directive_snippet){
						$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '<option value="'.$master_directive_snippet['directive_snippets_id'].'">'.$master_directive_snippet['name'].'</option>';
					}
					$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '</optgroup>';
				}
				
				if(is_array($c_directive_snippets) && !empty($c_directive_snippets)){
					$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '<optgroup label="'.$app->tform->wordbook["select_directive_snippet_txt"].'">';
					foreach($c_directive_snippets as $c_directive_snippet){
						$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '<option value="'.$c_directive_snippet['directive_snippets_id'].'">'.$c_directive_snippet['name'].'</option>';
					}
					$folder_directive_snippets[$i]['folder_directive_snippets_id'] .= '</optgroup>';
				}
			}
		}
		$app->tpl->setLoop('folder_directive_snippets', $folder_directive_snippets);
		if(is_array($web_config[$server_id])) {
			$app->tpl->setVar('is_spdy_enabled', ($web_config[$server_id]['enable_spdy'] === 'y'));
			$app->tpl->setVar('is_pagespeed_enabled', ($web_config[$server_id]['nginx_enable_pagespeed']));
		} else {
			$app->tpl->setVar('is_spdy_enabled', ($web_config['enable_spdy'] === 'y'));
			$app->tpl->setVar('is_pagespeed_enabled', ($web_config['nginx_enable_pagespeed']));
		}

		parent::onShowEnd();
	}

	function onShowEdit() {
		global $app;
		if($app->tform->checkPerm($this->id, 'riud')) $app->tform->formDef['tabs']['domain']['readonly'] = false;
		parent::onShowEdit();
	}

	function onSubmit() {
		global $app, $conf;
		// Set a few fixed values
		$this->dataRecord["vhost_type"] = 'name';
		if($this->_vhostdomain_type == 'domain') {
			$this->dataRecord["parent_domain_id"] = 0;
			$this->dataRecord["type"] = 'vhost';
		} else {
			// Get the record of the parent domain
			if(!@$this->dataRecord["parent_domain_id"] && $this->id) {
				$tmp = $app->db->queryOneRecord("SELECT parent_domain_id FROM web_domain WHERE domain_id = ?", $this->id);
				if($tmp) $this->dataRecord["parent_domain_id"] = $tmp['parent_domain_id'];
				unset($tmp);
			}

			$parent_domain = $app->db->queryOneRecord("select * FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL('r'), @$this->dataRecord["parent_domain_id"]);
			if(!$parent_domain || $parent_domain['domain_id'] != @$this->dataRecord['parent_domain_id']) $app->tform->errorMessage .= $app->tform->lng("no_domain_perm");

			if($this->_vhostdomain_type == 'subdomain') {
				$this->dataRecord["type"] = 'vhostsubdomain';
			} else {
				$this->dataRecord["type"] = 'vhostalias';
			}
			$this->dataRecord["server_id"] = $parent_domain["server_id"];
			$this->dataRecord["ip_address"] = $parent_domain["ip_address"];
			$this->dataRecord["ipv6_address"] = $parent_domain["ipv6_address"];
			$this->dataRecord["client_group_id"] = $parent_domain["client_group_id"];

			$this->parent_domain_record = $parent_domain;
		}

		$read_limits = array('limit_cgi', 'limit_ssi', 'limit_perl', 'limit_ruby', 'limit_python', 'force_suexec', 'limit_hterror', 'limit_wildcard', 'limit_ssl', 'limit_ssl_letsencrypt', 'limit_directive_snippets');

		/* check if the domain module is used - and check if the selected domain can be used! */
		if($app->tform->getCurrentTab() == 'domain') {
			if($this->_vhostdomain_type == 'subdomain') {
				// Check that domain (the subdomain part) is not empty
				if(!preg_match('/^[a-zA-Z0-9].*/',$this->dataRecord['domain'])) {
					$app->tform->errorMessage .= $app->tform->lng("subdomain_error_empty")."<br />";
				}
			}
			
			/* check if the domain module is used - and check if the selected domain can be used! */
			$app->uses('ini_parser,getconf');
			$settings = $app->getconf->get_global_config('domains');
			if ($settings['use_domain_module'] == 'y') {
				if($this->_vhostdomain_type == 'subdomain') $domain_check = $app->tools_sites->checkDomainModuleDomain($this->dataRecord['sel_domain']);
				else $domain_check = $app->tools_sites->checkDomainModuleDomain($this->dataRecord['domain']);
				if(!$domain_check) {
					// invalid domain selected
					$app->tform->errorMessage .= $app->tform->lng("domain_error_empty")."<br />";
				} else {
					if ($this->_vhostdomain_type == 'domain' &&
							($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid']))) {
						$this->dataRecord['client_group_id'] = $app->tools_sites->getClientIdForDomain($this->dataRecord['domain']);
					}
					if($this->_vhostdomain_type == 'subdomain') $this->dataRecord['domain'] = $this->dataRecord['domain'] . '.' . $domain_check;
					else $this->dataRecord['domain'] = $domain_check;
				}
			} else {
				if($this->_vhostdomain_type == 'subdomain') $this->dataRecord["domain"] = $this->dataRecord["domain"].'.'.$parent_domain["domain"];
			}

			if($this->_vhostdomain_type != 'domain') {
				$this->dataRecord['web_folder'] = strtolower($this->dataRecord['web_folder']);
				if(substr($this->dataRecord['web_folder'], 0, 1) === '/') $this->dataRecord['web_folder'] = substr($this->dataRecord['web_folder'], 1);
				if(substr($this->dataRecord['web_folder'], -1) === '/') $this->dataRecord['web_folder'] = substr($this->dataRecord['web_folder'], 0, -1);
				$forbidden_folders = array('', 'cgi-bin', 'log', 'private', 'ssl', 'tmp', 'webdav');
				$check_folder = strtolower($this->dataRecord['web_folder']);
				if(substr($check_folder, 0, 1) === '/') $check_folder = substr($check_folder, 1); // strip / at beginning to check against forbidden entries
				if(strpos($check_folder, '/') !== false) $check_folder = substr($check_folder, 0, strpos($check_folder, '/')); // get the first part of the path to check it
				if(in_array($check_folder, $forbidden_folders)) {
					$app->tform->errorMessage .= $app->tform->lng("web_folder_invalid_txt")."<br>";
				}

				// vhostaliasdomains do not have a quota of their own
				$this->dataRecord["hd_quota"] = 0;
			}
		}



		if($_SESSION["s"]["user"]["typ"] != 'admin') {
			// Get the limits of the client
			$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
			$client = $app->db->queryOneRecord("SELECT limit_traffic_quota, limit_web_domain, limit_web_aliasdomain, limit_web_subdomain, web_servers, parent_client_id, limit_web_quota, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

			$client['web_servers_ids'] = explode(',', $client['web_servers']);

			if($client['limit_cgi'] != 'y') $this->dataRecord['cgi'] = 'n';
			if($client['limit_ssi'] != 'y') $this->dataRecord['ssi'] = 'n';
			if($client['limit_perl'] != 'y') $this->dataRecord['perl'] = 'n';
			if($client['limit_ruby'] != 'y') $this->dataRecord['ruby'] = 'n';
			if($client['limit_python'] != 'y') $this->dataRecord['python'] = 'n';
			if($client['force_suexec'] == 'y') $this->dataRecord['suexec'] = 'y';
			if($client['limit_hterror'] != 'y') $this->dataRecord['errordocs'] = 'n';
			if($client['limit_wildcard'] != 'y' && $this->dataRecord['subdomain'] == '*') $this->dataRecord['subdomain'] = 'n';
			if($client['limit_ssl'] != 'y') $this->dataRecord['ssl'] = 'n';
			if($client['limit_ssl_letsencrypt'] != 'y') $this->dataRecord['ssl_letsencrypt'] = 'n';
			if($client['limit_directive_snippets'] != 'y') $this->dataRecord['directive_snippets_id'] = 0;

			// only generate quota and traffic warnings if value has changed
			if($this->id > 0) {
				$old_web_values = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $this->id);
			} else {
				$old_web_values = array();
			}
			
			if($this->_vhostdomain_type == 'domain') {
				//* ensure that quota value is not 0 when vhost type = domain
				if(isset($_POST["hd_quota"]) && $_POST["hd_quota"] == 0) {
					$app->tform->errorMessage .= $app->tform->lng("limit_web_quota_not_0_txt")."<br>";
				}
				
				//* Check the website quota of the client
				if(isset($_POST["hd_quota"]) && $client["limit_web_quota"] >= 0 && $_POST["hd_quota"] != $old_web_values["hd_quota"]) {
					$tmp = $app->db->queryOneRecord("SELECT sum(hd_quota) as webquota FROM web_domain WHERE domain_id != ? AND type = 'vhost' AND ".$app->tform->getAuthSQL('u'), $this->id);
					$webquota = $tmp["webquota"];
					$new_web_quota = $app->functions->intval($this->dataRecord["hd_quota"]);
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
			}

			//* Check the traffic quota of the client
			if(isset($_POST["traffic_quota"]) && $client["limit_traffic_quota"] > 0 && $_POST["traffic_quota"] != $old_web_values["traffic_quota"]) {
				$tmp = $app->db->queryOneRecord("SELECT sum(traffic_quota) as trafficquota FROM web_domain WHERE domain_id != ? AND ".$app->tform->getAuthSQL('u'), $this->id);
				$trafficquota = $tmp["trafficquota"];
				$new_traffic_quota = $app->functions->intval($this->dataRecord["traffic_quota"]);
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
				$reseller = $app->db->queryOneRecord("SELECT limit_traffic_quota, limit_web_domain, limit_web_aliasdomain, limit_web_subdomain, web_servers, limit_web_quota FROM client WHERE client_id = ?", $client['parent_client_id']);

				if($this->_vhostdomain_type == 'domain') {
					//* Check the website quota of the client
					if(isset($_POST["hd_quota"]) && $reseller["limit_web_quota"] >= 0 && $_POST["hd_quota"] != $old_web_values["hd_quota"]) {
						$tmp = $app->db->queryOneRecord("SELECT sum(hd_quota) as webquota FROM web_domain, sys_group, client WHERE web_domain.sys_groupid=sys_group.groupid AND sys_group.client_id=client.client_id AND ? IN (client.parent_client_id, client.client_id) AND domain_id != ? AND type = 'vhost'", $client['parent_client_id'], $this->id);

						$webquota = $tmp["webquota"];
						$new_web_quota = $app->functions->intval($this->dataRecord["hd_quota"]);
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
				}

				//* Check the traffic quota of the client
				if(isset($_POST["traffic_quota"]) && $reseller["limit_traffic_quota"] > 0 && $_POST["traffic_quota"] != $old_web_values["traffic_quota"]) {
					$tmp = $app->db->queryOneRecord("SELECT sum(traffic_quota) as trafficquota FROM web_domain, sys_group, client WHERE web_domain.sys_groupid=sys_group.groupid AND sys_group.client_id=client.client_id AND ? IN (client.parent_client_id, client.client_id) AND domain_id != ? AND type = 'vhost'", $client['parent_client_id'], $this->id);
					$trafficquota = $tmp["trafficquota"];
					$new_traffic_quota = $app->functions->intval($this->dataRecord["traffic_quota"]);
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
				$tmp = $app->db->queryOneRecord("SELECT server_id, `system_user`, `system_group`, `web_folder`, `cgi`, `ssi`, `perl`, `ruby`, `python`, `suexec`, `errordocs`, `subdomain`, `ssl`, `ssl_letsencrypt`, `directive_snippets_id` FROM web_domain WHERE domain_id = ?", $this->id);
				$this->dataRecord["server_id"] = $tmp["server_id"];
				if($this->_vhostdomain_type != 'domain') $this->dataRecord['web_folder'] = $tmp['web_folder']; // cannot be changed!
				$this->dataRecord['system_user'] = $tmp['system_user'];
				$this->dataRecord['system_group'] = $tmp['system_group'];

				// set the settings to current if not provided (or cleared due to limits)
				if($this->dataRecord['cgi'] == 'n') $this->dataRecord['cgi'] = $tmp['cgi'];
				if($this->dataRecord['ssi'] == 'n') $this->dataRecord['ssi'] = $tmp['ssi'];
				if($this->dataRecord['perl'] == 'n') $this->dataRecord['perl'] = $tmp['perl'];
				if($this->dataRecord['ruby'] == 'n') $this->dataRecord['ruby'] = $tmp['ruby'];
				if($this->dataRecord['python'] == 'n') $this->dataRecord['python'] = $tmp['python'];
				if($this->dataRecord['suexec'] == 'n') $this->dataRecord['suexec'] = $tmp['suexec'];
				if($this->dataRecord['errordocs'] == 'n') $this->dataRecord['errordocs'] = $tmp['errordocs'];
				if($this->dataRecord['subdomain'] == 'n') $this->dataRecord['subdomain'] = $tmp['subdomain'];
				if($this->dataRecord['ssl'] == 'n') $this->dataRecord['ssl'] = $tmp['ssl'];
				if($this->dataRecord['ssl_letsencrypt'] == 'n') $this->dataRecord['ssl_letsencrypt'] = $tmp['ssl_letsencrypt'];
				if($this->dataRecord['directive_snippets_id'] == 0) $this->dataRecord['directive_snippets_id'] = $tmp['directive_snippets_id'];
				
				unset($tmp);
				// When the record is inserted
			} else {
				if($this->_vhostdomain_type == 'domain') {
					//* display an error if chosen server is not allowed for this client
					if (!is_array($client['web_servers_ids']) || !in_array($this->dataRecord['server_id'], $client['web_servers_ids'])) {
						$app->error($app->tform->wordbook['server_chosen_not_ok']);
					}
				}

				// Check if the user may add another web_domain
				if($this->_vhostdomain_type == 'domain' && $client["limit_web_domain"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM web_domain WHERE sys_groupid = ? and type = 'vhost'", $client_group_id);
					if($tmp["number"] >= $client["limit_web_domain"]) {
						$app->error($app->tform->wordbook["limit_web_domain_txt"]);
					}
				} elseif($this->_vhostdomain_type == 'aliasdomain' && $client["limit_web_aliasdomain"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM web_domain WHERE sys_groupid = ? and (type = 'alias' OR type = 'vhostalias')", $client_group_id);
					if($tmp["number"] >= $client["limit_web_aliasdomain"]) {
						$app->error($app->tform->wordbook["limit_web_aliasdomain_txt"]);
					}
				} elseif($this->_vhostdomain_type == 'subdomain' && $client["limit_web_subdomain"] >= 0) {
					$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM web_domain WHERE sys_groupid = ? and (type = 'subdomain' OR type = 'vhostsubdomain')", $client_group_id);
					if($tmp["number"] >= $client["limit_web_subdomain"]) {
						$app->error($app->tform->wordbook["limit_web_subdomain_txt"]);
					}
				}
			}

			// Clients may not set the client_group_id, so we unset them if user is not a admin and the client is not a reseller
			if(!$app->auth->has_clients($_SESSION['s']['user']['userid'])) unset($this->dataRecord["client_group_id"]);
		}

		//* make sure that the domain is lowercase
		if(isset($this->dataRecord["domain"])) $this->dataRecord["domain"] = strtolower($this->dataRecord["domain"]);

		//* get the server config for this server
		$app->uses("getconf");
		if($this->id > 0){
			$web_rec = $app->tform->getDataRecord($this->id);
			$server_id = $web_rec["server_id"];
		} else {
			// Get the first server ID
			$tmp = $app->db->queryOneRecord("SELECT server_id FROM server WHERE web_server = 1 ORDER BY server_name LIMIT 0,1");
			$server_id = intval($tmp['server_id']);
		}
		$web_config = $app->getconf->get_server_config($app->functions->intval(isset($this->dataRecord["server_id"]) ? $this->dataRecord["server_id"] : $server_id), 'web');
		//* Check for duplicate ssl certs per IP if SNI is disabled
		if(isset($this->dataRecord['ssl']) && $this->dataRecord['ssl'] == 'y' && $web_config['enable_sni'] != 'y') {
			$sql = "SELECT count(domain_id) as number FROM web_domain WHERE `ssl` = 'y' AND ip_address = ? and domain_id != ?";
			$tmp = $app->db->queryOneRecord($sql, $this->dataRecord['ip_address'], $this->id);
			if($tmp['number'] > 0) $app->tform->errorMessage .= $app->tform->lng("error_no_sni_txt");
		}

		// Check if pm.max_children >= pm.max_spare_servers >= pm.start_servers >= pm.min_spare_servers > 0
		if(isset($this->dataRecord['pm_max_children']) && $this->dataRecord['pm'] == 'dynamic') {
			if($app->functions->intval($this->dataRecord['pm_max_children'], true) >= $app->functions->intval($this->dataRecord['pm_max_spare_servers'], true) && $app->functions->intval($this->dataRecord['pm_max_spare_servers'], true) >= $app->functions->intval($this->dataRecord['pm_start_servers'], true) && $app->functions->intval($this->dataRecord['pm_start_servers'], true) >= $app->functions->intval($this->dataRecord['pm_min_spare_servers'], true) && $app->functions->intval($this->dataRecord['pm_min_spare_servers'], true) > 0){

			} else {
				$app->tform->errorMessage .= $app->tform->lng("error_php_fpm_pm_settings_txt").'<br>';
			}
		}

		// Check rewrite rules
		$server_type = $web_config['server_type'];

		if($server_type == 'nginx' && isset($this->dataRecord['rewrite_rules']) && trim($this->dataRecord['rewrite_rules']) != '') {
			$rewrite_rules = trim($this->dataRecord['rewrite_rules']);
			$rewrites_are_valid = true;
			// use this counter to make sure all curly brackets are properly closed
			$if_level = 0;
			// Make sure we only have Unix linebreaks
			$rewrite_rules = str_replace("\r\n", "\n", $rewrite_rules);
			$rewrite_rules = str_replace("\r", "\n", $rewrite_rules);
			$rewrite_rule_lines = explode("\n", $rewrite_rules);
			if(is_array($rewrite_rule_lines) && !empty($rewrite_rule_lines)){
				foreach($rewrite_rule_lines as $rewrite_rule_line){
					// ignore comments
					if(substr(ltrim($rewrite_rule_line), 0, 1) == '#') continue;
					// empty lines
					if(trim($rewrite_rule_line) == '') continue;
					// rewrite
					if(preg_match('@^\s*rewrite\s+(^/)?\S+(\$)?\s+\S+(\s+(last|break|redirect|permanent|))?\s*;\s*$@', $rewrite_rule_line)) continue;
					if(preg_match('@^\s*rewrite\s+(^/)?(\'[^\']+\'|"[^"]+")+(\$)?\s+(\'[^\']+\'|"[^"]+")+(\s+(last|break|redirect|permanent|))?\s*;\s*$@', $rewrite_rule_line)) continue;
					if(preg_match('@^\s*rewrite\s+(^/)?(\'[^\']+\'|"[^"]+")+(\$)?\s+\S+(\s+(last|break|redirect|permanent|))?\s*;\s*$@', $rewrite_rule_line)) continue;
					if(preg_match('@^\s*rewrite\s+(^/)?\S+(\$)?\s+(\'[^\']+\'|"[^"]+")+(\s+(last|break|redirect|permanent|))?\s*;\s*$@', $rewrite_rule_line)) continue;
					// if
					if(preg_match('@^\s*if\s+\(\s*\$\S+(\s+(\!?(=|~|~\*))\s+(\S+|\".+\"))?\s*\)\s*\{\s*$@', $rewrite_rule_line)){
						$if_level += 1;
						continue;
					}
					// if - check for files, directories, etc.
					if(preg_match('@^\s*if\s+\(\s*\!?-(f|d|e|x)\s+\S+\s*\)\s*\{\s*$@', $rewrite_rule_line)){
						$if_level += 1;
						continue;
					}
					// break
					if(preg_match('@^\s*break\s*;\s*$@', $rewrite_rule_line)){
						continue;
					}
					// return code [ text ]
					if(preg_match('@^\s*return\s+\d\d\d.*;\s*$@', $rewrite_rule_line)) continue;
					// return code URL
					// return URL
					if(preg_match('@^\s*return(\s+\d\d\d)?\s+(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&%\$\-]+)*\@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|localhost|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&%\$#\=~_\-]+))*\s*;\s*$@', $rewrite_rule_line)) continue;
					// set
					if(preg_match('@^\s*set\s+\$\S+\s+\S+\s*;\s*$@', $rewrite_rule_line)) continue;
					// closing curly bracket
					if(trim($rewrite_rule_line) == '}'){
						$if_level -= 1;
						continue;
					}
					$rewrites_are_valid = false;
					break;
				}
			}

			if(!$rewrites_are_valid || $if_level != 0){
				$app->tform->errorMessage .= $app->tform->lng("invalid_rewrite_rules_txt").'<br>';
			}
		}
		
		// check custom php.ini settings
		if(isset($this->dataRecord['custom_php_ini']) && trim($this->dataRecord['custom_php_ini']) != '') {
			$custom_php_ini_settings = trim($this->dataRecord['custom_php_ini']);
			$custom_php_ini_settings_are_valid = true;
			// Make sure we only have Unix linebreaks
			$custom_php_ini_settings = str_replace("\r\n", "\n", $custom_php_ini_settings);
			$custom_php_ini_settings = str_replace("\r", "\n", $custom_php_ini_settings);
			$custom_php_ini_settings_lines = explode("\n", $custom_php_ini_settings);
			if(is_array($custom_php_ini_settings_lines) && !empty($custom_php_ini_settings_lines)){
				foreach($custom_php_ini_settings_lines as $custom_php_ini_settings_line){
					if(trim($custom_php_ini_settings_line) == '') continue;
					if(substr(trim($custom_php_ini_settings_line),0,1) == ';') continue;
					// empty value
					if(preg_match('@^\s*;*\s*[a-zA-Z0-9._]*\s*=\s*;*\s*$@', $custom_php_ini_settings_line)) continue;
					// value inside ""
					if(preg_match('@^\s*;*\s*[a-zA-Z0-9._]*\s*=\s*".*"\s*;*\s*$@', $custom_php_ini_settings_line)) continue;
					// value inside ''
					if(preg_match('@^\s*;*\s*[a-zA-Z0-9._]*\s*=\s*\'.*\'\s*;*\s*$@', $custom_php_ini_settings_line)) continue;
					// everything else
					if(preg_match('@^\s*;*\s*[a-zA-Z0-9._]*\s*=\s*[-a-zA-Z0-9~&=_\@/,.#\{\}\s\|]*\s*;*\s*$@', $custom_php_ini_settings_line)) continue;
					$custom_php_ini_settings_are_valid = false;
					break;
				}
			}
			if(!$custom_php_ini_settings_are_valid){
				$app->tform->errorMessage .= $app->tform->lng("invalid_custom_php_ini_settings_txt").'<br>';
			}
		}

		if($web_config['enable_spdy'] === 'n') {
			unset($app->tform->formDef["tabs"]['ssl']['fields']['enable_spdy']);
		}
	//	if($this->dataRecord["directive_snippets_id"] < 1) $this->dataRecord["enable_pagespeed"] = 'n';
		
		//print_r($_POST['folder_directive_snippets']);
		//print_r($_POST['folder_directive_snippets_id']);
		if(isset($_POST['folder_directive_snippets'])){
			$this->dataRecord['folder_directive_snippets'] = '';
			if(is_array($_POST['folder_directive_snippets']) && !empty($_POST['folder_directive_snippets'])){
				$existing_directive_snippets_folders = array();
				foreach($_POST['folder_directive_snippets'] as $folder_directive_snippet){
					$folder_directive_snippet['folder'] = trim($folder_directive_snippet['folder']);
					if($folder_directive_snippet['folder'] != '' && intval($folder_directive_snippet['snippets_id']) > 0){
						if(substr($folder_directive_snippet['folder'], -1) != '/') $folder_directive_snippet['folder'] .= '/';
						if(substr($folder_directive_snippet['folder'], 0, 1) == '/') $folder_directive_snippet['folder'] = substr($folder_directive_snippet['folder'], 1);
						if(in_array($folder_directive_snippet['folder'], $existing_directive_snippets_folders)){
							$app->tform->errorMessage .= $app->tform->lng("config_for_folder_exists_already_txt").'<br>';
						} else {
							$existing_directive_snippets_folders[] = $folder_directive_snippet['folder'];
						}
						$this->dataRecord['folder_directive_snippets'] .= $folder_directive_snippet['folder'].':'.intval($folder_directive_snippet['snippets_id'])."\n";
					}
					if(!preg_match('@^((?!(.*\.\.)|(.*\./)|(.*//))[^/][\w/_\.\-]{1,100})?$@', $folder_directive_snippet['folder'])) $app->tform->errorMessage .= $app->tform->lng("web_folder_error_regex").'<br>';
				}
			}
			$this->dataRecord['folder_directive_snippets'] = trim($this->dataRecord['folder_directive_snippets']);
		}
		
		// Check custom PHP version
		if(isset($this->dataRecord['fastcgi_php_version']) && $this->dataRecord['fastcgi_php_version'] != '') {
			// Check php-fpm mode
			if($this->dataRecord['php'] == 'php-fpm'){
				$tmp = $app->db->queryOneRecord("SELECT * FROM server_php WHERE active = 'y' AND CONCAT(name,':',php_fpm_init_script,':',php_fpm_ini_dir,':',php_fpm_pool_dir) = '".$app->db->quote($this->dataRecord['fastcgi_php_version'])."'");
				if(is_array($tmp)) {
					$this->dataRecord['fastcgi_php_version'] = $tmp['name'].':'.$tmp['php_fpm_init_script'].':'.$tmp['php_fpm_ini_dir'].':'.$tmp['php_fpm_pool_dir'];
				} else {
					$this->dataRecord['fastcgi_php_version'] = '';
				}
				unset($tmp);
			// Check fast-cgi mode
			} elseif($this->dataRecord['php'] == 'fast-cgi') {
				$tmp = $app->db->queryOneRecord("SELECT * FROM server_php WHERE active = 'y' AND CONCAT(name,':',php_fastcgi_binary,':',php_fastcgi_ini_dir) = '".$app->db->quote($this->dataRecord['fastcgi_php_version'])."'");
				if(is_array($tmp)) {
					$this->dataRecord['fastcgi_php_version'] = $tmp['name'].':'.$tmp['php_fastcgi_binary'].':'.$tmp['php_fastcgi_ini_dir'];
				} else {
					$this->dataRecord['fastcgi_php_version'] = '';
				}
				unset($tmp);
			} else {
				// Other PHP modes do not have custom versions, so we force the value to be empty
				$this->dataRecord['fastcgi_php_version'] = '';
			}
		}
		
		$this->validateDefaultFastcgiPhpVersion();
		
		parent::onSubmit();
	}
	
	function onBeforeInsert() {
		global $app, $conf;
		
		// Letsencrypt can not be activated before the website has been created
		// So we deactivate it here and add a datalog update in onAfterInsert
		if(isset($this->dataRecord['ssl_letsencrypt']) && $this->dataRecord['ssl_letsencrypt'] == 'y' && isset($this->dataRecord['ssl']) && $this->dataRecord['ssl'] == 'y') {
			// Disable letsencrypt and ssl temporarily
			$this->dataRecord['ssl_letsencrypt'] = 'n';
			$this->dataRecord['ssl'] = 'n';
			// Prevent that the datalog history gets written
			$app->tform->formDef['db_history'] = 'no';
			// Set variable that we check in onAfterInsert
			$this->_letsencrypt_on_insert = true;
		}
	}
	

	function onAfterInsert() {
		global $app, $conf;

		// make sure that the record belongs to the clinet group and not the admin group when admin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = ?, sys_perm_group = 'ru' WHERE domain_id = ?", $client_group_id, $this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = ?, sys_perm_group = 'riud' WHERE domain_id = ?", $client_group_id, $this->id);
		}

		// Get configuration for the web system
		$app->uses("getconf");
		$web_rec = $app->tform->getDataRecord($this->id);
		$web_config = $app->getconf->get_server_config($app->functions->intval($web_rec["server_id"]), 'web');
		
		// get global log retention value as default for web log retention
		$server_config = $app->getconf->get_server_config($app->functions->intval($web_rec["server_id"]), 'server');
		if($server_config['log_retention'] > 0) {
			$log_retention = $server_config['log_retention'];
		} else {
			$log_retention = 10;
		}

		// Get default value for chrooted PHP-FPM
		$php_fpm_chroot = 'n';
		if (!empty($web_config['php_fpm_default_chroot'])) {
			$php_fpm_chroot = $web_config['php_fpm_default_chroot'];
		}

		if($this->_vhostdomain_type == 'domain') {
			$document_root = str_replace("[website_id]", $this->id, $web_config["website_path"]);
			$document_root = str_replace("[website_idhash_1]", $this->id_hash($page_form->id, 1), $document_root);
			$document_root = str_replace("[website_idhash_2]", $this->id_hash($page_form->id, 1), $document_root);
			$document_root = str_replace("[website_idhash_3]", $this->id_hash($page_form->id, 1), $document_root);
			$document_root = str_replace("[website_idhash_4]", $this->id_hash($page_form->id, 1), $document_root);

			// get the ID of the client
			if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
				$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
				$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ?", $client_group_id);
				$client_id = $app->functions->intval($client["client_id"]);
			} else {
				//$client_id = $app->functions->intval($this->dataRecord["client_group_id"]);
				$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ?", $this->dataRecord["client_group_id"]);
				$client_id = $app->functions->intval($client["client_id"]);
			}

			// Set the values for document_root, system_user and system_group
			$system_user = 'web'.$this->id;
			$system_group = 'client'.$client_id;
			$document_root = str_replace("[client_id]", $client_id, $document_root);
			$document_root = str_replace("[client_idhash_1]", $this->id_hash($client_id, 1), $document_root);
			$document_root = str_replace("[client_idhash_2]", $this->id_hash($client_id, 2), $document_root);
			$document_root = str_replace("[client_idhash_3]", $this->id_hash($client_id, 3), $document_root);
			$document_root = str_replace("[client_idhash_4]", $this->id_hash($client_id, 4), $document_root);
			$document_root = $document_root;
			$php_open_basedir = str_replace("[website_path]", $document_root, $web_config["php_open_basedir"]);
			$php_open_basedir = str_replace("[website_domain]", $web_rec['domain'], $php_open_basedir);
			$htaccess_allow_override = $web_config["htaccess_allow_override"];
			$added_by = $_SESSION['s']['user']['username'];

			$sql = "UPDATE web_domain SET system_user = ?, system_group = ?, document_root = ?, allow_override = ?, php_open_basedir = ?, added_date = CURDATE(), added_by = ?, log_retention = ?, php_fpm_chroot = ? WHERE domain_id = ?";
			$app->db->query($sql, $system_user, $system_group, $document_root, $htaccess_allow_override, $php_open_basedir, $added_by, $log_retention, $php_fpm_chroot, $this->id);
		} else  {
			// Set the values for document_root, system_user and system_group
			$system_user = $this->parent_domain_record['system_user'];
			$system_group = $this->parent_domain_record['system_group'];
			$document_root = $this->parent_domain_record['document_root'];
			$php_open_basedir = str_replace("[website_path]/web", $document_root.'/'.$web_rec['web_folder'], $web_config["php_open_basedir"]);
			$php_open_basedir = str_replace("[website_domain]/web", $web_rec['domain'].'/'.$web_rec['web_folder'], $php_open_basedir);
			$php_open_basedir = str_replace("[website_path]", $document_root, $php_open_basedir);
			$php_open_basedir = str_replace("[website_domain]", $web_rec['domain'], $php_open_basedir);
			$htaccess_allow_override = $this->parent_domain_record['allow_override'];
			$added_by = $_SESSION['s']['user']['username'];

			$sql = "UPDATE web_domain SET sys_groupid = ?, system_user = ?, system_group = ?, document_root = ?, allow_override = ?, php_open_basedir = ?, added_date = CURDATE(), added_by = ?, log_retention = ?, php_fpm_chroot = ? WHERE domain_id = ?";
			$app->db->query($sql, $this->parent_domain_record['sys_groupid'], $system_user, $system_group, $document_root, $htaccess_allow_override, $php_open_basedir, $added_by, $log_retention, $php_fpm_chroot, $this->id);
		}
		if(isset($this->dataRecord['folder_directive_snippets'])) $app->db->query("UPDATE web_domain SET folder_directive_snippets = ? WHERE domain_id = ?", $this->dataRecord['folder_directive_snippets'], $this->id);

		// Add a datalog insert without letsencrypt and then an update with letsencrypt enabled (see also onBeforeInsert)
		if($this->_letsencrypt_on_insert == true) {
			$new_data_record = $app->tform->getDataRecord($this->id);
			$app->tform->datalogSave('INSERT', $this->id, array(), $new_data_record);
			$new_data_record['ssl_letsencrypt'] = 'y';
			$new_data_record['ssl'] = 'y';
			$app->db->datalogUpdate('web_domain', $new_data_record, 'domain_id', $this->id);
		}
	
	}

	function onBeforeUpdate () {
		global $app, $conf;

		if($this->_vhostdomain_type == 'domain') {
			//* Check if the server has been changed
			// We do this only for the admin or reseller users, as normal clients can not change the server ID anyway
			if($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
				if (isset($this->dataRecord["server_id"])) {
					$rec = $app->db->queryOneRecord("SELECT server_id from web_domain WHERE domain_id = ?", $this->id);
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
				$rec = $app->db->queryOneRecord("SELECT sys_perm_group, domain, ip_address, ipv6_address from web_domain WHERE domain_id = ?", $this->id);
				if(isset($this->dataRecord["domain"]) && $rec['domain'] != $this->dataRecord["domain"] && !$app->tform->checkPerm($this->id, 'u')) {
					//* Add a error message and switch back to old server
					$app->tform->errorMessage .= $app->lng('The Domain can not be changed. Please ask your Administrator if you want to change the domain name.');
					$this->dataRecord["domain"] = $rec['domain'];
				}
				if(isset($this->dataRecord["ip_address"]) && $rec['ip_address'] != $this->dataRecord["ip_address"] && $rec['sys_perm_group'] != 'riud') {
					//* Add a error message and switch back to old server
					$app->tform->errorMessage .= $app->lng('The IP can not be changed. Please ask your Administrator if you want to change the IPv4-Address.');
					$this->dataRecord["ip_address"] = $rec['ip_address'];
				}
				if(isset($this->dataRecord["ipv6_address"]) && $rec['ipv6_address'] != $this->dataRecord["ipv6_address"] && $rec['sys_perm_group'] != 'riud') {
					//* Add a error message and switch back to old server
					$app->tform->errorMessage .= $app->lng('The IP can not be changed. Please ask your Administrator if you want to change the IPv6-Address.');
					$this->dataRecord["ipv6_address"] = $rec['ipv6_address'];
				}
				unset($rec);
			}
		}

		//* Check that all fields for the SSL cert creation are filled
		if(isset($this->dataRecord['ssl_action']) && $this->dataRecord['ssl_action'] == 'create') {
			if($this->dataRecord['ssl_country'] == '') $app->tform->errorMessage .= $app->tform->lng('error_ssl_country_empty').'<br />';
		}

		if(isset($this->dataRecord['ssl_action']) && $this->dataRecord['ssl_action'] == 'save') {
			if(trim($this->dataRecord['ssl_cert']) == '') $app->tform->errorMessage .= $app->tform->lng('error_ssl_cert_empty').'<br />';
		}

	}
	
	function onAfterUpdate() {
		global $app, $conf;

		if(isset($this->dataRecord['folder_directive_snippets'])) $app->db->query("UPDATE web_domain SET folder_directive_snippets = ? WHERE domain_id = ?", $this->dataRecord['folder_directive_snippets'], $this->id);
	}
	
	function validateDefaultFastcgiPhpVersion() {
		global $app;

		// If PHP is not enabled, we don't need to validate the default PHP version
		if (empty($this->dataRecord['php']) || 'no' === $this->dataRecord['php']) {
			return;
		}

		// If the default PHP version is not hidden, we don't need to do any additional validation
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($this->dataRecord['server_id'], 'web');
		if (empty($web_config['php_default_hide']) || 'n' === $web_config['php_default_hide']) {
			return;
		}

		// The default PHP version is indicated by an empty string, so if the default PHP version is hidden
		// then an empty string is not a valid PHP version.
		if (empty($this->dataRecord['fastcgi_php_version'])) {
			$app->tform->errorMessage .= sprintf('%s<br>', $app->tform->lng('fastcgi_php_version_invalid_txt'));
			return;
		}
		
		// If the default PHP version is now hidden but this vhost was using it, we don't want to implicitly
		// switch the user to some random Additional PHP version. So we show a warning instead.
		$old_fastcgi_php_version = null;
		if ($this->id > 0) {
			$existing = $app->db->queryOneRecord('SELECT fastcgi_php_version FROM web_domain WHERE domain_id = ?', $this->id);
			$old_fastcgi_php_version = $existing['fastcgi_php_version'];
		}

		if ('' === $old_fastcgi_php_version) {
			// Warning was already shown, user confirmed the new PHP version
			if (!empty($_POST['fastcgi_php_version_default_hidden_warning_confirmed'])) {
				return;
			}

			$app->tform->errorMessage .= sprintf('%s<br>', $app->tform->lng('fastcgi_php_version_default_hidden_warning_txt'));
			$app->tpl->setVar('fastcgi_php_version_default_hidden_warning_confirmed', 1);
		}
	}
}

$page = new page_action;
$page->onLoad();

?>
