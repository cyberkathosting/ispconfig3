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
	

	//transport -> typ:[adres@email] - jeśli z klamrą to włączona opcja brak mx
		
	$params = array(	'server_id' => 1,
				'domain' => 'replikant.eu',
				'transport' => 'uucp:[arian@replikant.eu]',
				'sort_order' => '5',
				'active' => 'y');


	$client_id = 0;
	$mailuser_id = 1;
	$domain_id = $client->mail_transport_update($session_id, $mailuser_id, $client_id, $params);
	
	
	
	if($client->logout($session_id)) {
		echo 'Wylogowany.<br />';
	}
	
	
} catch (SoapFault $e) {
	die('SOAP Blad: '.$e->getMessage());
}

?>
