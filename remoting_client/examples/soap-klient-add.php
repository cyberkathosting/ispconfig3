<?php

$username = 'axelf';
$password = 'axelf';

$soap_location = 'http://localhost:8080/remote/index.php';
$soap_uri = 'http://localhost:8080/remote/';


$client = new SoapClient(null, array('location' => $soap_location,
                                     'uri'      => $soap_uri));


try {
        if($session_id = $client->login($username,$password)) {
                echo 'Zalogowany. Sesja:'.$session_id.'<br />';
        }

        $params = array(        'server_id' => 1,
                      
        					'company_name' => 'abm',
        					'contact_name' => 'artur',
        					'username' =>'artek123',
        					'password' =>'artek123',
        					'language' =>'pl',
        					'usertheme' =>'default',
        					'street' =>'dobrzanskiego',
        					'zip' =>'05-071',
        					'city' =>'sulejowek',
        					'state' =>'non-US',
        					'country' =>'PL',
        					'telephone' =>'',
        					'mobile' =>'',
       						'fax' =>'',
       						'email' =>'',
        					'internet' =>'',
        					'icq' =>'',
 		      				'notes' =>'',  
        					'template_master' => '1',
                                                'template_additional' =>'',
                                                'default_mailserver' =>'1',
                                                'limit_maildomain' =>'1',
                                                'limit_mailbox' =>'-1',
						'limit_mailalias' =>'-1',
                                                'limit_mailforward' =>'-1',
                                                'limit_mailcatchall' =>'-1',
        					'limit_mailrouting' => '-1',
        					'limit_mailfilter' =>'-1',
                   		                'limit_fetchmail' =>'-1',
                                                'limit_mailquota' =>'-1',
                                                'limit_spamfilter_wblist' =>'-1',
                                                'limit_spamfilter_user' =>'-1',
        					'limit_spamfilter_policy' =>'-1',
        					'default_webserver' =>'1',
        					'limit_web_domain' =>'-1',
       						'web_php_options' =>"SuPHP",
        					'limit_web_aliasdomain' =>'-1',
        					'limit_web_subdomain' =>'-1',
        					'limit_ftp_user' =>'-1',
        					'limit_shell_user' =>'-1',
        					'ssh_chroot' =>'None',
        					'default_dnsserver' =>'1',
        					'limit_dns_zone' =>'-1',
        					'limit_dns_record' =>'-1',
        					'limit_client' =>'0',
        					'default_dbserver' =>'1',
        					'limit_database' =>'-1',
        					'limit_cron' =>'0',
        					'limit_cron_type' =>'',
       						'limit_cron_frequency' =>'-1');


        $client_id = 0;
        $domain_id = $client->klient_add($session_id,$domain_id, $params);



        if($client->logout($session_id)) {
                echo 'Wylogowany.<br />';
        }

	}
catch (SoapFault $e) {
        die('SOAP Blad: '.$e->getMessage());
	}

?>
        
