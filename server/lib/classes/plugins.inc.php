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
	
	var $available_events = array();
	var $subscribed_events = array();
	
	/*
	 This function is called to load the plugins from the plugins-enabled or the plugins-core folder
	*/
	
	function loadPlugins($type) {
		global $app,$conf;

		$subPath = 'plugins-enabled';
		if ($type == 'core') $subPath = 'plugins-core';
		
		$plugins_dir = $conf["rootpath"].$conf["fs_div"].$subPath.$conf["fs_div"];
		$tmp_plugins = array();
		
		if (is_dir($plugins_dir)) {
			if ($dh = opendir($plugins_dir)) {
				//** Go trough all files in the plugin dir
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..' && substr($file,-8,8) == '.inc.php') {
						$plugin_name = substr($file,0,-8);
						$tmp_plugins[$plugin_name] = $file;
					}
				}
				//** sort the plugins by name
				ksort($tmp_plugins);
				
				//** load the plugins
				foreach($tmp_plugins as $plugin_name => $file) {
					include_once($plugins_dir.$file);
					$app->log("Loading Plugin: $plugin_name",LOGLEVEL_DEBUG);
					$app->loaded_plugins[$plugin_name] = new $plugin_name;
					$app->loaded_plugins[$plugin_name]->onLoad();
				}
			} else {
				$app->log("Unable to open the plugin directory: $plugins_dir",LOGLEVEL_ERROR);
			}
		} else {
			$app->log("Plugin directory missing: $plugins_dir",LOGLEVEL_ERROR);
		}
		
	}
	
	/*
		This function is used by the modules to announce which events they provide
	*/
	
	function announceEvents($module_name,$events) {
		global $app;
		foreach($events as $event_name) {
			$this->available_events[$event_name] = $module_name;
			$app->log("Announced event: $event_name",LOGLEVEL_DEBUG);
		}
	}
	
	
	/*
	 This function is called by the plugin to register for an event
	*/
	
	function registerEvent($event_name,$plugin_name,$function_name) {
		global $app;
		if(!isset($this->available_events[$event_name])) {
			$app->log("Unable to register the function '$function_name' in the plugin '$plugin_name' for event '$event_name'",LOGLEVEL_DEBUG);
		} else {
			$this->subscribed_events[$event_name][] = array('plugin' => $plugin_name, 'function' => $function_name);
			$app->log("Registered the function '$function_name' in the plugin '$plugin_name' for event '$event_name'.",LOGLEVEL_DEBUG);
		}
	}
	
	
	function raiseEvent($event_name,$data) {
		global $app;
		
		// Get the subscriptions for this event
		$events = $this->subscribed_events[$event_name];
		$app->log("Raised event: '$event_name'",LOGLEVEL_DEBUG);
		
		if(is_array($events)) {
			foreach($events as $event) {
				$plugin_name = $event["plugin"];
				$function_name = $event["function"];
				// Call the processing function of the plugin
				$app->log("Call function '$function_name' in plugin '$plugin_name' raised by event '$event_name'.",LOGLEVEL_DEBUG);
				call_user_method($function_name,$app->loaded_plugins[$plugin_name],$event_name,$data);
				unset($plugin_name);
				unset($function_name);
			}
		}
		unset($event);
		unset($events);
	}
	
}

?>