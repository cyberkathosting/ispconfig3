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
$app->tpl->setInclude('content_tpl', 'templates/language_import.htm');
$msg = '';
$error = '';

// Export the language file
if(isset($_FILES['file']['name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
	$lines = file($_FILES['file']['tmp_name']);
	// initial check
	$parts = explode('|',$lines[0]);
	if($parts[0] == '---' && $parts[1] == 'ISPConfig Language File') {
		if($_POST['ignore_version'] != 1 && $parts[2] != $conf["app_version"]) {
			$error .= 'Application version does not match. Appversion: '.$conf["app_version"].' Lanfile version: '.$parts[2];
		} else {
			unset($lines[0]);
			
			$buffer = '';
			$langfile_path = '';
			// all other lines
			foreach($lines as $line) {
				$parts = explode('|',$line);
				if(is_array($parts) && count($parts) > 0 && $parts[0] == '--') {
					// Write language file, if its not the first file
					if($buffer != '' && $langfile_path != '') {
						if(@$_REQUEST['overwrite'] != 1 && @is_file($langfile_path)) {
							$error .= "File exists, not written: $langfile_path<br />";
						} else {
							$msg .= "File written: $langfile_path<br />";
							file_put_contents($langfile_path,$buffer);
						}
					}
					// empty buffer and set variables
					$buffer = '';
					$module_name = trim($parts[1]);
					$selected_language = trim($parts[2]);
					$file_name = trim($parts[3]);
					if(!preg_match("/^[a-z]{2}$/i", $selected_language)) die("unallowed characters in selected language name: $selected_language");
					if(!preg_match("/^[a-z_]+$/i", $module_name)) die('unallowed characters in module name.');
					if(!preg_match("/^[a-z\._]+$/i", $file_name) || stristr($file_name,'..')) die("unallowed characters in language file name: '$file_name'");
					if($module_name == 'global') {
						$langfile_path = trim(ISPC_LIB_PATH."/lang/".$selected_language.".lng");
					} else {
						$langfile_path = trim(ISPC_WEB_PATH.'/'.$module_name.'/lib/lang/'.$file_name);
					}
				} else {
					$buffer .= $line;
				}
			}
		}
	}
}

$app->tpl->setVar('msg',$msg);
$app->tpl->setVar('error',$error);

//* load language file 
$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_language_import.lng';
include($lng_file);
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();


?>