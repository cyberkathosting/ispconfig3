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
	$database_user_id = 1;
	$client_id = 1;


	//* Get the database record
	$database_user_record = $client->sites_database_user_get($session_id, $database_user_id);

	//* Change password of the database user
	$database_user_record['database_password'] = 'abcde';

	$affected_rows = $client->sites_database_user_update($session_id, $client_id, $database_user_id, $database_user_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
