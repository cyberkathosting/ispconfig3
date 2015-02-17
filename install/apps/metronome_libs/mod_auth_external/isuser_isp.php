<?php
ini_set('display_errors', false);
$username = 'prosody';
$password = '23fm%4ks0';
/*
$soap_location = 'http://localhost:8080/ispconfig3/interface/web/remote/index.php';
$soap_uri = 'http://localhost:8080/ispconfig3/interface/web/remote/';
*/
$soap_location = 'https://tepin.spicyweb.de:8080/remote/index.php';
$soap_uri = 'https://tepin.spicyweb.de:8080/remote/';


$arg_email = '';

if(count($argv) == 3){
    $arg_email = $argv[1].'@'.$argv[2];
}

$client = new SoapClient(null, array('location' => $soap_location, 'uri' => $soap_uri));
try {
    //* Login to the remote server
    if($session_id = $client->login($username,$password)) {
        //var_dump($client->mail_alias_get($session_id, array('source' => 'blablubb@divepage.net', 'type' => 'alias', 'active' => 'y')));
        // Is Mail Alias?
        $alias = $client->mail_alias_get($session_id, array('source' => $arg_email, 'type' => 'alias', 'active' => 'y'));
        if(count($alias))
            $arg_email = $alias[0]['destination'];
        $mailbox = $client->mail_user_get($session_id, array('email' => $arg_email));
        if(count($mailbox)){
            echo 1;
            //$password = $mailbox[0]['password'];
            //echo intval(crypt($arg_password, $password) == $password);
        }
        else
            echo 0;
        //* Logout
        $client->logout($session_id);
    }
    else
        echo 0;
} catch (SoapFault $e) {
    echo 0;
}
?>