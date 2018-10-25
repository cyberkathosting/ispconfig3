<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
Copyright (c) 2014, Marius Cramer, pixcept KG
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

class validate_password {
	
	private function _get_password_strength($password) {
		$length = strlen($password);
		
		$points = 0;
		if ($length < 5) {
			return 1;
		}

		$different = 0;
		if (preg_match('/[abcdefghijklnmopqrstuvwxyz]/', $password)) {
			$different += 1;
		}

		if (preg_match('/[ABCDEFGHIJKLNMOPQRSTUVWXYZ]/', $password)) {
			$points += 1;
			$different += 1;
		}

		if (preg_match('/[0123456789]/', $password)) {
			$points += 1;
			$different += 1;
		}

		if (preg_match('/[`~!@#$%^&*()_+|\\=\-\[\]}{\';:\/?.>,<" ]/', $password)) {
			$points += 1;
			$different += 1;
		}
		

		if ($points == 0 || $different < 3) {
			if ($length >= 5 && $length <= 6) {
				return 1;
			} else if ($length >= 7 && $length <= 8) {
				return 2;
			} else {
				return 3;
			}
		} else if ($points == 1) {
			if ($length >= 5 && $length <= 6) {
				return 2;
			} else if ($length >= 7 && $length <=10) {
				return 3;
			} else {
				return 4;
			}
		} else if ($points == 2) {
			if ($length >= 5 && $length <= 8) {
				return 3;
			} else if ($length >= 9 && $length <= 10) {
				return 4;
			} else {
				return 5;
			}
		} else if ($points == 3) {
			if ($length >= 5 && $length <= 6) {
				return 3;
			} else if ($length >= 7 && $length <= 8) {
				return 4;
			} else {
				return 5;
			}
		} else if ($points >= 4) {
			if ($length >= 5 && $length <= 6) {
				return 4;
			} else {
				return 5;
			}
		}
		
	}
	
	/* Validator function */
	function password_check($field_name, $field_value, $validator) {
		global $app;
		
		if($field_value == '') return false;
		
		$app->uses('ini_parser,getconf');
		$server_config_array = $app->getconf->get_global_config();
		
		$min_password_strength = 0;
		$min_password_length = 5;
		if(isset($server_config_array['misc']['min_password_length'])) $min_password_length = $server_config_array['misc']['min_password_length'];
		if(isset($server_config_array['misc']['min_password_strength'])) $min_password_strength = $server_config_array['misc']['min_password_strength'];
		
		if($min_password_strength > 0) {
			$lng_text = $app->lng('weak_password_txt');
			$lng_text = str_replace(array('{chars}', '{strength}'), array($min_password_length, $app->lng('strength_' . $min_password_strength)), $lng_text);
		} else {
			$lng_text = $app->lng('weak_password_length_txt');
			$lng_text = str_replace('{chars}', $min_password_length, $lng_text);
		}
		if(!$lng_text) $lng_text = 'weak_password_txt'; // always return a string, even if language is missing - otherwise validator is NOT MATCHING!

		if(strlen($field_value) < $min_password_length) return $lng_text;
		if($this->_get_password_strength($field_value) < $min_password_strength) return $lng_text;
		
		return false;
	}
}
