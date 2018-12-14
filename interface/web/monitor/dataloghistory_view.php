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

$app->load('finediff');

// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/dataloghistory_view.htm');

$app->load_language_file('web/monitor/lib/lang/'.$_SESSION['s']['language'].'_dataloghistory_view.lng');
require('lib/lang/'.$_SESSION['s']['language'].'_dataloghistory_view.lng');
$app->tpl->setvar($wb);

$id = intval($_GET['id']);

$record = $app->db->queryOneRecord('SELECT * FROM sys_datalog WHERE datalog_id = ?', $id);

$out['id'] = $id;

$out['timestamp'] = date($app->lng('conf_format_datetime'), $record['tstamp']);
$out['table'] = $record['dbtable'];

$out['action_char'] = $record['action'];
$out['action_name'] = $app->lng($record['action']);

$out['session_id'] = $record['session_id'];

if(!$data = unserialize(stripslashes($record['data']))) {
	$data = unserialize($record['data']);
}

switch ($record['action']) {
	case 'i':
		$inserts = array();
		foreach ($data['new'] as $key=>$value) {
			$inserts[] = array(
				'key' => $key,
				'value' => nl2br($value),
			);
		}
		$app->tpl->setLoop('inserts', $inserts);
		break;
	case 'u':
		$updates = array();
		foreach ($data['new'] as $key=>$value) {
			if ($value != $data['old'][$key]) {
				$old = $data['old'][$key];
				$new = $value;
				$changes = show_diff_if_needed($old, $new);
				$updates[] = array(
					'key' => $key,
					'is_diff' => $changes['is_diff'],
					'old' => nl2br($changes['old']),
					'new' => nl2br($changes['new']),
					'diff' => nl2br($changes['diff']),
				);
			}
		}
		if (count($updates) > 0) {
			$app->tpl->setLoop('updates', $updates);
		} else {
			$out['no_changes'] = true;
		}
		break;
	case 'd':
		$deletes = array();
		foreach ($data['old'] as $key=>$value) {
			$deletes[] = array(
				'key' => $key,
				'value' => nl2br($value),
			);
		}
		$app->tpl->setLoop('deletes', $deletes);
		break;
}

$app->tpl->setVar($out);
$app->tpl->setVar('can_undo', ($out['action_char'] === 'u' || $out['action_char'] === 'd'));

$app->tpl_defaults();
$app->tpl->pparse();

function show_diff_if_needed($old, $new) {
	global $app;

	$diff_min_lines = 6;

	if (substr_count($old, "\n") >= $diff_min_lines || substr_count($new, "\n") >= $diff_min_lines) {
		$opcodes = FineDiff::getDiffOpcodes($old, $new);
		$html = FineDiff::renderUTF8DiffToHTMLFromOpcodes($old, $opcodes);
		return array('is_diff'=>true, 'old'=>'', 'new'=>'', 'diff'=>$html);
	} else {
		return array('is_diff'=>false, 'old'=>$old, 'new'=>$new, 'diff'=>'');
	}
}

?>
