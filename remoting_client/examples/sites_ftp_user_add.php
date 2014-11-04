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
		'parent_domain_id' => 1,
		'username' => 'tom',
		'password' => 'secret',
		'quota_size' => 10000,
		'active' => 'y',
		'uid' => '5000',
		'gid' => '5000',
		'dir' => '/var/www/clients/client0/web1',
		'quota_files' => -1,
		'ul_ratio' => -1,
		'dl_ratio' => -1,
		'ul_bandwidth' => -1,
		'dl_bandwidth' => -1
	);

	$affected_rows = $client->sites_ftp_user_add($session_id, $client_id, $params);

	echo "FTP User ID: ".$affected_rows."<br>";


	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
