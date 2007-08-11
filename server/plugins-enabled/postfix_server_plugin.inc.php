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

class postfix_server_plugin {
	
	var $plugin_name = 'postfix_server_plugin';
	var $class_name = 'postfix_server_plugin';
	
	
	var $postfix_config_dir = '/etc/postfix';
	
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		$app->plugins->registerEvent('server_insert','postfix_server_plugin','insert');
		$app->plugins->registerEvent('server_update','postfix_server_plugin','update');
		
		
		
	}
	
	function insert($event_name,$data) {
		global $app, $conf;
		
		$this->update($event_name,$data);
		
	}
	
	// The purpose of this plugin is to rewrite the main.cf file
	function update($event_name,$data) {
		global $app, $conf;
		
		// get the config
		$app->uses("getconf");
		$mail_config = $app->getconf->get_server_config($conf["server_id"], 'mail');
		
		copy('/etc/postfix/main.cf','/etc/postfix/main.cf~');
		
		if($mail_config["relayhost"] != '') {
			exec("postconf -e 'relayhost = ".$mail_config["relayhost"]."'");
			exec("postconf -e 'smtp_sasl_auth_enable = yes'");
			exec("postconf -e 'smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd'");
			exec("postconf -e 'smtp_sasl_security_options ='");
			
			// Store the sasl passwd
			$content = $mail_config["relayhost"]."   ".$mail_config["relayhost_user"].":".$mail_config["relayhost_password"];
			file_put_contents('/etc/postfix/sasl_passwd',$content);
			exec("chown root:root /etc/postfix/sasl_passwd");
			exec("chmod 600 /etc/postfix/sasl_passwd");
			exec("postmap /etc/postfix/sasl_passwd");
			exec("/etc/init.d/postfix restart");
			
		} else {
			exec("postconf -e 'relayhost ='");
		}
		
		exec("postconf -e 'mailbox_size_limit = ".intval($mail_config["mailbox_size_limit"])."'");
		exec("postconf -e 'message_size_limit = ".intval($mail_config["message_size_limit"])."'");
		
	}

} // end class

?>