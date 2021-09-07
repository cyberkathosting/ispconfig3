<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
Copyright (c) 2021, Jesse Norell <jesse@kci.net>
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

class validate_mail_relay_domain {

	function get_error($errmsg) {
		global $app;

		if(isset($app->tform->wordbook[$errmsg])) {
			return $app->tform->wordbook[$errmsg]."<br>\r\n";
		} else {
			return $errmsg."<br>\r\n";
		}
	}

	/* Validator function for checking the 'domain' of a mail relay domain */
	function validate_domain($field_name, $field_value, $validator) {
		global $app, $conf;

		if(isset($app->remoting_lib->primary_id)) {
			$id = $app->remoting_lib->primary_id;
		} else {
			$id = $app->tform->primary_id;
		}

		// mail_relay_domain.domain must be unique per server
		$sql = "SELECT relay_domain_id, domain FROM mail_relay_domain WHERE domain = ? AND server_id = ? AND relay_domain_id != ?";
		$domain_check = $app->db->queryOneRecord($sql, $field_value, $app->tform_actions->dataRecord['server_id'], $id);

		if($domain_check) return $this->get_error('domain_error_unique');
	}

}
