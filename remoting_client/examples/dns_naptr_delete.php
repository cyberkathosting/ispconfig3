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

	//* Parameters
	$id = 11;


	$affected_rows = $client->dns_naptr_delete($session_id, $id);

	echo "Number of records that have been deleted: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
