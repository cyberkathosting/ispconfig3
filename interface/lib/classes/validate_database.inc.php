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

class validate_database {

	/*
		Validator function to check if a given list of ips is ok.
	*/
	function valid_ip_list($field_name, $field_value, $validator) {
		global $app;

		if($_POST["remote_access"] == "y") {
			if(trim($field_value) == "") return;

			$values = explode(",", $field_value);
			foreach($values as $cur_value) {
				$cur_value = trim($cur_value);
				$valid = true;
				if(function_exists('filter_var')) {
					if(!filter_var($cur_value, FILTER_VALIDATE_IP)) {
						$valid = false;
					}
				} else return "function filter_var missing <br />\r\n";

				if($valid == false) {
					$errmsg = $validator['errmsg'];
					if(isset($app->tform->wordbook[$errmsg])) {
						return $app->tform->wordbook[$errmsg]."<br>\r\n";
					} else {
						return $errmsg."<br>\r\n";
					}
				}
			}
		}
	}




}
