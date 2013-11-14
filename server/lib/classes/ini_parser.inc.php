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

class ini_parser{

	var $config;

	function parse_ini_string($ini) {
		$ini = str_replace("\r\n", "\n", $ini);
		$lines = explode("\n", $ini);

		foreach($lines as $line) {
			$line = trim($line);
			if($line != '') {
				if(preg_match("/^\[([\w\d_]+)\]$/", $line, $matches)) {
					$section = strtolower($matches[1]);
				} elseif(preg_match("/^([\w\d_]+)=(.*)$/", $line, $matches) && $section != null) {
					$item = trim($matches[1]);
					$this->config[$section][$item] = trim($matches[2]);
				}
			}
		}
		return $this->config;
	}



	function get_ini_string($file) {
		$content = '';
		foreach($this->config as $section => $data) {
			$content .= "[$section]\n";
			foreach($data as $item => $value) {
				if($value != '') {
					$value  = trim($value);
					$item  = trim($item);
					$content .= "$item=$value\n";
				}
			}
		}
		return $content;
	}

}

?>
