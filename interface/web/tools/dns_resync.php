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
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

$app->uses('tpl,validate_dns');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/dns_resync.htm');
$msg = '';
$error = '';

// Resyncing dns zones
if(isset($_POST['resync']) && $_POST['resync'] == 1) {
	$zones = $app->db->queryAllRecords("SELECT id,origin,serial FROM dns_soa WHERE active = 'Y'");
	if(is_array($zones)) {
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
			$msg .= "Resynced: ".$zone['origin'].'<br />';
		}
	}
	
}

$app->tpl->setVar('msg',$msg);
$app->tpl->setVar('error',$error);


//* load language file
/*
$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_mailbox_import.lng';
include($lng_file);
$app->tpl->setVar($wb);
*/

$app->tpl_defaults();
$app->tpl->pparse();


?>