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
	$whitelist_id = 5;
	$client_id = 1;


	//* Get the email whitelist record
	$mail_whitelist_record = $client->mail_whitelist_get($session_id, $whitelist_id);

	//* Change the status to inactive
	$mail_whitelist_record['active'] = 'n';

	$affected_rows = $client->mail_whitelist_update($session_id, $client_id, $whitelist_id, $mail_whitelist_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
