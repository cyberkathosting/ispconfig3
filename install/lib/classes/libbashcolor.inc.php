<?php

/*
(c) 2017 by Marius Burkard, pixcept KG 
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


abstract class PXBashColor {

		
	private static $markers = array(
		'black' => 30,
		'red' => 31,
		'green' => 32,
		'yellow' => 33,
		'blue' => 34,
		'magenta' => 35,
		'cyan' => 36,
		'lightgrey' => 37,
		'default' => 39,
		'darkgrey' => 90,
		'lightred' => 91,
		'lightgreen' => 92,
		'lightyellow' => 93,
		'lightblue' => 94,
		'lightmagenta' => 95,
		'lightcyan' => 96,
		'white' => 97,
		
		'bg:black' => 40,
		'bg:red' => 41,
		'bg:green' => 42,
		'bg:yellow' => 43,
		'bg:blue' => 44,
		'bg:magenta' => 45,
		'bg:cyan' => 46,
		'bg:lightgrey' => 47,
		'bg:default' => 49,
		'bg:darkgrey' => 100,
		'bg:lightred' => 101,
		'bg:lightgreen' => 102,
		'bg:lightyellow' => 103,
		'bg:lightblue' => 104,
		'bg:lightmagenta' => 105,
		'bg:lightcyan' => 106,
		'bg:white' => 107,
		
		'bold' => 1,
		'dim' => 2,
		'italic' => 3,
		'underlined' => 4,
		'blink' => 5,
		'invert' => 7,
		'hidden' => 8
		
	);
	
	private static function getCode($active) {
		$code = "\033[0;";
		if(count($active) > 0) {
			$tmp = array();
			for($i = 0; $i < count($active); $i++) {
				$tmp[] = self::$markers[$active[$i]];
			}
			sort($tmp);
			$code .= implode(';', $tmp);
			unset($tmp);
		} else {
			$code .= "0";
		}
		
		$code .= "m";
		return $code;
	}

	public static function getString($string, $ignore_unknown_tags = false) {
		
		$active = array();
		$echo_string = "";
		
		while(preg_match('/<(\/?(?:bg:)?\w+)>/i', $string, $match, PREG_OFFSET_CAPTURE)) {
			$pos = $match[0][1];
			$tag = $match[1][0];
			$len = strlen($match[0][0]);
			
			$close = false;
			if(substr($tag, 0, 1) == '/') {
				$close = true;
				$tag = substr($tag, 1);
			}
			
			$key = $tag;
			if($key == 'strong' || $key == 'b') $key = 'bold';
			elseif($key == 'em' || $key == 'i') $key = 'italic';
			elseif($key == 'u') $key = 'underlined';
			elseif($key == 'inv') $key = 'invert';
			
			if(!array_key_exists($key, self::$markers)) {
				if($ignore_unknown_tags == false) {
					throw new Exception('unknown tag: ' . $tag);
				} else {
					$echo_string .= self::getCode($active);
					$echo_string .= substr($string, 0, $pos + $len);
					$string = substr($string, $pos + $len);
					continue;
				}
			}
			
			if($pos > 0) {
				$echo_string .= self::getCode($active);
				$echo_string .= substr($string, 0, $pos);
			}
			
			if($close == true) {
				$last = end($active);
				if($key != $last) {
					throw new Exception('unbalanced tag: ' . $tag . ' (' . $last . ' expected), ' . var_export($active, true));
				}
				array_pop($active);
			} else {
				array_push($active, $key);
			}

			$string = substr($string, $pos + $len);
		}
		
		if($string != '') {
			$echo_string .= self::getCode($active);
			$echo_string .= $string;
		}
		
		$echo_string .= "\e[0m";
		return $echo_string;
	}

}