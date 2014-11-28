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

$app->load('getconf');

$security_config = $app->getconf->get_security_config('permissions');
if($security_config['password_reset_allowed'] != 'yes') die('Password reset function has been disabled.');

// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/password_reset.htm');

$app->tpl_defaults();

include ISPC_ROOT_PATH.'/web/login/lib/lang/'.$_SESSION['s']['language'].'.lng';
$app->tpl->setVar($wb);

if(isset($_POST['username']) && $_POST['username'] != '' && $_POST['email'] != '' && $_POST['username'] != 'admin') {

	if(!preg_match("/^[\w\.\-\_]{1,64}$/", $_POST['username'])) die($app->lng('user_regex_error'));
	if(!preg_match("/^\w+[\w.-]*\w+@\w+[\w.-]*\w+\.[a-z]{2,10}$/i", $_POST['email'])) die($app->lng('email_error'));

	$username = $app->db->quote($_POST['username']);
	$email = $app->db->quote($_POST['email']);

	$client = $app->db->queryOneRecord("SELECT client.*, sys_user.lost_password_function FROM client,sys_user WHERE client.username = ? AND client.email = ? AND client.client_id = sys_user.client_id", $username, $email);

	if($client['lost_password_function'] == 0) {
		$app->tpl->setVar("error", $wb['lost_password_function_disabled_txt']);
	} else {
		if($client['client_id'] > 0) {
			$new_password = $app->auth->get_random_password();
			$new_password_encrypted = $app->auth->crypt_password($new_password);
			$new_password_encrypted = $app->db->quote($new_password_encrypted);

			$username = $app->db->quote($client['username']);
			$app->db->query("UPDATE sys_user SET passwort = '$new_password_encrypted' WHERE username = '$username'");
			$app->db->query("UPDATE client SET password = '$new_password_encrypted' WHERE username = '$username'");
			$app->tpl->setVar("message", $wb['pw_reset']);

			$app->uses('getconf,ispcmail');
			$mail_config = $app->getconf->get_global_config('mail');
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
	$app->tpl->setVar("msg", $wb['pw_error_noinput']);
}


$app->tpl_defaults();
$app->tpl->pparse();





?>
