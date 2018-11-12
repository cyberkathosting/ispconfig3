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


// Loading the template
$app->uses('tpl,validate_dns,tform');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/dns_wizard.htm');
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
$records = $app->db->queryAllRecords("SELECT * FROM dns_template WHERE visible = 'Y' ORDER BY name ASC");
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

$app->uses('ini_parser,getconf');
$domains_settings = $app->getconf->get_global_config('domains');

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

	if ($domains_settings['use_domain_module'] != 'y') {
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
}

if ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {

	// Get the limits of the client
	$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
	$client = $app->db->queryOneRecord("SELECT client.client_id, client.contact_name, CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as contactname, sys_group.name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
	$client = $app->functions->htmlentities($client);

	if ($domains_settings['use_domain_module'] != 'y') {
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
}

if($_SESSION["s"]["user"]["typ"] != 'admin')
{
	$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
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
		$options_dns_servers .= '<option value="'.$dns_server['server_id'].'"'.($_POST['server_id'] == $dns_server['server_id'] ? ' selected="selected"' : '').'>'.$dns_server['server_name'].'</option>';
	}

	$app->tpl->setVar("server_id", $options_dns_servers);
	unset($options_dns_servers);

}

//* TODO: store dnssec-keys in the database - see below for non-admin-users
//* hide dnssec if we found dns-mirror-servers
$sql = "SELECT count(*) AS count FROM server WHERE mirror_server_id > 0 and dns_server = 1";
$rec=$app->db->queryOneRecord($sql);

$template_record = $app->db->queryOneRecord("SELECT * FROM dns_template WHERE template_id = ?", $template_id);
$fields = explode(',', $template_record['fields']);
if(is_array($fields)) {
	foreach($fields as $field) {
		if($field == 'DNSSEC' && $rec['count'] > 0) {
			//hide dnssec
		} else {
			$app->tpl->setVar($field."_VISIBLE", 1);
			$field = strtolower($field);
			$app->tpl->setVar($field, $_POST[$field], true);
		}
	}
}

/*
 * Now we have to check, if we should use the domain-module to select the domain
 * or not
 */
if ($domains_settings['use_domain_module'] == 'y') {
	/*
	 * The domain-module is in use.
	*/
	$domains = $app->tools_sites->getDomainModuleDomains("dns_soa", 'domain');
	$domain_select = '';
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

if($_POST['create'] == 1) {
	
	//* CSRF Check
	$app->auth->csrf_token_check();
	
	$error = '';

	if ($post_server_id)
	{
		$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
		$client = $app->db->queryOneRecord("SELECT dns_servers FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);

		$client['dns_servers_ids'] = explode(',', $client['dns_servers']);

		// Check if chosen server is in authorized servers for this client
		if (!(is_array($client['dns_servers_ids']) && in_array($server_id, $client['dns_servers_ids'])) && $_SESSION["s"]["user"]["typ"] != 'admin') {
			$error .= $app->lng('error_not_allowed_server_id').'<br />';
		}
	}
	else
	{
		$error .= $app->lng('error_no_server_id').'<br />';
	}

	// apply filters
	if(isset($_POST['domain']) && $_POST['domain'] != ''){
		/* check if the domain module is used - and check if the selected domain can be used! */
		if ($domains_settings['use_domain_module'] == 'y') {
			if ($_SESSION["s"]["user"]["typ"] == 'admin' || $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
				$_POST['client_group_id'] = $app->tools_sites->getClientIdForDomain($_POST['domain']);
			}
			$domain_check = $app->tools_sites->checkDomainModuleDomain($_POST['domain']);
			if(!$domain_check) {
				// invalid domain selected
				$_POST['domain'] = '';
			} else {
				$_POST['domain'] = $domain_check;
			}
		} else {
			$_POST['domain'] = $app->functions->idn_encode($_POST['domain']);
			$_POST['domain'] = strtolower($_POST['domain']);
		}
	}
	if(isset($_POST['ns1']) && $_POST['ns1'] != ''){
		$_POST['ns1'] = $app->functions->idn_encode($_POST['ns1']);
		$_POST['ns1'] = strtolower($_POST['ns1']);
	}
	if(isset($_POST['ns2']) && $_POST['ns2'] != ''){
		$_POST['ns2'] = $app->functions->idn_encode($_POST['ns2']);
		$_POST['ns2'] = strtolower($_POST['ns2']);
	}
	if(isset($_POST['email']) && $_POST['email'] != ''){
		$_POST['email'] = $app->functions->idn_encode($_POST['email']);
		$_POST['email'] = strtolower($_POST['email']);
	}


	if(isset($_POST['domain']) && $_POST['domain'] == '') $error .= $app->lng('error_domain_empty').'<br />';
	elseif(isset($_POST['domain']) && !preg_match('/^[\w\.\-]{2,64}\.[a-zA-Z0-9\-]{2,30}$/', $_POST['domain'])) $error .= $app->lng('error_domain_regex').'<br />';

	if(isset($_POST['ip']) && $_POST['ip'] == '') $error .= $app->lng('error_ip_empty').'<br />';

	if(isset($_POST['ipv6']) && $_POST['ipv6'] == '') $error .= $app->lng('error_ipv6_empty').'<br />';

	if(isset($_POST['ns1']) && $_POST['ns1'] == '') $error .= $app->lng('error_ns1_empty').'<br />';
	elseif(isset($_POST['ns1']) && !preg_match('/^[\w\.\-]{2,64}\.[a-zA-Z0-9]{2,30}$/', $_POST['ns1'])) $error .= $app->lng('error_ns1_regex').'<br />';

	if(isset($_POST['ns2']) && $_POST['ns2'] == '') $error .= $app->lng('error_ns2_empty').'<br />';
	elseif(isset($_POST['ns2']) && !preg_match('/^[\w\.\-]{2,64}\.[a-zA-Z0-9]{2,30}$/', $_POST['ns2'])) $error .= $app->lng('error_ns2_regex').'<br />';

	if(isset($_POST['email']) && $_POST['email'] == '') $error .= $app->lng('error_email_empty').'<br />';
	elseif(isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) === false) $error .= $app->lng('error_email_regex').'<br />';

	// make sure that the record belongs to the client group and not the admin group when admin inserts it
	if($_SESSION["s"]["user"]["typ"] == 'admin' && isset($_POST['client_group_id'])) {
		$sys_groupid = $app->functions->intval($_POST['client_group_id']);
	} elseif($app->auth->has_clients($_SESSION['s']['user']['userid']) && isset($_POST['client_group_id'])) {
		$sys_groupid = $app->functions->intval($_POST['client_group_id']);
	} else {
		$sys_groupid = $_SESSION["s"]["user"]["default_group"];
	}

	$tform_def_file = "form/dns_soa.tform.php";
	$app->uses('tform');
	$app->tform->loadFormDef($tform_def_file);

	if($_SESSION['s']['user']['typ'] != 'admin') {
		if(!$app->tform->checkClientLimit('limit_dns_zone')) {
			$error .= $app->tform->wordbook["limit_dns_zone_txt"];
		}
		if(!$app->tform->checkResellerLimit('limit_dns_zone')) {
			$error .= $app->tform->wordbook["limit_dns_zone_txt"];
		}
	}


	// replace template placeholders
	$tpl_content = $template_record['template'];
	if($_POST['domain'] != '') $tpl_content = str_replace('{DOMAIN}', $_POST['domain'], $tpl_content);
	if($_POST['ip'] != '') $tpl_content = str_replace('{IP}', $_POST['ip'], $tpl_content);
	if($_POST['ipv6'] != '') $tpl_content = str_replace('{IPV6}',$_POST['ipv6'],$tpl_content);
	if($_POST['ns1'] != '') $tpl_content = str_replace('{NS1}', $_POST['ns1'], $tpl_content);
	if($_POST['ns2'] != '') $tpl_content = str_replace('{NS2}', $_POST['ns2'], $tpl_content);
	if($_POST['email'] != '') $tpl_content = str_replace('{EMAIL}', $_POST['email'], $tpl_content);
	$enable_dnssec = (($_POST['dnssec'] == 'Y') ? 'Y' : 'N');
	if(isset($_POST['dkim']) && preg_match('/^[\w\.\-\/]{2,255}\.[a-zA-Z0-9\-]{2,30}[\.]{0,1}$/', $_POST['domain'])) {
		$sql = $app->db->queryOneRecord("SELECT dkim_public, dkim_selector FROM mail_domain WHERE domain = ? AND dkim = 'y' AND ".$app->tform->getAuthSQL('r'), $_POST['domain']);
		$public_key = $sql['dkim_public'];
		if ($public_key!='') {
			if (empty($sql['dkim_selector'])) $sql['dkim_selector'] = 'default';
			$dns_record=str_replace(array("\r\n", "\n", "\r", "-----BEGIN PUBLIC KEY-----", "-----END PUBLIC KEY-----"), '', $public_key);
			$tpl_content .= "\n".'TXT|'.$sql['dkim_selector'].'._domainkey.'.$_POST['domain'].'.|v=DKIM1; t=s; p='.$dns_record;
		}
	}

	// Parse the template
	$tpl_rows = explode("\n", $tpl_content);
	$section = '';
	$vars = array();
	$vars['xfer']='';
	$dns_rr = array();
	foreach($tpl_rows as $row) {
		$row = trim($row);
		if(substr($row, 0, 1) == '[') {
			if($row == '[ZONE]') {
				$section = 'zone';
			} elseif($row == '[DNS_RECORDS]') {
				$section = 'dns_records';
			} else {
				die('Unknown section type');
			}
		} else {
			if($row != '') {
				// Handle zone section
				if($section == 'zone') {
					$parts = explode('=', $row);
					$key = trim($parts[0]);
					$val = trim($parts[1]);
					if($key != '') $vars[$key] = $val;
				}
				// Handle DNS Record rows
				if($section == 'dns_records') {
					$parts = explode('|', $row);
					$dns_rr[] = array(
						'name' => $parts[1],
						'type' => $parts[0],
						'data' => $parts[2],
						'aux'  => $parts[3],
						'ttl'  => $parts[4]
					);
				}
			}
		}

	} // end foreach

	if($vars['origin'] == '') $error .= $app->lng('error_origin_empty').'<br />';
	if($vars['ns'] == '') $error .= $app->lng('error_ns_empty').'<br />';
	if($vars['mbox'] == '') $error .= $app->lng('error_mbox_empty').'<br />';
	if($vars['refresh'] == '') $error .= $app->lng('error_refresh_empty').'<br />';
	if($vars['retry'] == '') $error .= $app->lng('error_retry_empty').'<br />';
	if($vars['expire'] == '') $error .= $app->lng('error_expire_empty').'<br />';
	if($vars['minimum'] == '') $error .= $app->lng('error_minimum_empty').'<br />';
	if($vars['ttl'] == '') $error .= $app->lng('error_ttl_empty').'<br />';

	if($error == '') {
		// Insert the soa record
		$sys_userid = $_SESSION['s']['user']['userid'];
		$origin = $vars['origin'];
		$ns = $vars['ns'];
		$mbox = str_replace('@', '.', $vars['mbox']);
		$refresh = $vars['refresh'];
		$retry = $vars['retry'];
		$expire = $vars['expire'];
		$minimum = $vars['minimum'];
		$ttl = $vars['ttl'];
		$xfer = $vars['xfer'];
		$also_notify = $vars['also_notify'];
		$update_acl = $vars['update_acl'];
		$serial = $app->validate_dns->increase_serial(0);

		$insert_data = array(
			"sys_userid" => $sys_userid,
			"sys_groupid" => $sys_groupid,
			"sys_perm_user" => 'riud',
			"sys_perm_group" => 'riud',
			"sys_perm_other" => '',
			"server_id" => $server_id,
			"origin" => $origin,
			"ns" => $ns,
			"mbox" => $mbox,
			"serial" => $serial,
			"refresh" => $refresh,
			"retry" => $retry,
			"expire" => $expire,
			"minimum" => $minimum,
			"ttl" => $ttl,
			"active" => 'Y',
			"xfer" => $xfer,
			"also_notify" => $also_notify,
			"update_acl" => $update_acl,
			"dnssec_wanted" => $enable_dnssec
		);
		$dns_soa_id = $app->db->datalogInsert('dns_soa', $insert_data, 'id');
		if($dns_soa_id > 0) $app->plugin->raiseEvent('dns:wizard:on_after_insert', $dns_soa_id);

		// Insert the dns_rr records
		if(is_array($dns_rr) && $dns_soa_id > 0) {
			foreach($dns_rr as $rr) {
				$insert_data = array(
					"sys_userid" => $sys_userid,
					"sys_groupid" => $sys_groupid,
					"sys_perm_user" => 'riud',
					"sys_perm_group" => 'riud',
					"sys_perm_other" => '',
					"server_id" => $server_id,
					"zone" => $dns_soa_id,
					"name" => $rr['name'],
					"type" => $rr['type'],
					"data" => $rr['data'],
					"aux" => $rr['aux'],
					"ttl" => $rr['ttl'],
					"active" => 'Y'
				);
				$dns_rr_id = $app->db->datalogInsert('dns_rr', $insert_data, 'id');
			}
		}

		header("Location: dns_soa_list.php");
		exit;

	} else {
		$app->tpl->setVar("error", $error);
	}

}



$app->tpl->setVar("title", 'DNS Wizard');

//* SET csrf token
$csrf_token = $app->auth->csrf_token_get('dns_wizard');
$app->tpl->setVar('_csrf_id',$csrf_token['csrf_id']);
$app->tpl->setVar('_csrf_key',$csrf_token['csrf_key']);

$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_dns_wizard.lng';
include $lng_file;
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();


?>
