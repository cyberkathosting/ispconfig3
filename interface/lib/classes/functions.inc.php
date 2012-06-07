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
	

	public function mail($to, $subject, $text, $from, $filepath = '', $filetype = 'application/pdf', $filename = '') {
		global $app,$conf;
		
		if($conf['demo_mode'] == true) $app->error("Mail sending disabled in demo mode.");
		
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

			$header = "Return-Path: $form\nFrom: $from\nReply-To: $from\n";
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
			$header .= "Content-Type: text/plain;\n\tcharset=\"UTF-8\"\n";
			$header .= "Content-Transfer-Encoding: 8bit\n\n";
			$subject      = "=?utf-8?B?".base64_encode($subject)."?=";
			mail($to, $subject, $text, $header);
		}

		return true;
	}
	
	public function array_merge($array1,$array2) {
		$out = $array1;
		foreach($array2 as $key => $val) {
			$out[$key] = $val;
		}
		return $out;
	}
	
	public function currency_format($number) {
		global $app;
		$number_format_decimals = (int)$app->lng('number_format_decimals');
		$number_format_dec_point = $app->lng('number_format_dec_point');
		$number_format_thousands_sep = $app->lng('number_format_thousands_sep');
		if($number_format_thousands_sep == 'number_format_thousands_sep') $number_format_thousands_sep = '';
		return number_format((double)$number, $number_format_decimals, $number_format_dec_point, $number_format_thousands_sep);
	}
	
	public function get_ispconfig_url() {
		$url = (stristr($_SERVER['SERVER_PROTOCOL'],'HTTPS') || stristr($_SERVER['HTTPS'],'on'))?'https':'http';
		$url .= '://'.$_SERVER['SERVER_NAME'];
		if($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
			$url .= ':'.$_SERVER['SERVER_PORT'];
		}
		return $url;
	}
	
    function json_encode($data) {
		if(!function_exists('json_encode')){
			if(is_array($data) || is_object($data)){
				$islist = is_array($data) && (empty($data) || array_keys($data) === range(0,count($data)-1));

				if($islist){
					$json = '[' . implode(',', array_map(array($this, "json_encode"), $data) ) . ']';
				} else {
					$items = Array();
					foreach( $data as $key => $value ) {
						$items[] = $this->json_encode("$key") . ':' . $this->json_encode($value);
					}
					$json = '{' . implode(',', $items) . '}';
				}
			} elseif(is_string($data)){
				# Escape non-printable or Non-ASCII characters.
				# I also put the \\ character first, as suggested in comments on the 'addclashes' page.
				$string = '"'.addcslashes($data, "\\\"\n\r\t/".chr(8).chr(12)).'"';
				$json = '';
				$len = strlen($string);
				# Convert UTF-8 to Hexadecimal Codepoints.
				for($i = 0; $i < $len; $i++){
					$char = $string[$i];
					$c1 = ord($char);

					# Single byte;
					if($c1 <128){
						$json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
						continue;
					}

					# Double byte
					$c2 = ord($string[++$i]);
					if(($c1 & 32) === 0){
						$json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
						continue;
					}

					# Triple
					$c3 = ord($string[++$i]);
					if(($c1 & 16) === 0){
						$json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128));
						continue;
					}

					# Quadruple
					$c4 = ord($string[++$i]);
					if(($c1 & 8) === 0){
						$u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1;

						$w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3);
						$w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128);
						$json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
					}
				}
			} else {
				# int, floats, bools, null
				$json = strtolower(var_export($data, true));
			}
			return $json;
		} else {
			return json_encode($data);
		}
    }

	
		
}

?>