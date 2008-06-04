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

//* Check permissions for module
$app->auth->check_module_permissions('designer');

if($_SESSION["s"]["user"]["typ"] != "admin") die("Admin permissions required.");

$app->uses('tpl');

$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/form_show.htm');


// TODO: Check ID for malicius chars
$module_name = $_REQUEST["module_name"];
$form_name = $_REQUEST["form_name"];

if(!preg_match('/^[A-Za-z0-9_]{1,50}$/',$module_name)) die("module_name contains invalid chars.");
if(!preg_match('/^[A-Za-z0-9_]{1,50}$/',$form_name)) die("form_name contains invalid chars.");

include_once("../".$module_name."/form/".$form_name.".tform.php");
$tabs = $form["tabs"];
unset($form["tabs"]);
$record = $form;
$record["form_name"] = $form_name;
$record["module_name"] = $module_name;

// loading language file 
$lng_file = "lib/lang/".$_SESSION["s"]["language"]."_form_show.lng";
include($lng_file);
$app->tpl->setVar($wb);

// baue Tabs navi
$content = "";
$n1 = 0;
$n2 = 0;
if(is_array($tabs)) {
foreach($tabs as $tab_id => $tab) {
	$content .= "<tr>
    <td colspan='2' class='frmText11'>
	  <table width='100%'>
	  	<tr>
		  <td class='tblHead'>$tab[title]</td>
		  <td class='tblHead' width='220' align='right'>
		  <input type=\"button\" name=\"bt1$n2\" value=\"$wb[edit_txt]\" onClick=\"location.href='form_tab_edit.php?module_name=$module_name&form_name=$form_name&tab_id=$tab_id'\" class=\"button\" />
		  <input type=\"button\" name=\"bt2$n2\" value=\"$wb[delete_txt]\" onClick=\"del_menu('form_tab_del.php?module_name=$module_name&form_name=$form_name&tab_id=$tab_id');\" class=\"button\" />
		  <input type=\"button\" name=\"bt3$n2\" value=\"$wb[new_txt]\" onClick=\"location.href='form_tab_item_edit.php?module_name=$module_name&form_name=$form_name&tab_id=$tab_id'\" class=\"button\" />
		  <input type=\"button\" name=\"bt4$n2\" value=\"$wb[up_txt]\" onClick=\"location.href='form_tab_flip.php?module_name=$module_name&form_name=$form_name&tab_id=$tab_id&dir=up'\" class=\"button\" />
		  <input type=\"button\" name=\"bt5$n2\" value=\"$wb[down_txt]\" onClick=\"location.href='form_tab_flip.php?module_name=$module_name&form_name=$form_name&tab_id=$tab_id&dir=down'\" class=\"button\" />
		  </td>
		</tr>";
	//$content .= "<tr><td bgcolor='#EEEEEE' class='frmText11'>Bereich:</td><td class='frmText11' bgcolor='#EEEEEE'><input name=\"module[nav][$n1][title]\" type=\"text\" class=\"text\" value=\"$section[title]\" size=\"30\" maxlength=\"255\"><input name=\"module[nav][$n1][open]\" type=\"hidden\" value=\"$section[open]\"></td></tr>\r\n";
	foreach($tab["fields"] as $field_id => $field) {
		//$content .= "<tr><td class='frmText11'>Titel:</td><td class='frmText11'><input name=\"module[nav][$n1][items][$n2][title]\" type=\"text\" class=\"text\" value=\"$item[title]\" size=\"30\" maxlength=\"255\"></td></tr>\r\n";
		//$content .= "<tr><td class='frmText11'>Ziel:</td><td class='frmText11'>&nbsp; &nbsp; &nbsp; &nbsp;<input name=\"module[nav][$n1][items][$n2][target]\" type=\"text\" class=\"text\" value=\"$item[target]\" size=\"10\" maxlength=\"255\"></td></tr>\r\n";
		//$content .= "<tr><td class='frmText11'>Link:</td><td class='frmText11'>&nbsp; &nbsp; &nbsp; &nbsp;<input name=\"module[nav][$n1][items][$n2][link]\" type=\"text\" class=\"text\" value=\"$item[link]\" size=\"30\" maxlength=\"255\"></td></tr>\r\n";
		$content .= "<tr>
		  <td class='frmText11'>$field_id</td>
		  <td class='frmText11' width='220' align='right'>
		  <input type=\"button\" name=\"bt6$n2\" value=\"$wb[edit_txt]\" onClick=\"location.href='form_field_edit.php?module_name=$module_name&form_name=$form_name&tab_id=$tab_id&field_id=$field_id'\" class=\"button\" />
		  <input type=\"button\" name=\"bt7$n2\" value=\"$wb[delete_txt]\" onClick=\"del_menuitem('form_field_del.php?module_name=$module_name&form_name=$form_name&tab_id=$tab_id&field_id=$field_id');\" class=\"button\" />
		  <input type=\"button\" name=\"bt8$n2\" value=\"$wb[up_txt]\" onClick=\"location.href='form_field_flip.php?module_name=$module_name&form_name=$form_name&tab_id=$tab_id&field_id=$field_id&dir=up'\" class=\"button\" />
		  <input type=\"button\" name=\"bt9$n2\" value=\"$wb[down_txt]\" onClick=\"location.href='form_field_flip.php?module_name=$module_name&form_name=$form_name&tab_id=$tab_id&field_id=$field_id&dir=down'\" class=\"button\" />
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