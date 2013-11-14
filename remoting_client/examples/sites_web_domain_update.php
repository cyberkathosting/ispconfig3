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
	$client_id = 0;
	$domain_id = 1;


	//* Get the web domain record
	$domain_record = $client->sites_web_domain_get($session_id, $domain_id);

	//* Change parameters
	$domain_record['active'] = 'n';
	$domain_record['document_root'] = '/web/doc';
	$domain_record['allow_override'] = 'All';
	$domain_record['php_open_basedir'] = '/php';

	$affected_rows = $client->sites_web_domain_update($session_id, $client_id, $domain_id, $domain_record);

	echo "Number of records that have been changed in the database: ".$affected_rows."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
