<?php

/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('dns');

$msg = '';
$error = '';

// Loading the template
$app->uses('tpl,validate_dns');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/dns_import.htm');
$app->load_language_file('/web/dns/lib/lang/'.$_SESSION['s']['language'].'_dns_wizard.lng');

// import variables
$template_id = (isset($_POST['template_id']))?$app->functions->intval($_POST['template_id']):0;
$sys_groupid = (isset($_POST['client_group_id']))?$app->functions->intval($_POST['client_group_id']):0;
$domain = (isset($_POST['domain'])&&!empty($_POST['domain']))?$_POST['domain']:NULL;

// get the correct server_id
if (isset($_POST['server_id'])) {
	$server_id = $app->functions->intval($_POST['server_id']);
	$post_server_id = true;
} elseif (isset($_POST['server_id_value'])) {
	$server_id = $app->functions->intval($_POST['server_id_value']);
	$post_server_id = true;
} else {
	$server_id = 1;
	$post_server_id = false;
}


// Load the templates
$records = $app->db->queryAllRecords("SELECT * FROM dns_template WHERE visible = 'Y'");
$template_id_option = '';
$n = 0;
foreach($records as $rec){
	$checked = ($rec['template_id'] == $template_id)?' SELECTED':'';
	$template_id_option .= '<option value="'.$rec['template_id'].'"'.$checked.'>'.$rec['name'].'</option>';
	if($n == 0 && $template_id == 0) $template_id = $rec['template_id'];
	$n++;
}
unset($n);
$app->tpl->setVar("template_id_option", $template_id_option);

// If the user is administrator
if($_SESSION['s']['user']['typ'] == 'admin') {

	// Load the list of servers
	$records = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE mirror_server_id = 0 AND dns_server = 1 ORDER BY server_name");
	$server_id_option = '';
	foreach($records as $rec){
		$checked = ($rec['server_id'] == $server_id)?' SELECTED':'';
		$server_id_option .= '<option value="'.$rec['server_id'].'"'.$checked.'>'.$rec['server_name'].'</option>';
	}
	$app->tpl->setVar("server_id", $server_id_option);

	// load the list of clients
	$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND sys_group.client_id > 0 ORDER BY client.company_name, client.contact_name, sys_group.name";
	$clients = $app->db->queryAllRecords($sql);
	$client_select = '';
	if($_SESSION["s"]["user"]["typ"] == 'admin') $client_select .= "<option value='0'></option>";
	if(is_array($clients)) {
		foreach( $clients as $client) {
			$selected = ($client["groupid"] == $sys_groupid)?'SELECTED':'';
			$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
		}
	}

	$app->tpl->setVar("client_group_id", $client_select);
}

if ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

	// Get the limits of the client
	$client_group_id = intval($_SESSION["s"]["user"]["default_group"]);
	$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");


	// load the list of clients
	$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".intval($client['client_id'])." ORDER BY client.company_name, client.contact_name, sys_group.name";
	$clients = $app->db->queryAllRecords($sql);
	$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".intval($client['client_id']));
	$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contactname'].'</option>';
	if(is_array($clients)) {
		foreach( $clients as $client) {
			$selected = ($client["groupid"] == $sys_groupid)?'SELECTED':'';
			$client_select .= "<option value='$client[groupid]' $selected>$client[contactname]</option>\r\n";
		}
	}

	$app->tpl->setVar("client_group_id", $client_select);
}

