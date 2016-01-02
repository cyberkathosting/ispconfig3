<?php

/**
 Copyright (c) 2015, Florian Schaal, schaal @it
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

class validate_openvz {

	function get_error($errmsg, $additional='') {
		global $app;
		if(isset($app->tform->wordbook[$errmsg])) {
			return $app->tform->wordbook[$errmsg].$additional."<br>\r\n";
		} else {
			return $errmsg."<br>".$additional."<br>\r\n";
		}
	}

	function check_custom($field_name, $field_value, $validator) {
		$template = file('../vm/templates/openvz.conf.tpl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		$custom_array = explode("\n", $field_value);
		$used_parameters = array();
		foreach ($template as $line) {
			$line = trim ($line);
			if (preg_match('/^[^#].*=\".*\"/', $line)) {
				$line = explode('=', $line, 2);
				$used_parameters[] = $line[0];
			}
		}
		foreach ($custom_array as $check) {
			$check = trim(strtoupper($check));
			$check = explode('=', trim($check), 2);
			$check = trim($check[0]);
			if (in_array($check, $used_parameters)) {
				return $this->get_error($validator['errmsg'], $check);
			}
		}
	}

}
