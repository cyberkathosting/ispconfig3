<?php

require 'soap_config.php';


$client = new SoapClient(null, array('location' => $soap_location,
		'uri'      => $soap_uri,
		'trace' => 1,
		'exceptions' => 1));


try {
	if($session_id = $client->login($username, $password)) {
		echo 'Logged successfull. Session ID:'.$session_id.'<br />';
	}

	//* Parameters
	$ip_id = 2;
	$client_id = 1;


	//* Get the openvz ip record
	$ip_record = $client->openvz_ip_get($session_id, $ip_id);

	//* Change the reservation to yes
	$ip_record['reserved'] = 'y';

	$affected_rows = $client->openvz_ip_update($session_id, $client_id, $ip_id, $ip_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
