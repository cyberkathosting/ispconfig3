<?php
/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

/*
 * Check if the logout is forced
 */
$forceLogout = false;
if (isset($_GET['l']) && ($_GET['l']== 1)) $forceLogout = true;

/*
 * if the admin is logged in as client, then ask, if the admin want't to
 * "re-login" as admin again
 */
if ((isset($_SESSION['s_old']) && ($_SESSION['s_old']['user']['typ'] == 'admin' || $app->auth->has_clients($_SESSION['s_old']['user']['userid']))) &&
	(!$forceLogout)){
	$utype = ($_SESSION['s_old']['user']['typ'] == 'admin' ? 'admin' : 'reseller');
	$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_login_as.lng';
	include $lng_file;
	echo '
		<br /> <br />	<br /> <br />
		'.str_replace('{UTYPE}', $utype, $wb['login_as_or_logout_txt']).'<br />
		<input type="hidden" name="username" value="' . $_SESSION['s_old']['user']['username'] . '" />
		<input type="hidden" name="password" value="' . $_SESSION['s_old']['user']['passwort'] .'" />
		<input type="hidden" name="s_mod" value="login" />
		<input type="hidden" name="s_pg" value="index" />
		<input type="hidden" name="login_as" value="1" />
	    <div class="wf_actions buttons">
	      <button class="btn btn-default formbutton-success" type="button" value="Yes, re-login as ' . $utype . '" data-submit-form="pageForm" data-form-action="login/index.php"><span>Yes, re-login as ' . $utype . '</span></button>
	      <button class="btn btn-default formbutton-default" type="button" value="No, logout" data-load-content="login/logout.php?l=1"><span>No, logout</span></button>
	    </div>
	';
	exit;
}

$app->plugin->raiseEvent('logout', true);

$_SESSION["s"]["user"] = null;
$_SESSION["s"]["module"] = null;
$_SESSION['s_old'] = null;

//header("Location: ../index.php?phpsessid=".$_SESSION["s"]["id"]);

if($_SESSION["s"]["site"]["logout"] != '') {
	echo 'URL_REDIRECT:'.$_SESSION["s"]["site"]["logout"];
} else {
	if($conf["interface_logout_url"] != '') {
		echo 'URL_REDIRECT:'.$conf["interface_logout_url"];
	} else {
		echo 'URL_REDIRECT:index.php';
	}
}
// Destroy the session completely now
$_SESSION = array();
session_destroy();
session_write_close();
?>
