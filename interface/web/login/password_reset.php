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

// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/password_reset.htm');

$app->tpl_defaults();

include(ISPC_ROOT_PATH.'/web/login/lib/lang/'.$_SESSION['s']['language'].'.lng');
$app->tpl->setVar($wb);

if(isset($_POST['username']) && $_POST['username'] != '' && $_POST['email'] != '' && $_POST['username'] != 'admin') {
	
	if(!preg_match("/^[\w\.\-\_]{1,64}$/", $_POST['username'])) die($app->lng('user_regex_error'));
	if(!preg_match("/^\w+[\w.-]*\w+@\w+[\w.-]*\w+\.[a-z]{2,10}$/i", $_POST['email'])) die($app->lng('email_error'));
	
	$username = $app->db->quote($_POST['username']);
	$email = $app->db->quote($_POST['email']);
	
	$client = $app->db->queryOneRecord("SELECT * FROM client WHERE username = '$username' AND email = '$email'");
	
	if($client['client_id'] > 0) {
		$new_password = md5 (uniqid (rand()));
		$salt="$1$";
		$base64_alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
		for ($n=0;$n<8;$n++) {
			//$salt.=chr(mt_rand(64,126));
			$salt.=$base64_alphabet[mt_rand(0,63)];
		}
		$salt.="$";
		$new_password_encrypted = crypt($new_password,$salt);
		$new_password_encrypted = $app->db->quote($new_password_encrypted);
		
		$username = $app->db->quote($client['username']);
		$app->db->query("UPDATE sys_user SET passwort = '$new_password_encrypted' WHERE username = '$username'");
		$app->db->query("UPDATE client SET ´password´ = '$new_password_encrypted' WHERE username = '$username'");
		$app->tpl->setVar("message",$wb['pw_reset']);
		
		mail($client['email'],$wb['pw_reset_mail_title'],$wb['pw_reset_mail_msg'].$new_password);
		
		$app->plugin->raiseEvent('password_reset',true);
		
	} else {
		$app->tpl->setVar("message",$wb['pw_error']);
	}
	
} else {
	$app->tpl->setVar("message",$wb['pw_error_noinput']);
}



$app->tpl_defaults();
$app->tpl->pparse();





?>