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

class nginx_plugin {

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

		if($conf['services']['web'] == true && !@is_link('/usr/local/ispconfig/server/plugins-enabled/apache2_plugin.inc.php')) {
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
		$app->plugin_webserver_base->registerEvents('nginx');
	}

	// Handle php.ini changes
	function php_ini_changed($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventPhpIniChanged($event_name, $data, 'nginx');

	}

	// Handle the creation of SSL certificates
	function ssl($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventSsl($event_name, $data, 'nginx');
	}


	function insert($event_name, $data) {
		$this->action = 'insert';
		// just run the update function
		$this->update($event_name, $data);
	}


	function update($event_name, $data) {
		global $app;

		if($this->action != 'insert') $this->action = 'update';

		$app->plugin_webserver_base->eventUpdate($event_name, $data, $this->action, 'nginx');

		//* Unset action to clean it for next processed vhost.
		$this->action = '';
	}

	function delete($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventDelete($event_name, $data, 'nginx');
	}

	//* This function is called when a IP on the server is inserted, updated or deleted or when anon_ip setting is altered
	function server_ip($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventServerIp($event_name, $data, 'nginx');

	}

	//* Create or update the .htaccess folder protection
	function web_folder_user($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventWebFolderUser($event_name, $data, 'nginx');

	}

	//* Remove .htpasswd file, when folder protection is removed
	function web_folder_delete($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventWebFolderDelete($event_name, $data, 'nginx');
	}

	//* Update folder protection, when path has been changed
	function web_folder_update($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventWebFolderUpdate($event_name, $data, 'nginx');
	}

	public function ftp_user_delete($event_name, $data) {
		global $app;

		$ftpquota_file = $data['old']['dir'].'/.ftpquota';
		if(file_exists($ftpquota_file)) $app->system->unlink($ftpquota_file);

	}

	function client_delete($event_name, $data) {
		global $app;

		$app->plugin_webserver_base->eventClientDelete($event_name, $data, 'nginx');
	}


} // end class

