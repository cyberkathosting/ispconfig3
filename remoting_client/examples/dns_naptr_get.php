<?php

require 'soap_config.php';


$context = stream_context_create( array(
	'ssl' => array(
		// set some SSL/TLS specific options
		'verify_peer' => false,
		'verify_peer_name' => false,
		'allow_self_signed' => true
	),
));


$client = new SoapClient(null, array('location' => $soap_location,
		'uri'      => $soap_uri,
		'trace' => 1,
		'exceptions' => 1,
		'stream_context' => $context));


try {
	if($session_id = $client->login($username, $password)) {
		echo 'Logged successfull. Session ID:'.$session_id.'<br />';
	}

	//* Set the function parameters.
	$id = 11;

	$dns_record = $client->dns_naptr_get($session_id, $id);

	print_r($dns_record);

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
