<?php

/*
Copyright (c) 2020, ISPConfig
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

class ISPConfigRemotingHandlerBase
{
	protected $methods = array();
	protected $classes = array();

	public function __construct()
	{
		global $app;

		// load main remoting file
		$app->load('remoting');

		// load all remoting classes and get their methods
		$this->load_remoting_classes(realpath(__DIR__) . '/remote.d/*.inc.php');

		// load all remoting classes from modules
		$this->load_remoting_classes(realpath(__DIR__) . '/../../web/*/lib/classes/remote.d/*.inc.php');

		// add main methods
		$this->methods['login'] = 'remoting';
		$this->methods['logout'] = 'remoting';
		$this->methods['get_function_list'] = 'remoting';

		// create main class
		$this->classes['remoting'] = new remoting(array_keys($this->methods));
	}

	private function load_remoting_classes($glob_pattern)
	{
		$files = glob($glob_pattern);

		foreach ($files as $file) {
			$name = str_replace('.inc.php', '', basename($file));
			$class_name = 'remoting_' . $name;

			include_once $file;
			if(class_exists($class_name, false)) {
				$this->classes[$class_name] = new $class_name();
				foreach(get_class_methods($this->classes[$class_name]) as $method) {
					$this->methods[$method] = $class_name;
				}
			}
		}
	}
}
