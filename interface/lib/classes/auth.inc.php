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

class auth {
	var $client_limits = null;

	public function get_user_id()
	{
		global $app;
		return $app->functions->intval($_SESSION['s']['user']['userid']);
	}

	public function is_admin() {
		if($_SESSION['s']['user']['typ'] == 'admin') {
			return true;
		} else {
			return false;
		}
	}
	
	public function is_superadmin() {
		if($_SESSION['s']['user']['typ'] == 'admin' && $_SESSION['s']['user']['userid'] == 1) {
			return true;
		} else {
			return false;
		}
	}

	public function has_clients($userid) {
		global $app, $conf;

		$userid = $app->functions->intval($userid);
		$client = $app->db->queryOneRecord("SELECT client.limit_client FROM sys_user, client WHERE sys_user.userid = ? AND sys_user.client_id = client.client_id", $userid);
		if($client['limit_client'] != 0) {
			return true;
		} else {
			return false;
		}
	}
	
	// Function to check if a client belongs to a reseller
	public function is_client_of_reseller($userid = 0) {
		global $app, $conf;
		
		if($userid == 0) $userid = $_SESSION['s']['user']['userid'];

		$client = $app->db->queryOneRecord("SELECT client.sys_userid, client.sys_groupid FROM sys_user, client WHERE sys_user.userid = ? AND sys_user.client_id = client.client_id", $userid);
		if($client['sys_userid'] > 1 || $client['sys_groupid'] > 1) {
			return true;
		} else {
			return false;
		}
	}

	//** This function adds a given group id to a given user.
	public function add_group_to_user($userid, $groupid) {
		global $app;

		$userid = $app->functions->intval($userid);
		$groupid = $app->functions->intval($groupid);

		if($userid > 0 && $groupid > 0) {
			$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE userid = ?", $userid);
			$groups = explode(',', $user['groups']);
			if(!in_array($groupid, $groups)) $groups[] = $groupid;
			$groups_string = implode(',', $groups);
			$sql = "UPDATE sys_user SET groups = ? WHERE userid = ?";
			$app->db->query($sql, $groups_string, $userid);
			return true;
		} else {
			return false;
		}
	}

	//** This function returns given client limit as integer, -1 means no limit
	public function get_client_limit($userid, $limitname)
	{
		global $app;
		
		$userid = $app->functions->intval($userid);
		if(!preg_match('/^[a-zA-Z0-9\-\_]{1,64}$/',$limitname)) $app->error('Invalid limit name '.$limitname);
		
		// simple query cache
		if($this->client_limits===null)
			$this->client_limits = $app->db->queryOneRecord("SELECT client.* FROM sys_user, client WHERE sys_user.userid = ? AND sys_user.client_id = client.client_id", $userid);

		// isn't client -> no limit
		if(!$this->client_limits)
			return -1;

		if(isset($this->client_limits['limit_'.$limitname])) {
			return $this->client_limits['limit_'.$limitname];
		}
	}

	//** This function removes a given group id from a given user.
	public function remove_group_from_user($userid, $groupid) {
		global $app;

		$userid = $app->functions->intval($userid);
		$groupid = $app->functions->intval($groupid);

		if($userid > 0 && $groupid > 0) {
			$user = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE userid = ?", $userid);
			$groups = explode(',', $user['groups']);
			$key = array_search($groupid, $groups);
			unset($groups[$key]);
			$groups_string = implode(',', $groups);
			$sql = "UPDATE sys_user SET groups = ? WHERE userid = ?";
			$app->db->query($sql, $groups_string, $userid);
			return true;
		} else {
			return false;
		}
	}


       /**
        * Check that the user has access to the given module.
        *
        * @return boolean
        */
       public function verify_module_permissions($module)  {
		// Check if the current user has the permissions to access this module
		$module = trim(preg_replace('@\s+@', '', $module));
		$user_modules = explode(',',$_SESSION["s"]["user"]["modules"]);
               $can_use_module = false;
		if(strpos($module, ',') !== false){
			$tmp_modules = explode(',', $module);
			if(is_array($tmp_modules) && !empty($tmp_modules)){
				foreach($tmp_modules as $tmp_module){
					if($tmp_module != ''){
						if(in_array($tmp_module,$user_modules)) {
							$can_use_module = true;
							break;
						}
					}
				}
			}
               }
               elseif(in_array($module,$user_modules)) {
                       $can_use_module = true;
               }
               return $can_use_module;
       }

       /**
        * Check that the user has access to the given module, redirect and exit on failure.
        */
       public function check_module_permissions($module)  {
               if(!$this->verify_module_permissions($module)) {
                       // echo "LOGIN_REDIRECT:/index.php";
                       header("Location: /index.php");
                       exit;
		}
	}
	
	public function check_security_permissions($permission) {
		
		global $app;
		
		$app->uses('getconf');
		$security_config = $app->getconf->get_security_config('permissions');

		$security_check = false;
		if($security_config[$permission] == 'yes') $security_check = true;
		if($security_config[$permission] == 'superadmin' && $app->auth->is_superadmin()) $security_check = true;
		if($security_check !== true) {
			$app->error($app->lng('security_check1_txt').' '.$permission.' '.$app->lng('security_check2_txt'));
		}
		
	}

