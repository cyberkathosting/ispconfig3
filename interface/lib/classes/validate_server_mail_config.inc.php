<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
Copyright (c) 2012, Marius Cramer, pixcept KG
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

class validate_server_mail_config {

	function get_error($errmsg) {
		global $app;

		if(isset($app->tform->wordbook[$errmsg])) {
			return $app->tform->wordbook[$errmsg]."<br>\r\n";
		} else {
			return $errmsg."<br>\r\n";
		}
	}

	/* Validator function to check for changing virtual_uidgid_maps */
	function mailbox_virtual_uidgid_maps($field_name, $field_value, $validator) {
		global $app, $conf;

		if (empty($field_value)) $field_value = 'n';
		$app->uses('getconf,system,db');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		
		// try to activat the function -> only if only one mailserver out there and if dovecot is installed
		if ($field_value == 'y') {
			// if this count is more then 1, there is more than 1 webserver, more than 1 mailserver or different web+mailserver -> so this feature isn't possible
			$num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM server WHERE mail_server=1 OR web_server=1");
			if($num_rec['number'] > 1) {
				return $this->get_error('mailbox_virtual_uidgid_maps_error_nosingleserver');
			}
		}
		
		// Value can only be changed if there is no mailuser set
		if ($mail_config["mailbox_virtual_uidgid_maps"] != $field_value) {
			$num_rec = $app->db->queryOneRecord("SELECT count(*) as number FROM mail_user");
			if($num_rec['number'] > 0) {
				return $this->get_error('mailbox_virtual_uidgid_maps_error_alreadyusers');
			}
		}
	}

}
