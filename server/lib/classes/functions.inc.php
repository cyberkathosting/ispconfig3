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

		/* left in here just for the case...
		if($filepath != '') {
			if(!file_exists($filepath)) $app->error("Mail attachement does not exist ".$filepath);

			$content = file_get_contents($filepath);
			$content = chunk_split(base64_encode($content));
			$uid = strtoupper(md5(uniqid(time())));
			$subject      = "=?utf-8?B?".base64_encode($subject)."?=";

			if($filename == '') {
				$path_parts = pathinfo($filepath);
				$filename = $path_parts["basename"];
				unset($path_parts);
			}

			$header = "Return-Path: $from\nFrom: $from\nReply-To: $from\n";
			if($cc != '') $header .= "Cc: $cc\n";
			if($bcc != '') $header .= "Bcc: $bcc\n";
			$header .= "MIME-Version: 1.0\n";
			$header .= "Content-Type: multipart/mixed; boundary=$uid\n";

			$header .= "--$uid\n";
			$header .= "Content-Type: text/plain;\n\tcharset=\"UTF-8\"\n";
			$header .= "Content-Transfer-Encoding: 8bit\n\n";
			$header .= "$text\n";

			$header .= "--$uid\n";
			$header .= "Content-Type: $filetype; name=\"$filename\"\n";

			$header .= "Content-Transfer-Encoding: base64\n";
			$header .= "Content-Disposition: attachment; filename=\"$filename\"\n\n";
			$header .= "$content\n";

			$header .= "--$uid--";

			mail($to, $subject, "", $header);
		} else {
			$header = "From: $from\nReply-To: $from\n";
			if($cc != '') $header .= "Cc: $cc\n";
			if($bcc != '') $header .= "Bcc: $bcc\n";
			$header .= "Content-Type: text/plain;\n\tcharset=\"UTF-8\"\n";
			$header .= "Content-Transfer-Encoding: 8bit\n\n";
			$subject      = "=?utf-8?B?".base64_encode($subject)."?=";
			mail($to, $subject, $text, $header);
		}
		*/
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

		$ips = array();
		$results = $app->db->queryAllRecords("SELECT ip_address AS ip FROM server_ip WHERE ip_type = ?", $type);
		if(!empty($results) && is_array($results)){
			foreach($results as $result){
				if(preg_match($regex, $result['ip'])) $ips[] = $result['ip'];
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

		$results = $app->db->queryAllRecords("SELECT xfer FROM dns_slave WHERE xfer != ''");
		if(!empty($results) && is_array($results)){
			foreach($results as $result){
				$tmp_ips = explode(',', $result['xfer']);
				foreach($tmp_ips as $tmp_ip){
					$tmp_ip = trim($tmp_ip);
					if(preg_match($regex, $tmp_ip)) $ips[] = $tmp_ip;
				}
			}
		}
		$results = $app->db->queryAllRecords("SELECT xfer FROM dns_soa WHERE xfer != ''");
		if(!empty($results) && is_array($results)){
			foreach($results as $result){
				$tmp_ips = explode(',', $result['xfer']);
				foreach($tmp_ips as $tmp_ip){
					$tmp_ip = trim($tmp_ip);
					if(preg_match($regex, $tmp_ip)) $ips[] = $tmp_ip;
				}
			}
		}
		$results = $app->db->queryAllRecords("SELECT also_notify FROM dns_soa WHERE also_notify != ''");
		if(!empty($results) && is_array($results)){
			foreach($results as $result){
				$tmp_ips = explode(',', $result['also_notify']);
				foreach($tmp_ips as $tmp_ip){
					$tmp_ip = trim($tmp_ip);
					if(preg_match($regex, $tmp_ip)) $ips[] = $tmp_ip;
				}
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
					'description' => $type,
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
				if(defined('IDNA_NONTRANSITIONAL_TO_ASCII') && defined('INTL_IDNA_VARIANT_UTS46') && constant('IDNA_NONTRANSITIONAL_TO_ASCII')) {
					$domain = idn_to_ascii($domain, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
				} else {
					$domain = idn_to_ascii($domain);
				}
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
				if(defined('IDNA_NONTRANSITIONAL_TO_ASCII') && defined('INTL_IDNA_VARIANT_UTS46') && constant('IDNA_NONTRANSITIONAL_TO_ASCII')) {
					$domain = idn_to_utf8($domain, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
				} else {
					$domain = idn_to_utf8($domain);
				}
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
	
	public function generate_ssh_key($client_id, $username = ''){
		global $app;
		
		// generate the SSH key pair for the client
		$id_rsa_file = '/tmp/'.uniqid('',true);
		$id_rsa_pub_file = $id_rsa_file.'.pub';
		if(file_exists($id_rsa_file)) unset($id_rsa_file);
		if(file_exists($id_rsa_pub_file)) unset($id_rsa_pub_file);
		if(!file_exists($id_rsa_file) && !file_exists($id_rsa_pub_file)) {
			$app->system->exec_safe('ssh-keygen -t rsa -C ? -f ? -N ""', $username.'-rsa-key-'.time(), $id_rsa_file);
			$app->db->query("UPDATE client SET created_at = UNIX_TIMESTAMP(), id_rsa = ?, ssh_rsa = ? WHERE client_id = ?", $app->system->file_get_contents($id_rsa_file), $app->system->file_get_contents($id_rsa_pub_file), $client_id);
			$app->system->exec_safe('rm -f ? ?', $id_rsa_file, $id_rsa_pub_file);
		} else {
			$app->log("Failed to create SSH keypair for ".$username, LOGLEVEL_WARN);
		}
	}

}

?>
