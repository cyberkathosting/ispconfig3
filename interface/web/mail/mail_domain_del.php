<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/mail_domain.list.php";
$tform_def_file = "form/mail_domain.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('mail');

// Loading classes
$app->uses('tpl,tform,tform_actions');
$app->load('tform_actions');

class page_action extends tform_actions {

	function onBeforeDelete() {
		global $app; $conf;

		$domain = $this->dataRecord['domain'];

		// Before we delete the email domain,
		// we will delete all depending records.

		// Delete all forwardings where the source or destination belongs to this domain
		$records = $app->db->queryAllRecords("SELECT forwarding_id as id FROM mail_forwarding WHERE source like ? OR (destination like ? AND type != 'forward')", '%@' . $domain, '%@' . $domain);
		foreach($records as $rec) {
			$app->db->datalogDelete('mail_forwarding', 'forwarding_id', $rec['id']);
		}

		// Delete all fetchmail accounts where destination belongs to this domain
		$records = $app->db->queryAllRecords("SELECT mailget_id as id FROM mail_get WHERE destination like ?", '%@' . $domain);
		foreach($records as $rec) {
			$app->db->datalogDelete('mail_get', 'mailget_id', $rec['id']);
		}

		// Delete all mailboxes where destination belongs to this domain
		$records = $app->db->queryAllRecords("SELECT mailuser_id as id FROM mail_user WHERE email like ?", '%@' . $domain);
		foreach($records as $rec) {
			$app->db->datalogDelete('mail_user', 'mailuser_id', $rec['id']);
		}

		// Delete all spamfilters that belong to this domain
		$records = $app->db->queryAllRecords("SELECT id FROM spamfilter_users WHERE email like ?", '%@' . $domain);
		foreach($records as $rec) {
			$app->db->datalogDelete('spamfilter_users', 'id', $rec['id']);
		}

		// Delete all mailinglists that belong to this domain
		$records = $app->db->queryAllRecords("SELECT mailinglist_id FROM mail_mailinglist WHERE domain = ?", $domain);
		foreach($records as $rec) {
			$app->db->datalogDelete('mail_mailinglist', 'mailinglist_id', $rec['id']);
		}

	}

}

$page = new page_action;
$page->onDelete();

?>
