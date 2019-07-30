<?php

/**
 Copyright (c) 2007 - 2013, Till Brehm, projektfarm Gmbh
 Copyright (c) 2013, Florian Schaal, info@schaal-24.de
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

 @author Florian Schaal, info@schaal-24.de
 @copyright Florian Schaal, info@schaal-24.de
 */


class validate_dkim {

	function get_error($errmsg) {
		global $app;
		if(isset($app->tform->wordbook[$errmsg])) {
			return $app->tform->wordbook[$errmsg]."<br>\r\n";
		} else {
			return $errmsg."<br>\r\n";
		}
	}


	/**
	 * Validator function for private DKIM-Key
	 */
	function check_private_key($field_name, $field_value, $validator) {
		global $app;
		
		$dkim_enabled=$_POST['dkim'];
		if ($dkim_enabled == 'y') {
			if (empty($field_value)) return $this->get_error($validator['errmsg']);
			$app->system->exec_safe('echo ?|openssl rsa -check', $field_value);
			$result = $app->system->last_exec_retcode();
			if($result != 0) return $this->get_error($validator['errmsg']);
		}
	}

	/**
	 * Check function for DNS-Template
	 */
	function check_template($field_name, $field_value, $validator) {
		$dkim=false;
		if(is_array($field_value) && !empty($field_value)){
			foreach($field_value as $field ) { if($field == 'DKIM') $dkim=true; }
			if ($dkim && $field_value[0]!='DOMAIN') return $this->get_error($validator['errmsg']);
		}
	}


	/**
	 * Validator function for $_POST
	 *
	 * @return boolean - true if $POST contains a real key-file
	 */
	function validate_post($key, $value, $dkim_strength) {
		$value=str_replace(array("\n", "-----BEGIN RSA PRIVATE KEY-----", "-----END RSA PRIVATE KEY-----", " "), "", $value);
		switch ($key) {
		case 'public':
			if (preg_match("/(^-----BEGIN PUBLIC KEY-----)[a-zA-Z0-9\r\n\/\+=]{1,221}(-----END PUBLIC KEY-----(\n|\r)?$)/", $value) === 1) { return true; } else { return false; }
			break;
		case 'private':
			if ( $dkim_strength == 1024 ) $range = "{812,816}";
			if ( $dkim_strength == 2048 ) $range = "{1588,1592}";
			if ( $dkim_strength == 4096 ) $range = "{3132,3136}";
			if ( preg_match("/^[a-zA-Z0-9\/\+=]".$range."$/", $value ) === 1) return true; else return false;
			break;
		}
	}

}
