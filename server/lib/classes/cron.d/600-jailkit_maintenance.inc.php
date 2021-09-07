<?php

/*
Copyright (c) 2020, Jesse Norell <jesse@kci.net>
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

class cronjob_jailkit_maintenance extends cronjob {

	// job schedule
	protected $_schedule = '*/5 * * * *';
	protected $_run_at_new = true;

	public function onRunJob() {
		global $app, $conf;

		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');

		$jailkit_config = $app->getconf->get_server_config($conf['server_id'], 'jailkit');
		if (isset($this->jailkit_config) && isset($this->jailkit_config['jailkit_hardlinks'])) {
			if ($this->jailkit_config['jailkit_hardlinks'] == 'yes') {
				$options = array('hardlink');
			} elseif ($this->jailkit_config['jailkit_hardlinks'] == 'no') {
				$options = array();
			}
		} else {
			$options = array('allow_hardlink');
		}

		// force all jails to update every 2 weeks
		if (! is_file('/usr/local/ispconfig/server/temp/jailkit_force_update.ts')) {
			if(!@is_dir('/usr/local/ispconfig/server/temp')) {
				$app->system->mkdirpath('/usr/local/ispconfig/server/temp');
			}
			$app->system->touch('/usr/local/ispconfig/server/temp/jailkit_force_update.ts');
		} elseif ( time() - filemtime('/usr/local/ispconfig/server/temp/jailkit_force_update.ts') > 60 * 60 * 24 * 14 ) {
			$update_hash = 'force_update'.time();
			$app->db->query("UPDATE web_domain SET last_jailkit_hash = ? WHERE type = 'vhost' AND server_id = ?", $update_hash, $conf['server_id']);
			$app->system->touch('/usr/local/ispconfig/server/temp/jailkit_force_update.ts');
		}

		// limit the number of jails we update at one time according to time of day
		$num_jails_to_update = (date('H') < 6) ? 25 : 3;

		$sql = "SELECT domain_id, domain, document_root, system_user, system_group, php_fpm_chroot, jailkit_chroot_app_sections, jailkit_chroot_app_programs, delete_unused_jailkit, last_jailkit_hash FROM web_domain WHERE type = 'vhost' AND (last_jailkit_update IS NULL OR last_jailkit_update < (NOW() - INTERVAL 24 HOUR)) AND server_id = ? ORDER by last_jailkit_update LIMIT ?";
		$records = $app->db->queryAllRecords($sql, $conf['server_id'], $num_jails_to_update);

		foreach($records as $rec) {
			if (!is_dir($rec['document_root']) || !is_dir($rec['document_root'].'/etc/jailkit')) {
				$app->db->query("UPDATE `web_domain` SET `last_jailkit_update` = NOW() WHERE `document_root` = ?", $rec['document_root']);
				continue;
			}

			//$app->log('Beginning jailkit maintenance for domain '.$rec['domain'].' at '.$rec['document_root'], LOGLEVEL_DEBUG);
			print 'Beginning jailkit maintenance for domain '.$rec['domain'].' at '.$rec['document_root']."\n";

			// check for any shell_user using this jail
			$shell_user_inuse = $app->db->queryOneRecord('SELECT shell_user_id FROM `shell_user` WHERE `parent_domain_id` = ? AND `chroot` = ? AND `server_id` = ?', $rec['domain_id'], 'jailkit', $conf['server_id']);

			// check for any cron job using this jail
			$cron_inuse = $app->db->queryOneRecord('SELECT id FROM `cron` WHERE `parent_domain_id` = ? AND `type` = ? AND `server_id` = ?', $rec['domain_id'], 'chrooted', $conf['server_id']);

			$records2 = $app->db->queryAllRecords('SELECT web_folder FROM `web_domain` WHERE `parent_domain_id` = ? AND `document_root` = ? AND web_folder != \'\' AND web_folder IS NOT NULL AND `server_id` = ?', $rec['domain_id'], $rec['document_root'], $conf['server_id']);
			foreach ($records2 as $record2) {
				if ($record2['web_folder'] == NULL || $record2['web_folder'] == '') {
					continue;
				}
				$options[] = 'skip='.$record2['web_folder'];
			}

			if ($shell_user_inuse || $cron_inuse || $rec['php_fpm_chroot'] == 'y' || $rec['delete_unused_jailkit'] != 'y') {
				$sections = $jailkit_config['jailkit_chroot_app_sections'];
				if (isset($rec['jailkit_chroot_app_sections']) && $rec['jailkit_chroot_app_sections'] != '') {
					$sections = $rec['jailkit_chroot_app_sections'];
				}
				$programs = $jailkit_config['jailkit_chroot_app_programs'];
				if (isset($rec['jailkit_chroot_app_programs']) && $rec['jailkit_chroot_app_programs'] != '') {
					$programs = $rec['jailkit_chroot_app_programs'];
				}
				$programs .= ' '.$jailkit_config['jailkit_chroot_cron_programs'];

				$last_updated = preg_split('/[\s,]+/', $sections.' '.$programs);
				$last_updated = array_unique($last_updated, SORT_REGULAR);
				sort($last_updated, SORT_STRING);
				$update_hash = hash('md5', implode(' ', $last_updated));

				if (substr($rec['last_jailkit_hash'], 0, strlen('force_update')) === 'force_update') {
					$options[] = 'force';
				} elseif (is_file( $rec['document_root']."/bin/bash" )) {
					# test that /bin/bash functions in the jail
					$app->system->exec_safe("chroot --userspec ?:? ? /bin/bash -c true 2>/dev/null", $rec['system_user'], $rec['system_group'], $rec['document_root']);
					if ($app->system->last_exec_retcode()) {  # return 0 means success
						$options[] = 'force';
						# bogus hash will not match, triggering an update
						$update_hash = 'force_update'.time();
					}
				}

				if ($update_hash != $rec['last_jailkit_hash']) {
					$app->system->web_folder_protection($rec['document_root'], false);
					$app->system->update_jailkit_chroot($rec['document_root'], $sections, $programs, $options);
					$app->system->web_folder_protection($rec['document_root'], true);
					$app->db->query("UPDATE `web_domain` SET `last_jailkit_update` = NOW(), `last_jailkit_hash` = ? WHERE `document_root` = ?", $update_hash, $rec['document_root']);
				} else {
					$app->db->query("UPDATE `web_domain` SET `last_jailkit_update` = NOW() WHERE `document_root` = ?", $rec['document_root']);
				}
			} elseif ($rec['delete_unused_jailkit'] == 'y') {
				//$app->log('Removing unused jail: '.$rec['document_root'], LOGLEVEL_DEBUG);
				print 'Removing unused jail: '.$rec['document_root']."\n";
				$app->system->web_folder_protection($rec['document_root'], false);
				$app->system->delete_jailkit_chroot($rec['document_root'], $options);
				$app->system->web_folder_protection($rec['document_root'], true);

				$app->db->query("UPDATE `web_domain` SET `last_jailkit_update` = NOW(), `last_jailkit_hash` = NULL WHERE `document_root` = ?", $rec['document_root']);
			} else {
				$app->db->query("UPDATE `web_domain` SET `last_jailkit_update` = NOW() WHERE `document_root` = ?", $rec['document_root']);
			}
		}

		parent::onRunJob();
	}

}

