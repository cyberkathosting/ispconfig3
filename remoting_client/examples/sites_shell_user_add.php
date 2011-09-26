<?php

require('soap_config.php');


$client = new SoapClient(null, array('location' => $soap_location,
                                     'uri'      => $soap_uri,
									 'trace' => 1,
									 'exceptions' => 1));


try {
	if($session_id = $client->login($username,$password)) {
		echo 'Logged successfull. Session ID:'.$session_id.'<br />';
	}
	
	//* Set the function parameters.
	$client_id = 3;
	
	$params = array(
			'server_id' => 1,
			'parent_domain_id' => 1,
			'username' => 'threep2',
			'password' => 'wood',
			'quota_size' => 10000,
			'active' => 'y',
			'puser' => 'null',
			'pgroup' => 'null',
			'shell' => '/bin/bash',
			'dir' => 'maybe',
			'chroot' => ''
			);
	
	$affected_rows = $client->sites_shell_user_add($session_id, $client_id, $params);
	
	echo "Shell User ID: ".$affected_rows."<br>";

	
	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}
	
	
} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
