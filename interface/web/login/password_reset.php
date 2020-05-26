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

include ISPC_ROOT_PATH.'/web/login/lib/lang/'.$app->functions->check_language($conf['language']).'.lng';
$app->tpl->setVar($wb);
$continue = true;

if(isset($_POST['username']) && $_POST['username'] != '' && $_POST['email'] != '' && $_POST['username'] != 'admin') {
	if(!preg_match("/^[\w\.\-\_]{1,64}$/", $_POST['username'])) {
		$app->tpl->setVar("error", $wb['user_regex_error']);
		$continue = false;
	}
	if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
		$app->tpl->setVar("error", $wb['email_error']);
		$continue = false;
	}

	$username = $_POST['username'];
	$email = $_POST['email'];

	$client = $app->db->queryOneRecord("SELECT client.*, sys_user.lost_password_function, sys_user.lost_password_hash, IF(sys_user.lost_password_reqtime IS NOT NULL AND DATE_SUB(NOW(), INTERVAL 15 MINUTE) < sys_user.lost_password_reqtime, 1, 0) as `lost_password_wait` FROM client,sys_user WHERE client.username = ? AND client.email = ? AND client.client_id = sys_user.client_id", $username, $email);

	if($client['lost_password_function'] == 0) {
		$app->tpl->setVar("error", $wb['lost_password_function_disabled_txt']);
	} elseif($client['lost_password_wait'] == 1) {
		$app->tpl->setVar("error", $wb['lost_password_function_wait_txt']);
	} elseif ($continue) {
		if($client['client_id'] > 0) {
			$username = $client['username'];
			$password_hash = sha1(uniqid('ispc_pw'));
			$app->db->query("UPDATE sys_user SET lost_password_reqtime = NOW(), lost_password_hash = ? WHERE username = ?", $password_hash, $username);

			$server_domain = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST']);
			if($server_domain == '_') {
				$tmp = explode(':',$_SERVER["HTTP_HOST"]);
				$server_domain = $tmp[0];
				unset($tmp);
			}
			if(!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != 'on') $server_domain = 'http://' . $server_domain;
			else $server_domain = 'https://' . $server_domain;

			if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '443') $server_domain .= ':' . $_SERVER['SERVER_PORT'];

			$app->uses('getconf,ispcmail');
			$server_config_array = $app->getconf->get_global_config();
			$mail_config = $server_config_array['mail'];
			if($mail_config['smtp_enabled'] == 'y') {
				$mail_config['use_smtp'] = true;
				$app->ispcmail->setOptions($mail_config);
			}
			$app->ispcmail->setSender($mail_config['admin_mail'], $mail_config['admin_name']);
			$app->ispcmail->setSubject($wb['pw_reset_act_mail_title']);
			$app->ispcmail->setMailText($wb['pw_reset_act_mail_msg'].$server_domain . '/login/password_reset.php?username=' . urlencode($username) . '&hash=' . urlencode($password_hash));
			$send_result = $app->ispcmail->send(array($client['contact_name'] => $client['email']));
			$app->ispcmail->finish();

			if($send_result !== false) {
				$app->tpl->setVar("msg", $wb['pw_reset_act']);
			} else {
				$app->tpl->setVar("error", $wb['pw_reset_error_smtp_connection']);
			}
		} else {
			$app->tpl->setVar("error", $wb['pw_error']);
		}
	}
} elseif(isset($_GET['username']) && $_GET['username'] != '' && $_GET['hash'] != '') {

	if(!preg_match("/^[\w\.\-\_]{1,64}$/", $_GET['username'])) {
		$app->tpl->setVar("error", $wb['user_regex_error']);
		$continue = false;
	}
	
	$username = $_GET['username'];
	$hash = $_GET['hash'];

	$client = $app->db->queryOneRecord("SELECT client.*, sys_user.lost_password_function, sys_user.lost_password_hash, IF(sys_user.lost_password_reqtime IS NULL OR DATE_SUB(NOW(), INTERVAL 1 DAY) > sys_user.lost_password_reqtime, 1, 0) as `lost_password_expired` FROM client,sys_user WHERE client.username = ? AND client.client_id = sys_user.client_id", $username);

	if($client['lost_password_function'] == 0) {
		$app->tpl->setVar("error", $wb['lost_password_function_disabled_txt']);
	} elseif($client['lost_password_expired'] == 1) {
		$app->tpl->setVar("error", $wb['lost_password_function_expired_txt']);
	} elseif($client['lost_password_hash'] != $hash) {
		$app->tpl->setVar("error", $wb['lost_password_function_denied_txt']);
	} elseif ($continue) {
		if($client['client_id'] > 0) {
			$server_config_array = $app->getconf->get_global_config();
			$min_password_length = 8;
			if(isset($server_config_array['misc']['min_password_length'])) $min_password_length = $server_config_array['misc']['min_password_length'];
			
			$new_password = $app->auth->get_random_password($min_password_length, true);
			$new_password_encrypted = $app->auth->crypt_password($new_password);

			$username = $client['username'];
			$app->db->query("UPDATE sys_user SET passwort = ?, lost_password_hash = '', lost_password_reqtime = NULL WHERE username = ?", $new_password_encrypted, $username);
			$app->db->query("UPDATE client SET password = ? WHERE username = ?", $new_password_encrypted, $username);

			$app->uses('getconf,ispcmail');
			$mail_config = $server_config_array['mail'];
			if($mail_config['smtp_enabled'] == 'y') {
				$mail_config['use_smtp'] = true;
				$app->ispcmail->setOptions($mail_config);
			}
			$app->ispcmail->setSender($mail_config['admin_mail'], $mail_config['admin_name']);
			$app->ispcmail->setSubject($wb['pw_reset_mail_title']);
			$app->ispcmail->setMailText($wb['pw_reset_mail_msg'].$new_password);
			$send_result = $app->ispcmail->send(array($client['contact_name'] => $client['email']));
			$app->ispcmail->finish();

			$app->plugin->raiseEvent('password_reset', true);

			if($send_result !== false) {
				$app->tpl->setVar("msg", $wb['pw_reset']);
			} else {
				$app->tpl->setVar("error", $wb['pw_reset_error_smtp_connection']);
			}

		} else {
			$app->tpl->setVar("error", $wb['pw_error']);
		}
	}
} else {
	if(isset($_POST) && count($_POST) > 0) $app->tpl->setVar("msg", $wb['pw_error_noinput']);
}

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
$app->tpl->setVar('company_name', $sys_config['company_name']. ' :: ');

$app->tpl_defaults();
$app->tpl->pparse();





?>
