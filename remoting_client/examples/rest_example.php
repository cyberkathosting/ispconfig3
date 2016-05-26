<?php


$remote_user = 'test';
$remote_pass = 'apipassword';
$remote_url = 'https://yourserver.com:8080/remote/json.php';

function restCall($method, $data) {
	global $remote_url;
	
	if(!is_array($data)) return false;
	$json = json_encode($data);
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_POST, 1);

	if($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

	// needed for self-signed cert
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	// end of needed for self-signed cert
	
	curl_setopt($curl, CURLOPT_URL, $remote_url . '?' . $method);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
	$result = curl_exec($curl);
	curl_close($curl);

	return $result;
}

$result = restCall('login', array('username' => $remote_user, 'password' => $remote_pass, 'client_login' => false));
if($result) {
	$data = json_decode($result, true);
	if(!$data) die("ERROR!\n");
	
	$session_id = $data['response'];
	
	$result = restCall('client_get', array('session_id' => $session_id, 'client_id' => array('username' => 'abcde')));
	if($result) var_dump(json_decode($result, true));
	else print "Could not get client_get result\n";
	
	// or by id
	$result = restCall('client_get', array('session_id' => $session_id, 'client_id' => 2));
	if($result) var_dump(json_decode($result, true));
	else print "Could not get client_get result\n";
	
	// or all
	$result = restCall('client_get', array('session_id' => $session_id, 'client_id' => array()));
	if($result) var_dump(json_decode($result, true));
	else print "Could not get client_get result\n";

	$result = restCall('logout', array('session_id' => $session_id));
	if($result) var_dump(json_decode($result, true));
	else print "Could not get logout result\n";
}
