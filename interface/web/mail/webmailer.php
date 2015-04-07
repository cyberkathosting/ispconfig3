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
$app->auth->check_module_permissions('mail');

/* get the id of the mail (must be int!) */
if (!isset($_GET['id'])){
	die ("No E-Mail selected!");
}
$emailId = $app->functions->intval($_GET['id']);

/*
 * Get the data to connect to the database
 */
$dbData = $app->db->queryOneRecord("SELECT server_id FROM mail_user WHERE mailuser_id = ?", $emailId);
$serverId = $app->functions->intval($dbData['server_id']);
if ($serverId == 0){
	die ("No E-Mail - Server found!");
}

$serverData = $app->db->queryOneRecord("SELECT server_name FROM server WHERE server_id = ?", $serverId);

$app->uses('getconf');
$global_config = $app->getconf->get_global_config('mail');

if($global_config['webmail_url'] != '') {
	$webmail_url = $global_config['webmail_url'];
	$webmail_url = str_replace('[SERVERNAME]', $serverData['server_name'], $webmail_url);
	header('Location:' . $webmail_url);
} else {

	/*
 * We only redirect to the login-form, so there is no need, to check any rights
 */
	isset($_SERVER['HTTPS'])? $http = 'https' : $http = 'http';
	if($web_config['server_type'] == 'nginx') {
		header('Location: http://' . $serverData['server_name'] . ':8081/webmail');
	} else {
		header('Location: ' . $http . '://' . $serverData['server_name'] . '/webmail');
	}
	isset($_SERVER['HTTPS'])? $http = 'https' : $http = 'http';
}
exit;
?>
