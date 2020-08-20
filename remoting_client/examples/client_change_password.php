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
	$new_password = 'YourNewPassword';

    $success = $client->client_change_password($session_id, $client_id, $new_password);

	if ($success == 1)
	{
        echo "Password has been changed successfully";
	}
	else
	{
		echo "Error";
	}

	echo "<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
