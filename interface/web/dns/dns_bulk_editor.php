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
$app->uses('tpl,tfrom_base,validate_dns,functions');
$app->tpl->newTemplate("form.tpl.htm");

include 'lib/lang/'.$_SESSION['s']['language'].'_dns_bulk_editor.lng';
$app->tpl->setVar($wb);

// Load clients (if admin):

if ($app->auth->is_admin()) {
	$clients = $app->db->queryAllRecords("SELECT sys_group.groupid,CONCAT(IF(client.company_name != '', CONCAT(client.company_name, ' :: '), ''), IF(client.contact_firstname != '', CONCAT(client.contact_firstname, ' '), ''), client.contact_name, ' (', client.username, IF(client.customer_no != '', CONCAT(', ', client.customer_no), ''), ')') as name FROM sys_group, client WHERE sys_group.groupid != 1 AND sys_group.client_id = client.client_id ORDER BY client.company_name, client.contact_name");

	$clients_select_options = '<option value="">'.$wb['select_client_txt'].'</option>';

	foreach($clients as $client) {
		$selected = (intval($_POST["client_group_id"]) == $client['groupid'])?'SELECTED':'';
		$clients_select_options .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
	}

	$app->tpl->setVar('clients_select_options', $clients_select_options);
}

// Load zones:

if ($app->auth->is_admin()) {
	if (isset($_POST["client_group_id"])) {
		$client_group_ids = intval($_POST["client_group_id"]);
	}
} else {
	$client_group_ids = $_SESSION['s']['user']['groups'];
}

if(isset($client_group_ids)) {
	$sql = 'SELECT id, origin FROM dns_soa WHERE sys_groupid IN ('.$client_group_ids.') AND '.$app->tform_base->getAuthSQL('u');

	$zones = $app->db->queryAllRecords($sql);

	$zones_rows = array(); // All zones (for output)

	foreach ($zones as $zone) {
		$zones_rows[] = array(
			'zone_id'=>$zone['id'],
			'zone_name'=>$zone['origin'],
			'zone_selected'=>isset($_POST['zone_'.$zone['id']]),
		);

	}	

	$app->tpl->setLoop('zones_rows', $zones_rows);
	$app->tpl->setVar('zones_rows_count', count($zones_rows));

	$update_zones = array(); // Currently selected zones in form (if any)

	foreach ($zones as $zone) {
		if (isset($_POST['zone_'.$zone['id']])) {
			$update_zones[$zone['id']] = $zone['origin'];
		}
	}
} else {
	$app->tpl->setVar('zones_rows_count', 0);
}

if (isset($_GET['submitted'])) {
	validate_and_update($update_zones);
}

$app->tpl_defaults();

if (isset($result)) {
	$app->tpl->setVar('result', $result);
	$app->tpl->setInclude('content_tpl', 'templates/dns_bulk_editor_result.htm');
} else {
	$app->tpl->setInclude('content_tpl', 'templates/dns_bulk_editor.htm');
}

$app->tpl->pparse();

