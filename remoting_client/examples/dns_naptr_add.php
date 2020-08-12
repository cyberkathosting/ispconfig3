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

	$timestamp = date("Y-m-d H:i:s");

	//* Set the function parameters.
	$client_id = 1;
	$params = array(
		'server_id' => 1,
		'zone' => 10,
		'name' => 'server',
		'type' => 'naptr',
		'data' => '100 "s" "thttp+L2R" "" thttp.example.com.',
		'aux' => '100',
		'ttl' => '3600',
		'active' => 'y',
		'stamp' => $timestamp,
		'serial' => '1',
	);

	$id = $client->dns_naptr_add($session_id, $client_id, $params);

	echo "ID: ".$id."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
