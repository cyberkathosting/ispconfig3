<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

class postfix_filter_plugin {

	var $plugin_name = 'postfix_filter_plugin';
	var $class_name = 'postfix_filter_plugin';


	var $postfix_config_dir = '/etc/postfix';

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

		$app->plugins->registerEvent('mail_content_filter_insert', 'postfix_filter_plugin', 'insert');
		$app->plugins->registerEvent('mail_content_filter_update', 'postfix_filter_plugin', 'update');
		$app->plugins->registerEvent('mail_content_filter_delete', 'postfix_filter_plugin', 'delete');



	}

	function insert($event_name, $data) {
		global $app, $conf;

		$this->update($event_name, $data);

	}

	function update($event_name, $data) {
		global $app, $conf;

		$type = $data["new"]["type"];
		$restart = false;

		if($type != '') {
			$sql = "SELECT * FROM mail_content_filter WHERE server_id = ? AND type = ? AND active = 'y'";
			$rules = $app->db->queryAllRecords($sql, $conf["server_id"], $type);
			$content = '';
			foreach($rules as $rule) {
				$content .= $rule["pattern"];
				$content .= "  ".$rule["action"]." ".$rule["data"]."\n";
			}

			if($type == 'header') {
				file_put_contents('/etc/postfix/header_checks', $content);
				$app->log("Writing /etc/postfix/header_checks", LOGLEVEL_DEBUG);
				$restart = true;
			}

			if($type == 'mime_header') {
				file_put_contents('/etc/postfix/mime_header_checks', $content);
				$app->log("Writing /etc/postfix/mime_header_checks", LOGLEVEL_DEBUG);
				$restart = true;
			}

			if($type == 'nested_header') {
				file_put_contents('/etc/postfix/nested_header_checks', $content);
				$app->log("Writing /etc/postfix/nested_header_checks", LOGLEVEL_DEBUG);
				$restart = true;
			}

			if($type == 'body') {
				file_put_contents('/etc/postfix/body_checks', $content);
				$app->log("Writing /etc/postfix/body_checks", LOGLEVEL_DEBUG);
				$restart = true;
			}
		}

		$type = $data["old"]["type"];
		if($type != '') {
			$sql = "SELECT * FROM mail_content_filter WHERE server_id = ? AND type = ? AND active = 'y'";
			$rules = $app->db->queryAllRecords($sql, $conf["server_id"], $type);
			$content = '';
			foreach($rules as $rule) {
				$content .= $rule["pattern"];
				$content .= "  ".$rule["action"]." ".$rule["data"]."\n";
			}

			if($type == 'header') {
				file_put_contents('/etc/postfix/header_checks', $content);
				$app->log("Writing /etc/postfix/header_checks", LOGLEVEL_DEBUG);
				$restart = true;
			}

			if($type == 'mime_header') {
				file_put_contents('/etc/postfix/mime_header_checks', $content);
				$app->log("Writing /etc/postfix/mime_header_checks", LOGLEVEL_DEBUG);
				$restart = true;
			}

			if($type == 'nested_header') {
				file_put_contents('/etc/postfix/nested_header_checks', $content);
				$app->log("Writing /etc/postfix/nested_header_checks", LOGLEVEL_DEBUG);
				$restart = true;
			}

			if($type == 'body') {
				file_put_contents('/etc/postfix/body_checks', $content);
				$app->log("Writing /etc/postfix/body_checks", LOGLEVEL_DEBUG);
				$restart = true;
			}
		}
		if($restart) exec('postfix reload');
	}

	function delete($event_name, $data) {
		global $app, $conf;

		$this->update($event_name, $data);
	}


} // end class

?>
