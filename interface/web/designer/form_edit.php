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
$app->tpl->setInclude('content_tpl','templates/form_edit.htm');

// Importing variables
$module_name = $_REQUEST["module_name"];
$form_name = $_REQUEST["form_name"];

// Checking imported variables
if(!preg_match('/^[A-Za-z0-9_]{1,50}$/',$module_name)) die("module_name contains invalid chars.");
if(!preg_match('/^[A-Za-z0-9_]{0,50}$/',$form_name)) die("form_name contains invalid chars.");

$id = $form_name;

if(count($_POST) > 1) {
	// Bestimme aktion
	if($id != '') {
		$action = 'UPDATE';
	} else {
		$action = 'INSERT';
	}

	
	if($error == '') {
		
		$filename = "../".$module_name."/form/".$form_name.".tform.php";
		$form_new = $_POST["form"];
		
		if(@is_file($filename)) {
			include_once($filename);
			$tabs = $form["tabs"];
			unset($form["tabs"]);
			$form_new["tabs"] = $tabs;
		}
		
		$file_content = "<?php\r\n".'$form = '.var_export($form_new,true)."\r\n?>";
		
		die($file_content);
		
		// writing module.conf
		if (!$handle = fopen($filename, 'w')) { 
			print "Cannot open file ($filename)"; 
			exit; 
		} 

		if (!fwrite($handle, $file_content)) { 
			print "Cannot write to file ($filename)"; 
			exit; 
		} 
    
		fclose($handle);
		
		// zu Liste springen
    	header("Location: form_list.php");
        exit;
			
	} else {
		$app->tpl->setVar("error","<b>Fehler:</b><br>".$error);
		$app->tpl->setVar($_POST);
	}
}

if($id != '') {
// Datensatz besteht bereits
	// bestehenden Datensatz anzeigen
	if($error == '') {
		// es liegt ein Fehler vor
		include_once("../".$module_name."/form/".$form_name.".tform.php");
		//$tabs = $form["tabs"];
		unset($form["tabs"]);
		$record = $form;
		$record["form_name"] = $form_name;
		$record["module_name"] = $module_name;
		$record["auth_preset_userid"] = $form["auth_preset"]["userid"];
		$record["auth_preset_groupid"] = $form["auth_preset"]["groupid"];
		$record["auth_preset_perm_user"] = $form["auth_preset"]["perm_user"];
		$record["auth_preset_perm_group"] = $form["auth_preset"]["perm_group"];
		$record["auth_preset_perm_other"] = $form["auth_preset"]["perm_other"];
	} else {
		// ein Fehler
		$record = $_POST;
		//$navi = $_POST["nav"];
		unset($_POST["tabs"]);
	}
	$record["readonly"] = 'style="background-color: #EEEEEE;" readonly';
} else {
// neuer datensatz
	if($error == '') {
		// es liegt kein Fehler vor
		// Pewsets
		$record["template"] = "module.tpl.htm";
	} else {
		// ein Fehler
		$record = $_POST;
		unset($_POST["tabs"]);
		
	}
	$record["readonly"] = '';
}

$record["id"] = $form_name;

$app->tpl->setVar($record);

include_once("lib/lang/".$_SESSION["s"]["language"]."_form_edit.lng");
$app->tpl->setVar($wb);

// Defaultwerte setzen
$app->tpl_defaults();

// Template parsen
$app->tpl->pparse();

?>