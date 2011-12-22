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

if($_SESSION["s"]["user"]["typ"] != "admin") die("Admin permissions required.");

//* Check permissions for module
$app->auth->check_module_permissions('designer');

// Lade Template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/module_nav_edit.htm');

// ID importieren
$module_name = $_REQUEST["module_name"];
$nav_id = $_REQUEST["nav_id"];
$item_id = $_REQUEST["item_id"];

if(!preg_match('/^[A-Za-z0-9_]{1,50}$/',$module_name)) die("module_name contains invalid chars.");
if(!preg_match('/^[A-Za-z0-9_]{1,50}$/',$nav_id)) die("nav_id contains invalid chars.");
if(!preg_match('/^[A-Za-z0-9_]{0,50}$/',$item_id)) die("item_id contains invalid chars.");

if(empty($module_name)) die("module is empty.");

if($nav_id != '') {

	$filename = "../".$module_name."/lib/module.conf.php";
		
	if(!@is_file($filename)) die("File not found: $filename");
	include_once($filename);
	
	unset($module["nav"][$nav_id]["items"][$item_id]);
	
	$m = "<?php\r\n".'$module = '.var_export($module,true)."\r\n?>";
			
	// writing module.conf
	if (!$handle = fopen($filename, 'w')) { 
		print "Cannot open file ($filename)"; 
		exit; 
	} 
	if (!fwrite($handle, $m)) { 
		print "Cannot write to file ($filename)"; 
		exit; 
	} 
   
	fclose($handle);
	
	
	// zu Liste springen
   	header("Location: module_show.php?id=$module_name");
    exit;
}
?>