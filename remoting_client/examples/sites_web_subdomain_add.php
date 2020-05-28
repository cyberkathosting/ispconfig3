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
		'ip_address' => '',
		'domain' => 'tsssssubt.int',
		'type' => 'subdomain',
		'parent_domain_id' => 1,
		'vhost_type' => '',
		'document_root' => '/web/dom',
		'system_user' => 'benutzer',
		'system_group' => 'gruppe',
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
		'ssl_cert' => '',
		'ssl_bundle' => '',
		'ssl_action' => '',
		'stats_password' => '',
		'stats_type' => 'webalizer',
		'allow_override' => 'All',
		'apache_directives' => '',
		'php_open_basedir' => '/php',
		'custom_php_ini' => '',
		'backup_interval' => '',
		'backup_copies' => 1,
		'backup_format_web' => 'default',
		'backup_format_db' => 'gzip',
		'active' => 'y',
		'traffic_quota_lock' => 'n'
	);

	$subdomain_id = $client->sites_web_subdomain_add($session_id, $client_id, $params);

	echo "Subdomain ID: ".$subdomain_id."<br>";

	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
