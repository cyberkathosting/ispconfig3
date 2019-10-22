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

class web_module {

	var $module_name = 'web_module';
	var $class_name = 'web_module';
	var $actions_available = array( 'web_domain_insert',
		'web_domain_update',
		'web_domain_delete',
		'ftp_user_insert',
		'ftp_user_update',
		'ftp_user_delete',
		'shell_user_insert',
		'shell_user_update',
		'shell_user_delete',
		'webdav_user_insert',
		'webdav_user_update',
		'webdav_user_delete',
		'web_folder_insert',
		'web_folder_update',
		'web_folder_delete',
		'web_folder_user_insert',
		'web_folder_user_update',
		'web_folder_user_delete',
		'web_backup_insert',
		'web_backup_update',
		'web_backup_delete',
		'aps_instance_insert',
		'aps_instance_update',
		'aps_instance_delete',
		'aps_instance_setting_insert',
		'aps_instance_setting_update',
		'aps_instance_setting_delete',
		'aps_package_insert',
		'aps_package_update',
		'aps_package_delete',
		'aps_setting_insert',
		'aps_setting_update',
		'aps_setting_delete');

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['web'] == true) {
			return true;
		} else {
			return false;
		}

	}

	/*
	 	This function is called when the module is loaded
	*/

	function onLoad() {
		global $app;

		/*
		Annonce the actions that where provided by this module, so plugins
		can register on them.
		*/

		$app->plugins->announceEvents($this->module_name, $this->actions_available);

		/*
		As we want to get notified of any changes on several database tables,
		we register for them.

		The following function registers the function "functionname"
 		to be executed when a record for the table "dbtable" is
 		processed in the sys_datalog. "classname" is the name of the
 		class that contains the function functionname.
		*/

		$app->modules->registerTableHook('web_domain', 'web_module', 'process');
		$app->modules->registerTableHook('ftp_user', 'web_module', 'process');
		$app->modules->registerTableHook('shell_user', 'web_module', 'process');
		$app->modules->registerTableHook('webdav_user', 'web_module', 'process');
		$app->modules->registerTableHook('web_folder', 'web_module', 'process');
		$app->modules->registerTableHook('web_folder_user', 'web_module', 'process');
		$app->modules->registerTableHook('web_backup', 'web_module', 'process');
		$app->modules->registerTableHook('aps_instances', 'web_module', 'process');
		$app->modules->registerTableHook('aps_instances_settings', 'web_module', 'process');
		$app->modules->registerTableHook('aps_packages', 'web_module', 'process');
		$app->modules->registerTableHook('aps_settings', 'web_module', 'process');

		// Register service
		$app->services->registerService('httpd', 'web_module', 'restartHttpd');
		$app->services->registerService('php-fpm', 'web_module', 'restartPHP_FPM');

	}

	/*
	 This function is called when a change in one of the registered tables is detected.
	 The function then raises the events for the plugins.
	*/

	function process($tablename, $action, $data) {
		global $app;

		switch ($tablename) {
		case 'web_domain':
			if($action == 'i') $app->plugins->raiseEvent('web_domain_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('web_domain_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('web_domain_delete', $data);
			break;
		case 'ftp_user':
			if($action == 'i') $app->plugins->raiseEvent('ftp_user_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('ftp_user_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('ftp_user_delete', $data);
			break;
		case 'shell_user':
			if($action == 'i') $app->plugins->raiseEvent('shell_user_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('shell_user_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('shell_user_delete', $data);
			break;
		case 'webdav_user':
			if($action == 'i') $app->plugins->raiseEvent('webdav_user_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('webdav_user_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('webdav_user_delete', $data);
			break;
		case 'web_folder':
			if($action == 'i') $app->plugins->raiseEvent('web_folder_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('web_folder_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('web_folder_delete', $data);
			break;
		case 'web_folder_user':
			if($action == 'i') $app->plugins->raiseEvent('web_folder_user_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('web_folder_user_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('web_folder_user_delete', $data);
			break;
		case 'web_backup':
			if($action == 'i') $app->plugins->raiseEvent('web_backup_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('web_backup_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('web_backup_delete', $data);
			break;
		case 'aps_instances':
			if($action == 'i') $app->plugins->raiseEvent('aps_instance_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('aps_instance_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('aps_instance_delete', $data);
			break;
		case 'aps_instances_settings':
			if($action == 'i') $app->plugins->raiseEvent('aps_instance_setting_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('aps_instance_setting_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('aps_instance_setting_delete', $data);
			break;
		case 'aps_packages':
			if($action == 'i') $app->plugins->raiseEvent('aps_package_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('aps_package_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('aps_package_delete', $data);
			break;
		case 'aps_settings':
			if($action == 'i') $app->plugins->raiseEvent('aps_setting_insert', $data);
			if($action == 'u') $app->plugins->raiseEvent('aps_setting_update', $data);
			if($action == 'd') $app->plugins->raiseEvent('aps_setting_delete', $data);
			break;
		} // end switch
	} // end function


	// This function is used
	function restartHttpd($action = 'restart') {
		global $app, $conf;

		// load the server configuration options
		$app->uses('getconf,system');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		$daemon = '';
		switch ($web_config['server_type']) {
		case 'nginx':
			$daemon = $web_config['server_type'];
			break;
		default:
			if (!empty($web_config['apache_init_script'])) {
				$daemon = $web_config['apache_init_script'];
			} elseif(is_file($conf['init_scripts'] . '/' . 'httpd') || is_dir('/etc/httpd')) {
				$daemon = 'httpd';
			} else {
				$daemon = 'apache2';
			}
		}

		$retval = array('output' => '', 'retval' => 0);
		if($action == 'restart') {
			$cmd = $app->system->getinitcommand($daemon, 'restart');
		} elseif($action == 'force-reload') {
			$cmd = $app->system->getinitcommand($daemon, 'force-reload');
		} else {
			$cmd = $app->system->getinitcommand($daemon, 'reload');
		}
		
		if($web_config['server_type'] == 'nginx'){
			$app->log("Checking nginx configuration...", LOGLEVEL_DEBUG);
			exec('nginx -t 2>&1', $retval['output'], $retval['retval']);
			if($retval['retval'] == 0){
				$app->log("nginx configuration ok!", LOGLEVEL_DEBUG);
			} else {
				$app->log("nginx config test failed!", LOGLEVEL_DEBUG);
				return $retval;
			}
		}
		
		exec($cmd.' 2>&1', $retval['output'], $retval['retval']);
		
		// if restart failed despite successful syntax check => try again
		if($web_config['server_type'] == 'nginx' && $retval['retval'] > 0){
			sleep(2);
			exec($cmd.' 2>&1', $retval['output'], $retval['retval']);
		}
		$app->log("Restarting httpd: $cmd", LOGLEVEL_DEBUG);
		
		// nginx: do a syntax check because on some distributions, the init script always returns 0 - even if the syntax is not ok (how stupid is that?)
		//if($web_config['server_type'] == 'nginx' && $retval['retval'] == 0){
			//exec('nginx -t 2>&1', $retval['output'], $retval['retval']);
		//}
		return $retval;
	}

	function restartPHP_FPM($action = 'restart') {
		global $app, $conf;

		// load the server configuration options
		$app->uses('getconf,system');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		list($action, $init_script) = explode(':', $action);

		if(!$init_script){
			//$init_script = $conf['init_scripts'].'/'.$web_config['php_fpm_init_script'];
			$initcommand = $app->system->getinitcommand($web_config['php_fpm_init_script'], $action);
		} else {
			$path_parts = pathinfo($init_script);
			$initcommand = $app->system->getinitcommand($path_parts['basename'], $action, $path_parts['dirname']);
			
			if($action == 'reload' && $init_script == $conf['init_scripts'].'/'.$web_config['php_fpm_init_script']) {
				// we have to do a workaround because of buggy ubuntu fpm reload handling
				// @see: https://bugs.launchpad.net/ubuntu/+source/php5/+bug/1242376
				if(file_exists('/etc/os-release')) {
					$tmp = file_get_contents('/etc/os-release');
					//if(preg_match('/^ID=ubuntu/m', $tmp) && preg_match('/^VERSION_ID="14\.04"/m', $tmp)) {
					if(preg_match('/^ID=ubuntu/m', $tmp) && preg_match('/^VERSION_ID="14\.04"/m', $tmp) && stristr(phpversion(), 'deb.sury.org') === false) {
						$initcommand = '/sbin/start-stop-daemon --stop --signal USR2 --quiet --pidfile /var/run/php5-fpm.pid --name php5-fpm';
					}
					// And the next workaround, php-fpm reloads in centos 7 downt work as well.
                                        /*
					if(preg_match('/^ID=centos/m', $tmp) && preg_match('/^VERSION_ID="7"/m', $tmp)) {
						$initcommand = 'systemctl restart php-fpm.service';
					}
                                        */
					unset($tmp);
				}	
			}
			/*
			if($action == 'reload') {
				// And the next workaround, php-fpm reloads in centos 7 downt work as well.
				if(file_exists('/etc/os-release')) {
					$tmp = file_get_contents('/etc/os-release');
					// And the next workaround, php-fpm reloads in centos 7 downt work as well.
					if(preg_match('/^ID="centos"/m', $tmp) && preg_match('/^VERSION_ID="7"/m', $tmp)) {
						$initcommand = 'systemctl restart php-fpm.service';
					}
					unset($tmp);
				}
			}
                        */
		}

		$retval = array('output' => '', 'retval' => 0);
		exec($initcommand.' 2>&1', $retval['output'], $retval['retval']);
		$app->log("Restarting php-fpm: $initcommand", LOGLEVEL_DEBUG);
		return $retval;
	}

} // end class

?>