if($_SESSION["s"]["user"]["typ"] != 'admin')
{
	$client_group_id = $_SESSION["s"]["user"]["default_group"];
	$client_dns = $app->db->queryOneRecord("SELECT dns_servers FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

	$client_dns['dns_servers_ids'] = explode(',', $client_dns['dns_servers']);

	$only_one_server = count($client_dns['dns_servers_ids']) === 1;
	$app->tpl->setVar('only_one_server', $only_one_server);

	if ($only_one_server) {
		$app->tpl->setVar('server_id_value', $client_dns['dns_servers_ids'][0]);
	}

	$sql = "SELECT server_id, server_name FROM server WHERE server_id IN (" . $client_dns['dns_servers'] . ");";
	$dns_servers = $app->db->queryAllRecords($sql);

	$options_dns_servers = "";

	foreach ($dns_servers as $dns_server) {
		$options_dns_servers .= "<option value='$dns_server[server_id]'>$dns_server[server_name]</option>";
	}

	$app->tpl->setVar("server_id", $options_dns_servers);
	unset($options_dns_servers);

}

/*
 * Now we have to check, if we should use the domain-module to select the domain
 * or not
 */
$app->uses('ini_parser,getconf');
$settings = $app->getconf->get_global_config('domains');
if ($settings['use_domain_module'] == 'y') {
	/*
	 * The domain-module is in use.
	*/
	$domains = $app->tools_sites->getDomainModuleDomains();
	/*
	 * We can leave domain empty if domain is filename
	*/
	$domain_select = "<option value=''></option>\r\n";
	if(is_array($domains) && sizeof($domains) > 0) {
		/* We have domains in the list, so create the drop-down-list */
		foreach( $domains as $domain) {
			$domain_select .= "<option value=" . $domain['domain_id'] ;
			if ($domain['domain'] == $_POST['domain']) {
				$domain_select .= " selected";
			}
			$domain_select .= ">" . $app->functions->idn_decode($domain['domain']) . ".</option>\r\n";
		}
	}
	$app->tpl->setVar("domain_option", $domain_select);
	/* check if the selected domain can be used! */
	if ($domain) {
		$domain_check = $app->tools_sites->checkDomainModuleDomain($domain);
		if(!$domain_check) {
			// invalid domain selected
			$domain = NULL;
		} else {
			$domain = $domain_check;
		}
	}
}

$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dns_import.lng';
include $lng_file;
$app->tpl->setVar($wb);

// Import the zone-file
//if(1=="1")
if(isset($_FILES['file']['name']) && is_uploaded_file($_FILES['file']['tmp_name'])){
	$valid_zone_file = FALSE;

	$sql = "SELECT server_name FROM `server` WHERE server_id=".$app->functions->intval($server_id)." OR mirror_server_id=".$app->functions->intval($server_id)." ORDER BY server_name ASC";
	$servers = $app->db->queryAllRecords($sql);
	for ($i=0;$i<count($servers);$i++)
	{
		if (substr($servers[$i]['server_name'], strlen($servers[$i]['server_name'])-1) != ".")
		{
			$servers[$i]['server_name'] .= ".";
		}
	}
	$lines = file($_FILES['file']['tmp_name']);

	// Remove empty lines, comments, whitespace, tabs, etc.
	$new_lines = array();
	foreach($lines as $line){
		$line = trim($line);
		if ($line != '' && substr($line, 0, 1) != ';'){
			if(strpos($line, ";") !== FALSE) $line = substr($line, 0, strpos($line, ";"));
			if(strpos($line, "(") !== FALSE) $line = substr($line, 0, strpos($line, "("));
			if(strpos($line, ")") !== FALSE) $line = substr($line, 0, strpos($line, ")"));
			$line = trim($line);
			if ($line != ''){
				$sPattern = '/\s+/m';
				$sReplace = ' ';
				$new_lines[] = preg_replace($sPattern, $sReplace, $line);
			}
		}
	}
	unset($lines);
	$lines = $new_lines;
	unset($new_lines);

	//$lines = file("apriqot.se.txt");
	$name = str_replace("txt", "", $_FILES['file']['name']);
	$name = str_replace("zone", "", $name);

	if ($domain !== NULL){
		$name = $domain;
	}

	if (substr($name, -1) != "."){
		$name .= ".";
	}

	$i = 0;
	$origin_exists = FALSE;
	$soa_array_key = -1;
	$soa = array();
	$soa['name'] = $name;
	$r = 0;
	$dns_rr = array();
	foreach($lines as $line){

		$parts = explode(' ', $line);

		// make all elements lowercase
		$new_parts = array();
		foreach($parts as $part){
			$new_parts[] = strtolower($part);
		}
		unset($parts);
		$parts = $new_parts;
		unset($new_parts);

		// if ORIGIN exists, overwrite $soa['name']
		if($parts[0] == '$origin'){
			$soa['name'] = $parts[1];
			$origin_exists = TRUE;
		}
		// TTL
		if($parts[0] == '$ttl'){
			$time_format = strtolower(substr($parts[1], -1));
			switch ($time_format) {
			case 's':
				$soa['ttl'] = $app->functions->intval(substr($parts[1], 0, -1));
				break;
			case 'm':
				$soa['ttl'] = $app->functions->intval(substr($parts[1], 0, -1)) * 60;
				break;
			case 'h':
				$soa['ttl'] = $app->functions->intval(substr($parts[1], 0, -1)) * 3600;
				break;
			case 'd':
				$soa['ttl'] = $app->functions->intval(substr($parts[1], 0, -1)) * 86400;
				break;
			case 'w':
				$soa['ttl'] = $app->functions->intval(substr($parts[1], 0, -1)) * 604800;
				break;
			default:
				$soa['ttl'] = $app->functions->intval($parts[1]);
			}
			unset($time_format);
		}
		// SOA
		if(in_array("soa", $parts)){
			$soa['mbox'] = array_pop($parts);
			//$soa['ns'] = array_pop($parts);
			$soa['ns'] = $servers[0]['server_name'];
			// if domain is part of SOA, overwrite $soa['name']
			if($parts[0] != '@' && $parts[0] != 'in' && $parts[0] != 'soa' && $origin_exists === FALSE){
				$soa['name'] = $parts[0];
			}
			$soa_array_key = $i;
			$valid_zone_file = TRUE;
		}
		// SERIAL
		if($i == ($soa_array_key + 1)) $soa['serial'] = $app->functions->intval($parts[0]);
		// REFRESH
		if($i == ($soa_array_key + 2)){
			$time_format = strtolower(substr($parts[0], -1));
			switch ($time_format) {
			case 's':
				$soa['refresh'] = $app->functions->intval(substr($parts[0], 0, -1));
				break;
			case 'm':
				$soa['refresh'] = $app->functions->intval(substr($parts[0], 0, -1)) * 60;
				break;
			case 'h':
				$soa['refresh'] = $app->functions->intval(substr($parts[0], 0, -1)) * 3600;
				break;
			case 'd':
				$soa['refresh'] = $app->functions->intval(substr($parts[0], 0, -1)) * 86400;
				break;
			case 'w':
				$soa['refresh'] = $app->functions->intval(substr($parts[0], 0, -1)) * 604800;
				break;
			default:
				$soa['refresh'] = $app->functions->intval($parts[0]);
			}
			unset($time_format);
		}
		// RETRY
		if($i == ($soa_array_key + 3)){
			$time_format = strtolower(substr($parts[0], -1));
			switch ($time_format) {
			case 's':
				$soa['retry'] = $app->functions->intval(substr($parts[0], 0, -1));
				break;
			case 'm':
				$soa['retry'] = $app->functions->intval(substr($parts[0], 0, -1)) * 60;
				break;
			case 'h':
				$soa['retry'] = $app->functions->intval(substr($parts[0], 0, -1)) * 3600;
				break;
			case 'd':
				$soa['retry'] = $app->functions->intval(substr($parts[0], 0, -1)) * 86400;
				break;
			case 'w':
				$soa['retry'] = $app->functions->intval(substr($parts[0], 0, -1)) * 604800;
				break;
			default:
				$soa['retry'] = $app->functions->intval($parts[0]);
			}
			unset($time_format);
		}
		// EXPIRE
		if($i == ($soa_array_key + 4)){
			$time_format = strtolower(substr($parts[0], -1));
			switch ($time_format) {
			case 's':
				$soa['expire'] = $app->functions->intval(substr($parts[0], 0, -1));
				break;
			case 'm':
				$soa['expire'] = $app->functions->intval(substr($parts[0], 0, -1)) * 60;
				break;
			case 'h':
				$soa['expire'] = $app->functions->intval(substr($parts[0], 0, -1)) * 3600;
				break;
			case 'd':
				$soa['expire'] = $app->functions->intval(substr($parts[0], 0, -1)) * 86400;
				break;
			case 'w':
				$soa['expire'] = $app->functions->intval(substr($parts[0], 0, -1)) * 604800;
				break;
			default:
				$soa['expire'] = $app->functions->intval($parts[0]);
			}
			unset($time_format);
		}
		// MINIMUM
		if($i == ($soa_array_key + 5)){
			$time_format = strtolower(substr($parts[0], -1));
			switch ($time_format) {
			case 's':
				$soa['minimum'] = $app->functions->intval(substr($parts[0], 0, -1));
				break;
			case 'm':
				$soa['minimum'] = $app->functions->intval(substr($parts[0], 0, -1)) * 60;
				break;
			case 'h':
				$soa['minimum'] = $app->functions->intval(substr($parts[0], 0, -1)) * 3600;
				break;
			case 'd':
				$soa['minimum'] = $app->functions->intval(substr($parts[0], 0, -1)) * 86400;
				break;
			case 'w':
				$soa['minimum'] = $app->functions->intval(substr($parts[0], 0, -1)) * 604800;
				break;
			default:
				$soa['minimum'] = $app->functions->intval($parts[0]);
			}
			unset($time_format);
		}
		// RESOURCE RECORDS
		if($i > ($soa_array_key + 5)){
			if(substr($parts[0], -1) == '.' || $parts[0] == '@' || ($parts[0] != 'a' && $parts[0] != 'aaaa' && $parts[0] != 'ns' && $parts[0] != 'cname' && $parts[0] != 'hinfo' && $parts[0] != 'mx' && $parts[0] != 'naptr' && $parts[0] != 'ptr' && $parts[0] != 'rp' && $parts[0] != 'srv' && $parts[0] != 'txt')){
				if(is_numeric($parts[1])){
					if($parts[2] == 'in'){
						$resource_type = $parts[3];
						$pkey = 3;
					} else {
						$resource_type = $parts[2];
						$pkey = 2;
					}
				} else {
					if($parts[1] == 'in'){
						$resource_type = $parts[2];
						$pkey = 2;
					} else {
						$resource_type = $parts[1];
						$pkey = 1;
					}
				}
				$dns_rr[$r]['type'] = $resource_type;
				if($parts[0] == '@' || $parts[0] == '.'){
					$dns_rr[$r]['name'] = $soa['name'];
				} else {
					$dns_rr[$r]['name'] = $parts[0];
				}
				if(is_numeric($parts[1])){
					$dns_rr[$r]['ttl'] = $app->functions->intval($parts[1]);
				} else {
					$dns_rr[$r]['ttl'] = $soa['ttl'];
				}
				switch ($resource_type) {
				case 'mx':
				case 'srv':
					$dns_rr[$r]['aux'] = $app->functions->intval($parts[$pkey+1]);
					$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+2));
					break;
				case 'txt':
					$dns_rr[$r]['aux'] = 0;
					$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
					if(substr($dns_rr[$r]['data'], 0, 1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 1);
					if(substr($dns_rr[$r]['data'], -1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 0, -1);
					break;
				default:
					$dns_rr[$r]['aux'] = 0;
					$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
				}
			} else {
				// a 3600 IN A 1.2.3.4
				if(is_numeric($parts[1]) && $parts[2] == 'in' && ($parts[3] == 'a' || $parts[3] == 'aaaa' || $parts[3] == 'ns'|| $parts[3] == 'cname' || $parts[3] == 'hinfo' || $parts[3] == 'mx' || $parts[3] == 'naptr' || $parts[3] == 'ptr' || $parts[3] == 'rp' || $parts[3] == 'srv' || $parts[3] == 'txt')){
					$resource_type = $parts[3];
					$pkey = 3;
					$dns_rr[$r]['type'] = $resource_type;
					$dns_rr[$r]['name'] = $parts[0];
					$dns_rr[$r]['ttl'] = $app->functions->intval($parts[1]);
					switch ($resource_type) {
					case 'mx':
					case 'srv':
						$dns_rr[$r]['aux'] = $app->functions->intval($parts[$pkey+1]);
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+2));
						break;
					case 'txt':
						$dns_rr[$r]['aux'] = 0;
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
						if(substr($dns_rr[$r]['data'], 0, 1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 1);
						if(substr($dns_rr[$r]['data'], -1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 0, -1);
						break;
					default:
						$dns_rr[$r]['aux'] = 0;
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
					}
				}
				// a IN A 1.2.3.4
				elseif($parts[1] == 'in' && ($parts[2] == 'a' || $parts[2] == 'aaaa' || $parts[2] == 'ns'|| $parts[2] == 'cname' || $parts[2] == 'hinfo' || $parts[2] == 'mx' || $parts[2] == 'naptr' || $parts[2] == 'ptr' || $parts[2] == 'rp' || $parts[2] == 'srv' || $parts[2] == 'txt')){
					$resource_type = $parts[2];
					$pkey = 2;
					$dns_rr[$r]['type'] = $resource_type;
					$dns_rr[$r]['name'] = $parts[0];
					$dns_rr[$r]['ttl'] = $soa['ttl'];
					switch ($resource_type) {
					case 'mx':
					case 'srv':
						$dns_rr[$r]['aux'] = $app->functions->intval($parts[$pkey+1]);
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+2));
						break;
					case 'txt':
						$dns_rr[$r]['aux'] = 0;
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
						if(substr($dns_rr[$r]['data'], 0, 1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 1);
						if(substr($dns_rr[$r]['data'], -1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 0, -1);
						break;
					default:
						$dns_rr[$r]['aux'] = 0;
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
					}
				}
				// a 3600 A 1.2.3.4
				elseif(is_numeric($parts[1]) && ($parts[2] == 'a' || $parts[2] == 'aaaa' || $parts[2] == 'ns'|| $parts[2] == 'cname' || $parts[2] == 'hinfo' || $parts[2] == 'mx' || $parts[2] == 'naptr' || $parts[2] == 'ptr' || $parts[2] == 'rp' || $parts[2] == 'srv' || $parts[2] == 'txt')){
					$resource_type = $parts[2];
					$pkey = 2;
					$dns_rr[$r]['type'] = $resource_type;
					$dns_rr[$r]['name'] = $parts[0];
					$dns_rr[$r]['ttl'] = $app->functions->intval($parts[1]);
					switch ($resource_type) {
					case 'mx':
					case 'srv':
						$dns_rr[$r]['aux'] = $app->functions->intval($parts[$pkey+1]);
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+2));
						break;
					case 'txt':
						$dns_rr[$r]['aux'] = 0;
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
						if(substr($dns_rr[$r]['data'], 0, 1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 1);
						if(substr($dns_rr[$r]['data'], -1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 0, -1);
						break;
					default:
						$dns_rr[$r]['aux'] = 0;
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
					}
				}
				// A 1.2.3.4
				// MX 10 mail
				// TXT "v=spf1 a mx ptr -all"
				else {
					$resource_type = $parts[0];
					$pkey = 0;
					$dns_rr[$r]['type'] = $resource_type;
					$dns_rr[$r]['name'] = $soa['name'];
					$dns_rr[$r]['ttl'] = $soa['ttl'];
					switch ($resource_type) {
					case 'mx':
					case 'srv':
						$dns_rr[$r]['aux'] = $app->functions->intval($parts[$pkey+1]);
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+2));
						break;
					case 'txt':
						$dns_rr[$r]['aux'] = 0;
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
						if(substr($dns_rr[$r]['data'], 0, 1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 1);
						if(substr($dns_rr[$r]['data'], -1) == '"') $dns_rr[$r]['data'] = substr($dns_rr[$r]['data'], 0, -1);
						break;
					default:
						$dns_rr[$r]['aux'] = 0;
						$dns_rr[$r]['data'] = implode(' ', array_slice($parts, $pkey+1));
					}
				}
			}
			$dns_rr[$r]['type'] = strtoupper($dns_rr[$r]['type']);
			if($dns_rr[$r]['type'] == 'NS' && $dns_rr[$r]['name'] == $soa['name']){
				unset($dns_rr[$r]);
			}
			$r++;
		}
		$i++;
	}

	/*
	$i = 0;
	$r = 0;
	$s = 0;
	$dns_rr = array();
	foreach ($lines as $line)
	{
		$line = trim($line);
		if ($line != '' && substr($line,0,1) != ';' && substr($line,0,1) != '$')
		{
			$line = str_replace("\n",NULL,$line);
			$i++;

			// TODO - Find a better way to parse the SOA record. Lazy checking.
			if ($i <= 7)
			{
				if ($i > 1)
				{
					$s++;
					$line = str_replace("\t",NULL,$line);
					if (!empty($line))
					{
						print(strpos(";",$line));
						$line = substr($line,0,strpos($line,";"));
						if ($s == 1)
							$soa['serial'] = $line;
						else if ($s == 2)
							$soa['refresh'] = $line;
						else if ($s == 3)
							$soa['retry'] = $line;
						else if ($s == 4)
							$soa['expire'] = $line;
						else if ($s == 5)
							$soa['minimum'] = $line;

					}
				}
				else
				{
					$line = str_replace("\t",",",$line);
					$line = str_replace(" ",",",$line);
					$recs = explode(",",$line);

					foreach ($recs as $key => $rec)
					{
						$rec = trim($rec);
						if($rec == '') continue;
						//name	type	data	aux	ttl	active
						if ($key == 0)
						{
							if ($rec == '@')
							{
								$rec = $name;
							}

							$soa['name'] = $rec;
						}

						if ($key != 0 && strtolower($rec) == 'soa')
						{
							$typekeys[$s] = $key;
						}
						else if ($key > $typekey[$r])
						{
							if ($rec != "" && $rec != "(")
							{
								$rec = explode(" ",$rec);

								$soa['ns'] = $servers[0]['server_name'];
								$soa['mbox'] = $rec[1];
							}
						}
					}
				}
			}
			else
			{
				$line = str_replace("\n","",trim($line));

				if (!empty($line))
				{

					preg_match_all('/(.*?)\s*IN\s*(A|CNAME|MX|TXT|NS|AAAA)\s*(.*)/',$line, $recs);

					if ($recs[1][0] == '@' || trim($recs[1][0]) == "")
					{
						$recs[1][0] = $name;
					}
					$dns_rr[$r]['name'] = $recs[1][0];
					$dns_rr[$r]['type'] = $recs[2][0];
					if (strtolower($dns_rr[$r]['type'])=='mx')
					{
						$recs[3][0] = str_replace(" ","\t",$recs[3][0]);
						$mx[$r] = explode("\t",$recs[3][0]);
						for ($m=1;$m<count($mx[$r]);$m++)
						{
							if (!empty($mx[$r][$m]))
								$dns_rr[$r]['data'] = $mx[$r][$m];
						}

						$dns_rr[$r]['aux'] = $mx[$r][0];
					}
					else if (strtolower($dns_rr[$r]['type'])=='txt')
					{
						$dns_rr[$r]['data'] = substr($recs[3][0],1,(strlen($recs[3][0])-2));
					}
					else
					{
						$dns_rr[$r]['data'] = $recs[3][0];
					}

					if (strtolower($dns_rr[$r]['type'])=='ns' && strtolower($dns_rr[$r]['name'])==$name)
					{
						unset($dns_rr[$r]);
					}

					$r++;
				}
			}

		}
	}
	*/

	foreach ($servers as $server){
		$dns_rr[$r]['name'] = $soa['name'];
		$dns_rr[$r]['type'] = 'NS';
		$dns_rr[$r]['data'] = $server['server_name'];
		$dns_rr[$r]['aux'] = 0;
		$r++;
	}
	//print('<pre>');
	//print_r($dns_rr);
	//print('</pre>');


	// Insert the soa record
	$sys_userid = $_SESSION['s']['user']['userid'];
	$origin = $app->db->quote($soa['name']);
	$ns = $app->db->quote($soa['ns']);
	$mbox = $app->db->quote($soa['mbox']);
	$refresh = $app->db->quote($soa['refresh']);
	$retry = $app->db->quote($soa['retry']);
	$expire = $app->db->quote($soa['expire']);
	$minimum = $app->db->quote($soa['minimum']);
	$ttl = $app->db->quote($soa['ttl']);
	$xfer = $app->db->quote('');
	$serial = $app->db->quote($app->functions->intval($soa['serial'])+1);
	//print_r($soa);
	//die();
	if($valid_zone_file){
		$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `origin`, `ns`, `mbox`, `serial`, `refresh`, `retry`, `expire`, `minimum`, `ttl`, `active`, `xfer`) VALUES
		('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$origin', '$ns', '$mbox', '$serial', '$refresh', '$retry', '$expire', '$minimum', '$ttl', 'Y', '$xfer')";
		$dns_soa_id = $app->db->datalogInsert('dns_soa', $insert_data, 'id');

		// Insert the dns_rr records
		if(is_array($dns_rr) && $dns_soa_id > 0)
		{
			foreach($dns_rr as $rr)
			{
				$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `zone`, `name`, `type`, `data`, `aux`, `ttl`, `active`) VALUES
				('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$dns_soa_id', '".$app->db->quote($rr['name'])."', '".$app->db->quote($rr['type'])."', '".$app->db->quote($rr['data'])."', '".$app->db->quote($rr['aux'])."', '".$app->db->quote($rr['ttl'])."', 'Y')";
				$dns_rr_id = $app->db->datalogInsert('dns_rr', $insert_data, 'id');
			}
		}
		$msg .= $wb['zone_file_successfully_imported_txt'];
	} else {
		$error .= $wb['error_no_valid_zone_file_txt'];
	}
	//header('Location: /dns/dns_soa_edit.php?id='.$dns_soa_id);
} else {
	if(isset($_FILES['file']['name'])) {
		$error = $wb['no_file_uploaded_error'];
	}
}


$app->tpl->setVar('msg', $msg);
$app->tpl->setVar('error', $error);

$app->tpl_defaults();
$app->tpl->pparse();


?>
