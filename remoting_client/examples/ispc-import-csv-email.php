#!/usr/bin/php
<?php
#
# ispc-import-csv-email.php: import email accounts from csv into ispconfig
#

# ISPConfig remote api params
$remote_user = 'importer';
$remote_pass = 'apipassword';
$remote_url = 'https://yourserver.com:8080/remote/json.php';

# CSV file
$csv_file="/home/migrations/test.csv";


# csv file format (first line is header names, column order does not matter):
#
# "email","password","quota","name","cc","bcc","move_junk","autoresponder","autoresponder_text","virus_lover","spam_lover"
# "api_standard@apitest.com","insecure","150","API User Insert: Standard Mailbox","","","yes","no","this is vacation text, although vacation is not enabled","N","N"
# "api_no_spambox@apitest.com","insecure","150","API User Insert: Mailbox with move_junk off","","","no","no","this is vacation text, although vacation is not enabled","N","N"
# "api_vacation@apitest.com","insecure","150","API User Insert: Mailbox with vacation","","","yes","yes","this is vacation text, with vacation enabled","N","N"
# "api_forward@apitest.com","insecure","150","API User Insert: Mail Forward","your-test-addr@test.com","","no","no","this is vacation text, although vacation is not enabled","N","N"
# "api_both1@apitest.com","insecure","150","API User Insert: Mailbox with forward via cc","your-test-addr@test.com","","yes","no","this is vacation text, although vacation is not enabled","N","N"
# "api_both2@apitest.com","insecure","150","API User Insert: Mailbox with forward via bcc","","your-test-addr@test.com","yes","no","this is vacation text, although vacation is not enabled","N","N"
# "api_virus_lover@apitest.com","insecure","150","API User Insert: Mailbox with virus_lover","","","yes","no","","Y","N"
# "api_spam_lover@apitest.com","insecure","150","API User Insert: Mailbox with spam_lover","","","yes","no","","N","Y"
# "api_both_lover@apitest.com","insecure","150","API User Insert: Mailbox with virus_lover and spam_lover","","","yes","no","","Y","Y"


/**
 * Call REST endpoint.
 */
function restCall( $method, $data ) {
	global $remote_url;
	
	if(!is_array($data)) return false;
	$json = json_encode($data);
	
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_POST, 1);

	if($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $json);

	// needed for self-signed cert
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	// end of needed for self-signed cert
	
	curl_setopt($curl, CURLOPT_URL, $remote_url . '?' . $method);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	
	$result = curl_exec($curl);
	curl_close($curl);

	return $result;
}

$session_id = '';

/**
 * Logout of active session and die with message.
 */
function session_die( $msg ) {
	global $session_id;

	if ( isset( $session_id ) && $session_id ) {
		$result = restCall( 'logout', [ 'session_id' => $session_id ] );
		$result || die( "$msg\nAdditionally, could not get logout result, session id $session_id may now be abandoned.\n" );
	}

	die( "$msg\n" );
}

/**
 * Make api call, checking for errors and return 'response' from the decoded data.  Opens session if required.
 */
function apiCall( ...$args ) {
	global $remote_user, $remote_pass, $session_id;

	// login to remote api and obtain session id if needed
	if ( ! ( isset( $session_id ) && $session_id ) ) {
		$result = restCall( 'login', [ 'username' => $remote_user, 'password' => $remote_pass, 'client_login' => false, ] );

		if ( $result ) {
			$result = json_decode( $result, true );
			if ( ! $result ) {
				die( "Error: unable to login to remote api (json_decode failed)\n" );
			}

			if ( isset( $result['response'] ) ) {
				$session_id = $result['response'];
			} else {
				die( "Error: failed to obtain session id from remote api login\n" );
			}
		}
	}

	$rest_args = func_get_args();
	$method = array_shift( $rest_args );

	$result = restCall( $method, array_merge( [ 'session_id' => $session_id, ], ...$rest_args ) );

	if ( $result ) $data = json_decode( $result, true );
	else session_die( "Could not get $method result" );

	if ( isset( $data['code'] ) && 'ok' != $data['code'] ) {
		$msg = "$method returned " . $data['code']
		     . ( isset( $data['message'] ) ? ": " . $data['message'] . "\n" : "\n" );
		session_die( $msg );
	}

	return ( isset( $data['response'] ) ? $data['response'] : $data );
}

