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
 * This script is invoked by interface/web/dns/templates/dns_dkim_edit.htm
 * when generating the DKIM Private-key.
 *
 * return DKIM Public-Key for the DNS-record
 */
require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('dns');

global $app, $conf;

// Loading classes
$app->uses('tform,tform_actions');

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
	foreach($pubkey as $values) $public_key=$public_key.$values;
	return $public_key;
}

$_POST=getRealPost();

if (ctype_digit($_POST['zone'])) {
	// Get the parent soa record of the domain
	$soa = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE id = '".$app->db->quote($_POST['zone'])."' AND ".$app->tform->getAuthSQL('r'));

	$public_key=$app->db->queryOneRecord("SELECT dkim_public FROM mail_domain WHERE domain = '".substr_replace($soa['origin'], '', -1)."' AND ".$app->tform->getAuthSQL('r'));

	$public_key=pub_key($public_key);

	$public_key=str_replace(array('-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\r", "\n"), '', $public_key);

	echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
	echo "<formatname>\n";
	echo "<data>".$public_key."</data>\n";
	echo "<name>".$soa['origin']."</name>\n";
	echo "</formatname>\n";
}
?>
