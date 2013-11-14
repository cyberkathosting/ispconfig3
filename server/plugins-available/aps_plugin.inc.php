<?php
/*
Copyright (c) 2012, ISPConfig UG
Contributors: web wack creations,  http://www.web-wack.at
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

if(defined('ISPC_ROOT_PATH')) include_once ISPC_ROOT_PATH.'/lib/classes/aps_installer.inc.php';
//require_once(ISPC_ROOT_PATH.'/lib/classes/class.installer.php');

class aps_plugin
{
	public $plugin_name = 'aps_plugin';
	public $class_name = 'aps_plugin';

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

	/**
	 * This method gets called when the plugin is loaded
	 */


	public function onLoad()
	{
		global $app;

		// Register the available events
		$app->plugins->registerEvent('aps_instance_insert', $this->plugin_name, 'install');
		$app->plugins->registerEvent('aps_instance_update', $this->plugin_name, 'install');
		$app->plugins->registerEvent('aps_instance_delete', $this->plugin_name, 'delete');
	}



	/**
	 * (Re-)install a package
	 */
	public function install($event_name, $data)
	{
		global $app, $conf;

		//* dont run the installer on a mirror server to prevent
		//  that the pplication gets installed twice.
		if($conf['mirror_server_id'] > 0) return true;

		$app->log("Starting APS install", LOGLEVEL_DEBUG);
		if(!isset($data['new']['id'])) return false;
		$instanceid = $data['new']['id'];

		if($data['new']['instance_status'] == INSTANCE_INSTALL) {
			$aps = new ApsInstaller($app);
			$app->log("Running installHandler", LOGLEVEL_DEBUG);
			$aps->installHandler($instanceid, 'install');
		}

		if($data['new']['instance_status'] == INSTANCE_REMOVE) {
			$aps = new ApsInstaller($app);
			$app->log("Running installHandler", LOGLEVEL_DEBUG);
			$aps->installHandler($instanceid, 'delete');
		}
	}



	/**
	 * Update an existing instance (currently unused)
	 */
	/*
    public function update($event_name, $data)
    {
    }
	*/



	/**
	 * Uninstall an instance
	 */
	public function delete($event_name, $data)
	{
		global $app, $conf;

		if(!isset($data['new']['id'])) return false;
		$instanceid = $data['new']['id'];

		if($data['new']['instance_status'] == INSTANCE_REMOVE) {
			$aps = new ApsInstaller($app);
			$aps->installHandler($instanceid, 'install');
		}
	}

}

?>