if ( ! file_exists( "$csv_file" ) ) {
	die( "CSV file ($csv_file) not found.\n" );
}

// get all mail policies
$mail_policies = apiCall( 'mail_policy_get', [ 'primary_id' => [] ] );
if ( ! $mail_policies ) {
	session_die( "Error: could not look up mail policies\n" );
}

// get all spamfilter_user settings
$mail_spamfilter_users = apiCall( 'mail_spamfilter_user_get', [ 'primary_id' => [] ] );
if ( ! $mail_spamfilter_users ) {
	session_die( "Error: could not look up mail spamfilter users\n" );
}

$mail_domains = [];

// Read csv file, map rows and loop through them
$rows   = array_map( 'str_getcsv', file( $csv_file ) );
$header = array_shift( $rows );
$email_idx = array_search( 'email', $header );
if ( $email_idx === FALSE ) {
	session_die( "Error in csv file: 'email' field not found.\n" );
}
$csv    = [];
foreach( $rows as $row ) {
	$email = $row[$email_idx];
	$domain = substr( $email, strpos( $email, '@' ) + 1 );

	if ( is_array( $row ) && count( $header ) == count( $row ) ) {
		$csv[$email] = array_combine( $header, $row );
	} else {
		print "Error in csv file: problem parsing email '$email'\n";
		continue;
	}

	// look up mail_domain record for this domain
	if ( ! isset( $mail_domains[$domain] ) ) {
		$data = apiCall( 'mail_domain_get_by_domain', [ 'domain' => $domain ] );

		if ( is_array( $data ) && isset( $data[0] ) ) {

			// unset these (large and don't need them)
			unset( $data[0]['dkim'] );
			unset( $data[0]['dkim_selector'] );
			unset( $data[0]['dkim_public'] );
			unset( $data[0]['dkim_private'] );

			$mail_domains[$domain] = $data[0];

			foreach ( $mail_spamfilter_users as $msu ) {
				if ( $msu['email'] == "@$domain" && $msu['server_id'] == $mail_domains[$domain]['server_id'] ) {
					$mail_domains[$domain]['spamfilter_policy_id'] = $msu['policy_id'];
				}
			}
		} else {
			$mail_domains[$domain] = [ 'domain_id' => -1, 'domain' => $domain, ];
			print( "Error: mail_domain $domain does not exist, you must create it first.\n" );
		}
	}
}

// dump manually created account to compare values
//$data = apiCall( 'mail_user_get', [ 'primary_id' => [ 'email' => 'manual@apitest.com' ] ] );
//var_dump( $data, true );

