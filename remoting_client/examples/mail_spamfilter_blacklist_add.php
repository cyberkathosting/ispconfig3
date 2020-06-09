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
		'server_id' => 1,
		'wb' => 'B',
		'rid' => '', // Fill in an ID from the spamfilter_users table.
		'email' => 'hmmnoe@test.int',
		'priority' => 1,
		'active' => 'y'
	);

	$affected_rows = $client->mail_spamfilter_blacklist_add($session_id, $client_id, $params);

	echo "Blacklist ID: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
