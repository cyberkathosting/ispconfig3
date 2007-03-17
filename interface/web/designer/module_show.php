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

// Checking permissions for the module
if(!stristr($_SESSION["s"]["user"]["modules"],$_SESSION["s"]["module"]["name"])) {
	header("Location: ../index.php");
	exit;
}

if($_SESSION["s"]["user"]["typ"] != "admin") die("Admin permissions required.");

$app->uses('tpl');

$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/module_show.htm');


$module_name = $_REQUEST["id"];
if(!preg_match('/^[A-Za-z0-9_]{0,50}$/',$module_name)) die("id contains invalid chars.");

include_once("../".$module_name."/lib/module.conf.php");
$navi = $module["nav"];
unset($module["nav"]);
$record = $module;

// loading language file 
$lng_file = "lib/lang/".$_SESSION["s"]["user"]["language"]."_module_show.lng";
include($lng_file);
$app->tpl->setVar($wb);

// baue Modul navi
$content = "";
$n1 = 0;
$n2 = 0;
if(is_array($navi)) {
foreach($navi as $nav_id => $section) {
	$content .= "<tr>
    <td colspan='2' class='frmText11'>
	  <table width='100%'>
	  	<tr>
		  <td class='tblHead'>$section[title]</td>
		  <td class='tblHead' width='280' align='right'>
		  <input type=\"button\" name=\"bt1$n2\" value=\"$wb[edit_txt]\" onClick=\"loadContent('designer/module_nav_edit.php?module_name=$module_name&nav_id=$nav_id');\" class=\"button\" /><div class=\"buttonEnding\"></div>
		  <input type=\"button\" name=\"bt2$n2\" value=\"$wb[delete_txt]\" onClick=\"del_record('designer/module_nav_del.php?module_name=$module_name&nav_id=$nav_id');\" class=\"button\" /><div class=\"buttonEnding\"></div>
		  <input type=\"button\" name=\"bt3$n2\" value=\"$wb[new_txt]\" onClick=\"loadContent('designer/module_nav_item_edit.php?module_name=$module_name&nav_id=$nav_id');\" class=\"button\" /><div class=\"buttonEnding\"></div>
		  <input type=\"button\" name=\"bt4$n2\" value=\"$wb[up_txt]\" onClick=\"loadContent('designer/module_nav_flip.php?module_name=$module_name&nav_id=$nav_id&dir=up');\" class=\"button\" /><div class=\"buttonEnding\"></div>
		  <input type=\"button\" name=\"bt5$n2\" value=\"$wb[down_txt]\" onClick=\"loadContent('designer/module_nav_flip.php?module_name=$module_name&nav_id=$nav_id&dir=down');\" class=\"button\" /><div class=\"buttonEnding\"></div>
		  </td>
		</tr>";
	//$content .= "<tr><td bgcolor='#EEEEEE' class='frmText11'>Bereich:</td><td class='frmText11' bgcolor='#EEEEEE'><input name=\"module[nav][$n1][title]\" type=\"text\" class=\"text\" value=\"$section[title]\" size=\"30\" maxlength=\"255\"><input name=\"module[nav][$n1][open]\" type=\"hidden\" value=\"$section[open]\"></td></tr>\r\n";
	foreach($section["items"] as $item_id => $item) {
		//$content .= "<tr><td class='frmText11'>Titel:</td><td class='frmText11'><input name=\"module[nav][$n1][items][$n2][title]\" type=\"text\" class=\"text\" value=\"$item[title]\" size=\"30\" maxlength=\"255\"></td></tr>\r\n";
		//$content .= "<tr><td class='frmText11'>Ziel:</td><td class='frmText11'>&nbsp; &nbsp; &nbsp; &nbsp;<input name=\"module[nav][$n1][items][$n2][target]\" type=\"text\" class=\"text\" value=\"$item[target]\" size=\"10\" maxlength=\"255\"></td></tr>\r\n";
		//$content .= "<tr><td class='frmText11'>Link:</td><td class='frmText11'>&nbsp; &nbsp; &nbsp; &nbsp;<input name=\"module[nav][$n1][items][$n2][link]\" type=\"text\" class=\"text\" value=\"$item[link]\" size=\"30\" maxlength=\"255\"></td></tr>\r\n";
		$content .= "<tr>
		  <td class='frmText11'>$item[title]</td>
		  <td class='frmText11' width='280' align='right'>
		  <input type=\"button\" name=\"bt6$n2\" value=\"$wb[edit_txt]\" onClick=\"loadContent('designer/module_nav_item_edit.php?module_name=$module_name&nav_id=$nav_id&item_id=$item_id');\" class=\"button\" /><div class=\"buttonEnding\"></div>
		  <input type=\"button\" name=\"bt7$n2\" value=\"$wb[delete_txt]\" onClick=\"del_record('designer/module_nav_item_del.php?module_name=$module_name&nav_id=$nav_id&item_id=$item_id');\" class=\"button\" /><div class=\"buttonEnding\"></div>
		  <input type=\"button\" name=\"bt8$n2\" value=\"$wb[up_txt]\" onClick=\"loadContent('designer/module_nav_item_flip.php?module_name=$module_name&nav_id=$nav_id&item_id=$item_id&dir=up');\" class=\"button\" /><div class=\"buttonEnding\"></div>
		  <input type=\"button\" name=\"bt9$n2\" value=\"$wb[down_txt]\" onClick=\"loadContent('designer/module_nav_item_flip.php?module_name=$module_name&nav_id=$nav_id&item_id=$item_id&dir=down');\" class=\"button\" /><div class=\"buttonEnding\"></div>
		  </td>
		</tr>";
		$n2++;
	}
	$content .= "<tr><td colspan='2' class='tblFooter'>&nbsp;</td></tr>
	  </table>
	</td>
  </tr>";
	$n1++;
}
}

$record["nav"] = $content;


$app->tpl->setVar($record);


$app->tpl->setLoop('records',$modules_list);

$app->tpl_defaults();
$app->tpl->pparse();



?>