function validate_and_update($update_zones) {
	global $app, $wb, $client_group_ids, $result;

	// Validate:

	if ($client_group_ids == 0) {
		$app->tpl->setVar('error', $wb['error_no_client_txt']);
		return;
	}

	if (!isset($_POST['action'])) {
		$app->tpl->setVar('error', $wb['error_no_action_txt']);
		return;
	}

        switch ($_POST['action']) {
		case 'a_records':
			$app->tpl->setVar('action_a_records', true);
			$app->tpl->setVar('a_records_search', htmlspecialchars($_POST['a_records_search']));
			$app->tpl->setVar('a_records_replace', htmlspecialchars($_POST['a_records_replace']));

			if (!validate_ips($_POST['a_records_search'], $_POST['a_records_replace'])) {
				// Error message is set in validate_ips
				return;
			}

			break;
		case 'mx_records':
			$app->tpl->setVar('action_mx_records', true);
			$app->tpl->setVar('mx_records_search', htmlspecialchars($_POST['mx_records_search']));
			$app->tpl->setVar('mx_records_replace', htmlspecialchars($_POST['mx_records_replace']));

			if (!validate_zone($_POST['mx_records_search']) || !validate_zone($_POST['mx_records_replace'])) {
				$app->tpl->setVar('error', $wb['error_invalid_dns_zone_txt']);
				return;
			}

			break;
		case 'ttl':
			$app->tpl->setVar('action_ttl', true);
			$app->tpl->setVar('ttl', htmlspecialchars($_POST['ttl']));

			if (trim($_POST['ttl']) == '' || !is_numeric($_POST['ttl']) || intval($_POST['ttl']) < 60) {
				$app->tpl->setVar('error', $wb['error_no_ttl_txt']);
				return;
			}

			break;
	}

	if (!(isset($update_zones) && count($update_zones) > 0)) {
		$app->tpl->setVar('error', $wb['error_no_zone_txt']);
		return;
	}

	foreach ($update_zones as $id=>$origin) {
		$sql = 'SELECT id FROM dns_soa WHERE id = ? AND '.$app->tform_base->getAuthSQL('u');
		if (!is_array($app->db->queryOneRecord($sql, $id))) {
			$app->tpl->setVar('error', $wb['error_invalid_zone_txt']);
			return;
		}
	}

	// Update:

	switch ($_POST['action']) {
		case 'a_records':
			$result = '<h3>'.$wb['a_records_txt'].'</h3>';

			foreach ($update_zones as $id=>$origin) {
				$result .= "<h4>".$wb['zone_txt']." $origin</h4>";

				$records = $app->db->queryAllRecords('SELECT id, type, name FROM dns_rr WHERE zone = ? AND data = ? AND type IN (\'A\', \'AAAA\') ORDER BY 2,3', $id, $_POST['a_records_search']);

				if (count($records) == 0) {
					// Zone has no records or no matching records
					$result .= $wb['no_matches_txt'];
					continue;
				}

				$result .= "<ul>";

				foreach ($records as $record) {
					$app->db->datalogUpdate('dns_rr', "data='".$app->db->escape($_POST['a_records_replace'])."'", 'id', $record['id']);
					$result .= '<li>'.$record['type']." ".$record['name']." ".htmlentities($_POST['a_records_search']).' &#x27a1; <strong>'.htmlentities($_POST['a_records_replace']).'</strong></li>';
				}

				$result .= "</ul>";

				if (count($records) > 0) {
					soa_increase_serial($id);
				}
			}

			break;
		case 'mx_records':
			$result = '<h3>'.$wb['mx_records_txt'].'</h3>';

			foreach ($update_zones as $id=>$origin) {
				$result .= "<h4>".$wb['zone_txt']." $origin</h4>";

				$search_zone = normalize_zone($_POST['mx_records_search']);
				$replace_zone = normalize_zone($_POST['mx_records_replace']);

				$records = $app->db->queryAllRecords('SELECT id, type, name FROM dns_rr WHERE zone = ? AND data = ? AND type = \'MX\' ORDER BY 2,3', $id, $search_zone);

				if (count($records) == 0) {
					// Zone has no records or no matching records
					echo 'No matches';
					$result .= $wb['no_matches_txt'];
					continue;
				}

				$result .= "<ul>";

				foreach ($records as $record) {
					$app->db->datalogUpdate('dns_rr', "data='".$app->db->escape($replace_zone)."'", 'id', $record['id']);
					$result .= '<li>'.$record['type']." ".$record['name']." ".$search_zone.' &#x27a1; <strong>'.$replace_zone.'</strong></li>';
				}

				$result .= "</ul>";

				if (count($records) > 0) {
					soa_increase_serial($id);
				}
			}

			break;
		case 'ttl':
			$result = '<h3>'.$wb['ttl_txt'].'</h3>';

			$ttl = intval($_POST['ttl']);

			foreach ($update_zones as $id=>$origin) {
				$result .= "<h4>".$wb['zone_txt']." $origin</h4>";

				$records = $app->db->queryAllRecords('SELECT id, type, name FROM dns_rr WHERE zone = ? AND type IN (\'A\', \'AAAA\', \'MX\') ORDER BY 2,3', $id);

				if (count($records) == 0) {
					// Zone has no records?
					$result .= $wb['no_matches_txt'];
					continue;
				}

				$result .= "<ul>";

				foreach ($records as $record) {
					$app->db->datalogUpdate('dns_rr', "ttl=$ttl", 'id', $record['id']);
					$result .= "<li>".$record['type']." ".$record['name']." <strong>$ttl</strong></li>";
				}
				
				$result .= "</ul>";

				if (count($records) > 0) {
					soa_increase_serial($id);
				}
			}

			break;
	}
}

function validate_ips($search, $replace) {
	global $app, $wb;

	if (trim($search) == '' || trim($replace) == '') {
		$app->tpl->setVar('error', $wb['error_no_search_replace_txt']);
		return false;
	}

	$search_ip_type = get_ip_type($search);
	$replace_ip_type = get_ip_type($replace);

	if ($search_ip_type == 'none' || $replace_ip_type == 'none') {
		$app->tpl->setVar('error', $wb['error_invalid_ip_txt']);
		return false;
	}

	if ($search_ip_type != $replace_ip_type) {
		$app->tpl->setVar('error', $wb['error_ip_type_mismatch_txt']);
		return false;
	}

	return true;
}

function get_ip_type($s) {
	if (filter_var($s, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) return 'IPv4';
	if (filter_var($s, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) return 'IPv6';
	return 'none';
}

function validate_zone($zone) {
	$s = normalize_zone($zone);
	$result = preg_match('/^[a-z0-9\.\-\*]{1,255}$/', $s) === 1;
	return $result;
}

function normalize_zone($zone) {
	global $app;

	$s = trim($zone);
	$s = strtolower($s);
	$s = $app->functions->idn_encode($s);
	return $s;
}

function soa_increase_serial($id) {
	global $app;

	$soa = $app->db->queryOneRecord('SELECT serial FROM dns_soa WHERE id = ?', $id);
	$serial = $app->validate_dns->increase_serial($soa['serial']);
	$app->db->datalogUpdate('dns_soa', "serial=$serial", 'id', $id);
}

?>
