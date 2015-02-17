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

$auth_keys = array(
    'iplay-esports.de' => 'f47kmm5Yh5hJzSws2KTS',
    'weirdempire.de' => 'scNDcU37gQ7MCMeBgaJX'
);

$arg_email = '';
$arg_password = '';

if(count($argv) == 4){
    $arg_email = $argv[1].'@'.$argv[2];
    $arg_password = $argv[3];
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
            $password = $mailbox[0]['password'];
            echo checkAuth($argv[1], $argv[2], $arg_password, $password);//intval(crypt($arg_password, $password) == $password);
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

function checkAuth($user, $domain, $pw, $pw_mailbox){
    global $auth_keys;
    if(crypt($pw, $pw_mailbox) == $pw_mailbox)
        return intval(1);

    if(array_key_exists($domain, $auth_keys)){
        $datetime = new DateTime();
        $datetime->setTimezone(new DateTimeZone("UTC"));
        for($t = $datetime->getTimestamp(); $t >= $datetime->getTimestamp()-30; $t--){
            $pw_api = md5($domain.'@'.$auth_keys[$domain].'@'.$user.'@'.$t);
            if($pw_api == $pw)
                return intval(1);
        }
    }
    return intval(0);
}
?>