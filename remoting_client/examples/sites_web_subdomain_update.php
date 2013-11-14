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
	$subdomain_id = 10;
	$client_id = 1;


	//* Get the subdomain record
	$subdomain_record = $client->sites_web_subdomain_get($session_id, $subdomain_id);

	//* Change the status to inactive
	$subdomain_record['active'] = 'n';

	$affected_rows = $client->sites_web_subdomain_update($session_id, $client_id, $subdomain_id, $subdomain_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
