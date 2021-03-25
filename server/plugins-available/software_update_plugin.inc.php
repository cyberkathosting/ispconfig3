<?php

/*
Copyright (c) 2007-2012, Till Brehm, projektfarm Gmbh, Oliver Vogel www.muv.com
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

class software_update_plugin {

	var $plugin_name = 'software_update_plugin';
	var $class_name  = 'software_update_plugin';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	public function onInstall() {
		global $conf;

		return true;
	}

	/*
	 	This function is called when the plugin is loaded
	*/

	public function onLoad() {
		global $app;
		//* Register for actions
		$app->plugins->registerAction('os_update', $this->plugin_name, 'os_update');
	}

	//* Operating system update
	public function os_update($action_name, $data) {
		global $app;

		//** Debian and compatible Linux distributions
		if(file_exists('/etc/debian_version')) {
			exec("apt-get update");
			exec("apt-get upgrade -y");
			$app->log('Execeuted Debian / Ubuntu update', LOGLEVEL_DEBUG);
		}

		//** Redhat, CentOS, Fedora
		if(file_exists('/etc/redhat-release')) {
			exec("which dnf &> /dev/null && dnf -y update || yum -y update");
		}

		//** Gentoo Linux
		if(file_exists('/etc/gentoo-release')) {
			exec("glsa-check -f --nocolor affected");
			$app->log('Execeuted Gentoo update', LOGLEVEL_DEBUG);
		}

		return 'ok';
	}

} // end class

?>