foreach ( $csv as $record ) {
	$email = $record['email'];
	$addr = substr( $email, 0, strpos( $email, '@' ) );
	$domain = substr( $email, strpos( $email, '@' ) + 1 );

	// ensure we have mail_domain info
	if ( ! isset( $mail_domains[$domain] ) || -1 == $mail_domains[$domain]['domain_id'] ) {
		print "Config for domain $domain not available, cannot add email $email.\n";
		continue;
	}

	// skip if email already exists
	$data = apiCall( 'mail_user_get', [ 'primary_id' => [ 'email' => $email ] ] );
	if ( is_array( $data ) && isset( $data[0] ) && isset( $data[0]['mailuser_id'] ) ) {
		print "Email $email already exists, skipping.\n";
		continue;
	}

	// get client_id for this sys_userid
	if ( isset( $mail_domains[$domain]['client_id'] ) ) {
		$client_id = $mail_domains[$domain]['client_id'];
	} else {
		$client_id = apiCall( 'client_get_id', [ 'sys_userid' => $mail_domains[$domain]['sys_userid'] ] );
		if ( ! $client_id ) {
			print "Error: unable to determine client_id for $domain (sys_userid is " . $mail_domains[$domain]['sys_userid'] . "),\n";
			print "cannot create mailbox for Email $email\n";
			continue;
		}
		$mail_domains[$domain]['client_id'] = $client_id;
	}

	// mail_user_add parameters for this email
	$params = [ 'params' => [
			'server_id' => $mail_domains[$domain]['server_id'],
			'email' => $email,
			'login' => $email,
			'password' => $record['password'],
			'name' => $record['name'],
			'uid' => 5000,
			'gid' => 5000,
			'maildir' => "/var/vmail/$domain/$addr",
			'quota' => $record['quota'] * 1024 * 1024,
			'cc' => implode( ',', array_filter( [ $record['cc'], $record['bcc'] ] ) ),
			'homedir' => "/var/vmail/",
			'autoresponder' => ( preg_match( '/^y/i', $record['autoresponder'] ) ? 'y' : 'n' ),
			'autoresponder_start_date' => date( 'Y-m-d H:i:s' ),
			'autoresponder_end_date' => date( '2024-m-d H:i:s' ),
			'autoresponder_text' => $record['autoresponder_text'],
			'move_junk' => ( preg_match( '/^y/i', $record['move_junk'] ) ? 'y' : 'n' ),
			'custom_mailfilter' => "",
			'postfix' => 'y',
			'access' => 'y',
		//	'disableimap' => 'n',
		//	'disablepop3' => 'n',
		//	'disabledeliver' => 'n',
		//	'disablesmtp' => 'n',
			],
		];

	// add mail user
	$data = apiCall( 'mail_user_add', [ 'client_id' => $client_id ], $params );

	if ( ! $data ) {
		print "mail_user_add may have a problem inserting $email\n";
		continue;
	}

	//$data = apiCall( 'mail_user_get', [ 'primary_id' => [ 'email' => $email ] ] );
	//var_dump( $data, true );

	// determine mail policy
	$spam_lover = ( preg_match( '/^y/i', $record['move_junk'] ) ? $record['spam_lover'] : 'N' );
	$virus_lover = $record['virus_lover'];
	$spamfilter_policy_id = null;

	// check domain's policy settings for bypass_spam_checks == 'N' and matching spam_lover/virus_lover,
	// if a match, we're done
	if ( isset( $mail_domains[$domain]['spamfilter_policy_id'] ) ) {
		foreach ( $mail_policies as $policy ) {
			if ( $policy['id'] == $mail_domains[$domain]['spamfilter_policy_id'] ) {
				if ( 'N' == $policy['bypass_spam_checks'] && $policy['spam_lover'] == $spam_lover && $policy['virus_lover'] == $virus_lover ) {
					$spamfilter_policy_id = $policy['id'];
				}
			}
		}
	}
	// if domain's policy doesn't match, loop through all policies to find a match and insert it
	if ( null === $spamfilter_policy_id ) {
		foreach ( $mail_policies as $policy ) {
			if ( 'Y' == $policy['bypass_spam_checks'] ) {
				continue;
			}
			if ( $policy['spam_lover'] == $spam_lover && $policy['virus_lover'] == $virus_lover ) {
				$spamfilter_policy_id = $policy['id'];

				// mail_spamfilter_user entry for this user / policy_id
				$params = [ 'params' => [
						'server_id' => $mail_domains[$domain]['server_id'],
						'priority' => "10",
						'policy_id' => $policy['id'],
						'email' => $email,
						'fullname' => $email,
						'local' => "Y",
						],
					];

				$data = apiCall( 'mail_spamfilter_user_add', [ 'client_id' => $client_id ], $params );

				// either we inserted a spamfilter_user or it failed,
				// either way, on to the next email
				continue 2;
			}
		}
	}
}


// logout so session id is cleaned up
if ( isset( $session_id ) && $session_id ) {
	$result = restCall( 'logout', [ 'session_id' => $session_id ] );
	$result || die( "Could not get logout result, session id $session_id may now be abandoned.\n" );
}

exit();
