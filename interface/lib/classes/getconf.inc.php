<?php
/**
 * getconf class
 * 
 * @author Till Brehm
 * @copyright  2005, Till Brehm, projektfarm Gmbh
 * @version 0.1
 * @package ISPConfig
 */
/*
Copyright (c) 2006, Till Brehm, projektfarm Gmbh
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

class getconf {

	private $config;
	
	public function get_server_config($server_id, $section = '') {
		global $app;
	
		if(!is_array($this->config[$server_id])) {
			$app->uses('ini_parser');
			$server_id = intval($server_id);
			$server = $app->db->queryOneRecord("SELECT config FROM server WHERE server_id = $server_id");
			$this->config[$server_id] = $app->ini_parser->parse_ini_string(stripslashes($server["config"]));
		}
		return ($section == '') ? $this->config[$server_id] : $this->config[$server_id][$section];
	}
	
	public function get_global_config() {
		
		die("not yet implemented");
	}
}

?>