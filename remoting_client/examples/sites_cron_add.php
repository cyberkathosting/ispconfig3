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

	$params = array(
		'server_id' => 1,
		'parent_domain_id' => 1,
		'type' => 'url',
		'command' => 'echo 1',
		'run_min' => '1',
		'run_hour' => '1',
		'run_mday' => '1',
		'run_month' => '1',
		'run_wday' => '1',
		'active' => 'y',
	);

	$affected_rows = $client->sites_cron_add($session_id, $client_id, $params);

	echo "Cron ID: ".$affected_rows."<br>";


	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
