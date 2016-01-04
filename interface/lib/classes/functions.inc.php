<?php

/*
Copyright (c) 2010, Till Brehm, projektfarm Gmbh
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

//* The purpose of this library is to provide some general functions.
//* This class is loaded automatically by the ispconfig framework.

class functions {
	var $idn_converter = null;
	var $idn_converter_name = '';

	public function mail($to, $subject, $text, $from, $filepath = '', $filetype = 'application/pdf', $filename = '', $cc = '', $bcc = '', $from_name = '') {
		global $app, $conf;

		if($conf['demo_mode'] == true) $app->error("Mail sending disabled in demo mode.");

		$app->uses('getconf,ispcmail');
		$mail_config = $app->getconf->get_global_config('mail');
		if($mail_config['smtp_enabled'] == 'y') {
			$mail_config['use_smtp'] = true;
			$app->ispcmail->setOptions($mail_config);
		}
		$app->ispcmail->setSender($from, $from_name);
		$app->ispcmail->setSubject($subject);
		$app->ispcmail->setMailText($text);

		if($filepath != '') {
			if(!file_exists($filepath)) $app->error("Mail attachement does not exist ".$filepath);
			$app->ispcmail->readAttachFile($filepath);
		}

		if($cc != '') $app->ispcmail->setHeader('Cc', $cc);
		if($bcc != '') $app->ispcmail->setHeader('Bcc', $bcc);

		$app->ispcmail->send($to);
		$app->ispcmail->finish();

		return true;
	}

	public function array_merge($array1, $array2) {
		$out = $array1;
		foreach($array2 as $key => $val) {
			$out[$key] = $val;
		}
		return $out;
	}

	public function currency_format($number, $view = '') {
		global $app;
		if($view != '') $number_format_decimals = (int)$app->lng('number_format_decimals_'.$view);
		if(!$number_format_decimals) $number_format_decimals = (int)$app->lng('number_format_decimals');

		$number_format_dec_point = $app->lng('number_format_dec_point');
		$number_format_thousands_sep = $app->lng('number_format_thousands_sep');
		if($number_format_thousands_sep == 'number_format_thousands_sep') $number_format_thousands_sep = '';
		return number_format((double)$number, $number_format_decimals, $number_format_dec_point, $number_format_thousands_sep);
	}

	//* convert currency formatted number back to floating number
	public function currency_unformat($number) {
		global $app;

		$number_format_dec_point = $app->lng('number_format_dec_point');
		$number_format_thousands_sep = $app->lng('number_format_thousands_sep');
		if($number_format_thousands_sep == 'number_format_thousands_sep') $number_format_thousands_sep = '';

		if($number_format_thousands_sep != '') $number = str_replace($number_format_thousands_sep, '', $number);
		if($number_format_dec_point != '.' && $number_format_dec_point != '') $number = str_replace($number_format_dec_point, '.', $number);

		return (double)$number;
	}

	public function get_ispconfig_url() {
		global $app;

		$url = (stristr($_SERVER['SERVER_PROTOCOL'], 'HTTPS') || stristr($_SERVER['HTTPS'], 'on'))?'https':'http';
		if($_SERVER['SERVER_NAME'] != '_') {
			$url .= '://'.$_SERVER['SERVER_NAME'];
			if($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
				$url .= ':'.$_SERVER['SERVER_PORT'];
			}
		} else {
			$app->uses("getconf");
			$server_config = $app->getconf->get_server_config(1, 'server');
			$url .= '://'.$server_config['hostname'];
			if($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
				$url .= ':'.$_SERVER['SERVER_PORT'];
			}
		}
		return $url;
	}

	public function json_encode($data) {
		if(!function_exists('json_encode')){
			if(is_array($data) || is_object($data)){
				$islist = is_array($data) && (empty($data) || array_keys($data) === range(0, count($data)-1));

				if($islist){
					$json = '[' . implode(',', array_map(array($this, "json_encode"), $data) ) . ']';
				} else {
					$items = array();
					foreach( $data as $key => $value ) {
						$items[] = $this->json_encode("$key") . ':' . $this->json_encode($value);
					}
					$json = '{' . implode(',', $items) . '}';
				}
			} elseif(is_string($data)){
				// Escape non-printable or Non-ASCII characters.
				// I also put the \\ character first, as suggested in comments on the 'addclashes' page.
				$string = '"'.addcslashes($data, "\\\"\n\r\t/".chr(8).chr(12)).'"';
				$json = '';
				$len = strlen($string);
				// Convert UTF-8 to Hexadecimal Codepoints.
				for($i = 0; $i < $len; $i++){
					$char = $string[$i];
					$c1 = ord($char);

					// Single byte;
					if($c1 <128){
						$json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
						continue;
					}

					// Double byte
					$c2 = ord($string[++$i]);
					if(($c1 & 32) === 0){
						$json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
						continue;
					}

					// Triple
					$c3 = ord($string[++$i]);
					if(($c1 & 16) === 0){
						$json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128));
						continue;
					}

					// Quadruple
					$c4 = ord($string[++$i]);
					if(($c1 & 8) === 0){
						$u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1;

						$w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3);
						$w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128);
						$json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
					}
				}
			} else {
				// int, floats, bools, null
				$json = strtolower(var_export($data, true));
			}
			return $json;
		} else {
			return json_encode($data);
		}
	}

	public function suggest_ips($type = 'IPv4'){
		global $app;

		if($type == 'IPv4'){
//			$regex = "/^[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}(\.)[0-9]{1,3}$/";
			$regex = "/^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/";
		} else {
			// IPv6
			$regex = "/^(\:\:([a-f0-9]{1,4}\:){0,6}?[a-f0-9]{0,4}|[a-f0-9]{1,4}(\:[a-f0-9]{1,4}){0,6}?\:\:|[a-f0-9]{1,4}(\:[a-f0-9]{1,4}){1,6}?\:\:([a-f0-9]{1,4}\:){1,6}?[a-f0-9]{1,4})(\/\d{1,3})?$/i";
		}

		$server_by_id = array();
		$server_by_ip = array();
		$servers = $app->db->queryAllRecords("SELECT * FROM server");
		if(is_array($servers) && !empty($servers)){
			foreach($servers as $server){
				$server_by_id[$server['server_id']] = $server['server_name'];
			}
		}

		$ips = array();
		$results = $app->db->queryAllRecords("SELECT ip_address AS ip, server_id FROM server_ip WHERE ip_type = ?", $type);
		if(!empty($results) && is_array($results)){
			foreach($results as $result){
				if(preg_match($regex, $result['ip'])){
					$ips[] = $result['ip'];
					$server_by_ip[$result['ip']] = $server_by_id[$result['server_id']];
				}
			}
		}
		$results = $app->db->queryAllRecords("SELECT ip_address AS ip FROM openvz_ip");
		if(!empty($results) && is_array($results)){
			foreach($results as $result){
				if(preg_match($regex, $result['ip'])) $ips[] = $result['ip'];
			}
		}
		$results = $app->db->queryAllRecords("SELECT data AS ip FROM dns_rr WHERE type = 'A' OR type = 'AAAA'");
		if(!empty($results) && is_array($results)){
			foreach($results as $result){
				if(preg_match($regex, $result['ip'])) $ips[] = $result['ip'];
			}
		}
		$results = $app->db->queryAllRecords("SELECT ns AS ip FROM dns_slave");
		if(!empty($results) && is_array($results)){
			foreach($results as $result){
				if(preg_match($regex, $result['ip'])) $ips[] = $result['ip'];
			}
		}
		
		$results = $app->db->queryAllRecords("SELECT remote_ips FROM web_database WHERE remote_ips != ''");
		if(!empty($results) && is_array($results)){
			foreach($results as $result){
				$tmp_ips = explode(',', $result['remote_ips']);
				foreach($tmp_ips as $tmp_ip){
					$tmp_ip = trim($tmp_ip);
					if(preg_match($regex, $tmp_ip)) $ips[] = $tmp_ip;
				}
			}
		}
		$ips = array_unique($ips);
		sort($ips, SORT_NUMERIC);

		$result_array = array('cheader' => array(), 'cdata' => array());

		if(!empty($ips)){
			$result_array['cheader'] = array('title' => 'IPs',
				'total' => count($ips),
				'limit' => count($ips)
			);

			foreach($ips as $ip){
				$result_array['cdata'][] = array( 'title' => $ip,
					'description' => $type.($server_by_ip[$ip] != ''? ' &gt; '.$server_by_ip[$ip] : ''),
					'onclick' => '',
					'fill_text' => $ip
				);
			}
		}

		return $result_array;
	}

	public function intval($string, $force_numeric = false) {
		if(intval($string) == 2147483647 || ($string > 0 && intval($string) < 0)) {
			if($force_numeric == true) return floatval($string);
			elseif(preg_match('/^([-]?)[0]*([1-9][0-9]*)([^0-9].*)*$/', $string, $match)) return $match[1].$match[2];
			else return 0;
		} else {
			return intval($string);
		}
	}

	/**
	 * Function to change bytes to kB, MB, GB or TB
	 * @param int $size - size in bytes
	 * @param int precicion - after-comma-numbers (default: 2)
	 * @return string - formated bytes
	 */
	public function formatBytes($size, $precision = 2) {
		$base=log($size)/log(1024);
		$suffixes=array('', ' kB', ' MB', ' GB', ' TB');
		return round(pow(1024, $base-floor($base)), $precision).$suffixes[floor($base)];
	}

	/** IDN converter wrapper.
	 * all converter classes should be placed in ISPC_CLASS_PATH.'/idn/'
	 */
	private function _idn_encode_decode($domain, $encode = true) {
		if($domain == '') return '';
		if(preg_match('/^[0-9\.]+$/', $domain)) return $domain; // may be an ip address - anyway does not need to bee encoded

		// get domain and user part if it is an email
		$user_part = false;
		if(strpos($domain, '@') !== false) {
			$user_part = substr($domain, 0, strrpos($domain, '@'));
			$domain = substr($domain, strrpos($domain, '@') + 1);
		}

		if($encode == true) {
			if(function_exists('idn_to_ascii')) {
				$domain = idn_to_ascii($domain, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
			} elseif(file_exists(ISPC_CLASS_PATH.'/idn/idna_convert.class.php')) {
				/* use idna class:
                 * @author  Matthias Sommerfeld <mso@phlylabs.de>
                 * @copyright 2004-2011 phlyLabs Berlin, http://phlylabs.de
                 * @version 0.8.0 2011-03-11
                 */

				if(!is_object($this->idn_converter) || $this->idn_converter_name != 'idna_convert.class') {
					include_once ISPC_CLASS_PATH.'/idn/idna_convert.class.php';
					$this->idn_converter = new idna_convert(array('idn_version' => 2008));
					$this->idn_converter_name = 'idna_convert.class';
				}
				$domain = $this->idn_converter->encode($domain);
			}
		} else {
			if(function_exists('idn_to_utf8')) {
				$domain = idn_to_utf8($domain, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
			} elseif(file_exists(ISPC_CLASS_PATH.'/idn/idna_convert.class.php')) {
				/* use idna class:
                 * @author  Matthias Sommerfeld <mso@phlylabs.de>
                 * @copyright 2004-2011 phlyLabs Berlin, http://phlylabs.de
                 * @version 0.8.0 2011-03-11
                 */

				if(!is_object($this->idn_converter) || $this->idn_converter_name != 'idna_convert.class') {
					include_once ISPC_CLASS_PATH.'/idn/idna_convert.class.php';
					$this->idn_converter = new idna_convert(array('idn_version' => 2008));
					$this->idn_converter_name = 'idna_convert.class';
				}
				$domain = $this->idn_converter->decode($domain);
			}
		}

		if($user_part !== false) return $user_part . '@' . $domain;
		else return $domain;
	}

	public function idn_encode($domain) {
		$domains = explode("\n", $domain);
		for($d = 0; $d < count($domains); $d++) {
			$domains[$d] = $this->_idn_encode_decode($domains[$d], true);
		}
		return implode("\n", $domains);
	}

	public function idn_decode($domain) {
		$domains = explode("\n", $domain);
		for($d = 0; $d < count($domains); $d++) {
			$domains[$d] = $this->_idn_encode_decode($domains[$d], false);
		}
		return implode("\n", $domains);
	}

	public function is_allowed_user($username, $restrict_names = false) {
		global $app;
		
		$name_blacklist = array('root','ispconfig','vmail','getmail');
		if(in_array($username,$name_blacklist)) return false;
		
		if(preg_match('/^[a-zA-Z0-9\.\-_]{1,32}$/', $username) == false) return false;
		
		if($restrict_names == true && preg_match('/^web\d+$/', $username) == false) return false;
		
		return true;
	}
	
	public function is_allowed_group($groupname, $restrict_names = false) {
		global $app;
		
		$name_blacklist = array('root','ispconfig','vmail','getmail');
		if(in_array($groupname,$name_blacklist)) return false;
		
		if(preg_match('/^[a-zA-Z0-9\.\-_]{1,32}$/', $groupname) == false) return false;
		
		if($restrict_names == true && preg_match('/^client\d+$/', $groupname) == false) return false;
		
		return true;
	}
	
	public function getimagesizefromstring($string){
		if (!function_exists('getimagesizefromstring')) {
			$uri = 'data://application/octet-stream;base64,' . base64_encode($string);
			return getimagesize($uri);
		} else {
			return getimagesizefromstring($string);
		}		
	}
	
	public function password($minLength = 10, $special = false){
		global $app;
	
		$iteration = 0;
		$password = "";
		$maxLength = $minLength + 5;
		$length = $this->getRandomInt($minLength, $maxLength);

		while($iteration < $length){
			$randomNumber = (floor(((mt_rand() / mt_getrandmax()) * 100)) % 94) + 33;
			if(!$special){
				if (($randomNumber >=33) && ($randomNumber <=47)) { continue; }
				if (($randomNumber >=58) && ($randomNumber <=64)) { continue; }
				if (($randomNumber >=91) && ($randomNumber <=96)) { continue; }
				if (($randomNumber >=123) && ($randomNumber <=126)) { continue; }
			}
			$iteration++;
			$password .= chr($randomNumber);
		}
		$app->uses('validate_password');
		if($app->validate_password->password_check('', $password, '') !== false) $password = $this->password($minLength, $special);
		return $password;
	}

	public function getRandomInt($min, $max){
		return floor((mt_rand() / mt_getrandmax()) * ($max - $min + 1)) + $min;
	}
	
	public function generate_customer_no(){
		global $app;
		// generate customer no.
		$customer_no = mt_rand(100000, 999999);
		while($app->db->queryOneRecord("SELECT client_id FROM client WHERE customer_no = '".$customer_no."'")){
			$customer_no = mt_rand(100000, 999999);
		}
		
		return $customer_no;
	}
	
	public function generate_activation_code(){
		
		$activation_code = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
		
		return $activation_code;
	}
	
	public function client_activate($client_id){
		global $app, $conf;
		
		if(!is_file(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php')) return false;
		include(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php');
		
		$context = stream_context_create(array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
			)
		));

		$soap_client = new SoapClient(null, array('location' => $robot_conf['soap']['soap_location'],
									'uri'      => $robot_conf['soap']['soap_uri'],
									'trace' => 1,
									'exceptions' => 1,
									'stream_context' => $context));
	
	
		try {
			if($session_id = $soap_client->login($robot_conf['soap']['username'] , $robot_conf['soap']['password'])) {
				//echo 'Logged successfull. Session ID:'.$session_id.'<br />';
			}
			$error = '';
			$client_record = $soap_client->client_get($session_id, $client_id);
					
			$client_record['password'] = $this->password();
			if(trim($client_record['customer_no']) == '') $client_record['customer_no'] = $this->generate_customer_no();
			$client_record['username'] = 'c'.$client_record['customer_no'];
			//die($client_record['customer_no']);
			//$client_record['locked'] = 'n';
			$client_record['canceled'] = 'n';
			$soap_client->client_update($session_id, $client_id, 0, $client_record);
		
			$app->db->query("UPDATE client SET validation_status = 'accept', activation_code = '' WHERE client_id = ".$client_id);
			
			$activation_letter_filename = ISPC_ROOT_PATH.'/pdf/activation_letters/c'.$client_id.'-'.$client_record['activation_code'].'.pdf';
			if(is_file($activation_letter_filename)) unlink($activation_letter_filename);
		
			$webdetails['ispconfiguser'] = $client_record['username'];
			$webdetails['ispconfigpassword'] = $client_record['password'];
			$webdetails['customer_no'] = $client_record['customer_no'];
			$webdetails['contact'] = ($client_record['contact_firstname'] != ''? $client_record['contact_firstname'].' ' : '').$client_record['contact_name'];
			$webdetails['salutation_de'] = ($client_record['gender'] == 'f'? 'Frau' : 'Herr');
			$webdetails['salutation_en'] = ($client_record['gender'] == 'f'? 'Mrs.' : 'Mr.');
			$webdetails['ispconfigurl'] = 'http'.($_SERVER['HTTPS'] == 'on'? 's' : '').'://'.$_SERVER['HTTP_HOST'];
			$webdetails['signature_de'] = $robot_conf['textbaustein']['emailfooter'];
			$webdetails['signature_en'] = $robot_conf['textbaustein_en']['emailfooter'];
		
			if($error == ''){
				// send email with login details
				$invoice_client_settings = $app->db->queryOneRecord("SELECT * FROM invoice_client_settings WHERE client_id = ".intval($client_id));
				$company = $app->db->queryOneRecord("SELECT * FROM invoice_company WHERE invoice_company_id = ".$invoice_client_settings['invoice_company_id']);
				
				$subject = '['.$company['company_name_short'].'] Zugangsdaten zu unserem Kundeninterface / Login details for our customer interface';
			
				$app->uses('tpl');
				$tpl = new tpl;
				$tpl->newTemplate(ISPC_WEB_PATH."/client/templates/ispconfig_login.master");
				$tpl->setVar($webdetails);
				$message = $tpl->grab();
			
				if($robot_conf['production_mode']){
					$app->functions->mail(trim($client_record['email']), $subject, $message, 'support@timmehosting.de', '', 'application/pdf', '', '', 'f.timme@timmehosting.de,hetzner@timmehosting.de', 'TimmeHosting.de Support');
				
					$app->db->query("INSERT INTO `th_robot_message` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `client_id`, `subject`, `message`, `message_sent_date`, `message_sent_tstamp`, `email_from`, `email_to`, `email_to_bcc`) VALUES(1, 1, 'riud', 'riud', '', ".intval($client_id).", '".$app->db->quote($subject)."', '".$app->db->quote($message)."', '".date('Y-m-d')."', ".time().", 'support@timmehosting.de', '".trim($client_record['email'])."', 'f.timme@timmehosting.de,hetzner@timmehosting.de')");
				}
			}
		
			if($soap_client->logout($session_id)) {
				//echo 'Logged out.<br />';
			}

		} catch (SoapFault $e) {
			//$error .= $client->__getLastResponse();
			$error .= 'SOAP Error: '.$e->getMessage();
		}
	}
	
	public function client_activation_failed($client){
		global $app, $conf;
		
		if(!is_file(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php')) return false;
		include(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php');
		
		$client_id = intval($client['client_id']);
		$webdetails['contact'] = ($client['contact_firstname'] != ''? $client['contact_firstname'].' ' : '').$client['contact_name'];
		$webdetails['salutation_de'] = ($client['gender'] == 'f'? 'Frau' : 'Herr');
		$webdetails['salutation_en'] = ($client['gender'] == 'f'? 'Mrs.' : 'Mr.');
		$webdetails['signature_de'] = $robot_conf['textbaustein']['emailfooter'];
		$webdetails['signature_en'] = $robot_conf['textbaustein_en']['emailfooter'];
		
		
		// send email with login details
		$invoice_client_settings = $app->db->queryOneRecord("SELECT * FROM invoice_client_settings WHERE client_id = ".intval($client_id));
		$company = $app->db->queryOneRecord("SELECT * FROM invoice_company WHERE invoice_company_id = ".$invoice_client_settings['invoice_company_id']);
		$subject = '['.$company['company_name_short'].'] Aktivierung Ihres Kundenaccounts fehlgeschlagen / Activation of your customer account failed';
			
		$app->uses('tpl');
		$tpl = new tpl;
		$tpl->newTemplate(ISPC_WEB_PATH."/client/templates/ispconfig_client_activation_failed.master");
		$tpl->setVar($webdetails);
		$message = $tpl->grab();
			
		if($robot_conf['production_mode']){
			$app->functions->mail(trim($client['email']), $subject, $message, 'support@timmehosting.de', '', 'application/pdf', '', '', 'f.timme@timmehosting.de,hetzner@timmehosting.de', 'TimmeHosting.de Support');
				
			$app->db->query("INSERT INTO `th_robot_message` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `client_id`, `subject`, `message`, `message_sent_date`, `message_sent_tstamp`, `email_from`, `email_to`, `email_to_bcc`) VALUES(1, 1, 'riud', 'riud', '', ".intval($client_id).", '".$app->db->quote($subject)."', '".$app->db->quote($message)."', '".date('Y-m-d')."', ".time().", 'support@timmehosting.de', '".trim($client['email'])."', 'f.timme@timmehosting.de,hetzner@timmehosting.de')");
		}
	}
	
	public function client_review($client_id){
		global $app, $conf;
		
		if(!is_file(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php')) return false;
		include(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php');
		
		$context = stream_context_create(array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
			)
		));

		$soap_client = new SoapClient(null, array('location' => $robot_conf['soap']['soap_location'],
									'uri'      => $robot_conf['soap']['soap_uri'],
									'trace' => 1,
									'exceptions' => 1,
									'stream_context' => $context));
									
		try {
			if($session_id = $soap_client->login($robot_conf['soap']['username'] , $robot_conf['soap']['password'])) {
				//echo 'Logged successfull. Session ID:'.$session_id.'<br />';
			}
			$error = '';
			$client_record = $soap_client->client_get($session_id, $client_id);
					
			if(trim($client_record['customer_no']) == ''){
				$client_record['customer_no'] = $this->generate_customer_no();
				$soap_client->client_update($session_id, $client_id, 0, $client_record);
			}
		
			$activation_code = $this->generate_activation_code();
			$app->db->query("UPDATE client SET activation_code = '".$activation_code."'".($client_record['validation_status'] != 'review'? ", validation_status = 'review'" : "")." WHERE client_id = ".$client_id);
		
			$webdetails['customer_no'] = $client_record['customer_no'];
			$webdetails['contact'] = ($client_record['contact_firstname'] != ''? $client_record['contact_firstname'].' ' : '').$client_record['contact_name'];
			$webdetails['salutation_de'] = ($client_record['gender'] == 'f'? 'Frau' : 'Herr');
			$webdetails['salutation_en'] = ($client_record['gender'] == 'f'? 'Mrs.' : 'Mr.');
			$webdetails['signature_de'] = $robot_conf['textbaustein']['emailfooter'];
			$webdetails['signature_en'] = $robot_conf['textbaustein_en']['emailfooter'];
			$webdetails['email'] = $client_record['email'];
			include ISPC_LIB_PATH.'/lang/'.strtolower($client_record['language']).'.lng';
			$webdetails['latest_activation_date'] = date($wb['conf_format_dateshort'], $client_record['created_at'] + 14 * 86400);
		
			if($error == ''){
				// send email with login details
				$invoice_client_settings = $app->db->queryOneRecord("SELECT * FROM invoice_client_settings WHERE client_id = ".intval($client_id));
				$company = $app->db->queryOneRecord("SELECT * FROM invoice_company WHERE invoice_company_id = ".$invoice_client_settings['invoice_company_id']);
				
				$subject = '['.$company['company_name_short'].'] Aktivierung Ihres Kundenkontos / Activation of your customer account';
				$webdetails['company_name_short'] = $company['company_name_short'];
			
				$app->uses('tpl');
				$tpl = new tpl;
				$tpl->newTemplate(ISPC_WEB_PATH."/client/templates/ispconfig_client_activation_email.master");
				$tpl->setVar($webdetails);
				$message = $tpl->grab();
			
				if($robot_conf['production_mode']){
					$app->functions->mail(trim($client_record['email']), $subject, $message, 'support@timmehosting.de', '', 'application/pdf', '', '', 'f.timme@timmehosting.de,hetzner@timmehosting.de', 'TimmeHosting.de Support');
				
					$app->db->query("INSERT INTO `th_robot_message` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `client_id`, `subject`, `message`, `message_sent_date`, `message_sent_tstamp`, `email_from`, `email_to`, `email_to_bcc`) VALUES(1, 1, 'riud', 'riud', '', ".intval($client_id).", '".$app->db->quote($subject)."', '".$app->db->quote($message)."', '".date('Y-m-d')."', ".time().", 'support@timmehosting.de', '".trim($client_record['email'])."', 'f.timme@timmehosting.de,hetzner@timmehosting.de')");
				}
			}
		
			// create activation letter pdf
			$app->uses('pdf');
			$app->pdf->AliasNbPages();
			$app->pdf->createActivationLetter($client_id);

			$pdf_content = $app->pdf->Output('doc.pdf', 'S');

			$activation_letter_filename = ISPC_ROOT_PATH.'/pdf/activation_letters/c'.$client_id.'-'.$activation_code.'.pdf';
			file_put_contents($activation_letter_filename, $pdf_content);
		
			if(is_file($activation_letter_filename)){
				include(ISPC_WEB_PATH.'/billing/lib/onlinebrief24/Net/SFTP.php');
				$sftp = new Net_SFTP('api.letterei-onlinebrief.de');
				if (!$sftp->login($company['onlinebrief24_user'], $company['onlinebrief24_password'])) {
					$error_msg = $app->lng('onlinebrief24_login_failed_txt');
					$app->error($error_msg);
				}
				$upload_filename = ($company['onlinebrief24_print'] == 'coloured'? '1' : '0').'00'.($client_record['country'] == 'DE'? '1' : '0').'000000000-c'.$client_id.'-'.$activation_code.'.pdf';
				//die($upload_filename);
				$sftp->chdir('upload/api');
				$sftp->put($upload_filename, $activation_letter_filename, NET_SFTP_LOCAL_FILE);
			}
		
			if($soap_client->logout($session_id)) {
				//echo 'Logged out.<br />';
			}

		} catch (SoapFault $e) {
			//$error .= $client->__getLastResponse();
			$error .= 'SOAP Error: '.$e->getMessage();
		}
	}
	
	public function client_reject($client_id){
		global $app, $conf;
		
		if(!is_file(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php')) return false;
		include(ISPC_WEB_PATH.'/robot/lib/robot_config.inc.php');
		
		$context = stream_context_create(array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
			)
		));

		$soap_client = new SoapClient(null, array('location' => $robot_conf['soap']['soap_location'],
									'uri'      => $robot_conf['soap']['soap_uri'],
									'trace' => 1,
									'exceptions' => 1,
									'stream_context' => $context));
		
		try {
			if($session_id = $soap_client->login($robot_conf['soap']['username'] , $robot_conf['soap']['password'])) {
				//echo 'Logged successfull. Session ID:'.$session_id.'<br />';
			}
			$error = '';
			$client_record = $soap_client->client_get($session_id, $client_id);
					
			$client_record['locked'] = 'y';
			$client_record['canceled'] = 'y';
			$soap_client->client_update($session_id, $client_id, 0, $client_record);
		
			$app->db->query("UPDATE client SET validation_status = 'reject', activation_code = '' WHERE client_id = ".$client_id);
			$app->db->query("DELETE FROM th_order WHERE client_id = ".$client_id);
			
			$activation_letter_filename = ISPC_ROOT_PATH.'/pdf/activation_letters/c'.$client_id.'-'.$client_record['activation_code'].'.pdf';
			if(is_file($activation_letter_filename)) unlink($activation_letter_filename);
		
			$webdetails['contact'] = ($client_record['contact_firstname'] != ''? $client_record['contact_firstname'].' ' : '').$client_record['contact_name'];
			$webdetails['salutation_de'] = ($client_record['gender'] == 'f'? 'Frau' : 'Herr');
			$webdetails['salutation_en'] = ($client_record['gender'] == 'f'? 'Mrs.' : 'Mr.');
			$webdetails['signature_de'] = $robot_conf['textbaustein']['emailfooter'];
			$webdetails['signature_en'] = $robot_conf['textbaustein_en']['emailfooter'];
		
			if($error == ''){
				// send email with login details
				$invoice_client_settings = $app->db->queryOneRecord("SELECT * FROM invoice_client_settings WHERE client_id = ".intval($client_id));
				$company = $app->db->queryOneRecord("SELECT * FROM invoice_company WHERE invoice_company_id = ".$invoice_client_settings['invoice_company_id']);
				
				$subject = '['.$company['company_name_short'].'] Sperrung Ihres Kundenaccounts / Suspension of your customer account';
			
				$app->uses('tpl');
				$tpl = new tpl;
				$tpl->newTemplate(ISPC_WEB_PATH."/client/templates/ispconfig_client_rejection.master");
				$tpl->setVar($webdetails);
				$message = $tpl->grab();
			
				if($robot_conf['production_mode']){
					$app->functions->mail(trim($client_record['email']), $subject, $message, 'support@timmehosting.de', '', 'application/pdf', '', '', 'f.timme@timmehosting.de,hetzner@timmehosting.de', 'TimmeHosting.de Support');
				
					$app->db->query("INSERT INTO `th_robot_message` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `client_id`, `subject`, `message`, `message_sent_date`, `message_sent_tstamp`, `email_from`, `email_to`, `email_to_bcc`) VALUES(1, 1, 'riud', 'riud', '', ".intval($client_id).", '".$app->db->quote($subject)."', '".$app->db->quote($message)."', '".date('Y-m-d')."', ".time().", 'support@timmehosting.de', '".trim($client_record['email'])."', 'f.timme@timmehosting.de,hetzner@timmehosting.de')");
				}
			}
		
			if($soap_client->logout($session_id)) {
				//echo 'Logged out.<br />';
			}

		} catch (SoapFault $e) {
			//$error .= $client->__getLastResponse();
			$error .= 'SOAP Error: '.$e->getMessage();
		}
	}

}

?>
