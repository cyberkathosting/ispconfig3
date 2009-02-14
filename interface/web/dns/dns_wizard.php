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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('dns');


// Loading the template
$app->uses('tpl,validate_dns');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/dns_wizard.htm');

// import variables
$template_id = (isset($_POST['template_id']))?intval($_POST['template_id']):1;
$sys_groupid = (isset($_POST['client_group_id']))?intval($_POST['client_group_id']):0;

// get the correct server_id
if($_SESSION['s']['user']['typ'] == 'admin') {
	$server_id = (isset($_POST['server_id']))?intval($_POST['server_id']):1;
} else {
	$client_group_id = $_SESSION["s"]["user"]["default_group"];
	$client = $app->db->queryOneRecord("SELECT default_dnsserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
	$server_id = $client["default_dnsserver"];
}


// Load the templates
$records = $app->db->queryAllRecords("SELECT * FROM dns_template WHERE visible = 'y'");
$template_id_option = '';
foreach($records as $rec){
	$checked = ($rec['template_id'] == $template_id)?' SELECTED':'';
	$template_id_option .= '<option value="'.$rec['template_id'].'"'.$checked.'>'.$rec['name'].'</option>';
}
$app->tpl->setVar("template_id_option",$template_id_option);

// If the user is administrator
if($_SESSION['s']['user']['typ'] == 'admin') {
	
	// Load the list of servers
	$records = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE dns_server = 1 ORDER BY server_name");
	$server_id_option = '';
	foreach($records as $rec){
		$checked = ($rec['server_id'] == $server_id)?' SELECTED':'';
		$server_id_option .= '<option value="'.$rec['server_id'].'"'.$checked.'>'.$rec['server_name'].'</option>';
	}
	$app->tpl->setVar("server_id",$server_id_option);
	
	// load the list of clients
	$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0";
	$clients = $app->db->queryAllRecords($sql);
	$client_select = '';
	if($_SESSION["s"]["user"]["typ"] == 'admin') $client_select .= "<option value='0'></option>";
	if(is_array($clients)) {
		foreach( $clients as $client) {
			$selected = ($client["groupid"] == $sys_groupid)?'SELECTED':'';
			$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
		}
	}

	$app->tpl->setVar("client_group_id",$client_select);
	
}

$template_record = $app->db->queryOneRecord("SELECT * FROM dns_template WHERE template_id = '$template_id'");
$fields = explode(',',$template_record['fields']);
if(is_array($fields)) {
	foreach($fields as $field) {
		$app->tpl->setVar($field."_VISIBLE",1);
		$field = strtolower($field);
		$app->tpl->setVar($field,$_POST[$field]);
	}
}

if($_POST['create'] == 1) {
	
	$error = '';
	
	if(isset($_POST['domain']) && $_POST['domain'] == '') $error .= $app->lng('error_domain_empty');
	if(isset($_POST['ip']) && $_POST['ip'] == '') $error .= $app->lng('error_ip_empty');
	if(isset($_POST['ns1']) && $_POST['ns1'] == '') $error .= $app->lng('error_ns1_empty');
	if(isset($_POST['ns2']) && $_POST['ns2'] == '') $error .= $app->lng('error_ns2_empty');
	if(isset($_POST['email']) && $_POST['email'] == '') $error .= $app->lng('error_email_empty');
	
	
	// replace template placeholders
	$tpl_content = $template_record['template'];
	if($_POST['domain'] != '') $tpl_content = str_replace('{DOMAIN}',$_POST['domain'],$tpl_content);
	if($_POST['ip'] != '') $tpl_content = str_replace('{IP}',$_POST['ip'],$tpl_content);
	if($_POST['ns1'] != '') $tpl_content = str_replace('{NS1}',$_POST['ns1'],$tpl_content);
	if($_POST['ns2'] != '') $tpl_content = str_replace('{NS2}',$_POST['ns2'],$tpl_content);
	if($_POST['email'] != '') $tpl_content = str_replace('{EMAIL}',$_POST['email'],$tpl_content);
	
	// Parse the template
	$tpl_rows = explode("\n",$tpl_content);
	$section = '';
	$vars = array();
	$dns_rr = array();
	foreach($tpl_rows as $row) {
		$row = trim($row);
		if(substr($row,0,1) == '[') {
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
					$parts = explode('=',$row);
					$key = trim($parts[0]);
					$val = trim($parts[1]);
					if($key != '') $vars[$key] = $val;
				}
				// Handle DNS Record rows
				if($section == 'dns_records') {
					$parts = explode('|',$row);
					$dns_rr[] = array(
						'name' => $app->db->quote($parts[1]),
						'type' => $app->db->quote($parts[0]),
						'data' => $app->db->quote($parts[2]),
						'aux'  => $app->db->quote($parts[3]),
						'ttl'  => $app->db->quote($parts[4])
					);
				}
			}
		}
		
	} // end foreach
	
	if($vars['origin'] == '') $error .= $app->lng('error_origin_empty');
	if($vars['ns'] == '') $error .= $app->lng('error_ns_empty');
	if($vars['mbox'] == '') $error .= $app->lng('error_mbox_empty');
	if($vars['refresh'] == '') $error .= $app->lng('error_refresh_empty');
	if($vars['retry'] == '') $error .= $app->lng('error_retry_empty');
	if($vars['expire'] == '') $error .= $app->lng('error_expire_empty');
	if($vars['minimum'] == '') $error .= $app->lng('error_minimum_empty');
	if($vars['ttl'] == '') $error .= $app->lng('error_ttl_empty');
	
	if($error == '') {
		// Insert the soa record
		$sys_userid = $_SESSION['s']['user']['userid'];
		$origin = $app->db->quote($vars['origin']);
		$ns = $app->db->quote($vars['ns']);
		$mbox = $app->db->quote(str_replace('@','.',$vars['mbox']));
		$refresh = $app->db->quote($vars['refresh']);
		$retry = $app->db->quote($vars['retry']);
		$expire = $app->db->quote($vars['expire']);
		$minimum = $app->db->quote($vars['minimum']);
		$ttl = $app->db->quote($vars['ttl']);
		$xfer = $app->db->quote($vars['xfer']);
		$serial = $app->validate_dns->increase_serial(0);
		
		$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `origin`, `ns`, `mbox`, `serial`, `refresh`, `retry`, `expire`, `minimum`, `ttl`, `active`, `xfer`) VALUES 
		('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$origin', '$ns', '$mbox', '$serial', '$refresh', '$retry', '$expire', '$minimum', '$ttl', 'Y', '$xfer')";
		$dns_soa_id = $app->db->datalogInsert('dns_soa', $insert_data, 'id');
		
		// Insert the dns_rr records
		if(is_array($dns_rr) && $dns_soa_id > 0) {
			foreach($dns_rr as $rr) {
				$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `zone`, `name`, `type`, `data`, `aux`, `ttl`, `active`) VALUES 
				('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$dns_soa_id', '$rr[name]', '$rr[type]', '$rr[data]', '$rr[aux]', '$rr[ttl]', 'Y')";
				$dns_rr_id = $app->db->datalogInsert('dns_rr', $insert_data, 'id');
			}
		}
		
		header("Location: dns_soa_list.php");
		exit;
		
	} else {
		$app->tpl->setVar("error",$error);
	}
	
}



$app->tpl->setVar("title",'DNS Wizard');

$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dns_wizard.lng';
include($lng_file);
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();


?>