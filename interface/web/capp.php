<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

require_once('../lib/config.inc.php');
require_once('../lib/app.inc.php');

//* Import module variable
$mod = $_REQUEST["mod"];

//* Check if user is logged in
if($_SESSION["s"]["user"]['active'] != 1) {
	header("Location: index.php?phpsessid=".$_SESSION["s"]["id"]);
	die();
}

//* Check if user may use the module.
$user_modules = explode(",",$_SESSION["s"]["user"]["modules"]);

if(!in_array($mod,$user_modules)) $app->error($app->lng(301));

//* Load module configuration into the session.
if(is_file($mod."/lib/module.conf.php")) {
	include_once($mod."/lib/module.conf.php");
	$_SESSION["s"]["module"] = $module;
	session_write_close();
	echo "HEADER_REDIRECT:".$_SESSION["s"]["module"]["startpage"];
} else {
	$app->error($app->lng(302));
}
?>