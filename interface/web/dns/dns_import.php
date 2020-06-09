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

$msg = [];
$warn = [];
$error = [];

// Loading the template
$app->uses('tform,tpl,validate_dns');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/dns_import.htm');
$app->load_language_file('/web/dns/lib/lang/'.$_SESSION['s']['language'].'_dns_wizard.lng');

// Check if dns record limit has been reached. We will check only users, not admins
if($_SESSION["s"]["user"]["typ"] == 'user') {
	$app->tform->formDef['db_table_idx'] = 'id';
	$app->tform->formDef['db_table'] = 'dns_soa';
	if(!$app->tform->checkClientLimit('limit_dns_zone')) {
		$app->error($app->lng('limit_dns_zone_txt'));
	}
	if(!$app->tform->checkResellerLimit('limit_dns_zone')) {
		$app->error('Reseller: '.$app->lng('limit_dns_zone_txt'));
	}
}

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
	$settings = $app->getconf->get_global_config('dns');
	$server_id = $app->functions->intval($settings['default_dnsserver']);
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
	$clients = $app->functions->htmlentities($clients);
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
	$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
	$client = $app->functions->htmlentities($client);

	// load the list of clients
	$sql = "SELECT sys_group.groupid, sys_group.name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ? ORDER BY client.company_name, client.contact_name, sys_group.name";
	$clients = $app->db->queryAllRecords($sql, $client['client_id']);
	$clients = $app->functions->htmlentities($clients);
	$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ?", $client['client_id']);
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
	$client_dns = $app->db->queryOneRecord("SELECT dns_servers FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

	$client_dns['dns_servers_ids'] = explode(',', $client_dns['dns_servers']);

	$only_one_server = count($client_dns['dns_servers_ids']) === 1;
	$app->tpl->setVar('only_one_server', $only_one_server);

	if ($only_one_server) {
		$app->tpl->setVar('server_id_value', $client_dns['dns_servers_ids'][0]);
	}

	$sql = "SELECT server_id, server_name FROM server WHERE server_id IN ?";
	$dns_servers = $app->db->queryAllRecords($sql, $client_dns['dns_servers_ids']);

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
	$domains = $app->tools_sites->getDomainModuleDomains("dns_soa");
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

$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_dns_import.lng';
include $lng_file;
$app->tpl->setVar($wb);

/** Returns shortest name for an owner with giving $origin in effect */
function origin_name( $owner, $origin ) {
	if ($owner == "@") { return ''; }
	if ($owner == "*") { return $owner; }
	if ($owner == "") { return $origin; }
	if ($origin == "") { return $owner; }
	if (substr($owner, -1) == ".") {
		if (substr($origin, -1) == ".") {
			return substr_replace( $owner, '', 0 - (strlen($origin) + 1) );
		} else {
			return $owner;
		}
	}
	if ($origin == ".") {
		return "${owner}.";
	}
	if (substr($origin, -1) != ".") {
		// should be an erorr,
		// only "." terminated $origin can be handled determinately
		return "${owner}.${origin}";
	}
	return $owner;

}

/** Returns full name for an owner with given $origin in effect */
function fqdn_name( $owner, $origin ) {
	if (substr($owner, -1) == ".") {
		return $owner;
	}
	$name = origin_name( $owner, $origin );
	return $name . (strlen($name) > 0 ? "." : "") . $origin;
}

// Import the zone-file
//if(1=="1")
if(isset($_FILES['file']['name']) && is_uploaded_file($_FILES['file']['tmp_name'])){
	$valid_zone_file = FALSE;

	$sql = "SELECT server_name FROM `server` WHERE server_id=? OR mirror_server_id=? ORDER BY server_name ASC";
	$servers = $app->db->queryAllRecords($sql, $server_id, $server_id);
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
		$line = rtrim($line);
		$line = preg_replace('/^\s+/', ' ', $line);
		if ($line != '' && substr($line, 0, 1) != ';'){
			if(preg_match("/\sNAPTR\s/i", $line)) {
				// NAPTR contains regex strings, there's not much we can safely clean up.
				// remove a comment if found after the ending period (and comment doesn't contain period)
				$line = preg_replace( '/^(.+\.)(\s*;[^\.]*)$/', '\1', $line );
			} else {
				if(strpos($line, ";") !== FALSE) {
					if(!preg_match("/\"[^\"]+;[^\"]*\"/", $line)) {
						$line = substr($line, 0, strpos($line, ";"));
					}
				}
				if(strpos($line, "(") !== FALSE ) {
					if (!preg_match("/v=DKIM/",$line)) {
						$line = substr($line, 0, strpos($line, "("));
					}
				}
				if(strpos($line, ")") !== FALSE ) {
					if (!preg_match("/v=DKIM/",$line)) {
						$line = substr($line, 0, strpos($line, ")"));
					}
				}
			}
			
			$line = rtrim($line);
			if ($line != ''){
				// this of course breaks TXT when it includes whitespace
				$sPattern = '/\s+/m';
				$sReplace = ' ';
				$new_lines[] = preg_replace($sPattern, $sReplace, $line);
			}
		}
	}
	unset($lines);
	$lines = $new_lines;
	unset($new_lines);

	if ($domain !== NULL){
		// SOA name will be the specified domain
		$name = origin_name( $domain, '.' );
	} else {
		// SOA name will be read from SOA record
		$name = '.';
	}

	$i = 0;
	$ttl = 3600;
	$soa_ttl = '86400';
	$soa_array_key = -1;
	$soa = array();
	$soa['name'] = $name;
	$origin = $name;
	$owner = $name;
	$r = 0;
	$dns_rr = array();
	$add_default_ns = TRUE;
	$found_soa = FALSE;
	foreach($lines as $line){

		$parts = explode(' ', $line);

		// leading whitespace means same owner as previous record
		if ($parts[0] == '') {
			// SOA is (only) read from multiple lines
			if($i > ($soa_array_key) && $i <= ($soa_array_key + 5)) {
				array_shift($parts);
			} else {
				$parts[0] = $owner;
			}
		} elseif (strpos( $parts[0], '$' ) !== 0) {
			$owner = fqdn_name( $parts[0], $origin );
		}

		// make elements lowercase
		$new_parts = array();
		foreach($parts as $part){
		if(
			(strpos($part, ';') === false) &&
			(!preg_match("/^\"/", $part)) &&
			(!preg_match("/\"$/", $part))
		) {
				$new_parts[] = strtolower($part);
			} else {
				$new_parts[] = $part;
			}
		}
		unset($parts);
		$parts = $new_parts;
		unset($new_parts);

		// Set current $ORIGIN (note: value in file can be a name relative to current origin)
		if($parts[0] == '$origin'){
			$origin = fqdn_name( $parts[1], $origin );
		}
		// TTL
		if($parts[0] == '$ttl'){
			$time_format = strtolower(substr($parts[1], -1));
			switch ($time_format) {
			case 's':
				$ttl = $app->functions->intval(substr($parts[1], 0, -1));
				break;
			case 'm':
				$ttl = $app->functions->intval(substr($parts[1], 0, -1)) * 60;
				break;
			case 'h':
				$ttl = $app->functions->intval(substr($parts[1], 0, -1)) * 3600;
				break;
			case 'd':
				$ttl = $app->functions->intval(substr($parts[1], 0, -1)) * 86400;
				break;
			case 'w':
				$ttl = $app->functions->intval(substr($parts[1], 0, -1)) * 604800;
				break;
			default:
				$ttl = $app->functions->intval($parts[1]);
			}
			$soa_ttl = $ttl;
			unset($time_format);
		}
		// SOA
		if(in_array("soa", $parts)){
			// Check for multiple SOA records in file
			if($found_soa && $soa_array_key != -1){
				// we could just skip any SOA which doesn't match the domain name,
				// which would allow concating zone files (sub1.d.tld, sub2.d.tld, d.tld) together for import
				$error[] = $wb['zone_file_multiple_soa'];
				$valid_zone_file = FALSE;
			} else {
				$soa['mbox'] = array_pop($parts);

				//$soa['ns'] = array_pop($parts);
				$soa['ns'] = $servers[0]['server_name'];

				// $parts[0] will always be owner name
				$soa_domain = fqdn_name( $parts[0], $origin );

				if ($domain !== NULL){
					// domain was given, check that domain and SOA domain share some root
					if (    ( strpos( $soa_domain, origin_name( $domain, '.' ) ) !== FALSE )
					     || ( strpos( origin_name( $domain, '.' ), $soa_domain ) !== FALSE ) ) {
						$valid_zone_file = TRUE;
					}
				} else {
					// domain not given, use domain from SOA
					if($soa_domain != ".") {
						$soa['name'] = $soa_domain;
						$valid_zone_file = TRUE;
					}
				}

				if(is_numeric($parts[1])){
					$soa['ttl'] = $app->functions->intval($parts[1]);
				} else {
					$soa['ttl'] = $soa_ttl;
				}

				$found_soa = TRUE;
				$soa_array_key = $i;
			}
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

			$dns_rr[$r]['name'] = fqdn_name( $owner, $soa['name'] );
			array_shift($parts);  // shift record owner from $parts[0]

			if(is_numeric($parts[0])) {
				$dns_rr[$r]['ttl'] = $app->functions->intval($parts[0]);
				array_shift($parts);  // shift ttl from $parts[0]
			} else {
				$dns_rr[$r]['ttl'] = $ttl;
			}

			if($parts[0] == 'in'){
				array_shift($parts);  // shift class from $parts[0]
			} elseif (in_array( $parts[0], [ 'ch', 'hs', ] )) {
				$warn[] = $wb['ignore_record_not_class_in'] . "  ($owner " . strtoupper($parts[0]) . ")";
				unset($dns_rr[$r]);
				continue;
			}

			// A 1.2.3.4
			// MX 10 mail
			// TXT "v=spf1 a mx ptr -all"
			$resource_type = array_shift($parts);
			switch ($resource_type) {
			case 'mx':
			case 'srv':
			case 'naptr':
				$dns_rr[$r]['aux'] = $app->functions->intval(array_shift($parts));
				$dns_rr[$r]['data'] = implode(' ', $parts);
				break;
			case 'txt':
				$dns_rr[$r]['aux'] = 0;
				$dns_rr[$r]['data'] = implode(' ', $parts);
				$dns_rr[$r]['data'] = preg_replace( [ '/^\"/', '/\"$/' ], '', $dns_rr[$r]['data']);
				break;
			default:
				$dns_rr[$r]['aux'] = 0;
				$dns_rr[$r]['data'] = implode(' ', $parts);
			}

			$dns_rr[$r]['type'] = strtoupper($resource_type);

			if($dns_rr[$r]['type'] == 'NS' && fqdn_name( $dns_rr[$r]['name'], $soa['name'] ) == $soa['name']){
				$add_default_ns = FALSE;
			}

			$dns_rr[$r]['ttl'] = $app->functions->intval($dns_rr[$r]['ttl']);
			$dns_rr[$r]['aux'] = $app->functions->intval($dns_rr[$r]['aux']);

			// this really breaks NAPTR .. conceivably TXT, too.
			// just make sure data is encoded when saved and escaped when displayed/used
			if(!in_array($dns_rr[$r]['type'],array('NAPTR','TXT',))) {
				$dns_rr[$r]['data'] = strip_tags($dns_rr[$r]['data']);
			}

			// regex based on https://stackoverflow.com/questions/3026957/how-to-validate-a-domain-name-using-regex-php
			// probably should find something better that covers valid syntax, moreso than just valid hostnames
			if(!preg_match('/^(|@|\*|(?!\-)(?:(\*|(?:[a-zA-Z\d_][a-zA-Z\d\-_]{0,61})?[a-zA-Z\d_])\.){1,126}(?!\d+)[a-zA-Z\d_]{1,63}\.?)$/',$dns_rr[$r]['name'])) {
				$error[] = $wb['ignore_record_invalid_owner'] . " (" . htmlspecialchars($dns_rr[$r]['name']) . ")";
				unset( $dns_rr[$r] );
				continue;
			}

			if(!in_array($dns_rr[$r]['type'],array('A','AAAA','ALIAS','CNAME','DS','HINFO','LOC','MX','NAPTR','NS','PTR','RP','SRV','TXT','TLSA','DNSKEY'))) {
				$error[] = $wb['ignore_record_unknown_type'] . " (" . htmlspecialchars($dns_rr[$r]['type']) . ")";
				unset( $dns_rr[$r] );
				continue;
			}
			
			$r++;
		}
		$i++;
	}

	if ( $add_default_ns ) {
		foreach ($servers as $server){
			$dns_rr[$r]['name'] = $soa['name'];
			$dns_rr[$r]['type'] = 'NS';
			$dns_rr[$r]['data'] = $server['server_name'];
			$dns_rr[$r]['aux'] = 0;
			$dns_rr[$r]['ttl'] = $soa['ttl'];
			$r++;
		}
	}
	//print('<pre>');
	//print_r($dns_rr);
	//print('</pre>');

	if (!$found_soa) {
		$valid_zone_file = false;
		$error[] = $wb['zone_file_missing_soa'];
	}
	if (intval($soa['serial']) == 0
		|| (intval($soa['refresh']) == 0 && intval($soa['retry']) == 0 && intval($soa['expire']) == 0 && intval($soa['minimum']) == 0 )
	) {
		$valid_zone_file = false;
		$error[] = $wb['zone_file_soa_parser'];
$error[] = print_r( $soa, true );
	}
	if ($settings['use_domain_module'] == 'y' && ! $app->tools_sites->checkDomainModuleDomain($soa['name']) ) {
		$valid_zone_file = false;
		$error[] = $wb['zone_not_allowed'];
	}

	// Insert the soa record
	$sys_userid = $_SESSION['s']['user']['userid'];
	$xfer = '';
	$serial = $app->functions->intval($soa['serial']+1);
	//print_r($soa);
	//die();
	$records = $app->db->queryAllRecords("SELECT id FROM dns_soa WHERE origin = ?", $soa['name']);
	if (count($records) > 0) {
		$error[] = $wb['zone_already_exists'];
	} elseif ($valid_zone_file) {
		$insert_data = array(
			"sys_userid" => $sys_userid,
			"sys_groupid" => $sys_groupid,
			"sys_perm_user" => 'riud',
			"sys_perm_group" => 'riud',
			"sys_perm_other" => '',
			"server_id" => $server_id,
			"origin" => $soa['name'],
			"ns" => $soa['ns'],
			"mbox" => $soa['mbox'],
			"serial" => $serial,
			"refresh" => $soa['refresh'],
			"retry" => $soa['retry'],
			"expire" => $soa['expire'],
			"minimum" => $soa['minimum'],
			"ttl" => $soa['ttl'],
			"active" => 'Y',
			"xfer" => $xfer
		);
		$dns_soa_id = $app->db->datalogInsert('dns_soa', $insert_data, 'id');

		// Insert the dns_rr records
		if(is_array($dns_rr) && $dns_soa_id > 0)
		{
			foreach($dns_rr as $rr)
			{
				// ensure record name is within $soa['name'] zone
				if(fqdn_name( $rr['name'], $soa['name'] ) != $soa['name']
				   && (strpos( fqdn_name( $rr['name'], $soa['name'] ), ".".$soa['name'] ) === FALSE ) ){
					continue;
				}
				$insert_data = array(
					"sys_userid" => $sys_userid,
					"sys_groupid" => $sys_groupid,
					"sys_perm_user" => 'riud',
					"sys_perm_group" => 'riud',
					"sys_perm_other" => '',
					"server_id" => $server_id,
					"zone" => $dns_soa_id,
					"name" => origin_name( $rr['name'], $soa['name'] ),
					"type" => $rr['type'],
					"data" => $rr['data'],
					"aux" => $rr['aux'],
					"ttl" => $rr['ttl'],
					"active" => 'Y'
				);
				$dns_rr_id = $app->db->datalogInsert('dns_rr', $insert_data, 'id');
			}

			$msg[] = $wb['zone_file_successfully_imported_txt'];
		} elseif (is_array($dns_rr)) {
			$error[] = $wb['zone_file_import_fail'];
		} else {
			$msg[] = $wb['zone_file_successfully_imported_txt'];
		}
	} else {
		$error[] = $wb['error_no_valid_zone_file_txt'];
	}
	//header('Location: /dns/dns_soa_edit.php?id='.$dns_soa_id);
} else {
	if(isset($_FILES['file']['name'])) {
		$error[] = $wb['no_file_uploaded_error'];
	}
}

$_error='';
if (count($error) > 0) {
	// this markup should really be moved to ispconfig.js
	$_error = '<div class="alert alert-danger clear">' . implode( '<br />', $error) . '</div>';
}
if (count($warn) > 0) {
	// and add a 'warn' variable to templates and ispconfig.js
	$_error = '<div class="alert alert-warning clear">' . implode( '<br />', $warn) . '</div>';
}
$app->tpl->setVar('error', $_error);

$_msg='';
if (count($msg) > 0) {
	$_msg = '<div class="alert alert-success clear">' . implode( '<br />', $msg) . '</div>';
}
$app->tpl->setVar('msg', $_msg);

$app->tpl_defaults();
$app->tpl->pparse();


?>
