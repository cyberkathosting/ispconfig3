<?php

/*
Copyright (c) 2008 - 2015, Till Brehm, ISPConfig UG
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

$app->load('getconf');

$security_config = $app->getconf->get_security_config('permissions');
if($security_config['password_reset_allowed'] != 'yes') die('Password reset function has been disabled.');

// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate('main_login.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/password_reset.htm');

$app->tpl_defaults();

include ISPC_ROOT_PATH.'/web/login/lib/lang/'.$_SESSION['s']['language'].'.lng';
$app->tpl->setVar($wb);
$continue = true;

if(isset($_POST['username']) && $_POST['username'] != '' && $_POST['email'] != '' && $_POST['username'] != 'admin') {

	if(!preg_match("/^[\w\.\-\_]{1,64}$/", $_POST['username'])) {
		$app->tpl->setVar("error", $wb['user_regex_error']);
		$continue = false;
	}
	if(!preg_match("/^\w+[\w.-]*\w+@\w+[\w.-]*\w+\.[a-z]{2,10}$/i", $_POST['email'])) {
		$app->tpl->setVar("error", $wb['email_error']);
		$continue = false;
	}

	$username = $_POST['username'];
	$email = $_POST['email'];

	$client = $app->db->queryOneRecord("SELECT client.*, sys_user.lost_password_function FROM client,sys_user WHERE client.username = ? AND client.email = ? AND client.client_id = sys_user.client_id", $username, $email);

	if($client['lost_password_function'] == 0) {
		$app->tpl->setVar("error", $wb['lost_password_function_disabled_txt']);
	} elseif ($continue) {
		if($client['client_id'] > 0) {
			$server_config_array = $app->getconf->get_global_config();
			$min_password_length = 8;
			if(isset($server_config_array['misc']['min_password_length'])) $min_password_length = $server_config_array['misc']['min_password_length'];
			
			$new_password = $app->auth->get_random_password($min_password_length, true);
			$new_password_encrypted = $app->auth->crypt_password($new_password);

			$username = $client['username'];
			$app->db->query("UPDATE sys_user SET passwort = ? WHERE username = ?", $new_password_encrypted, $username);
			$app->db->query("UPDATE client SET password = ? WHERE username = ?", $new_password_encrypted, $username);
			$app->tpl->setVar("message", $wb['pw_reset']);

			$app->uses('getconf,ispcmail');
			$mail_config = $server_config_array['mail'];
			if($mail_config['smtp_enabled'] == 'y') {
				$mail_config['use_smtp'] = true;
				$app->ispcmail->setOptions($mail_config);
			}
			$app->ispcmail->setSender($mail_config['admin_mail'], $mail_config['admin_name']);
			$app->ispcmail->setSubject($wb['pw_reset_mail_title']);
			$app->ispcmail->setMailText($wb['pw_reset_mail_msg'].$new_password);
			$app->ispcmail->send(array($client['contact_name'] => $client['email']));
			$app->ispcmail->finish();

			$app->plugin->raiseEvent('password_reset', true);
			$app->tpl->setVar("msg", $wb['pw_reset']);
		} else {
			$app->tpl->setVar("error", $wb['pw_error']);
		}
	}
} else {
	if(isset($_POST) && count($_POST) > 0) $app->tpl->setVar("msg", $wb['pw_error_noinput']);
}

$app->tpl->setVar('current_theme', isset($_SESSION['s']['theme']) ? $_SESSION['s']['theme'] : 'default');

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
$app->tpl->setVar('company_name', $sys_config['company_name']. ' :: ');

$app->tpl_defaults();
$app->tpl->pparse();





?>