	public function get_random_password($minLength = 8, $special = false) {
		if($minLength < 8) $minLength = 8;
		$maxLength = $minLength + 5;
		$length = mt_rand($minLength, $maxLength);
		
		$alphachars = "abcdefghijklmnopqrstuvwxyz";
		$upperchars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$numchars = "1234567890";
		$specialchars = "!@#_";
		
		$num_special = 0;
		if($special == true) {
			$num_special = intval(mt_rand(0, round($length / 4))) + 1;
		}
		$numericlen = mt_rand(1, 2);
		$alphalen = $length - $num_special - $numericlen;
		$upperlen = intval($alphalen / 2);
		$alphalen = $alphalen - $upperlen;
		$password = '';
		
		for($i = 0; $i < $alphalen; $i++) {
			$password .= substr($alphachars, mt_rand(0, strlen($alphachars) - 1), 1);
		}
		
		for($i = 0; $i < $upperlen; $i++) {
			$password .= substr($upperchars, mt_rand(0, strlen($upperchars) - 1), 1);
		}
		
		for($i = 0; $i < $num_special; $i++) {
			$password .= substr($specialchars, mt_rand(0, strlen($specialchars) - 1), 1);
		}
		
		for($i = 0; $i < $numericlen; $i++) {
			$password .= substr($numchars, mt_rand(0, strlen($numchars) - 1), 1);
		}
		
		return str_shuffle($password);
	}

	public function crypt_password($cleartext_password, $charset = 'UTF-8') {
		if($charset != 'UTF-8') {
			$cleartext_password = mb_convert_encoding($cleartext_password, $charset, 'UTF-8');
		}
		
		if(defined('CRYPT_SHA512') && CRYPT_SHA512 == 1) {
			$salt = '$6$rounds=5000$';
			$salt_length = 16;
		} elseif(defined('CRYPT_SHA256') && CRYPT_SHA256 == 1) {
			$salt = '$5$rounds=5000$';
			$salt_length = 16;
		} else {
			$salt = '$1$';
			$salt_length = 12;
		}
		
		if(function_exists('openssl_random_pseudo_bytes')) {
			$salt .= substr(bin2hex(openssl_random_pseudo_bytes($salt_length)), 0, $salt_length);
		} else {
			$base64_alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789./';
			for($n = 0; $n < $salt_length; $n++) {
				$salt .= $base64_alphabet[mt_rand(0, 63)];
			}
		}
		$salt .= "$";
		return crypt($cleartext_password, $salt);
	}
	
	public function csrf_token_get($form_name) {
		/* CSRF PROTECTION */
		// generate csrf protection id and key
		$_csrf_id = uniqid($form_name . '_'); // form id
		$_csrf_key = sha1(uniqid(microtime(true), true)); // the key
		if(!isset($_SESSION['_csrf'])) $_SESSION['_csrf'] = array();
		if(!isset($_SESSION['_csrf_timeout'])) $_SESSION['_csrf_timeout'] = array();
		$_SESSION['_csrf'][$_csrf_id] = $_csrf_key;
		$_SESSION['_csrf_timeout'][$_csrf_id] = time() + 3600; // timeout hash in 1 hour
		
		return array('csrf_id' => $_csrf_id,'csrf_key' => $_csrf_key);
	}
	
	public function csrf_token_check($method = 'POST') {
		global $app;
		
		if($method == 'POST') {
			$input_vars = $_POST;
		} elseif ($method == 'GET') {
			$input_vars = $_GET;
		} else {
			$app->error('Unknown CSRF verification method.');
		}
		
		//print_r($input_vars);
		//die(print_r($_SESSION['_csrf']));
		
		if(isset($input_vars) && is_array($input_vars)) {
			$_csrf_valid = false;
			if(isset($input_vars['_csrf_id']) && isset($input_vars['_csrf_key'])) {
				$_csrf_id = trim($input_vars['_csrf_id']);
				$_csrf_key = trim($input_vars['_csrf_key']);
				if(isset($_SESSION['_csrf']) && isset($_SESSION['_csrf'][$_csrf_id]) && isset($_SESSION['_csrf_timeout']) && isset($_SESSION['_csrf_timeout'][$_csrf_id])) {
					if($_SESSION['_csrf'][$_csrf_id] === $_csrf_key && $_SESSION['_csrf_timeout'][$_csrf_id] >= time()) $_csrf_valid = true;
				}
			}
			if($_csrf_valid !== true) {
				$app->log('CSRF attempt blocked. Referer: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'unknown'), LOGLEVEL_WARN);
				$app->error($app->lng('err_csrf_attempt_blocked'));
			}
			$_SESSION['_csrf'][$_csrf_id] = null;
			$_SESSION['_csrf_timeout'][$_csrf_id] = null;
			unset($_SESSION['_csrf'][$_csrf_id]);
			unset($_SESSION['_csrf_timeout'][$_csrf_id]);
			
			if(isset($_SESSION['_csrf_timeout']) && is_array($_SESSION['_csrf_timeout'])) {
				$to_unset = array();
				foreach($_SESSION['_csrf_timeout'] as $_csrf_id => $timeout) {
					if($timeout < time()) $to_unset[] = $_csrf_id;
				}
				foreach($to_unset as $_csrf_id) {
					$_SESSION['_csrf'][$_csrf_id] = null;
					$_SESSION['_csrf_timeout'][$_csrf_id] = null;
					unset($_SESSION['_csrf'][$_csrf_id]);
					unset($_SESSION['_csrf_timeout'][$_csrf_id]);
				}
				unset($to_unset);
			}
		}
	}

}

?>
