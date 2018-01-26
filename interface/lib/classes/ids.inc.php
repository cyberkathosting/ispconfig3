<?php

/*
Copyright (c) 2014, Till Brehm, ISPConfig UG
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

class ids {

	public function start()
	{
		global $app, $conf;
		
		$security_config = $app->getconf->get_security_config('ids');
		
		set_include_path(
			get_include_path()
			. PATH_SEPARATOR
			. ISPC_CLASS_PATH.'/'
		);
			
		require_once(ISPC_CLASS_PATH.'/IDS/Init.php');
		require_once(ISPC_CLASS_PATH.'/IDS/Monitor.php');
		require_once(ISPC_CLASS_PATH.'/IDS/Filter.php');
		require_once(ISPC_CLASS_PATH.'/IDS/Filter/Storage.php');
		require_once(ISPC_CLASS_PATH.'/IDS/Report.php');
		require_once(ISPC_CLASS_PATH.'/IDS/Event.php');
		require_once(ISPC_CLASS_PATH.'/IDS/Converter.php');
		
		$ids_request = array(
			'GET' => $_GET,
			'POST' => $_POST,
			'COOKIE' => $_COOKIE
		);
		
		$ids_init = IDS\Init::init(ISPC_CLASS_PATH.'/IDS/Config/Config.ini.php');
		
		$ids_init->config['General']['base_path'] = ISPC_CLASS_PATH.'/IDS/';
		$ids_init->config['General']['tmp_path'] = '../../../temp';
		$ids_init->config['General']['use_base_path'] = true;
		$ids_init->config['Caching']['caching'] = 'none';
		$ids_init->config['Logging']['path'] = '../../../temp/ids.log';
		
		$current_script_name = trim($_SERVER['SCRIPT_NAME']);
		
		// Get whitelist
		$whitelist_path = '/usr/local/ispconfig/security/ids.whitelist';
		if(is_file('/usr/local/ispconfig/security/ids.whitelist.custom')) $whitelist_path = '/usr/local/ispconfig/security/ids.whitelist.custom';
		if(!is_file($whitelist_path)) $whitelist_path = realpath(ISPC_ROOT_PATH.'/../security/ids.whitelist');
		
		$whitelist_lines = file($whitelist_path);
		if(is_array($whitelist_lines)) {
			foreach($whitelist_lines as $line) {
				$line = trim($line);
				if(substr($line,0,1) != '#') {
					list($user,$path,$varname) = explode(':',$line);
					if($current_script_name == $path) {
						if($user = 'any' 
							|| ($user == 'user' && ($_SESSION['s']['user']['typ'] == 'user' || $_SESSION['s']['user']['typ'] == 'admin')) 
							|| ($user == 'admin' && $_SESSION['s']['user']['typ'] == 'admin')) {
								$ids_init->config['General']['exceptions'][] = $varname;
								
						}
					}
				}
			}
		}
		
		// Get HTML fields
		$htmlfield_path = '/usr/local/ispconfig/security/ids.htmlfield';
		if(is_file('/usr/local/ispconfig/security/ids.htmlfield.custom')) $htmlfield_path = '/usr/local/ispconfig/security/ids.htmlfield.custom';
		if(!is_file($htmlfield_path)) $htmlfield_path = realpath(ISPC_ROOT_PATH.'/../security/ids.htmlfield');
		
		$htmlfield_lines = file($htmlfield_path);
		if(is_array($htmlfield_lines)) {
			foreach($htmlfield_lines as $line) {
				$line = trim($line);
				if(substr($line,0,1) != '#') {
					list($user,$path,$varname) = explode(':',$line);
					if($current_script_name == $path) {
						if($user = 'any' 
							|| ($user == 'user' && ($_SESSION['s']['user']['typ'] == 'user' || $_SESSION['s']['user']['typ'] == 'admin')) 
							|| ($user == 'admin' && $_SESSION['s']['user']['typ'] == 'admin')) {
								$ids_init->config['General']['html'][] = $varname;
						}
					}
				}
			}
		}
		
		$ids = new IDS\Monitor($ids_init);
		$ids_result = $ids->run($ids_request);
		
		if (!$ids_result->isEmpty()) {
			
			$impact = $ids_result->getImpact();
			
			// Choose level from security config
			if($app->auth->is_admin()) {
				// User is admin
				$ids_log_level = $security_config['ids_admin_log_level'];
				$ids_warn_level = $security_config['ids_admin_warn_level'];
				$ids_block_level = $security_config['ids_admin_block_level'];
			} elseif(is_array($_SESSION['s']['user']) && $_SESSION['s']['user']['userid'] > 0) {
				// User is Client or Reseller
				$ids_log_level = $security_config['ids_user_log_level'];
				$ids_warn_level = $security_config['ids_user_warn_level'];
				$ids_block_level = $security_config['ids_user_block_level'];
			} else {
				// Not logged in
				$ids_log_level = $security_config['ids_anon_log_level'];
				$ids_warn_level = $security_config['ids_anon_warn_level'];
				$ids_block_level = $security_config['ids_anon_block_level'];
			}
			
			if($impact >= $ids_log_level) {
				$ids_log = ISPC_ROOT_PATH.'/temp/ids.log';
				if(!is_file($ids_log)) touch($ids_log);
				
				$user = isset($_SESSION['s']['user']['typ'])?$_SESSION['s']['user']['typ']:'any';
				
				$log_lines = '';
				foreach ($ids_result->getEvents() as $event) {
					$log_lines .= $user.':'.$current_script_name.':'.$event->getName()."\n";
				}
				file_put_contents($ids_log,$log_lines,FILE_APPEND);
				
			}
			
			if($impact >= $ids_warn_level) {
				$app->log("PHP IDS Alert.".$ids_result, 2);
			}
			
			if($impact >= $ids_block_level) {
				$app->error("Possible attack detected. This action has been logged.",'', true, 2);
			}
			
		}
	}
	
}

?>
