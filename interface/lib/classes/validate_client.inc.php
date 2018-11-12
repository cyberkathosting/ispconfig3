<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class validate_client {

	/*
		Validator function to check if a username is unique.
	*/
	function username_unique($field_name, $field_value, $validator) {
		global $app;

		if(isset($app->remoting_lib->primary_id)) {
			$client_id = $app->remoting_lib->primary_id;
		} else {
			$client_id = $app->tform->primary_id;
		}

		if($client_id == 0) {
			$num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM sys_user WHERE username = ?", $field_value);
			if($num_rec["number"] > 0) {
				$errmsg = $validator['errmsg'];
				if(isset($app->tform->wordbook[$errmsg])) {
					return $app->tform->wordbook[$errmsg]."<br>\r\n";
				} else {
					return $errmsg."<br>\r\n";
				}
			}
		} else {
			$num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM sys_user WHERE username = ? AND client_id != ?", $field_value, $client_id);
			if($num_rec["number"] > 0) {
				$errmsg = $validator['errmsg'];
				if(isset($app->tform->wordbook[$errmsg])) {
					return $app->tform->wordbook[$errmsg]."<br>\r\n";
				} else {
					return $errmsg."<br>\r\n";
				}
			}
		}
	}

	function username_collision($field_name, $field_value, $validator) {
		global $app;

		if(isset($app->remoting_lib->primary_id)) {
			$client_id = $app->remoting_lib->primary_id;
		} else {
			$client_id = $app->tform->primary_id;
		}

		$app->uses('getconf');
		$global_config = $app->getconf->get_global_config('sites');

		if((trim($field_value) == 'web' || preg_match('/^web[0-9]/', $field_value)) &&
			($global_config['ftpuser_prefix'] == '[CLIENTNAME]' ||
				$global_config['ftpuser_prefix'] == '' ||
				$global_config['shelluser_prefix'] == '[CLIENTNAME]' ||
				$global_config['shelluser_prefix'] == '' ) &&
			$global_config['client_username_web_check_disabled'] == 'n') {
			$errmsg = $validator['errmsg'];
			if(isset($app->tform->wordbook[$errmsg])) {
				return $app->tform->wordbook[$errmsg]."<br>\r\n";
			} else {
				return $errmsg."<br>\r\n";
			}
		}




	}

	function check_used_servers($field_name, $field_value, $validator)
	{
		global $app;

		if (is_array($field_value))
		{
			$client_id = intval($_POST['id']);
			if($client_id > 0) {
				$used_servers = null;

				switch ($field_name)
				{
				case 'web_servers':
					$used_servers = $app->db->queryAllRecords('SELECT domain_id FROM web_domain INNER JOIN sys_user ON web_domain.sys_userid = sys_user.userid WHERE client_id = ? AND server_id NOT IN ?', $client_id, $field_value);
					break;

				case 'dns_servers':
					$used_servers = $app->db->queryAllRecords('SELECT id FROM dns_rr INNER JOIN sys_user ON dns_rr.sys_userid = sys_user.userid WHERE client_id = ? AND server_id NOT IN ?', $client_id, $field_value);
					break;

				case 'db_servers':
					$used_servers = $app->db->queryAllRecords('SELECT database_id FROM web_database INNER JOIN sys_user ON web_database.sys_userid = sys_user.userid WHERE client_id = ? AND server_id NOT IN ?', $client_id, $field_value);
					break;

				case 'mail_servers':
					$used_servers = $app->db->queryAllRecords('SELECT domain_id FROM mail_domain INNER JOIN sys_user ON mail_domain.sys_userid = sys_user.userid WHERE client_id = ? AND server_id NOT IN ?', $client_id, $field_value);
					break;

				case 'xmpp_servers':
					$used_servers = $app->db->queryAllRecords('SELECT domain_id FROM xmpp_domain INNER JOIN sys_user ON xmpp_domain.sys_userid = sys_user.userid WHERE client_id = ? AND server_id NOT IN ?', $client_id, $field_value);
					break;
				}

				if ($used_servers === null || count($used_servers))
				{
					$errmsg = $validator['errmsg'];
					if(isset($app->tform->wordbook[$errmsg])) {
						return $app->tform->wordbook[$errmsg]."<br>\r\n";
					} else {
						return $errmsg."<br>\r\n";
					}
				}
			}
		}
	}

	function check_vat_id ($field_name, $field_value, $validator){
		global $app, $page;
		
		$vatid = trim($field_value);
		if(isset($app->remoting_lib->primary_id)) {
			$country = $app->remoting_lib->dataRecord['country'];
		} else {
			$country = $page->dataRecord['country'];
		}
		
		// check if country is member of EU
		$country_details = $app->db->queryOneRecord("SELECT * FROM country WHERE iso = ?", $country);
		if($country_details['eu'] == 'y' && $vatid != ''){
		
			$vatid = preg_replace('/\s+/', '', $vatid);
			$vatid = str_replace(array('.', '-', ','), '', $vatid);
			$cc = substr($vatid, 0, 2);
			$vn = substr($vatid, 2);

			// Test if the country of the VAT-ID matches the country of the customer
			if($country != ''){
				// Greece
				if($country == 'GR') $country = 'EL';
				if(strtoupper($cc) != $country){
					$errmsg = $validator['errmsg'];
					if(isset($app->tform->wordbook[$errmsg])) {
						return $app->tform->wordbook[$errmsg]."<br>\r\n";
					} else {
						return $errmsg."<br>\r\n";
					}
				}
			}
			try {
				$client = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
				$params = array('countryCode' => $cc, 'vatNumber' => $vn);
				try{
					$r = $client->checkVat($params);
					if($r->valid == true){
					} else {
						$errmsg = $validator['errmsg'];
							if(isset($app->tform->wordbook[$errmsg])) {
								return $app->tform->wordbook[$errmsg]."<br>\r\n";
							} else {
								return $errmsg."<br>\r\n";
							}
					}

				// This foreach shows every single line of the returned information
				/*
				foreach($r as $k=>$prop){
					echo $k . ': ' . $prop;
				}
				*/

				} catch(SoapFault $e) {
					//echo 'Error, see message: '.$e->faultstring;
					switch ($e->faultstring) {
						case 'INVALID_INPUT':
							$errmsg = $validator['errmsg'];
							if(isset($app->tform->wordbook[$errmsg])) {
								return $app->tform->wordbook[$errmsg]."<br>\r\n";
							} else {
								return $errmsg."<br>\r\n";
							}
							break;
						// the following cases shouldn't be the user's fault, so we return no error
						case 'SERVICE_UNAVAILABLE':
						case 'MS_UNAVAILABLE':
						case 'TIMEOUT':
						case 'SERVER_BUSY':
							break;
					}
				}
			} catch(SoapFault $e){
				// Connection to host not possible, europe.eu down?
				// this shouldn't be the user's fault, so we return no error
			}
		}
	}


}
