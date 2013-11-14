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
	$wblist_id = 2;
	$client_id = 1;


	//* Get the spamfilter blacklist record
	$mail_wblist_record = $client->mail_spamfilter_blacklist_get($session_id, $wblist_id);

	//* Change the priority to 2
	$mail_wblist_record['priority'] = 2;

	$affected_rows = $client->mail_spamfilter_blacklist_update($session_id, $client_id, $wblist_id, $mail_wblist_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
