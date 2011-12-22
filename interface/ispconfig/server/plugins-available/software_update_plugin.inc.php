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

class software_update_plugin {
	
	var $plugin_name = 'software_update_plugin';
	var $class_name  = 'software_update_plugin';
	
	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;
		
		return true;
		
	}
	
		
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		//* Mailboxes
		$app->plugins->registerEvent('software_update_inst_insert',$this->plugin_name,'process');
		//$app->plugins->registerEvent('software_update_inst_update',$this->plugin_name,'process');
		//$app->plugins->registerEvent('software_update_inst_delete',$this->plugin_name,'process');
		
		
	}
	
	function set_install_status($inst_id, $status) {
        global $app;
        
        $app->db->query("UPDATE software_update_inst SET status = '{$status}' WHERE software_update_inst_id = '{$inst_id}'");
        $app->dbmaster->query("UPDATE software_update_inst SET status = '{$status}' WHERE software_update_inst_id = '{$inst_id}'");
    }
    
	function process($event_name,$data) {
		global $app, $conf;
		
		//* Get the info of the package:
        $software_update_id = intval($data["new"]["software_update_id"]);
		$software_update = $app->db->queryOneRecord("SELECT * FROM software_update WHERE software_update_id = '$software_update_id'");
		$software_package = $app->db->queryOneRecord("SELECT * FROM software_package WHERE package_name = '".$app->db->quote($software_update['package_name'])."'");
		
		if($software_package['package_type'] == 'ispconfig' && !$conf['software_updates_enabled'] == true) {
			$app->log('Software Updates not enabled on this server. To enable updates, set $conf["software_updates_enabled"] = true; in config.inc.php',LOGLEVEL_WARN);
            $this->set_install_status($data["new"]["software_update_inst_id"], "failed");
			return false;
		}
		
		$installuser = '';
		if($software_package['package_type'] == 'ispconfig') {
			$installuser = '';
		} elseif ($software_package['package_type'] == 'app') {
			$installuser = 'ispapps';
		} else {
			$app->log('package_type not supported',LOGLEVEL_WARN);
            $this->set_install_status($data["new"]["software_update_inst_id"], "failed");
			return false;
		}
		
		$temp_dir = '/tmp/'.md5 (uniqid (rand()));
		$app->log("The temp dir is $temp_dir",LOGLEVEL_DEBUG);
		mkdir($temp_dir);
		if($installuser != '') exec('chown '.$installuser.' '.$temp_dir);
		
		if(!is_dir($temp_dir)) {
			$app->log("Unable to create temp directory.",LOGLEVEL_WARN);
            $this->set_install_status($data["new"]["software_update_inst_id"], "failed");
			return false;
		}
		
		$cmd = "cd $temp_dir && wget ".$software_update["update_url"];
		if($installuser == '') {
			exec($cmd);
		} else {
			exec("su -c ".escapeshellarg($cmd)." $installuser");
		}
		$app->log("Downloading the update file from: ".$software_update["update_url"],LOGLEVEL_DEBUG);
		
		$url_parts = parse_url($software_update["update_url"]);
		$update_filename = basename($url_parts["path"]);
		$app->log("The update filename is $update_filename",LOGLEVEL_DEBUG);
		
		if(is_file($temp_dir.'/'.$update_filename)) {
			
			//* Checking the md5sum
			if(md5_file($temp_dir.'/'.$update_filename) != $software_update["update_md5"]) {
				$app->log("The md5 sum of the downloaded file is incorrect. Update aborted.",LOGLEVEL_WARN);
				exec("rm -rf $temp_dir");
				$app->log("Deleting the temp directory $temp_dir",LOGLEVEL_DEBUG);
                $this->set_install_status($data["new"]["software_update_inst_id"], "failed");
				return false;
			} else {
				$app->log("md5sum of the downloaded file is verified.",LOGLEVEL_DEBUG);
			}
			
			
			//* unpacking the update
			$cmd = "cd $temp_dir && unzip $update_filename";
			if($installuser == '') {
				exec($cmd);
			} else {
				exec("su -c ".escapeshellarg($cmd)." $installuser");
			}
			
			if(is_file($temp_dir.'/setup.sh')) {
				// Execute the setup script
				exec('chmod +x '.$temp_dir.'/setup.sh');
				$app->log("Executing setup.sh file in directory $temp_dir",LOGLEVEL_DEBUG);
				$cmd = 'cd '.$temp_dir.' && ./setup.sh > package_install.log';
				if($installuser == '') {
					exec($cmd);
				} else {
					exec("su -c ".escapeshellarg($cmd)." $installuser");
				}
                
                $log_data = @file_get_contents("{$temp_dir}/package_install.log");
                if(preg_match("'.*\[OK\]\s*$'is", $log_data)) {
                    $app->log("Installation successful",LOGLEVEL_DEBUG);
                    $app->log($log_data,LOGLEVEL_DEBUG);
                    $this->set_install_status($data["new"]["software_update_inst_id"], "installed");
                } else {
                    $app->log("Installation failed:\n\n" . $log_data,LOGLEVEL_WARN);
                    $this->set_install_status($data["new"]["software_update_inst_id"], "failed");
                }
			} else {
				$app->log("setup.sh file not found",LOGLEVEL_ERROR);
                $this->set_install_status($data["new"]["software_update_inst_id"], "failed");
			}
		} else {
			$app->log("Download of the update file failed",LOGLEVEL_WARN);
            $this->set_install_status($data["new"]["software_update_inst_id"], "failed");
		}
		
		exec("rm -rf $temp_dir");
		$app->log("Deleting the temp directory $temp_dir",LOGLEVEL_DEBUG);
	}
	

} // end class

?>