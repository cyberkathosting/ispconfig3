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
if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

//* Check permissions for module
$app->auth->check_module_permissions('designer');

// Lade Template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/module_edit.htm');

// ID importieren
$id = $_REQUEST["id"];
if(!preg_match('/^[A-Za-z0-9_]{0,50}$/',$id)) die("id contains invalid chars.");

if(count($_POST) > 1) {
	// Bestimme aktion
	if($id != '') {
		$action = 'UPDATE';
	} else {
		$action = 'INSERT';
	}

	
	if($error == '') {
		
		$id = $_POST["module"]["name"];
		if(!preg_match('/^[A-Za-z0-9_]{0,50}$/',$id)) die("id contains invalid chars.");
		
		$filename = "../".$id."/lib/module.conf.php";
		$module_new = $_POST["module"];
		
		if(@is_file($filename)) {
			include_once($filename);
			$navi = $module["nav"];
			unset($module);
			$module_new["nav"] = $navi;
		}
		
		$m = "<?php\r\n".'$module = '.var_export($module_new,true)."\r\n?>";
		
		// creating the module directories
		if(!@is_dir("../".$id)) mkdir("../".$id) or die("Cannot make directory: ../".$id);
		if(!@is_dir("../".$id."/lib")) mkdir("../".$id."/lib") or die("Cannot make directory: ../".$id."/lib");
		if(!@is_dir("../".$id."/lib/lang")) mkdir("../".$id."/lib/lang") or die("Cannot make directory: ../".$id."/lib/lang");
		if(!@is_dir("../".$id."/form")) mkdir("../".$id."/form") or die("Cannot make directory: ../".$id."/form");
		if(!@is_dir("../".$id."/list")) mkdir("../".$id."/list") or die("Cannot make directory: ../".$id."/list");
		if(!@is_dir("../".$id."/templates")) mkdir("../".$id."/templates") or die("Cannot make directory: ../".$id."/templates");
		
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
		
		// writing admin conf
		$admin_conf_filename = "../".$id."/lib/admin.conf.php";
		if(!is_file($admin_conf_filename)) {
			if (!$handle = fopen($admin_conf_filename, 'w')) { 
				print "Cannot open file ($admin_conf_filename)"; 
				exit; 
			} 

			if (!fwrite($handle, "<?php\r\n?>")) { 
				print "Cannot write to file ($admin_conf_filename)"; 
				exit; 
			} 
    
			fclose($handle); 
		}
		
		// zu Liste springen
    	header("Location: module_list.php");
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
		include_once("../".$id."/lib/module.conf.php");
		//$navi = $module["nav"];
		unset($module["nav"]);
		$record = $module;
	} else {
		// ein Fehler
		$record = $_POST;
		//$navi = $_POST["nav"];
		unset($_POST["nav"]);
	}
	$record["readonly"] = 'style="background-color: #EEEEEE;" readonly';
} else {
// neuer datensatz
	if($error == '') {
		// es liegt ein Fehler vor
		$record["template"] = "module.tpl.htm";
	} else {
		// ein Fehler
		$record = $_POST;
		//$navi = $_POST["nav"];
		unset($_POST["nav"]);
		
	}
	$record["readonly"] = '';
}

$record["id"] = $id;

/*
// baue Modul navi
$content = "";
$n1 = 0;
$n2 = 0;
foreach($navi as $section) {
	$content .= "<tr><td bgcolor='#EEEEEE' class='frmText11'>Bereich:</td><td class='frmText11' bgcolor='#EEEEEE'><input name=\"module[nav][$n1][title]\" type=\"text\" class=\"text\" value=\"$section[title]\" size=\"30\" maxlength=\"255\"><input name=\"module[nav][$n1][open]\" type=\"hidden\" value=\"$section[open]\"></td></tr>\r\n";
	foreach($section["items"] as $item) {
		$content .= "<tr><td class='frmText11'>Titel:</td><td class='frmText11'><input name=\"module[nav][$n1][items][$n2][title]\" type=\"text\" class=\"text\" value=\"$item[title]\" size=\"30\" maxlength=\"255\"></td></tr>\r\n";
		$content .= "<tr><td class='frmText11'>Ziel:</td><td class='frmText11'>&nbsp; &nbsp; &nbsp; &nbsp;<input name=\"module[nav][$n1][items][$n2][target]\" type=\"text\" class=\"text\" value=\"$item[target]\" size=\"10\" maxlength=\"255\"></td></tr>\r\n";
		$content .= "<tr><td class='frmText11'>Link:</td><td class='frmText11'>&nbsp; &nbsp; &nbsp; &nbsp;<input name=\"module[nav][$n1][items][$n2][link]\" type=\"text\" class=\"text\" value=\"$item[link]\" size=\"30\" maxlength=\"255\"></td></tr>\r\n";
		$n2++;
	}
	$n1++;
}

$record["nav"] = $content;
*/

$app->tpl->setVar($record);

include_once("lib/lang/".$_SESSION["s"]["language"]."_module_edit.lng");
$app->tpl->setVar($wb);

// Defaultwerte setzen
$app->tpl_defaults();

// Template parsen
$app->tpl->pparse();

?>