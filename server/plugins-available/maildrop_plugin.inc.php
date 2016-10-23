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

class maildrop_plugin {

	var $plugin_name = 'maildrop_plugin';
	var $class_name = 'maildrop_plugin';


	var $mailfilter_config_dir = '';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['mail'] == true && isset($conf['courier']['installed']) && $conf['courier']['installed'] == true) {
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

		$app->plugins->registerEvent('mail_user_insert', 'maildrop_plugin', 'update');
		$app->plugins->registerEvent('mail_user_update', 'maildrop_plugin', 'update');
		$app->plugins->registerEvent('mail_user_delete', 'maildrop_plugin', 'delete');

	}


	function update($event_name, $data) {
		global $app, $conf;

		// load the server configuration options
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		if(substr($mail_config["homedir_path"], -1) == '/') {
			$mail_config["homedir_path"] = substr($mail_config["homedir_path"], 0, -1);
		}
		$this->mailfilter_config_dir = $mail_config["homedir_path"].'/mailfilters';


		// Check if the config directory exists.
		if(!is_dir($this->mailfilter_config_dir)) {
			$app->log("Mailfilter config directory '".$this->mailfilter_config_dir."' does not exist. Creating it now.", LOGLEVEL_WARN);
			mkdir($this->mailfilter_config_dir);
			chown($this->mailfilter_config_dir, 'vmail');
			chmod($this->mailfilter_config_dir, 0770);
		}

		if(isset($data["new"]["email"])) {
			$email_parts = explode("@", $data["new"]["email"]);
		} else {
			$email_parts = explode("@", $data["old"]["email"]);
		}

		// make sure that the config directories exist
		if(!is_dir($this->mailfilter_config_dir.'/'.$email_parts[1])) {
			mkdir($this->mailfilter_config_dir.'/'.$email_parts[1]);
			chown($this->mailfilter_config_dir.'/'.$email_parts[1], 'vmail');
			chmod($this->mailfilter_config_dir.'/'.$email_parts[1], 0770);
		}
		if(!is_dir($this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0])) {
			mkdir($this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0]);
			chown($this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0], 'vmail');
			chmod($this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0], 0770);
		}

		// Check if something has been changed regarding the autoresponders
		if($data["old"]["autoresponder_text"] != $data["new"]["autoresponder_text"]
			or $data["old"]["autoresponder"] != $data["new"]["autoresponder"]
			or (isset($data["new"]["email"]) and $data["old"]["email"] != $data["new"]["email"])
			or $data["old"]["autoresponder_start_date"] != $data["new"]["autoresponder_start_date"]
			or $data["old"]["autoresponder_end_date"] != $data["new"]["autoresponder_end_date"]) {

			// We delete the old autoresponder, if it exists
			$email_parts = explode("@", $data["old"]["email"]);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.lock';
			if(is_file($file)) unlink($file) or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.lst';
			if(is_file($file)) unlink($file) or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.lst.gdbm';
			if(is_file($file)) unlink($file) or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.lst.lock';
			if(is_file($file)) unlink($file) or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.msg';
			if(is_file($file)) unlink($file) or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.autoresponder';
			if(is_file($file)) unlink($file) or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);


			//Now we create the new autoresponder, if it is enabled
			if($data["new"]["autoresponder"] == 'y') {
				if(isset($data["new"]["email"])) {
					$email_parts = explode("@", $data["new"]["email"]);
				} else {
					$email_parts = explode("@", $data["old"]["email"]);
				}

				// Load the master template
				if(file_exists($conf["rootpath"].'/conf-custom/autoresponder.master')) {
					$tpl = file_get_contents($conf["rootpath"].'/conf-custom/autoresponder.master');
				} else {
					$tpl = file_get_contents($conf["rootpath"].'/conf/autoresponder.master');
				}
				$tpl = str_replace('{vmail_mailbox_base}', $mail_config["homedir_path"], $tpl);

				if ($data['new']['autoresponder_start_date'] && $data["new"]["autoresponder_start_date"] != '0000-00-00 00:00:00') { // Dates have been set
					$tpl = str_replace('{start_date}', strtotime($data["new"]["autoresponder_start_date"]), $tpl);
					$tpl = str_replace('{end_date}', strtotime($data["new"]["autoresponder_end_date"]), $tpl);
				} else {
					$tpl = str_replace('{start_date}', -7200, $tpl);
					$tpl = str_replace('{end_date}', 2147464800, $tpl);
				}

				// Write the config file.
				$config_file_path = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.autoresponder';
				file_put_contents($config_file_path, $tpl);
				$app->log("Writing Autoresponder mailfilter file: $config_file_path", LOGLEVEL_DEBUG);
				chmod($config_file_path, 0770);
				chown($config_file_path, 'vmail');
				unset($tpl);
				unset($config_file_path);

				// Write the autoresponder message file
				$config_file_path = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.msg';
				file_put_contents($config_file_path, $data["new"]["autoresponder_text"]);
				chmod($config_file_path, 0770);
				chown($config_file_path, 'vmail');
				$app->log("Writing Autoresponder message file: $config_file_path", LOGLEVEL_DEBUG);
			}
		}

		// Write the custom mailfilter script, if mailfilter recipe has changed
		if($data["old"]["custom_mailfilter"] != $data["new"]["custom_mailfilter"]
			or $data["old"]["move_junk"] != $data["new"]["move_junk"]
			or $data["old"]["cc"] != $data["new"]["cc"]) {

			$app->log("Mailfilter config has been changed", LOGLEVEL_DEBUG);
			if(trim($data["new"]["custom_mailfilter"]) != ''
				or $data["new"]["move_junk"] != 'n'
				or $data["new"]["cc"] != '') {

				// Delete the old filter recipe
				$email_parts = explode("@", $data["old"]["email"]);
				$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.mailfilter';
				if(is_file($file)) unlink($file)  or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);

				// write the new recipe
				if(isset($data["new"]["email"])) {
					$email_parts = explode("@", $data["new"]["email"]);
				} else {
					$email_parts = explode("@", $data["old"]["email"]);
				}
				$config_file_path = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.mailfilter';

				$mailfilter_content = '';

				if($data["new"]["cc"] != '') {
					$tmp_mails_arr = explode(',',$data["new"]["cc"]);
					foreach($tmp_mails_arr as $address) {
						if(trim($address) != '') $mailfilter_content .= "cc \"!".trim($address)."\"\n";
					}
					//$mailfilter_content .= "cc \"!".$data["new"]["cc"]."\"\n";
					$app->log("Added CC address ".$data["new"]["cc"].' to mailfilter file.', LOGLEVEL_DEBUG);
				}

				if($data["new"]["move_junk"] == 'y') {
					if(file_exists($conf["rootpath"].'/conf-custom/mailfilter_move_junk.master')) {
						$mailfilter_content .= file_get_contents($conf["rootpath"].'/conf-custom/mailfilter_move_junk.master')."\n";
					} else {
						$mailfilter_content .= file_get_contents($conf["rootpath"].'/conf/mailfilter_move_junk.master')."\n";
					}
				}
				$mailfilter_content .= str_replace("\r\n","\n",$data["new"]["custom_mailfilter"]);

				// Replace windows linebreaks in mailfilter file
				$mailfilter_content = str_replace("\r\n", "\n", $mailfilter_content);

				file_put_contents($config_file_path, $mailfilter_content);
				$app->log("Writing new custom Mailfiter".$config_file_path, LOGLEVEL_DEBUG);
				chmod($config_file_path, 0770);
				chown($config_file_path, 'vmail');
				unset($config_file_path);
			} else {
				// Delete the mailfilter recipe
				if(isset($data["old"]["email"])) {
					$email_parts = explode("@", $data["old"]["email"]);
					$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.mailfilter';
					if(is_file($file)) {
						unlink($file)  or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
						$app->log("Deleting custom Mailfiter".$file, LOGLEVEL_DEBUG);
					}
				}
			}
		}
	}

	function delete($event_name, $data) {
		global $app, $conf;

		// load the server configuration options
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		$this->mailfilter_config_dir = $mail_config["homedir_path"].'/mailfilters';

		$email_parts = explode("@", $data["old"]["email"]);
		$dir = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0];
		if(is_dir($dir)) {
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.lock';
			if(is_file($file)) unlink($file)  or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.lst';
			if(is_file($file)) unlink($file)  or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.msg';
			if(is_file($file)) unlink($file)  or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.autoresponder';
			if(is_file($file)) unlink($file)  or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.mailfilter';
			if(is_file($file)) unlink($file)  or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.lst.gdbm';
			if(is_file($file)) unlink($file)  or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			$file = $this->mailfilter_config_dir.'/'.$email_parts[1].'/'.$email_parts[0].'/.vacation.lst.lock';
			if(is_file($file)) unlink($file)  or $app->log("Unable to delete file: $file", LOGLEVEL_WARN);
			rmdir($dir) or $app->log("Unable to delete directory: $dir", LOGLEVEL_WARN);
		}
	}


} // end class

?>
