<?php

/*
Copyright (c) 2007 - 2012, Till Brehm, projektfarm Gmbh
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

class apache2_plugin {

	var $plugin_name;
	var $class_name;

	// private variables
	var $action = '';
	var $ssl_certificate_changed = false;
	var $update_letsencrypt = false;

	public function __construct() {
		$this->plugin_name = get_class($this);
		$this->class_name = get_class($this);
	}

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['web'] == true) {
			return true;
		} else {
			return false;
		}

	}


	/*
	 	This function is called when the plugin is loaded
	*/

	function onLoad() {
		global $app;

		/*
		Register for the events
		*/
		$app->plugin_webserver_base->registerEvents('apache');
	}

	// Handle php.ini changes
	function php_ini_changed($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventPhpIniChanged($event_name, $data, 'apache');

	}

	// Handle the creation of SSL certificates
	function ssl($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventSsl($event_name, $data, 'apache');
	}


	function insert($event_name, $data) {
		$this->action = 'insert';
		// just run the update function
		$this->update($event_name, $data);
	}


	function update($event_name, $data) {
		global $app;

		if($this->action != 'insert') $this->action = 'update';

		$app->plugin_webserver_base->eventUpdate($event_name, $data, $this->action, 'apache');

		//* Unset action to clean it for next processed vhost.
		$this->action = '';
	}

	function delete($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventDelete($event_name, $data, 'apache');
	}

	//* This function is called when a IP on the server is inserted, updated or deleted or when anon_ip setting is altered
	function server_ip($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventServerIp($event_name, $data, 'apache');

	}

	//* Create or update the .htaccess folder protection
	function web_folder_user($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventWebFolderUser($event_name, $data, 'apache');

	}

	//* Remove .htaccess and .htpasswd file, when folder protection is removed
	function web_folder_delete($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventWebFolderDelete($event_name, $data, 'apache');
	}

	//* Update folder protection, when path has been changed
	function web_folder_update($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventWebFolderUpdate($event_name, $data, 'apache');
	}

	public function ftp_user_delete($event_name, $data) {
		global $app;

		$ftpquota_file = $data['old']['dir'].'/.ftpquota';
		if(file_exists($ftpquota_file)) $app->system->unlink($ftpquota_file);
	}

	/**
	 * This function is called when a Webdav-User is inserted, updated or deleted.
	 *
	 * @author Oliver Vogel
	 * @param string $event_name
	 * @param array $data
	 */
	public function webdav($event_name, $data) {
		global $app, $conf;

		/*
		 * load the server configuration options
		*/
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		if (($event_name == 'webdav_user_insert') || ($event_name == 'webdav_user_update')) {

			/*
			 * Get additional informations
			*/
			$sitedata = $app->db->queryOneRecord('SELECT document_root, domain, system_user, system_group FROM web_domain WHERE domain_id = ?', $data['new']['parent_domain_id']);
			$documentRoot = $sitedata['document_root'];
			$domain = $sitedata['domain'];
			$user = $sitedata['system_user'];
			$group = $sitedata['system_group'];
			$webdav_user_dir = $documentRoot . '/webdav/' . $data['new']['dir'];

			/* Check if this is a chrooted setup */
			if($web_config['website_basedir'] != '' && @is_file($web_config['website_basedir'].'/etc/passwd')) {
				$is_chrooted = true;
				$app->log('Info: Apache is chrooted.', LOGLEVEL_DEBUG);
			} else {
				$is_chrooted = false;
			}

			//* We dont want to have relative paths here
			if(stristr($webdav_user_dir, '..')  || stristr($webdav_user_dir, './')) {
				$app->log('Folder path '.$webdav_user_dir.' contains ./ or .. '.$documentRoot, LOGLEVEL_WARN);
				return false;
			}

			//* Check if the resulting path exists if yes, if it is inside the docroot
			if(is_dir($webdav_user_dir) && substr(realpath($webdav_user_dir), 0, strlen($documentRoot)) != $documentRoot) {
				$app->log('Folder path '.$webdav_user_dir.' is outside of docroot '.$documentRoot, LOGLEVEL_WARN);
				return false;
			}

			/*
			 * First the webdav-root - folder has to exist
			*/
			if(!is_dir($webdav_user_dir)) {
				$app->log('Webdav User directory '.$webdav_user_dir.' does not exist. Creating it now.', LOGLEVEL_DEBUG);
				$app->system->mkdirpath($webdav_user_dir);
			}

			/*
			 * The webdav - Root needs the group/user as owner and the apache as read and write
			*/
			//$app->system->_exec('chown ' . $user . ':' . $group . ' ' . escapeshellcmd($documentRoot . '/webdav/'));
			//$app->system->_exec('chmod 770 ' . escapeshellcmd($documentRoot . '/webdav/'));
			$app->system->chown($documentRoot . '/webdav', $user);
			$app->system->chgrp($documentRoot . '/webdav', $group);
			$app->system->chmod($documentRoot . '/webdav', 0770);

			/*
			 * The webdav folder (not the webdav-root!) needs the same (not in ONE step, because the
			 * pwd-files are owned by root)
			*/
			//$app->system->_exec('chown ' . $user . ':' . $group . ' ' . escapeshellcmd($webdav_user_dir.' -R'));
			//$app->system->_exec('chmod 770 ' . escapeshellcmd($webdav_user_dir.' -R'));
			$app->system->chown($webdav_user_dir, $user);
			$app->system->chgrp($webdav_user_dir, $group);
			$app->system->chmod($webdav_user_dir, 0770);

			/*
			 * if the user is active, we have to write/update the password - file
			 * if the user is inactive, we have to inactivate the user by removing the user from the file
			*/
			if ($data['new']['active'] == 'y') {
				$this->_writeHtDigestFile( $webdav_user_dir . '.htdigest', $data['new']['username'], $data['new']['dir'], $data['new']['password']);
			}
			else {
				/* empty pwd removes the user! */
				$this->_writeHtDigestFile( $webdav_user_dir . '.htdigest', $data['new']['username'], $data['new']['dir'], '');
			}

			/*
			 * Next step, patch the vhost - file
			*/
			$vhost_file = escapeshellcmd($web_config['vhost_conf_dir'] . '/' . $domain . '.vhost');
			$app->plugin_webserver_base->_patchVhostWebdav($vhost_file, $documentRoot . '/webdav');

			/*
			 * Last, restart apache
			*/
			if($is_chrooted) {
				$app->services->restartServiceDelayed('httpd', 'restart');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd', 'reload');
			}

		}

		if ($event_name == 'webdav_user_delete') {
			/*
			 * Get additional informations
			*/
			$sitedata = $app->db->queryOneRecord('SELECT document_root, domain FROM web_domain WHERE domain_id = ?', $data['old']['parent_domain_id']);
			$documentRoot = $sitedata['document_root'];
			$domain = $sitedata['domain'];

			/*
			 * We dont't want to destroy any (transfer)-Data. So we do NOT delete any dir.
			 * So the only thing, we have to do, is to delete the user from the password-file
			*/
			$this->_writeHtDigestFile( $documentRoot . '/webdav/' . $data['old']['dir'] . '.htdigest', $data['old']['username'], $data['old']['dir'], '');

			/*
			 * Next step, patch the vhost - file
			*/
			$vhost_file = escapeshellcmd($web_config['vhost_conf_dir'] . '/' . $domain . '.vhost');
			$app->plugin_webserver_base->_patchVhostWebdav($vhost_file, $documentRoot . '/webdav');

			/*
			 * Last, restart apache
			*/
			if($is_chrooted) {
				$app->services->restartServiceDelayed('httpd', 'restart');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd', 'reload');
			}
		}
	}


	/**
	 * This function writes the htdigest - files used by webdav and digest
	 * more info: see http://riceball.com/d/node/424
	 * @author Oliver Vogel
	 * @param string $filename The name of the digest-file
	 * @param string $username The name of the webdav-user
	 * @param string $authname The name of the realm
	 * @param string $pwdhash      The password-hash of the user
	 */
	private function _writeHtDigestFile($filename, $username, $authname, $pwdhash ) {
		global $app;

		$changed = false;
		if(is_file($filename) && !is_link($filename)) {
			$in = fopen($filename, 'r');
			$output = '';
			/*
			* read line by line and search for the username and authname
			*/
			while (preg_match("/:/", $line = fgets($in))) {
				$line = rtrim($line);
				$tmp = explode(':', $line);
				if ($tmp[0] == $username && $tmp[1] == $authname) {
					/*
					* found the user. delete or change it?
					*/
					if ($pwdhash != '') {
						$output .= $tmp[0] . ':' . $tmp[1] . ':' . $pwdhash . "\n";
					}
					$changed = true;
				}
				else {
					$output .= $line . "\n";
				}
			}
			fclose($in);
		}
		/*
		 * if we didn't change anything, we have to add the new user at the end of the file
		*/
		if (!$changed) {
			$output .= $username . ':' . $authname . ':' . $pwdhash . "\n";
		}


		/*
		 * Now lets write the new file
		*/
		if(trim($output) == '') {
			$app->system->unlink($filename);
		} else {
			$app->system->file_put_contents($filename, $output);
		}
	}

	function client_delete($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventClientDelete($event_name, $data, 'apache');
	}


} // end class
