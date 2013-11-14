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
	$blacklist_id = 7;
	$client_id = 1;


	//* Get the mail blacklist record
	$mail_blacklist_record = $client->mail_blacklist_get($session_id, $blacklist_id);

	//* Change the status to inactive
	$mail_blacklist_record['active'] = 'n';

	$affected_rows = $client->mail_blacklist_update($session_id, $client_id, $blacklist_id, $mail_blacklist_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
