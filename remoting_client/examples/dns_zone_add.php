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
	$client_id = 2;
	$params = array(
		'server_id' => 1,
		'origin' => 'test.intt.',
		'ns' => 'one',
		'mbox' => 'zonemaster.test.tld.',
		'serial' => '1',
		'refresh' => '28800',
		'retry' => '7200',
		'expire' => '604800',
		'minimum' => '3600',
		'ttl' => '3600',
		'active' => 'y',
		'xfer' => '',
		'also_notify' => '',
		'update_acl' => '',
	);

	$id = $client->dns_zone_add($session_id, $client_id, $params);

	echo "DNS ID: ".$id."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
