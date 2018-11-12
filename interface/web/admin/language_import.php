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

function normalize_string($string, $quote, $allow_special = false) {
	$escaped = false;
	$in_string = true;
	$new_string = '';

	for($c = 0; $c < mb_strlen($string); $c++) {
		$char = mb_substr($string, $c, 1);

		if($in_string === true && $escaped === false && $char === $quote) {
			// this marks a string end (e.g. for concatenation)
			$in_string = false;
			continue;
		} elseif($in_string === false) {
			if($escaped === false && $char === $quote) {
				$in_string = true;
				continue;
			} else {
				continue; // we strip everything from outside the string!
			}
		}

		if($char === '"' && $escaped === true && $quote === '"') {
			// unescape this
			$new_string .= $char;
			$escaped = false;
			continue;
		} elseif($char === "'" && $escaped === false && $quote === '"') {
			// escape this
			$new_string .= '\\' . $char;
			continue;
		}

		if($escaped === true) {
			// the next character is the escaped one.
			if($allow_special === true && ($char === 'n' || $char === 'r' || $char === 't')) {
				$new_string .= '\' . "\\' . $char . '" . \'';
			} else {
				$new_string .= '\\' . $char;
			}
			$escaped = false;
		} else {
			if($char === '\\') {
				$escaped = true;
			} else {
				$new_string .= $char;
			}
		}
	}
	return $new_string;
}

function validate_line($line) {
	$line = trim($line);
	if($line === '' || $line === '<?php' || $line === '?>') return $line; // don't treat empty lines as malicious

	$ok = preg_match('/^\s*\$wb\[(["\'])(.*?)\\1\]\s*=\s*(["\'])(.*?)\\3\s*;\s*$/', $line, $matches);
	if(!$ok) return false; // this line has invalid form and could lead to malfunction

	$keyquote = $matches[1]; // ' or "
	$key = $matches[2];
	if(strpos($key, '"') !== false || strpos($key, "'") !== false) return false;

	$textquote = $matches[3]; // ' or "
	$text = $matches[4];

	$new_line = '$wb[\'';

	// validate the language key
	$key = normalize_string($key, $keyquote);

	$new_line .= $key . '\'] = \'';

	// validate this text to avoid code injection
	$text = normalize_string($text, $textquote, true);

	$new_line .= $text . '\';';

	return $new_line;
}

//* Check permissions for module
$app->auth->check_module_permissions('admin');
$app->auth->check_security_permissions('admin_allow_langedit');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');
if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

if(!$conf['language_file_import_enabled']) $app->error('Languge import function is disabled in the interface config.inc.php file.');

$app->uses('tpl');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/language_import.htm');
$msg = '';
$error = '';

// Export the language file
if(isset($_FILES['file']['name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
	
	//* CSRF Check
	$app->auth->csrf_token_check();
	
	$lines = file($_FILES['file']['tmp_name']);
	// initial check
	$parts = explode('|', $lines[0]);
	if($parts[0] == '---' && $parts[1] == 'ISPConfig Language File') {
		if($_POST['ignore_version'] != 1 && $parts[2] != $conf["app_version"]) {
			$error .= 'Application version does not match. Appversion: '.$conf["app_version"].' Lanfile version: '.$parts[2];
		} else {
			unset($lines[0]);

			$buffer = '';
			$langfile_path = '';
			// all other lines
			$ln = 1;
			foreach($lines as $line) {
				$ln++;
				$parts = explode('|', $line);
				if(is_array($parts) && count($parts) > 0 && $parts[0] == '--') {
					// Write language file, if its not the first file
					if($buffer != '' && $langfile_path != '') {
						$buffer = trim($buffer)."\n";
						if(@$_REQUEST['overwrite'] != 1 && @is_file($langfile_path)) {
							$error .= "File exists, not written: $langfile_path<br />";
						} else {
							$msg .= "File written: $langfile_path<br />";
							file_put_contents($langfile_path, $buffer);
						}
					}
					// empty buffer and set variables
					$buffer = '';
					$module_name = trim($parts[1]);
					$selected_language = trim($parts[2]);
					$file_name = trim($parts[3]);
					if(!preg_match("/^[a-z]{2}$/i", $selected_language)) die("unallowed characters in selected language name: $selected_language");
					if(!preg_match("/^[a-z_]+$/i", $module_name)) die('unallowed characters in module name.');
					if(!preg_match("/^[a-z\._\-]+$/i", $file_name) || stristr($file_name, '..')) die("unallowed characters in language file name: '$file_name'");
					if($module_name == 'global') {
						$langfile_path = trim(ISPC_LIB_PATH."/lang/".$selected_language.".lng");
					} else {
						$langfile_path = trim(ISPC_WEB_PATH.'/'.$module_name.'/lib/lang/'.$file_name);
					}
				} elseif(is_array($parts) && count($parts) > 1 && $parts[0] == '---' && $parts[1] == 'EOF') {
					// EOF line, ignore it.
				} else {
					$line = validate_line($line);
					if($line === false) $error .= "Language file contains invalid language entry on line $ln.<br />";
					else $buffer .= $line."\n";
				}
			}
		}
	}
}

$app->tpl->setVar('msg', $msg);
$app->tpl->setVar('error', $error);

//* SET csrf token
$csrf_token = $app->auth->csrf_token_get('language_import');
$app->tpl->setVar('_csrf_id',$csrf_token['csrf_id']);
$app->tpl->setVar('_csrf_key',$csrf_token['csrf_key']);

//* load language file
$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_language_import.lng';
include $lng_file;
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();


?>
