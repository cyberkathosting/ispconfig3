<?php

/*
  Copyright (c) 2007-2011, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

class webserver_plugin {

	var $plugin_name = 'webserver_plugin';
	var $class_name = 'webserver_plugin';

	/**
	 * This function is called during ispconfig installation to determine
	 * if a symlink shall be created for this plugin.
	 */


	public function onInstall() {
		global $conf;

		if($conf['services']['web'] == true) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * This function is called when the module is loaded
	 */
	public function onLoad() {
		global $app;

		$app->plugins->registerAction('server_plugins_loaded', $this->plugin_name, 'check_phpini_changes');
		$app->plugins->registerEvent('server_update', $this->plugin_name, 'server_update');
	}

	/**
	 * This function is called when a change in one of the registered tables is detected.
	 * The function then raises the events for the plugins.
	 */
	public function process($tablename, $action, $data) {
		// not needed
	}

	/**
	 * The method checks for a change of a php.ini file
	 */
	public function check_phpini_changes() {
		global $app, $conf;

		//** check if the main php.ini of the system changed so we need to regenerate all custom php.inis
		$app->uses('getconf');

		//** files to check
		$check_files = array();

		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		$fastcgi_config = $app->getconf->get_server_config($conf['server_id'], 'fastcgi');

		if($web_config['php_ini_check_minutes'] == 0 || @date('i') % $web_config['php_ini_check_minutes'] != 0) {
			$app->log('Info: php.ini change checking not enabled or not in this minute: ' . $web_config['php_ini_check_minutes'], LOGLEVEL_DEBUG);
			return; // do not process
		}

		//** add default php.ini files to check
		$check_files[] = array('file' => $web_config['php_ini_path_apache'],
			'mode' => 'mod',
			'php_version' => 0); // default;

		$check_files[] = array('file' => $web_config['php_ini_path_cgi'],
			'mode' => '', // all but 'mod' and 'fast-cgi'
			'php_version' => 0); // default;

		if($fastcgi_config["fastcgi_phpini_path"] && $fastcgi_config["fastcgi_phpini_path"] != $web_config['php_ini_path_cgi']) {
			$check_files[] = array('file' => $fastcgi_config["fastcgi_phpini_path"],
				'mode' => 'fast-cgi',
				'php_version' => 0); // default;
		} else {
			$check_files[] = array('file' => $web_config['php_ini_path_cgi'],
				'mode' => 'fast-cgi', // all but 'mod'
				'php_version' => 0); // default;
		}


		//** read additional php versions of this server
		$php_versions = $app->db->queryAllRecords('SELECT server_php_id, php_fastcgi_ini_dir, php_fpm_ini_dir FROM server_php WHERE server_id = ?', $conf['server_id']);
		foreach($php_versions as $php) {
			if($php['php_fastcgi_ini_dir'] && $php['php_fastcgi_ini_dir'] . '/php.ini' != $web_config['php_ini_path_cgi']) {
				$check_files[] = array('file' => $php['php_fastcgi_ini_dir'] . '/php.ini',
					'mode' => 'fast-cgi',
					'php_version' => $php['server_php_id']);
			} elseif($php['php_fpm_ini_dir'] && $php['php_fpm_ini_dir'] . '/php.ini' != $web_config['php_ini_path_cgi']) {
				$check_files[] = array('file' => $php['php_fpm_ini_dir'] . '/php.ini',
					'mode' => 'php-fpm',
					'php_version' => $php['server_php_id']);
			}
		}
		unset($php_versions);

		//** read md5sum status file
		$new_php_ini_md5 = array();
		$php_ini_md5 = array();
		$php_ini_changed = false;
		$rewrite_ini_files = false;

		if(file_exists(SCRIPT_PATH . '/temp/php.ini.md5sum')) {
			$rewrite_ini_files = true;
			$php_ini_md5 = unserialize(base64_decode(trim($app->system->file_get_contents(SCRIPT_PATH . '/temp/php.ini.md5sum'))));
		}
		if(!is_array($php_ini_md5)) $php_ini_md5 = array();

		$processed = array();
		foreach($check_files as $file) {
			$file_path = $file['file'];
			if(substr($file_path, -8) !== '/php.ini') $file_path .= (substr($file_path, -1) !== '/' ? '/' : '') . 'php.ini';
			if(!file_exists($file_path)) continue;

			//** check if this php.ini file was already processed (if additional php version uses same php.ini)
			$ident = $file_path . '::' . $file['mode'] . '::' . $file['php_version'];
			if(in_array($ident, $processed) == true) continue;
			$processed[] = $ident;

			//** check if md5sum of file changed
			$file_md5 = md5_file($file_path);
			if(array_key_exists($file_path, $php_ini_md5) == false || $php_ini_md5[$file_path] != $file_md5) {
				$php_ini_changed = true;

				$app->log('Info: PHP.ini changed: ' . $file_path . ', mode ' . $file['mode'] . ' vers ' . $file['php_version'] . '.', LOGLEVEL_DEBUG);
				// raise action for this file
				if($rewrite_ini_files == true) $app->plugins->raiseAction('php_ini_changed', $file);
			}

			$new_php_ini_md5[$file_path] = $file_md5;
		}

		//** write new md5 sums if something changed
		if($php_ini_changed == true) $app->system->file_put_contents(SCRIPT_PATH . '/temp/php.ini.md5sum', base64_encode(serialize($new_php_ini_md5)));
		unset($new_php_ini_md5);
		unset($php_ini_md5);
		unset($processed);
	}


	/*
	 * Checks for changes to jailkit settings in server config and schedules affected jails to be updated.
	 */
	function server_update($event_name, $data) {
		global $app, $conf;

		// load the server configuration options
		$app->uses('ini_parser,system');

		$old = $app->ini_parser->parse_ini_string($data['old']['config']);
		$new = $app->ini_parser->parse_ini_string($data['new']['config']);
		if (is_array($old) && is_array($new) && isset($old['jailkit']) && isset($new['jailkit'])) {
			$old = $old['jailkit'];
			$new = $new['jailkit'];
		} else {
			$app->log('server_update: could not parse jailkit section of server config.', LOGLEVEL_WARN);
			return;
		}

		$hardlink_mode_changed = (boolean)(($old['jailkit_hardlinks'] != $new['jailkit_hardlinks']) && $new['jailkit_hardlinks'] != 'allow');

		if (($old['jailkit_chroot_app_sections'] != $new['jailkit_chroot_app_sections']) ||
		    ($old['jailkit_chroot_app_programs'] != $new['jailkit_chroot_app_programs']) ||
		    ($old['jailkit_chroot_cron_programs'] != $new['jailkit_chroot_cron_programs']) ||
		    ($hardlink_mode_changed && $new['jailkit_hardlinks'] != 'allow'))
		{
			$app->log('Jailkit config has changed, scheduling affected chroot jails to be updated.', LOGLEVEL_DEBUG);

			$web_domains = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE type = 'vhost' AND server_id = ?", $conf['server_id']);

			foreach ($web_domains as $web) {
				// we could check (php_fpm_chroot == y || jailkit shell user exists || jailkit cron exists),
				// but will just shortcut the db checks to see if jailkit was setup previously:
				if (!is_dir($web['document_root'].'/etc/jailkit')) {
					continue;
				}

				if ($hardlink_mode_changed ||
				    // chroot cron programs changed
				    ($old['jailkit_chroot_cron_programs'] != $new['jailkit_chroot_cron_programs']) ||
				    // jailkit sections changed and website does not overwrite
				    (($old['jailkit_chroot_app_sections'] != $new['jailkit_chroot_app_sections']) &&
				     (!(isset($web['jailkit_chroot_app_sections']) && $web['jailkit_chroot_app_sections'] != '' ))) ||
				    // jailkit apps changed and website does not overwrite
				    (($old['jailkit_chroot_app_programs'] != $new['jailkit_chroot_app_programs']) &&
				     (!(isset($web['jailkit_chroot_app_programs']) && $web['jailkit_chroot_app_programs'] != '' ))))
				{

					$sections = $new['jailkit_chroot_app_sections'];
					if (isset($web['jailkit_chroot_app_sections']) && $web['jailkit_chroot_app_sections'] != '' ) {
						$sections = $web['jailkit_chroot_app_sections'];
					}

					$programs = $new['jailkit_chroot_app_programs'];
					if (isset($web['jailkit_chroot_app_sections']) && $web['jailkit_chroot_app_sections'] != '' ) {
						$programs = $web['jailkit_chroot_app_sections'];
					}

					if (isset($new['jailkit_hardlinks'])) {
						if ($new['jailkit_hardlinks'] == 'yes') {
							$options = array('hardlink');
						} elseif ($new['jailkit_hardlinks'] == 'no') {
							$options = array();
						}
					} else {
						$options = array('allow_hardlink');
					}

					$options[] = 'force';

					// we could add a server config setting to allow updating these immediately:
					//   $app->system->update_jailkit_chroot($new['document_root'], $sections, $programs, $options);
					//
					// but to mitigate disk contention, will just queue "update needed"
					// for jailkit maintenance cronjob via last_jailkit_update timestamp
					$app->db->query("UPDATE `web_domain` SET `last_jailkit_update` = FROM_UNIXTIME(0) WHERE `document_root` = ?", $web['document_root']);
				}
			}
		}
	}
}

?>
