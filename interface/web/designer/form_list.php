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

$app->uses('tpl');

$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/form_list.htm');

function getinfo($file,$form_file,$bgcolor) {
	global $conf,$app;
	$module_name = $file;
	include($conf["rootpath"]."/web/".$file."/form/$form_file");
	return array( 	'name' => $form["name"],
					'title' => $form["title"],
					'description' => $form["description"],
					'module_name' => $module_name,
					'bgcolor' => $bgcolor);
	//unset($form);
}

// lese Module aus
$bgcolor = "#FFFFFF";
$modules_list = array();
$handle = @opendir($conf["rootpath"]."/web"); 
while ($file = @readdir ($handle)) { 
    if ($file != "." && $file != "..") {
        if(@is_dir($conf["rootpath"]."/web/".$file)) {
            if(is_file($conf["rootpath"]."/web/".$file."/lib/module.conf.php") and $file != 'login') {
				
				if(@is_dir($conf["rootpath"]."/web/".$file."/form")) {
					$handle2 = opendir($conf["rootpath"]."/web/".$file."/form");
					while ($form_file = @readdir ($handle2)) {
						if (substr($form_file,0,1) != ".") {
						    //echo $conf["rootpath"]."/web/".$file."/form/$form_file<br>";
							//include_once($conf["rootpath"]."/web/".$file."/form/$form_file");
							// Farbwechsel
							$bgcolor = ($bgcolor == "#FFFFFF")?"#EEEEEE":"#FFFFFF";
				
							$modules_list[] = getinfo($file,$form_file,$bgcolor);
							
						}
					}
				}
			}
        }
	}
}


$app->tpl->setLoop('records',$modules_list);

// loading language file 
$lng_file = "lib/lang/".$_SESSION["s"]["language"]."_form_list.lng";
include($lng_file);
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();



?>