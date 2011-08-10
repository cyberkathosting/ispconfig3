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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

/*
 * Check if the logout is forced
 */
$forceLogout = false;
if (isset($_GET['l']) && ($_GET['l']== 1)) $forceLogout = true;

/*
 * if the admin is logged in as client, then ask, if the admin want't to
 * "re-login" as admin again
 */
if ((isset($_SESSION['s_old']) && ($_SESSION['s_old']['user']['typ'] == 'admin')) &&
	(!$forceLogout)){
	echo '
		<br /> <br />	<br /> <br />
		Do you want to re-login as admin or log out?<br />
		<div style="visibility:hidden">
			<input type="text" name="username" value="' . $_SESSION['s_old']['user']['username'] . '" />
			<input type="password" name="passwort" value="' . $_SESSION['s_old']['user']['passwort'] .'" />
		</div>
		<input type="hidden" name="s_mod" value="login" />
		<input type="hidden" name="s_pg" value="index" />
	    <div class="wf_actions buttons">
	      <button class="positive iconstxt icoPositive" type="button" value="Yes, re-login as Admin" onClick="submitLoginForm(' . "'pageForm'" . ');"><span>Yes, re-login as Admin</span></button>
	      <button class="negative iconstxt icoNegative" type="button" value="No, logout" onClick="loadContent('. "'login/logout.php?l=1'" . ');"><span>No, logout</span></button>
	    </div>
	';
	exit;
}

$app->plugin->raiseEvent('logout',true);

$_SESSION["s"]["user"] = null;
$_SESSION["s"]["module"] = null;
$_SESSION['s_old'] = null;
session_write_close();

//header("Location: ../index.php?phpsessid=".$_SESSION["s"]["id"]);

if($_SESSION["s"]["site"]["logout"] != '') {
	echo('URL_REDIRECT:'.$_SESSION["s"]["site"]["logout"]);
} else {
	if($conf["interface_logout_url"] != '') {
		echo('URL_REDIRECT:'.$conf["interface_logout_url"]);
	} else {
		echo('URL_REDIRECT:index.php');
	}
}
exit;
?>