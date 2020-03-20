<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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
$app->auth->check_module_permissions('sites');

$app->uses('getconf,tform');

$server_id = $app->functions->intval($_GET["server_id"]);
$web_id = $app->functions->intval($_GET["web_id"]);
$php_type = $_GET["php_type"];
$client_group_id = $app->functions->intval($_GET['client_group_id']);
$type = $_GET["type"];

//if($_SESSION["s"]["user"]["typ"] == 'admin') {

if($type == 'getservertype'){
	$json = '{"servertype":"';
	$server_type = 'apache';
	$web_config = $app->getconf->get_server_config($server_id, 'web');
	if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
	$json .= $server_type;
	unset($web_config);
	$json .= '"}';
}

if($type == 'getserverid'){
	$json = '{"serverid":"';
	$sql = "SELECT server_id FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL('r');
	$server = $app->db->queryOneRecord($sql, $web_id);
	$json .= $server['server_id'];
	unset($server);
	$json .= '"}';
}

if($type == 'getphpfastcgi'){
	$json = '{';

	$server_type = 'apache';
	$web_config = $app->getconf->get_server_config($server_id, 'web');
	if(!empty($web_config['server_type'])) $server_type = $web_config['server_type'];
	if($server_type == 'nginx' && $php_type == 'fast-cgi') $php_type = 'php-fpm';
	$sql_where = '';

	//* Client: If the logged in user is not admin and has no sub clients (no reseller)
	if($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
		$sql_where = " AND (client_id = 0 OR client_id = ".$app->functions->intval($_SESSION["s"]["user"]["client_id"]) . ")";
		//* Reseller: If the logged in user is not admin and has sub clients (is a reseller)
	} elseif ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
		$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE groupid = ?", $client_group_id);
		//$sql_where = " AND (client_id = 0 OR client_id = ".$_SESSION["s"]["user"]["client_id"];
		$sql_where = " AND (client_id = 0";
		if($app->functions->intval($client['client_id']) > 0) $sql_where .= " OR client_id = ".$app->functions->intval($client['client_id']);
		$sql_where .= ")";
		//* Admin: If the logged in user is admin
	} else {
		//$sql_where = '';
		$client = $app->db->queryOneRecord("SELECT client_id FROM sys_group WHERE groupid = ?", $client_group_id);
		//$sql_where = " AND (client_id = 0 OR client_id = ".$_SESSION["s"]["user"]["client_id"];
		$sql_where = " AND (client_id = 0";
		if($app->functions->intval($client['client_id']) > 0) $sql_where .= " OR client_id = ".$app->functions->intval($client['client_id']);
		$sql_where .= ")";
	}

	if($php_type == 'php-fpm' || ($php_type == 'hhvm' && $server_type == 'nginx')){
		$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ? AND active = 'y'".$sql_where, $server_id);
	} elseif($php_type == 'fast-cgi'){
		$php_records = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fastcgi_binary != '' AND php_fastcgi_ini_dir != '' AND server_id = ? AND active = 'y'".$sql_where, $server_id);
	}
	if (empty($web_config['php_default_hide']) || 'n' === $web_config['php_default_hide']) {
		$php_records[]=array('name' => $app->functions->htmlentities($web_config['php_default_name']));
	}
	uasort($php_records, 'sort_php');
	$php_select = "";
	if(is_array($php_records) && !empty($php_records)) {
		foreach( $php_records as $php_record) {
			if($php_type == 'php-fpm' || ($php_type == 'hhvm' && $server_type == 'nginx')){
				$php_version = $php_record['name'].':'.$php_record['php_fpm_init_script'].':'.$php_record['php_fpm_ini_dir'].':'.$php_record['php_fpm_pool_dir'];
			} else {
				$php_version = $php_record['name'].':'.$php_record['php_fastcgi_binary'].':'.$php_record['php_fastcgi_ini_dir'];
			}
			if($php_record['name'] != $web_config['php_default_name']) {
				$json .= '"'.$php_version.'": "'.$php_record['name'].'",';
			} else {
				$json .= '"": "'.$php_record['name'].'",';
			}
		}
	}
	unset($php_records);
	if(substr($json, -1) == ',') $json = substr($json, 0, -1);
	$json .= '}';
}

if($type == 'getphptype'){
	$json = '{"phptype":"';
	$sql = "SELECT php FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL('r');
	$php = $app->db->queryOneRecord($sql, $web_id);
	$json .= $php['php'];
	unset($php);
	$json .= '"}';
}

if($type == 'getredirecttype'){
	$json = '{"redirecttype":"';
	$sql = "SELECT redirect_type FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL('r');
	$redirect = $app->db->queryOneRecord($sql, $web_id);
	$json .= $redirect['redirect_type'];
	unset($redirect);
	$json .= '"}';
}

