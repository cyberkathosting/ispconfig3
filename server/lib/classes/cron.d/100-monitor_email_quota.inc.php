<?php

/*
Copyright (c) 2013, Marius Cramer, pixcept KG
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

class cronjob_monitor_email_quota extends cronjob {

	// job schedule
	protected $_schedule = '*/15 * * * *';
	protected $_run_at_new = true;

	private $_tools = null;

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

		$app->uses('getconf');
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		if($mail_config['mailbox_quota_stats'] == 'n') return;

		/* used for all monitor cronjobs */
		$app->load('monitor_tools');
		$this->_tools = new monitor_tools();
		/* end global section for monitor cronjobs */


		//* Initialize data array
		$data = array();

		//* the id of the server as int
		$server_id = intval($conf['server_id']);

		//* The type of the data
		$type = 'email_quota';

		//* The state of the email_quota.
		$state = 'ok';

		$mailboxes = $app->db->queryAllRecords("SELECT email,maildir FROM mail_user WHERE server_id = ?", $server_id);
		if(is_array($mailboxes)) {

			//* with dovecot we can use doveadm instead of 'du -s'
			$dovecot = false;
			if (isset($mail_config['pop3_imap_daemon']) && $mail_config ['pop3_imap_daemon'] = 'dovecot' && is_executable('doveadm')) {
				exec('doveadm quota 2>&1', $tmp_output, $tmp_retval); // with dovecot 2.2.x 'doveadm quota' is unuseable
				if ($retval = 64) $dovecot = true;
			}

			foreach($mailboxes as $mb) {
				$email = $mb['email'];
				$email_parts = explode('@', $mb['email']);
				$filename = $mb['maildir'].'/.quotausage';
				if(!file_exists($filename) && $dovecot) {
					$app->system->exec_safe('doveadm quota recalc -u ?', $email);
				}
				if(file_exists($filename) && !is_link($filename)) {
					$quotafile = file($filename);
					preg_match('/storage.*?([0-9]+)/s', implode('',$quotafile), $storage_value);
					$data[$email]['used'] = $storage_value[1];
					$app->log("Mail storage $email: " . $storage_value[1], LOGLEVEL_DEBUG);
					unset($quotafile);
				} else {
					$app->system->exec_safe('du -s ?', $mb['maildir']);
					$out = $app->system->last_exec_out();
					$parts = explode(' ', $out[0]);
					$data[$email]['used'] = intval($parts[0])*1024;
					unset($out);
					unset($parts);
				}
			}
		}

		unset($mailboxes);

		//* Dovecot quota check Courier in progress lathama@gmail.com
		/*
				if($dir = opendir("/var/vmail")){
						while (($quotafiles = readdir($dir)) !== false){
								if(preg_match('/.\_quota$/', $quotafiles)){
										$quotafile = (file("/var/vmail/" . $quotafiles));
										$emailaddress = preg_replace('/_quota/',"", $quotafiles);
										$emailaddress = preg_replace('/_/',"@", $emailaddress);
										$data[$emailaddress]['used'] = trim($quotafile['1']);
								}
						}
						closedir($dir);
				}
		*/
		$res = array();
		$res['server_id'] = $server_id;
		$res['type'] = $type;
		$res['data'] = $data;
		$res['state'] = $state;

		/*
         * Insert the data into the database
         */
		$sql = 'REPLACE INTO monitor_data (server_id, type, created, data, state) ' .
			'VALUES (?, ?, UNIX_TIMESTAMP(), ?, ?)';
		$app->dbmaster->query($sql, $res['server_id'], $res['type'], serialize($res['data']), $res['state']);

		/* The new data is written, now we can delete the old one */
		$this->_tools->delOldRecords($res['type'], $res['server_id']);


		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
