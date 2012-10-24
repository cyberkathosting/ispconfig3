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
		
		$app->plugins->registerEvent('mail_user_insert','maildeliver_plugin','update');
		$app->plugins->registerEvent('mail_user_update','maildeliver_plugin','update');
		$app->plugins->registerEvent('mail_user_delete','maildeliver_plugin','delete');
		
	}
	
	
	function update($event_name,$data) {
		global $app, $conf;
		
		// load the server configuration options
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		if(substr($mail_config["homedir_path"],-1) == '/') {
			$mail_config["homedir_path"] = substr($mail_config["homedir_path"],0,-1);
		}
		
		if(isset($data["new"]["email"])) {
			$email_parts = explode("@",$data["new"]["email"]);
		} else {
			$email_parts = explode("@",$data["old"]["email"]);
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
				
			$app->log("Mailfilter config has been changed",LOGLEVEL_DEBUG);
				
			$sieve_file = $data["new"]["maildir"].'/.sieve';
			if(is_file($sieve_file)) unlink($sieve_file)  or $app->log("Unable to delete file: $sieve_file",LOGLEVEL_WARN);
				
			$app->load('tpl');
			
			//* Select sieve filter file for dovecot version
			exec('dovecot --version',$tmp);
			if(substr($tmp[0],0,3) == '1.0') {
				$filter_file_template = "sieve_filter.master";
			} elseif(substr($tmp[0],0,3) == '1.2') {
				$filter_file_template = "sieve_filter_1.2.master";
			} elseif(substr($tmp[0],0,1) == '2') {
				$filter_file_template = "sieve_filter_1.2.master";
			} else {
				$filter_file_template = "sieve_filter.master";
			}
			unset($tmp);
			
			//* Create new filter file based on template
			$tpl = new tpl();
			$tpl->newTemplate($filter_file_template);
			
			// cc Field
			$tpl->setVar('cc',$data["new"]["cc"]);
				
			// Custom filters
			$tpl->setVar('custom_mailfilter',$data["new"]["custom_mailfilter"]);
				
			// Move junk
			$tpl->setVar('move_junk',$data["new"]["move_junk"]);
			
			// Check autoresponder dates
			if($data["new"]["autoresponder_start_date"] == '0000-00-00 00:00:00' && $data["new"]["autoresponder_end_date"] == '0000-00-00 00:00:00') {
				$tpl->setVar('autoresponder_date_limit',0);
			} else {
				$tpl->setVar('autoresponder_date_limit',1);
			}
			

			// Set autoresponder start date
			$data["new"]["autoresponder_start_date"] = str_replace (" ", "T", $data["new"]["autoresponder_start_date"]);
			$tpl->setVar('start_date',$data["new"]["autoresponder_start_date"]);

			// Set autoresponder end date
			$data["new"]["autoresponder_end_date"] = str_replace (" ", "T", $data["new"]["autoresponder_end_date"]);
			$tpl->setVar('end_date',$data["new"]["autoresponder_end_date"]);

			// Autoresponder
			$tpl->setVar('autoresponder',$data["new"]["autoresponder"]);

			// Autoresponder Subject			
			$data["new"]["autoresponder_subject"] = str_replace("\"","'",$data["new"]["autoresponder_subject"]); 
			$tpl->setVar('autoresponder_subject',$data["new"]["autoresponder_subject"]);

			// Autoresponder Text
			$data["new"]["autoresponder_text"] = str_replace("\"","'",$data["new"]["autoresponder_text"]); 
			$tpl->setVar('autoresponder_text',$data["new"]["autoresponder_text"]);
			
			//* Set alias addresses for autoresponder
			$sql = "SELECT * FROM mail_forwarding WHERE type = 'alias' AND destination = '".$app->db->quote($data["new"]["email"])."'";
			$records = $app->db->queryAllRecords($sql);
			$addresses = '';
			if(is_array($records) && count($records) > 0) {
				$addresses .= ':addresses ["'.$data["new"]["email"].'",';
				foreach($records as $rec) {
					$addresses .= '"'.$rec['source'].'",';
				}
				$addresses = substr($addresses,0,-1);
				$addresses .= ']';
			}
			$tpl->setVar('addresses',$addresses);
			
			file_put_contents($sieve_file,$tpl->grab());
			
			unset($tpl);
				
		}
	}
	
	function delete($event_name,$data) {
		global $app, $conf;
		
		$sieve_file = $data["old"]["maildir"].'/.sieve';
		if(is_file($sieve_file)) unlink($sieve_file)  or $app->log("Unable to delete file: $sieve_file",LOGLEVEL_WARN);
	}
	

} // end class

?>