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

class cronjob_jkupdate extends cronjob {

	// job schedule
	protected $_schedule = '45 22 * * *';
	protected $_run_at_new = true;

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
		$jailkit_conf = $app->getconf->get_server_config($conf['server_id'], 'jailkit');
		$jailkit_programs = explode(' ', $jailkit_conf['jailkit_chroot_app_programs']);

		$sites = $app->db->queryAllRecords('SELECT domain_id, document_root FROM web_domain WHERE jailkit_jkupdate_cron = \'y\'');

		foreach($sites as $site) {
			$users = $app->db->queryOneRecord('SELECT COUNT(*) AS user_count FROM shell_user WHERE parent_domain_id = ? AND active=\'y\' AND chroot=\'jailkit\'', $site['domain_id']);
			$crons = $app->db->queryOneRecord('SELECT COUNT(*) AS cron_count FROM cron WHERE parent_domain_id = ? AND active=\'y\' AND type=\'chrooted\'', $site['domain_id']);
			if ($users['user_count'] > 0 || $crons['cron_count'] > 0) {
				if (!is_dir($site['document_root'])) {
					return;
				}

				$app->log('Running jailkit updates for '.$site['document_root']);

				$this->run_jk_update($site['document_root']);
				$this->run_jk_cp($site['document_root'], $jailkit_programs);
			}
		}

		parent::onRunJob();
	}

	private function run_jk_update($document_root) {
		global $app;

		$return_var = $this->exec_log('/usr/sbin/jk_update -j '.escapeshellarg($document_root));

		if ($return_var > 0) {
			$app->log('jk_update failed with -j, trying again without -j', LOGLEVEL_DEBUG);
			$return_var = $this->exec_log('/usr/sbin/jk_update '.escapeshellarg($document_root));

			if ($return_var > 0) {
				$app->log('jk_update failed (with and without -j parameter)', LOGLEVEL_WARN);
			}
		}
	}

	private function run_jk_cp($document_root, $programs) {
		global $app;

		foreach($programs as $program) {
			if (!file_exists($program)) {
				continue;
			}

			$return_var = $this->exec_log('/usr/sbin/jk_cp '.escapeshellarg($document_root).' '.escapeshellarg($program));

			if ($return_var > 0) {
				$app->log('jk_cp failed with -j, trying again with -j', LOGLEVEL_DEBUG);
				$return_var = $this->exec_log('/usr/sbin/jk_cp '.escapeshellarg($document_root).' '.escapeshellarg($program));

				if ($return_var > 0) {
					$app->log('jk_cp failed (without and with -j parameter)', LOGLEVEL_WARN);
				}
			}
		}
	}

	private function exec_log($cmd) {
		global $app;

		$app->log("Running $cmd", LOGLEVEL_DEBUG);

		exec($cmd, $output, $return_var);

		if (count($output) > 0) {
			$app->log("Output:\n" . implode("\n", $output), LOGLEVEL_DEBUG);
		}

		return $return_var;
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
