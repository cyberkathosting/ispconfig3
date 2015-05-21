<?php

/*
Copyright (c) 2014, Till Brehm, projektfarm Gmbh
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


$path = realpath(dirname(__FILE__) . '/..');
$iface_path = realpath(dirname(__FILE__) . '/../../interface/web');
$iface_base_path = realpath(dirname(__FILE__) . '/../../interface');

require $path . '/lib/mysql_clientdb.conf';

if(isset($argv[1])) $dbname = $argv[1];
else $dbname = 'dbispconfig';
if(!preg_match('/^[a-zA-Z0-9]+$/', $dbname)) die("Invalid database name\n");

$link = mysqli_init();
$con = mysqli_real_connect($link, $clientdb_host, $clientdb_user, $clientdb_password, $dbname);
if(!$con) die('DB CON ERROR' . "\n");

$qry = "SELECT username, passwort FROM sys_user WHERE active = '1'";
$result = mysqli_query($link, $qry);
if(!$result) die('Could not read users' . "\n");

$cont = '';
while($row = mysqli_fetch_assoc($result)) {
	$cont .= $row['username'] . ':' . $row['passwort'] . "\n";
}
mysqli_free_result($result);
mysqli_close($link);

if($cont == '') die('No users found' . "\n");

if(file_exists($iface_base_path . '/.htpasswd')) rename($iface_base_path . '/.htpasswd', $iface_base_path . '/.htpasswd.old');
file_put_contents($iface_base_path . '/.htpasswd', $cont);
chmod($iface_base_path . '/.htpasswd', 0644);

$cont = 'AuthType Basic
AuthName "Login"
AuthUserFile ' . $iface_base_path . '/.htpasswd
require valid-user';

if(file_exists($iface_path . '/.htaccess')) rename($iface_path . '/.htaccess', $iface_path . '/.htaccess.old');
file_put_contents($iface_path . '/.htaccess', $cont);
chmod($iface_path . '/.htaccess', 0644);
unset($cont);

print 'Data written. Please check, if everything is working correctly.' . "\n";
exit;

?>
