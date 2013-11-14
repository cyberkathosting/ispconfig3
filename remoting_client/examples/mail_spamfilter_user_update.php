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


	//* Get the mailing spamfilter user record
	$mail_spamfilter_user_record = $client->mail_spamfilter_user_get($session_id, $id);

	//* Change fullname to Hans Werner
	$mail_spamfilter_user_record['fullname'] = 'Hans Werner';

	$affected_rows = $client->mail_spamfilter_user_update($session_id, $client_id, $id, $mail_spamfilter_user_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
