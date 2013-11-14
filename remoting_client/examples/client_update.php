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
	$reseller_id = 1;
	$client_id = 1;


	//* Get the client record
	$client_record = $client->client_get($session_id, $client_id);

	//* Change parameters
	$client_record['country'] = 'de';
	$client_record['username'] = 'mguy';
	$client_record['contact_name'] = 'brush';

	//* We set the client password to a empty string as we do not want to change it.
	$client_record['password'] = '';

	$affected_rows = $client->client_update($session_id, $c_id, $reseller_id, $client_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
