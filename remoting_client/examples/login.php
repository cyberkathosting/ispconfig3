<?php

require 'soap_config.php';


$client = new SoapClient(null, array('location' => $soap_location,
		'uri'      => $soap_uri,
		'trace' => 1,
		'exceptions' => 1));


try {
	$session_id = $client->login($username, $password);
	echo 'Logged successfull. Session ID:'.$session_id.'<br />';
	echo "Logging out: ";
	$client->logout($session_id);
	echo "Logged out.";


}catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
