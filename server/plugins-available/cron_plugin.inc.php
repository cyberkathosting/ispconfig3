<?php

/*
Copyright (c) 2007 - 2009, Till Brehm, projektfarm Gmbh
Modified 2009, Marius Cramer, pixcept KG
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

class cron_plugin {

	var $plugin_name = 'cron_plugin';
	var $class_name = 'cron_plugin';

	// private variables
	var $action = '';

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
	 	This function is called when the plugin is loaded
	*/

	function onLoad() {
		global $app;

		/*
		Register for the events
		*/

		$app->plugins->registerEvent('cron_insert', $this->plugin_name, 'insert');
		$app->plugins->registerEvent('cron_update', $this->plugin_name, 'update');
		$app->plugins->registerEvent('cron_delete', $this->plugin_name, 'delete');

	}

	function insert($event_name, $data) {
		global $app, $conf;

		$this->action = 'insert';
		// just run the update function
		$this->update($event_name, $data);

	}


	function update($event_name, $data) {
		global $app, $conf;

		if($this->action != 'insert') $this->action = 'update';

		// load the server configuration options
		$app->uses("getconf");

		if($data["new"]["parent_domain_id"] == '') {
			$app->log("Parent domain not set", LOGLEVEL_WARN);
			return 0;
		}

		//* get data from web
		$parent_domain = $app->db->queryOneRecord("SELECT `domain_id`, `system_user`, `system_group`, `document_root`, `hd_quota` FROM `web_domain` WHERE `domain_id` = ?", $data["new"]["parent_domain_id"]);
		if(!$parent_domain["domain_id"]) {
			$app->log("Parent domain not found", LOGLEVEL_WARN);
			return 0;
		}

		if(!$app->system->is_allowed_user($parent_domain['system_user'], true, true)
			|| !$app->system->is_allowed_group($parent_domain['system_group'], true, true)) {
			$app->log("Websites (and Crons) cannot be owned by the root user or group.", LOGLEVEL_WARN);
			return false;
		}
		
		// Get the client ID
		$client = $app->dbmaster->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ?", $data["new"]["sys_groupid"]);
		$client_id = intval($client["client_id"]);
		unset($client);

		// Create group and user, if not exist
		$app->uses("system");

		$groupname = $parent_domain["system_group"];
		if($parent_domain["system_group"] != '' && !$app->system->is_group($parent_domain["system_group"])) {
			$app->system->exec_safe("groupadd ?", $groupname);
			$app->log("Adding the group: $groupname", LOGLEVEL_DEBUG);
		}

		$username = $parent_domain["system_user"];
		if($parent_domain["system_user"] != '' && !$app->system->is_user($parent_domain["system_user"])) {
			$app->system->exec_safe("useradd -d ? -g ? ? -s /bin/false", $parent_domain["document_root"], $groupname, $username);
			$app->log("Adding the user: $username", LOGLEVEL_DEBUG);
		}
        
        // Set the quota for the user
        if($username != '' && $app->system->is_user($username)) {
           if($parent_domain['hd_quota'] > 0) {
              $blocks_soft = $parent_domain['hd_quota'] * 1024;
              $mb_soft = $parent_domain['hd_quota'];
              $blocks_hard = $blocks_soft + 1024;
              $mb_hard = $mb_soft + 1;
            } else {
              $mb_soft = $mb_hard = $blocks_soft = $blocks_hard = 0;
            }

            // get the primitive folder for document_root and the filesystem, will need it later.
            $df_output=explode(" ", $app->system->exec_safe("df -T ?|awk 'END{print \$2,\$NF}'", $parent_domain["document_root"]));
            $file_system = $df_output[0];
            $primitive_root = $df_output[1];

            if ( in_array($file_system , array('ext2','ext3','ext4'),true) ) {
              $app->system->exec_safe('setquota -u ? ? ? 0 0 -a &> /dev/null', $username, $blocks_soft, $blocks_hard);
              $app->system->exec_safe('setquota -T -u ? 604800 604800 -a &> /dev/null', $username);
            } elseif ($file_system == 'xfs') {
                
              $app->system->exec_safe("xfs_quota -x -c ? ?", "limit -u bsoft=$mb_soft" . 'm'. " bhard=$mb_hard" . 'm'. " $username", $primitive_root);

              // xfs only supports timers globally, not per user.
              $app->system->exec_safe("xfs_quota -x -c 'timer -bir -i 604800' ?", $primitive_root);

              unset($project_uid, $username_position, $xfs_projects);
              unset($primitive_root, $df_output, $mb_hard, $mb_soft);
            }
        }

		//TODO : change this when distribution information has been integrated into server record
		//* Gentoo requires a user to be part of the crontab group.
		if (file_exists('/etc/gentoo-release')) {
			if (strpos($app->system->get_user_groups($username), 'crontab') === false) {
				$app->system->add_user_to_group('crontab', $username);
			}
		}

		// make temp directory writable for the apache and website users
		$app->system->chmod($parent_domain["document_root"].'/tmp', 0777);

		/** TODO READ CRON MASTER **/


		$this->parent_domain = $parent_domain;
		$this->_write_crontab();

		$this->action = '';

	}

	function delete($event_name, $data) {
		global $app, $conf;

		//* get data from web
		$parent_domain = $app->db->queryOneRecord("SELECT `domain_id`, `system_user`, `system_group`, `document_root`, `hd_quota` FROM `web_domain` WHERE `domain_id` = ?", $data["old"]["parent_domain_id"]);
		if(!$parent_domain["domain_id"]) {
			$app->log("Parent domain not found", LOGLEVEL_WARN);
			return 0;
		}

		// Get the client ID
		$client = $app->dbmaster->queryOneRecord("SELECT client_id FROM sys_group WHERE sys_group.groupid = ?", $data["old"]["sys_groupid"]);
		$client_id = intval($client["client_id"]);
		unset($client);

		$this->parent_domain = $parent_domain;
		$this->_write_crontab();
	}

	function _write_crontab() {
		global $app, $conf;

		//* load the server configuration options
		$app->uses("getconf");

		$cron_config = $app->getconf->get_server_config($conf["server_id"], 'cron');

		//* try to find customer's mail address

		/** TODO: add possibility for client to choose mail notification! **/
		$cron_content = "MAILTO=''\n";
		$cron_content .= "SHELL='/bin/sh'\n\n";
		$chr_cron_content = "MAILTO=''\n";
		$chr_cron_content .= "SHELL='/usr/sbin/jk_chrootsh'\n\n";

		$cmd_count = 0;
		$chr_cmd_count = 0;

		//* read all active cron jobs from database and write them to file
		$cron_jobs = $app->db->queryAllRecords("SELECT c.`run_min`, c.`run_hour`, c.`run_mday`, c.`run_month`, c.`run_wday`, c.`command`, c.`type`, c.`log`, `web_domain`.`domain` as `domain` FROM `cron` as c INNER JOIN `web_domain` ON `web_domain`.`domain_id` = c.`parent_domain_id` WHERE c.`parent_domain_id` = ? AND c.`active` = 'y'", $this->parent_domain["domain_id"]);
		if($cron_jobs && count($cron_jobs) > 0) {
			foreach($cron_jobs as $job) {
				if($job['run_month'] == '@reboot') {
					$cron_line = "@reboot";
				} else {
					$cron_line = str_replace(" ", "", $job['run_min']) . "\t" . str_replace(" ", "", $job['run_hour']) . "\t" . str_replace(" ", "", $job['run_mday']) . "\t" . str_replace(" ", "", $job['run_month']) . "\t" . str_replace(" ", "", $job['run_wday']);
				}
				
				$log_target = "";
				$log_wget_target = '/dev/null';
				$log_root = '';
				if($job['log'] == 'y') {
					if($job['type'] != 'chrooted') $log_root = $this->parent_domain['document_root'];
					$log_root .= '/private';
					
					$log_target = '>>' . $log_root . '/cron.log 2>>' . $log_root . '/cron_error.log';
					$log_wget_target = $log_root . '/cron_wget.log';
				}
				
				$cron_line .= "\t{$this->parent_domain['system_user']}"; //* running as user
				if($job['type'] == 'url') {
					$cron_line .= "\t{$cron_config['wget']} --no-check-certificate --user-agent='Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0' -q -t 1 -T 7200 -O " . $log_wget_target . " " . escapeshellarg($job['command']) . " " . $log_target;
				} else {
					if(strpos($job['command'], "\n") !== false || strpos($job['command'], "\r") !== false || strpos($job['command'], chr(0)) !== false) {
						$app->log("Insecure Cron job SKIPPED: " . $job['command'], LOGLEVEL_WARN);
						continue;
					}
					
					$web_root = '';
					if($job['type'] == 'chrooted') {
						if(substr($job['command'], 0, strlen($this->parent_domain['document_root'])) == $this->parent_domain['document_root']) {
							//* delete the unneeded path part
							$job['command'] = substr($job['command'], strlen($this->parent_domain['document_root']));
						}
					} else {
						$web_root = $this->parent_domain['document_root'];
					}
					
					$web_root .= '/web';
					$job['command'] = str_replace('[web_root]', $web_root, $job['command']);

					$cron_line .= "\t";
					//if($job['type'] != 'chrooted' && substr($job['command'], 0, 1) != "/") $cron_line .= $this->parent_domain['document_root'].'/';
					$cron_line .= $job['command'] . " " . $log_target;
				}

				if($job['type'] == 'chrooted') {
					$chr_cron_content .= $cron_line . " #{$job['domain']}\n";
					$chr_cmd_count++;
				} else {
					$cron_content .= $cron_line . " #{$job['domain']}\n";
					$cmd_count++;
				}
			}
		}

		$cron_file = $cron_config["crontab_dir"].'/ispc_'.$this->parent_domain["system_user"];
		//TODO : change this when distribution information has been integrated into server record
		//* Gentoo vixie-cron requires files to end with .cron in the cron.d directory
		if (file_exists('/etc/gentoo-release')) {
			$cron_file .= '.cron';
		}

		if($cmd_count > 0) {
			$app->system->file_put_contents($cron_file, $cron_content);
			$app->log("Wrote Cron file $cron_file with content:\n$cron_content", LOGLEVEL_DEBUG);
		} else {
			$app->system->unlink($cron_file);
			$app->log("Deleted Cron file $cron_file", LOGLEVEL_DEBUG);
		}

		$cron_file = $cron_config["crontab_dir"].'/ispc_chrooted_'.$this->parent_domain["system_user"];
		if($chr_cmd_count > 0) {
			$app->system->file_put_contents($cron_file, $chr_cron_content);
			$app->log("Wrote Cron file $cron_file with content:\n$chr_cron_content", LOGLEVEL_DEBUG);
		} else {
			$app->system->unlink($cron_file);
			$app->log("Deleted Cron file $cron_file", LOGLEVEL_DEBUG);
		}

		return 0;
	}

} // end class

?>
