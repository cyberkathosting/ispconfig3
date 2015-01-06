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
* returns DKIM keys, selector, and dns-record
*/


require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';
require_once '../../lib/classes/validate_dkim.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('mail');

header('Content-Type: text/xml; charset=utf-8');
header('Cache-Control: must-revalidate, pre-check=0, no-store, no-cache, max-age=0, post-check=0');

function validate_domain($domain) {
	$regex = '/^[\w\.\-]{2,255}\.[a-zA-Z0-9\-]{2,30}$/';
	if ( preg_match($regex, $domain) === 1 ) return true; else return false;
}

function validate_selector($selector) {
	$regex = '/^[a-z0-9]{0,63}$/';
	if ( preg_match($regex, $selector) === 1 ) return true; else return false;
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

function get_public_key($private_key, $dkim_strength) {
	$validate_dkim=new validate_dkim ();
	if($validate_dkim->validate_post('private', $private_key, $dkim_strength)) { /* validate the $_POST-value */
		exec('echo '.escapeshellarg($private_key).'|openssl rsa -pubout -outform PEM 2> /dev/null',$pubkey,$result);
		$public_key=pub_key($pubkey);
	} else {
		$public_key='invalid key';
	}
	return $public_key;
}

/**
 * This function updates the selector if a new key-pair was created
 * and the selector is already used in the dns-record
 * @param string $old_selector
 * @return string selector
 */
function new_selector ($old_selector, $domain, $client_id = -1) {
	global $app;
	//* validate post-values
	if ( validate_domain($domain) && validate_selector($old_selector) ) {
		//* get active selectors from dns
		$soa_rec = $app->db->queryOneRecord("SELECT * FROM dns_soa WHERE active = 'Y' AND origin = ?");
		if ( isset($soa_rec) && !empty($soa_rec) ) {
			//* check for a dkim-record in the dns?
			$dns_data = $app->db->queryOneRecord("SELECT name FROM dns_rr WHERE name = ? AND active = 'Y''", $old_selector.'._domainkey.'.$domain.'.');
			if ( !empty($dns_data) ){
				$selector = str_replace( '._domainkey.'.$domain.'.', '', $dns_data['name']);
			} else {
			}
		} else { //* no dns-zone found - check for existing mail-domain to create a new selector (we need this if a external dns is used)
			if ( $client_id >= 0 ) {
				$sql = "SELECT * from mail_domain WHERE dkim = 'y' AND domain = ? AND dkim_selector = ?";
				$maildomain =  $app->db->queryOneRecord($sql, $domain, $old_selector);
				if ( !empty($maildomain) ) {
					$selector = $maildomain['selector'];
				}
			}
		}
		if ( $old_selector == $selector) {
			$selector = substr($old_selector, 0, 53) . time(); //* add unix-timestamp to delimiter to allow old and new key in the dns
		} else {
			$selector = $old_selector;
		}
	} else {
		$selector = 'invalid domain or selector';
	}
	return $selector;
}

$client_id = $app->functions->intval($_POST['client_id']);

//* get dkim-strength for server_id
$sql = "SELECT server_id from mail_domain WHERE domain = ?";
$mail_server = $app->db->queryOneRecord($sql, $_POST['domain']);
if ( is_array($mail_server) ) { //* we are adding an existing mail-domain
	$mail_server_id = $app->functions->intval( $mail_server['server_id'] );
} else {
	$sql = "SELECT default_mailserver FROM client WHERE client_id = ?";
	$mail_server = $app->db->queryOneRecord($sql, $client_id);
	$mail_server_id = $app->functions->intval( $mail_server['default_mailserver'] );
}
unset($mail_server);
$mail_config = $app->getconf->get_server_config($mail_server_id, 'mail');
$dkim_strength = $app->functions->intval($mail_config['dkim_strength']);
unset($mail_config);

if ( empty($dkim_strength) ) $dkim_strength = 2048;

$rnd_val = $dkim_strength * 10;
exec('openssl rand -out ../../temp/random-data.bin '.$rnd_val.' 2> /dev/null', $output, $result);
exec('openssl genrsa -rand ../../temp/random-data.bin '.$dkim_strength.' 2> /dev/null', $privkey, $result);
unlink("../../temp/random-data.bin");
foreach($privkey as $values) $private_key=$private_key.$values."\n";
//* check the selector for updated dkim-settings only
if ( isset($_POST['dkim_public']) && !empty($_POST['dkim_public']) ) $selector = new_selector($_POST['dkim_selector'], $_POST['domain'], $client_id); 

if ( !isset($public_key) ) $public_key=get_public_key($private_key, $dkim_strength);

$dns_record=str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n"),'',$public_key);

if ( !isset($selector) ) {
	if ( validate_selector($_POST['dkim_selector']) ) $selector=$_POST['dkim_selector']; 
}
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
echo "<formatname>\n";
echo "<selector>".$selector."</selector>\n";
echo "<privatekey>".$private_key."</privatekey>\n";
echo "<publickey>".$public_key."</publickey>\n";
if ( validate_domain($_POST['domain']) ) {
	echo '<dns_record>'.$selector.'._domainkey.'.$_POST['domain'].'. 3600	TXT	"v=DKIM1; t=s; p='.$dns_record.'"</dns_record>';
}
echo "</formatname>\n";
?>
