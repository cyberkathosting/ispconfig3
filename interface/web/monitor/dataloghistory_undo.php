<?php

/*
Copyright (c) 2007-2008, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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
$app->auth->check_module_permissions('monitor');

// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/dataloghistory_undo.htm');

require('lib/lang/'.$_SESSION['s']['language'].'_dataloghistory_undo.lng');
$app->tpl->setvar($wb);

$id = intval($_GET['id']);

$record = $app->db->queryOneRecord('SELECT * FROM sys_datalog WHERE datalog_id = ?', $id);

$dbidx = explode(':', $record['dbidx']);

$old_record = $app->db->queryOneRecord('SELECT * FROM ?? WHERE ??=?', $record['dbtable'], $dbidx[0], $dbidx[1]);

if($record['action'] === 'u') {
	if (is_array($old_record)) {
		if(!$data = unserialize(stripslashes($record['data']))) {
			$data = unserialize($record['data']);
		}

		$new_record = $data['old'];

		$app->db->datalogUpdate($record['dbtable'], $new_record, $dbidx[0], $dbidx[1]);

		$app->tpl->setVar('success', true);
	} else {
		$app->tpl->setVar('success', false);
	}
} elseif($record['action'] === 'd') {
	if(is_array($old_record)) {
		$app->tpl->setVar('success', false);
		$app->tpl->setVar('error_txt', $wb['error_undelete_txt']);
	} else {
		if(!$data = unserialize(stripslashes($record['data']))) {
			$data = unserialize($record['data']);
		}

		$new_record = $data['old'];
		/* TODO: maybe check some data, e. g. server_id -> server still there?, sys_groupid -> sys_group/sys_user still there? */

		$app->db->datalogInsert($record['dbtable'], $new_record, $dbidx[0]);

		$app->tpl->setVar('success', true);
	}
}

$app->tpl_defaults();
$app->tpl->pparse();

?>
