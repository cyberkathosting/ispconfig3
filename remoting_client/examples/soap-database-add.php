<?php

$username = 'admin';
$password = 'admin';

$soap_location = 'http://localhost:8080/remote/index.php';
$soap_uri = 'http://localhost:8080/remote/';

$client = new SoapClient(null, array('location' => $soap_location,
                                     'uri'      => $soap_uri));
try {
    if($session_id = $client->login($username,$password)) {
                    echo "Logged:".$session_id."<br />\n";
}

$database_type = 'mysql'; //Only mysql type avaliable more types coming soon.
$database_name = 'yourdbname';
$database_username = 'yourusername';
$database_password = 'yourpassword';
$database_charset = ''; // blank = db default, latin1 or utf8
$database_remoteips = ''; //remote ip´s separated by commas

$params = array(
          'server_id' => 1,
                'type' => $database_type,
                'database_name' => $database_name,
                'database_user' => $database_username,
                'database_password' => $database_password,
                'database_charset' =>  $database_charset,
                'remote_access' => 'n', // n disabled - y enabled
                'active' => 'y', // n disabled - y enabled
                'remote_ips' => $database_remoteips
                );

$client_id = 1;
$database_id = $client->sites_database_add($session_id, $client_id, $params);

if($client->logout($session_id)) {
    echo "Logout.<br />\n";
}


} catch (SoapFault $e) {
        die('Error: '.$e->getMessage());
}

?>

