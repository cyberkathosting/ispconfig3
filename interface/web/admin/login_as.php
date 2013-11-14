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

/* Check permissions for module */
$app->auth->check_module_permissions('admin');

/* for security reasons ONLY the admin can login as other user */
if ($_SESSION["s"]["user"]["typ"] != 'admin') {
	die ("You don't have the right to login as other user!");
}

/* get the id of the user (must be int!) */
if (!isset($_GET['id']) && !isset($_GET['cid'])){
	die ("No user selected!");
}

if(isset($_GET['id'])) {
	$userId = $app->functions->intval($_GET['id']);
	$backlink = 'admin/users_list.php';
} else {
	$client_id = $app->functions->intval($_GET['cid']);
	$tmp_client = $app->db->queryOneRecord("SELECT username FROM client WHERE client_id = $client_id");
	$tmp_sys_user = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE username = '".$app->db->quote($tmp_client['username'])."'");
	$userId = $tmp_sys_user['userid'];
	unset($tmp_client);
	unset($tmp_sys_user);
	$backlink = 'client/client_list.php';
}

/*
 * Get the data to login as user x
 */
$dbData = $app->db->queryOneRecord(
	"SELECT username, passwort FROM sys_user WHERE userid = " . $userId);

/*
 * Now generate the login-Form
 * TODO: move the login_as form to a template file -> themeability
 */

$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_login_as.lng';
include $lng_file;

echo '
	<br /> <br />	<br /> <br />
	'.$wb['login_1_txt'].' ' .  $dbData['username'] . '?<br />
	'.$wb['login_2_txt'].'<br />
	<div style="visibility:hidden">
		<input type="text" name="username" value="' . $dbData['username'] . '" />
		<input type="password" name="passwort" value="' . $dbData['passwort'] .'" />
	</div>
	<input type="hidden" name="s_mod" value="login" />
	<input type="hidden" name="s_pg" value="index" />
    <div class="wf_actions buttons">
      <button class="positive iconstxt icoPositive" type="button" value="'.$wb['btn_yes_txt'].'" onclick="submitLoginForm(' . "'pageForm'" . ');"><span>'.$wb['btn_yes_txt'].'</span></button>
      <button class="negative iconstxt icoNegative" type="button" value="'.$wb['btn_back_txt'].'" onclick="loadContent('. "'$backlink'" . ');"><span>'.$wb['btn_back_txt'].'</span></button>
    </div>
';
?>
