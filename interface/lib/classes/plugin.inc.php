<?php

/*
Copyright (c) 2010, Till Brehm, projektfarm Gmbh
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

class plugin {
	
	private $subscribed_events = array();
	private $debug = true;
	
	
	/*
	 This function is called to load the plugins from the plugins folder and update the plugin cache
	*/
	
	private function loadPluginCache() {
		global $app,$conf;
		
		
		if(isset($_SESSION['s']['plugin_cache'])) unset($_SESSION['s']['plugin_cache']);
		
		$plugins_dir = ISPC_LIB_PATH.FS_DIV.'plugins'.FS_DIV;
		$_SESSION['s']['plugin_cache'] = array();
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
					if($this->debug) $app->log("Loading Plugin: $plugin_name",LOGLEVEL_DEBUG);
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
	 This function is called by the plugin to register for an event which is saved into the plugin cache 
	 for faster lookups without the need to load all plugins for every page.
	*/
	
	public function registerEvent($event_name,$plugin_name,$function_name) {
		global $app;
		
		$_SESSION['s']['plugin_cache'][$event_name][] = array('plugin' => $plugin_name, 'function' => $function_name);
		if($this->debug) $app->log("Plugin '$plugin_name' has registered the function '$function_name' for the event '$event_name'",LOGLEVEL_DEBUG);

	}
	
	/*
		This function is called when a certian action occurs, e.g. a form gets saved or a user is logged in.
	*/
	
	public function raiseEvent($event_name,$data) {
		global $app;
		
		if(!isset($_SESSION['s']['plugin_cache'])) {
			$this->loadPluginCache();
			if($this->debug) $app->log("Loaded the plugin cache.",LOGLEVEL_DEBUG);
		}
		
		
		$sub_events = explode(':',$event_name);
		
		if(is_array($sub_events)) {
			if(count($sub_events) == 3) {
				$tmp_event = $sub_events[2];
				if($this->debug) $app->log("Called Event '$tmp_event'",LOGLEVEL_DEBUG);
				$this->callPluginEvent($tmp_event,$data);
				$tmp_event = $sub_events[0].':'.$sub_events[2];
				if($this->debug) $app->log("Called Event '$tmp_event'",LOGLEVEL_DEBUG);
				$this->callPluginEvent($tmp_event,$data);
				$tmp_event = $sub_events[0].':'.$sub_events[1].':'.$sub_events[2];
				if($this->debug) $app->log("Called Event '$tmp_event'",LOGLEVEL_DEBUG);
				$this->callPluginEvent($tmp_event,$data);
				
				/*$sub_events = array_reverse($sub_events);
				$tmp_event = '';
				foreach($sub_events as $n => $sub_event) {
					$tmp_event = ($n == 0)?$sub_event:$sub_event.':'.$tmp_event;
					if($this->debug) $app->log("Called Event '$tmp_event'",LOGLEVEL_DEBUG);
					$this->callPluginEvent($tmp_event,$data);
				}
				*/
			} else {
				if($this->debug) $app->log("Called Event '$sub_events[0]'",LOGLEVEL_DEBUG);
				$this->callPluginEvent($sub_events[0],$data);
			}
		}
		
	 } // end function raiseEvent
	 
	 //* Internal function to load the plugin and call the event function in the plugin.
	 private function callPluginEvent($event_name,$data) {
	 	global $app;
	 	
	 	//* execute the functions for the events
		if(is_array($_SESSION['s']['plugin_cache'][$event_name])) {
			foreach($_SESSION['s']['plugin_cache'][$event_name] as $rec) {
				$plugin_name = $rec['plugin'];
				$function_name = $rec['function'];
				$plugin_file = ISPC_LIB_PATH.FS_DIV.'plugins'.FS_DIV.$plugin_name.'.inc.php';
				if(is_file($plugin_file)) {
					if(!isset($app->loaded_plugins[$plugin_name])) {
						include_once($plugin_file);
						$app->loaded_plugins[$plugin_name] = new $plugin_name;
					}
					if($this->debug) $app->log("Called method: '$function_name' in plugin '$plugin_name' for event '$event_name'",LOGLEVEL_DEBUG);
					call_user_method($function_name,$app->loaded_plugins[$plugin_name],$event_name,$data);
				}
			}
			
		}
	 } // end functiom callPluginEvent
	
	
}

?>