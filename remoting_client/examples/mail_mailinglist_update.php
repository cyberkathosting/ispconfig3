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
	$mailinglist_id = 1;
	$client_id = 1;


	//* Get the email mailinglist record
	$mailinglist_record = $client->mail_mailinglist_get($session_id, $mailinglist_id);

	//* Change the listname to home
	$mailinglist_record['listname'] = 'home';

	$affected_rows = $client->mail_mailinglist_update($session_id, $client_id, $mailinglist_id, $mailinglist_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
