<?php

//* Examples for the remoting framework

//* Login credentials
$username = 'test';
$password = 'test';

//* The URI to the remoting interface. Please replace with the URI to your real server
$soap_location = 'http://'.$_SERVER["HTTP_HOST"].'/ispconfig3/interface/web/remote/index.php';
$soap_uri = 'http://'.$_SERVER["HTTP_HOST"].'/ispconfig3/interface/web/remote/';


// Create the SOAP Client
$client = new SoapClient(null, array('location' => $soap_location,
                                     'uri'      => $soap_uri));


try {
	//* Login to the remote server
	if($session_id = $client->login($username,$password)) {
		echo 'Logged into remote server sucessfully. The SessionID is '.$session_id.'<br />';
	}
	
	//* ----------------------------------------------------------
	//* Example functions
	//* ----------------------------------------------------------
	
	/*
	//* Add a email domain
	$params = array(	'server_id' => 1,
						'domain' => 'test.com',
						'active' => 'y');
	$client_id = 0;
	$domain_id = $client->mail_domain_add($session_id, $client_id, $params);
	*/
	
	/*
	//* Update email domain
	$params = array(	'server_id' => 1,
						'domain' => 'test.org',
						'active' => 'y');
	//* ID of the client. 0 = the admin owns this record.
	$client_id = 0;
	
	// ID of the domain whch shall be updated.
	$domain_id = 1;
	
	// Call the domain update function
	$domain_id = $client->mail_domain_update($session_id, $client_id, $domain_id, $params);
	*/
	
	
	//* Logout
	if($client->logout($session_id)) {
		echo 'Logged out sucessfully.<br />';
	}
	
	
} catch (SoapFault $e) {
	die('SOAP Error: '.$e->getMessage());
}













?>