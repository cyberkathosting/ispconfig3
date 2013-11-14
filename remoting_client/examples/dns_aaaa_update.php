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
	$id = 1;
	$client_id = 1;


	//* Get the dns record
	$dns_record = $client->dns_aaaa_get($session_id, $id);

	//* Change active to inactive
	$dns_record['active'] = 'n';

	$affected_rows = $client->dns_aaaa_update($session_id, $client_id, $id, $dns_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
