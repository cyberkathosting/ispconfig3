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
	$template_id = 2;
	$client_id = 1;


	//* Get the openvz template record
	$template_record = $client->openvz_template_get($session_id, $template_id);

	//* Change the status to inactive
	$template_record['active'] = 'n';

	$affected_rows = $client->openvz_template_update($session_id, $client_id, $template_id, $template_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
