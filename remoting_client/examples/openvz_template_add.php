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
		'template_name' => 'tone',
		'diskspace' => 10,
		'traffic' => -1,
		'bandwidth' => -1,
		'ram' => 256,
		'ram_burst' => 512,
		'cpu_units' => 1000,
		'cpu_num' => 4,
		'cpu_limit' => 400,
		'io_priority' => 4,
		'active' => 'y',
		'description' => 'howto',
		'numproc' => '999999:999999',
		'numtcpsock' => '7999992:7999992',
		'numothersock' => '7999992:7999992',
		'vmguarpages' => '65536:unlimited',
		'kmemsize' => '2147483646:2147483646',
		'tcpsndbuf' => '214748160:396774400',
		'tcprcvbuf' => '214748160:396774400',
		'othersockbuf' => '214748160:396774400',
		'dgramrcvbuf' => '214748160:396774400',
		'oomguarpages' => '65536:65536',
		'privvmpages' => '131072:139264',
		'lockedpages' => '999999:999999',
		'shmpages' => '65536:65536',
		'physpages' => '0:2147483647',
		'numfile' => '23999976:23999976',
		'avnumproc' => '180:180',
		'numflock' => '999999:999999',
		'numpty' => '500000:500000',
		'numsiginfo' => '999999:999999',
		'dcachesize' => '2147483646:2147483646',
		'numiptent' => '999999:999999',
		'swappages' => '256000:256000',
		'hostname' => 'host',
		'nameserver' => 'ns1',
		'create_dns' => 'n',
		'capability' => ''
	);

	$template_id = $client->openvz_template_add($session_id, $client_id, $params);

	echo "Template ID: ".$template_id."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
