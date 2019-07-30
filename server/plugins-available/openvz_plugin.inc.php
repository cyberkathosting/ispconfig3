<?php

/*
Copyright (c) 2011-2012, Till Brehm, projektfarm Gmbh
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

class openvz_plugin {

	var $plugin_name = 'openvz_plugin';
	var $class_name  = 'openvz_plugin';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		//* this is only true on openvz host servers, not in openvz guests
		if(@file_exists('/proc/vz/version')) {
			return true;
		} else {
			return false;
		}

	}


	/*
	 	This function is called when the plugin is loaded
	*/

	function onLoad() {
		global $app;

		/*
		Register for the events
		*/

		//* Virtual machine
		$app->plugins->registerEvent('openvz_vm_insert', $this->plugin_name, 'vm_insert');
		$app->plugins->registerEvent('openvz_vm_update', $this->plugin_name, 'vm_update');
		$app->plugins->registerEvent('openvz_vm_delete', $this->plugin_name, 'vm_delete');

		//* Register for actions
		$app->plugins->registerAction('openvz_start_vm', $this->plugin_name, 'actions');
		$app->plugins->registerAction('openvz_stop_vm', $this->plugin_name, 'actions');
		$app->plugins->registerAction('openvz_restart_vm', $this->plugin_name, 'actions');
		$app->plugins->registerAction('openvz_create_ostpl', $this->plugin_name, 'actions');



	}


	function vm_insert($event_name, $data) {
		global $app, $conf;

		$veid = intval($data['new']['veid']);

		if($veid == 0) {
			$app->log("VEID = 0, we stop here.", LOGLEVEL_WARN);
			return;
		}

		$tmp = $app->db->queryOneRecord("SELECT template_file FROM openvz_ostemplate WHERE ostemplate_id = ?", $data['new']['ostemplate_id']);
		$ostemplate = $tmp['template_file'];
		unset($tmp);

		//* Create the virtual machine
		$app->system->exec_safe("vzctl create ? --ostemplate ?", $veid, $ostemplate);
		$app->log("Create OpenVZ VM: vzctl create $veid --ostemplate $ostemplate", LOGLEVEL_DEBUG);

		//* Write the configuration of the VM
		file_put_contents('/etc/vz/conf/'.$veid.'.conf', $data['new']['config']);

		//* Start the VM
		if($data['new']['active'] == 'y') {
			exec("vzctl start $veid");
			$app->log("Starting OpenVZ VM: vzctl start $veid", LOGLEVEL_DEBUG);
		}

		//* Set the root password in the virtual machine
		$app->system->exec_safe("vzctl set ? --userpasswd root:?", $veid, $data['new']['vm_password']);

	}

	function vm_update($event_name, $data) {
		global $app, $conf;

		$veid = intval($data['new']['veid']);

		if($veid == 0) {
			$app->log("VEID = 0, we stop here.", LOGLEVEL_WARN);
			return;
		}

		//* Write the configuration of the VM
		file_put_contents('/etc/vz/conf/'.$veid.'.conf', $data['new']['config']);
		$app->log("Writing new configuration for $veid", LOGLEVEL_DEBUG);

		//* new diskspace for ploop-containers requieres "vzctl set"
		if($data['new']['diskspace'] != $data['old']['diskspace']) {
			escapeshell("vzctl set ? --diskspace ? --save", $veid, $data['new']['diskspace']."G");
		}

		//* Apply config changes to the VM
		if($data['new']['active'] == 'y' && $data['old']['active'] == 'y') {
			exec("vzctl restart $veid");
			$app->log("Restarting OpenVZ VM: vzctl restart $veid", LOGLEVEL_DEBUG);
		} elseif ($data['new']['active'] == 'y' && $data['old']['active'] == 'n') {
			exec("vzctl start $veid");
			$app->log("Starting OpenVZ VM: vzctl start $veid", LOGLEVEL_DEBUG);
		} elseif ($data['new']['active'] == 'n' && $data['old']['active'] == 'y') {
			exec("vzctl stop $veid");
			$app->log("Stopping OpenVZ VM: vzctl stop $veid", LOGLEVEL_DEBUG);
		}

		//* Set the root password in the virtual machine
		if($data['new']['vm_password'] != $data['old']['vm_password']) {
			$app->system->exec_safe("vzctl set ? --userpasswd root:?", $veid, $data['new']['vm_password']);
		}


	}

	function vm_delete($event_name, $data) {
		global $app, $conf;

		$veid = intval($data['old']['veid']);

		if($veid == 0) {
			$app->log("VEID = 0, we stop here.", LOGLEVEL_WARN);
			return;
		}

		exec("vzctl stop $veid");
		exec("vzctl destroy $veid");
		$app->log("Destroying OpenVZ VM: vzctl destroy $veid", LOGLEVEL_DEBUG);

	}

	function actions($action_name, $data) {
		global $app, $conf;

		if ($action_name == 'openvz_start_vm') {
			$veid = intval($data);
			if($veid > 0) {
				exec("vzctl start $veid");
				$app->log("Start VM: vzctl start $veid", LOGLEVEL_DEBUG);
			}
			return 'ok';
		}
		if ($action_name == 'openvz_stop_vm') {
			$veid = intval($data);
			if($veid > 0) {
				exec("vzctl stop $veid");
				$app->log("Stop VM: vzctl stop $veid", LOGLEVEL_DEBUG);
			}
			return 'ok';
		}
		if ($action_name == 'openvz_restart_vm') {
			$veid = intval($data);
			if($veid > 0) {
				exec("vzctl restart $veid");
				$app->log("Restart VM: vzctl restart $veid", LOGLEVEL_DEBUG);
			}
			return 'ok';
		}
		if ($action_name == 'openvz_create_ostpl') {
			$parts = explode(':', $data);
			$veid = intval($parts[0]);
			$template_cache_dir = '/vz/template/cache/';
			$template_name = $parts[1];
			if($veid > 0 && $template_name != '' && is_dir($template_cache_dir)) {
				$command = "vzdump --suspend --compress --stdexcludes --dumpdir ? ?";
				$app->system->exec_safe($command, $template_cache_dir, $veid);
				$app->system->exec_safe("mv ?*.tgz ?", $template_cache_dir."vzdump-openvz-".$veid, $template_cache_dir.$template_name.".tar.gz");
				$app->system->exec_safe("rm -f ?*.log", $template_cache_dir."vzdump-openvz-".$veid);
			}
			$app->log("Created OpenVZ OStemplate $template_name from VM $veid", LOGLEVEL_DEBUG);
			return 'ok';
		}

	}


} // end class

?>
