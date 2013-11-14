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
		'policy_name' => 'numberone',
		'virus_lover' => 'Y',
		'spam_lover' => 'Y',
		'banned_files_lover' => 'Y',
		'bad_header_lover' => 'Y',
		'bypass_virus_checks' => 'Y',
		'bypass_spam_checks' => 'Y',
		'bypass_banned_checks' => 'Y',
		'bypass_header_checks' => 'Y',
		'spam_modifies_subj' => 'Y',
		'virus_quarantine_to' => '',
		'spam_quarantine_to' => '',
		'banned_quarantine_to' => '',
		'bad_header_quarantine_to' => '',
		'clean_quarantine_to' => '',
		'other_quarantine_to' => '',
		'spam_tag_level' => 1,
		'spam_tag2_level' => 1,
		'spam_kill_level' => 1,
		'spam_dsn_cutoff_level' => 1,
		'spam_quarantine_cutoff_level' => 1,
		'addr_extension_virus' => '',
		'addr_extension_spam' => '',
		'addr_extension_banned' => '',
		'addr_extension_bad_header' => '',
		'warnvirusrecip' => 'Y',
		'warnbannedrecip' => 'Y',
		'warnbadhrecip' => 'Y',
		'newvirus_admin' => '',
		'virus_admin' => '',
		'banned_admin' => '',
		'bad_header_admin' => '',
		'spam_admin' => '',
		'spam_subject_tag' => '',
		'spam_subject_tag2' => '',
		'message_size_limit' => 10,
		'banned_rulenames' => 'welt'
	);

	$affected_rows = $client->mail_policy_add($session_id, $client_id, $params);

	echo "Policy ID: ".$affected_rows."<br>";


	if($client->logout($session_id)) {
		echo 'Logged out.<br />';
	}


} catch (SoapFault $e) {
	echo $client->__getLastResponse();
	die('SOAP Error: '.$e->getMessage());
}

?>
