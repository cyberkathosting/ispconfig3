<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

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
						'domain' => 'domain.tld',
						'active' => 'y');
	$client_id = 0;
	$domain_id = $client->mail_domain_add($session_id, $client_id, $params);
	*/
	
	/*
	//* Update email domain
	$params = array(	'server_id' => 1,
						'domain' => 'domain.tld',
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