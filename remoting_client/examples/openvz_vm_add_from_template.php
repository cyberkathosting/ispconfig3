<?php

$username = 'admin';
$password = 'admin';

$soap_location = 'http://localhost:8080/remote/index.php';
$soap_uri = 'http://localhost:8080/remote/';


$client = new SoapClient(null, array('location' => $soap_location,
		'uri'      => $soap_uri,
		'trace' => 1,
		'exceptions' => 1));


try {
	if($session_id = $client->login($username, $password)) {
		echo 'Logged successfull. Session ID:'.$session_id.'<br />';
	}

	//* Params are optional for this function. If params are set,
	//* then they override the template settings.
	$params = array();

	//* Set the function parameters.
	$client_id = 0;
	$ostemplate_id = 3;
	$template_id = 3;

	$vm_id = $client->openvz_vm_add_from_template($session_id, $client_id, $ostemplate_id, $template_id, $params);

	print_r($vm_id);
	echo "<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
