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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

$app->uses('tpl');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/language_edit.htm');

$lang = $_REQUEST['lang'];
$module = $_REQUEST['module'];
$lang_file = $_REQUEST['lang_file'];

if(!preg_match("/^[a-z]+$/i", $lang)) die('unallowed characters in language name.');
if(!preg_match("/^[a-z_]+$/i", $module)) die('unallowed characters in module name.');
if(!preg_match("/^[a-z\._]+$/i", $lang_file)) die('unallowed characters in language file name.');

$msg = '';

//* Save data
if(isset($_POST['records']) && is_array($_POST['records'])) {
	$file_content = "<?php\n";
	foreach($_POST['records'] as $key => $val) {
		$val = stripslashes($val);
		$val = str_replace("'",'',$val);
		$val = str_replace('"','',$val);
		$file_content .= '$wb['."'$key'".'] = '."'$val';\n";
		$msg = 'File saved.';
	}
	$file_content .= "?>\n";
	if($module == 'global') {
		file_put_contents(ISPC_LIB_PATH."/lang/$lang_file" ,$file_content);
	} else {
		file_put_contents(ISPC_WEB_PATH."/$module/lib/lang/$lang_file" ,$file_content);
	}
}


$app->tpl->setVar(array('module' => $module,'lang_file' => $lang_file, 'lang' => $lang, 'msg' => $msg));

if($module == 'global') {
	include(ISPC_LIB_PATH."/lang/$lang_file");
	$file_path = ISPC_LIB_PATH."/lang/$lang_file";
} else {
	include(ISPC_WEB_PATH."/$module/lib/lang/$lang_file");
	$file_path = ISPC_LIB_PATH."/$module/lib/lang/$lang_file";
}
$app->tpl->setVar("file_path", $file_path);

$keyword_list = array();
if(isset($wb) && is_array($wb)) {
	foreach($wb as $key => $val) {
		$keyword_list[] = array('key' => $key, 'val' => $val);
	}

	$app->tpl->setLoop('records', $keyword_list);
	unset($wb);
}


//* load language file 
$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_language_edit.lng';
include($lng_file);
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();


?>
