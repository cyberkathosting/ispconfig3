<?php

/*
Copyright (c) 2007 - 2009, Till Brehm, projektfarm Gmbh
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

class getmail_plugin {

	var $plugin_name = 'getmail_plugin';
	var $class_name = 'getmail_plugin';

	var $getmail_config_dir = '';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['mail'] == true) {
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

		$app->plugins->registerEvent('mail_get_insert', 'getmail_plugin', 'insert');
		$app->plugins->registerEvent('mail_get_update', 'getmail_plugin', 'update');
		$app->plugins->registerEvent('mail_get_delete', 'getmail_plugin', 'delete');



	}

	function insert($event_name, $data) {
		global $app, $conf;

		$this->update($event_name, $data);

	}

	function update($event_name, $data) {
		global $app, $conf;
		
		// Do not write getmail config files on mirror servers to avoid double fetching of emails.
		if($conf['mirror_server_id'] > 0) return true;

		// load the server specific configuration options for getmail
		$app->uses("getconf");
		$getmail_config = $app->getconf->get_server_config($conf["server_id"], 'getmail');
		$this->getmail_config_dir = $getmail_config["getmail_config_dir"];

		// Check if the config directory exists.
		if(!is_dir($this->getmail_config_dir)) {
			$app->log("Getmail config directory '".$this->getmail_config_dir."' does not exist.", LOGLEVEL_ERROR);
		} else {

			// Delete the config file first, if it exists
			$this->delete($event_name, $data);

			// Get the new config file path
			$config_file_path = $this->getmail_config_dir.'/'.$this->_clean_path($data["new"]["source_server"]).'_'.$this->_clean_path($data["new"]["source_username"]).'.conf';
			if(stristr($config_file_path, "..") or stristr($config_file_path, "|") or stristr($config_file_path, ";") or stristr($config_file_path, '$')) {
				$app->log("Possibly faked path for getmail config file: '$config_file_path'. File is not written.", LOGLEVEL_ERROR);
				return false;
			}


			if($data["new"]["active"] == 'y') {
				// Open master template
				if(file_exists($conf["rootpath"].'/conf-custom/getmail.conf.master')) {
					$tpl = file_get_contents($conf["rootpath"].'/conf-custom/getmail.conf.master');
				} else {
					$tpl = file_get_contents($conf["rootpath"].'/conf/getmail.conf.master');
				}

				// Shall emails be deleted after retrieval
				if($data["new"]["source_delete"] == 'y') {
					$tpl = str_replace('{DELETE}', 'true', $tpl);
				} else {
					$tpl = str_replace('{DELETE}', 'false', $tpl);
				}

				if($data["new"]["read_all"] == 'y') {
					$tpl = str_replace('{READ_ALL}', 'true', $tpl);
				} else {
					$tpl = str_replace('{READ_ALL}', 'false', $tpl);
				}

				// Set the data retriever
				if($data["new"]["type"] == 'pop3') {
					$tpl = str_replace('{TYPE}', 'SimplePOP3Retriever', $tpl);
				} elseif ($data["new"]["type"] == 'imap') {
					$tpl = str_replace('{TYPE}', 'SimpleIMAPRetriever', $tpl);
				} elseif ($data["new"]["type"] == 'pop3ssl') {
					$tpl = str_replace('{TYPE}', 'SimplePOP3SSLRetriever', $tpl);
				} elseif ($data["new"]["type"] == 'imapssl') {
					$tpl = str_replace('{TYPE}', 'SimpleIMAPSSLRetriever', $tpl);
				}

				// Set server, username, password and destination.
				$tpl = str_replace('{SERVER}', $data["new"]["source_server"], $tpl);
				$tpl = str_replace('{USERNAME}', $data["new"]["source_username"], $tpl);
				$tpl = str_replace('{PASSWORD}', $data["new"]["source_password"], $tpl);
				$tpl = str_replace('{DESTINATION}', $data["new"]["destination"], $tpl);

				// Write the config file.
				file_put_contents($config_file_path, $tpl);
				$app->log("Writing Getmail config file: $config_file_path", LOGLEVEL_DEBUG);
				chmod($config_file_path, 0400);
				chown($config_file_path, 'getmail');
				unset($tpl);
				unset($config_file_path);

			} else {
				// If record is set to inactive, we will delete the file
				if(is_file($config_file_path)) unlink($config_file_path);
			}
		}
	}

	function delete($event_name, $data) {
		global $app, $conf;

		// load the server specific configuration options for getmail
		$app->uses("getconf");
		$getmail_config = $app->getconf->get_server_config($conf["server_id"], 'getmail');
		$this->getmail_config_dir = $getmail_config["getmail_config_dir"];

		$config_file_path = $this->getmail_config_dir.'/'.$this->_clean_path($data["old"]["source_server"]).'_'.$this->_clean_path($data["old"]["source_username"]).'.conf';
		if(stristr($config_file_path, "..") || stristr($config_file_path, "|") || stristr($config_file_path, ";") || stristr($config_file_path, '$')) {
			$app->log("Possibly faked path for getmail config file: '$config_file_path'. File is not written.", LOGLEVEL_ERROR);
			return false;
		}
		if(is_file($config_file_path)) unlink($config_file_path);
	}

	function _clean_path($input) {
		return preg_replace('/[^A-Za-z0-9\-_]/', '_', $input);
	}


} // end class

?>
