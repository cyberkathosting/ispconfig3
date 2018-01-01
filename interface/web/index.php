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

// Check if we have an active users ession and redirect to login if thats not the case.
if($_SESSION['s']['user']['active'] != 1) {
	header('Location: /login/');
	die();
}

if(!isset($_SESSION['s']['module']['name'])) $_SESSION['s']['module']['name'] = 'dashboard';

$app->uses('tpl');
$app->tpl->newTemplate('main.tpl.htm');
$app->tpl->setVar('startpage', isset($_SESSION['s']['module']['startpage']) ? $_SESSION['s']['module']['startpage'] : '', true);
$app->tpl->setVar('logged_in', ($_SESSION['s']['user']['active'] != 1 ? 'n' : 'y'));

// tab change warning?
// read misc config
$app->uses('getconf');
$sys_config = $app->getconf->get_global_config('misc');
if($sys_config['tab_change_warning'] == 'y') {
	$app->tpl->setVar('tabchange_warning_enabled', 'y');
	$app->tpl->setVar('global_tabchange_warning_txt', $app->lng('global_tabchange_warning_txt'));
} else {
	$app->tpl->setVar('tabchange_warning_enabled', 'n');
}
$app->tpl->setVar('tabchange_discard_enabled', $sys_config['tab_change_discard']);
if($sys_config['tab_change_discard'] == 'y') {
	$app->tpl->setVar('global_tabchange_discard_txt', $app->lng('global_tabchange_discard_txt'));
}

if($sys_config['use_loadindicator'] == 'y') {
	$app->tpl->setVar('use_loadindicator', 'y');
}
if($sys_config['use_combobox'] == 'y') {
	$app->tpl->setVar('use_combobox', 'y');
}


if(isset($_SESSION['show_info_msg'])) {
	$app->tpl->setVar('show_info_msg', $_SESSION['show_info_msg']);
	unset($_SESSION['show_info_msg']);
}
if(isset($_SESSION['show_error_msg'])) {
	$app->tpl->setVar('show_error_msg', $_SESSION['show_error_msg']);
	unset($_SESSION['show_error_msg']);
}

// read js.d files
$js_d = ISPC_WEB_PATH . '/js/js.d';
$js_d_files = array();
if(@is_dir($js_d)) {
	$dir = opendir($js_d);
	while($file = readdir($dir)) {
		$filename = $js_d . '/' . $file;
		if($file === '.' || $file === '..' || !is_file($filename)) continue;
		if(substr($file, -3) !== '.js') continue;
		$js_d_files[] = array('file' => $file);
	}
	closedir($dir);
}

if (!empty($js_d_files)) $app->tpl->setLoop('js_d_includes', $js_d_files);
unset($js_d_files);

$app->tpl->setVar('current_theme', isset($_SESSION['s']['theme']) ? $_SESSION['s']['theme'] : 'default', true);

// Logo
$logo = $app->db->queryOneRecord("SELECT * FROM sys_ini WHERE sysini_id = 1");
if($logo['custom_logo'] != ''){
	$base64_logo_txt = $logo['custom_logo'];
} else {
	$base64_logo_txt = $logo['default_logo'];
}
$tmp_base64 = explode(',', $base64_logo_txt, 2);
$logo_dimensions = $app->functions->getimagesizefromstring(base64_decode($tmp_base64[1]));
$app->tpl->setVar('base64_logo_width', $logo_dimensions[0].'px');
$app->tpl->setVar('base64_logo_height', $logo_dimensions[1].'px');
$app->tpl->setVar('base64_logo_txt', $base64_logo_txt);

// Title
if (!empty($sys_config['company_name'])) {
	$app->tpl->setVar('company_name', $sys_config['company_name']. ' :: ');
}

$app->tpl_defaults();
$app->tpl->pparse();
?>
