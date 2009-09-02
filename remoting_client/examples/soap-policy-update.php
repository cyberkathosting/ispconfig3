<?php

$username = 'admin';
$password = 'admin';

$soap_location = 'http://localhost:8080/remote/index.php';
$soap_uri = 'http://localhost:8080/remote/';


$client = new SoapClient(null, array('location' => $soap_location,
                                     'uri'      => $soap_uri));


try {
	if($session_id = $client->login($username,$password)) {
		echo 'Zalogowany. Sesja:'.$session_id.'<br />';
	}
	
	$params = array(        'policy_name' => 'nazwa',
                                'virus_lover' => 'n',
                                'spam_lover' => 'y',
                                'banned_files_lover' => 'n',
                                'bad_header_lover' => 'y',
                                'bypass_virus_checks' => 'n',
                                'bypass_banned_checks' => 'y',
                                'bypass_header_checks' => 'n');



	$client_id = 0;
	$mailuser_id = 10;
	$domain_id = $client->mail_policy_update($session_id, $mailuser_id, $client_id, $params);
	
	
	
	if($client->logout($session_id)) {
		echo 'Wylogowany.<br />';
	}
	
	
} catch (SoapFault $e) {
	die('SOAP Blad: '.$e->getMessage());
}

?>
