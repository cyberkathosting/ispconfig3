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

class plugins {
	
	var $notification_events = array();
	
	/*
	 This function is called to load the plugins from the plugins-available folder
	*/
	
	function loadPlugins() {
		global $app,$conf;
		
		$plugins_dir = $conf["rootpath"].$conf["fs_div"]."lib".$conf["fs_div"]."plugins-enabled".$conf["fs_div"]
		
		if (is_dir($plugins_dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..') {
						$plugin_name = substr($file,0,-8);
						include_once($plugins_dir.$file);
						$app->log("Loading Plugin: $plugin_name",LOGLEVEL_DEBUG);
						$app->plugins[$plugin_name] = new $module_name;
						$app->plugins[$plugin_name]->onLoad();
					}
				}
			}
		} else {
			$app->log("Plugin directory missing: $plugins_dir",LOGLEVEL_ERROR);
		}
		
	}
	
	/*
	 This function is called by the modules to register for a specific
	 table change notification
	*/
	
	function registerEvent($event_name,$plugin_name,$function_name) {
		$this->notification_events[$event_name][] = array('plugin' => $plugin_name, 'function' => $function_name);
	}
	
	
	function raiseEvent($event_name,$data) {
		global $app;
		
		// Get the hooks for this table
		$events = $this->notification_hevents[$event_name];
		
		if(is_array($events)) {
			foreach($events as $event) {
				$plugin_name = $event["plugin"];
				$function_name = $event["function"];
				// Claa the processing function of the module
				call_user_method($function_name,$app->plugins[$plugin_name],$event_name,$data);
				unset($plugin_name);
				unset($function_name);
			}
		}
		unset($event);
		unset($events);
	}
	
}

?>