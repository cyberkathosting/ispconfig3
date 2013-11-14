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
		'domain' => 'test.int',
		'listname' => 'work',
		'email' => 'hmmnoe@test.int',
		'password' => 'hmmyea'
	);

	$mailinglist_id = $client->mail_mailinglist_add($session_id, $client_id, $params);

	echo "Mailinglist ID: ".$mailinglist_id."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
