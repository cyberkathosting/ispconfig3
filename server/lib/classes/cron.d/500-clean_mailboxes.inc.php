<?php

/*
Copyright (c) 2020, Florian Schaal, schaal @it UG
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

class cronjob_clean_mailboxes extends cronjob {

	// should run before quota notify and backup
	// quota notify and backup is both '0 0 * * *' 
	
	// job schedule
	protected $_schedule = '00 22 * * *';

	/* this function is optional if it contains no custom code */
	public function onPrepare() {
		global $app;

		parent::onPrepare();
	}

	/* this function is optional if it contains no custom code */
	public function onBeforeRun() {
		global $app;

		return parent::onBeforeRun();
	}

	public function onRunJob() {
		global $app, $conf;

		$trash_names=array('Trash', 'Papierkorb', 'Deleted Items', 'Deleted Messages', 'INBOX.Trash', 'INBOX.Papierkorb', 'INBOX.Deleted Messages');
		$junk_names=array('Junk', 'Junk Email', 'SPAM', 'INBOX.SPAM');

		$purge_cmd = 'doveadm expunge -u ? mailbox ? sentbefore ';

		$server_id = intval($conf['server_id']);
		$records = $app->db->queryAllRecords("SELECT email, maildir, purge_trash_days, purge_junk_days FROM mail_user WHERE maildir_format = 'maildir' AND disableimap = 'n' AND server_id = ? AND (purge_trash_days > 0 OR purge_junk_days > 0)", $server_id);
		
		if(is_array($records) && !empty($records)) {
			foreach($records as $email) {
				if($email['purge_trash_days'] > 0) {
					foreach($trash_names as $trash) {
						if(is_dir($email['maildir'].'/Maildir/.'.$trash)) {
							$app->system->exec_safe($purge_cmd.intval($email['purge_trash_days']).'d', $email['email'], $trash);
						}
					}
				}
				if($email['purge_junk_days'] > 0) {
					foreach($junk_names as $junk) {
						if(is_dir($email['maildir'].'/Maildir/.'.$junk)) {
							$app->system->exec_safe($purge_cmd.intval($email['purge_junk_days']).'d', $email['email'], $junk);
						}
					}
				}
			}
		}

		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
