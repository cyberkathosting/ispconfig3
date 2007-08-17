<?php

/* This is a conceptual style code for feedback and work in progress
	Translates the lang of the tables also...
   Please hack
*/

class ISPConfigClient
{
	private $SID;

	public function __construct($soap_session_id){
		$this->SID = $soap_session_id;
	}

	//*  Get Reseller List
	public function reseller_get_list(){
		
		$params = array (	'sid'       => $session_id,
        		            'module'	=> 'reseller',
                		    'function'	=> 'reseller_list',
                 		   	'params'    => ''
                 		);
    }
    
    public function reseller($ID){
    	// Get Reseller
		$params = array (	'sid'        => $session_id,
							'module'     => 'reseller',
							'function'   => 'reseller_get',
							'params'     => array ( reseller_title => "Reseller1"));
			}
	}

	public function reseller_edit(

		// Adding a reseller
		$params = array ( 'sid'      => $session_id,
						'module'   => 'reseller',
						'function' => 'reseller_add',
						'params'   => array (         reseller_title => 'Reseller1',
										firma => 'Reseller4',
										vorname => 'Jens',
										limit_user => '50',
										limit_disk => '1000',
										limit_web => '10',
										limit_domain => '10',
										name => 'Jensen',
										strasse => 'Hauptstr. 1',
										plz => '12345',
										ort => 'Hauptstadt',
										telefon => '0511 5469766',
										fax => '0511 9799655',
										email => 'test@hostobserver.com',
										internet => 'http://www.reseller4.tld',
										reseller_user => 'reseller4',
										reseller_passwort => 'huhu',
										anrede => 'Herr',  // Herr, Frau, Firma
										land => 'Deutschland',
										limit_httpd_include => '1',
										limit_dns_manager => '1',
										limit_domain_dns => '50',
										province => 'Niedersachsen',
										limit_shell_access => '0',
										limit_cgi => '1',
										limit_php => '1',
										limit_ssi => '1',
										limit_ftp => '1',
										limit_mysql => '1',
										limit_ssl => '1',
										limit_anonftp => '1',
										limit_standard_cgis => '1',
										limit_wap => '1',
										limit_error_pages => '1',
										limit_frontpage => '0',
										limit_mysql_anzahl_dbs => '100',
										limit_slave_dns => '50',
										client_salutatory_email_sender_email => '',
										client_salutatory_email_sender_name => '',
										client_salutatory_email_bcc => '',
										client_salutatory_email_subject => '',
										client_salutatory_email_message => '',
										standard_index => '',
										user_standard_index => ''
										));
                                
}

?>