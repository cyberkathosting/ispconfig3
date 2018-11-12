<?php
/*
Copyright (c) 2010, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

//die('Function has been removed.');

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

$app->uses('tpl');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/remote_action_ispcupdate.htm');

//* load language file
$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_remote_action.lng';
include $lng_file;

/*
 * We need a list of all Servers
 */

$sysServers = $app->db->queryAllRecords("SELECT server_id, server_name FROM server order by server_name");
$dropDown = "<option value='*'>" . $wb['select_all_server'] . "</option>";
foreach ($sysServers as $server) {
	$dropDown .= "<option value='" . $server['server_id'] . "'>" . $server['server_name'] . "</option>";
}
$app->tpl->setVar('server_option', $dropDown);

$msg = '';

/*
 * If the user wants to do the action, write this to our db
*/

//* Note: Disabled post action
if (1 == 0 && isset($_POST['server_select'])) {
	
	//* CSRF Check
	$app->auth->csrf_token_check();
	
	$server = $_POST['server_select'];
	$servers = array();
	if ($server == '*') {
		/* We need ALL Servers */
		foreach ($sysServers as $server) {
			$servers[] = $server['server_id'];
		}
	}
	else {
		/* We need only the selected Server */
		$servers[] = $_POST['server_select'];
	}
	foreach ($servers as $serverId) {
		$sql = "INSERT INTO sys_remoteaction (server_id, tstamp, action_type, action_param, action_state, response) " .
			"VALUES (?, UNIX_TIMESTAMP(), 'ispc_update', '', 'pending', '')";
		$app->db->query($sql, $serverId);
	}
	$msg = $wb['action_scheduled'];
}

$app->tpl->setVar('msg', $msg);

//* SET csrf token
$csrf_token = $app->auth->csrf_token_get('ispupdate');
$app->tpl->setVar('_csrf_id',$csrf_token['csrf_id']);
$app->tpl->setVar('_csrf_key',$csrf_token['csrf_key']);

$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();


?>
