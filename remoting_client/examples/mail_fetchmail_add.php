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
		'type' => 'pop3',
		'source_server' => 'webzor.ext',
		'source_username' => 'someguy',
		'source_password' => 'somepassword',
		'source_delete' => 'n',
		'destination' => 'hmmnoe@test.int',
		'active' => 'y',
		'source_read_all' => 'y',
	);

	$fetchmail_id = $client->mail_fetchmail_add($session_id, $client_id, $params);

	echo "Fetchmail ID: ".$fetchmail_id."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
