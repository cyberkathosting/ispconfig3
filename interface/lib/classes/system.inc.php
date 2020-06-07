<?php

/*
Copyright (c) 2016, Florian Schaal, schaal @it
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

class system {

	var $client_service = null;
	private $_last_exec_out = null;
	private $_last_exec_retcode = null;

	public function has_service($userid, $service) {
		global $app;

		if(!preg_match('/^[a-z]+$/', $service)) $app->error('Invalid service '.$service);

		if(isset($_SESSION['s']['user']) && $_SESSION['s']['user']['typ'] == 'admin') return true; //* We do not check admin-users

		// simple query cache
		if($this->client_service===null)
			$this->client_service =  $app->db->queryOneRecord("SELECT client.* FROM sys_user, client WHERE sys_user.userid = ? AND sys_user.client_id = client.client_id", $userid);

		// isn't service
		if(!$this->client_service) return false;

		if($this->client_service['default_'.$service.'server'] > 0 || $this->client_service[$service.'_servers'] != '') {
			return true;
		} else {
			return false;
		}
	}

	public function last_exec_out() {
		return $this->_last_exec_out;
	}
	
	public function last_exec_retcode() {
		return $this->_last_exec_retcode;
	}
	
	public function exec_safe($cmd) {
		$arg_count = func_num_args();
		if($arg_count != substr_count($cmd, '?') + 1) {
			trigger_error('Placeholder count not matching argument list.', E_USER_WARNING);
			return false;
		}
		if($arg_count > 1) {
			$args = func_get_args();
			array_shift($args);
			
			$pos = 0;
			$a = 0;
			foreach($args as $value) {
				$a++;
				
				$pos = strpos($cmd, '?', $pos);
				if($pos === false) {
					break;
				}
				$value = escapeshellarg($value);
				$cmd = substr_replace($cmd, $value, $pos, 1);
				$pos += strlen($value);
			}
		}
		
		$this->_last_exec_out = null;
		$this->_last_exec_retcode = null;
		return exec($cmd, $this->_last_exec_out, $this->_last_exec_retcode);
	}
	
	public function system_safe($cmd) {
		call_user_func_array(array($this, 'exec_safe'), func_get_args());
		return implode("\n", $this->_last_exec_out);
	}	

    //* Check if a application is installed
    public function is_installed($appname) {
        $this->exec_safe('which ? 2> /dev/null', $appname);
        $out = $this->last_exec_out();
        $returncode = $this->last_exec_retcode();
        if(isset($out[0]) && stristr($out[0], $appname) && $returncode == 0) {
            return true;
        } else {
            return false;
        }
    }
	
} //* End Class
