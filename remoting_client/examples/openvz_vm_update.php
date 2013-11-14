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
	$vm_id = 1;
	$client_id = 1;


	//* Get the virtual machine record
	$vm_record = $client->openvz_vm_get($session_id, $vm_id);

	//* Change active to no
	$vm_record['active'] = 'n';

	$affected_rows = $client->openvz_vm_update($session_id, $client_id, $vm_id, $vm_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
