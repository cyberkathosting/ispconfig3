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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('designer');

$app->uses('tpl');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/form_list.htm');

function getinfo($file, $form_file, $bgcolor) {
	$module_name = $file;
	include ISPC_WEB_PATH."/$file/form/$form_file";
	return array(  'name' =>        $form['name'],
		'title' =>       $form['title'],
		'description' => $form['description'],
		'module_name' => $module_name,
		'bgcolor' =>     $bgcolor
	);
}

// lese Module aus
$bgcolor = '#FFFFFF';
$modules_list = array();
$handle = @opendir(ISPC_WEB_PATH);
while ($file = @readdir($handle)) {
	if ($file != '.' && $file != '..') {
		if(@is_dir(ISPC_WEB_PATH."/$file")) {
			if(is_file(ISPC_WEB_PATH.'/'.$file.'/lib/module.conf.php') and $file != 'login') {
				if(@is_dir(ISPC_WEB_PATH."/$file/form")) {
					$handle2 = opendir(ISPC_WEB_PATH."/$file/form");
					while ($form_file = @readdir($handle2)) {
						if (substr($form_file, 0, 1) != ".") {
							//echo ISPC_ROOT_PATH."/web/".$file."/form/$form_file<br>";
							//include_once(ISPC_ROOT_PATH."/web/".$file."/form/$form_file");
							// Farbwechsel
							$bgcolor = ($bgcolor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
							$modules_list[] = getinfo($file, $form_file, $bgcolor);

						}
					}
				}
			}
		}
	}
}

$app->tpl->setLoop('records', $modules_list);

//* load language file
$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_form_list.lng';
include $lng_file;
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();


?>
