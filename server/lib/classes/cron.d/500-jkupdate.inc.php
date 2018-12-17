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
	protected $_schedule = '45 22 3 * *';
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
		//$jailkit_programs = explode(' ', $jailkit_conf['jailkit_chroot_app_programs']);
		$jailkit_programs = preg_split("/[\s,]+/", $jailkit_conf['jailkit_chroot_app_programs']);
		$jailkit_sections = trim($jailkit_conf['jailkit_chroot_app_sections']);

		$sites = $app->db->queryAllRecords("SELECT domain_id, document_root, fastcgi_php_version FROM web_domain WHERE jailkit_jkupdate_cron = 'y' AND type = 'vhost' AND parent_domain_id = 0 AND document_root != '' ORDER BY domain_id");

		foreach($sites as $site) {
			$set_php_symlink = false;
			
			$users = $app->db->queryOneRecord("SELECT COUNT(*) AS user_count FROM shell_user WHERE parent_domain_id = ? AND active='y' AND chroot='jailkit'", intval($site['domain_id']));
			$crons = $app->db->queryOneRecord("SELECT COUNT(*) AS cron_count FROM cron WHERE parent_domain_id = ? AND active='y' AND type='chrooted'", $site['domain_id']);
			if ($users['user_count'] > 0 || $crons['cron_count'] > 0) {
				
				if (!is_dir($site['document_root'])) {
					return;
				}
				
				//* Protect web folders
				$app->system->web_folder_protection($site['document_root'], false);
				
				$app->log('Running jailkit init for '.$site['document_root']);
				if($jailkit_sections != '') $this->run_jk_init($site['document_root'], $jailkit_sections);

				$app->log('Running jailkit updates for '.$site['document_root']);

				$this->run_jk_update($site['document_root']);
				if(preg_match('@(\d\d?\.\d\d?\.\d\d?)@', $site['fastcgi_php_version'], $matches)){
					if(!in_array('/opt/php-'.$matches[1].'/bin/php', $jailkit_programs)) $jailkit_programs[] = '/opt/php-'.$matches[1].'/bin/php';
					if(!in_array('/opt/php-'.$matches[1].'/include', $jailkit_programs)) $jailkit_programs[] = '/opt/php-'.$matches[1].'/include';
					if(!in_array('/opt/php-'.$matches[1].'/lib', $jailkit_programs)) $jailkit_programs[] = '/opt/php-'.$matches[1].'/lib';
					if(!in_array('/opt/th-php-libs', $jailkit_programs)) $jailkit_programs[] = '/opt/th-php-libs';
					
					$set_php_symlink = true;
					
				}
				if(is_array($jailkit_programs) && !empty($jailkit_programs)) $this->run_jk_cp($site['document_root'], $jailkit_programs);
				$this->fix_broken_symlinks($site['document_root']);
				
				if($set_php_symlink){
					// create symlink from /usr/bin/php to current PHP version
					if(preg_match('@(\d\d?\.\d\d?\.\d\d?)@', $site['fastcgi_php_version'], $matches) && (!file_exists($site['document_root'].'/usr/bin/php') || is_link($site['document_root'].'/usr/bin/php'))){
						@unlink($site['document_root'].'/usr/bin/php');
						@symlink('/opt/php-'.$matches[1].'/bin/php', $site['document_root'].'/usr/bin/php');
					}
				}
				
				//* Protect web folders
				$app->system->web_folder_protection($site['document_root'], true);
			}
		}
		
		if(file_exists('/dev/tty')){
			chmod('/dev/tty', 0666);
		}

		parent::onRunJob();
	}
	
	private function run_jk_init($document_root, $sections){
		global $app;
		
		$return_var = $this->exec_log('/usr/sbin/jk_init -f -k -c /etc/jailkit/jk_init.ini -j '.escapeshellarg($document_root).' '.$sections);
		
		if ($return_var > 0) {
			$app->log('jk_init failed with -j, trying again without -j', LOGLEVEL_DEBUG);
			
			$return_var = $this->exec_log('/usr/sbin/jk_init -f -k -c /etc/jailkit/jk_init.ini '.escapeshellarg($document_root).' '.$sections);
			
			if ($return_var > 0) {
				$app->log('jk_init failed (with and without -j parameter)', LOGLEVEL_WARN);
			}
		}
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
			$program = trim($program);
			if($program == ''){
				continue;
			}
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
		
		if(file_exists($document_root.'/dev/tty')){
			chmod($document_root.'/dev/tty', 0666);
		}
	}
	
	private function fix_broken_symlinks($document_root){
		global $app;
		
		exec('cd '.escapeshellarg($document_root).' && find . -type l \( ! -name web \) -xtype l', $output, $retval);

		if(is_array($output) && !empty($output)){
			foreach($output as $link){
				$link = trim($link);
				if(preg_match('@\.so(\.\d+)*$@',$link)){
					if(substr($link, 0, 1) == '.') $link = substr($link, 1);
					//echo $link."\n";
					$path = $document_root.$link;
					//if(is_link($path)) echo "Ist Link\n";
					//if(!file_exists($path)) echo "Aber Link ist kaputt!\n";
					if(is_link($path) && !file_exists($path)){
						//echo $path."\n";
						@unlink($path);
						$this->run_jk_cp($document_root, array($link));
					}
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
