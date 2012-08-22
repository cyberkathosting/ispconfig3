<?php

/*
Copyright (c) 2007 - 2012, Till Brehm, projektfarm Gmbh
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

	// private variables
	var $action = '';
	var $ssl_certificate_changed = false;

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
		$app->plugins->registerEvent('web_domain_insert',$this->plugin_name,'ssl');
		$app->plugins->registerEvent('web_domain_update',$this->plugin_name,'ssl');
		$app->plugins->registerEvent('web_domain_delete',$this->plugin_name,'ssl');

		$app->plugins->registerEvent('web_domain_insert',$this->plugin_name,'insert');
		$app->plugins->registerEvent('web_domain_update',$this->plugin_name,'update');
		$app->plugins->registerEvent('web_domain_delete',$this->plugin_name,'delete');

		$app->plugins->registerEvent('server_ip_insert',$this->plugin_name,'server_ip');
		$app->plugins->registerEvent('server_ip_update',$this->plugin_name,'server_ip');
		$app->plugins->registerEvent('server_ip_delete',$this->plugin_name,'server_ip');

		$app->plugins->registerEvent('webdav_user_insert',$this->plugin_name,'webdav');
		$app->plugins->registerEvent('webdav_user_update',$this->plugin_name,'webdav');
		$app->plugins->registerEvent('webdav_user_delete',$this->plugin_name,'webdav');
		
		$app->plugins->registerEvent('client_delete',$this->plugin_name,'client_delete');
		
		$app->plugins->registerEvent('web_folder_user_insert',$this->plugin_name,'web_folder_user');
		$app->plugins->registerEvent('web_folder_user_update',$this->plugin_name,'web_folder_user');
		$app->plugins->registerEvent('web_folder_user_delete',$this->plugin_name,'web_folder_user');
		
		$app->plugins->registerEvent('web_folder_update',$this->plugin_name,'web_folder_update');
		$app->plugins->registerEvent('web_folder_delete',$this->plugin_name,'web_folder_delete');
		
		$app->plugins->registerEvent('ftp_user_delete',$this->plugin_name,'ftp_user_delete');
		
	}

	// Handle the creation of SSL certificates
	function ssl($event_name,$data) {
		global $app, $conf;
		
		$app->uses('system');

		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		if ($web_config['CA_path']!='' && !file_exists($web_config['CA_path'].'/openssl.cnf'))
			$app->log("CA path error, file does not exist:".$web_config['CA_path'].'/openssl.conf',LOGLEVEL_ERROR);	
		
		//* Only vhosts can have a ssl cert
		if($data["new"]["type"] != "vhost" && $data["new"]["type"] != "vhostsubdomain") return;

		// if(!is_dir($data['new']['document_root'].'/ssl')) exec('mkdir -p '.$data['new']['document_root'].'/ssl');
		if(!is_dir($data['new']['document_root'].'/ssl')) $app->system->mkdirpath($data['new']['document_root'].'/ssl');
		
		$ssl_dir = $data['new']['document_root'].'/ssl';
		$domain = $data['new']['ssl_domain'];
		$key_file = $ssl_dir.'/'.$domain.'.key.org';
		$key_file2 = $ssl_dir.'/'.$domain.'.key';
		$csr_file = $ssl_dir.'/'.$domain.'.csr';
		$crt_file = $ssl_dir.'/'.$domain.'.crt';

		//* Create a SSL Certificate
		if($data['new']['ssl_action'] == 'create') {
			
			$this->ssl_certificate_changed = true;
			
			//* Rename files if they exist
			if(file_exists($key_file)) $app->system->rename($key_file,$key_file.'.bak');
			if(file_exists($key_file2)) $app->system->rename($key_file2,$key_file2.'.bak');
			if(file_exists($csr_file)) $app->system->rename($csr_file,$csr_file.'.bak');
			if(file_exists($crt_file)) $app->system->rename($crt_file,$crt_file.'.bak');
			
			$rand_file = $ssl_dir.'/random_file';
			$rand_data = md5(uniqid(microtime(),1));
			for($i=0; $i<1000; $i++) {
				$rand_data .= md5(uniqid(microtime(),1));
				$rand_data .= md5(uniqid(microtime(),1));
				$rand_data .= md5(uniqid(microtime(),1));
				$rand_data .= md5(uniqid(microtime(),1));
			}
			$app->system->file_put_contents($rand_file, $rand_data);

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
        C                      = ".trim($data['new']['ssl_country'])."
        ST                     = ".trim($data['new']['ssl_state'])."
        L                      = ".trim($data['new']['ssl_locality'])."
        O                      = ".trim($data['new']['ssl_organisation'])."
        OU                     = ".trim($data['new']['ssl_organisation_unit'])."
        CN                     = $domain
        emailAddress           = webmaster@".$data['new']['domain']."

        [ req_attributes ]
        challengePassword              = A challenge password";

			$ssl_cnf_file = $ssl_dir.'/openssl.conf';
			$app->system->file_put_contents($ssl_cnf_file,$ssl_cnf);

			$rand_file = escapeshellcmd($rand_file);
			$key_file = escapeshellcmd($key_file);
			$key_file2 = escapeshellcmd($key_file2);
			$ssl_days = 3650;
			$csr_file = escapeshellcmd($csr_file);
			$config_file = escapeshellcmd($ssl_cnf_file);
			$crt_file = escapeshellcmd($crt_file);

			if(is_file($ssl_cnf_file) && !is_link($ssl_cnf_file)) {
				
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

			$app->system->chmod($key_file2,0400);
			@$app->system->unlink($config_file);
			@$app->system->unlink($rand_file);
			$ssl_request = $app->db->quote($app->system->file_get_contents($csr_file));
			$ssl_cert = $app->db->quote($app->system->file_get_contents($crt_file));
			$ssl_key2 = $app->db->quote($app->system->file_get_contents($key_file2));
			/* Update the DB of the (local) Server */
			$app->db->query("UPDATE web_domain SET ssl_request = '$ssl_request', ssl_cert = '$ssl_cert', ssl_key = '$ssl_key2' WHERE domain = '".$data['new']['domain']."'");
			$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = '".$data['new']['domain']."'");
			/* Update also the master-DB of the Server-Farm */
			$app->dbmaster->query("UPDATE web_domain SET ssl_request = '$ssl_request', ssl_cert = '$ssl_cert', ssl_key = '$ssl_key2' WHERE domain = '".$data['new']['domain']."'");
			$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = '".$data['new']['domain']."'");
		}

		//* Save a SSL certificate to disk
		if($data["new"]["ssl_action"] == 'save') {
			$this->ssl_certificate_changed = true;
			$ssl_dir = $data["new"]["document_root"]."/ssl";
			$domain = ($data["new"]["ssl_domain"] != '')?$data["new"]["ssl_domain"]:$data["new"]["domain"];
			$key_file = $ssl_dir.'/'.$domain.'.key.org';
			$key_file2 = $ssl_dir.'/'.$domain.'.key';
			$csr_file = $ssl_dir.'/'.$domain.".csr";
			$crt_file = $ssl_dir.'/'.$domain.".crt";
			$bundle_file = $ssl_dir.'/'.$domain.".bundle";
			
			//* Backup files
			if(file_exists($key_file)) $app->system->copy($key_file,$key_file.'~');
			if(file_exists($key_file2)) $app->system->copy($key_file2,$key_file2.'~');
			if(file_exists($csr_file)) $app->system->copy($csr_file,$csr_file.'~');
			if(file_exists($crt_file)) $app->system->copy($crt_file,$crt_file.'~');
			if(file_exists($bundle_file)) $app->system->copy($bundle_file,$bundle_file.'~');
			
			//* Write new ssl files
			if(trim($data["new"]["ssl_request"]) != '') $app->system->file_put_contents($csr_file,$data["new"]["ssl_request"]);
			if(trim($data["new"]["ssl_cert"]) != '') $app->system->file_put_contents($crt_file,$data["new"]["ssl_cert"]);
			if(trim($data["new"]["ssl_bundle"]) != '') $app->system->file_put_contents($bundle_file,$data["new"]["ssl_bundle"]);
			if(trim($data["new"]["ssl_key"]) != '') $app->system->file_put_contents($key_file2,$data["new"]["ssl_key"]);
			
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
			$bundle_file = $ssl_dir.'/'.$domain.'.bundle';
			if(file_exists($web_config['CA_path'].'/openssl.cnf') && !is_link($web_config['CA_path'].'/openssl.cnf'))
				{
					exec("openssl ca -batch -config ".$web_config['CA_path']."/openssl.cnf -passin pass:".$web_config['CA_pass']." -revoke $crt_file");
					$app->log("Revoking CA-signed SSL Cert for: $domain",LOGLEVEL_DEBUG);
				};
			$app->system->unlink($csr_file);
			$app->system->unlink($crt_file);
			$app->system->unlink($bundle_file);
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

		if($this->action != 'insert') $this->action = 'update';

		if($data['new']['type'] != 'vhost' && $data['new']['type'] != 'vhostsubdomain' && $data['new']['parent_domain_id'] > 0) {

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
			$apache_chrooted = true;
			$app->log('Info: Apache is chrooted.',LOGLEVEL_DEBUG);
		} else {
			$apache_chrooted = false;
		}

		if($data['new']['document_root'] == '') {
			$app->log('document_root not set',LOGLEVEL_WARN);
			return 0;
		}
		if($data['new']['system_user'] == 'root' or $data['new']['system_group'] == 'root') {
			$app->log('Websites cannot be owned by the root user or group.',LOGLEVEL_WARN);
			return 0;
		}
		if(trim($data['new']['domain']) == '') {
			$app->log('domain is empty',LOGLEVEL_WARN);
			return 0;
		}
		
        $web_folder = 'web';
        $log_folder = 'log';
        if($data['new']['type'] == 'vhostsubdomain') {
            $tmp = $app->db->queryOneRecord('SELECT `domain` FROM web_domain WHERE domain_id = '.intval($data['new']['parent_domain_id']));
            $subdomain_host = preg_replace('/^(.*)\.' . preg_quote($tmp['domain'], '/') . '$/', '$1', $data['new']['domain']);
            if($subdomain_host == '') $subdomain_host = 'web'.$data['new']['domain_id'];
            $web_folder = $data['new']['web_folder'];
            $log_folder .= '/' . $subdomain_host;
            unset($tmp);
        }

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
				if($apache_chrooted) $this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param -G sshusers $username -s /bin/false");
			} else {
				exec('useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param $username -s /bin/false");
				if($apache_chrooted) $this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param $username -s /bin/false");
			}
			$app->log('Adding the user: '.$username,LOGLEVEL_DEBUG);
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
			
			//* Remove protection of old folders
			$app->system->web_folder_protection($data['old']['document_root'],false);

			//* Move the site data
			$tmp_docroot = explode('/',$data['new']['document_root']);
			unset($tmp_docroot[count($tmp_docroot)-1]);
			$new_dir = implode('/',$tmp_docroot);

			$tmp_docroot = explode('/',$data['old']['document_root']);
			unset($tmp_docroot[count($tmp_docroot)-1]);
			$old_dir = implode('/',$tmp_docroot);

			//* Check if there is already some data in the new docroot and rename it as we need a clean path to move the existing site to the new path
			if(@is_dir($data['new']['document_root'])) {
				$app->system->rename($data['new']['document_root'],$data['new']['document_root'].'_bak_'.date('Y_m_d'));
				$app->log('Renaming existing directory in new docroot location. mv '.$data['new']['document_root'].' '.$data['new']['document_root'].'_bak_'.date('Y_m_d'),LOGLEVEL_DEBUG);
			}
			
			//* Create new base directory, if it does not exist yet
			if(!is_dir($new_dir)) $app->system->mkdirpath($new_dir);
			//exec('mv '.$data['old']['document_root'].' '.$new_dir);
			$app->system->rename($data['old']['document_root'],$new_dir);
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

			if($apache_chrooted) $this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' '.$command);


		}

		//print_r($data);

		// Check if the directories are there and create them if necessary.
		$app->system->web_folder_protection($data['new']['document_root'],false);
		
		if(!is_dir($data['new']['document_root'].'/' . $web_folder)) $app->system->mkdirpath($data['new']['document_root'].'/' . $web_folder);
		if(!is_dir($data['new']['document_root'].'/' . $web_folder . '/error') and $data['new']['errordocs']) $app->system->mkdirpath($data['new']['document_root'].'/' . $web_folder . '/error');
		//if(!is_dir($data['new']['document_root'].'/'.$log_folder)) exec('mkdir -p '.$data['new']['document_root'].'/'.$log_folder);
		if(!is_dir($data['new']['document_root'].'/ssl')) $app->system->mkdirpath($data['new']['document_root'].'/ssl');
		if(!is_dir($data['new']['document_root'].'/cgi-bin')) $app->system->mkdirpath($data['new']['document_root'].'/cgi-bin');
		if(!is_dir($data['new']['document_root'].'/tmp')) $app->system->mkdirpath($data['new']['document_root'].'/tmp');
		if(!is_dir($data['new']['document_root'].'/webdav')) $app->system->mkdirpath($data['new']['document_root'].'/webdav');
		
		//* Create the new private directory
		if(!is_dir($data['new']['document_root'].'/private')) {
			$app->system->mkdirpath($data['new']['document_root'].'/private');
			$app->system->chmod($data['new']['document_root'].'/private',0710);
			$app->system->chown($data['new']['document_root'].'/private',$username);
			$app->system->chgrp($data['new']['document_root'].'/private',$groupname);
		}
		
		
		// Remove the symlink for the site, if site is renamed
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			if(is_dir('/var/log/ispconfig/httpd/'.$data['old']['domain'])) exec('rm -rf /var/log/ispconfig/httpd/'.$data['old']['domain']);
			if(is_link($data['old']['document_root'].'/'.$log_folder)) $app->system->unlink($data['old']['document_root'].'/'.$log_folder);
		}
		
		//* Create the log dir if nescessary and mount it
        if(!is_dir('/var/log/ispconfig/httpd/'.$data['new']['domain'])) exec('mkdir -p /var/log/ispconfig/httpd/'.$data['new']['domain']);
		if(!is_dir($data['new']['document_root'].'/'.$log_folder) || is_link($data['new']['document_root'].'/'.$log_folder)) {
			if(is_link($data['new']['document_root'].'/'.$log_folder)) unlink($data['new']['document_root'].'/'.$log_folder);
			$app->system->mkdirpath($data['new']['document_root'].'/'.$log_folder);
			$app->system->chown($data['new']['document_root'].'/'.$log_folder,'root');
			$app->system->chgrp($data['new']['document_root'].'/'.$log_folder,'root');
			$app->system->chmod($data['new']['document_root'].'/'.$log_folder,0755);
			exec('mount --bind '.escapeshellarg('/var/log/ispconfig/httpd/'.$data['new']['domain']).' '.escapeshellarg($data['new']['document_root'].'/'.$log_folder));
			//* add mountpoint to fstab
			$fstab_line = '/var/log/ispconfig/httpd/'.$data['new']['domain'].' '.$data['new']['document_root'].'/'.$log_folder.'    none    bind    0 0';
			$app->system->replaceLine('/etc/fstab',$fstab_line,$fstab_line,1,1);
		}
		
		$app->system->web_folder_protection($data['new']['document_root'],true);

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
					$app->system->unlink($tmp_symlink);
				}
				// create the symlinks, if not exist
				if(!is_link($tmp_symlink)) {
//					exec("ln -s ".escapeshellcmd($data["new"]["document_root"])."/ ".escapeshellcmd($tmp_symlink));
					if ($web_config["website_symlinks_rel"] == 'y') {
						$this->create_relative_link(escapeshellcmd($data["new"]["document_root"]), escapeshellcmd($tmp_symlink));
					} else {
						exec("ln -s ".escapeshellcmd($data["new"]["document_root"])."/ ".escapeshellcmd($tmp_symlink));
					}

					$app->log('Creating symlink: ln -s '.$data['new']['document_root'].'/ '.$tmp_symlink,LOGLEVEL_DEBUG);
				}
			}
		}



        // Install the Standard or Custom Error, Index and other related files
        // /usr/local/ispconfig/server/conf is for the standard files
        // /usr/local/ispconfig/server/conf-custom is for the custom files
        // setting a local var here
           
        // normally $conf['templates'] = "/usr/local/ispconfig/server/conf";
		if($this->action == 'insert' && ($data['new']['type'] == 'vhost' || $data['new']['type'] == 'vhostsubdomain')) {
            
            // Copy the error pages
			if($data['new']['errordocs']) {
				$error_page_path = escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/error/';
				if (file_exists($conf['rootpath'] . '/conf-custom/error/'.substr(escapeshellcmd($conf['language']),0,2))) {
					exec('cp ' . $conf['rootpath'] . '/conf-custom/error/'.substr(escapeshellcmd($conf['language']),0,2).'/* '.$error_page_path);
				}
				else {
					if (file_exists($conf['rootpath'] . '/conf-custom/error/400.html')) {
						exec('cp '. $conf['rootpath'] . '/conf-custom/error/*.html '.$error_page_path);
					}
					else {
						exec('cp ' . $conf['rootpath'] . '/conf/error/'.substr(escapeshellcmd($conf['language']),0,2).'/* '.$error_page_path);
					}
				}
				exec('chmod -R a+r '.$error_page_path);
			}

			if (file_exists($conf['rootpath'] . '/conf-custom/index/standard_index.html_'.substr(escapeshellcmd($conf['language']),0,2))) {
				exec('cp ' . $conf['rootpath'] . '/conf-custom/index/standard_index.html_'.substr(escapeshellcmd($conf['language']),0,2).' '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/index.html');
            
			if(is_file($conf['rootpath'] . '/conf-custom/index/favicon.ico')) {
                exec('cp ' . $conf['rootpath'] . '/conf-custom/index/favicon.ico '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
            }
			if(is_file($conf['rootpath'] . '/conf-custom/index/robots.txt')) {
                exec('cp ' . $conf['rootpath'] . '/conf-custom/index/robots.txt '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
                }
                if(is_file($conf['rootpath'] . '/conf-custom/index/.htaccess')) {
                    exec('cp ' . $conf['rootpath'] . '/conf-custom/index/.htaccess '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
                }
            }
			else {
				if (file_exists($conf['rootpath'] . '/conf-custom/index/standard_index.html')) {
					exec('cp ' . $conf['rootpath'] . '/conf-custom/index/standard_index.html '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/index.html');
				}
				else {
					exec('cp ' . $conf['rootpath'] . '/conf/index/standard_index.html_'.substr(escapeshellcmd($conf['language']),0,2).' '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/index.html');
					if(is_file($conf['rootpath'] . '/conf/index/favicon.ico')) exec('cp ' . $conf['rootpath'] . '/conf/index/favicon.ico '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
					if(is_file($conf['rootpath'] . '/conf/index/robots.txt')) exec('cp ' . $conf['rootpath'] . '/conf/index/robots.txt '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
					if(is_file($conf['rootpath'] . '/conf/index/.htaccess')) exec('cp ' . $conf['rootpath'] . '/conf/index/.htaccess '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
				}
			}
			exec('chmod -R a+r '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');

			//** Copy the error documents on update when the error document checkbox has been activated and was deactivated before
		} elseif ($this->action == 'update' && ($data['new']['type'] == 'vhost' || $data['new']['type'] == 'vhostsubdomain') && $data['old']['errordocs'] == 0 && $data['new']['errordocs'] == 1) {
            
			$error_page_path = escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/error/';
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
			$this->_exec('chown -R '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder);
			// The document root itself has to be owned by root in normal level and by the web owner in security level 20
			if($web_config['security_level'] == 20) {
				$this->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder);
			} else {
				$this->_exec('chown root:root '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder);
			}
		}

		//* If the security level is set to high
		if(($this->action == 'insert' && $data['new']['type'] == 'vhost') or ($web_config['set_folder_permissions_on_update'] == 'y' && $data['new']['type'] == 'vhost')) {
			
			$app->system->web_folder_protection($data['new']['document_root'],false);
			
			//* Check if we have the new private folder and create it if nescessary
			if(!is_dir($data['new']['document_root'].'/private')) $app->system->mkdir($data['new']['document_root'].'/private');
			
			if($web_config['security_level'] == 20) {
				
				$app->system->chmod($data['new']['document_root'],0755);
				$app->system->chmod($data['new']['document_root'].'/web',0710);
				$app->system->chmod($data['new']['document_root'].'/webdav',0710);
				$app->system->chmod($data['new']['document_root'].'/private',0710);
				$app->system->chmod($data['new']['document_root'].'/ssl',0755);

				// make tmp directory writable for Apache and the website users
				$app->system->chmod($data['new']['document_root'].'/tmp',0777);
			
				// Set Log directory to 755 to make the logs accessible by the FTP user
				if(realpath($data['new']['document_root'].'/'.$log_folder . '/error.log') == '/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log') {
					$app->system->chmod($data['new']['document_root'].'/'.$log_folder,0755);
				}
				
				if($web_config['add_web_users_to_sshusers_group'] == 'y') {
					$command = 'usermod';
					$command .= ' --groups sshusers';
					$command .= ' '.escapeshellcmd($data['new']['system_user']);
					$this->_exec($command);
				}

				//* if we have a chrooted Apache environment
				if($apache_chrooted) {
					$this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' '.$command);

					//* add the apache user to the client group in the chroot environment
					$tmp_groupfile = $app->system->server_conf['group_datei'];
					$app->system->server_conf['group_datei'] = $web_config['website_basedir'].'/etc/group';
					$app->system->add_user_to_group($groupname, escapeshellcmd($web_config['user']));
					$app->system->server_conf['group_datei'] = $tmp_groupfile;
					unset($tmp_groupfile);
				}

				//* add the Apache user to the client group
				$app->system->add_user_to_group($groupname, escapeshellcmd($web_config['user']));
				
				//* Chown all default directories
				$app->system->chown($data['new']['document_root'],'root');
				$app->system->chgrp($data['new']['document_root'],'root');
				$app->system->chown($data['new']['document_root'].'/cgi-bin',$username);
				$app->system->chgrp($data['new']['document_root'].'/cgi-bin',$groupname);
				if(realpath($data['new']['document_root'].'/'.$log_folder . '/error.log') == '/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log') {
					$app->system->chown($data['new']['document_root'].'/'.$log_folder,'root',false);
					$app->system->chgrp($data['new']['document_root'].'/'.$log_folder,$groupname,false);
				}
				$app->system->chown($data['new']['document_root'].'/ssl','root');
				$app->system->chgrp($data['new']['document_root'].'/ssl','root');
				$app->system->chown($data['new']['document_root'].'/tmp',$username);
				$app->system->chgrp($data['new']['document_root'].'/tmp',$groupname);
				$app->system->chown($data['new']['document_root'].'/web',$username);
				$app->system->chgrp($data['new']['document_root'].'/web',$groupname);
				$app->system->chown($data['new']['document_root'].'/web/error',$username);
				$app->system->chgrp($data['new']['document_root'].'/web/error',$groupname);
				$app->system->chown($data['new']['document_root'].'/web/stats',$username);
				$app->system->chgrp($data['new']['document_root'].'/web/stats',$groupname);
				$app->system->chown($data['new']['document_root'].'/webdav',$username);
				$app->system->chgrp($data['new']['document_root'].'/webdav',$groupname);
				$app->system->chown($data['new']['document_root'].'/private',$username);
				$app->system->chgrp($data['new']['document_root'].'/private',$groupname);

				// If the security Level is set to medium
			} else {

				$app->system->chmod($data['new']['document_root'],0755);
				$app->system->chmod($data['new']['document_root'].'/web',0755);
				$app->system->chmod($data['new']['document_root'].'/webdav',0755);
				$app->system->chmod($data['new']['document_root'].'/ssl',0755);
				$app->system->chmod($data['new']['document_root'].'/cgi-bin',0755);
				
				// make temp directory writable for Apache and the website users
				$app->system->chmod($data['new']['document_root'].'/tmp',0777);
				
				// Set Log directory to 755 to make the logs accessible by the FTP user
				if(realpath($data['new']['document_root'].'/'.$log_folder . '/error.log') == '/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log') {
					$app->system->chmod($data['new']['document_root'].'/'.$log_folder,0755);
				}
                
				$app->system->chown($data['new']['document_root'],'root');
				$app->system->chgrp($data['new']['document_root'],'root');
				$app->system->chown($data['new']['document_root'].'/cgi-bin',$username);
				$app->system->chgrp($data['new']['document_root'].'/cgi-bin',$groupname);
				if(realpath($data['new']['document_root'].'/'.$log_folder . '/error.log') == '/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log') {
					$app->system->chown($data['new']['document_root'].'/'.$log_folder,'root',false);
					$app->system->chgrp($data['new']['document_root'].'/'.$log_folder,$groupname,false);
				}
				
                $app->system->chown($data['new']['document_root'].'/ssl','root');
				$app->system->chgrp($data['new']['document_root'].'/ssl','root');
				$app->system->chown($data['new']['document_root'].'/tmp',$username);
				$app->system->chgrp($data['new']['document_root'].'/tmp',$groupname);
				$app->system->chown($data['new']['document_root'].'/web',$username);
				$app->system->chgrp($data['new']['document_root'].'/web',$groupname);
				$app->system->chown($data['new']['document_root'].'/web/error',$username);
				$app->system->chgrp($data['new']['document_root'].'/web/error',$groupname);
				$app->system->chown($data['new']['document_root'].'/web/stats',$username);
				$app->system->chgrp($data['new']['document_root'].'/web/stats',$groupname);
				$app->system->chown($data['new']['document_root'].'/webdav',$username);
				$app->system->chgrp($data['new']['document_root'].'/webdav',$groupname);
			}
		} elseif(($this->action == 'insert' && $data['new']['type'] == 'vhostsubdomain') or ($web_config['set_folder_permissions_on_update'] == 'y' && $data['new']['type'] == 'vhostsubdomain')) {
			if($web_config['security_level'] == 20) {
				$app->system->chmod($data['new']['document_root'].'/' . $web_folder,0710);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder,$username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder,$groupname);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder . '/error',$username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder . '/error',$groupname);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder . '/stats',$username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder . '/stats',$groupname);
            } else {
				$app->system->chmod($data['new']['document_root'].'/' . $web_folder,0755);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder,$username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder,$groupname);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder . '/error',$username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder . '/error',$groupname);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder . '/stats',$username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder . '/stats',$groupname);
            }
        }
		
		//* Protect web folders
		$app->system->web_folder_protection($data['new']['document_root'],true);

		if($data['new']['type'] == 'vhost') {
            // Change the ownership of the error log to the root user
            if(!@is_file('/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log')) exec('touch '.escapeshellcmd('/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log'));
            $app->system->chown('/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log','root');
            $app->system->chgrp('/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log','root');
        }

		//* Write the custom php.ini file, if custom_php_ini fieled is not empty
		$custom_php_ini_dir = $web_config['website_basedir'].'/conf/'.$data['new']['system_user'];
        if($data['new']['type'] == 'vhostsubdomain') $custom_php_ini_dir .= '_' . $web_folder;
		if(!is_dir($web_config['website_basedir'].'/conf')) $app->system->mkdir($web_config['website_basedir'].'/conf');
		
		//* add open_basedir restriction to custom php.ini content, required for suphp only
		if(!stristr($data['new']['custom_php_ini'],'open_basedir') && $data['new']['php'] == 'suphp') {
			$data['new']['custom_php_ini'] .= "\nopen_basedir = '".$data['new']['php_open_basedir']."'\n";
		}
		//* Create custom php.ini
		if(trim($data['new']['custom_php_ini']) != '') {
			$has_custom_php_ini = true;
			if(!is_dir($custom_php_ini_dir)) $app->system->mkdir($custom_php_ini_dir);
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
				$php_ini_content .= $app->system->file_get_contents($master_php_ini_path)."\n";
			}
			$php_ini_content .= str_replace("\r",'',trim($data['new']['custom_php_ini']));
			$app->system->file_put_contents($custom_php_ini_dir.'/php.ini',$php_ini_content);
		} else {
			$has_custom_php_ini = false;
			if(is_file($custom_php_ini_dir.'/php.ini')) $app->system->unlink($custom_php_ini_dir.'/php.ini');
		}


		//* Create the vhost config file
		$app->load('tpl');

		$tpl = new tpl();
		$tpl->newTemplate('vhost.conf.master');

		$vhost_data = $data['new'];
		//unset($vhost_data['ip_address']);
		$vhost_data['web_document_root'] = $data['new']['document_root'].'/' . $web_folder;
		$vhost_data['web_document_root_www'] = $web_config['website_basedir'].'/'.$data['new']['domain'].'/' . $web_folder;
		$vhost_data['web_basedir'] = $web_config['website_basedir'];
		$vhost_data['security_level'] = $web_config['security_level'];
		$vhost_data['allow_override'] = ($data['new']['allow_override'] == '')?'All':$data['new']['allow_override'];
		$vhost_data['php_open_basedir'] = ($data['new']['php_open_basedir'] == '')?$data['new']['document_root']:$data['new']['php_open_basedir'];
		$vhost_data['ssl_domain'] = $data['new']['ssl_domain'];
		$vhost_data['has_custom_php_ini'] = $has_custom_php_ini;
		$vhost_data['custom_php_ini_dir'] = escapeshellcmd($custom_php_ini_dir);
		
		// Custom Apache directives
		// Make sure we only have Unix linebreaks
		$vhost_data['apache_directives'] = str_replace("\r\n", "\n", $vhost_data['apache_directives']);
		$vhost_data['apache_directives'] = str_replace("\r", "\n", $vhost_data['apache_directives']);

		// Check if a SSL cert exists
		$ssl_dir = $data['new']['document_root'].'/ssl';
		$domain = $data['new']['ssl_domain'];
		$key_file = $ssl_dir.'/'.$domain.'.key';
		$crt_file = $ssl_dir.'/'.$domain.'.crt';
		$bundle_file = $ssl_dir.'/'.$domain.'.bundle';

		/*
		if($domain!='' && $data['new']['ssl'] == 'y' && @is_file($crt_file) && @is_file($key_file) && (@filesize($crt_file)>0)  && (@filesize($key_file)>0)) {
			$vhost_data['ssl_enabled'] = 1;
			$app->log('Enable SSL for: '.$domain,LOGLEVEL_DEBUG);
		} else {
			$vhost_data['ssl_enabled'] = 0;
			$app->log('SSL Disabled. '.$domain,LOGLEVEL_DEBUG);
		}
		*/

		if(@is_file($bundle_file)) $vhost_data['has_bundle_cert'] = 1;

		//$vhost_data['document_root'] = $data['new']['document_root'].'/' . $web_folder;
		
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
			if(substr($data['new']['redirect_path'],0,8) == '[scheme]'){
				$rewrite_target = 'http'.substr($data['new']['redirect_path'],8);
				$rewrite_target_ssl = 'https'.substr($data['new']['redirect_path'],8);
			} else {
				$rewrite_target = $data['new']['redirect_path'];
				$rewrite_target_ssl = $data['new']['redirect_path'];
			}
			/* Disabled path extension
			if($data['new']['redirect_type'] == 'no' && substr($data['new']['redirect_path'],0,4) != 'http') {
				$data['new']['redirect_path'] = $data['new']['document_root'].'/web'.realpath($data['new']['redirect_path']).'/';
			}
			*/

			switch($data['new']['subdomain']) {
				case 'www':
					$rewrite_rules[] = array(	'rewrite_domain' 	=> '^'.$data['new']['domain'],
						'rewrite_type' 		=> ($data['new']['redirect_type'] == 'no')?'':'['.$data['new']['redirect_type'].']',
						'rewrite_target' 	=> $rewrite_target,
						'rewrite_target_ssl' => $rewrite_target_ssl);
					$rewrite_rules[] = array(	'rewrite_domain' 	=> '^www.'.$data['new']['domain'],
							'rewrite_type' 		=> ($data['new']['redirect_type'] == 'no')?'':'['.$data['new']['redirect_type'].']',
							'rewrite_target' 	=> $rewrite_target,
							'rewrite_target_ssl' => $rewrite_target_ssl);
					break;
				case '*':
					$rewrite_rules[] = array(	'rewrite_domain' 	=> '(^|\.)'.$data['new']['domain'],
						'rewrite_type' 		=> ($data['new']['redirect_type'] == 'no')?'':'['.$data['new']['redirect_type'].']',
						'rewrite_target' 	=> $rewrite_target,
						'rewrite_target_ssl' => $rewrite_target_ssl);
					break;
				default:
					$rewrite_rules[] = array(	'rewrite_domain' 	=> '^'.$data['new']['domain'],
						'rewrite_type' 		=> ($data['new']['redirect_type'] == 'no')?'':'['.$data['new']['redirect_type'].']',
						'rewrite_target' 	=> $rewrite_target,
						'rewrite_target_ssl' => $rewrite_target_ssl);
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
		$aliases = $app->db->queryAllRecords('SELECT * FROM web_domain WHERE parent_domain_id = '.$data['new']['domain_id']." AND active = 'y' AND type != 'vhostsubdomain'");
		switch($data['new']['subdomain']) {
			case 'www':
				$server_alias[] .= 'www.'.$data['new']['domain'].' ';
				break;
			case '*':
				$server_alias[] .= '*.'.$data['new']['domain'].' ';
				break;
		}
		if(is_array($aliases)) {
			foreach($aliases as $alias) {
				switch($alias['subdomain']) {
					case 'www':
						$server_alias[] .= 'www.'.$alias['domain'].' '.$alias['domain'].' ';
						break;
					case '*':
						$server_alias[] .= '*.'.$alias['domain'].' '.$alias['domain'].' ';
						break;
					default:
						$server_alias[] .= $alias['domain'].' ';
						break;
				}
				$app->log('Add server alias: '.$alias['domain'],LOGLEVEL_DEBUG);
				// Rewriting
				if($alias['redirect_type'] != '' && $alias['redirect_path'] != '') {
					if(substr($alias['redirect_path'],-1) != '/') $alias['redirect_path'] .= '/';
					if(substr($alias['redirect_path'],0,8) == '[scheme]'){
						$rewrite_target = 'http'.substr($alias['redirect_path'],8);
						$rewrite_target_ssl = 'https'.substr($alias['redirect_path'],8);
					} else {
						$rewrite_target = $alias['redirect_path'];
						$rewrite_target_ssl = $alias['redirect_path'];
					}
					/* Disabled the path extension
					if($data['new']['redirect_type'] == 'no' && substr($data['new']['redirect_path'],0,4) != 'http') {
						$data['new']['redirect_path'] = $data['new']['document_root'].'/web'.realpath($data['new']['redirect_path']).'/';
					}
					*/
					
					switch($alias['subdomain']) {
						case 'www':
							$rewrite_rules[] = array(	'rewrite_domain' 	=> '^'.$alias['domain'],
								'rewrite_type' 		=> ($alias['redirect_type'] == 'no')?'':'['.$alias['redirect_type'].']',
								'rewrite_target' 	=> $rewrite_target,
								'rewrite_target_ssl' => $rewrite_target_ssl);
							$rewrite_rules[] = array(	'rewrite_domain' 	=> '^www.'.$alias['domain'],
									'rewrite_type' 		=> ($alias['redirect_type'] == 'no')?'':'['.$alias['redirect_type'].']',
									'rewrite_target' 	=> $rewrite_target,
									'rewrite_target_ssl' => $rewrite_target_ssl);
							break;
						case '*':
							$rewrite_rules[] = array(	'rewrite_domain' 	=> '(^|\.)'.$alias['domain'],
								'rewrite_type' 		=> ($alias['redirect_type'] == 'no')?'':'['.$alias['redirect_type'].']',
								'rewrite_target' 	=> $rewrite_target,
								'rewrite_target_ssl' => $rewrite_target_ssl);
							break;
						default:
							$rewrite_rules[] = array(	'rewrite_domain' 	=> '^'.$alias['domain'],
								'rewrite_type' 		=> ($alias['redirect_type'] == 'no')?'':'['.$alias['redirect_type'].']',
								'rewrite_target' 	=> $rewrite_target,
								'rewrite_target_ssl' => $rewrite_target_ssl);
					}
				}
			}
		}

		//* If we have some alias records
		if(count($server_alias) > 0) {
			$server_alias_str = '';
			$n = 0;

			// begin a new ServerAlias line after 30 alias domains
			foreach($server_alias as $tmp_alias) {
				if($n % 30 == 0) $server_alias_str .= "\n    ServerAlias ";
				$server_alias_str .= $tmp_alias;
			}
			unset($tmp_alias);

			$tpl->setVar('alias',trim($server_alias_str));
		} else {
			$tpl->setVar('alias','');
		}

		if(count($rewrite_rules) > 0 || $vhost_data['seo_redirect_enabled'] > 0) {
			$tpl->setVar('rewrite_enabled',1);
		} else {
			$tpl->setVar('rewrite_enabled',0);
		}

		//$tpl->setLoop('redirects',$rewrite_rules);

		/**
		 * install fast-cgi starter script and add script aliasd config
		 * first we create the script directory if not already created, then copy over the starter script
		 * settings are copied over from the server ini config for now
		 * TODO: Create form for fastcgi configs per site.
		 */

		if ($data['new']['php'] == 'fast-cgi') {
			$fastcgi_config = $app->getconf->get_server_config($conf['server_id'], 'fastcgi');

			$fastcgi_starter_path = str_replace('[system_user]',$data['new']['system_user'],$fastcgi_config['fastcgi_starter_path']);
			$fastcgi_starter_path = str_replace('[client_id]',$client_id,$fastcgi_starter_path);

			if (!is_dir($fastcgi_starter_path)) {
				$app->system->mkdirpath($fastcgi_starter_path);
				//exec('chown '.$data['new']['system_user'].':'.$data['new']['system_group'].' '.escapeshellcmd($fastcgi_starter_path));


				$app->log('Creating fastcgi starter script directory: '.$fastcgi_starter_path,LOGLEVEL_DEBUG);
			}

			//exec('chown -R '.$data['new']['system_user'].':'.$data['new']['system_group'].' '.escapeshellcmd($fastcgi_starter_path));
			$app->system->chown($fastcgi_starter_path,$data['new']['system_user']);
			$app->system->chgrp($fastcgi_starter_path,$data['new']['system_group']);
			
			$fcgi_tpl = new tpl();
			$fcgi_tpl->newTemplate('php-fcgi-starter.master');
			
			// Support for multiple PHP versions (FastCGI)
			if(trim($data['new']['fastcgi_php_version']) != ''){
				$default_fastcgi_php = false;
				list($custom_fastcgi_php_name, $custom_fastcgi_php_executable, $custom_fastcgi_php_ini_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
				if(substr($custom_fastcgi_php_ini_dir,-1) != '/') $custom_fastcgi_php_ini_dir .= '/';
			} else {
				$default_fastcgi_php = true;
			}
			
			if($has_custom_php_ini) {
				$fcgi_tpl->setVar('php_ini_path',escapeshellcmd($custom_php_ini_dir));
			} else {
				if($default_fastcgi_php){
					$fcgi_tpl->setVar('php_ini_path',escapeshellcmd($fastcgi_config['fastcgi_phpini_path']));
				} else {
					$fcgi_tpl->setVar('php_ini_path',escapeshellcmd($custom_fastcgi_php_ini_dir));
				}
			}
			$fcgi_tpl->setVar('document_root',escapeshellcmd($data['new']['document_root']));
			$fcgi_tpl->setVar('php_fcgi_children',escapeshellcmd($fastcgi_config['fastcgi_children']));
			$fcgi_tpl->setVar('php_fcgi_max_requests',escapeshellcmd($fastcgi_config['fastcgi_max_requests']));
			if($default_fastcgi_php){
				$fcgi_tpl->setVar('php_fcgi_bin',escapeshellcmd($fastcgi_config['fastcgi_bin']));
			} else {
				$fcgi_tpl->setVar('php_fcgi_bin',escapeshellcmd($custom_fastcgi_php_executable));
			}
			$fcgi_tpl->setVar('security_level',intval($web_config['security_level']));

			$php_open_basedir = ($data['new']['php_open_basedir'] == '')?$data['new']['document_root']:$data['new']['php_open_basedir'];
			$fcgi_tpl->setVar('open_basedir', escapeshellcmd($php_open_basedir));

			$fcgi_starter_script = escapeshellcmd($fastcgi_starter_path.$fastcgi_config['fastcgi_starter_script'].($data['new']['type'] == 'vhostsubdomain' ? '_web' . $data['new']['domain_id'] : ''));
			$app->system->file_put_contents($fcgi_starter_script,$fcgi_tpl->grab());
			unset($fcgi_tpl);

			$app->log('Creating fastcgi starter script: '.$fcgi_starter_script,LOGLEVEL_DEBUG);

			$app->system->chmod($fcgi_starter_script,0755);
			$app->system->chown($fcgi_starter_script,$data['new']['system_user']);
			$app->system->chgrp($fcgi_starter_script,$data['new']['system_group']);
						
			$tpl->setVar('fastcgi_alias',$fastcgi_config['fastcgi_alias']);
			$tpl->setVar('fastcgi_starter_path',$fastcgi_starter_path);
			$tpl->setVar('fastcgi_starter_script',$fastcgi_config['fastcgi_starter_script'].($data['new']['type'] == 'vhostsubdomain' ? '_web' . $data['new']['domain_id'] : ''));
			$tpl->setVar('fastcgi_config_syntax',$fastcgi_config['fastcgi_config_syntax']);

		} else {
			//remove the php fastgi starter script if available
			if ($data['old']['php'] == 'fast-cgi') {
                $fastcgi_config = $app->getconf->get_server_config($conf['server_id'], 'fastcgi');
                $fastcgi_starter_path = str_replace('[system_user]',$data['old']['system_user'],$fastcgi_config['fastcgi_starter_path']);
                $fastcgi_starter_path = str_replace('[client_id]',$client_id,$fastcgi_starter_path);
                if($data['old']['type'] == 'vhost') {
                    if (is_dir($fastcgi_starter_path)) {
                        exec('rm -rf '.$fastcgi_starter_path);
                    }
                } else {
                    $fcgi_starter_script = $fastcgi_starter_path.$fastcgi_config['fastcgi_starter_script'].'_web' . $data['old']['domain_id'];
                    if (file_exists($fcgi_starter_script)) {
                        exec('rm -f '.$fcgi_starter_script);
                    }
                }
			}
		}
		
		/**
		* PHP-FPM
		*/
		// Support for multiple PHP versions
		if($data['new']['php'] == 'php-fpm'){
			if(trim($data['new']['fastcgi_php_version']) != ''){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		} else {
			if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] == 'php-fpm'){
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
		$tpl->setVar('fpm_port', $web_config['php_fpm_start_port'] + $data['new']['domain_id'] - 1);

		/**
		 * install cgi starter script and add script alias to config.
		 * This is needed to allow cgi with suexec (to do so, we need a bin in the document-path!)
		 * first we create the script directory if not already created, then copy over the starter script.
		 * TODO: we have to fetch the data from the server-settings.
		 */

		if ($data['new']['php'] == 'cgi') {
			//$cgi_config = $app->getconf->get_server_config($conf['server_id'], 'cgi');

			$cgi_config['cgi_starter_path'] = $web_config['website_basedir'].'/php-cgi-scripts/[system_user]/';
			$cgi_config['cgi_starter_script'] = 'php-cgi-starter'.($data['new']['type'] == 'vhostsubdomain' ? '_web' . $data['new']['domain_id'] : '');
			$cgi_config['cgi_bin'] = '/usr/bin/php-cgi';

			$cgi_starter_path = str_replace('[system_user]',$data['new']['system_user'],$cgi_config['cgi_starter_path']);
			$cgi_starter_path = str_replace('[client_id]',$client_id,$cgi_starter_path);

			if (!is_dir($cgi_starter_path)) {
				$app->system->mkdirpath($cgi_starter_path);
				$app->system->chmod($cgi_starter_script,0755);
				$app->system->chown($cgi_starter_script,$data['new']['system_user']);
				$app->system->chgrp($cgi_starter_script,$data['new']['system_group']);

				$app->log('Creating cgi starter script directory: '.$cgi_starter_path,LOGLEVEL_DEBUG);
			}

			$cgi_tpl = new tpl();
			$cgi_tpl->newTemplate('php-cgi-starter.master');

			// This works because PHP "rewrites" a symlink to the physical path
			$php_open_basedir = ($data['new']['php_open_basedir'] == '')?$data['new']['document_root']:$data['new']['php_open_basedir'];
			$cgi_tpl->setVar('open_basedir', escapeshellcmd($php_open_basedir));
			$cgi_tpl->setVar('document_root', escapeshellcmd($data['new']['document_root']));

			// This will NOT work!
			//$cgi_tpl->setVar('open_basedir', '/var/www/' . $data['new']['domain']);
			$cgi_tpl->setVar('php_cgi_bin',$cgi_config['cgi_bin']);
			$cgi_tpl->setVar('security_level',$web_config['security_level']);
			
			$cgi_tpl->setVar('has_custom_php_ini',$has_custom_php_ini);
			if($has_custom_php_ini) {
				$cgi_tpl->setVar('php_ini_path',escapeshellcmd($custom_php_ini_dir));
			} else {
				$cgi_tpl->setVar('php_ini_path',escapeshellcmd($fastcgi_config['fastcgi_phpini_path']));
			}

			$cgi_starter_script = escapeshellcmd($cgi_starter_path.$cgi_config['cgi_starter_script'].($data['new']['type'] == 'vhostsubdomain' ? '_web' . $data['new']['domain_id'] : ''));
			$app->system->file_put_contents($cgi_starter_script,$cgi_tpl->grab());
			unset($cgi_tpl);

			$app->log('Creating cgi starter script: '.$cgi_starter_script,LOGLEVEL_DEBUG);


			$app->system->chmod($cgi_starter_script,0755);
			$app->system->chown($cgi_starter_script,$data['new']['system_user']);
			$app->system->chgrp($cgi_starter_script,$data['new']['system_group']);

			$tpl->setVar('cgi_starter_path',$cgi_starter_path);
			$tpl->setVar('cgi_starter_script',$cgi_config['cgi_starter_script'].($data['new']['type'] == 'vhostsubdomain' ? '_web' . $data['new']['domain_id'] : ''));

		}

		$vhost_file = escapeshellcmd($web_config['vhost_conf_dir'].'/'.$data['new']['domain'].'.vhost');
		//* Make a backup copy of vhost file
		if(file_exists($vhost_file)) $app->system->copy($vhost_file,$vhost_file.'~');
		
		//* create empty vhost array
		$vhosts = array();
		
		//* Add vhost for ipv4 IP	
		if(count($rewrite_rules) > 0){
			$vhosts[] = array('ip_address' => $data['new']['ip_address'], 'ssl_enabled' => 0, 'port' => 80, 'redirects' => $rewrite_rules);
		} else {
			$vhosts[] = array('ip_address' => $data['new']['ip_address'], 'ssl_enabled' => 0, 'port' => 80);
		}
		
		//* Add vhost for ipv4 IP with SSL
		$ssl_dir = $data['new']['document_root'].'/ssl';
		$domain = $data['new']['ssl_domain'];
		$key_file = $ssl_dir.'/'.$domain.'.key';
		$crt_file = $ssl_dir.'/'.$domain.'.crt';
		
		if($data['new']['ssl_domain'] != '' && $data['new']['ssl'] == 'y' && @is_file($crt_file) && @is_file($key_file) && (@filesize($crt_file)>0)  && (@filesize($key_file)>0)) {
			if(count($rewrite_rules) > 0){
				$vhosts[] = array('ip_address' => $data['new']['ip_address'], 'ssl_enabled' => 1, 'port' => '443', 'redirects' => $rewrite_rules);
			} else {
				$vhosts[] = array('ip_address' => $data['new']['ip_address'], 'ssl_enabled' => 1, 'port' => '443');
			}
			$app->log('Enable SSL for: '.$domain,LOGLEVEL_DEBUG);
		}
		
		//* Add vhost for IPv6 IP
		if($data['new']['ipv6_address'] != '') {
			if(count($rewrite_rules) > 0){
				$vhosts[] = array('ip_address' => '['.$data['new']['ipv6_address'].']', 'ssl_enabled' => 0, 'port' => 80, 'redirects' => $rewrite_rules);
			} else {
				$vhosts[] = array('ip_address' => '['.$data['new']['ipv6_address'].']', 'ssl_enabled' => 0, 'port' => 80);
			}
		
			//* Add vhost for ipv6 IP with SSL
			if($data['new']['ssl_domain'] != '' && $data['new']['ssl'] == 'y' && @is_file($crt_file) && @is_file($key_file) && (@filesize($crt_file)>0)  && (@filesize($key_file)>0)) {
				
				if(count($rewrite_rules) > 0){
					$vhosts[] = array('ip_address' => '['.$data['new']['ipv6_address'].']', 'ssl_enabled' => 1, 'port' => '443', 'redirects' => $rewrite_rules);
				} else {
					$vhosts[] = array('ip_address' => '['.$data['new']['ipv6_address'].']', 'ssl_enabled' => 1, 'port' => '443');
				}
				$app->log('Enable SSL for IPv6: '.$domain,LOGLEVEL_DEBUG);
			}
		}
		
		//* Set the vhost loop
		$tpl->setLoop('vhosts',$vhosts);
		
		//* Write vhost file
		$app->system->file_put_contents($vhost_file,$tpl->grab());
		$app->log('Writing the vhost file: '.$vhost_file,LOGLEVEL_DEBUG);
		unset($tpl);

		/*
		 * maybe we have some webdav - user. If so, add them...
		*/
		$this->_patchVhostWebdav($vhost_file, $data['new']['document_root'] . '/webdav');

		//* Set the symlink to enable the vhost
		//* First we check if there is a old type of symlink and remove it
		$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/'.$data['new']['domain'].'.vhost');
		if(is_link($vhost_symlink)) $app->system->unlink($vhost_symlink);
		
		//* Remove old or changed symlinks
		if($data['new']['subdomain'] != $data['old']['subdomain'] or $data['new']['active'] == 'n') {
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/900-'.$data['new']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/100-'.$data['new']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
		}
		
		//* New symlink
		if($data['new']['subdomain'] == '*') {
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/900-'.$data['new']['domain'].'.vhost');
		} else {
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/100-'.$data['new']['domain'].'.vhost');
		}
		if($data['new']['active'] == 'y' && !is_link($vhost_symlink)) {
			symlink($vhost_file,$vhost_symlink);
			$app->log('Creating symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
		}

		// remove old symlink and vhost file, if domain name of the site has changed
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/900-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/100-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_file = escapeshellcmd($web_config['vhost_conf_dir'].'/'.$data['old']['domain'].'.vhost');
			$app->system->unlink($vhost_file);
			$app->log('Removing file: '.$vhost_file,LOGLEVEL_DEBUG);
		}

		//* Create .htaccess and .htpasswd file for website statistics
		if(!is_file($data['new']['document_root'].'/' . $web_folder . '/stats/.htaccess') or $data['old']['document_root'] != $data['new']['document_root']) {
			if(!is_dir($data['new']['document_root'].'/' . $web_folder . '/stats')) $app->system->mkdir($data['new']['document_root'].'/' . $web_folder . '/stats');
			$ht_file = "AuthType Basic\nAuthName \"Members Only\"\nAuthUserFile ".$data['new']['document_root']."/.htpasswd_stats\nrequire valid-user";
			$app->system->file_put_contents($data['new']['document_root'].'/' . $web_folder . '/stats/.htaccess',$ht_file);
			$app->system->chmod($data['new']['document_root'].'/' . $web_folder . '/stats/.htaccess',0755);
			unset($ht_file);
		}

		if(!is_file($data['new']['document_root'].'/.htpasswd_stats') || $data['new']['stats_password'] != $data['old']['stats_password']) {
			if(trim($data['new']['stats_password']) != '') {
				$htp_file = 'admin:'.trim($data['new']['stats_password']);
				$app->system->web_folder_protection($data['new']['document_root'],false);
				$app->system->file_put_contents($data['new']['document_root'].'/.htpasswd_stats',$htp_file);
				$app->system->web_folder_protection($data['new']['document_root'],true);
				$app->system->chmod($data['new']['document_root'].'/.htpasswd_stats',0755);
				unset($htp_file);
			}
		}
		
		//* Create awstats configuration
		if($data['new']['stats_type'] == 'awstats' && ($data['new']['type'] == 'vhost' || $data['new']['type'] == 'vhostsubdomain')) {
			$this->awstats_update($data,$web_config);
		}
		
		$this->php_fpm_pool_update($data,$web_config,$pool_dir,$pool_name,$socket_dir);
		
		if($web_config['check_apache_config'] == 'y') {
			//* Test if apache starts with the new configuration file
			$apache_online_status_before_restart = $this->_checkTcp('localhost',80);
			$app->log('Apache status is: '.$apache_online_status_before_restart,LOGLEVEL_DEBUG);

			$app->services->restartService('httpd','restart');
			
			// wait a few seconds, before we test the apache status again
			sleep(2);
		
			//* Check if apache restarted successfully if it was online before
			$apache_online_status_after_restart = $this->_checkTcp('localhost',80);
			$app->log('Apache online status after restart is: '.$apache_online_status_after_restart,LOGLEVEL_DEBUG);
			if($apache_online_status_before_restart && !$apache_online_status_after_restart) {
				$app->log('Apache did not restart after the configuration change for website '.$data['new']['domain'].' Reverting the configuration. Saved non-working config as '.$vhost_file.'.err',LOGLEVEL_WARN);
				$app->system->copy($vhost_file,$vhost_file.'.err');
				if(is_file($vhost_file.'~')) {
					//* Copy back the last backup file
					$app->system->copy($vhost_file.'~',$vhost_file);
				} else {
					//* There is no backup file, so we create a empty vhost file with a warning message inside
					$app->system->file_put_contents($vhost_file,"# Apache did not start after modifying this vhost file.\n# Please check file $vhost_file.err for syntax errors.");
				}
				if($this->ssl_certificate_changed === true) {

					$ssl_dir = $data['new']['document_root'].'/ssl';
					$domain = $data['new']['ssl_domain'];
					$key_file = $ssl_dir.'/'.$domain.'.key.org';
					$key_file2 = $ssl_dir.'/'.$domain.'.key';
					$csr_file = $ssl_dir.'/'.$domain.'.csr';
					$crt_file = $ssl_dir.'/'.$domain.'.crt';
					$bundle_file = $ssl_dir.'/'.$domain.'.bundle';
					
					//* Backup the files that might have caused the error
					if(is_file($key_file)) $app->system->copy($key_file,$key_file.'.err');
					if(is_file($key_file2)) $app->system->copy($key_file2,$key_file2.'.err');
					if(is_file($csr_file)) $app->system->copy($csr_file,$csr_file.'.err');
					if(is_file($crt_file)) $app->system->copy($crt_file,$crt_file.'.err');
					if(is_file($bundle_file)) $app->system->copy($bundle_file,$bundle_file.'.err');
					
					//* Restore the ~ backup files
					if(is_file($key_file.'~')) $app->system->copy($key_file.'~',$key_file);
					if(is_file($key_file2.'~')) $app->system->copy($key_file2.'~',$key_file2);
					if(is_file($crt_file.'~')) $app->system->copy($crt_file.'~',$crt_file);
					if(is_file($csr_file.'~')) $app->system->copy($csr_file.'~',$csr_file);
					if(is_file($bundle_file.'~')) $app->system->copy($bundle_file.'~',$bundle_file);
					
					$app->log('Apache did not restart after the configuration change for website '.$data['new']['domain'].' Reverting the SSL configuration. Saved non-working SSL files with .err extension.',LOGLEVEL_WARN);
				}
				
				$app->services->restartService('httpd','restart');
			}
		} else {
			//* We do not check the apache config after changes (is faster)
			if($apache_chrooted) {
				$app->services->restartServiceDelayed('httpd','restart');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd','reload');
			}
		}
		
		//* The vhost is written and apache has been restarted, so we 
		// can reset the ssl changed var to false and cleanup some files
		$this->ssl_certificate_changed = false;
		
		$ssl_dir = $data['new']['document_root'].'/ssl';
		$domain = $data['new']['ssl_domain'];
		$key_file = $ssl_dir.'/'.$domain.'.key.org';
		$key_file2 = $ssl_dir.'/'.$domain.'.key';
		$csr_file = $ssl_dir.'/'.$domain.'.csr';
		$crt_file = $ssl_dir.'/'.$domain.'.crt';
		$bundle_file = $ssl_dir.'/'.$domain.'.bundle';
		
		if(@is_file($key_file.'~')) $app->system->unlink($key_file.'~');
		if(@is_file($key2_file.'~')) $app->system->unlink($key2_file.'~');
		if(@is_file($crt_file.'~')) $app->system->unlink($crt_file.'~');
		if(@is_file($csr_file.'~')) $app->system->unlink($csr_file.'~');
		if(@is_file($bundle_file.'~')) $app->system->unlink($bundle_file.'~');
		
		// Remove the backup copy of the config file.
		if(@is_file($vhost_file.'~')) $app->system->unlink($vhost_file.'~');

		//* Unset action to clean it for next processed vhost.
		$this->action = '';

	}

	function delete($event_name,$data) {
		global $app, $conf;

		// load the server configuration options
		$app->uses('getconf');
		$app->uses('system');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		
		$app->system->web_folder_protection($data['old']['document_root'],false);

		//* Check if this is a chrooted setup
		if($web_config['website_basedir'] != '' && @is_file($web_config['website_basedir'].'/etc/passwd')) {
			$apache_chrooted = true;
		} else {
			$apache_chrooted = false;
		}

		if($data['old']['type'] != 'vhost' && $data['old']['type'] != 'vhostsubdomain' && $data['old']['parent_domain_id'] > 0) {
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
			$vhost_file = escapeshellcmd($web_config['vhost_conf_dir'].'/'.$data['old']['domain'].'.vhost');
			
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)){
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/900-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)){
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config['vhost_conf_enabled_dir'].'/100-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)){
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file,LOGLEVEL_DEBUG);
			}
			
			$app->system->unlink($vhost_file);
			$app->log('Removing vhost file: '.$vhost_file,LOGLEVEL_DEBUG);
            
            if($data['old']['type'] == 'vhost') {
                $docroot = escapeshellcmd($data['old']['document_root']);
                if($docroot != '' && !stristr($docroot,'..')) exec('rm -rf '.$docroot);
			
                //remove the php fastgi starter script if available
                if ($data['old']['php'] == 'fast-cgi') {
                    $fastcgi_starter_path = str_replace('[system_user]',$data['old']['system_user'],$web_config['fastcgi_starter_path']);
                    if($data['old']['type'] == 'vhost') {
                        if (is_dir($fastcgi_starter_path)) {
                            exec('rm -rf '.$fastcgi_starter_path);
                        }
                    } else {
                        $fcgi_starter_script = $fastcgi_starter_path.$web_config['fastcgi_starter_script'].'_web'.$data['old']['domain_id'];
                        if (file_exists($fcgi_starter_script)) {
                            exec('rm -f '.$fcgi_starter_script);
                        }
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
                    if($data['old']['type'] == 'vhost') {
                        if (is_dir($cgi_starter_path)) {
                            exec('rm -rf '.$cgi_starter_path);
                        }
                    } else {
                        $cgi_starter_script = $cgi_starter_path.'php-cgi-starter_web'.$data['old']['domain_id'];
                        if (file_exists($cgi_starter_script)) {
                            exec('rm -f '.$cgi_starter_script);
                        }
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
                            $app->system->unlink($tmp_symlink);
                            $app->log('Removing symlink: '.$tmp_symlink,LOGLEVEL_DEBUG);
                        }
                    }
                }
                // end removing symlinks
            } else {
                // vhost subdomain
            }

            // Delete the log file directory
            $vhost_logfile_dir = escapeshellcmd('/var/log/ispconfig/httpd/'.$data['old']['domain']);
            if($data['old']['domain'] != '' && !stristr($vhost_logfile_dir,'..')) exec('rm -rf '.$vhost_logfile_dir);
            $app->log('Removing website logfile directory: '.$vhost_logfile_dir,LOGLEVEL_DEBUG);
            
            if($data['old']['type'] == 'vhost') {
                //delete the web user
                $command = 'userdel';
                $command .= ' '.$data['old']['system_user'];
                exec($command);
                if($apache_chrooted) $this->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' '.$command);
                
            }
            
            //* Remove the awstats configuration file
            if($data['old']['stats_type'] == 'awstats') {
                $this->awstats_delete($data,$web_config);
            }
			
			if($apache_chrooted) {
				$app->services->restartServiceDelayed('httpd','restart');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd','reload');
			}

		}
	}

	//* This function is called when a IP on the server is inserted, updated or deleted
	function server_ip($event_name,$data) {
		global $app, $conf;

		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		$app->load('tpl');

		$tpl = new tpl();
		$tpl->newTemplate('apache_ispconfig.conf.master');
		$records = $app->db->queryAllRecords('SELECT * FROM server_ip WHERE server_id = '.$conf['server_id']." AND virtualhost = 'y'");
		
		$records_out= array();
		if(is_array($records)) {
			foreach($records as $rec) {
				if($rec['ip_type'] == 'IPv6') {
					$ip_address = '['.$rec['ip_address'].']';
				} else {
					$ip_address = $rec['ip_address'];
				}
				$ports = explode(',',$rec['virtualhost_port']);
				if(is_array($ports)) {
					foreach($ports as $port) {
						$port = intval($port);
						if($port > 0 && $port < 65536 && $ip_address != '') {
							$records_out[] = array('ip_address' => $ip_address, 'port' => $port);
						}
					}
				}
			}
		}
		
		
		if(count($records_out) > 0) {
			$tpl->setLoop('ip_adresses',$records_out);
		}

		$vhost_file = escapeshellcmd($web_config['vhost_conf_dir'].'/ispconfig.conf');
		$app->system->file_put_contents($vhost_file,$tpl->grab());
		$app->log('Writing the conf file: '.$vhost_file,LOGLEVEL_DEBUG);
		unset($tpl);

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
		
        $web_folder = 'web';
        if($website['type'] == 'vhostsubdomain') $web_folder = $website['web_folder'];
        
		//* Get the folder path.
		if(substr($folder['path'],0,1) == '/') $folder['path'] = substr($folder['path'],1);
		if(substr($folder['path'],-1) == '/') $folder['path'] = substr($folder['path'],0,-1);
		$folder_path = escapeshellcmd($website['document_root'].'/' . $web_folder . '/'.$folder['path']);
		if(substr($folder_path,-1) != '/') $folder_path .= '/';
		
		//* Check if the resulting path is inside the docroot
		if(stristr($folder_path,'..') || stristr($folder_path,'./') || stristr($folder_path,'\\')) {
			$app->log('Folder path "'.$folder_path.'" contains .. or ./.',LOGLEVEL_DEBUG);
			return false;
		}
		
		//* Create the folder path, if it does not exist
		if(!is_dir($folder_path)) {
			$app->system->mkdirpath($folder_path);
			$app->system->chown($folder_path,$website['system_user']);
			$app->system->chgrp($folder_path,$website['system_group']);
		}
		
		//* Create empty .htpasswd file, if it does not exist
		if(!is_file($folder_path.'.htpasswd')) {
			touch($folder_path.'.htpasswd');
			$app->system->chmod($folder_path.'.htpasswd',0755);
			$app->system->chown($folder_path.'.htpasswd',$website['system_user']);
			$app->system->chgrp($folder_path.'.htpasswd',$website['system_group']);
			$app->log('Created file '.$folder_path.'.htpasswd',LOGLEVEL_DEBUG);
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
		
		
		//* Create the .htaccess file
		//if(!is_file($folder_path.'.htaccess')) {
			$ht_file = "AuthType Basic\nAuthName \"Members Only\"\nAuthUserFile ".$folder_path.".htpasswd\nrequire valid-user";
			$app->system->file_put_contents($folder_path.'.htaccess',$ht_file);
			$app->system->chmod($folder_path.'.htaccess',0755);
			$app->system->chown($folder_path.'.htaccess',$website['system_user']);
			$app->system->chgrp($folder_path.'.htaccess',$website['system_group']);
			$app->log('Created file '.$folder_path.'.htaccess',LOGLEVEL_DEBUG);
		//}
		
	}
	
	//* Remove .htaccess and .htpasswd file, when folder protection is removed
	function web_folder_delete($event_name,$data) {
		global $app, $conf;
		
		$folder_id = $data['old']['web_folder_id'];
		
		$folder = $data['old'];
		$website = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($folder['parent_domain_id']));
		
		if(!is_array($folder) or !is_array($website)) {
			$app->log('Not able to retrieve folder or website record.',LOGLEVEL_DEBUG);
			return false;
		}
		
        $web_folder = 'web';
        if($website['type'] == 'vhostsubdomain') $web_folder = $website['web_folder'];
        
		//* Get the folder path.
		if(substr($folder['path'],0,1) == '/') $folder['path'] = substr($folder['path'],1);
		if(substr($folder['path'],-1) == '/') $folder['path'] = substr($folder['path'],0,-1);
		$folder_path = realpath($website['document_root'].'/' . $web_folder . '/'.$folder['path']);
		if(substr($folder_path,-1) != '/') $folder_path .= '/';
		
		//* Check if the resulting path is inside the docroot
		if(substr($folder_path,0,strlen($website['document_root'])) != $website['document_root']) {
			$app->log('Folder path is outside of docroot.',LOGLEVEL_DEBUG);
			return false;
		}
		
		//* Remove .htpasswd file
		if(is_file($folder_path.'.htpasswd')) {
			$app->system->unlink($folder_path.'.htpasswd');
			$app->log('Removed file '.$folder_path.'.htpasswd',LOGLEVEL_DEBUG);
		}
		
		//* Remove .htaccess file
		if(is_file($folder_path.'.htaccess')) {
			$app->system->unlink($folder_path.'.htaccess');
			$app->log('Removed file '.$folder_path.'.htaccess',LOGLEVEL_DEBUG);
		}
	}
	
	//* Update folder protection, when path has been changed
	function web_folder_update($event_name,$data) {
		global $app, $conf;
		
		$website = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ".intval($data['new']['parent_domain_id']));
	
		if(!is_array($website)) {
			$app->log('Not able to retrieve folder or website record.',LOGLEVEL_DEBUG);
			return false;
		}
		
        $web_folder = 'web';
        if($website['type'] == 'vhostsubdomain') $web_folder = $website['web_folder'];
        
		//* Get the folder path.
		if(substr($data['old']['path'],0,1) == '/') $data['old']['path'] = substr($data['old']['path'],1);
		if(substr($data['old']['path'],-1) == '/') $data['old']['path'] = substr($data['old']['path'],0,-1);
		$old_folder_path = realpath($website['document_root'].'/' . $web_folder . '/'.$data['old']['path']);
		if(substr($old_folder_path,-1) != '/') $old_folder_path .= '/';
			
		if(substr($data['new']['path'],0,1) == '/') $data['new']['path'] = substr($data['new']['path'],1);
		if(substr($data['new']['path'],-1) == '/') $data['new']['path'] = substr($data['new']['path'],0,-1);
		$new_folder_path = escapeshellcmd($website['document_root'].'/' . $web_folder . '/'.$data['new']['path']);
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
		if(!is_dir($new_folder_path)) $app->system->mkdirpath($new_folder_path);
		
		if($data['old']['path'] != $data['new']['path']) {

		
			//* move .htpasswd file
			if(is_file($old_folder_path.'.htpasswd')) {
				$app->system->rename($old_folder_path.'.htpasswd',$new_folder_path.'.htpasswd');
				$app->log('Moved file '.$old_folder_path.'.htpasswd to '.$new_folder_path.'.htpasswd',LOGLEVEL_DEBUG);
			}
			
			//* delete old .htaccess file
			if(is_file($old_folder_path.'.htaccess')) {
				$app->system->unlink($old_folder_path.'.htaccess');
				$app->log('Deleted file '.$old_folder_path.'.htaccess',LOGLEVEL_DEBUG);
			}
		
		}
		
		//* Create the .htaccess file
		if($data['new']['active'] == 'y') {
			$ht_file = "AuthType Basic\nAuthName \"Members Only\"\nAuthUserFile ".$new_folder_path.".htpasswd\nrequire valid-user";
			$app->system->file_put_contents($new_folder_path.'.htaccess',$ht_file);
			$app->system->chmod($new_folder_path.'.htpasswd',0755);
			$app->system->chown($folder_path.'.htpasswd',$website['system_user']);
			$app->system->chgrp($folder_path.'.htpasswd',$website['system_group']);
			$app->log('Created file '.$new_folder_path.'.htpasswd',LOGLEVEL_DEBUG);
		}
		
		//* Remove .htaccess file
		if($data['new']['active'] == 'n' && is_file($new_folder_path.'.htaccess')) {
			$app->system->unlink($new_folder_path.'.htaccess');
			$app->log('Removed file '.$new_folder_path.'.htaccess',LOGLEVEL_DEBUG);
		}
		
		
	}
	
	public function ftp_user_delete($event_name,$data) {
		global $app, $conf;
		
		$ftpquota_file = $data['old']['dir'].'/.ftpquota';
		if(file_exists($ftpquota_file)) $app->system->unlink($ftpquota_file);
		
	}
	
	

	/**
	 * This function is called when a Webdav-User is inserted, updated or deleted.
	 *
	 * @author Oliver Vogel
	 * @param string $event_name
	 * @param array $data
	 */
	public function webdav($event_name,$data) {
		global $app, $conf;
		
		/*
		 * load the server configuration options
		*/
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		if (($event_name == 'webdav_user_insert') || ($event_name == 'webdav_user_update')) {

			/*
			 * Get additional informations
			*/
			$sitedata = $app->db->queryOneRecord('SELECT document_root, domain, system_user, system_group FROM web_domain WHERE domain_id = ' . $data['new']['parent_domain_id']);
			$documentRoot = $sitedata['document_root'];
			$domain = $sitedata['domain'];
			$user = $sitedata['system_user'];
			$group = $sitedata['system_group'];
			$webdav_user_dir = $documentRoot . '/webdav/' . $data['new']['dir'];

			/* Check if this is a chrooted setup */
			if($web_config['website_basedir'] != '' && @is_file($web_config['website_basedir'].'/etc/passwd')) {
				$apache_chrooted = true;
				$app->log('Info: Apache is chrooted.',LOGLEVEL_DEBUG);
			} else {
				$apache_chrooted = false;
			}
			
			//* We dont want to have relative paths here
			if(stristr($webdav_user_dir,'..')  || stristr($webdav_user_dir,'./')) {
				$app->log('Folder path '.$webdav_user_dir.' contains ./ or .. '.$documentRoot,LOGLEVEL_WARN);
				return false;
			}
			
			//* Check if the resulting path exists if yes, if it is inside the docroot
			if(is_dir($webdav_user_dir) && substr(realpath($webdav_user_dir),0,strlen($documentRoot)) != $documentRoot) {
				$app->log('Folder path '.$webdav_user_dir.' is outside of docroot '.$documentRoot,LOGLEVEL_WARN);
				return false;
			}

			/*
			 * First the webdav-root - folder has to exist
			*/
			if(!is_dir($webdav_user_dir)) {
				$app->log('Webdav User directory '.$webdav_user_dir.' does not exist. Creating it now.',LOGLEVEL_DEBUG);
				$app->system->mkdirpath($webdav_user_dir);
			}

			/*
			 * The webdav - Root needs the group/user as owner and the apache as read and write
			*/
			//$this->_exec('chown ' . $user . ':' . $group . ' ' . escapeshellcmd($documentRoot . '/webdav/'));
			//$this->_exec('chmod 770 ' . escapeshellcmd($documentRoot . '/webdav/'));
			$app->system->chown($documentRoot . '/webdav',$user);
			$app->system->chgrp($documentRoot . '/webdav',$group);
			$app->system->chmod($documentRoot . '/webdav',0770);

			/*
			 * The webdav folder (not the webdav-root!) needs the same (not in ONE step, because the
			 * pwd-files are owned by root)
			*/
			//$this->_exec('chown ' . $user . ':' . $group . ' ' . escapeshellcmd($webdav_user_dir.' -R'));
			//$this->_exec('chmod 770 ' . escapeshellcmd($webdav_user_dir.' -R'));
			$app->system->chown($webdav_user_dir,$user);
			$app->system->chgrp($webdav_user_dir,$group);
			$app->system->chmod($webdav_user_dir,0770);

			/*
			 * if the user is active, we have to write/update the password - file
			 * if the user is inactive, we have to inactivate the user by removing the user from the file
			*/
			if ($data['new']['active'] == 'y') {
				$this->_writeHtDigestFile( $webdav_user_dir . '.htdigest', $data['new']['username'], $data['new']['dir'], $data['new']['password']);
			}
			else {
				/* empty pwd removes the user! */
				$this->_writeHtDigestFile( $webdav_user_dir . '.htdigest', $data['new']['username'], $data['new']['dir'], '');
			}

			/*
			 * Next step, patch the vhost - file
			*/
			$vhost_file = escapeshellcmd($web_config['vhost_conf_dir'] . '/' . $domain . '.vhost');
			$this->_patchVhostWebdav($vhost_file, $documentRoot . '/webdav');

			/*
			 * Last, restart apache
			*/
			if($apache_chrooted) {
				$app->services->restartServiceDelayed('httpd','restart');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd','reload');
			}

		}

		if ($event_name == 'webdav_user_delete') {
			/*
			 * Get additional informations
			*/
			$sitedata = $app->db->queryOneRecord('SELECT document_root, domain FROM web_domain WHERE domain_id = ' . $data['old']['parent_domain_id']);
			$documentRoot = $sitedata['document_root'];
			$domain = $sitedata['domain'];

			/*
			 * We dont't want to destroy any (transfer)-Data. So we do NOT delete any dir.
			 * So the only thing, we have to do, is to delete the user from the password-file
			*/
			$this->_writeHtDigestFile( $documentRoot . '/webdav/' . $data['old']['dir'] . '.htdigest', $data['old']['username'], $data['old']['dir'], '');
			
			/*
			 * Next step, patch the vhost - file
			*/
			$vhost_file = escapeshellcmd($web_config['vhost_conf_dir'] . '/' . $domain . '.vhost');
			$this->_patchVhostWebdav($vhost_file, $documentRoot . '/webdav');
			
			/*
			 * Last, restart apache
			*/
			if($apache_chrooted) {
				$app->services->restartServiceDelayed('httpd','restart');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd','reload');
			}
		}
	}


	/**
	 * This function writes the htdigest - files used by webdav and digest
	 * more info: see http://riceball.com/d/node/424
	 * @author Oliver Vogel
	 * @param string $filename The name of the digest-file
	 * @param string $username The name of the webdav-user
	 * @param string $authname The name of the realm
	 * @param string $pwd      The password-hash of the user
	 */
	private function _writeHtDigestFile($filename, $username, $authname, $pwdhash ) {
		$changed = false;
		if(is_file($filename) && !is_link($filename)) {
			$in = fopen($filename, 'r');
			$output = '';
			/*
			* read line by line and search for the username and authname
			*/
			while (preg_match("/:/", $line = fgets($in))) {
				$line = rtrim($line);
				$tmp = explode(':', $line);
				if ($tmp[0] == $username && $tmp[1] == $authname) {
					/*
					* found the user. delete or change it?
					*/
					if ($pwdhash != '') {
						$output .= $tmp[0] . ':' . $tmp[1] . ':' . $pwdhash . "\n";
						}
					$changed = true;
				}
				else {
					$output .= $line . "\n";
				}
			}
			fclose($in);
		}
		/*
		 * if we didn't change anything, we have to add the new user at the end of the file
		*/
		if (!$changed) {
			$output .= $username . ':' . $authname . ':' . $pwdhash . "\n";
		}
		

		/*
		 * Now lets write the new file
		*/
		if(trim($output) == '') {
			$app->system->unlink($filename);
		} else {
			$app->system->file_put_contents($filename, $output);
		}
	}

	/**
	 * This function patches the vhost-file and adds all webdav - user.
	 * This function is written, because the creation of the vhost - file is sophisticated and
	 * i don't want to make it more "heavy" by also adding this code too...
	 * @author Oliver Vogel
	 * @param string $fileName The Name of the .vhost-File (path included)
	 * @param string $webdavRoot The root of the webdav-folder
	 */
	private function _patchVhostWebdav($fileName, $webdavRoot) {
		global $app;
		$in = fopen($fileName, 'r');
		$output = '';
		$inWebdavSection = false;

		/*
		 * read line by line and search for the username and authname
		*/
		while ($line = fgets($in)) {
			/*
			 *  is the "replace-comment" found...
			*/
			if (trim($line) == '# WEBDAV BEGIN') {
				/*
				 * The begin of the webdav - section is found, so ignore all lines til the end  is found
				*/
				$inWebdavSection = true;

				$output .= "      # WEBDAV BEGIN\n";

				/*
				 * add all the webdav-dirs to the webdav-section
				*/
				$files = @scandir($webdavRoot);
				if(is_array($files)) {
				foreach($files as $file) {
					if (substr($file, strlen($file) - strlen('.htdigest')) == '.htdigest' && preg_match("[a-zA-Z0-9\-_\.]",$file)) {
						/*
						 * found a htdigest - file, so add it to webdav
						*/
						$fn = substr($file, 0, strlen($file) - strlen('.htdigest'));
						$output .= "\n";
						// $output .= "      Alias /" . $fn . ' ' . $webdavRoot . '/' . $fn . "\n";
						// $output .= "      <Location /" . $fn . ">\n";
						$output .= "      Alias /webdav/" . $fn . ' ' . $webdavRoot . '/' . $fn . "\n";
						$output .= "      <Location /webdav/" . $fn . ">\n";
						$output .= "        DAV On\n";
						$output .= '        BrowserMatch "MSIE" AuthDigestEnableQueryStringHack=On'."\n";
						$output .= "        AuthType Digest\n";
						$output .= "        AuthName \"" . $fn . "\"\n";
						$output .= "        AuthUserFile " . $webdavRoot . '/' . $file . "\n";
						$output .= "        Require valid-user \n";
						$output .= "        Options +Indexes \n";
						$output .= "        Order allow,deny \n";
						$output .= "        Allow from all \n";
						$output .= "      </Location> \n";
					}
				}
				}
			}
			/*
			 *  is the "replace-comment-end" found...
			*/
			if (trim($line) == '# WEBDAV END') {
				/*
				 * The end of the webdav - section is found, so stop ignoring
				*/
				$inWebdavSection = false;
			}

			/*
			 * Write the line to the output, if it is not in the section
			*/
			if (!$inWebdavSection) {
				$output .= $line;
			}
		}
		fclose($in);

		/*
		 * Now lets write the new file
		*/
		$app->system->file_put_contents($fileName, $output);

	}
	
	//* Update the awstats configuration file
	private function awstats_update ($data,$web_config) {
		global $app;
		
        $web_folder = $data['new']['web_folder'];
        if($data['new']['type'] == 'vhost') $web_folder = 'web';
		$awstats_conf_dir = $web_config['awstats_conf_dir'];
		
		if(!is_dir($data['new']['document_root']."/" . $web_folder . "/stats/")) mkdir($data['new']['document_root']."/" . $web_folder . "/stats");
		if(!@is_file($awstats_conf_dir.'/awstats.'.$data['new']['domain'].'.conf') || ($data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain'])) {
			if ( @is_file($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf') ) {
				$app->system->unlink($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf');
			}
			
			$content = '';
			$content .= "Include \"".$awstats_conf_dir."/awstats.conf\"\n";
			$content .= "LogFile=\"/var/log/ispconfig/httpd/".$data['new']['domain']."/access.log\"\n";
			$content .= "SiteDomain=\"".$data['new']['domain']."\"\n";
			$content .= "HostAliases=\"www.".$data['new']['domain']."  localhost 127.0.0.1\"\n";
			
			$app->system->file_put_contents($awstats_conf_dir.'/awstats.'.$data['new']['domain'].'.conf',$content);
			$app->log('Created AWStats config file: '.$awstats_conf_dir.'/awstats.'.$data['new']['domain'].'.conf',LOGLEVEL_DEBUG);
		}
		
		if(is_file($data['new']['document_root']."/" . $web_folder . "/stats/index.html")) $app->system->unlink($data['new']['document_root']."/" . $web_folder . "/stats/index.html");
		$app->system->copy("/usr/local/ispconfig/server/conf/awstats_index.php.master",$data['new']['document_root']."/" . $web_folder . "/stats/index.php");
	}
	
	//* Delete the awstats configuration file
	private function awstats_delete ($data,$web_config) {
		global $app;
		
		$awstats_conf_dir = $web_config['awstats_conf_dir'];
		
		if ( @is_file($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf') ) {
			$app->system->unlink($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf');
			$app->log('Removed AWStats config file: '.$awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf',LOGLEVEL_DEBUG);
		}
	}
	
	//* Update the PHP-FPM pool configuration file
	private function php_fpm_pool_update ($data,$web_config,$pool_dir,$pool_name,$socket_dir) {
		global $app, $conf;
		//$reload = false;
		
		if($data['new']['php'] == 'php-fpm'){
			if(trim($data['new']['fastcgi_php_version']) != ''){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		} else {
			if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] == 'php-fpm'){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['old']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir,-1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		}
		
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');
		
		if($data['new']['php'] != 'php-fpm'){
			if(@is_file($pool_dir.$pool_name.'.conf')){
				$app->system->unlink($pool_dir.$pool_name.'.conf');
				//$reload = true;
			}
			if($data['old']['php'] == 'php-fpm'){
				if(!$default_php_fpm){
					$app->services->restartService('php-fpm','reload:'.$custom_php_fpm_init_script);
				} else {
					$app->services->restartService('php-fpm','reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
				}
			}
			//if($reload == true) $app->services->restartService('php-fpm','reload');
			return;
		}
				
		$app->load('tpl');
		$tpl = new tpl();
		$tpl->newTemplate('php_fpm_pool.conf.master');

		if($data['new']['php_fpm_use_socket'] == 'y'){
			$use_tcp = 0;
			$use_socket = 1;
			if(!is_dir($socket_dir)) $app->system->mkdirpath($socket_dir);
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
			
		$app->system->file_put_contents($pool_dir.$pool_name.'.conf',$tpl->grab());
		$app->log('Writing the PHP-FPM config file: '.$pool_dir.$pool_name.'.conf',LOGLEVEL_DEBUG);
		unset($tpl);
		
		// delete pool in all other PHP versions
		$default_pool_dir = escapeshellcmd($web_config['php_fpm_pool_dir']);
		if(substr($default_pool_dir,-1) != '/') $default_pool_dir .= '/';
		if($default_pool_dir != $pool_dir){
			if ( @is_file($default_pool_dir.$pool_name.'.conf') ) {
					$app->system->unlink($default_pool_dir.$pool_name.'.conf');
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
						$app->system->unlink($php_version['php_fpm_pool_dir'].$pool_name.'.conf');
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
		
		//$reload = true;

		//if($reload == true) $app->services->restartService('php-fpm','reload');
	}
	
	//* Delete the PHP-FPM pool configuration file
	private function php_fpm_pool_delete ($data,$web_config) {
		global $app, $conf;
		
		if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] == 'php-fpm'){
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
			$app->system->unlink($pool_dir.$pool_name.'.conf');
			$app->log('Removed PHP-FPM config file: '.$pool_dir.$pool_name.'.conf',LOGLEVEL_DEBUG);
			
			//$app->services->restartService('php-fpm','reload');
		}
		
		// delete pool in all other PHP versions
		$default_pool_dir = escapeshellcmd($web_config['php_fpm_pool_dir']);
		if(substr($default_pool_dir,-1) != '/') $default_pool_dir .= '/';
		if($default_pool_dir != $pool_dir){
			if ( @is_file($default_pool_dir.$pool_name.'.conf') ) {
					$app->system->unlink($default_pool_dir.$pool_name.'.conf');
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
						$app->system->unlink($php_version['php_fpm_pool_dir'].$pool_name.'.conf');
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

	public function create_relative_link($f, $t) {
		// $from already exists
		$from = realpath($f);

		// realpath requires the traced file to exist - so, lets touch it first, then remove
		@$app->system->unlink($t); touch($t);
		$to = realpath($t);
		@$app->system->unlink($t);

		// Remove from the left side matching path elements from $from and $to
		// and get path elements counts
		$a1 = explode('/', $from); $a2 = explode('/', $to);
		for ($c = 0; $a1[$c] == $a2[$c]; $c++) {
			unset($a1[$c]); unset($a2[$c]);
		}
		$cfrom = implode('/', $a1);

		// Check if a path is fully a subpath of another - no way to create symlink in the case
		if (count($a1) == 0 || count($a2) == 0) return false;

		// Add ($cnt_to-1) number of "../" elements to left side of $cfrom
		for ($c = 0; $c < (count($a2)-1); $c++) { $cfrom = '../'.$cfrom; }

		return symlink($cfrom, $to);
	}

} // end class

?>
