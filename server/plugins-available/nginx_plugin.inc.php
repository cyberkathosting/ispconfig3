<?php

/*
Copyright (c) 2007 - 2009, Till Brehm, projektfarm Gmbh
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

class nginx_plugin {

	var $plugin_name = 'nginx_plugin';
	var $class_name = 'nginx_plugin';

	// private variables
	var $action = '';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['web'] == true && !@is_link('/usr/local/ispconfig/server/plugins-enabled/apache2_plugin.inc.php')) {
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
		$app->plugins->registerEvent('web_domain_insert',$this->plugin_name,'ssl');
		$app->plugins->registerEvent('web_domain_update',$this->plugin_name,'ssl');
		$app->plugins->registerEvent('web_domain_delete',$this->plugin_name,'ssl');

		$app->plugins->registerEvent('web_domain_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('web_domain_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('web_domain_delete',$this->plugin_name,'delete');

		$app->plugins->registerEvent('server_ip_insert',$this->plugin_name,'server_ip');
		$app->plugins->registerEvent('server_ip_update',$this->plugin_name,'server_ip');
		$app->plugins->registerEvent('server_ip_delete',$this->plugin_name,'server_ip');

		/*
		$app->plugins->registerEvent('webdav_user_insert',$this->plugin_name,'webdav');
		$app->plugins->registerEvent('webdav_user_update',$this->plugin_name,'webdav');
		$app->plugins->registerEvent('webdav_user_delete',$this->plugin_name,'webdav');
		*/
		
		$app->plugins->registerEvent('client_delete',$this->plugin_name,'client_delete');
		
		$app->plugins->registerEvent('web_folder_user_insert',$this->plugin_name,'web_folder_user');
		$app->plugins->registerEvent('web_folder_user_update',$this->plugin_name,'web_folder_user');
		$app->plugins->registerEvent('web_folder_user_delete',$this->plugin_name,'web_folder_user');
		
		$app->plugins->registerEvent('web_folder_update',$this->plugin_name,'web_folder_update');
		$app->plugins->registerEvent('web_folder_delete',$this->plugin_name,'web_folder_delete');
	}

	// Handle the creation of SSL certificates
	function ssl($event_name,$data) {
		global $app, $conf;

		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		if ($web_config['CA_path']!='' && !file_exists($web_config['CA_path'].'/openssl.cnf'))
			$app->log("CA path error, file does not exist:".$web_config['CA_path'].'/openssl.conf',LOGLEVEL_ERROR);	
		
		//* Only vhosts can have a ssl cert
		if($data["new"]["type"] != "vhost") return;

		if(!is_dir($data['new']['document_root'].'/ssl')) exec('mkdir -p '.$data['new']['document_root'].'/ssl');
		$ssl_dir = $data['new']['document_root'].'/ssl';
		$domain = $data['new']['ssl_domain'];
		$key_file = $ssl_dir.'/'.$domain.'.key.org';
		$key_file2 = $ssl_dir.'/'.$domain.'.key';
		$csr_file = $ssl_dir.'/'.$domain.'.csr';
		$crt_file = $ssl_dir.'/'.$domain.'.crt';

		//* Create a SSL Certificate
		if($data['new']['ssl_action'] == 'create') {
			$rand_file = $ssl_dir.'/random_file';
			$rand_data = md5(uniqid(microtime(),1));
			for($i=0; $i<1000; $i++) {
				$rand_data .= md5(uniqid(microtime(),1));
				$rand_data .= md5(uniqid(microtime(),1));
				$rand_data .= md5(uniqid(microtime(),1));
				$rand_data .= md5(uniqid(microtime(),1));
			}
			file_put_contents($rand_file, $rand_data);

			$ssl_password = substr(md5(uniqid(microtime(),1)), 0, 15);

			$ssl_cnf = "        RANDFILE               = $rand_file

        [ req ]
        default_bits           = 2048
        default_keyfile        = keyfile.pem
        distinguished_name     = req_distinguished_name
        attributes             = req_attributes
        prompt                 = no
        output_password        = $ssl_password

        [ req_distinguished_name ]
        C                      = ".$data['new']['ssl_country']."
        ST                     = ".$data['new']['ssl_state']."
        L                      = ".$data['new']['ssl_locality']."
        O                      = ".$data['new']['ssl_organisation']."
        OU                     = ".$data['new']['ssl_organisation_unit']."
        CN                     = $domain
        emailAddress           = webmaster@".$data['new']['domain']."

        [ req_attributes ]
        challengePassword              = A challenge password";

			$ssl_cnf_file = $ssl_dir.'/openssl.conf';
			file_put_contents($ssl_cnf_file,$ssl_cnf);

			$rand_file = escapeshellcmd($rand_file);
			$key_file = escapeshellcmd($key_file);
			$key_file2 = escapeshellcmd($key_file2);
			$ssl_days = 3650;
			$csr_file = escapeshellcmd($csr_file);
			$config_file = escapeshellcmd($ssl_cnf_file);
			$crt_file = escapeshellcmd($crt_file);

			if(is_file($ssl_cnf_file)) {
				
				exec("openssl genrsa -des3 -rand $rand_file -passout pass:$ssl_password -out $key_file 2048");
				exec("openssl req -new -passin pass:$ssl_password -passout pass:$ssl_password -key $key_file -out $csr_file -days $ssl_days -config $config_file");
				exec("openssl rsa -passin pass:$ssl_password -in $key_file -out $key_file2");

				if(file_exists($web_config['CA_path'].'/openssl.cnf'))
				{
					exec("openssl ca -batch -out $crt_file -config ".$web_config['CA_path']."/openssl.cnf -passin pass:".$web_config['CA_pass']." -in $csr_file");
					$app->log("Creating CA-signed SSL Cert for: $domain",LOGLEVEL_DEBUG);
					if (filesize($crt_file)==0 || !file_exists($crt_file)) $app->log("CA-Certificate signing failed.  openssl ca -out $crt_file -config ".$web_config['CA_path']."/openssl.cnf -passin pass:".$web_config['CA_pass']." -in $csr_file",LOGLEVEL_ERROR);
				};
				if (@filesize($crt_file)==0 || !file_exists($crt_file)){
					exec("openssl req -x509 -passin pass:$ssl_password -passout pass:$ssl_password -key $key_file -in $csr_file -out $crt_file -days $ssl_days -config $config_file ");
					$app->log("Creating self-signed SSL Cert for: $domain",LOGLEVEL_DEBUG);
				};
			
			}

			exec('chmod 400 '.$key_file2);
			@unlink($config_file);
			@unlink($rand_file);
			$ssl_request = $app->db->quote(file_get_contents($csr_file));
			$ssl_cert = $app->db->quote(file_get_contents($crt_file));
			/* Update the DB of the (local) Server */
			$app->db->query("UPDATE web_domain SET ssl_request = '$ssl_request', ssl_cert = '$ssl_cert' WHERE domain = '".$data['new']['domain']."'");
			$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = '".$data['new']['domain']."'");
			/* Update also the master-DB of the Server-Farm */
			$app->dbmaster->query("UPDATE web_domain SET ssl_request = '$ssl_request', ssl_cert = '$ssl_cert' WHERE domain = '".$data['new']['domain']."'");
			$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = '".$data['new']['domain']."'");
		}

		//* Save a SSL certificate to disk
		if($data["new"]["ssl_action"] == 'save') {
			$ssl_dir = $data["new"]["document_root"]."/ssl";
			$domain = ($data["new"]["ssl_domain"] != '')?$data["new"]["ssl_domain"]:$data["new"]["domain"];
			$csr_file = $ssl_dir.'/'.$domain.".csr";
			$crt_file = $ssl_dir.'/'.$domain.".crt";
			//$bundle_file = $ssl_dir.'/'.$domain.".bundle";
			if(trim($data["new"]["ssl_request"]) != '') file_put_contents($csr_file,$data["new"]["ssl_request"]);
			if(trim($data["new"]["ssl_cert"]) != '') file_put_contents($crt_file,$data["new"]["ssl_cert"]);
			// for nginx, bundle files have to be appended to the certificate file
			if(trim($data["new"]["ssl_bundle"]) != ''){				
				if(file_exists($crt_file)){
					$crt_file_contents = trim(file_get_contents($crt_file));
				} else {
					$crt_file_contents = '';
				}
				if($crt_file_contents != '') $crt_file_contents .= "\n";
				$crt_file_contents .= $data["new"]["ssl_bundle"];
				file_put_contents($crt_file,$app->file->unix_nl($crt_file_contents));
				unset($crt_file_contents);
			}
			/* Update the DB of the (local) Server */
			$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = '".$data['new']['domain']."'");
			/* Update also the master-DB of the Server-Farm */
			$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = '".$data['new']['domain']."'");
			$app->log('Saving SSL Cert for: '.$domain,LOGLEVEL_DEBUG);
		}

		//* Delete a SSL certificate
		if($data['new']['ssl_action'] == 'del') {
			$ssl_dir = $data['new']['document_root'].'/ssl';
			$domain = ($data["new"]["ssl_domain"] != '')?$data["new"]["ssl_domain"]:$data["new"]["domain"];
			$csr_file = $ssl_dir.'/'.$domain.'.csr';
			$crt_file = $ssl_dir.'/'.$domain.'.crt';
			//$bundle_file = $ssl_dir.'/'.$domain.'.bundle';
			if(file_exists($web_config['CA_path'].'/openssl.cnf'))
				{
					exec("openssl ca -batch -config ".$web_config['CA_path']."/openssl.cnf -passin pass:".$web_config['CA_pass']." -revoke $crt_file");
					$app->log("Revoking CA-signed SSL Cert for: $domain",LOGLEVEL_DEBUG);
				};
			unlink($csr_file);
			unlink($crt_file);
			//unlink($bundle_file);
			/* Update the DB of the (local) Server */
			$app->db->query("UPDATE web_domain SET ssl_request = '', ssl_cert = '' WHERE domain = '".$data['new']['domain']."'");
			$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = '".$data['new']['domain']."'");
			/* Update also the master-DB of the Server-Farm */
			$app->dbmaster->query("UPDATE web_domain SET ssl_request = '', ssl_cert = '' WHERE domain = '".$data['new']['domain']."'");
			$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = '".$data['new']['domain']."'");
			$app->log('Deleting SSL Cert for: '.$domain,LOGLEVEL_DEBUG);
		}

	}


	function insert($event_name,$data) {
		global $app, $conf;

		$this->action = 'insert';
		// just run the update function
		$this->update($event_name,$data);


	}


	function update($event_name,$data) {
		global $app, $conf;
		
		//* Check if the apache plugin is enabled
		if(@is_link('/usr/local/ispconfig/server/plugins-enabled/apache2_plugin.inc.php')) {
			$app->log('The nginx plugin can not be used together with the apache2 plugin..',LOGLEVEL_WARN);
			return 0;
		}
		
		if($this->action != 'insert') $this->action = 'update';

		if($data['new']['type'] != 'vhost' && $data['new']['parent_domain_id'] > 0) {

			$old_parent_domain_id = intval($data['old']['parent_domain_id']);
			$new_parent_domain_id = intval($data['new']['parent_domain_id']);

			// If the parent_domain_id has been changed, we will have to update the old site as well.
			if($this->action == 'update' && $data['new']['parent_domain_id'] != $data['old']['parent_domain_id']) {
				$tmp = $app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id = '.$old_parent_domain_id." AND active = 'y'");
				$data['new'] = $tmp;
				$data['old'] = $tmp;
				$this->action = 'update';
				$this->update($event_name,$data);
			}

			// This is not a vhost, so we need to update the parent record instead.
			$tmp = $app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id = '.$new_parent_domain_id." AND active = 'y'");
			$data['new'] = $tmp;
			$data['old'] = $tmp;
			$this->action = 'update';
		}

		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		//* Check if this is a chrooted setup
		if($web_config['website_basedir'] != '' && @is_file($web_config['website_basedir'].'/etc/passwd')) {
			$nginx_chrooted = true;
			$app->log('Info: nginx is chrooted.',LOGLEVEL_DEBUG);
		} else {
			$nginx_chrooted = false;
		}

		if($data['new']['document_root'] == '') {
			$app->log('document_root not set',LOGLEVEL_WARN);
			return 0;
		}
		if($data['new']['system_user'] == 'root' or $data['new']['system_group'] == 'root') {
			$app->log('Websites cannot be owned by the root user or group.',LOGLEVEL_WARN);
			return 0;
		}

		//* If the client of the site has been changed, we have a change of the document root
		if($this->action == 'update' && $data['new']['document_root'] != $data['old']['document_root']) {

			//* Get the old client ID
			$old_client = $app->dbmaster->queryOneRecord('SELECT client_id FROM sys_group WHERE sys_group.groupid = '.intval($data['old']['sys_groupid']));
			$old_client_id = intval($old_client['client_id']);
			unset($old_client);

			//* Remove the old symlinks
			$tmp_symlinks_array = explode(':',$web_config['website_symlinks']);
			if(is_array($tmp_symlinks_array)) {
				foreach($tmp_symlinks_array as $tmp_symlink) {
					$tmp_symlink = str_replace('[client_id]',$old_client_id,$tmp_symlink);
					$tmp_symlink = str_replace('[website_domain]',$data['old']['domain'],$tmp_symlink);
					// Remove trailing slash
					if(substr($tmp_symlink, -1, 1) == '/') $tmp_symlink = substr($tmp_symlink, 0, -1);
					// create the symlinks, if not exist
					if(is_link($tmp_symlink)) {
						exec('rm -f '.escapeshellcmd($tmp_symlink));
						$app->log('Removed symlink: rm -f '.$tmp_symlink,LOGLEVEL_DEBUG);
					}
				}
			}

			//* Move the site data
			$tmp_docroot = explode('/',$data['new']['document_root']);
			unset($tmp_docroot[count($tmp_docroot)-1]);
			$new_dir = implode('/',$tmp_docroot);

			$tmp_docroot = explode('/',$data['old']['document_root']);
			unset($tmp_docroot[count($tmp_docroot)-1]);
			$old_dir = implode('/',$tmp_docroot);
			
			//* Check if there is already some data in the new docroot and rename it as we need a clean path to move the existing site to the new path
			if(@is_dir($data['new']['document_root'])) {
				rename($data['new']['document_root'],$data['new']['document_root'].'_bak_'.date('Y_m_d'));
				$app->log('Renaming existing directory in new docroot location. mv '.$data['new']['document_root'].' '.$data['new']['document_root'].'_bak_'.date('Y_m_d'),LOGLEVEL_DEBUG);
			}
			
			//* Create new base directory, if it does not exist yet
			if(!is_dir($new_dir)) exec('mkdir -p '.$new_dir);
			exec('mv '.$data['old']['document_root'].' '.$new_dir);
			$app->log('Moving site to new document root: mv '.$data['old']['document_root'].' '.$new_dir,LOGLEVEL_DEBUG);

			// Handle the change in php_open_basedir
			$data['new']['php_open_basedir'] = str_replace($data['old']['document_root'],$data['new']['document_root'],$data['old']['php_open_basedir']);

			//* Change the owner of the website files to the new website owner
			exec('chown --recursive --from='.escapeshellcmd($data['old']['system_user']).':'.escapeshellcmd($data['old']['system_group']).' '.escapeshellcmd($data['new']['system_user']).':'.escapeshellcmd($data['new']['system_group']).' '.$new_dir);

			//* Change the home directory and group of the website user
			$command = 'usermod';
			$command .= ' --home '.escapeshellcmd($data['new']['document_root']);
			$command .= ' --gid '.escapeshellcmd($data['new']['system_group']);
			$command .= ' '.escapeshellcmd($data['new']['system_user']);
			exec($command);

			if($nginx_chrooted) $this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' '.$command);


		}

		//print_r($data);

		// Check if the directories are there and create them if necessary.
		if(!is_dir($data['new']['document_root'].'/web')) exec('mkdir -p '.$data['new']['document_root'].'/web');
		if(!is_dir($data['new']['document_root'].'/web/error') and $data['new']['errordocs']) exec('mkdir -p '.$data['new']['document_root'].'/web/error');
		//if(!is_dir($data['new']['document_root'].'/log')) exec('mkdir -p '.$data['new']['document_root'].'/log');
		if(!is_dir($data['new']['document_root'].'/ssl')) exec('mkdir -p '.$data['new']['document_root'].'/ssl');
		if(!is_dir($data['new']['document_root'].'/cgi-bin')) exec('mkdir -p '.$data['new']['document_root'].'/cgi-bin');
		if(!is_dir($data['new']['document_root'].'/tmp')) exec('mkdir -p '.$data['new']['document_root'].'/tmp');

		// Remove the symlink for the site, if site is renamed
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			if(is_dir('/var/log/ispconfig/httpd/'.$data['old']['domain'])) exec('rm -rf /var/log/ispconfig/httpd/'.$data['old']['domain']);
			if(is_link($data['old']['document_root'].'/log')) unlink($data['old']['document_root'].'/log');
		}

		// Create the symlink for the logfiles
		if(!is_dir('/var/log/ispconfig/httpd/'.$data['new']['domain'])) exec('mkdir -p /var/log/ispconfig/httpd/'.$data['new']['domain']);
		if(!is_link($data['new']['document_root'].'/log')) {
			exec('ln -s /var/log/ispconfig/httpd/'.$data['new']['domain'].' '.$data['new']['document_root'].'/log');
			$app->log('Creating symlink: ln -s /var/log/ispconfig/httpd/'.$data['new']['domain'].' '.$data['new']['document_root'].'/log',LOGLEVEL_DEBUG);
		}
		/*
		// Create the symlink for the logfiles
		// This does not work as vlogger cannot log trough symlinks.
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			if(is_dir($data['old']['document_root'].'/log')) exec('rm -rf '.$data['old']['document_root'].'/log');
			if(is_link('/var/log/ispconfig/httpd/'.$data['old']['domain'])) unlink('/var/log/ispconfig/httpd/'.$data['old']['domain']);
		}
		
		// Create the symlink for the logfiles
		if(!is_dir($data['new']['document_root'].'/log')) exec('mkdir -p '.$data['new']['document_root'].'/log');
		if(!is_link('/var/log/ispconfig/httpd/'.$data['new']['domain'])) {
			exec('ln -s '.$data['new']['document_root'].'/log /var/log/ispconfig/httpd/'.$data['new']['domain']);
			$app->log('Creating symlink: ln -s '.$data['new']['document_root'].'/log /var/log/ispconfig/httpd/'.$data['new']['domain'],LOGLEVEL_DEBUG);
		}
		*/

		// Get the client ID
		$client = $app->dbmaster->queryOneRecord('SELECT client_id FROM sys_group WHERE sys_group.groupid = '.intval($data['new']['sys_groupid']));
		$client_id = intval($client['client_id']);
		unset($client);

		// Remove old symlinks, if site is renamed
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			$tmp_symlinks_array = explode(':',$web_config['website_symlinks']);
			if(is_array($tmp_symlinks_array)) {
				foreach($tmp_symlinks_array as $tmp_symlink) {
					$tmp_symlink = str_replace('[client_id]',$client_id,$tmp_symlink);
					$tmp_symlink = str_replace('[website_domain]',$data['old']['domain'],$tmp_symlink);
					// Remove trailing slash
					if(substr($tmp_symlink, -1, 1) == '/') $tmp_symlink = substr($tmp_symlink, 0, -1);
					// remove the symlinks, if not exist
					if(is_link($tmp_symlink)) {
						exec('rm -f '.escapeshellcmd($tmp_symlink));
						$app->log('Removed symlink: rm -f '.$tmp_symlink,LOGLEVEL_DEBUG);
					}
				}
			}
		}

		// Create the symlinks for the sites
		$tmp_symlinks_array = explode(':',$web_config['website_symlinks']);
		if(is_array($tmp_symlinks_array)) {
			foreach($tmp_symlinks_array as $tmp_symlink) {
				$tmp_symlink = str_replace('[client_id]',$client_id,$tmp_symlink);
				$tmp_symlink = str_replace('[website_domain]',$data['new']['domain'],$tmp_symlink);
				// Remove trailing slash
				if(substr($tmp_symlink, -1, 1) == '/') $tmp_symlink = substr($tmp_symlink, 0, -1);
				//* Remove symlink if target folder has been changed.
				if($data['old']['document_root'] != '' && $data['old']['document_root'] != $data['new']['document_root'] && is_link($tmp_symlink)) {
					unlink($tmp_symlink);
				}
				// create the symlinks, if not exist
				if(!is_link($tmp_symlink)) {
					exec('ln -s '.escapeshellcmd($data['new']['document_root']).'/ '.escapeshellcmd($tmp_symlink));
					$app->log('Creating symlink: ln -s '.$data['new']['document_root'].'/ '.$tmp_symlink,LOGLEVEL_DEBUG);
				}
			}
		}



        // Install the Standard or Custom Error, Index and other related files
        // /usr/local/ispconfig/server/conf is for the standard files
        // /usr/local/ispconfig/server/conf-custom is for the custom files
        // setting a local var here
           
        // normally $conf['templates'] = "/usr/local/ispconfig/server/conf";

		if($this->action == 'insert' && $data['new']['type'] == 'vhost') {
			// Copy the error pages
			if($data['new']['errordocs']) {
				$error_page_path = escapeshellcmd($data['new']['document_root']).'/web/error/';
				if (file_exists($conf['rootpath'].'/conf-custom/error/'.substr(escapeshellcmd($conf['language']),0,2))) {
					exec('cp ' . $conf['rootpath'].'/conf-custom/error/'.substr(escapeshellcmd($conf['language']),0,2).'/* '.$error_page_path);
				}
				else {
					if (file_exists($conf['rootpath'].'/conf-custom/error/400.html')) {
						exec('cp '. $conf['rootpath'].'/conf-custom/error/*.html '.$error_page_path);
					}
					else {
						exec('cp ' . $conf['rootpath'] . '/conf/error/'.substr(escapeshellcmd($conf['language']),0,2).'/* '.$error_page_path);
					}
				}
				exec('chmod -R a+r '.$error_page_path);
			}

			if (file_exists($conf['rootpath'] . '/conf-custom/index/standard_index.html_'.substr(escapeshellcmd($conf['language']),0,2))) {
				exec('cp ' . $conf['rootpath'] . '/conf-custom/index/standard_index.html_'.substr(escapeshellcmd($conf['language']),0,2).' '.escapeshellcmd($data['new']['document_root']).'/web/index.html');
            
			if(is_file($conf['rootpath'] . '/conf-custom/index/favicon.ico')) {
                exec('cp ' . $conf['rootpath'] . '/conf-custom/index/favicon.ico '.escapeshellcmd($data['new']['document_root']).'/web/');
            }
			if(is_file($conf['rootpath'] . '/conf-custom/index/robots.txt')) {
                exec('cp ' . $conf['rootpath'] . '/conf-custom/index/robots.txt '.escapeshellcmd($data['new']['document_root']).'/web/');
                }
                if(is_file($conf['rootpath'] . '/conf-custom/index/.htaccess')) {
                    exec('cp ' . $conf['rootpath'] . '/conf-custom/index/.htaccess '.escapeshellcmd($data['new']['document_root']).'/web/');
                }
            }
			else {
				if (file_exists($conf['rootpath'] . '/conf-custom/index/standard_index.html')) {
					exec('cp ' . $conf['rootpath'] . '/conf-custom/index/standard_index.html '.escapeshellcmd($data['new']['document_root']).'/web/index.html');
				}
				else {
					exec('cp ' . $conf['rootpath'] . '/conf/index/standard_index.html_'.substr(escapeshellcmd($conf['language']),0,2).' '.escapeshellcmd($data['new']['document_root']).'/web/index.html');
					if(is_file($conf['rootpath'] . '/conf/index/favicon.ico')) exec('cp ' . $conf['rootpath'] . '/conf/index/favicon.ico '.escapeshellcmd($data['new']['document_root']).'/web/');
					if(is_file($conf['rootpath'] . '/conf/index/robots.txt')) exec('cp ' . $conf['rootpath'] . '/conf/index/robots.txt '.escapeshellcmd($data['new']['document_root']).'/web/');
					if(is_file($conf['rootpath'] . '/conf/index/.htaccess')) exec('cp ' . $conf['rootpath'] . '/conf/index/.htaccess '.escapeshellcmd($data['new']['document_root']).'/web/');
				}
			}
			exec('chmod -R a+r '.escapeshellcmd($data['new']['document_root']).'/web/');

			//** Copy the error documents on update when the error document checkbox has been activated and was deactivated before
		} elseif ($this->action == 'update' && $data['new']['type'] == 'vhost' && $data['old']['errordocs'] == 0 && $data['new']['errordocs'] == 1) {

			$error_page_path = escapeshellcmd($data['new']['document_root']).'/web/error/';
			if (file_exists($conf['rootpath'] . '/conf-custom/error/'.substr(escapeshellcmd($conf['language']),0,2))) {
				exec('cp ' . $conf['rootpath'] . '/conf-custom/error/'.substr(escapeshellcmd($conf['language']),0,2).'/* '.$error_page_path);
			}
			else {
				if (file_exists($conf['rootpath'] . '/conf-custom/error/400.html')) {
					exec('cp ' . $conf['rootpath'] . '/conf-custom/error/*.html '.$error_page_path);
				}
				else {
					exec('cp ' . $conf['rootpath'] . '/conf/error/'.substr(escapeshellcmd($conf['language']),0,2).'/* '.$error_page_path);
				}
			}
			exec('chmod -R a+r '.$error_page_path);
			exec('chown -R '.$data['new']['system_user'].':'.$data['new']['system_group'].' '.$error_page_path);
		}  // end copy error docs

		// Create group and user, if not exist
		$app->uses('system');
		
		if($web_config['connect_userid_to_webid'] == 'y') {
			//* Calculate the uid and gid
			$connect_userid_to_webid_start = ($web_config['connect_userid_to_webid_start'] < 1000)?1000:intval($web_config['connect_userid_to_webid_start']);
			$fixed_uid_gid = intval($connect_userid_to_webid_start + $data['new']['domain_id']);
			$fixed_uid_param = '--uid '.$fixed_uid_gid;
			$fixed_gid_param = '--gid '.$fixed_uid_gid;
			
			//* Check if a ispconfigend user and group exists and create them
			if(!$app->system->is_group('ispconfigend')) {
				exec('groupadd --gid '.($connect_userid_to_webid_start + 10000).' ispconfigend');
			}
			if(!$app->system->is_user('ispconfigend')) {
				exec('useradd -g ispconfigend -d /usr/local/ispconfig --uid '.($connect_userid_to_webid_start + 10000).' ispconfigend');
			}
		} else {
			$fixed_uid_param = '';
			$fixed_gid_param = '';
		}

		$groupname = escapeshellcmd($data['new']['system_group']);
		if($data['new']['system_group'] != '' && !$app->system->is_group($data['new']['system_group'])) {
			exec('groupadd '.$fixed_gid_param.' '.$groupname);
			if($apache_chrooted) $this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' groupadd '.$groupname);
			$app->log('Adding the group: '.$groupname,LOGLEVEL_DEBUG);
		}

		$username = escapeshellcmd($data['new']['system_user']);
		if($data['new']['system_user'] != '' && !$app->system->is_user($data['new']['system_user'])) {
			if($web_config['add_web_users_to_sshusers_group'] == 'y') {
				exec('useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param -G sshusers $username -s /bin/false");
				if($nginx_chrooted) $this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param -G sshusers $username -s /bin/false");
			} else {
				exec('useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param $username -s /bin/false");
				if($nginx_chrooted) $this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param $username -s /bin/false");
			}
			$app->log('Adding the user: '.$username,LOGLEVEL_DEBUG);
		}

		// Set the quota for the user
		if($username != '' && $app->system->is_user($username)) {
			if($data['new']['hd_quota'] > 0) {
				$blocks_soft = $data['new']['hd_quota'] * 1024;
				$blocks_hard = $blocks_soft + 1024;
			} else {
				$blocks_soft = $blocks_hard = 0;
			}
			exec("setquota -u $username $blocks_soft $blocks_hard 0 0 -a &> /dev/null");
			exec('setquota -T -u '.$username.' 604800 604800 -a &> /dev/null');
		}

		if($this->action == 'insert' || $data["new"]["system_user"] != $data["old"]["system_user"]) {
			// Chown and chmod the directories below the document root
			$this->_exec('chown -R '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root']).'/web');
			// The document root itself has to be owned by root in normal level and by the web owner in security level 20
			if($web_config['security_level'] == 20) {
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root']).'/web');
			} else {
				$this->_exec('chown root:root '.escapeshellcmd($data['new']['document_root']).'/web');
			}
		}
		
		//* If the security level is set to high
		if(($this->action == 'insert' && $data['new']['type'] == 'vhost') or ($web_config['set_folder_permissions_on_update'] == 'y' && $data['new']['type'] == 'vhost')) {
			if($web_config['security_level'] == 20) {

				$this->_exec('chmod 751 '.escapeshellcmd($data['new']['document_root']));
				$this->_exec('chmod 751 '.escapeshellcmd($data['new']['document_root']).'/*');
				$this->_exec('chmod 710 '.escapeshellcmd($data['new']['document_root'].'/web'));

				// make tmp directory writable for nginx and the website users
				$this->_exec('chmod 777 '.escapeshellcmd($data['new']['document_root'].'/tmp'));
			
				// Set Log symlink to 755 to make the logs accessible by the FTP user
				$this->_exec("chmod 755 ".escapeshellcmd($data["new"]["document_root"])."/log");

				if($web_config['add_web_users_to_sshusers_group'] == 'y') {
					$command = 'usermod';
					$command .= ' --groups sshusers';
					$command .= ' '.escapeshellcmd($data['new']['system_user']);
					$this->_exec($command);
				}

				//* if we have a chrooted nginx environment
				if($nginx_chrooted) {
					$this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' '.$command);

					//* add the nginx user to the client group in the chroot environment
					$tmp_groupfile = $app->system->server_conf['group_datei'];
					$app->system->server_conf['group_datei'] = $web_config['website_basedir'].'/etc/group';
					$app->system->add_user_to_group($groupname, escapeshellcmd($web_config['user']));
					$app->system->server_conf['group_datei'] = $tmp_groupfile;
					unset($tmp_groupfile);
				}

				//* add the nginx user to the client group
				$app->system->add_user_to_group($groupname, escapeshellcmd($web_config['nginx_user']));
				
				//* Chown all default directories
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root']));
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root'].'/cgi-bin'));
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root'].'/log'));
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root'].'/ssl'));
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root'].'/tmp'));
				$this->_exec('chown -R '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root'].'/web'));

				/*
				* Workaround for jailkit: If jailkit is enabled for the site, the 
				* website root has to be owned by the root user and we have to chmod it to 755 then
				*/

				//* Check if there is a jailkit user for this site
				$tmp = $app->db->queryOneRecord('SELECT count(shell_user_id) as number FROM shell_user WHERE parent_domain_id = '.$data['new']['domain_id']." AND chroot = 'jailkit'");
				if($tmp['number'] > 0) {
					$this->_exec('chmod 755 '.escapeshellcmd($data['new']['document_root']));
					$this->_exec('chown root:root '.escapeshellcmd($data['new']['document_root']));
				}
				unset($tmp);

				// If the security Level is set to medium
			} else {

				$this->_exec('chmod 755 '.escapeshellcmd($data['new']['document_root']));
				$this->_exec('chmod 755 '.escapeshellcmd($data['new']['document_root'].'/cgi-bin'));
				$this->_exec('chmod 755 '.escapeshellcmd($data['new']['document_root'].'/log'));
				$this->_exec('chmod 755 '.escapeshellcmd($data['new']['document_root'].'/ssl'));
				$this->_exec('chmod 755 '.escapeshellcmd($data['new']['document_root'].'/web'));
				
				// make temp directory writable for nginx and the website users
				$this->_exec('chmod 777 '.escapeshellcmd($data['new']['document_root'].'/tmp'));
				
				$this->_exec('chown root:root '.escapeshellcmd($data['new']['document_root']));
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root'].'/cgi-bin'));
				$this->_exec('chown root:root '.escapeshellcmd($data['new']['document_root'].'/log'));
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root'].'/tmp'));
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root'].'/ssl'));
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root'].'/web'));
			}
		}

		// Change the ownership of the error log to the owner of the website
		if(!@is_file($data['new']['document_root'].'/log/error.log')) exec('touch '.escapeshellcmd($data['new']['document_root']).'/log/error.log');
		$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root']).'/log/error.log');


		/*
		//* Write the custom php.ini file, if custom_php_ini filed is not empty
		$custom_php_ini_dir = $web_config['website_basedir'].'/conf/'.$data['new']['system_user'];
		if(!is_dir($web_config['website_basedir'].'/conf')) mkdir($web_config['website_basedir'].'/conf');
		if(trim($data['new']['custom_php_ini']) != '') {
			$has_custom_php_ini = true;
			if(!is_dir($custom_php_ini_dir)) mkdir($custom_php_ini_dir);
			$php_ini_content = '';
			if($data['new']['php'] == 'mod') {
				$master_php_ini_path = $web_config['php_ini_path_apache'];
			} else {
				if($data["new"]['php'] == 'fast-cgi' && file_exists($fastcgi_config["fastcgi_phpini_path"])) {
					$master_php_ini_path = $fastcgi_config["fastcgi_phpini_path"];
				} else {
					$master_php_ini_path = $web_config['php_ini_path_cgi'];
				}
			}
			if($master_php_ini_path != '' && substr($master_php_ini_path,-7) == 'php.ini' && is_file($master_php_ini_path)) {
				$php_ini_content .= file_get_contents($master_php_ini_path)."\n";
			}
			$php_ini_content .= trim($data['new']['custom_php_ini']);
			file_put_contents($custom_php_ini_dir.'/php.ini',$php_ini_content);
		} else {
			$has_custom_php_ini = false;
			if(is_file($custom_php_ini_dir.'/php.ini')) unlink($custom_php_ini_dir.'/php.ini');
		}
		*/

		//* Create the vhost config file
		$app->load('tpl');

		$tpl = new tpl();
		$tpl->newTemplate('nginx_vhost.conf.master');

		$vhost_data = $data['new'];
		$vhost_data['web_document_root'] = $data['new']['document_root'].'/web';
		$vhost_data['web_document_root_www'] = $web_config['website_basedir'].'/'.$data['new']['domain'].'/web';
		$vhost_data['web_basedir'] = $web_config['website_basedir'];
		
		// IPv6
		if($data['new']['ipv6_address'] != '') $tpl->setVar('ipv6_enabled', 1);
		
		// PHP-FPM
		// Support for multiple PHP versions
		/*
		if(trim($data['new']['fastcgi_php_version']) != ''){
			$default_php_fpm = false;
			list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
			if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
		} else {
			$default_php_fpm = true;
		}
		*/
		if($data['new']['php'] != 'no'){
			if(trim($data['new']['fastcgi_php_version']) != ''){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		} else {
			if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] != 'no'){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['old']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		}
		
		if($default_php_fpm){
			$pool_dir = escapeshellcmd($web_config['php_fpm_pool_dir']);
		} else {
			$pool_dir = $custom_php_fpm_pool_dir;
		}
		if(substr($pool_dir,-1) != '/') $pool_dir .= '/';
		$pool_name = 'web'.$data['new']['domain_id'];
		$socket_dir = escapeshellcmd($web_config['php_fpm_socket_dir']);
		if(substr($socket_dir,-1) != '/') $socket_dir .= '/';
		
		if($data['new']['php_fpm_use_socket'] == 'y'){
			$use_tcp = 0;
			$use_socket = 1;
		} else {
			$use_tcp = 1;
			$use_socket = 0;
		}
		$tpl->setVar('use_tcp', $use_tcp);
		$tpl->setVar('use_socket', $use_socket);
		$fpm_socket = $socket_dir.$pool_name.'.sock';
		$tpl->setVar('fpm_socket', $fpm_socket);
		$vhost_data['fpm_port'] = $web_config['php_fpm_start_port'] + $data['new']['domain_id'] - 1;
		
		// backwards compatibility; since ISPConfig 3.0.5, the PHP mode for nginx is called 'php-fpm' instead of 'fast-cgi'. The following line makes sure that old web sites that have 'fast-cgi' in the database still get PHP-FPM support.
		if($vhost_data['php'] == 'fast-cgi') $vhost_data['php'] = 'php-fpm';
		
		// Custom nginx directives
		$final_nginx_directives = array();
		$nginx_directives = $data['new']['nginx_directives'];
		// Make sure we only have Unix linebreaks
		$nginx_directives = str_replace("\r\n", "\n", $nginx_directives);
		$nginx_directives = str_replace("\r", "\n", $nginx_directives);
		$nginx_directive_lines = explode("\n", $nginx_directives);
		if(is_array($nginx_directive_lines) && !empty($nginx_directive_lines)){
			foreach($nginx_directive_lines as $nginx_directive_line){
				$final_nginx_directives[] = array('nginx_directive' => $nginx_directive_line);
			}
		}
		$tpl->setLoop('nginx_directives', $final_nginx_directives);

		// Check if a SSL cert exists
		$ssl_dir = $data['new']['document_root'].'/ssl';
		$domain = $data['new']['ssl_domain'];
		$key_file = $ssl_dir.'/'.$domain.'.key';
		$crt_file = $ssl_dir.'/'.$domain.'.crt';

		if($domain!='' && $data['new']['ssl'] == 'y' && @is_file($crt_file) && @is_file($key_file) && (@filesize($crt_file)>0)  && (@filesize($key_file)>0)) {
			$vhost_data['ssl_enabled'] = 1;
			$app->log('Enable SSL for: '.$domain,LOGLEVEL_DEBUG);
		} else {
			$vhost_data['ssl_enabled'] = 0;
			$app->log('SSL Disabled. '.$domain,LOGLEVEL_DEBUG);
		}

		// Set SEO Redirect
		if($data['new']['seo_redirect'] != '' && ($data['new']['subdomain'] == 'www' || $data['new']['subdomain'] == '*')){
			$vhost_data['seo_redirect_enabled'] = 1;
			if($data['new']['seo_redirect'] == 'non_www_to_www'){
				$vhost_data['seo_redirect_origin_domain'] = $data['new']['domain'];
				$vhost_data['seo_redirect_target_domain'] = 'www.'.$data['new']['domain'];
			}
			if($data['new']['seo_redirect'] == 'www_to_non_www'){
				$vhost_data['seo_redirect_origin_domain'] = 'www.'.$data['new']['domain'];
				$vhost_data['seo_redirect_target_domain'] = $data['new']['domain'];
			}
		} else {
			$vhost_data['seo_redirect_enabled'] = 0;
		}
		
		$tpl->setVar($vhost_data);

		// Rewrite rules
		$rewrite_rules = array();
		if($data['new']['redirect_type'] != '' && $data['new']['redirect_path'] != '') {
			if(substr($data['new']['redirect_path'],-1) != '/') $data['new']['redirect_path'] .= '/';
			if(substr($data['new']['redirect_path'],0,8) == '[scheme]') $data['new']['redirect_path'] = '$scheme'.substr($data['new']['redirect_path'],8);
			
			/* Disabled path extension
			if($data['new']['redirect_type'] == 'no' && substr($data['new']['redirect_path'],0,4) != 'http') {
				$data['new']['redirect_path'] = $data['new']['document_root'].'/web'.realpath($data['new']['redirect_path']).'/';
			}
			*/

			switch($data['new']['subdomain']) {
				case 'www':
					if(substr($data['new']['redirect_path'],0,1) == '/'){ // relative path
						$rewrite_exclude = '(?!'.substr($data['new']['redirect_path'],0,-1).')';
					} else { // URL - check if URL is local
						$tmp_redirect_path = $data['new']['redirect_path'];
						if(substr($tmp_redirect_path,0,7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path,7);
						$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
						if($tmp_redirect_path_parts['host'] == $data['new']['domain'] && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
							if(substr($tmp_redirect_path_parts['path'],-1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'],0,-1);
							if(substr($tmp_redirect_path_parts['path'],0,1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
							$rewrite_exclude = '(?!'.$tmp_redirect_path_parts['path'].')';
						} else {
							$rewrite_exclude = '(.?)';
						}
						unset($tmp_redirect_path);
						unset($tmp_redirect_path_parts);
					}
					$rewrite_rules[] = array(	'rewrite_domain' 	=> '^'.$data['new']['domain'],
					'rewrite_type' 		=> ($data['new']['redirect_type'] == 'no')?'':$data['new']['redirect_type'],
					'rewrite_target' 	=> $data['new']['redirect_path'],
					'rewrite_exclude'	=> $rewrite_exclude);
					
					if(substr($data['new']['redirect_path'],0,1) == '/'){ // relative path
						$rewrite_exclude = '(?!'.substr($data['new']['redirect_path'],0,-1).')';
					} else { // URL - check if URL is local
						$tmp_redirect_path = $data['new']['redirect_path'];
						if(substr($tmp_redirect_path,0,7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path,7);
						$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
						if($tmp_redirect_path_parts['host'] == 'www.'.$data['new']['domain'] && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
							if(substr($tmp_redirect_path_parts['path'],-1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'],0,-1);
							if(substr($tmp_redirect_path_parts['path'],0,1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
							$rewrite_exclude = '(?!'.$tmp_redirect_path_parts['path'].')';
						} else {
							$rewrite_exclude = '(.?)';
						}
						unset($tmp_redirect_path);
						unset($tmp_redirect_path_parts);
					}
					$rewrite_rules[] = array(	'rewrite_domain' 	=> '^www.'.$data['new']['domain'],
							'rewrite_type' 		=> ($data['new']['redirect_type'] == 'no')?'':$data['new']['redirect_type'],
							'rewrite_target' 	=> $data['new']['redirect_path'],
							'rewrite_exclude'	=> $rewrite_exclude);
					break;
				case '*':
					if(substr($data['new']['redirect_path'],0,1) == '/'){ // relative path
						$rewrite_exclude = '(?!'.substr($data['new']['redirect_path'],0,-1).')';
					} else { // URL - check if URL is local
						$tmp_redirect_path = $data['new']['redirect_path'];
						if(substr($tmp_redirect_path,0,7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path,7);
						$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
						if(substr($tmp_redirect_path_parts['host'],-strlen($data['new']['domain'])) == $data['new']['domain'] && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
							if(substr($tmp_redirect_path_parts['path'],-1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'],0,-1);
							if(substr($tmp_redirect_path_parts['path'],0,1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
							$rewrite_exclude = '(?!'.$tmp_redirect_path_parts['path'].')';
						} else {
							$rewrite_exclude = '(.?)';
						}
						unset($tmp_redirect_path);
						unset($tmp_redirect_path_parts);
					}
					$rewrite_rules[] = array(	'rewrite_domain' 	=> $data['new']['domain'],
						'rewrite_type' 		=> ($data['new']['redirect_type'] == 'no')?'':$data['new']['redirect_type'],
						'rewrite_target' 	=> $data['new']['redirect_path'],
						'rewrite_exclude'	=> $rewrite_exclude);
					break;
				default:
					if(substr($data['new']['redirect_path'],0,1) == '/'){ // relative path
						$rewrite_exclude = '(?!'.substr($data['new']['redirect_path'],0,-1).')';
					} else { // URL - check if URL is local
						$tmp_redirect_path = $data['new']['redirect_path'];
						if(substr($tmp_redirect_path,0,7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path,7);
						$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
						if($tmp_redirect_path_parts['host'] == $data['new']['domain'] && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
							if(substr($tmp_redirect_path_parts['path'],-1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'],0,-1);
							if(substr($tmp_redirect_path_parts['path'],0,1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
							$rewrite_exclude = '(?!'.$tmp_redirect_path_parts['path'].')';
						} else {
							$rewrite_exclude = '(.?)';
						}
						unset($tmp_redirect_path);
						unset($tmp_redirect_path_parts);
					}
					$rewrite_rules[] = array(	'rewrite_domain' 	=> '^'.$data['new']['domain'],
					'rewrite_type' 		=> ($data['new']['redirect_type'] == 'no')?'':$data['new']['redirect_type'],
					'rewrite_target' 	=> $data['new']['redirect_path'],
					'rewrite_exclude'	=> $rewrite_exclude);
			}
		}
		
		$server_alias = array();
		
		// get autoalias
		$auto_alias = $web_config['website_autoalias'];
		if($auto_alias != '') {
			// get the client username
			$client = $app->db->queryOneRecord("SELECT `username` FROM `client` WHERE `client_id` = '" . intval($client_id) . "'");
			$aa_search = array('[client_id]', '[website_id]', '[client_username]', '[website_domain]');
			$aa_replace = array($client_id, $data['new']['domain_id'], $client['username'], $data['new']['domain']);
			$auto_alias = str_replace($aa_search, $aa_replace, $auto_alias);
			unset($client);
			unset($aa_search);
			unset($aa_replace);
			$server_alias[] .= $auto_alias;
		}
		
		// get alias domains (co-domains and subdomains)
		$aliases = $app->db->queryAllRecords('SELECT * FROM web_domain WHERE parent_domain_id = '.$data['new']['domain_id']." AND active = 'y'");
		switch($data['new']['subdomain']) {
			case 'www':
				$server_alias[] = 'www.'.$data['new']['domain'].' ';
				break;
			case '*':
				$server_alias[] = '*.'.$data['new']['domain'].' ';
				break;
		}
		if(is_array($aliases)) {
			foreach($aliases as $alias) {
				switch($alias['subdomain']) {
					case 'www':
						$server_alias[] = 'www.'.$alias['domain'].' '.$alias['domain'].' ';
						break;
					case '*':
						$server_alias[] = '*.'.$alias['domain'].' '.$alias['domain'].' ';
						break;
					default:
						$server_alias[] = $alias['domain'].' ';
						break;
				}
				$app->log('Add server alias: '.$alias['domain'],LOGLEVEL_DEBUG);
				// Rewriting
				if($alias['redirect_type'] != '' && $alias['redirect_path'] != '') {
					if(substr($alias['redirect_path'],-1) != '/') $alias['redirect_path'] .= '/';
					if(substr($alias['redirect_path'],0,8) == '[scheme]') $alias['redirect_path'] = '$scheme'.substr($alias['redirect_path'],8);	
					
					/* Disabled the path extension
					if($data['new']['redirect_type'] == 'no' && substr($data['new']['redirect_path'],0,4) != 'http') {
						$data['new']['redirect_path'] = $data['new']['document_root'].'/web'.realpath($data['new']['redirect_path']).'/';
					}
					*/
					
					switch($alias['subdomain']) {
						case 'www':
							if(substr($alias['redirect_path'],0,1) == '/'){ // relative path
								$rewrite_exclude = '(?!'.substr($alias['redirect_path'],0,-1).')';
							} else { // URL - check if URL is local
								$tmp_redirect_path = $alias['redirect_path'];
								if(substr($tmp_redirect_path,0,7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path,7);
								$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
								if($tmp_redirect_path_parts['host'] == $alias['domain'] && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
									if(substr($tmp_redirect_path_parts['path'],-1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'],0,-1);
									if(substr($tmp_redirect_path_parts['path'],0,1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
									$rewrite_exclude = '(?!'.$tmp_redirect_path_parts['path'].')';
								} else {
									$rewrite_exclude = '(.?)';
								}
								unset($tmp_redirect_path);
								unset($tmp_redirect_path_parts);
							}
							$rewrite_rules[] = array(	'rewrite_domain' 	=> '^'.$alias['domain'],
								'rewrite_type' 		=> ($alias['redirect_type'] == 'no')?'':$alias['redirect_type'],
								'rewrite_target' 	=> $alias['redirect_path'],
								'rewrite_exclude'	=> $rewrite_exclude);
								
							if(substr($alias['redirect_path'],0,1) == '/'){ // relative path
								$rewrite_exclude = '(?!'.substr($alias['redirect_path'],0,-1).')';
							} else { // URL - check if URL is local
								$tmp_redirect_path = $alias['redirect_path'];
								if(substr($tmp_redirect_path,0,7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path,7);
								$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
								if($tmp_redirect_path_parts['host'] == 'www.'.$alias['domain'] && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
									if(substr($tmp_redirect_path_parts['path'],-1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'],0,-1);
									if(substr($tmp_redirect_path_parts['path'],0,1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
									$rewrite_exclude = '(?!'.$tmp_redirect_path_parts['path'].')';
								} else {
									$rewrite_exclude = '(.?)';
								}
								unset($tmp_redirect_path);
								unset($tmp_redirect_path_parts);
							}
							$rewrite_rules[] = array(	'rewrite_domain' 	=> '^www.'.$alias['domain'],
									'rewrite_type' 		=> ($alias['redirect_type'] == 'no')?'':$alias['redirect_type'],
									'rewrite_target' 	=> $alias['redirect_path'],
									'rewrite_exclude'	=> $rewrite_exclude);
							break;
						case '*':
							if(substr($alias['redirect_path'],0,1) == '/'){ // relative path
								$rewrite_exclude = '(?!'.substr($alias['redirect_path'],0,-1).')';
							} else { // URL - check if URL is local
								$tmp_redirect_path = $alias['redirect_path'];
								if(substr($tmp_redirect_path,0,7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path,7);
								$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
								if(substr($tmp_redirect_path_parts['host'],-strlen($alias['domain'])) == $alias['domain'] && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
									if(substr($tmp_redirect_path_parts['path'],-1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'],0,-1);
									if(substr($tmp_redirect_path_parts['path'],0,1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
									$rewrite_exclude = '(?!'.$tmp_redirect_path_parts['path'].')';
								} else {
									$rewrite_exclude = '(.?)';
								}
								unset($tmp_redirect_path);
								unset($tmp_redirect_path_parts);
							}
							$rewrite_rules[] = array(	'rewrite_domain' 	=> $alias['domain'],
								'rewrite_type' 		=> ($alias['redirect_type'] == 'no')?'':$alias['redirect_type'],
								'rewrite_target' 	=> $alias['redirect_path'],
								'rewrite_exclude'	=> $rewrite_exclude);
							break;
						default:
							if(substr($alias['redirect_path'],0,1) == '/'){ // relative path
								$rewrite_exclude = '(?!'.substr($alias['redirect_path'],0,-1).')';
							} else { // URL - check if URL is local
								$tmp_redirect_path = $alias['redirect_path'];
								if(substr($tmp_redirect_path,0,7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path,7);
								$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
								if($tmp_redirect_path_parts['host'] == $alias['domain'] && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
									if(substr($tmp_redirect_path_parts['path'],-1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'],0,-1);
									if(substr($tmp_redirect_path_parts['path'],0,1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
									$rewrite_exclude = '(?!'.$tmp_redirect_path_parts['path'].')';
								} else {
									$rewrite_exclude = '(.?)';
								}
								unset($tmp_redirect_path);
								unset($tmp_redirect_path_parts);
							}
							$rewrite_rules[] = array(	'rewrite_domain' 	=> '^'.$alias['domain'],
							'rewrite_type' 		=> ($alias['redirect_type'] == 'no')?'':$alias['redirect_type'],
							'rewrite_target' 	=> $alias['redirect_path'],
							'rewrite_exclude'	=> $rewrite_exclude);
					}
				}
			}
		}

		//* If we have some alias records
		if(count($server_alias) > 0) {
			$server_alias_str = '';
			$n = 0;

			foreach($server_alias as $tmp_alias) {
				$server_alias_str .= $tmp_alias;
			}
			unset($tmp_alias);

			$tpl->setVar('alias',trim($server_alias_str));
		} else {
			$tpl->setVar('alias','');
		}

		if(count($rewrite_rules) > 0) {
			$tpl->setLoop('redirects',$rewrite_rules);
		}
		
		//* Create basic http auth for website statistics
		$tpl->setVar('stats_auth_passwd_file', $data['new']['document_root']."/.htpasswd_stats");
		
		// Create basic http auth for other directories
		$basic_auth_locations = $this->_create_web_folder_auth_configuration($data['new']);
		if(is_array($basic_auth_locations) && !empty($basic_auth_locations)) $tpl->setLoop('basic_auth_locations', $basic_auth_locations);

		$vhost_file = escapeshellcmd($web_config['nginx_vhost_conf_dir'].'/'.$data['new']['domain'].'.vhost');
		//* Make a backup copy of vhost file
		if(file_exists($vhost_file)) copy($vhost_file,$vhost_file.'~');
		
		//* Write vhost file
		file_put_contents($vhost_file,$this->nginx_merge_locations($tpl->grab()));
		$app->log('Writing the vhost file: '.$vhost_file,LOGLEVEL_DEBUG);
		unset($tpl);

		//* Set the symlink to enable the vhost
		//* First we check if there is a old type of symlink and remove it
		$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/'.$data['new']['domain'].'.vhost');
		if(is_link($vhost_symlink)) unlink($vhost_symlink);
		
		//* Remove old or changed symlinks
		if($data['new']['subdomain'] != $data['old']['subdomain'] or $data['new']['active'] == 'n') {
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/900-'.$data['new']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/100-'.$data['new']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
		}
		
		//* New symlink
		if($data['new']['subdomain'] == '*') {
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/900-'.$data['new']['domain'].'.vhost');
		} else {
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/100-'.$data['new']['domain'].'.vhost');
		}
		if($data['new']['active'] == 'y' && !is_link($vhost_symlink)) {
			symlink($vhost_file,$vhost_symlink);
			$app->log('Creating symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
		}

		// remove old symlink and vhost file, if domain name of the site has changed
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/900-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/100-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_file = escapeshellcmd($web_config['nginx_vhost_conf_dir'].'/'.$data['old']['domain'].'.vhost');
			unlink($vhost_file);
			$app->log('Removing file: '.$vhost_file,LOGLEVEL_DEBUG);
		}
		
		// create password file for stats directory
		if(!is_file($data['new']['document_root'].'/.htpasswd_stats') || $data['new']['stats_password'] != $data['old']['stats_password']) {
			if(trim($data['new']['stats_password']) != '') {
				$htp_file = 'admin:'.trim($data['new']['stats_password']);
				file_put_contents($data['new']['document_root'].'/.htpasswd_stats',$htp_file);
				chmod($data['new']['document_root'].'/.htpasswd_stats',0755);
				unset($htp_file);
			}
		}
		
		//* Create awstats configuration
		if($data['new']['stats_type'] == 'awstats' && $data['new']['type'] == 'vhost') {
			$this->awstats_update($data,$web_config);
		}
		
		$this->php_fpm_pool_update($data,$web_config,$pool_dir,$pool_name,$socket_dir);
		
		if($web_config['check_apache_config'] == 'y') {
			//* Test if nginx starts with the new configuration file
			$nginx_online_status_before_restart = $this->_checkTcp('localhost',80);
			$app->log('nginx status is: '.$nginx_online_status_before_restart,LOGLEVEL_DEBUG);

			$app->services->restartService('httpd','restart');
			
			// wait a few seconds, before we test the apache status again
			sleep(2);
		
			//* Check if nginx restarted successfully if it was online before
			$nginx_online_status_after_restart = $this->_checkTcp('localhost',80);
			$app->log('nginx online status after restart is: '.$nginx_online_status_after_restart,LOGLEVEL_DEBUG);
			if($nginx_online_status_before_restart && !$nginx_online_status_after_restart) {
				$app->log('nginx did not restart after the configuration change for website '.$data['new']['domain'].' Reverting the configuration. Saved non-working config as '.$vhost_file.'.err',LOGLEVEL_WARN);
				copy($vhost_file,$vhost_file.'.err');
				if(is_file($vhost_file.'~')) {
					//* Copy back the last backup file
					copy($vhost_file.'~',$vhost_file);
				} else {
					//* There is no backup file, so we create a empty vhost file with a warning message inside
					file_put_contents($vhost_file,"# nginx did not start after modifying this vhost file.\n# Please check file $vhost_file.err for syntax errors.");
				}
				$app->services->restartService('httpd','restart');
			}
		} else {
			//* We do not check the nginx config after changes (is faster)
			if($nginx_chrooted) {
				$app->services->restartServiceDelayed('httpd','reload');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd','reload');
			}
		}
		
		// Remove the backup copy of the config file.
		if(@is_file($vhost_file.'~')) unlink($vhost_file.'~');
		

		//* Unset action to clean it for next processed vhost.
		$this->action = '';

	}

	function delete($event_name,$data) {
		global $app, $conf;

		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		//* Check if this is a chrooted setup
		if($web_config['website_basedir'] != '' && @is_file($web_config['website_basedir'].'/etc/passwd')) {
			$nginx_chrooted = true;
		} else {
			$nginx_chrooted = false;
		}

		if($data['old']['type'] != 'vhost' && $data['old']['parent_domain_id'] > 0) {
			//* This is a alias domain or subdomain, so we have to update the website instead
			$parent_domain_id = intval($data['old']['parent_domain_id']);
			$tmp = $app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id = '.$parent_domain_id." AND active = 'y'");
			$data['new'] = $tmp;
			$data['old'] = $tmp;
			$this->action = 'update';
			// just run the update function
			$this->update($event_name,$data);

		} else {
			//* This is a website
			// Deleting the vhost file, symlink and the data directory			
			$vhost_file = escapeshellcmd($web_config['nginx_vhost_conf_dir'].'/'.$data['old']['domain'].'.vhost');
			
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)){
				unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/900-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)){
				unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['nginx_vhost_conf_enabled_dir'].'/100-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)){
				unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			
			unlink($vhost_file);
			$app->log('Removing vhost file: '.$vhost_file,LOGLEVEL_DEBUG);

			$docroot = escapeshellcmd($data['old']['document_root']);
			if($docroot != '' && !stristr($docroot,'..')) exec('rm -rf '.$docroot);

			//remove the php fastgi starter script and PHP-FPM pool definition if available
			if ($data['old']['php'] == 'fast-cgi') {
				$this->php_fpm_pool_delete($data,$web_config);
				$fastcgi_starter_path = str_replace('[system_user]',$data['old']['system_user'],$web_config['fastcgi_starter_path']);
				if (is_dir($fastcgi_starter_path)) {
					exec('rm -rf '.$fastcgi_starter_path);
				}
			}
			
			// remove PHP-FPM pool
			if ($data['old']['php'] == 'php-fpm') {
				$this->php_fpm_pool_delete($data,$web_config);
			}

			//remove the php cgi starter script if available
			if ($data['old']['php'] == 'cgi') {
				// TODO: fetch the date from the server-settings
				$web_config['cgi_starter_path'] = $web_config['website_basedir'].'/php-cgi-scripts/[system_user]/';

				$cgi_starter_path = str_replace('[system_user]',$data['old']['system_user'],$web_config['cgi_starter_path']);
				if (is_dir($cgi_starter_path)) {
					exec('rm -rf '.$cgi_starter_path);
				}
			}

			$app->log('Removing website: '.$docroot,LOGLEVEL_DEBUG);

			// Delete the symlinks for the sites
			$client = $app->db->queryOneRecord('SELECT client_id FROM sys_group WHERE sys_group.groupid = '.intval($data['old']['sys_groupid']));
			$client_id = intval($client['client_id']);
			unset($client);
			$tmp_symlinks_array = explode(':',$web_config['website_symlinks']);
			if(is_array($tmp_symlinks_array)) {
				foreach($tmp_symlinks_array as $tmp_symlink) {
					$tmp_symlink = str_replace('[client_id]',$client_id,$tmp_symlink);
					$tmp_symlink = str_replace('[website_domain]',$data['old']['domain'],$tmp_symlink);
					// Remove trailing slash
					if(substr($tmp_symlink, -1, 1) == '/') $tmp_symlink = substr($tmp_symlink, 0, -1);
					// create the symlinks, if not exist
					if(is_link($tmp_symlink)) {
						unlink($tmp_symlink);
						$app->log('Removing symlink: '.$tmp_symlink,LOGLEVEL_DEBUG);
					}
				}
			}
			// end removing symlinks

			// Delete the log file directory
			$vhost_logfile_dir = escapeshellcmd('/var/log/ispconfig/httpd/'.$data['old']['domain']);
			if($data['old']['domain'] != '' && !stristr($vhost_logfile_dir,'..')) exec('rm -rf '.$vhost_logfile_dir);
			$app->log('Removing website logfile directory: '.$vhost_logfile_dir,LOGLEVEL_DEBUG);

			//delete the web user
			$command = 'userdel';
			$command .= ' '.$data['old']['system_user'];
			exec($command);
			if($nginx_chrooted) $this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' '.$command);
			
			//* Remove the awstats configuration file
			if($data['old']['stats_type'] == 'awstats') {
				$this->awstats_delete($data,$web_config);
			}
			
			$app->services->restartServiceDelayed('httpd','reload');

		}
	}

	//* This function is called when a IP on the server is inserted, updated or deleted
	function server_ip($event_name,$data) {
		return;
	}
	
	//* Create or update the .htaccess folder protection
	function web_folder_user($event_name,$data) {
		global $app, $conf;

		$app->uses('system');
		
		if($event_name == 'web_folder_user_delete') {
			$folder_id = $data['old']['web_folder_id'];
		} else {
			$folder_id = $data['new']['web_folder_id'];
		}
		
		$folder = $app->db->queryOneRecord("SELECT * FROM web_folder WHERE web_folder_id = ".intval($folder_id));
		$website = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($folder['parent_domain_id']));
		
		if(!is_array($folder) or !is_array($website)) {
			$app->log('Not able to retrieve folder or website record.',LOGLEVEL_DEBUG);
			return false;
		}
		
		//* Get the folder path.
		if(substr($folder['path'],0,1) == '/') $folder['path'] = substr($folder['path'],1);
		if(substr($folder['path'],-1) == '/') $folder['path'] = substr($folder['path'],0,-1);
		$folder_path = escapeshellcmd($website['document_root'].'/web/'.$folder['path']);
		if(substr($folder_path,-1) != '/') $folder_path .= '/';
		
		//* Check if the resulting path is inside the docroot
		if(stristr($folder_path,'..') || stristr($folder_path,'./') || stristr($folder_path,'\\')) {
			$app->log('Folder path "'.$folder_path.'" contains .. or ./.',LOGLEVEL_DEBUG);
			return false;
		}
		
		//* Create the folder path, if it does not exist
		if(!is_dir($folder_path)) {
			exec('mkdir -p '.$folder_path);
			chown($folder_path,$website['system_user']);
			chgrp($folder_path,$website['system_group']);
		}
		
		//* Create empty .htpasswd file, if it does not exist
		if(!is_file($folder_path.'.htpasswd')) {
			touch($folder_path.'.htpasswd');
			chmod($folder_path.'.htpasswd',0755);
			chown($folder_path.'.htpasswd',$website['system_user']);
			chgrp($folder_path.'.htpasswd',$website['system_group']);
			$app->log('Created file'.$folder_path.'.htpasswd',LOGLEVEL_DEBUG);
		}
		
		/*
		$auth_users = $app->db->queryAllRecords("SELECT * FROM web_folder_user WHERE active = 'y' AND web_folder_id = ".intval($folder_id));
		$htpasswd_content = '';
		if(is_array($auth_users) && !empty($auth_users)){
			foreach($auth_users as $auth_user){
				$htpasswd_content .= $auth_user['username'].':'.$auth_user['password']."\n";
			}
		}
		$htpasswd_content = trim($htpasswd_content);
		@file_put_contents($folder_path.'.htpasswd', $htpasswd_content);
		$app->log('Changed .htpasswd file: '.$folder_path.'.htpasswd',LOGLEVEL_DEBUG);
		*/
		
		if(($data['new']['username'] != $data['old']['username'] || $data['new']['active'] == 'n') && $data['old']['username'] != '') {
			$app->system->removeLine($folder_path.'.htpasswd',$data['old']['username'].':');
			$app->log('Removed user: '.$data['old']['username'],LOGLEVEL_DEBUG);
		}
		
		//* Add or remove the user from .htpasswd file
		if($event_name == 'web_folder_user_delete') {
			$app->system->removeLine($folder_path.'.htpasswd',$data['old']['username'].':');
			$app->log('Removed user: '.$data['old']['username'],LOGLEVEL_DEBUG);
		} else {
			if($data['new']['active'] == 'y') {
				$app->system->replaceLine($folder_path.'.htpasswd',$data['new']['username'].':',$data['new']['username'].':'.$data['new']['password'],0,1);
				$app->log('Added or updated user: '.$data['new']['username'],LOGLEVEL_DEBUG);
			}
		}
		
		// write basic auth configuration to vhost file because nginx does not support .htaccess
		$webdata['new'] = $webdata['old'] = $website;
		$this->update('web_domain_update', $webdata);
	}
	
	//* Remove .htpasswd file, when folder protection is removed
	function web_folder_delete($event_name,$data) {
		global $app, $conf;
		
		$folder_id = $data['old']['web_folder_id'];
		
		$folder = $data['old'];
		$website = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($folder['parent_domain_id']));
		
		if(!is_array($folder) or !is_array($website)) {
			$app->log('Not able to retrieve folder or website record.',LOGLEVEL_DEBUG);
			return false;
		}
		
		//* Get the folder path.
		if(substr($folder['path'],0,1) == '/') $folder['path'] = substr($folder['path'],1);
		if(substr($folder['path'],-1) == '/') $folder['path'] = substr($folder['path'],0,-1);
		$folder_path = realpath($website['document_root'].'/web/'.$folder['path']);
		if(substr($folder_path,-1) != '/') $folder_path .= '/';
		
		//* Check if the resulting path is inside the docroot
		if(substr($folder_path,0,strlen($website['document_root'])) != $website['document_root']) {
			$app->log('Folder path is outside of docroot.',LOGLEVEL_DEBUG);
			return false;
		}
		
		//* Remove .htpasswd file
		if(is_file($folder_path.'.htpasswd')) {
			unlink($folder_path.'.htpasswd');
			$app->log('Removed file '.$folder_path.'.htpasswd',LOGLEVEL_DEBUG);
		}
		
		// write basic auth configuration to vhost file because nginx does not support .htaccess
		$webdata['new'] = $webdata['old'] = $website;
		$this->update('web_domain_update', $webdata);
	}
	
	//* Update folder protection, when path has been changed
	function web_folder_update($event_name,$data) {
		global $app, $conf;
		
		$website = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($data['new']['parent_domain_id']));
	
		if(!is_array($website)) {
			$app->log('Not able to retrieve folder or website record.',LOGLEVEL_DEBUG);
			return false;
		}
		
		//* Get the folder path.
		if(substr($data['old']['path'],0,1) == '/') $data['old']['path'] = substr($data['old']['path'],1);
		if(substr($data['old']['path'],-1) == '/') $data['old']['path'] = substr($data['old']['path'],0,-1);
		$old_folder_path = realpath($website['document_root'].'/web/'.$data['old']['path']);
		if(substr($old_folder_path,-1) != '/') $old_folder_path .= '/';
			
		if(substr($data['new']['path'],0,1) == '/') $data['new']['path'] = substr($data['new']['path'],1);
		if(substr($data['new']['path'],-1) == '/') $data['new']['path'] = substr($data['new']['path'],0,-1);
		$new_folder_path = escapeshellcmd($website['document_root'].'/web/'.$data['new']['path']);
		if(substr($new_folder_path,-1) != '/') $new_folder_path .= '/';
		
		//* Check if the resulting path is inside the docroot
		if(stristr($new_folder_path,'..') || stristr($new_folder_path,'./') || stristr($new_folder_path,'\\')) {
			$app->log('Folder path "'.$new_folder_path.'" contains .. or ./.',LOGLEVEL_DEBUG);
			return false;
		}
		if(stristr($old_folder_path,'..') || stristr($old_folder_path,'./') || stristr($old_folder_path,'\\')) {
			$app->log('Folder path "'.$old_folder_path.'" contains .. or ./.',LOGLEVEL_DEBUG);
			return false;
		}
		
		//* Check if the resulting path is inside the docroot
		if(substr($old_folder_path,0,strlen($website['document_root'])) != $website['document_root']) {
			$app->log('Old folder path '.$old_folder_path.' is outside of docroot.',LOGLEVEL_DEBUG);
			return false;
		}
		if(substr($new_folder_path,0,strlen($website['document_root'])) != $website['document_root']) {
			$app->log('New folder path '.$new_folder_path.' is outside of docroot.',LOGLEVEL_DEBUG);
			return false;
		}
			
		//* Create the folder path, if it does not exist
		if(!is_dir($new_folder_path)) exec('mkdir -p '.$new_folder_path);
		
		if($data['old']['path'] != $data['new']['path']) {

		
			//* move .htpasswd file
			if(is_file($old_folder_path.'.htpasswd')) {
				rename($old_folder_path.'.htpasswd',$new_folder_path.'.htpasswd');
				$app->log('Moved file '.$old_folder_path.'.htpasswd to '.$new_folder_path.'.htpasswd',LOGLEVEL_DEBUG);
			}
		
		}

		// write basic auth configuration to vhost file because nginx does not support .htaccess
		$webdata['new'] = $webdata['old'] = $website;
		$this->update('web_domain_update', $webdata);
	}
	
	function _create_web_folder_auth_configuration($website){
		global $app, $conf;
		//* Create the domain.auth file which is included in the vhost configuration file
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		$basic_auth_file = escapeshellcmd($web_config['nginx_vhost_conf_dir'].'/'.$website['domain'].'.auth');
		//$app->load('tpl');
		//$tpl = new tpl();
		//$tpl->newTemplate('nginx_http_authentication.auth.master');
		$website_auth_locations = $app->db->queryAllRecords("SELECT * FROM web_folder WHERE active = 'y' AND parent_domain_id = ".intval($website['domain_id']));
		$basic_auth_locations = array();
		if(is_array($website_auth_locations) && !empty($website_auth_locations)){
			foreach($website_auth_locations as $website_auth_location){
				if(substr($website_auth_location['path'],0,1) == '/') $website_auth_location['path'] = substr($website_auth_location['path'],1);
				if(substr($website_auth_location['path'],-1) == '/') $website_auth_location['path'] = substr($website_auth_location['path'],0,-1);
				if($website_auth_location['path'] != ''){
					$website_auth_location['path'] .= '/';
				}
				$basic_auth_locations[] = array('htpasswd_location' => '/'.$website_auth_location['path'],
												'htpasswd_path' => $website['document_root'].'/web/'.$website_auth_location['path']);
			}
		}
		return $basic_auth_locations;
		//$tpl->setLoop('basic_auth_locations', $basic_auth_locations);
		//file_put_contents($basic_auth_file,$tpl->grab());
		//$app->log('Writing the http basic authentication file: '.$basic_auth_file,LOGLEVEL_DEBUG);
		//unset($tpl);
		//$app->services->restartServiceDelayed('httpd','reload');
	}
	
	//* Update the awstats configuration file
	private function awstats_update ($data,$web_config) {
		global $app;
		
		$awstats_conf_dir = $web_config['awstats_conf_dir'];
		
		if(!is_dir($data['new']['document_root']."/web/stats/")) mkdir($data['new']['document_root']."/web/stats");
		if(!@is_file($awstats_conf_dir.'/awstats.'.$data['new']['domain'].'.conf') || ($data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain'])) {
			if ( @is_file($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf') ) {
				unlink($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf');
			}
			
			$content = '';
			$content .= "Include \"".$awstats_conf_dir."/awstats.conf\"\n";
			$content .= "LogFile=\"/var/log/ispconfig/httpd/".$data['new']['domain']."/access.log\"\n";
			$content .= "SiteDomain=\"".$data['new']['domain']."\"\n";
			$content .= "HostAliases=\"www.".$data['new']['domain']."  localhost 127.0.0.1\"\n";
			
			file_put_contents($awstats_conf_dir.'/awstats.'.$data['new']['domain'].'.conf',$content);
			$app->log('Created AWStats config file: '.$awstats_conf_dir.'/awstats.'.$data['new']['domain'].'.conf',LOGLEVEL_DEBUG);
		}
		
		if(is_file($data['new']['document_root']."/web/stats/index.html")) unlink($data['new']['document_root']."/web/stats/index.html");
		copy("/usr/local/ispconfig/server/conf/awstats_index.php.master",$data['new']['document_root']."/web/stats/index.php");
	}
	
	//* Delete the awstats configuration file
	private function awstats_delete ($data,$web_config) {
		global $app;
		
		$awstats_conf_dir = $web_config['awstats_conf_dir'];
		
		if ( @is_file($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf') ) {
			unlink($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf');
			$app->log('Removed AWStats config file: '.$awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf',LOGLEVEL_DEBUG);
		}
	}
	
	//* Update the PHP-FPM pool configuration file
	private function php_fpm_pool_update ($data,$web_config,$pool_dir,$pool_name,$socket_dir) {
		global $app, $conf;
		/*
		if(trim($data['new']['fastcgi_php_version']) != ''){
			$default_php_fpm = false;
			list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
			if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
		} else {
			$default_php_fpm = true;
		}
		*/
		if($data['new']['php'] != 'no'){
			if(trim($data['new']['fastcgi_php_version']) != ''){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		} else {
			if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] != 'no'){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['old']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		}
		
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');
		
		if($data['new']['php'] == 'no'){
			if(@is_file($pool_dir.$pool_name.'.conf')){
				unlink($pool_dir.$pool_name.'.conf');
			}
			if($data['old']['php'] != 'no'){
				if(!$default_php_fpm){
					$app->services->restartService('php-fpm','reload:'.$custom_php_fpm_init_script);
				} else {
					$app->services->restartService('php-fpm','reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
				}
			}
			return;
		}
				
		$app->load('tpl');
		$tpl = new tpl();
		$tpl->newTemplate('php_fpm_pool.conf.master');

		if($data['new']['php_fpm_use_socket'] == 'y'){
			$use_tcp = 0;
			$use_socket = 1;
			if(!is_dir($socket_dir)) exec('mkdir -p '.$socket_dir);
		} else {
			$use_tcp = 1;
			$use_socket = 0;
		}
		$tpl->setVar('use_tcp', $use_tcp);
		$tpl->setVar('use_socket', $use_socket);
			
		$fpm_socket = $socket_dir.$pool_name.'.sock';
		$tpl->setVar('fpm_socket', $fpm_socket);
			
		$tpl->setVar('fpm_pool', $pool_name);
		$tpl->setVar('fpm_port', $web_config['php_fpm_start_port'] + $data['new']['domain_id'] - 1);
		$tpl->setVar('fpm_user', $data['new']['system_user']);
		$tpl->setVar('fpm_group', $data['new']['system_group']);
		$tpl->setVar('pm', $data['new']['pm']);
		$tpl->setVar('pm_max_children', $data['new']['pm_max_children']);
		$tpl->setVar('pm_start_servers', $data['new']['pm_start_servers']);
		$tpl->setVar('pm_min_spare_servers', $data['new']['pm_min_spare_servers']);
		$tpl->setVar('pm_max_spare_servers', $data['new']['pm_max_spare_servers']);
		$tpl->setVar('pm_process_idle_timeout', $data['new']['pm_process_idle_timeout']);
		$tpl->setVar('pm_max_requests', $data['new']['pm_max_requests']);
		$tpl->setVar('document_root', $data['new']['document_root']);
		$tpl->setVar('security_level',$web_config['security_level']);
		$php_open_basedir = ($data['new']['php_open_basedir'] == '')?escapeshellcmd($data['new']['document_root']):escapeshellcmd($data['new']['php_open_basedir']);
		$tpl->setVar('php_open_basedir', $php_open_basedir);
		if($php_open_basedir != ''){
			$tpl->setVar('enable_php_open_basedir', '');
		} else {
			$tpl->setVar('enable_php_open_basedir', ';');
		}
			
		// Custom php.ini settings
		$final_php_ini_settings = array();
		$custom_php_ini_settings = trim($data['new']['custom_php_ini']);
		if($custom_php_ini_settings != ''){
			// Make sure we only have Unix linebreaks
			$custom_php_ini_settings = str_replace("\r\n", "\n", $custom_php_ini_settings);
			$custom_php_ini_settings = str_replace("\r", "\n", $custom_php_ini_settings);
			$ini_settings = explode("\n", $custom_php_ini_settings);
			if(is_array($ini_settings) && !empty($ini_settings)){
				foreach($ini_settings as $ini_setting){
						list($key, $value) = explode('=', $ini_setting);
						if($value){
							$value = escapeshellcmd(trim($value));
							$key = escapeshellcmd(trim($key));
							switch (strtolower($value)) {
								case '0':
									// PHP-FPM might complain about invalid boolean value if you use 0
									$value = 'off';
								case '1':
								case 'on':
								case 'off':
								case 'true':
								case 'false':
								case 'yes':
								case 'no':
									$final_php_ini_settings[] = array('ini_setting' => 'php_admin_flag['.$key.'] = '.$value);
									break;
								default:
									$final_php_ini_settings[] = array('ini_setting' => 'php_admin_value['.$key.'] = '.$value);
							}
						}
				}
			}
		}
			
		$tpl->setLoop('custom_php_ini_settings', $final_php_ini_settings);
			
		file_put_contents($pool_dir.$pool_name.'.conf',$tpl->grab());
		$app->log('Writing the PHP-FPM config file: '.$pool_dir.$pool_name.'.conf',LOGLEVEL_DEBUG);
		unset($tpl);
		
		// delete pool in all other PHP versions
		$default_pool_dir = escapeshellcmd($web_config['php_fpm_pool_dir']);
		if(substr($default_pool_dir,-1) != '/') $default_pool_dir .= '/';
		if($default_pool_dir != $pool_dir){
			if ( @is_file($default_pool_dir.$pool_name.'.conf') ) {
					unlink($default_pool_dir.$pool_name.'.conf');
					$app->log('Removed PHP-FPM config file: '.$default_pool_dir.$pool_name.'.conf',LOGLEVEL_DEBUG);
					$app->services->restartService('php-fpm','reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
			}
		}
		$php_versions = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ".$conf["server_id"]);
		if(is_array($php_versions) && !empty($php_versions)){
			foreach($php_versions as $php_version){
				if(substr($php_version['php_fpm_pool_dir'],-1) != '/') $php_version['php_fpm_pool_dir'] .= '/';
				if($php_version['php_fpm_pool_dir'] != $pool_dir){
					if ( @is_file($php_version['php_fpm_pool_dir'].$pool_name.'.conf') ) {
						unlink($php_version['php_fpm_pool_dir'].$pool_name.'.conf');
						$app->log('Removed PHP-FPM config file: '.$php_version['php_fpm_pool_dir'].$pool_name.'.conf',LOGLEVEL_DEBUG);
						$app->services->restartService('php-fpm','reload:'.$php_version['php_fpm_init_script']);
					}
				}
			}
		}
		// Reload current PHP-FPM after all others
		sleep(1);
		if(!$default_php_fpm){
			$app->services->restartService('php-fpm','reload:'.$custom_php_fpm_init_script);
		} else {
			$app->services->restartService('php-fpm','reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
		}
	}
	
	//* Delete the PHP-FPM pool configuration file
	private function php_fpm_pool_delete ($data,$web_config) {
		global $app, $conf;
		
		if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] != 'no'){
			$default_php_fpm = false;
			list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['old']['fastcgi_php_version']));
			if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
		} else {
			$default_php_fpm = true;
		}
		
		if($default_php_fpm){
			$pool_dir = escapeshellcmd($web_config['php_fpm_pool_dir']);
		} else {
			$pool_dir = $custom_php_fpm_pool_dir;
		}
		
		if(substr($pool_dir,-1) != '/') $pool_dir .= '/';
		$pool_name = 'web'.$data['old']['domain_id'];
		
		if ( @is_file($pool_dir.$pool_name.'.conf') ) {
			unlink($pool_dir.$pool_name.'.conf');
			$app->log('Removed PHP-FPM config file: '.$pool_dir.$pool_name.'.conf',LOGLEVEL_DEBUG);
		}
		
		// delete pool in all other PHP versions
		$default_pool_dir = escapeshellcmd($web_config['php_fpm_pool_dir']);
		if(substr($default_pool_dir,-1) != '/') $default_pool_dir .= '/';
		if($default_pool_dir != $pool_dir){
			if ( @is_file($default_pool_dir.$pool_name.'.conf') ) {
					unlink($default_pool_dir.$pool_name.'.conf');
					$app->log('Removed PHP-FPM config file: '.$default_pool_dir.$pool_name.'.conf',LOGLEVEL_DEBUG);
					$app->services->restartService('php-fpm','reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
			}
		}	
		$php_versions = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ".$data['old']['server_id']);
		if(is_array($php_versions) && !empty($php_versions)){
			foreach($php_versions as $php_version){
				if(substr($php_version['php_fpm_pool_dir'],-1) != '/') $php_version['php_fpm_pool_dir'] .= '/';
				if($php_version['php_fpm_pool_dir'] != $pool_dir){
					if ( @is_file($php_version['php_fpm_pool_dir'].$pool_name.'.conf') ) {
						unlink($php_version['php_fpm_pool_dir'].$pool_name.'.conf');
						$app->log('Removed PHP-FPM config file: '.$php_version['php_fpm_pool_dir'].$pool_name.'.conf',LOGLEVEL_DEBUG);
						$app->services->restartService('php-fpm','reload:'.$php_version['php_fpm_init_script']);
					}
				}
			}
		}
		
		// Reload current PHP-FPM after all others
		sleep(1);
		if(!$default_php_fpm){
			$app->services->restartService('php-fpm','reload:'.$custom_php_fpm_init_script);
		} else {
			$app->services->restartService('php-fpm','reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
		}
	}
	
	private function nginx_merge_locations($vhost_conf){

		$lines = explode("\n", $vhost_conf);
		
		if(is_array($lines) && !empty($lines)){
		
			$locations = array();
			$islocation = false;
			$linecount = sizeof($lines);

			for($i=0;$i<$linecount;$i++){
				$l = trim($lines[$i]);
				if(substr($l, 0, 8) == 'location' && !$islocation){
				
					$islocation = true;
					$level = 0;
					
					// Remove unnecessary whitespace
					$l = preg_replace('/\s\s+/', ' ', $l);
					
					$loc_parts = explode(' ', $l);
					// see http://wiki.nginx.org/HttpCoreModule#location
					if($loc_parts[1] == '=' || $loc_parts[1] == '~' || $loc_parts[1] == '~*' || $loc_parts[1] == '^~'){
						$location = $loc_parts[1].' '.$loc_parts[2];
					} else {
						$location = $loc_parts[1];
					}
					unset($loc_parts);
					
					if(!isset($locations[$location]['action'])) $locations[$location]['action'] = 'replace';
					if(substr($l, -9) == '##merge##'){
						$locations[$location]['action'] = 'merge';
					}
					
					if(!isset($locations[$location]['open_tag'])) $locations[$location]['open_tag'] = '        location '.$location.' {';
					if(!isset($locations[$location]['location']) || $locations[$location]['action'] == 'replace') $locations[$location]['location'] = '';
					if(!isset($locations[$location]['end_tag'])) $locations[$location]['end_tag'] = '        }';
					if(!isset($locations[$location]['start_line'])) $locations[$location]['start_line'] = $i;

					unset($lines[$i]);
					
				} else {
				
					if($islocation){
						if(strpos($l, '{') !== false){
							$level += 1;
						}
						if(strpos($l, '}') !== false && $level > 0){
							$level -= 1;
							$locations[$location]['location'] .= $lines[$i]."\n";
						} elseif(strpos($l, '}') !== false && $level == 0){
							$islocation = false;
						} else {
							$locations[$location]['location'] .= $lines[$i]."\n";
						}
						unset($lines[$i]);
					}
					
				}
			}
			
			if(is_array($locations) && !empty($locations)){
				foreach($locations as $key => $val){
					$new_location = $val['open_tag']."\n".$val['location'].$val['end_tag'];
					$lines[$val['start_line']] = $new_location;
				}
			}
			ksort($lines);
			$vhost_conf = implode("\n", $lines);
		}
		
		return $vhost_conf;
	}
	
	function client_delete($event_name,$data) {
		global $app, $conf;
		
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');
		
		$client_id = intval($data['old']['client_id']);
		if($client_id > 0) {
			
			$client_dir = $web_config['website_basedir'].'/clients/client'.$client_id;
			if(is_dir($client_dir) && !stristr($client_dir,'..')) {
				@rmdir($client_dir);
				$app->log('Removed client directory: '.$client_dir,LOGLEVEL_DEBUG);
			}
			
			$this->_exec('groupdel client'.$client_id);
			$app->log('Removed group client'.$client_id,LOGLEVEL_DEBUG);
		}
		
	}

	//* Wrapper for exec function for easier debugging
	private function _exec($command) {
		global $app;
		$app->log('exec: '.$command,LOGLEVEL_DEBUG);
		exec($command);
	}
	
	private function _checkTcp ($host,$port) {

		$fp = @fsockopen ($host, $port, $errno, $errstr, 2);

		if ($fp) {
			fclose($fp);
			return true;
		} else {
			return false;
		}
	}


} // end class

?>