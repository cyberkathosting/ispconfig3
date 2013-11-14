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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('sites');

// Loading classes
$app->uses('tpl,tform,tform_actions,tools_sites');
$app->load('tform_actions');

class page_action extends tform_actions {

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

	function onShowNew() {
		global $app, $conf;

		// we will check only users, not admins
		if($_SESSION["s"]["user"]["typ"] == 'user') {
			if(!$app->tform->checkClientLimit('limit_web_domain', "type = 'vhost'")) {
				$app->error($app->tform->wordbook["limit_web_domain_txt"]);
			}
			if(!$app->tform->checkResellerLimit('limit_web_domain', "type = 'vhost'")) {
				$app->error('Reseller: '.$app->tform->wordbook["limit_web_domain_txt"]);
			}

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.default_webserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			$app->tpl->setVar("server_id_value", $client['default_webserver']);
		}
		$app->tform->formDef['tabs']['domain']['readonly'] = false;

		parent::onShowNew();
	}

	function onShowEnd() {
		global $app, $conf;

		$app->uses('ini_parser,getconf');

		$read_limits = array('limit_cgi', 'limit_ssi', 'limit_perl', 'limit_ruby', 'limit_python', 'force_suexec', 'limit_hterror', 'limit_wildcard', 'limit_ssl');

		//* Client: If the logged in user is not admin and has no sub clients (no reseller)
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.limit_web_domain, client.default_webserver, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			//* Get global web config
			$web_config = $app->getconf->get_server_config($client['default_webserver'], 'web');

			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ".intval($client['default_webserver']));
			$app->tpl->setVar("server_id", "<option value='$client[default_webserver]'>$tmp[server_name]</option>");
			unset($tmp);

			//* Fill the IPv4 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".$client['default_webserver']." AND ip_type = 'IPv4' AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = ($web_config['enable_ip_wildcard'] == 'y')?"<option value='*'>*</option>":"";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address", $ip_select);
			unset($tmp);
			unset($ips);

			//* Fill the IPv6 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".intval($client['default_webserver'])." AND ip_type = 'IPv6' AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = "<option value=''></option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ipv6_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ipv6_address", $ip_select);
			unset($tmp);
			unset($ips);

			//PHP Version Selection (FastCGI)
			$server_type = 'apache';
			if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
			if($server_type == 'nginx' && $this->dataRecord['php'] == 'fast-cgi') $this->dataRecord['php'] = 'php-fpm';
			if($this->dataRecord['php'] == 'php-fpm'){
				$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ".($this->id > 0 ? $this->dataRecord['server_id'] : intval($client['default_webserver']))." AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")");
			}
			if($this->dataRecord['php'] == 'fast-cgi'){
				$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ".($this->id > 0 ? $this->dataRecord['server_id'] : intval($client['default_webserver']))." AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")");
			}
			$php_select = "<option value=''>Default</option>";
			if(is_array($php_records) && !empty($php_records)) {
				foreach( $php_records as $php_record) {
					if($this->dataRecord['php'] == 'php-fpm'){
						$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					} else {
						$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					}
					$selected = ($php_version == $this->dataRecord["fastcgi_php_version"])?'SELECTED':'';
					$php_select .= "<option value='$php_version' $selected>".$php_record['name']."</option>\r\n";
				}
			}
			$app->tpl->setVar("fastcgi_php_version", $php_select);
			unset($php_records);

			// add limits to template to be able to hide settings
			foreach($read_limits as $limit) $app->tpl->setVar($limit, $client[$limit]);


			//* Reseller: If the logged in user is not admin and has sub clients (is a reseller)
		} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client.client_id, client.limit_web_domain, client.default_webserver, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			//* Get global web config
			$web_config = $app->getconf->get_server_config($client['default_webserver'], 'web');

			// Set the webserver to the default server of the client
			$tmp = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ".intval($client['default_webserver']));
			$app->tpl->setVar("server_id", "<option value='$client[default_webserver]'>$tmp[server_name]</option>");
			unset($tmp);

			// Fill the client select field
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$client['client_id']." ORDER BY sys_group.name";
			$records = $app->db->queryAllRecords($sql);
			$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$client['client_id']);
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

			//* Fill the IPv4 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".intval($client['default_webserver'])." AND ip_type = 'IPv4' AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = ($web_config['enable_ip_wildcard'] == 'y')?"<option value='*'>*</option>":"";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address", $ip_select);
			unset($tmp);
			unset($ips);

			//* Fill the IPv6 select field with the IP addresses that are allowed for this client
			$sql = "SELECT ip_address FROM server_ip WHERE server_id = ".intval($client['default_webserver'])." AND ip_type = 'IPv6' AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id'].")";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = "<option value=''></option>";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ipv6_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ipv6_address", $ip_select);
			unset($tmp);
			unset($ips);

			//PHP Version Selection (FastCGI)
			$server_type = 'apache';
			if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
			if($server_type == 'nginx' && $this->dataRecord['php'] == 'fast-cgi') $this->dataRecord['php'] = 'php-fpm';
			$selected_client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE groupid = $selected_client_group_id");
			//$sql_where = " AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id']." OR client_id = ".intval($selected_client['client_id']).")";
			$sql_where = " AND (client_id = 0 OR client_id = ".intval($selected_client['client_id']).")";
			if($this->dataRecord['php'] == 'php-fpm'){
				$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ".($this->id > 0 ? $this->dataRecord['server_id'] : intval($client['default_webserver'])).$sql_where);
			}
			if($this->dataRecord['php'] == 'fast-cgi') {
				$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ".($this->id > 0 ? $this->dataRecord['server_id'] : intval($client['default_webserver'])).$sql_where);
			}
			$php_select = "<option value=''>Default</option>";
			if(is_array($php_records) && !empty($php_records)) {
				foreach( $php_records as $php_record) {
					if($this->dataRecord['php'] == 'php-fpm'){
						$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					} else {
						$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					}
					$selected = ($php_version == $this->dataRecord["fastcgi_php_version"])?'SELECTED':'';
					$php_select .= "<option value='$php_version' $selected>".$php_record['name']."</option>\r\n";
				}
			}
			$app->tpl->setVar("fastcgi_php_version", $php_select);
			unset($php_records);

			// add limits to template to be able to hide settings
			foreach($read_limits as $limit) $app->tpl->setVar($limit, $client[$limit]);


			//* Admin: If the logged in user is admin
		} else {

			// The user is admin, so we fill in all IP addresses of the server
			if($this->id > 0) {
				if(!isset($this->dataRecord["server_id"])){
					$tmp = $app->db->queryOneRecord("SELECT server_id FROM web_domain WHERE domain_id = ".$app->functions->intval($this->id));
					$this->dataRecord["server_id"] = $tmp["server_id"];
					unset($tmp);
				}
				$server_id = intval(@$this->dataRecord["server_id"]);
			} else {
				// Get the first server ID
				$tmp = $app->db->queryOneRecord("SELECT server_id FROM server WHERE web_server = 1 ORDER BY server_name LIMIT 0,1");
				$server_id = intval($tmp['server_id']);
			}

			//* get global web config
			$web_config = $app->getconf->get_server_config($server_id, 'web');

			//* Fill the IPv4 select field
			$sql = "SELECT ip_address FROM server_ip WHERE ip_type = 'IPv4' AND server_id = $server_id";
			$ips = $app->db->queryAllRecords($sql);
			$ip_select = ($web_config['enable_ip_wildcard'] == 'y')?"<option value='*'>*</option>":"";
			//$ip_select = "";
			if(is_array($ips)) {
				foreach( $ips as $ip) {
					$selected = ($ip["ip_address"] == $this->dataRecord["ip_address"])?'SELECTED':'';
					$ip_select .= "<option value='$ip[ip_address]' $selected>$ip[ip_address]</option>\r\n";
				}
			}
			$app->tpl->setVar("ip_address", $ip_select);
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
			$app->tpl->setVar("ipv6_address", $ip_select);
			unset($tmp);
			unset($ips);

			// Fill the client select field
			$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY sys_group.name";
			$clients = $app->db->queryAllRecords($sql);
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

			//PHP Version Selection (FastCGI)
			$server_type = 'apache';
			if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
			if($server_type == 'nginx' && $this->dataRecord['php'] == 'fast-cgi') $this->dataRecord['php'] = 'php-fpm';
			$selected_client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE groupid = $selected_client_group_id");
			//$sql_where = " AND (client_id = 0 OR client_id=".$_SESSION['s']['user']['client_id']." OR client_id = ".intval($selected_client['client_id']).")";
			$sql_where = " AND (client_id = 0 OR client_id = ".intval($selected_client['client_id']).")";
			if($this->dataRecord['php'] == 'php-fpm'){
				$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = $server_id".$sql_where);
			}
			if($this->dataRecord['php'] == 'fast-cgi') {
				$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = $server_id".$sql_where);
			}
			$php_select = "<option value=''>Default</option>";
			if(is_array($php_records) && !empty($php_records)) {
				foreach( $php_records as $php_record) {
					if($this->dataRecord['php'] == 'php-fpm'){
						$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
					} else {
						$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
					}
					$selected = ($php_version == $this->dataRecord["fastcgi_php_version"])?'SELECTED':'';
					$php_select .= "<option value='$php_version' $selected>".$php_record['name']."</option>\r\n";
				}
			}
			$app->tpl->setVar("fastcgi_php_version", $php_select);
			unset($php_records);

			foreach($read_limits as $limit) $app->tpl->setVar($limit, ($limit == 'force_suexec' ? 'n' : 'y'));

			// Directive Snippets
			$php_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'php' AND active = 'y'");
			$php_directive_snippets_txt = '';
			if(is_array($php_directive_snippets) && !empty($php_directive_snippets)){
				foreach($php_directive_snippets as $php_directive_snippet){
					$php_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$php_directive_snippet['name'].']<pre class="addPlaceholderContent" style="display:none;">'.htmlentities($php_directive_snippet['snippet']).'</pre></a> ';
				}
			}
			if($php_directive_snippets_txt == '') $php_directive_snippets_txt = '------';
			$app->tpl->setVar("php_directive_snippets_txt", $php_directive_snippets_txt);

			if($server_type == 'apache'){
				$apache_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'apache' AND active = 'y'");
				$apache_directive_snippets_txt = '';
				if(is_array($apache_directive_snippets) && !empty($apache_directive_snippets)){
					foreach($apache_directive_snippets as $apache_directive_snippet){
						$apache_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$apache_directive_snippet['name'].']<pre class="addPlaceholderContent" style="display:none;">'.htmlentities($apache_directive_snippet['snippet']).'</pre></a> ';
					}
				}
				if($apache_directive_snippets_txt == '') $apache_directive_snippets_txt = '------';
				$app->tpl->setVar("apache_directive_snippets_txt", $apache_directive_snippets_txt);
			}

			if($server_type == 'nginx'){
				$nginx_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'nginx' AND active = 'y'");
				$nginx_directive_snippets_txt = '';
				if(is_array($nginx_directive_snippets) && !empty($nginx_directive_snippets)){
					foreach($nginx_directive_snippets as $nginx_directive_snippet){
						$nginx_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$nginx_directive_snippet['name'].']<pre class="addPlaceholderContent" style="display:none;">'.htmlentities($nginx_directive_snippet['snippet']).'</pre></a> ';
					}
				}
				if($nginx_directive_snippets_txt == '') $nginx_directive_snippets_txt = '------';
				$app->tpl->setVar("nginx_directive_snippets_txt", $nginx_directive_snippets_txt);
			}

			$proxy_directive_snippets = $app->db->queryAllRecords("SELECT * FROM directive_snippets WHERE type = 'proxy' AND active = 'y'");
			$proxy_directive_snippets_txt = '';
			if(is_array($proxy_directive_snippets) && !empty($proxy_directive_snippets)){
				foreach($proxy_directive_snippets as $proxy_directive_snippet){
					$proxy_directive_snippets_txt .= '<a href="javascript:void(0);" class="addPlaceholderContent">['.$proxy_directive_snippet['name'].']<pre class="addPlaceholderContent" style="display:none;">'.htmlentities($proxy_directive_snippet['snippet']).'</pre></a> ';
				}
			}
			if($proxy_directive_snippets_txt == '') $proxy_directive_snippets_txt = '------';
			$app->tpl->setVar("proxy_directive_snippets_txt", $proxy_directive_snippets_txt);
		}

		$ssl_domain_select = '';
		$tmp = $app->db->queryOneRecord("SELECT domain FROM web_domain WHERE domain_id = ".$this->id);
		$ssl_domains = array($tmp["domain"], 'www.'.$tmp["domain"], '*.'.$tmp["domain"]);
		if(is_array($ssl_domains)) {
			foreach( $ssl_domains as $ssl_domain) {
				$selected = ($ssl_domain == $this->dataRecord['ssl_domain'])?'SELECTED':'';
				$ssl_domain_select .= "<option value='$ssl_domain' $selected>$ssl_domain</option>\r\n";
			}
		}
		$app->tpl->setVar("ssl_domain", $ssl_domain_select);
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
			$domains = $app->tools_sites->getDomainModuleDomains();
			$domain_select = '';
			if(is_array($domains) && sizeof($domains) > 0) {
				/* We have domains in the list, so create the drop-down-list */
				foreach( $domains as $domain) {
					$domain_select .= "<option value=" . $domain['domain_id'] ;
					if ($domain['domain'] == $this->dataRecord["domain"]) {
						$domain_select .= " selected";
					}
					$domain_select .= ">" . $app->functions->idn_decode($domain['domain']) . "</option>\r\n";
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
		}

		// check for configuration errors in sys_datalog
		if($this->id > 0) {
			$datalog = $app->db->queryOneRecord("SELECT sys_datalog.error, sys_log.tstamp FROM sys_datalog, sys_log WHERE sys_datalog.dbtable = 'web_domain' AND sys_datalog.dbidx = 'domain_id:".$this->id."' AND sys_datalog.datalog_id = sys_log.datalog_id AND sys_log.message = CONCAT('Processed datalog_id ',sys_log.datalog_id) ORDER BY sys_datalog.tstamp DESC");
			if(is_array($datalog) && !empty($datalog)){
				if(trim($datalog['error']) != ''){
					$app->tpl->setVar("config_error_msg", nl2br(htmlentities($datalog['error'])));
					$app->tpl->setVar("config_error_tstamp", date($app->lng('conf_format_datetime'), $datalog['tstamp']));
				}
			}
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

		/* check if the domain module is used - and check if the selected domain can be used! */
		if($app->tform->getCurrentTab() == 'domain') {
			$app->uses('ini_parser,getconf');
			$settings = $app->getconf->get_global_config('domains');
			if ($settings['use_domain_module'] == 'y') {
				$domain_check = $app->tools_sites->checkDomainModuleDomain($this->dataRecord['domain']);
				if(!$domain_check) {
					// invalid domain selected
					$app->tform->errorMessage .= $app->tform->lng("domain_error_empty")."<br />";
				} else {
					$this->dataRecord['domain'] = $domain_check;
				}
			}
		}

		// nginx: if redirect type is proxy and redirect path is no URL, display error
		//if($this->dataRecord["redirect_type"] == 'proxy' && substr($this->dataRecord['redirect_path'],0,1) == '/'){
		// $app->tform->errorMessage .= $app->tform->lng("error_proxy_requires_url")."<br />";
		//}

		// Set a few fixed values
		$this->dataRecord["parent_domain_id"] = 0;
		$this->dataRecord["type"] = 'vhost';
		$this->dataRecord["vhost_type"] = 'name';

		$read_limits = array('limit_cgi', 'limit_ssi', 'limit_perl', 'limit_ruby', 'limit_python', 'force_suexec', 'limit_hterror', 'limit_wildcard', 'limit_ssl');


		if($_SESSION["s"]["user"]["typ"] != 'admin') {
			// Get the limits of the client
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT limit_traffic_quota, limit_web_domain, default_webserver, parent_client_id, limit_web_quota, client." . implode(", client.", $read_limits) . " FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

			if($client['limit_cgi'] != 'y') $this->dataRecord['cgi'] = '-';
			if($client['limit_ssi'] != 'y') $this->dataRecord['ssi'] = '-';
			if($client['limit_perl'] != 'y') $this->dataRecord['perl'] = '-';
			if($client['limit_ruby'] != 'y') $this->dataRecord['ruby'] = '-';
			if($client['limit_python'] != 'y') $this->dataRecord['python'] = '-';
			if($client['force_suexec'] == 'y') $this->dataRecord['suexec'] = 'y';
			if($client['limit_hterror'] != 'y') $this->dataRecord['errordocs'] = '-';
			if($client['limit_wildcard'] != 'y' && $this->dataRecord['subdomain'] == '*') $this->dataRecord['subdomain'] = '-';
			if($client['limit_ssl'] != 'y') $this->dataRecord['ssl'] = '-';

			// only generate quota and traffic warnings if value has changed
			if($this->id > 0) {
				$old_web_values = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".$app->functions->intval($this->id));
			} else {
				$old_web_values = $_POST;
			}

			//* Check the website quota of the client
			if(isset($_POST["hd_quota"]) && $client["limit_web_quota"] >= 0 && $_POST["hd_quota"] != $old_web_values["hd_quota"]) {
				$tmp = $app->db->queryOneRecord("SELECT sum(hd_quota) as webquota FROM web_domain WHERE domain_id != ".$app->functions->intval($this->id)." AND type = 'vhost' AND ".$app->tform->getAuthSQL('u'));
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

			//* Check the traffic quota of the client
			if(isset($_POST["traffic_quota"]) && $client["limit_traffic_quota"] > 0 && $_POST["traffic_quota"] != $old_web_values["traffic_quota"]) {
				$tmp = $app->db->queryOneRecord("SELECT sum(traffic_quota) as trafficquota FROM web_domain WHERE domain_id != ".$app->functions->intval($this->id)." AND ".$app->tform->getAuthSQL('u'));
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
				$reseller = $app->db->queryOneRecord("SELECT limit_traffic_quota, limit_web_domain, default_webserver, limit_web_quota FROM client WHERE client_id = ".$client['parent_client_id']);

				//* Check the website quota of the client
				if(isset($_POST["hd_quota"]) && $reseller["limit_web_quota"] >= 0 && $_POST["hd_quota"] != $old_web_values["hd_quota"]) {
					$tmp = $app->db->queryOneRecord("SELECT sum(hd_quota) as webquota FROM web_domain WHERE domain_id != ".$app->functions->intval($this->id)." AND type = 'vhost' AND ".$app->tform->getAuthSQL('u'));
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

				//* Check the traffic quota of the client
				if(isset($_POST["traffic_quota"]) && $reseller["limit_traffic_quota"] > 0 && $_POST["traffic_quota"] != $old_web_values["traffic_quota"]) {
					$tmp = $app->db->queryOneRecord("SELECT sum(traffic_quota) as trafficquota FROM web_domain WHERE domain_id != ".$app->functions->intval($this->id)." AND ".$app->tform->getAuthSQL('u'));
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
				$tmp = $app->db->queryOneRecord("SELECT server_id, `cgi`, `ssi`, `perl`, `ruby`, `python`, `suexec`, `errordocs`, `subdomain`, `ssl` FROM web_domain WHERE domain_id = ".$app->functions->intval($this->id));
				$this->dataRecord["server_id"] = $tmp["server_id"];

				// set the settings to current if not provided (or cleared due to limits)
				if($this->dataRecord['cgi'] == '-') $this->dataRecord['cgi'] = $tmp['cgi'];
				if($this->dataRecord['ssi'] == '-') $this->dataRecord['ssi'] = $tmp['ssi'];
				if($this->dataRecord['perl'] == '-') $this->dataRecord['perl'] = $tmp['perl'];
				if($this->dataRecord['ruby'] == '-') $this->dataRecord['ruby'] = $tmp['ruby'];
				if($this->dataRecord['python'] == '-') $this->dataRecord['python'] = $tmp['python'];
				if($this->dataRecord['suexec'] == '-') $this->dataRecord['suexec'] = $tmp['suexec'];
				if($this->dataRecord['errordocs'] == '-') $this->dataRecord['errordocs'] = $tmp['errordocs'];
				if($this->dataRecord['subdomain'] == '-') $this->dataRecord['subdomain'] = $tmp['subdomain'];
				if($this->dataRecord['ssl'] == '-') $this->dataRecord['ssl'] = $tmp['ssl'];

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
			$sql = "SELECT count(domain_id) as number FROM web_domain WHERE `ssl` = 'y' AND ip_address = '".$app->db->quote($this->dataRecord['ip_address'])."' and domain_id != ".$this->id;
			$tmp = $app->db->queryOneRecord($sql);
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

		parent::onSubmit();
	}

	function onAfterInsert() {
		global $app, $conf;

		// make sure that the record belongs to the clinet group and not the admin group when admin inserts it
		// also make sure that the user can not delete domain created by a admin
		if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE domain_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE domain_id = ".$this->id);
		}

		// Get configuration for the web system
		$app->uses("getconf");
		$web_rec = $app->tform->getDataRecord($this->id);
		$web_config = $app->getconf->get_server_config($app->functions->intval($web_rec["server_id"]), 'web');
		$document_root = str_replace("[website_id]", $this->id, $web_config["website_path"]);
		$document_root = str_replace("[website_idhash_1]", $this->id_hash($page_form->id, 1), $document_root);
		$document_root = str_replace("[website_idhash_2]", $this->id_hash($page_form->id, 1), $document_root);
		$document_root = str_replace("[website_idhash_3]", $this->id_hash($page_form->id, 1), $document_root);
		$document_root = str_replace("[website_idhash_4]", $this->id_hash($page_form->id, 1), $document_root);

		// get the ID of the client
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = $client_group_id");
			$client_id = $app->functions->intval($client["client_id"]);
		} else {
			//$client_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ".$app->functions->intval($this->dataRecord["client_group_id"]));
			$client_id = $app->functions->intval($client["client_id"]);
		}

		// Set the values for document_root, system_user and system_group
		$system_user = $app->db->quote('web'.$this->id);
		$system_group = $app->db->quote('client'.$client_id);
		$document_root = str_replace("[client_id]", $client_id, $document_root);
		$document_root = str_replace("[client_idhash_1]", $this->id_hash($client_id, 1), $document_root);
		$document_root = str_replace("[client_idhash_2]", $this->id_hash($client_id, 2), $document_root);
		$document_root = str_replace("[client_idhash_3]", $this->id_hash($client_id, 3), $document_root);
		$document_root = str_replace("[client_idhash_4]", $this->id_hash($client_id, 4), $document_root);
		$document_root = $app->db->quote($document_root);
		$php_open_basedir = str_replace("[website_path]", $document_root, $web_config["php_open_basedir"]);
		$php_open_basedir = $app->db->quote(str_replace("[website_domain]", $web_rec['domain'], $php_open_basedir));
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
			$rec = $app->db->queryOneRecord("SELECT sys_perm_group, domain, ip_address, ipv6_address from web_domain WHERE domain_id = ".$this->id);
			if(isset($this->dataRecord["domain"]) && $rec['domain'] != $this->dataRecord["domain"] && $app->tform->checkPerm($this->id, 'u')) {
				//* Add a error message and switch back to old server
				$app->tform->errorMessage .= $app->lng('The Domain can not be changed. Please ask your Administrator if you want to change the domain name.');
				$this->dataRecord["domain"] = $rec['domain'];
			}
			if(isset($this->dataRecord["ip_address"]) && $rec['ip_address'] != $this->dataRecord["ip_address"] && $rec['sys_perm_group'] != 'riud') {
				$this->dataRecord["ip_address"] = $rec['ip_address'];
			}
			if(isset($this->dataRecord["ipv6_address"]) && $rec['ipv6_address'] != $this->dataRecord["ipv6_address"] && $rec['sys_perm_group'] != 'riud') {
				$this->dataRecord["ipv6_address"] = $rec['ipv6_address'];
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
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'ru' WHERE domain_id = ".$this->id);
		}
		if($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $app->functions->intval($this->dataRecord["client_group_id"]);
			$app->db->query("UPDATE web_domain SET sys_groupid = $client_group_id, sys_perm_group = 'riud' WHERE domain_id = ".$this->id);
		}

		// Get configuration for the web system
		$app->uses("getconf");
		$web_rec = $app->tform->getDataRecord($this->id);
		$web_config = $app->getconf->get_server_config($app->functions->intval($web_rec["server_id"]), 'web');
		$document_root = str_replace("[website_id]", $this->id, $web_config["website_path"]);
		$page_formid = isset($page_form->id) ? $page_form->id : '';
		$document_root = str_replace("[website_idhash_1]", $this->id_hash($page_formid, 1), $document_root);
		$document_root = str_replace("[website_idhash_2]", $this->id_hash($page_formid, 1), $document_root);
		$document_root = str_replace("[website_idhash_3]", $this->id_hash($page_formid, 1), $document_root);
		$document_root = str_replace("[website_idhash_4]", $this->id_hash($page_formid, 1), $document_root);

		// get the ID of the client
		if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
			$client_group_id = $_SESSION["s"]["user"]["default_group"];
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = $client_group_id");
			$client_id = $app->functions->intval($client["client_id"]);
		} elseif (isset($this->dataRecord["client_group_id"])) {
			$client_group_id = $this->dataRecord["client_group_id"];
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ".$app->functions->intval(@$this->dataRecord["client_group_id"]));
			$client_id = $app->functions->intval($client["client_id"]);
		} else {
			$client_group_id = $web_rec['sys_groupid'];
			$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ".$app->functions->intval($client_group_id));
			$client_id = $app->functions->intval($client["client_id"]);
		}

		if(($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) &&  isset($this->dataRecord["client_group_id"]) && $this->dataRecord["client_group_id"] != $this->oldDataRecord["sys_groupid"]) {
			// Set the values for document_root, system_user and system_group
			$system_user = $app->db->quote('web'.$this->id);
			$system_group = $app->db->quote('client'.$client_id);
			$document_root = str_replace("[client_id]", $client_id, $document_root);
			$document_root = str_replace("[client_idhash_1]", $this->id_hash($client_id, 1), $document_root);
			$document_root = str_replace("[client_idhash_2]", $this->id_hash($client_id, 2), $document_root);
			$document_root = str_replace("[client_idhash_3]", $this->id_hash($client_id, 3), $document_root);
			$document_root = str_replace("[client_idhash_4]", $this->id_hash($client_id, 4), $document_root);
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
			$records = $app->db->queryAllRecords("SELECT domain_id, `domain`, `type`, `web_folder` FROM web_domain WHERE parent_domain_id = ".$this->id);
			foreach($records as $rec) {
				$update_columns = "sys_userid = '".$web_rec['sys_userid']."', sys_groupid = '".$web_rec['sys_groupid']."'";
				if($rec['type'] == 'vhostsubdomain') {
					$php_open_basedir = str_replace("[website_path]/web", $document_root.'/'.$rec['web_folder'], $web_config["php_open_basedir"]);
					$php_open_basedir = str_replace("[website_domain]/web", $rec['domain'].'/'.$rec['web_folder'], $php_open_basedir);
					$php_open_basedir = str_replace("[website_path]", $document_root, $php_open_basedir);
					$php_open_basedir = $app->db->quote(str_replace("[website_domain]", $rec['domain'], $php_open_basedir));

					$update_columns .= ", document_root = '".$document_root."', `php_open_basedir` = '".$php_open_basedir."'";
				}
				$app->db->datalogUpdate('web_domain', $update_columns, 'domain_id', $rec['domain_id']);
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

		//* If the domain name has been changed, we will have to change all subdomains + APS instances
		if(!empty($this->dataRecord["domain"]) && !empty($this->oldDataRecord["domain"]) && $this->dataRecord["domain"] != $this->oldDataRecord["domain"]) {
			$records = $app->db->queryAllRecords("SELECT domain_id,domain FROM web_domain WHERE (type = 'subdomain' OR type = 'vhostsubdomain') AND domain LIKE '%.".$app->db->quote($this->oldDataRecord["domain"])."'");
			foreach($records as $rec) {
				$subdomain = $app->db->quote(str_replace($this->oldDataRecord["domain"], $this->dataRecord["domain"], $rec['domain']));
				$app->db->datalogUpdate('web_domain', "domain = '".$subdomain."'", 'domain_id', $rec['domain_id']);
			}
			unset($records);
			unset($rec);
			unset($subdomain);

			// Update APS instances
			$records = $app->db->queryAllRecords("SELECT id, instance_id FROM aps_instances_settings WHERE name = 'main_domain' AND value = '".$this->oldDataRecord["domain"]."'");
			if(is_array($records) && !empty($records)){
				foreach($records as $rec){
					$app->db->datalogUpdate('aps_instances_settings', "value = '".$this->dataRecord["domain"]."'", 'id', $rec['id']);
					// Reinstall of package needed?
					//$app->db->datalogUpdate('aps_instances', "instance_status = '1'", 'id', $rec['instance_id']);
				}
			}
			unset($records);
			unset($rec);
		}

		//* Set allow_override if empty
		if($web_rec['allow_override'] == '') {
			$sql = "UPDATE web_domain SET allow_override = '".$app->db->quote($web_config["htaccess_allow_override"])."' WHERE domain_id = ".$this->id;
			$app->db->query($sql);
		}

		//* Set php_open_basedir if empty or domain or client has been changed
		if(empty($web_rec['php_open_basedir']) ||
			(!empty($this->dataRecord["domain"]) && !empty($this->oldDataRecord["domain"]) && $this->dataRecord["domain"] != $this->oldDataRecord["domain"])) {
			$php_open_basedir = $web_rec['php_open_basedir'];
			$php_open_basedir = str_replace($this->oldDataRecord['domain'], $web_rec['domain'], $php_open_basedir);
			$sql = "UPDATE web_domain SET php_open_basedir = '$php_open_basedir' WHERE domain_id = ".$this->id;
			$app->db->query($sql);
		}
		if(empty($web_rec['php_open_basedir']) ||
			(isset($this->dataRecord["client_group_id"]) && $this->dataRecord["client_group_id"] != $this->oldDataRecord["sys_groupid"])) {
			$document_root = $app->db->quote(str_replace("[client_id]", $client_id, $document_root));
			$php_open_basedir = str_replace("[website_path]", $document_root, $web_config["php_open_basedir"]);
			$php_open_basedir = $app->db->quote(str_replace("[website_domain]", $web_rec['domain'], $php_open_basedir));
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

		//* Change vhost subdomain ip/ipv6 if domain ip/ipv6 has changed
		if(isset($this->dataRecord['ip_address']) && ($this->dataRecord['ip_address'] != $this->oldDataRecord['ip_address'] || $this->dataRecord['ipv6_address'] != $this->oldDataRecord['ipv6_address'])) {
			$records = $app->db->queryAllRecords("SELECT domain_id FROM web_domain WHERE type = 'vhostsubdomain' AND parent_domain_id = ".$this->id);
			foreach($records as $rec) {
				$app->db->datalogUpdate('web_domain', "ip_address = '".$web_rec['ip_address']."', ipv6_address = '".$web_rec['ipv6_address']."'", 'domain_id', $rec['domain_id']);
			}
			unset($records);
			unset($rec);
		}
	}

	function onAfterDelete() {
		global $app, $conf;

		// Delete the sub and alias domains
		$child_domains = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE parent_domain_id = ".$this->id);
		foreach($child_domains as $d) {
			// Saving record to datalog when db_history enabled
			if($app->tform->formDef["db_history"] == 'yes') {
				$app->tform->datalogSave('DELETE', $d["domain_id"], $d, array());
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
