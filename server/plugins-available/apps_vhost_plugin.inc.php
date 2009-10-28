<?php

/*
Copyright (c) 2009, Till Brehm, projektfarm Gmbh
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

class apps_vhost_plugin {
	
	var $plugin_name = 'apps_vhost_plugin';
	var $class_name = 'apps_vhost_plugin';
	
	
	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;
		
		return true;
		
	}
	
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		$app->plugins->registerEvent('server_insert','apps_vhost_plugin','insert');
		$app->plugins->registerEvent('server_update','apps_vhost_plugin','update');		
		
		
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
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');
				
		// Dont just copy over the virtualhost template but add some custom settings
        $content = file_get_contents($conf["rootpath"]."/conf/apache_apps.vhost.master");
		
		$vhost_conf_dir = $web_config['vhost_conf_dir'];
        $vhost_conf_enabled_dir = $web_config['vhost_conf_enabled_dir'];
		$apps_vhost_servername = ($web_config['apps_vhost_servername'] == '')?'':'ServerName '.$web_config['apps_vhost_servername'];
		
		$content = str_replace('{apps_vhost_ip}', $web_config['apps_vhost_ip'], $content);
		$content = str_replace('{apps_vhost_port}', $web_config['apps_vhost_port'], $content);
		$content = str_replace('{apps_vhost_dir}', $web_config['website_basedir'].'/apps', $content);
		$content = str_replace('{apps_vhost_servername}', $apps_vhost_servername, $content);
		
		
		// comment out the listen directive if port is 80 or 443
		if($web_config['apps_vhost_ip'] == 80 or $web_config['apps_vhost_ip'] == 443) {
			$content = str_replace('{vhost_port_listen}', '#', $content);
		} else {
			$content = str_replace('{vhost_port_listen}', '', $content);
		}
		
		file_put_contents("$vhost_conf_dir/apps.vhost", $content);
		
		$app->services->restartServiceDelayed('httpd','restart');
		
		
	}
	

} // end class



?>