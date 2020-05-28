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
		'ip_address' => '*',
		'domain' => 'test2.int',
		'type' => 'vhost',
		'parent_domain_id' => 0,
		'vhost_type' => 'name',
		'hd_quota' => 100000,
		'traffic_quota' => -1,
		'cgi' => 'y',
		'ssi' => 'y',
		'suexec' => 'y',
		'errordocs' => 1,
		'is_subdomainwww' => 1,
		'subdomain' => '',
		'php' => 'y',
		'ruby' => 'n',
		'redirect_type' => '',
		'redirect_path' => '',
		'ssl' => 'n',
		'ssl_state' => '',
		'ssl_locality' => '',
		'ssl_organisation' => '',
		'ssl_organisation_unit' => '',
		'ssl_country' => '',
		'ssl_domain' => '',
		'ssl_request' => '',
		'ssl_key' => '',
		'ssl_cert' => '',
		'ssl_bundle' => '',
		'ssl_action' => '',
		'stats_password' => '',
		'stats_type' => 'webalizer',
		'allow_override' => 'All',
		'apache_directives' => '',
		'php_open_basedir' => '/',
		'pm_max_requests' => 0,
		'pm_process_idle_timeout' => 10,
		'custom_php_ini' => '',
		'backup_interval' => '',
		'backup_copies' => 1,
		'backup_format_web' => 'default',
		'backup_format_db' => 'gzip',
		'active' => 'y',
		'traffic_quota_lock' => 'n',
		'http_port' => '80',
		'https_port' => '443'
	);

	$affected_rows = $client->sites_web_domain_add($session_id, $client_id, $params, $readonly = false);

	echo "Web Domain ID: ".$affected_rows."<br>";


	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
