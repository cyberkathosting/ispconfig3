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

class apache2_plugin {
	
	var $plugin_name = 'apache2_plugin';
	var $class_name = 'apache2_plugin';
	
		
	/*
	 	This function is called when the plugin is loaded
	*/
	
	function onLoad() {
		global $app;
		
		/*
		Register for the events
		*/
		
		$app->plugins->registerEvent('web_domain_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('web_domain_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('web_domain_delete',$this->plugin_name,'delete');
		
	}
	
	function insert($event_name,$data) {
		global $app, $conf;
		
		// just run the update function
		$this->update($event_name,$data);
		
		
	}
	
	
	function update($event_name,$data) {
		global $app, $conf;
		
		
		if($data["new"]["type"] != "vhost" && $data["new"]["parent_domain_id"] > 0) {
			// This is not a vhost, so we need to update the parent record instead.
			$parent_domain_id = intval($data["new"]["parent_domain_id"]);
			$tmp = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".$parent_domain_id." AND active = 'y'");
			$data["new"] = $tmp;
			$data["old"] = $tmp;
		}
		
		
		// load the server configuration options
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');
		
		if($data["new"]["document_root"] == '') {
			$app->log("document_root not set",LOGLEVEL_WARN);
			return 0;
		}
		if($data["new"]["system_user"] == 'root' or $data["new"]["system_group"] == 'root') {
			$app->log("Websites can not be owned by the root user or group.",LOGLEVEL_WARN);
			return 0;
		}
		
		//print_r($data);
		
		// Check if the directories are there and create them if nescessary.
		if(!is_dir($data["new"]["document_root"]."/web")) exec("mkdir -p ".$data["new"]["document_root"]."/web");
		if(!is_dir($data["new"]["document_root"]."/log")) exec("mkdir -p ".$data["new"]["document_root"]."/log");
		if(!is_dir($data["new"]["document_root"]."/ssl")) exec("mkdir -p ".$data["new"]["document_root"]."/ssl");
		if(!is_dir($data["new"]["document_root"]."/cgi-bin")) exec("mkdir -p ".$data["new"]["document_root"]."/cgi-bin");
		
		// TODO: Create the symlinks
		
		
		// Create group and user, if not exist
		$app->uses("system");
		
		$groupname = escapeshellcmd($data["new"]["system_group"]);
		if($data["new"]["system_group"] != '' && !$app->system->is_group($data["new"]["system_group"])) {
			exec("groupadd $groupname");
			$app->log("Adding the group: $groupname",LOGLEVEL_DEBUG);
		}
		
		$username = escapeshellcmd($data["new"]["system_user"]);
		if($data["new"]["system_user"] != '' && !$app->system->is_user($data["new"]["system_user"])) {
			exec("useradd -d ".escapeshellcmd($data["new"]["document_root"])." -g $groupname $username");
			$app->log("Adding the user: $username",LOGLEVEL_DEBUG);
		}
		
		// Set the quota for the user
		if($username != '' && $app->system->is_user($username)) {
			if($data["new"]["hd_quota"] > 0){
    			$blocks_soft = $data["new"]["hd_quota"] * 1024;
    			$blocks_hard = $blocks_soft + 1024;
  			} else {
    			$blocks_soft = $blocks_hard = 0;
  			}
			exec("setquota -u $username $blocks_soft $blocks_hard 0 0 -a &> /dev/null");
			exec("setquota -T -u $username 604800 604800 -a &> /dev/null");
		}
		
		
		
		// Chown and chmod the directories
		exec("chown -R $username:$groupname ".escapeshellcmd($data["new"]["document_root"]));
		
		// Create the vhost config file
		$app->load('tpl');
		
		$tpl = new tpl();
		$tpl->newTemplate("vhost.conf.master");
		
		$vhost_data = $data["new"];
		$vhost_data["web_document_root"] = $data["new"]["document_root"]."/web";
		//$vhost_data["document_root"] = $data["new"]["document_root"]."/web";
		$tpl->setVar($vhost_data);
		
		// Rewrite rules
		$rewrite_rules = array();
		if($data["new"]["redirect_type"] != '') {
			$rewrite_rules[] = array(	'rewrite_domain' 	=> $data["new"]["domain"],
										'rewrite_type' 		=> $data["new"]["redirect_type"],
										'rewrite_target' 	=> $data["new"]["redirect_path"]);
		}
		
		// get alias domains (co-domains and subdomains)
		$aliases = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE parent_domain_id = ".$data["new"]["domain_id"]." AND active = 'y'");
		$server_alias = '';
		if(is_array($aliases)) {
			foreach($aliases as $alias) {
				$server_alias .= $alias["domain"].' ';
				$app->log("Add server alias: $alias[domain]",LOGLEVEL_DEBUG);
				// Rewriting
				if($alias["redirect_type"] != '') {
					$rewrite_rules[] = array(	'rewrite_domain' 	=> $alias["domain"],
												'rewrite_type' 		=> $alias["redirect_type"],
												'rewrite_target' 	=> $alias["redirect_path"]);
				}
			}
		}
		$tpl->setVar('alias',trim($server_alias));
		if(count($rewrite_rules) > 0) {
			$tpl->setVar('rewrite_enabled',1);
		} else {
			$tpl->setVar('rewrite_enabled',0);
		}
		$tpl->setLoop('redirects',$rewrite_rules);
		
		$vhost_file = escapeshellcmd($web_config["vhost_conf_dir"].'/'.$data["new"]["domain"].'.vhost');
		file_put_contents($vhost_file,$tpl->grab());
		$app->log("Writing the vhost file: $vhost_file",LOGLEVEL_DEBUG);
		unset($tpl);
		
		// Set the symlink to enable the vhost
		$vhost_symlink = escapeshellcmd($web_config["vhost_conf_enabled_dir"].'/'.$data["new"]["domain"].'.vhost');
		if($data["new"]["active"] == 'y' && !is_link($vhost_symlink)) {
			symlink($vhost_file,$vhost_symlink);
			$app->log("Creating the symlink: $vhost_symlink => $vhost_file",LOGLEVEL_DEBUG);
		}
		
		// Remove the symlink, if site is inactive
		if($data["new"]["active"] == 'n' && is_link($vhost_symlink)) {
			unlink($vhost_symlink);
			$app->log("Removing symlink: $vhost_symlink => $vhost_file",LOGLEVEL_DEBUG);
		}
		
		// request a httpd reload when all records have been processed
		$app->services->restartServiceDelayed('httpd','reload');
		
	}
	
	function delete($event_name,$data) {
		global $app, $conf;
		
		// load the server configuration options
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');
		
		// Deleting the vhost file, symlink and the data directory
		$vhost_symlink = escapeshellcmd($web_config["vhost_conf_enabled_dir"].'/'.$data["old"]["domain"].'.vhost');
		unlink($vhost_symlink);
		$app->log("Removing symlink: $vhost_symlink => $vhost_file",LOGLEVEL_DEBUG);
		
		$vhost_file = escapeshellcmd($web_config["vhost_conf_dir"].'/'.$data["old"]["domain"].'.vhost');
		unlink($vhost_file);
		$app->log("Removing vhost file: $vhost_file",LOGLEVEL_DEBUG);
		
		$docroot = escapeshellcmd($data["old"]["document_root"]);
		if($docroot != '' && !stristr($docroot,'..')) exec("rm -rf $docroot");
		$app->log("Removing website: $docroot",LOGLEVEL_DEBUG);
		
	}
	

} // end class

?>