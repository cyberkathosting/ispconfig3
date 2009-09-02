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
	
	$params = array(	'server_id' => 1,
				'wb' => 'w',
				'rid' => '',
				'email' => 'ktos@replikant.eu',
				'priority' => '4',
				'active' => 'y');


	$client_id = 0;
	$domain_id = $client->mail_whitelist_add($session_id,$client_id,$params,$domain_id);
	
	
	
	if($client->logout($session_id)) {
		echo 'Wylogowany.<br />';
	}
	
	
} catch (SoapFault $e) {
	die('SOAP Blad: '.$e->getMessage());
}

?>
