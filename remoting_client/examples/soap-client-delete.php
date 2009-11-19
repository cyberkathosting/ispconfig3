<?php

require('soap_config.php');


$client = new SoapClient(null, array('location' => $soap_location,
                                     'uri'      => $soap_uri));


try {
	if($session_id = $client->login($username,$password)) {
		echo 'Login successfull. SessionID:'.$session_id.'<br />';
	}

	$client_id = 3;
	$affected_rows = $client->client_delete($session_id, $client_id);
	
	
	
	if($client->logout($session_id)) {
		echo 'Logout.<br />';
	}
	
	
} catch (SoapFault $e) {
	die('SOAP Error: '.$e->getMessage());
}

?>
