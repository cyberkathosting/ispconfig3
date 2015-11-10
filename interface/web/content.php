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

require_once '../lib/config.inc.php';
require_once '../lib/app.inc.php';

/*
$module = $_REQUEST["s_mod"];
$page = $_REQUEST["s_pg"];
*/

$module = 'dashboard';
$page = 'dashboard';

if(!preg_match("/^[a-z]{2,20}$/i", $module)) die('module name contains unallowed chars.');
if(!preg_match("/^[a-z]{2,20}$/i", $page)) die('page name contains unallowed chars.');

if(is_file(ISPC_WEB_PATH."/$module/$page.php")) {

	include_once ISPC_WEB_PATH."/$module/$page.php";

	$classname = $module.'_'.$page;
	$page = new $classname();

	$content = $page->render();
	if($page->status == 'OK') {
		echo $content;
	} elseif($page->status == 'REDIRECT') {
		$target_parts = explode(':', $page->target);
		$module = $target_parts[0];
		$page = $target_parts[1];
		if(!preg_match("/^[a-z]{2,20}$/i", $module)) die('target module name contains unallowed chars.');
		if(!preg_match("/^[a-z]{2,20}$/i", $page)) die('target page name contains unallowed chars.');

		if(is_file(ISPC_WEB_PATH."/$module/$page.php")) {
			include_once ISPC_WEB_PATH."/$module/$page.php";

			$classname = $module.'_'.$page;
			$page = new $classname();

			$content = $page->render();
			if($page->status == 'OK') {
				echo $content;
			}
		}

	}

} elseif (is_array($_SESSION["s"]['user']) or is_array($_SESSION["s"]["module"])) {
	// If the user is logged in, we try to load the default page of the module
	die('- error -');
} else {
	die('Page does not exist.');
}

?>
