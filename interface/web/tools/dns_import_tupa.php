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
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

$app->uses('tpl,validate_dns');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/dns_import_tupa.htm');
$msg = '';
$error = '';

// Resyncing dns zones
if(isset($_POST['start']) && $_POST['start'] == 1) {
	
	//* CSRF Check
	$app->auth->csrf_token_check();

	//* Set variables in template
	$app->tpl->setVar('dbhost', $_POST['dbhost'], true);
	$app->tpl->setVar('dbname', $_POST['dbname'], true);
	$app->tpl->setVar('dbuser', $_POST['dbuser'], true);
	$app->tpl->setVar('dbpassword', $_POST['dbpassword'], true);
	$app->tpl->setVar('dbssl', 'true', true);

	//* Establish connection to external database
	$msg .= 'Connecting to external database...<br />';

	//* Set external db client flags
	$db_client_flags = 0;
	if(isset($_POST['dbssl']) && $_POST['dbssl'] == 1) $db_client_flags |= MYSQLI_CLIENT_SSL;

	//* create new db object with external login details
	try {
		$exdb = new db($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpassword'], $_POST['dbname'], 3306, $db_client_flags);
	} catch (Exception $e) {
		$error .= "Error connecting to Tupa database" . ($e->getMessage() ? ": " . $e->getMessage() : '.') . "<br />\n";
		$exdb = false;
	}

	$server_id = 1;
	$sys_userid = 1;
	$sys_groupid = 1;

	function addot($text) {
		return trim($text) . '.';
	}

	//* Connect to DB
	if($exdb !== false) {
		$domains = $exdb->queryAllRecords("SELECT * FROM domains WHERE type = 'MASTER'");
		if(is_array($domains)) {
			foreach($domains as $domain) {
				$soa = $exdb->queryOneRecord("SELECT * FROM records WHERE type = 'SOA' AND domain_id = ?", $domain['id']);
				if(is_array($soa)) {
					$parts = explode(' ', $soa['content']);
					$origin = addot($soa['name']);
					$ns = addot($parts[0]);
					$mbox = addot($parts[1]);
					$serial = $parts[2];
					$refresh = 7200;
					$retry =  540;
					$expire = 604800;
					$minimum = 3600;
					$ttl = $soa['ttl'];

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
						"xfer" => ''
					);
					$dns_soa_id = $app->db->datalogInsert('dns_soa', $insert_data, 'id');
					unset($parts);
					$msg .= 'Import Zone: '.$soa['name'].'<br />';

					//* Process the other records
					$records = $exdb->queryAllRecords("SELECT * FROM records WHERE type != 'SOA' AND domain_id = ?", $domain['id']);
					if(is_array($records)) {
						foreach($records as $rec) {
							$rr = array();

							$rr['name'] = addot($rec['name']);
							$rr['type'] = $rec['type'];
							$rr['aux'] = $rec['prio'];
							$rr['ttl'] = $rec['ttl'];

							if($rec['type'] == 'NS' || $rec['type'] == 'MX' || $rec['type'] == 'CNAME') {
								$rr['data'] = addot($rec['content']);
							} else {
								$rr['data'] = $rec['content'];
							}

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
							//$msg .= $insert_data.'<br />';
						}
					}
				}
			}
		}
	}

}

$app->tpl->setVar('msg', $msg);
$app->tpl->setVar('error', $error);

//* SET csrf token
$csrf_token = $app->auth->csrf_token_get('dns_import');
$app->tpl->setVar('_csrf_id',$csrf_token['csrf_id']);
$app->tpl->setVar('_csrf_key',$csrf_token['csrf_key']);

$app->tpl_defaults();
$app->tpl->pparse();


?>
