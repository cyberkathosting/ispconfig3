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


	//* Get the policy record
	$mail_policy_record = $client->mail_policy_get($session_id, $id);

	//* Change policy_name to Hans_Werner
	$mail_policy_record['policy_name'] = 'Hans_Werner';

	$affected_rows = $client->mail_policy_update($session_id, $client_id, $id, $mail_policy_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
