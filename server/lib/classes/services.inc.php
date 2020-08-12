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

class services {

	var $registered_services = array();
	var $delayed_restarts = array();
	var $debug = false;

	// This function adds a request for restarting
	// a service at the end of the configuration run.
	function restartServiceDelayed($service_name, $action = 'restart') {
		global $app;
		if(is_array($this->registered_services[$service_name])) {
			$this->delayed_restarts[$service_name] = $action;
		} else {
			$app->log("Unable to add a delayed restart for '$service_name'. Service not registered.", LOGLEVEL_WARN);
		}

	}

	// This function restarts a service when the function is called
	function restartService($service_name, $action = 'restart') {
		global $app;

		if(is_array($this->registered_services[$service_name])) {
			$module_name = $this->registered_services[$service_name]['module'];
			$function_name = $this->registered_services[$service_name]['function'];
			$app->log("Calling function '$function_name' from module '$module_name'.", LOGLEVEL_DEBUG);
			// call_user_method($function_name,$app->loaded_modules[$module_name],$action);
			return call_user_func(array($app->loaded_modules[$module_name], $function_name), $action);
		} else {
			$app->log("Unable to restart $service_name. Service not registered.", LOGLEVEL_WARNING);
			return array('output' => '', 'retval' => 0);
		}

	}

	// This function is used to register callback functions for services that can be restarted
	function registerService($service_name, $module_name, $function_name) {
		global $app;
		$this->registered_services[$service_name] = array('module' => $module_name, 'function' => $function_name);
		if($this->debug) $app->log("Registered Service '$service_name' in module '$module_name' for processing function '$function_name'", LOGLEVEL_DEBUG);
	}

	// This function is called at the end of the server script to restart services.
	function processDelayedActions() {
		global $app;
		foreach($this->delayed_restarts as $service_name => $action) {
			$this->restartService($service_name, $action);
		}
	}

}

?>
