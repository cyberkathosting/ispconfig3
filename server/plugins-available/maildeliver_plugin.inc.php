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

class maildeliver_plugin {

	var $plugin_name = 'maildeliver_plugin';
	var $class_name = 'maildeliver_plugin';


	var $mailfilter_config_dir = '';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['mail'] == true && isset($conf['dovecot']['installed']) && $conf['dovecot']['installed'] == true) {
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

		$app->plugins->registerEvent('mail_user_insert', 'maildeliver_plugin', 'update');
		$app->plugins->registerEvent('mail_user_update', 'maildeliver_plugin', 'update');
		$app->plugins->registerEvent('mail_user_delete', 'maildeliver_plugin', 'delete');

	}


	function update($event_name, $data) {
		global $app, $conf;

		// load the server configuration options
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		if(substr($mail_config["homedir_path"], -1) == '/') {
			$mail_config["homedir_path"] = substr($mail_config["homedir_path"], 0, -1);
		}

		if(isset($data["new"]["email"])) {
			$email_parts = explode("@", $data["new"]["email"]);
		} else {
			$email_parts = explode("@", $data["old"]["email"]);
		}

		// Write the custom mailfilter script, if mailfilter recipe has changed
		if($data["old"]["custom_mailfilter"] != $data["new"]["custom_mailfilter"]
			or $data["old"]["move_junk"] != $data["new"]["move_junk"]
			or $data["old"]["autoresponder_subject"] != $data["new"]["autoresponder_subject"]
			or $data["old"]["autoresponder_text"] != $data["new"]["autoresponder_text"]
			or $data["old"]["autoresponder"] != $data["new"]["autoresponder"]
			or (isset($data["new"]["email"]) and $data["old"]["email"] != $data["new"]["email"])
			or $data["old"]["autoresponder_start_date"] != $data["new"]["autoresponder_start_date"]
			or $data["old"]["autoresponder_end_date"] != $data["new"]["autoresponder_end_date"]
			or $data["old"]["cc"] != $data["new"]["cc"]
		) {

			$app->log("Mailfilter config has been changed", LOGLEVEL_DEBUG);

			$sieve_file = $data["new"]["maildir"].'/.sieve';
			$sieve_file_svbin = $data["new"]["maildir"].'/.sieve.svbin';
			$old_sieve_file_isp = $data["new"]["maildir"].'/sieve/ispconfig.sieve';
			$sieve_file_isp = $data["new"]["maildir"].'/.ispconfig.sieve';
			$sieve_file_isp_svbin = $data["new"]["maildir"].'/.ispconfig.svbin';
			if(is_file($old_sieve_file_isp)) unlink($old_sieve_file_isp)  or $app->log("Unable to delete file: $old_sieve_file_isp", LOGLEVEL_WARN);
			// cleanup .sieve file if it is now a broken link
			if(is_link($sieve_file) && !file_exists($sieve_file)) unlink($sieve_file)  or $app->log("Unable to delete file: $sieve_file", LOGLEVEL_WARN);
			if(is_file($sieve_file_svbin)) unlink($sieve_file_svbin)  or $app->log("Unable to delete file: $sieve_file_svbin", LOGLEVEL_WARN);
			if(is_file($sieve_file_isp)) unlink($sieve_file_isp)  or $app->log("Unable to delete file: $sieve_file_isp", LOGLEVEL_WARN);
			if(is_file($sieve_file_isp_svbin)) unlink($sieve_file_isp_svbin)  or $app->log("Unable to delete file: $sieve_file_isp_svbin", LOGLEVEL_WARN);
			$app->load('tpl');

			//* Select sieve filter file for dovecot version
			exec('dovecot --version', $tmp);
			if(substr($tmp[0], 0, 3) == '1.0') {
				$filter_file_template = "sieve_filter.master";
			} elseif(substr($tmp[0], 0, 3) == '1.2') {
				$filter_file_template = "sieve_filter_1.2.master";
			} elseif(substr($tmp[0], 0, 1) == '2') {
				$filter_file_template = "sieve_filter_1.2.master";
			} else {
				$filter_file_template = "sieve_filter.master";
			}
			unset($tmp);

			//* Create new filter file based on template
			$tpl = new tpl();
			$tpl->newTemplate($filter_file_template);

			// cc Field
			$tmp_mails_arr = explode(',',$data["new"]["cc"]);
			$tmp_addresses_arr = array();
			foreach($tmp_mails_arr as $address) {
				if(trim($address) != '') $tmp_addresses_arr[] = array('address' => trim($address));
			}
			
			$tpl->setVar('cc', $data["new"]["cc"]);
			$tpl->setLoop('ccloop', $tmp_addresses_arr);

			// Custom filters
			if($data["new"]["custom_mailfilter"] == 'NULL') $data["new"]["custom_mailfilter"] = '';
			$tpl->setVar('custom_mailfilter', str_replace("\r\n","\n",$data["new"]["custom_mailfilter"]));

			// Move junk
			$tpl->setVar('move_junk', $data["new"]["move_junk"]);

			// Check autoresponder dates
			if((!$data['new']['autoresponder_start_date'] || $data["new"]["autoresponder_start_date"] == '0000-00-00 00:00:00') && (!$data['new']['autoresponder_end_date'] || $data["new"]["autoresponder_end_date"] == '0000-00-00 00:00:00')) {
				$tpl->setVar('autoresponder_date_limit', 0);
			} else {
				$tpl->setVar('autoresponder_date_limit', 1);
			}


			// Set autoresponder start date
			$data["new"]["autoresponder_start_date"] = str_replace(" ", "T", $data["new"]["autoresponder_start_date"]);
			$tpl->setVar('start_date', $data["new"]["autoresponder_start_date"]);

			// Set autoresponder end date
			$data["new"]["autoresponder_end_date"] = str_replace(" ", "T", $data["new"]["autoresponder_end_date"]);
			$tpl->setVar('end_date', $data["new"]["autoresponder_end_date"]);

			// Autoresponder
			$tpl->setVar('autoresponder', $data["new"]["autoresponder"]);

			// Autoresponder Subject
			$data["new"]["autoresponder_subject"] = str_replace("\"", "'", $data["new"]["autoresponder_subject"]);
			$tpl->setVar('autoresponder_subject', $data["new"]["autoresponder_subject"]);

			// Autoresponder Text
			$data["new"]["autoresponder_text"] = str_replace("\"", "'", $data["new"]["autoresponder_text"]);
			$tpl->setVar('autoresponder_text', $data["new"]["autoresponder_text"]);

			//* Set alias addresses for autoresponder
			$sql = "SELECT * FROM mail_forwarding WHERE type = 'alias' AND destination = ?";
			$records = $app->db->queryAllRecords($sql, $data["new"]["email"]);

			$addresses = array();
			$addresses[] = $data["new"]["email"];
			if(is_array($records) && count($records) > 0) {
				foreach($records as $rec) {
					$addresses[] = $rec['source'];
				}
			}

			$app->log("Found " . count($addresses) . " addresses.", LOGLEVEL_DEBUG);

			$alias_addresses = array();

			$email_parts = explode('@', $data["new"]["email"]);
			$sql = "SELECT * FROM mail_forwarding WHERE type = 'aliasdomain' AND destination = ?";
			$records = $app->db->queryAllRecords($sql, '@'.$email_parts[1]);
			if(is_array($records) && count($records) > 0) {
				$app->log("Found " . count($records) . " records (aliasdomains).", LOGLEVEL_DEBUG);
				foreach($records as $rec) {
					$aliasdomain = substr($rec['source'], 1);
					foreach($addresses as $email) {
						$email_parts = explode('@', $email);
						$alias_addresses[] = $email_parts[0].'@'.$aliasdomain;
					}
				}
			}

			$app->log("Found " . count($addresses) . " addresses at all.", LOGLEVEL_DEBUG);

			$addresses = array_unique(array_merge($addresses, $alias_addresses));

			$app->log("Found " . count($addresses) . " unique addresses at all.", LOGLEVEL_DEBUG);

			$address_str = '';
			if(is_array($addresses) && count($addresses) > 0) {
				$address_str .= ':addresses [';
				foreach($addresses as $rec) {
					$address_str .= '"'.$rec.'",';
				}
				$address_str = substr($address_str, 0, -1);
				$address_str .= ']';
			}


			$tpl->setVar('addresses', $address_str);

			if ( ! is_dir($data["new"]["maildir"].'/sieve/') ) {
				$app->system->mkdirpath($data["new"]["maildir"].'/sieve/', 0700, $mail_config['mailuser_name'], $mail_config['mailuser_group']);
			}

			file_put_contents($sieve_file_isp, $tpl->grab()) or $app->log("Unable to write sieve filter file", LOGLEVEL_WARN);
			if ( is_file($sieve_file_isp) ) {
				$app->system->chown($sieve_file_isp,$mail_config['mailuser_name'],false);
				$app->system->chgrp($sieve_file_isp,$mail_config['mailuser_group'],false);

				$app->system->exec_safe("sievec ?", "$sieve_file_isp");
				if ( is_file($sieve_file_isp_svbin) ) {
					$app->system->chown($sieve_file_isp_svbin,$mail_config['mailuser_name'],false);
					$app->system->chgrp($sieve_file_isp_svbin,$mail_config['mailuser_group'],false);
				}
			}

			unset($tpl);

		}
	}

	function delete($event_name, $data) {
		global $app, $conf;

		$sieve_file = $data["old"]["maildir"].'/.sieve';
		$sieve_file_svbin = $data["old"]["maildir"].'/.sieve.svbin';
		$old_sieve_file_isp = $data["old"]["maildir"].'/sieve/ispconfig.sieve';
		$sieve_file_isp = $data["old"]["maildir"].'/.ispconfig.sieve';
		$sieve_file_isp_svbin = $data["old"]["maildir"].'/.ispconfig.svbin';
		if(is_file($old_sieve_file_isp)) unlink($old_sieve_file_isp)  or $app->log("Unable to delete file: $old_sieve_file_isp", LOGLEVEL_WARN);
		// cleanup .sieve file if it is now a broken link
		if(is_link($sieve_file) && !file_exists($sieve_file)) unlink($sieve_file)  or $app->log("Unable to delete file: $sieve_file", LOGLEVEL_WARN);
		if(is_file($sieve_file_svbin)) unlink($sieve_file_svbin)  or $app->log("Unable to delete file: $sieve_file_svbin", LOGLEVEL_WARN);
		if(is_file($sieve_file_isp)) unlink($sieve_file_isp)  or $app->log("Unable to delete file: $sieve_file_isp", LOGLEVEL_WARN);
		if(is_file($sieve_file_isp_svbin)) unlink($sieve_file_isp_svbin)  or $app->log("Unable to delete file: $sieve_file_isp_svbin", LOGLEVEL_WARN);
	}


} // end class

?>
