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



	function parse_ini_file($file) {
		if(!is_file($file)) {
			return false;
		}
		return $this->parse_ini_string(file_get_contents($file));
	}



	function array_to_ini($array,$out="") {
		if(!is_array($array)) {
			return $array;
		}
		$t="";
		$q=false;
		foreach($array as $c=>$d) {
			if(is_array($d)) {
				$t .= $this->array_to_ini($d,$c);
			} else {
				if($c===intval($c)) {
					if(!empty($out)) {
						$t.="\r\n".$out." = \"".$d."\"";
						if($q!=2) {
							$q=true;
						}
					} else {
						$t.="\r\n".$d;
					}
				} else {
					$t.="\r\n".$c." = \"".$d."\"";
					$q=2;
				}
			}
		}
		if($q!=true && !empty($out))
			return "[".$out."]\r\n".$t;
		if(!empty($out))
			return  $t;
		return trim($t);
	}



	function write_ini_file($array, $file) {
		$ret = false;
		$ini = $this->array_to_ini($array);

		if ($fp = fopen($file, 'w')) {
			$startTime = microtime();
			do
			{
				$canWrite = flock($fp, LOCK_EX);
				// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
				if(!$canWrite) usleep(round(rand(0, 100)*1000));
			} while ((!$canWrite) and ((microtime()-$startTime) < 1000));

			// file was locked so now we can store information
			if ($canWrite) {
				$ret = fwrite($fp, $ini);
				flock($fp, LOCK_UN);
			}
			fclose($fp);
		}
		return $ret;
	}



	// unused function, and misleading arg ($file is unused)
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
