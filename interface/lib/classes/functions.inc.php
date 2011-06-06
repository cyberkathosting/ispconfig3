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
			
			if($filename == '') {
				$path_parts = pathinfo($filepath);
				$filename = $path_parts["basename"];
				unset($path_parts);
			}

			$header = "From: $from\nReply-To: $from\n";
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
		$url = (stristr($_SERVER['SERVER_PROTOCOL'],'HTTPS'))?'https':'http';
		$url .= '://'.$_SERVER['SERVER_NAME'];
		if($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) {
			$url .= ':'.$_SERVER['SERVER_PORT'];
		}
		return $url;
	}
	
		
}

?>