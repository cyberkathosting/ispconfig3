<?php
/*
Copyright (c) 2012, Till Brehm, projektfarm Gmbh, ISPConfig UG
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
$app->tpl->setInclude('content_tpl', 'templates/resync.htm');
$msg = '';
$error = '';

//* load language file
$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_resync.lng';
include $lng_file;
$app->tpl->setVar($wb);

if(isset($_POST) && count($_POST) > 1) {	
	//* CSRF Check
	$app->auth->csrf_token_check();
}

//* Resyncing websites
if(isset($_POST['resync_sites']) && $_POST['resync_sites'] == 1) {
	$db_table = 'web_domain';
	$index_field = 'domain_id';
	$sql = "SELECT * FROM ".$db_table." WHERE active = 'y'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$msg .= "Resynced Website: ".$rec['domain'].'<br />';
		}
	}
}

//* Resyncing ftp
if(isset($_POST['resync_ftp']) && $_POST['resync_ftp'] == 1) {
	$db_table = 'ftp_user';
	$index_field = 'ftp_user_id';
	$sql = "SELECT * FROM ".$db_table." WHERE active = 'y'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$msg .= "Resynced FTP user: ".$rec['username'].'<br />';
		}
	}
}

//* Resyncing shell
if(isset($_POST['resync_shell']) && $_POST['resync_shell'] == 1) {
	$db_table = 'shell_user';
	$index_field = 'shell_user_id';
	$sql = "SELECT * FROM ".$db_table." WHERE active = 'y'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$msg .= "Resynced Shell user: ".$rec['username'].'<br />';
		}
	}
}

//* Resyncing Cronjobs
if(isset($_POST['resync_cron']) && $_POST['resync_cron'] == 1) {
	$db_table = 'cron';
	$index_field = 'id';
	$sql = "SELECT * FROM ".$db_table." WHERE active = 'y'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$msg .= "Resynced Cron: ".$rec['id'].'<br />';
		}
	}
}

//* Resyncing Databases
if(isset($_POST['resync_db']) && $_POST['resync_db'] == 1) {
	$db_table = 'web_database_user';
	$index_field = 'database_user_id';
	$sql = "SELECT * FROM ".$db_table." WHERE 1";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$msg .= "Resynced Database user: ".$rec['database_user'].'<br />';
		}
	}
	$db_table = 'web_database';
	$index_field = 'database_id';
	$sql = "SELECT * FROM ".$db_table." WHERE active = 'y'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$msg .= "Resynced Database: ".$rec['database_name'].'<br />';
		}
	}
}

//* Resyncing Mailbox Domains
if(isset($_POST['resync_mailbox']) && $_POST['resync_mailbox'] == 1) {
	$db_table = 'mail_domain';
	$index_field = 'domain_id';
	$sql = "SELECT * FROM ".$db_table." WHERE active = 'y'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$msg .= "Resynced Mail Domain: ".$rec['domain'].'<br />';
		}
	}
}

//* Resyncing Mailboxes
if(isset($_POST['resync_mailbox']) && $_POST['resync_mailbox'] == 1) {
	$db_table = 'mail_user';
	$index_field = 'mailuser_id';
	$sql = "SELECT * FROM ".$db_table;
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$msg .= "Resynced Mailbox: ".$rec['email'].'<br />';
		}
	}
	$db_table = 'mail_forwarding';
	$index_field = 'forwarding_id';
	$sql = "SELECT * FROM ".$db_table;
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$msg .= "Resynced Alias: ".$rec['source'].'<br />';
		}
	}
}

//* Resyncing dns zones
if(isset($_POST['resync_dns']) && $_POST['resync_dns'] == 1) {
	$zones = $app->db->queryAllRecords("SELECT id,origin,serial FROM dns_soa WHERE active = 'Y'");
	if(is_array($zones) && !empty($zones)) {
		foreach($zones as $zone) {
			$records = $app->db->queryAllRecords("SELECT id,serial FROM dns_rr WHERE zone = ".$zone['id']." AND active = 'Y'");
			if(is_array($records)) {
				foreach($records as $rec) {
					$new_serial = $app->validate_dns->increase_serial($rec["serial"]);
					$app->db->datalogUpdate('dns_rr', "serial = '".$new_serial."'", 'id', $rec['id']);

				}
			}
			$new_serial = $app->validate_dns->increase_serial($zone["serial"]);
			$app->db->datalogUpdate('dns_soa', "serial = '".$new_serial."'", 'id', $zone['id']);
			$msg .= "Resynced DNS zone: ".$zone['origin'].'<br />';
		}
	} else {
		$error .= "No zones found to sync.<br />";
	}

}

//* Resyncing Clients
if(isset($_POST['resync_client']) && $_POST['resync_client'] == 1) {
	$tform_def_file = "form/client.tform.php";
	$app->uses('tpl,tform,tform_actions');
	$app->load('tform_actions');
	
	$db_table = 'client';
	$index_field = 'client_id';
	$sql = "SELECT * FROM ".$db_table;
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {
			$app->db->datalogUpdate($db_table, $rec, $index_field, $rec[$index_field], true);
			$tmp = new tform_actions;
			$tmp->id = $rec[$index_field];
			$tmp->dataRecord = $rec;
			$tmp->oldDataRecord = $rec;
			$app->plugin->raiseEvent('client:client:on_after_update', $tmp);
			$msg .= "Resynced Client: ".$rec['contact_name'].'<br />';
			unset($tmp);
		}
	}
}


$app->tpl->setVar('msg', $msg);
$app->tpl->setVar('error', $error);

//* SET csrf token
$csrf_token = $app->auth->csrf_token_get('tools_resync');
$app->tpl->setVar('_csrf_id',$csrf_token['csrf_id']);
$app->tpl->setVar('_csrf_key',$csrf_token['csrf_key']);

$app->tpl_defaults();
$app->tpl->pparse();


?>
