<?php

require 'soap_config.php';

// Disable SSL verification for this test script
$context = stream_context_create([
    'ssl' => [
        // set some SSL/TLS specific options
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

$client = new SoapClient(null, array('location' => $soap_location,
                'uri'      => $soap_uri,
                'trace' => 1,
                'exceptions' => 1,
                'stream_context' => $context));


try {
        if($session_id = $client->login($username, $password)) {
                echo 'Logged successfull. Session ID:'.$session_id.'<br />';
        }

        //* Set the function parameters.
        $client_id = 1;
        $template_id = 1;
        $domain = 'test.tld';
        $ip = '192.168.0.100';
        $ns1 = 'ns1.testhoster.tld';
        $ns2 = 'ns2.testhoster.tld';
        $email = 'email.test.tld';

        $id = $client->dns_templatezone_add($session_id, $client_id, $template_id, $domain, $ip, $ns1, $ns2, $email);

        echo "ID: ".$id."<br>";

        if($client->logout($session_id)) {
                echo 'Logged out.<br />';
        }


} catch (SoapFault $e) {
        echo $client->__getLastResponse();
        die('SOAP Error: '.$e->getMessage());
}

?>