<?php

/*
Copyright (c) 2007-2012, Till Brehm, projektfarm Gmbh
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
	var $subscribed_actions = array();
	var $debug = false;

	/*
	 This function is called to load the plugins from the plugins-enabled or the plugins-core folder
	*/

	function loadPlugins($type) {
		global $app, $conf;

		$subPath = 'plugins-enabled';
		//if ($type == 'core') $subPath = 'plugins-core';

		$plugins_dir = $conf['rootpath'].$conf['fs_div'].$subPath.$conf['fs_div'];
		$tmp_plugins = array();

		if (is_dir($plugins_dir)) {
			if ($dh = opendir($plugins_dir)) {
				//** Go trough all files in the plugin dir
				while (($file = readdir($dh)) !== false) {
					if($file != '.' && $file != '..' && substr($file, -8, 8) == '.inc.php') {
						$plugin_name = substr($file, 0, -8);
						$tmp_plugins[$plugin_name] = $file;
					}
				}
				//** sort the plugins by name
				ksort($tmp_plugins);

				//** load the plugins
				foreach($tmp_plugins as $plugin_name => $file) {
					include_once $plugins_dir.$file;
					if($this->debug) $app->log('Loading plugin: '.$plugin_name, LOGLEVEL_DEBUG);
					$app->loaded_plugins[$plugin_name] = new $plugin_name;
					$app->loaded_plugins[$plugin_name]->onLoad();
				}
			} else {
				$app->log('Unable to open the plugins directory: '.$plugins_dir, LOGLEVEL_ERROR);
			}
		} else {
			$app->log('Plugins directory missing: '.$plugins_dir, LOGLEVEL_ERROR);
		}
	}

	/*
		This function is used by the modules to announce which events they provide
	*/

	function announceEvents($module_name, $events) {
		global $app;
		foreach($events as $event_name) {
			$this->available_events[$event_name] = $module_name;
			if($this->debug) $app->log('Announced event: '.$event_name, LOGLEVEL_DEBUG);
		}
	}


	/*
	 This function is called by the plugin to register for an event
	*/

	function registerEvent($event_name, $plugin_name, $function_name) {
		global $app;
		if(!isset($this->available_events[$event_name])) {
			$app->log("Unable to register function '$function_name' from plugin '$plugin_name' for event '$event_name'", LOGLEVEL_DEBUG);
		} else {
			$this->subscribed_events[$event_name][] = array('plugin' => $plugin_name, 'function' => $function_name);
			if($this->debug)  $app->log("Registered function '$function_name' from plugin '$plugin_name' for event '$event_name'.", LOGLEVEL_DEBUG);
		}
	}


	function raiseEvent($event_name, $data) {
		global $app;

		// Get the subscriptions for this event
		$events = (isset($this->subscribed_events[$event_name]))?$this->subscribed_events[$event_name]:'';
		if($this->debug) $app->log('Raised event: '.$event_name, LOGLEVEL_DEBUG);

		if(is_array($events)) {
			foreach($events as $event) {
				$plugin_name = $event['plugin'];
				$function_name = $event['function'];
				// Call the processing function of the plugin
				$app->log("Calling function '$function_name' from plugin '$plugin_name' raised by event '$event_name'.", LOGLEVEL_DEBUG);
				// call_user_method($function_name,$app->loaded_plugins[$plugin_name],$event_name,$data);
				call_user_func(array($app->loaded_plugins[$plugin_name], $function_name), $event_name, $data);
				unset($plugin_name);
				unset($function_name);
			}
		}
		unset($event);
		unset($events);
	}

	/*
	 This function is called by the plugin to register for an action
	*/

	function registerAction($action_name, $plugin_name, $function_name) {
		global $app;
		$this->subscribed_actions[$action_name][] = array('plugin' => $plugin_name, 'function' => $function_name);
		if($this->debug)  $app->log("Registered function '$function_name' from plugin '$plugin_name' for action '$action_name'.", LOGLEVEL_DEBUG);
	}


	function raiseAction($action_name, $data, $return_data = false) {
		global $app;

		//* Get the subscriptions for this action
		$actions = (isset($this->subscribed_actions[$action_name]))?$this->subscribed_actions[$action_name]:'';
		if($this->debug) $app->log('Raised action: '.$action_name, LOGLEVEL_DEBUG);

		$result = '';

		if(is_array($actions)) {
			foreach($actions as $action) {
				$plugin_name = $action['plugin'];
				$function_name = $action['function'];
				$state_out = 'ok';
				//* Call the processing function of the plugin
				$app->log("Calling function '$function_name' from plugin '$plugin_name' raised by action '$action_name'.", LOGLEVEL_DEBUG);
				$state = call_user_func(array($app->loaded_plugins[$plugin_name], $function_name), $action_name, $data);
				//* ensure that we return the highest warning / error level if a error occured in one of the functions
				if($return_data) {
					if($state) $result .= $state;
				} else {
					if($state == 'warning' && $state_out != 'error') $state_out = 'warning';
					elseif($state == 'error') $state_out = 'error';
				}
				
				unset($plugin_name);
				unset($function_name);
			}
		}
		unset($action);
		unset($actions);

		if($return_data == true) return $result;
		else return $state_out;
	}

}

?>
