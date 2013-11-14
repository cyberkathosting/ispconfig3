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

	//* Set the function parameters.
	$client_id = 1;
	$params = array(
		'mailuser_id' => '0',
		'rulename' => 'NULL',
		'source' => '',
		'searchterm' => 'NULL',
		'op' => '',
		'action' => '',
		'target' => '',
		'active' => 'y'
	);

	$filter_id = $client->mail_user_filter_add($session_id, $client_id, $params);

	echo "Filter ID: ".$filter_id."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
