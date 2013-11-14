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
	$reseller_id = 0; // this id has to be 0 if the client shall not be assigned to admin or if the client is a reseller
	$params = array(
		'company_name' => 'awesomecompany',
		'contact_name' => 'name',
		'customer_no' => '1',
		'vat_id' => '1',
		'street' => 'fleetstreet',
		'zip' => '21337',
		'city' => 'london',
		'state' => 'bavaria',
		'country' => 'GB',
		'telephone' => '123456789',
		'mobile' => '987654321',
		'fax' => '546718293',
		'email' => 'e@mail.int',
		'internet' => '',
		'icq' => '111111111',
		'notes' => 'awesome',
		'default_mailserver' => 1,
		'limit_maildomain' => -1,
		'limit_mailbox' => -1,
		'limit_mailalias' => -1,
		'limit_mailaliasdomain' => -1,
		'limit_mailforward' => -1,
		'limit_mailcatchall' => -1,
		'limit_mailrouting' => 0,
		'limit_mailfilter' => -1,
		'limit_fetchmail' => -1,
		'limit_mailquota' => -1,
		'limit_spamfilter_wblist' => 0,
		'limit_spamfilter_user' => 0,
		'limit_spamfilter_policy' => 1,
		'default_webserver' => 1,
		'limit_web_ip' => '',
		'limit_web_domain' => -1,
		'limit_web_quota' => -1,
		'web_php_options' => 'no,fast-cgi,cgi,mod,suphp',
		'limit_web_subdomain' => -1,
		'limit_web_aliasdomain' => -1,
		'limit_ftp_user' => -1,
		'limit_shell_user' => 0,
		'ssh_chroot' => 'no,jailkit,ssh-chroot',
		'limit_webdav_user' => 0,
		'default_dnsserver' => 1,
		'limit_dns_zone' => -1,
		'limit_dns_slave_zone' => -1,
		'limit_dns_record' => -1,
		'default_dbserver' => 1,
		'limit_database' => -1,
		'limit_cron' => 0,
		'limit_cron_type' => 'url',
		'limit_cron_frequency' => 5,
		'limit_traffic_quota' => -1,
		'limit_client' => 0, // If this value is > 0, then the client is a reseller
		'parent_client_id' => 0,
		'username' => 'guy3',
		'password' => 'brush',
		'language' => 'en',
		'usertheme' => 'default',
		'template_master' => 0,
		'template_additional' => '',
		'created_at' => 0
	);

	$affected_rows = $client->client_add($session_id, $reseller_id, $params);

	echo "Client: ".$affected_rows."<br>";


	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
