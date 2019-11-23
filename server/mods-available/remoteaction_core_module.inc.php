<?php
/*
Copyright (c) 2007-2010, Till Brehm, projektfarm Gmbh, Oliver Vogel www.muv.com
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

class remoteaction_core_module {
	var $module_name = 'remoteaction_core_module';
	var $class_name = 'remoteaction_core_module';
	/* No actions at this time. maybe later... */
	var $actions_available = array();
	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		return false;
	}

	/*
        This function is called when the module is loaded
	*/
	function onLoad() {
		/*
       	 * Check for actions to execute
		*/
		//* This module has been replaced by the new action framework.
		// $this->_execActions();
	}

	/*
     This function is called when a change in one of the registered tables is detected.
     The function then raises the events for the plugins.
	*/
	function process($tablename, $action, $data) {
		// not needed
	} // end function

	private function _actionDone($id, $state) {
		/*
		 * First set the state
		 */
		global $app;
		$sql = "UPDATE sys_remoteaction SET action_state = ? WHERE action_id = ?";
		$app->dbmaster->query($sql, $state, $id);

		/*
		 * Then save the maxid for the next time...
		 */
		$fp = fopen(dirname(__FILE__) .  "/../lib/remote_action.inc.php", 'wb');
		$content = '<?php' . "\n" . '$maxid_remote_action = ' . $id . ';' . "\n?>";
		fwrite($fp, $content);
		fclose($fp);
	}


	/**
	 * This method searches for scheduled actions and exec then
	 */


	private function _execActions() {
		global $app;
		global $conf;

		/* the id of the server as int */
		$server_id = intval($conf["server_id"]);

		/*
		 * First we (till and i, oliver) thought, it was enough to write
		 * "select from where action_status = 'pending'" and then execute this actions.
		 * But it is not!
		 * If a hacker can hack into a server, she can change the valus of action_status
		 * and so re-exec a action, executed some days bevore. So she can (for example)
		 * stop a service, a admin stopped some days before! To avoid this, we ignore
		 * the status (it is only for the interface to show) and use our own maxid
		*/
		include_once SCRIPT_PATH."/lib/remote_action.inc.php";

		/*
		 * Get all actions this server should execute
		*/
		$sql = "SELECT action_id, action_type, action_param FROM sys_remoteaction WHERE server_id = ? AND action_id > ? ORDER BY action_id";
		$actions = $app->dbmaster->queryAllRecords($sql, $server_id, $maxid_remote_action);

		/*
		 * process all actions
		*/
		if(is_array($actions)) {
			foreach ($actions as $action) {
				if ($action['action_type'] == 'os_update') {
					/* do the update */
					$this->_doOsUpdate($action);
					/* this action takes so much time,
					* we stop executing the actions not to waste more time */
					return;
				}

				if ($action['action_type'] == 'ispc_update') {
					/* do the update */
					// Update function has been removed
					// $this->_doIspCUpdate($action);
					/* this action takes so much time,
					* we stop executing the actions not to waste more time */
					$this->_actionDone($action['action_id'], 'ok');
				}
				if ($action['action_type'] == 'openvz_start_vm') {
					$veid = intval($action['action_param']);
					if($veid > 0) {
						exec("vzctl start $veid");
					}
					$this->_actionDone($action['action_id'], 'ok');
				}
				if ($action['action_type'] == 'openvz_stop_vm') {
					$veid = intval($action['action_param']);
					if($veid > 0) {
						exec("vzctl stop $veid");
					}
					$this->_actionDone($action['action_id'], 'ok');
				}
				if ($action['action_type'] == 'openvz_restart_vm') {
					$veid = intval($action['action_param']);
					if($veid > 0) {
						exec("vzctl restart $veid");
					}
					$this->_actionDone($action['action_id'], 'ok');
				}
				if ($action['action_type'] == 'openvz_create_ostpl') {
					$parts = explode(':', $action['action_param']);
					$veid = intval($parts[0]);
					$template_cache_dir = '/vz/template/cache/';
					$template_name = $parts[1];
					if($veid > 0 && $template_name != '' && is_dir($template_cache_dir)) {
						$command = "vzdump --suspend --compress --stdexcludes --dumpdir ? ?";
						$app->system->exec_safe($command, $template_cache_dir, $veid);
						$app->system->exec_safe("mv ?*.tgz ?", $template_cache_dir."vzdump-openvz-".$veid, $template_cache_dir.$template_name.".tar.gz");
						$app->system->exec_safe("rm -f ?*.log", $template_cache_dir."vzdump-openvz-".$veid);
					}
					$this->_actionDone($action['action_id'], 'ok');
					/* this action takes so much time,
					* we stop executing the actions not to waste more time */
					return;
				}


			}
		}
	}

	private function _doOsUpdate($action) {
		/*
		 * Do the update
		 */
		exec("aptitude update");
		exec("aptitude safe-upgrade -y");

		//TODO : change this when distribution information has been integrated into server record
		if(file_exists('/etc/gentoo-release')) {
			exec("glsa-check -f --nocolor affected");
		}
		else {
			exec("aptitude update");
			exec("aptitude safe-upgrade -y");
		}

		/*
		 * All well done!
		 */
		$this->_actionDone($action['action_id'], 'ok');
	}

	private function _doIspCUpdate($action) {
		global $app;
		
		// Ensure that this code is not executed twice as this would cause a loop in case of a failure
		$this->_actionDone($action['action_id'], 'ok');

		/*
		 * Get the version-number of the newest version
		 */
		$new_version = @file_get_contents('http://www.ispconfig.org/downloads/ispconfig3_version.txt');
		$new_version = trim($new_version);

		/*
		 * Do the update
		 */

		/* jump into the temporary dir */
		$oldDir = getcwd();
		chdir("/tmp");

		/* delete the old files (if there are any...) */
		$app->system->exec_safe("rm ?", "/tmp/ISPConfig-" . $new_version . ".tar.gz");
		exec("rm /tmp/ispconfig3_install -R");

		/* get the newest version */
		$app->system->exec_safe("wget ?", "http://www.ispconfig.org/downloads/ISPConfig-" . $new_version . ".tar.gz");

		/* extract the files */
		$app->system->exec_safe("tar xvfz ?", "ISPConfig-" . $new_version . ".tar.gz");

		/*
		 * Initialize the automated update
		 * (the update is then done next start of server.sh
		 */
		chdir("/tmp/ispconfig3_install/install");
		exec("touch autoupdate");

		/*
		 * do some clean-up
		 */
		$app->system->exec_safe("rm ?", "/tmp/ISPConfig-" . $new_version . ".tar.gz");

		/*
		 * go back to the "old path"
		 */
		chdir($oldDir);

		/*
		 * All well done!
		 */
		//$this->_actionDone($action['action_id'], 'ok');
	}

}

?>
