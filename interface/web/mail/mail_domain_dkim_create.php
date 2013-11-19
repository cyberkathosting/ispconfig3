<?php

/**
 Copyright (c) 2007 - 2013, Till Brehm, projektfarm Gmbh
 Copyright (c) 2013, Florian Schaal, info@schaal-24.de
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

/**
* This script is invoked by interface/js/mail_domain_dkim.js
* to generate or show the DKIM Private-key and to show the Private-key.
* returns DKIM Private-Key and DKIM Public-Key
*/


require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';
require_once '../../lib/classes/validate_dkim.inc.php';

$validate_dkim=new validate_dkim ();

//* Check permissions for module
$app->auth->check_module_permissions('mail');

header('Content-Type: text/xml; charset=utf-8');
header('Cache-Control: must-revalidate, pre-check=0, no-store, no-cache, max-age=0, post-check=0');

/**
 * This function fix PHP's messing up POST input containing characters space, dot,
 * open square bracket and others to be compatible with with the deprecated register_globals
 * @return array POST
 */
function getRealPOST() {
	$pairs = explode("&", file_get_contents("php://input"));
	$vars = array();
	foreach ($pairs as $pair) {
		$nv = explode("=", $pair, 2);
		$name = urldecode($nv[0]);
		$value = $nv[1];
		$vars[$name] = $value;
	}
	return $vars;
}

/**
 * This function formats the public-key
 * @param array $pubkey
 * @return string public-key
 */
function pub_key($pubkey) {
	$public_key='';
	foreach($pubkey as $values) $public_key=$public_key.$values."\n";
	return $public_key;
}

function get_public_key($private_key) {
	require_once('../../lib/classes/validate_dkim.inc.php');
	$validate_dkim=new validate_dkim ();
	if($validate_dkim->validate_post('private',$private_key)) { /* validate the $_POST-value */
		exec('echo '.escapeshellarg($private_key).'|openssl rsa -pubout -outform PEM',$pubkey,$result);
		$public_key=pub_key($pubkey);
	} else {
		$public_key='invalid key';
	}
	return $public_key;
}

$_POST=getRealPOST();

switch ($_POST['action']) {
	case 'create': /* create DKIM Private-key */
		exec('openssl rand -out /usr/local/ispconfig/server/temp/random-data.bin 4096', $output, $result);
		exec('openssl genrsa -rand /usr/local/ispconfig/server/temp/random-data.bin 1024', $privkey, $result);
		unlink("/usr/local/ispconfig/server/temp/random-data.bin");
		$private_key='';
	break;

	case 'show': /* show the DNS-Record onLoad */
		$private_key=$_POST['pkey'];
	break;
}

$public_key=get_public_key($private_key);
$dns_record=str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"),'',$public_key);
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
echo "<formatname>\n";
echo "<privatekey>".$private_key."</privatekey>\n";
echo "<publickey>".$public_key."</publickey>\n";
echo "<dns_record>v=DKIM1; t=s; p=".$dns_record."</dns_record>\n";
echo "</formatname>\n";
?>
