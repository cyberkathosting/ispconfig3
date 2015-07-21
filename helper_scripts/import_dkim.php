<?php

/**
 Copyright (c) 2015, Florian Schaal, schaal @it
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


/* define your settings here */
$username = 'admin';
$password = 'admin';
$soap_location = 'http://192.168.0.105:8080/remote/index.php';
$soap_uri = 'http://192.168.0.105:8080/remote/';
/* stop editing */


error_reporting(E_ALL^ E_WARNING);

exec('which amavisd-new 2> /dev/null', $tmp_output, $tmp_retval);
if ($tmp_retval != 0) {
	exec('which amavisd 2> /dev/null', $tmp_output, $tmp_retval);
	if ($tmp_retval == 0) $amavis = $tmp_output[0];
} else $amavis = $tmp_output[0];

if (!isset($amavis)) die ("amavisd not found");


echo "Importing dkim-settings from amavis.\n\nTo import the settings even when the public-key is not available, use ".$argv[0]." --force\nNOTE: In force-mode dkim will be set to 'no' if no public-key was found.\n\n";

if ( isset($argv) && isset ($argv[1]) && $argv[1] == '--force' ) $force = true; else $force = false;

$client = new SoapClient(null, array('location' => $soap_location,
	'uri'      => $soap_uri,
	'trace' => 1,
	'exceptions' => 1));


exec($amavis.' showkeys', $tmp_output, $tmp_retval);

foreach ( $tmp_output as $line ) {
	//* get domain and private key-file
	if ( preg_match('#^; key#', $line) ) {
		$line_array = explode(' ', $line);
		if ( $line_array[2] = 'domain' ) {
			$domain = rtrim($line_array[3], ',');
			$private_keyfile = $line_array[4];
			//* get the public-key from private-key
			unset($public_key);
			unset($pubkey);
			unset($private_key);
			$private_key = file_get_contents($private_keyfile);
			if ( isset($private_key) && !empty($private_key)) {
				exec('echo '.escapeshellarg($private_key).'|openssl rsa -pubout -outform PEM 2> /dev/null',$pubkey,$result);
				$public_key='';
				foreach($pubkey as $values) $public_key=$public_key.$values."\n";
			}
		}
	}

	//* get selector
	if ( isset($domain) ) {
		if ( preg_match('/_domainkey.'.$domain.'.* TXT \(/', $line) ) {
			$line_array = explode(' ', $line);
			$selector = substr ( $line_array[0], 0, strpos($line_array[0], '.') );
		}
	}

	if ( isset($domain) && isset($selector) && isset($private_keyfile) && isset($public_key) ) {
		
		try {
			if ( !$session_id = $client->login($username, $password) ) {
				echo 'SOAP-ERROR: Can´t login';
			}

			echo "\nprocessing ".$domain."...\n";

			$record = $client->mail_domain_get_by_domain($session_id, $domain);

			if ( !empty($record) ) {
				$record = $record[0];
				echo "  OK: domain exists in the database\n";
				//* check if the public-key is available
				exec($amavis.' testkeys '.escapeshellarg($domain).'', $test_output, $test_retval);
				$pub_key = false;
				if ( preg_match('/^TESTING.*'.$selector.'._domainkey.'.$domain.'.*pass/',$test_output[0]) ) $pub_key = true;
   				$client_id = $client->client_get_id($session_id, $record['sys_userid']);
				unset($test_output);
				if ( $pub_key ) {
					$record['dkim_selector'] = $selector;
					$record['dkim'] = 'y';
					if ( preg_match("/(^-----BEGIN PUBLIC KEY-----)[a-zA-Z0-9\r\n\/\+=]{1,221}(-----END PUBLIC KEY-----(\n|\r)?$)/", $record['dkim_public'] ) ) {
						$record['dkim_public'] = $public_key;
						echo "  OK: public key\n";
					} else {
						$record['dkim_public'] = '';
						$record['dkim'] = 'n';
						echo "  ERROR: public key invalid\n  disable dkim for ".$domain."\n";
					}
					if ( preg_match("/(^-----BEGIN RSA PRIVATE KEY-----)[a-zA-Z0-9\r\n\/\+=]{1,850}(-----END RSA PRIVATE KEY-----(\n|\r)?$)/", $private_key) ) {
						$record['dkim_private'] = $private_key;
						echo "  OK: private key\n";
					} else {
						$record['dkim_private'] = '';
						$record['dkim'] = 'n';
						echo "  ERROR: private key invalid\n  disable dkim for ".$domain."\n";
					}
					$client->mail_domain_update($session_id, $client_id, $record['domain_id'], $record);
					echo "  OK: updating database\n";
				} else {
					echo "  ERROR: no public-key available - skipping ".$domain."\n";
				}
			} else {
				echo "  ERROR: domain not in the database - skipping ".$domain."\n";
			}
			$client->logout($session_id);
		} catch (SoapFault $e) {
			echo $client->__getLastResponse();
			die('SOAP Error: '.$e->getMessage());
		}
		unset($domain);
		unset($selector);
	}
}
?>
