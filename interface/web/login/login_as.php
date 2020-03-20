<?php
/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

/* check if the user is logged in */
if(!isset($_SESSION['s']['user'])) {
	die ("You have to be logged in to login as other user!");
}

/* for security reasons ONLY the admin or a reseller can login as other user */
if ($_SESSION["s"]["user"]["typ"] != 'admin' && !$app->auth->has_clients($_SESSION['s']['user']['userid'])) {
	die ("You don't have the right to login as other user!");
}

/* get the id of the user (must be int!) */
if (!isset($_GET['id']) && !isset($_GET['cid'])){
	die ("No user selected!");
}

if(isset($_GET['id'])) {
	if($_SESSION["s"]["user"]["typ"] != 'admin') {
		die ("You don't have the right to login as system user!");
	}
	$userId = $app->functions->intval($_GET['id']);
	$backlink = 'admin/users_list.php';
} else {
	$client_id = $app->functions->intval($_GET['cid']);
	$tmp_client = $app->db->queryOneRecord("SELECT username, parent_client_id FROM client WHERE client_id = ?", $client_id);
	$tmp_sys_user = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE username = ?", $tmp_client['username']);
	$userId = $app->functions->intval($tmp_sys_user['userid']);
	/* check if this client belongs to reseller that tries to log in, if we are not admin */
	if($_SESSION["s"]["user"]["typ"] != 'admin') {
		$client_group_id = $app->functions->intval($_SESSION["s"]["user"]["default_group"]);
		$client = $app->db->queryOneRecord("SELECT client.client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = ?", $client_group_id);
		if(!$client || $tmp_client["parent_client_id"] != $client["client_id"]) {
			die("You don't have the right to login as this user!");
		}
		unset($client);
	}
	
	unset($tmp_client);
	unset($tmp_sys_user);
	$backlink = 'client/client_list.php';
}

/*
 * Get the data to login as user x
 */
$dbData = $app->db->queryOneRecord(
	"SELECT username, passwort FROM sys_user WHERE userid = ?", $userId);

/*
 * Now generate the login-Form
 * TODO: move the login_as form to a template file -> themeability
 */

$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_login_as.lng';
include $lng_file;

echo '
	<br /> <br />	<br /> <br />
	'.$wb['login_1_txt'].' ' .  $dbData['username'] . '?<br />
	'.$wb['login_2_txt'].'<br />
	<input type="hidden" name="username" value="' . $dbData['username'] . '" />
	<input type="hidden" name="password" value="' . $dbData['passwort'] .'" />
	<input type="hidden" name="s_mod" value="dashboard" />
	<input type="hidden" name="s_pg" value="dashboard" />
	<input type="hidden" name="login_as" value="1" />
    <div class="wf_actions buttons">
      <button class="btn btn-default formbutton-success" type="button" value="'.$wb['btn_yes_txt'].'" data-submit-form="pageForm" data-form-action="login/index.php"><span>'.$wb['btn_yes_txt'].'</span></button>
      <button class="btn btn-default formbutton-default" value="'.$wb['btn_back_txt'].'" data-load-content="'.$backlink.'"><span>'.$wb['btn_back_txt'].'</span></button>
    </div>
';
?>
