<?php

/* This is a conceptual stlye code for feedback and work in progress
   Please hack
*/

class ISPConfigClient
{

	public function __construct($db_connection = null){
	
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

}

?>