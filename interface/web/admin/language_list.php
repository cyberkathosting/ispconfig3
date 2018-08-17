<?php
/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
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
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

$app->uses('tpl');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/language_list.htm');

$language_files_list = array();
$bgcolor = '#FFFFFF';

//* reading languages
$language_option = '';
$selected_language = (isset($_REQUEST['lng_select']))?substr($_REQUEST['lng_select'], 0, 2):$_SESSION['s']['language'];
$handle = opendir(ISPC_ROOT_PATH.'/lib/lang/');
while ($file = readdir($handle)) {
	if ($file != '.' && $file != '..') {
		$tmp_lng = substr($file, 0, -4);
		if($tmp_lng !='') {
			$selected = ($tmp_lng == $selected_language)?'SELECTED':'';
			$language_option .= "<option value='$tmp_lng' $selected>$tmp_lng</option>";

			//$bgcolor = ($bgcolor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
			if($file == $selected_language.'.lng') {
				$language_files_list[] = array( 'module' => 'global',
					'lang_file' => $file,
					'lang_file_date' => date("Y-m-d H:i:s", filectime(ISPC_ROOT_PATH.'/lib/lang/'.$file)),
					'bgcolor'  => $bgcolor,
					'lang' => $selected_language);
			}


		}
	}
}
$app->tpl->setVar('language_option', $language_option);
// $app->tpl->setLoop('records', $language_list);

//* list all language files of the selected language
$handle = @opendir(ISPC_WEB_PATH);
while ($file = @readdir($handle)) {
	if ($file != '.' && $file != '..') {
		if(@is_dir(ISPC_WEB_PATH.'/'.$file.'/lib/lang')) {
			$handle2 = opendir(ISPC_WEB_PATH.'/'.$file.'/lib/lang');
			while ($lang_file = @readdir($handle2)) {
				if ($lang_file != '.' && $lang_file != '..' && substr($lang_file, 0, 2) == $selected_language) {
					$bgcolor = ($bgcolor == '#FFFFFF') ? '#EEEEEE' : '#FFFFFF';
					$language_files_list[] = array( 'module' => $file,
						'lang_file' => $lang_file,
						'lang_file_date' => date("Y-m-d H:i:s", filectime(ISPC_WEB_PATH.'/'.$file.'/lib/lang/'.$lang_file)),
						'bgcolor'  => $bgcolor,
						'lang' => $selected_language);
				}
			}
		}
	}
}

$app->tpl->setLoop('records', $language_files_list);




//* load language file
$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_language_list.lng';
include $lng_file;
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();


?>
