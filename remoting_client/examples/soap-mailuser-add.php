<?php

$username = 'admin';
$password = 'admin';

$soap_location = 'http://82.177.45.46:8080/remote/index.php';
$soap_uri = 'http://82.177.45.46:8080/remote/';


$client = new SoapClient(null, array('location' => $soap_location,
                                     'uri'      => $soap_uri));


try {
	if($session_id = $client->login($username,$password)) {
		echo 'Zalogowany. Sesja:'.$session_id.'<br />';
	}
	
	$params = array(	'server_id' => 1,
						'email' => 'franek@dsad.dsa',
						'password' => 'franek',
						'quota' => '10',
						'maildir' => '/var/vmail/dsad.dsa/franek',
						'homedir' => '/var/vmail',							'uid' => '5000',
						'gid' => '5000',
						'postfix' => 'y',
						'disableimap' => '0',
						'disablepop3' => '0');


	$client_id = 0;
	$domain_id = $client->mail_user_add($session_id,$client_id, $params);
	
	
	
	if($client->logout($session_id)) {
		echo 'Wylogowany.<br />';
	}
	
	
} catch (SoapFault $e) {
	die('SOAP Blad: '.$e->getMessage());
}

?>