if($type == 'get_ipv4'){
	$result = array();

	// ipv4
	//$result[] = _search('admin', 'server_ip', "AND ip_type = 'IPv4' AND (client_id = 0 OR client_id=".$app->functions->intval($_SESSION['s']['user']['client_id']).")");
	$result[] = $app->functions->suggest_ips('IPv4');

	$json = $app->functions->json_encode($result);
}

if($type == 'get_ipv6'){
	$result = array();

	// ipv6
	//$result[] = _search('admin', 'server_ip', "AND ip_type = 'IPv6' AND (client_id = 0 OR client_id=".$app->functions->intval($_SESSION['s']['user']['client_id']).")");
	$result[] = $app->functions->suggest_ips('IPv6');

	$json = $app->functions->json_encode($result);
}

if($type == 'getdatabaseusers') {
	$json = '{}';

	$sql = "SELECT sys_groupid FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL('r');
	$group = $app->db->queryOneRecord($sql, $web_id);
	if($group) {
		$sql = "SELECT database_user_id, database_user FROM web_database_user WHERE sys_groupid = ?";
		$records = $app->db->queryAllRecords($sql, $group['sys_groupid']);

		$tmp_array = array();
		foreach($records as $record) {
			$tmp_array[$record['database_user_id']] = $record['database_user'];
		}
		$json = $app->functions->json_encode($tmp_array);
		unset($records, $group, $tmp_array);
	}
}

if($type == 'get_use_combobox'){
	$json = '{"usecombobox":"';
	$use_combobox = 'y';
	$server_config_array = $app->getconf->get_global_config();
	if($server_config_array['misc']['use_combobox'] != 'y') $use_combobox = 'n';
	$json .= $use_combobox;
	unset($server_config_array);
	$json .= '"}';
}

if($type == 'get_use_loadindicator'){
	$json = '{"useloadindicator":"';
	$use_loadindicator = 'y';
	$server_config_array = $app->getconf->get_global_config();
	if($server_config_array['misc']['use_loadindicator'] != 'y') $use_loadindicator = 'n';
	$json .= $use_loadindicator;
	unset($server_config_array);
	$json .= '"}';
}

if ($type == 'getdirectivesnippet') {
	$server_type = 'apache';
	$web_config = $app->getconf->get_server_config($server_id, 'web');
	if (!empty($web_config['server_type'])) $server_type = $web_config['server_type'];

	$m_snippets = $app->db->queryAllRecords("SELECT directive_snippets_id, name FROM directive_snippets WHERE customer_viewable = 'y' AND active = 'y' AND master_directive_snippets_id > 0 AND type = ? ORDER BY name ASC", $server_type);
	
	$snippets = $app->db->queryAllRecords("SELECT directive_snippets_id, name FROM directive_snippets WHERE customer_viewable = 'y' AND active = 'y' AND master_directive_snippets_id = 0 AND type = ? ORDER BY name ASC", $server_type);

	$json = json_encode(array('m_snippets' => $m_snippets, 'snippets' => $snippets));
}

if($type == 'getclientssldata'){
	$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ? AND ".$app->tform->getAuthSQL('r'), $web_id);
	$sys_group = $app->db->queryOneRecord("SELECT * FROM sys_group WHERE groupid = ?", $web['sys_groupid']);
	$client = $app->db->queryOneRecord("SELECT company_name,contact_firstname, contact_name, street, zip, city, telephone, mobile,fax, country, state, email FROM client WHERE client_id = ?",$sys_group['client_id']);
	if(is_array($client) && !empty($client)){
		if($client['telephone'] == '' && $client['mobile'] != '') $client['telephone'] = $client['mobile'];
		
		$fname = '';
		$lname = '';
		$parts = preg_split("/\s+/", $client['contact_name']);
		if(sizeof($parts) == 2){
			$fname = $parts[0];
			$lname = $parts[1];
		}
		if(sizeof($parts) > 2){
			$fname = $parts[0].' ';
			for($i=1;$i<sizeof($parts);$i++){
				if($i == (sizeof($parts) - 1)){
					$lname .= $parts[$i];
				} else {
					if(preg_match('@^(von|van|ten|ter|zur|zu|auf|sieber)$@i', $parts[$i])){
						$lname .= implode(' ', array_slice($parts, $i));
						break;
					} else {
						$fname .= $parts[$i].' ';
					}
				}
			}
		}
		$fname = trim($fname);
		$lname = trim($lname);
		$client['fname'] = $fname;
		$client['lname'] = $lname;
		if(trim($client['company_name']) == '') $client['company_name'] = $fname.' '.$lname;
	}
	$json = $app->functions->json_encode($client);
}

//}

header('Content-type: application/json');
echo $json;

function sort_php($a, $b) {
	return strcmp($a['name'], $b['name']);
}

?>
