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
		'veid' => 1,
		'ostemplate_id' => 0,
		'template_id' => 0,
		'ip_address' => '192.168.0.111',
		'hostname' => 'host',
		'vm_password' => 'password',
		'start_boot' => 'y',
		'active' => 'y',
		'active_until_date' => '',
		'description' => '',
		'diskspace' => 10,
		'traffic' => -1,
		'bandwidth' => -1,
		'ram' => 256,
		'ram_burst' => 512,
		'cpu_units' => 1000,
		'cpu_num' => 4,
		'cpu_limit' => 400,
		'io_priority' => 4,
		'nameserver' => '8.8.8.8 8.8.4.4',
		'create_dns' => 'n',
		'capability' => '',
		'config' => ''
	);

	$vm_id = $client->openvz_vm_add($session_id, $client_id, $params);

	echo "VM ID: ".$vm_id."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
