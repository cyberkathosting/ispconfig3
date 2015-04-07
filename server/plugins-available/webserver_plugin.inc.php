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
			'php_version' => ''); // default;

		$check_files[] = array('file' => $web_config['php_ini_path_cgi'],
			'mode' => '', // all but 'mod' and 'fast-cgi'
			'php_version' => ''); // default;

		if($fastcgi_config["fastcgi_phpini_path"] && $fastcgi_config["fastcgi_phpini_path"] != $web_config['php_ini_path_cgi']) {
			$check_files[] = array('file' => $fastcgi_config["fastcgi_phpini_path"],
				'mode' => 'fast-cgi',
				'php_version' => ''); // default;
		} else {
			$check_files[] = array('file' => $web_config['php_ini_path_cgi'],
				'mode' => 'fast-cgi', // all but 'mod'
				'php_version' => ''); // default;
		}


		//** read additional php versions of this server
		$php_versions = $app->db->queryAllRecords('SELECT server_php_id, php_fastcgi_ini_dir, php_fpm_ini_dir FROM server_php WHERE server_id = ?', $conf['server_id']);
		foreach($php_versions as $php) {
			if($php['php_fastcgi_ini_dir'] && $php['php_fastcgi_ini_dir'] . '/php.ini' != $web_config['php_ini_path_cgi']) {
				$check_files[] = array('file' => $php['php_fastcgi_ini_dir'] . '/php.ini',
					'mode' => 'fast-cgi',
					'php_version' => $php['php_fastcgi_ini_dir']);
			} elseif($php['php_fpm_ini_dir'] && $php['php_fpm_ini_dir'] . '/php.ini' != $web_config['php_ini_path_cgi']) {
				$check_files[] = array('file' => $php['php_fpm_ini_dir'] . '/php.ini',
					'mode' => 'php-fpm',
					'php_version' => $php['php_fpm_ini_dir']);
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

}

?>
