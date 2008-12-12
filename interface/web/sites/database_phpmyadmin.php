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
$app->auth->check_module_permissions('sites');

/* get the id of the database (must be int!) */
if (!isset($_GET['id'])){
    die ("No DB selected!");
}
$databaseId = intval($_GET['id']);

/*
 * Get the data to connect to the database
 */
$dbData = $app->db->queryOneRecord(
    "SELECT sys_userid, sys_groupid, sys_perm_user, sys_perm_group, server_id, database_name, database_user, database_password FROM web_database WHERE database_id = " .
    $databaseId);

/*
 * We also need the data of the server
 */
$serverId = intval($dbData['server_id']);
if ($serverId == 0){
    die ("No DB-Server found!");
}

$serverData = $app->db->queryOneRecord(
    "SELECT server_name FROM server WHERE server_id = " .
    $serverId);

/*
 * Check if the user has the right to open phpmyadmin with this database
 * (we will check only users, not admins)
 */
if($_SESSION["s"]["user"]["typ"] == 'user') {
	/* Get the group of the client */
	$client_group_id = intval($_SESSION["s"]["user"]["default_group"]);
	/* compare both */
	if ($dbData['sys_groupid'] != $client_group_id){
	    die ("You don't have the right to access this db!");
	}
}

/*
 * Now generate the login-Form
 */
isset($_SERVER['HTTPS'])? $http = 'https' : $http = 'http';
echo '
starting phpMyAdmin...<br>
<form method="post" action="' . $http . '://' . $serverData['server_name'] . '/phpmyadmin/index.php" name="login_form" target="_top" style="visibility:hidden">
    <input type="text" name="pma_username" id="input_username" value="' .  $dbData['database_user'] . '" />
    <input type="password" name="pma_password" id="input_password" value="' . $dbData['database_password'] . '" size="24" class="textfield" />
</form>
<script type="text/javascript" language="javascript">
<!--
document.forms["login_form"].submit();
//-->
</script>';
?>