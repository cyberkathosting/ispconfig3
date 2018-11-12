<?php

/*
Copyright (c) 2018, Till Brehm, projektfarm Gmbh
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

class plugin_webserver_base {
	
	public function registerEvents($server_type = 'apache') {
		global $app;
		
		$app->plugins->registerEvent('web_domain_insert', $this->plugin_name, 'ssl');
		$app->plugins->registerEvent('web_domain_update', $this->plugin_name, 'ssl');
		$app->plugins->registerEvent('web_domain_delete', $this->plugin_name, 'ssl');

		$app->plugins->registerEvent('web_domain_insert', $this->plugin_name, 'insert');
		$app->plugins->registerEvent('web_domain_update', $this->plugin_name, 'update');
		$app->plugins->registerEvent('web_domain_delete', $this->plugin_name, 'delete');

		$app->plugins->registerEvent('server_ip_insert', $this->plugin_name, 'server_ip');
		$app->plugins->registerEvent('server_ip_update', $this->plugin_name, 'server_ip');
		$app->plugins->registerEvent('server_ip_delete', $this->plugin_name, 'server_ip');
		
		$app->plugins->registerEvent('server_insert', $this->plugin_name, 'server_ip');
		$app->plugins->registerEvent('server_update', $this->plugin_name, 'server_ip');

		$app->plugins->registerEvent('client_delete', $this->plugin_name, 'client_delete');

		$app->plugins->registerEvent('web_folder_user_insert', $this->plugin_name, 'web_folder_user');
		$app->plugins->registerEvent('web_folder_user_update', $this->plugin_name, 'web_folder_user');
		$app->plugins->registerEvent('web_folder_user_delete', $this->plugin_name, 'web_folder_user');

		$app->plugins->registerEvent('web_folder_update', $this->plugin_name, 'web_folder_update');
		$app->plugins->registerEvent('web_folder_delete', $this->plugin_name, 'web_folder_delete');

		$app->plugins->registerEvent('ftp_user_delete', $this->plugin_name, 'ftp_user_delete');

		$app->plugins->registerAction('php_ini_changed', $this->plugin_name, 'php_ini_changed');
		
		if($server_type === 'apache') {
			$app->plugins->registerEvent('webdav_user_insert', $this->plugin_name, 'webdav');
			$app->plugins->registerEvent('webdav_user_update', $this->plugin_name, 'webdav');
			$app->plugins->registerEvent('webdav_user_delete', $this->plugin_name, 'webdav');
		}
	}
	
	private function get_master_php_ini_content($web_data) {
		global $app, $conf;
		
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		$fastcgi_config = $app->getconf->get_server_config($conf['server_id'], 'fastcgi');
		
		$php_ini_content = '';
		$master_php_ini_path = '';
		
		if($web_data['php'] == 'mod') {
			$master_php_ini_path = $web_config['php_ini_path_apache'];
		} else {
			// check for custom php
			if($web_data['fastcgi_php_version'] != '') {
				$tmp = explode(':', $web_data['fastcgi_php_version']);
				if(isset($tmp[2])) {
					$tmppath = $tmp[2];
					if(substr($tmppath, -7) != 'php.ini') {
						if(substr($tmppath, -1) != '/') $tmppath .= '/';
						$tmppath .= 'php.ini';
					}
					if(file_exists($tmppath)) {
						$master_php_ini_path = $tmppath;
					}
					unset($tmppath);
				}
				unset($tmp);
			}

			if(!$master_php_ini_path) {
				if($web_data['php'] == 'fast-cgi' && file_exists($fastcgi_config["fastcgi_phpini_path"])) {
					$master_php_ini_path = $fastcgi_config["fastcgi_phpini_path"];
				} elseif($web_data['php'] == 'php-fpm' && file_exists($web_config['php_fpm_ini_path'])) {
					$master_php_ini_path = $fastcgi_config["fastcgi_phpini_path"];
				} else {
					$master_php_ini_path = $web_config['php_ini_path_cgi'];
				}
			}
		}
		
		// Resolve inconsistant path settings
		if($master_php_ini_path != '' && is_dir($master_php_ini_path) && is_file($master_php_ini_path.'/php.ini')) {
			$master_php_ini_path .= '/php.ini';
		}

		// Load the custom php.ini content
		if($master_php_ini_path != '' && substr($master_php_ini_path, -7) == 'php.ini' && is_file($master_php_ini_path)) {
			$php_ini_content .= $app->system->file_get_contents($master_php_ini_path)."\n";
		}
		
		return $php_ini_content;
	}

	// Handle php.ini changes
	/* TODO: change to be compatible to nginx, too */
	public function eventPhpIniChanged($event_name, $data, $server_type = 'apache') {
		global $app, $conf;

		if($server_type === 'nginx') {
			// not yet implemented
			return;
		}
		
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		/* $data contains an array with these keys:
         * file -> full path of changed php_ini
         * mode -> web_domain php modes to change (mod, fast-cgi, php-fpm or '' for all except 'mod')
         * php_version -> php ini path that changed (additional php versions)
         */

		$param = '';
		$qrystr = "SELECT * FROM web_domain WHERE custom_php_ini != ''";
		if($data['mode'] == 'mod') {
			$qrystr .= " AND php = 'mod'";
		} elseif($data['mode'] == 'fast-cgi') {
			$qrystr .= " AND php = 'fast-cgi'";
			if($data['php_version']) {
				$qrystr .= " AND fastcgi_php_version LIKE ?";
				$param = '%:' . $data['php_version'];
			}
		} elseif($data['mode'] == 'php-fpm') {
			$qrystr .= " AND php = 'php-fpm'";
			if($data['php_version']) {
				$qrystr .= " AND fastcgi_php_version LIKE ?";
				$param = '%:' . $data['php_version'] . ':%';
			}
		} else {
			$qrystr .= " AND php != 'mod' AND php != 'fast-cgi'";
		}


		//** Get all the webs
		$web_domains = $app->db->queryAllRecords($qrystr, $param);
		foreach($web_domains as $web_data) {
			$custom_php_ini_dir = $web_config['website_basedir'].'/conf/'.$web_data['system_user'];
			$web_folder = 'web';
			if($web_data['type'] == 'vhostsubdomain' || $web_data['type'] == 'vhostalias') {
				$web_folder = $web_data['web_folder'];
				$custom_php_ini_dir .= '_' . $web_folder;
			}
			if(!is_dir($web_config['website_basedir'].'/conf')) $app->system->mkdir($web_config['website_basedir'].'/conf');
			
			if(!is_dir($custom_php_ini_dir)) $app->system->mkdir($custom_php_ini_dir);
			
			$php_ini_content = $this->get_master_php_ini_content($web_data);
			
			if(intval($web_data['directive_snippets_id']) > 0){
				$snippet = $app->db->queryOneRecord("SELECT * FROM directive_snippets WHERE directive_snippets_id = ? AND type = 'apache' AND active = 'y' AND customer_viewable = 'y'", intval($web_data['directive_snippets_id']));
				if(isset($snippet['required_php_snippets']) && trim($snippet['required_php_snippets']) != ''){
					$required_php_snippets = explode(',', trim($snippet['required_php_snippets']));
					if(is_array($required_php_snippets) && !empty($required_php_snippets)){
						foreach($required_php_snippets as $required_php_snippet){
							$required_php_snippet = intval($required_php_snippet);
							if($required_php_snippet > 0){
								$php_snippet = $app->db->queryOneRecord("SELECT * FROM directive_snippets WHERE directive_snippets_id = ? AND type = 'php' AND active = 'y'", $required_php_snippet);
								$php_snippet['snippet'] = trim($php_snippet['snippet']);
								if($php_snippet['snippet'] != ''){
									$web_data['custom_php_ini'] .= "\n".$php_snippet['snippet'];
								}
							}
						}
					}
				}
			}
		
			$php_ini_content .= str_replace("\r", '', trim($web_data['custom_php_ini']));
			$app->system->file_put_contents($custom_php_ini_dir.'/php.ini', $php_ini_content);
			$app->log('Info: rewrote custom php.ini for web ' . $web_data['domain_id'] . ' (' . $web_data['domain'] . ').', LOGLEVEL_DEBUG);
		}

		if(count($web_domains) > 0) {
			//* We do not check the apache config here - we only changed the php.ini
			//* Check if this is a chrooted setup
			if($web_config['website_basedir'] != '' && @is_file($web_config['website_basedir'].'/etc/passwd')) {
				$is_chrooted = true;
				$app->log('Info: Apache is chrooted.', LOGLEVEL_DEBUG);
			} else {
				$is_chrooted = false;
			}

			$app->log('Info: rewrote all php.ini and reloading apache now.', LOGLEVEL_DEBUG);
			if($is_chrooted) {
				$app->services->restartServiceDelayed('httpd', 'restart');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd', 'reload');
			}
		} else {
			$app->log('Info: No webs affected by php.ini change.', LOGLEVEL_DEBUG);
		}
	}
	
	// Handle the creation of SSL certificates
	public function eventSsl($event_name, $data, $server_type = 'apache') {
		global $app, $conf;

		$app->uses('system');

		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		if ($web_config['CA_path']!='' && !file_exists($web_config['CA_path'].'/openssl.cnf'))
			$app->log("CA path error, file does not exist:".$web_config['CA_path'].'/openssl.cnf', LOGLEVEL_ERROR);

		//* Only vhosts can have a ssl cert
		if($data["new"]["type"] != "vhost" && $data["new"]["type"] != "vhostsubdomain" && $data["new"]["type"] != "vhostalias") return;

		// if(!is_dir($data['new']['document_root'].'/ssl')) exec('mkdir -p '.$data['new']['document_root'].'/ssl');
		if(!is_dir($data['new']['document_root'].'/ssl') && !is_dir($data['old']['document_root'].'/ssl')) $app->system->mkdirpath($data['new']['document_root'].'/ssl');

		$ssl_dir = $data['new']['document_root'].'/ssl';
		$domain = ($data['new']['ssl_domain'] != '') ? $data['new']['ssl_domain'] : $data['new']['domain'];
		$key_file = $ssl_dir.'/'.$domain.'.key';
		$key_file2 = $ssl_dir.'/'.$domain.'.key.org';
		$csr_file = $ssl_dir.'/'.$domain.'.csr';
		$crt_file = $ssl_dir.'/'.$domain.'.crt';
		$bundle_file = $ssl_dir.'/'.$domain.'.bundle';

		//* Create a SSL Certificate, but only if this is not a mirror server.
		if($data['new']['ssl_action'] == 'create' && $conf['mirror_server_id'] == 0) {

			$this->ssl_certificate_changed = true;

			//* Rename files if they exist
			if(file_exists($key_file)){
				$app->system->rename($key_file, $key_file.'.bak');
				$app->system->chmod($key_file.'.bak', 0400);
			}
			if(file_exists($key_file2)){
				$app->system->rename($key_file2, $key_file2.'.bak');
				$app->system->chmod($key_file2.'.bak', 0400);
			}
			if(file_exists($csr_file)) $app->system->rename($csr_file, $csr_file.'.bak');
			if(file_exists($crt_file)) $app->system->rename($crt_file, $crt_file.'.bak');

			$rand_file = $ssl_dir.'/random_file';
			$rand_data = md5(uniqid(microtime(), 1));
			for($i=0; $i<1000; $i++) {
				$rand_data .= md5(uniqid(microtime(), 1));
				$rand_data .= md5(uniqid(microtime(), 1));
				$rand_data .= md5(uniqid(microtime(), 1));
				$rand_data .= md5(uniqid(microtime(), 1));
			}
			$app->system->file_put_contents($rand_file, $rand_data);

			$ssl_password = substr(md5(uniqid(microtime(), 1)), 0, 15);

			$ssl_cnf = "        RANDFILE               = $rand_file

        [ req ]
        default_bits           = 2048
		default_md             = sha256
        default_keyfile        = keyfile.pem
        distinguished_name     = req_distinguished_name
        attributes             = req_attributes
        prompt                 = no
        output_password        = $ssl_password

        [ req_distinguished_name ]
        C                      = ".trim($data['new']['ssl_country'])."
        " . (trim($data['new']['ssl_state']) == '' ? '' : "ST                     = ".trim($data['new']['ssl_state'])) . "
        " . (trim($data['new']['ssl_locality']) == '' ? '' : "L                      = ".trim($data['new']['ssl_locality']))."
        " . (trim($data['new']['ssl_organisation']) == '' ? '' : "O                      = ".trim($data['new']['ssl_organisation']))."
        " . (trim($data['new']['ssl_organisation_unit']) == '' ? '' : "OU                     = ".trim($data['new']['ssl_organisation_unit']))."
        CN                     = $domain
        emailAddress           = webmaster@".$data['new']['domain']."

        [ req_attributes ]
        ";//challengePassword              = A challenge password";

			$ssl_cnf_file = $ssl_dir.'/openssl.conf';
			$app->system->file_put_contents($ssl_cnf_file, $ssl_cnf);

			$rand_file = escapeshellcmd($rand_file);
			$key_file2 = escapeshellcmd($key_file2);
			$openssl_cmd_key_file2 = $key_file2;
			if(substr($domain, 0, 2) == '*.' && strpos($key_file2, '/ssl/\*.') !== false) $key_file2 = str_replace('/ssl/\*.', '/ssl/*.', $key_file2); // wildcard certificate
			$key_file = escapeshellcmd($key_file);
			$openssl_cmd_key_file = $key_file;
			if(substr($domain, 0, 2) == '*.' && strpos($key_file, '/ssl/\*.') !== false) $key_file = str_replace('/ssl/\*.', '/ssl/*.', $key_file); // wildcard certificate
			$ssl_days = 3650;
			$csr_file = escapeshellcmd($csr_file);
			$openssl_cmd_csr_file = $csr_file;
			if(substr($domain, 0, 2) == '*.' && strpos($csr_file, '/ssl/\*.') !== false) $csr_file = str_replace('/ssl/\*.', '/ssl/*.', $csr_file); // wildcard certificate
			$config_file = escapeshellcmd($ssl_cnf_file);
			$crt_file = escapeshellcmd($crt_file);
			$openssl_cmd_crt_file = $crt_file;
			if(substr($domain, 0, 2) == '*.' && strpos($crt_file, '/ssl/\*.') !== false) $crt_file = str_replace('/ssl/\*.', '/ssl/*.', $crt_file); // wildcard certificate

			if(is_file($ssl_cnf_file) && !is_link($ssl_cnf_file)) {

				exec("openssl genrsa -des3 -rand $rand_file -passout pass:$ssl_password -out $openssl_cmd_key_file2 2048");
				exec("openssl req -new -sha256 -passin pass:$ssl_password -passout pass:$ssl_password -key $openssl_cmd_key_file2 -out $openssl_cmd_csr_file -days $ssl_days -config $config_file");
				exec("openssl rsa -passin pass:$ssl_password -in $openssl_cmd_key_file2 -out $openssl_cmd_key_file");

				if(file_exists($web_config['CA_path'].'/openssl.cnf'))
				{
					exec("openssl ca -batch -out $openssl_cmd_crt_file -config ".$web_config['CA_path']."/openssl.cnf -passin pass:".$web_config['CA_pass']." -in $openssl_cmd_csr_file");
					$app->log("Creating CA-signed SSL Cert for: $domain", LOGLEVEL_DEBUG);
					if (filesize($crt_file)==0 || !file_exists($crt_file)) $app->log("CA-Certificate signing failed.  openssl ca -out $openssl_cmd_crt_file -config ".$web_config['CA_path']."/openssl.cnf -passin pass:".$web_config['CA_pass']." -in $openssl_cmd_csr_file", LOGLEVEL_ERROR);
				}
				if (@filesize($crt_file)==0 || !file_exists($crt_file)){
					exec("openssl req -x509 -passin pass:$ssl_password -passout pass:$ssl_password -key $openssl_cmd_key_file2 -in $openssl_cmd_csr_file -out $openssl_cmd_crt_file -days $ssl_days -config $config_file ");
					$app->log("Creating self-signed SSL Cert for: $domain", LOGLEVEL_DEBUG);
				}

			}

			$app->system->chmod($key_file2, 0400);
			$app->system->chmod($key_file, 0400);
			@$app->system->unlink($config_file);
			@$app->system->unlink($rand_file);
			$ssl_request = $app->system->file_get_contents($csr_file);
			$ssl_cert = $app->system->file_get_contents($crt_file);
			$ssl_key = $app->system->file_get_contents($key_file);
			/* Update the DB of the (local) Server */
			$app->db->query("UPDATE web_domain SET ssl_request = ?, ssl_cert = ?, ssl_key = ? WHERE domain = ?", $ssl_request, $ssl_cert, $ssl_key, $data['new']['domain']);
			$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
			/* Update also the master-DB of the Server-Farm */
			$app->dbmaster->query("UPDATE web_domain SET ssl_request = ?, ssl_cert = ?, ssl_key = ? WHERE domain = ?", $ssl_request, $ssl_cert, $ssl_key, $data['new']['domain']);
			$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
		}
		
		//* Check that the SSL key is not password protected
		if($data["new"]["ssl_action"] == 'save') {
			if(stristr($data["new"]["ssl_key"],'Proc-Type: 4,ENCRYPTED')) {
				$data["new"]["ssl_action"] = '';
			
				$app->log('SSL Certificate not saved. The SSL key is encrypted.', LOGLEVEL_WARN);
				$app->dbmaster->datalogError('SSL Certificate not saved. The SSL key is encrypted.');
			
				/* Update the DB of the (local) Server */
				$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);

				/* Update also the master-DB of the Server-Farm */
				$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
			}
		}
		
		//* and check that SSL cert does not contain subdomain of domain acme.invalid
		if($data["new"]["ssl_action"] == 'save') {
			$tmp = array();
			$crt_data = '';
			exec('openssl x509 -noout -text -in '.escapeshellarg($crt_file),$tmp);
			$crt_data = implode("\n",$tmp);
			if(stristr($crt_data,'.acme.invalid')) {
				$data["new"]["ssl_action"] = '';
			
				$app->log('SSL Certificate not saved. The SSL cert contains domain acme.invalid.', LOGLEVEL_WARN);
				$app->dbmaster->datalogError('SSL Certificate not saved. The SSL cert contains domain acme.invalid.');
			
				/* Update the DB of the (local) Server */
				$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);

				/* Update also the master-DB of the Server-Farm */
				$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
			}
		}

		//* Save a SSL certificate to disk
		if($data["new"]["ssl_action"] == 'save') {
			$this->ssl_certificate_changed = true;

			//* Backup files
			if(file_exists($key_file)){
				$app->system->copy($key_file, $key_file.'~');
				$app->system->chmod($key_file.'~', 0400);
			}
			if(file_exists($key_file2)){
				$app->system->copy($key_file2, $key_file2.'~');
				$app->system->chmod($key_file2.'~', 0400);
			}
			if(file_exists($csr_file)) $app->system->copy($csr_file, $csr_file.'~');
			if(file_exists($crt_file)) $app->system->copy($crt_file, $crt_file.'~');
			if(file_exists($bundle_file)) $app->system->copy($bundle_file, $bundle_file.'~');

			//* Write new ssl files
			if(trim($data["new"]["ssl_request"]) != '') $app->system->file_put_contents($csr_file, $data["new"]["ssl_request"]);
			
			if($server_type === 'nginx' || ($server_type === 'apache' && version_compare($app->system->getapacheversion(true), '2.4.8', '>='))) {
				// In nginx and apache 2.4.8 and newer, the ssl crt file contains the bundle, so we need no separate bundle file
				$tmp_data = '';
				if(trim($data["new"]["ssl_cert"]) != '') $tmp_data .= $data["new"]["ssl_cert"] . "\n";
				if(trim($data["new"]["ssl_bundle"]) != '') $tmp_data .= $data["new"]["ssl_bundle"];
				if(trim($tmp_data) != '') $app->system->file_put_contents($crt_file, $app->file->unix_nl($tmp_data));
			} else {
				// Write separate crt and bundle file
				if(trim($data["new"]["ssl_cert"]) != '') $app->system->file_put_contents($crt_file, $data["new"]["ssl_cert"]);
				if(trim($data["new"]["ssl_bundle"]) != '') $app->system->file_put_contents($bundle_file, $data["new"]["ssl_bundle"]);
			}

			//* Write the key file, if field is empty then import the key into the db
			if(trim($data["new"]["ssl_key"]) != '') {
				$app->system->file_put_contents($key_file, $data["new"]["ssl_key"]);
				$app->system->chmod($key_file, 0400);
			} else {
				$ssl_key = $app->system->file_get_contents($key_file);
				/* Update the DB of the (local) Server */
				$app->db->query("UPDATE web_domain SET ssl_key = ? WHERE domain = ?", $ssl_key, $data['new']['domain']);
				/* Update also the master-DB of the Server-Farm */
				$app->dbmaster->query("UPDATE web_domain SET ssl_key = ? WHERE domain = ?", $ssl_key, $data['new']['domain']);
			}

			/* Update the DB of the (local) Server */
			$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);

			/* Update also the master-DB of the Server-Farm */
			$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
			$app->log('Saving SSL Cert for: '.$domain, LOGLEVEL_DEBUG);
		}

		//* Delete a SSL certificate
		if($data['new']['ssl_action'] == 'del') {
			if(file_exists($web_config['CA_path'].'/openssl.cnf') && !is_link($web_config['CA_path'].'/openssl.cnf'))
			{
				exec("openssl ca -batch -config ".$web_config['CA_path']."/openssl.cnf -passin pass:".$web_config['CA_pass']." -revoke ".escapeshellcmd($crt_file));
				$app->log("Revoking CA-signed SSL Cert for: $domain", LOGLEVEL_DEBUG);
			}
			$app->system->unlink($csr_file);
			$app->system->unlink($crt_file);
			$app->system->unlink($bundle_file);
			/* Update the DB of the (local) Server */
			$app->db->query("UPDATE web_domain SET ssl_request = '', ssl_cert = '' WHERE domain = ? AND server_id = ?", $data['new']['domain'], $data['new']['server_id']);
			$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ? AND server_id = ?", $data['new']['domain'], $data['new']['server_id']);
			/* Update also the master-DB of the Server-Farm */
			$app->dbmaster->query("UPDATE web_domain SET ssl_request = '', ssl_cert = '' WHERE domain = ? AND server_id = ?", $data['new']['domain'], $data['new']['server_id']);
			$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ? AND server_id = ?", $data['new']['domain'], $data['new']['server_id']);
			$app->log('Deleting SSL Cert for: '.$domain, LOGLEVEL_DEBUG);
		}

	}
	

	//* Update the awstats configuration file
	public function awstats_update($data, $web_config) {
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
			if (is_file($awstats_conf_dir."/awstats.conf")) {
				$include_file = $awstats_conf_dir."/awstats.conf";
			} elseif (is_file($awstats_conf_dir."/awstats.model.conf")) {
				$include_file = $awstats_conf_dir."/awstats.model.conf";
			}
			$content .= "Include \"".$include_file."\"\n";
			$content .= "LogFile=\"/var/log/ispconfig/httpd/".$data['new']['domain']."/access.log\"\n";
			$content .= "SiteDomain=\"".$data['new']['domain']."\"\n";
			$content .= "HostAliases=\"www.".$data['new']['domain']."  localhost 127.0.0.1\"\n";

			if (isset($include_file)) {
				$app->system->file_put_contents($awstats_conf_dir.'/awstats.'.$data['new']['domain'].'.conf', $content);
				$app->log('Created AWStats config file: '.$awstats_conf_dir.'/awstats.'.$data['new']['domain'].'.conf', LOGLEVEL_DEBUG);
			} else {
				$app->log("No awstats base config found. Either awstats.conf or awstats.model.conf must exist in ".$awstats_conf_dir.".", LOGLEVEL_WARN);
			}
		}

		if(is_file($data['new']['document_root']."/" . $web_folder . "/stats/index.html")) $app->system->unlink($data['new']['document_root']."/" . $web_folder . "/stats/index.html");
		if(file_exists("/usr/local/ispconfig/server/conf-custom/awstats_index.php.master")) {
			$app->system->copy("/usr/local/ispconfig/server/conf-custom/awstats_index.php.master", $data['new']['document_root']."/" . $web_folder . "/stats/index.php");
		} else {
			$app->system->copy("/usr/local/ispconfig/server/conf/awstats_index.php.master", $data['new']['document_root']."/" . $web_folder . "/stats/index.php");
		}
	}

	//* Delete the awstats configuration file
	public function awstats_delete ($data, $web_config) {
		global $app;

		$awstats_conf_dir = $web_config['awstats_conf_dir'];

		if ( @is_file($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf') ) {
			$app->system->unlink($awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf');
			$app->log('Removed AWStats config file: '.$awstats_conf_dir.'/awstats.'.$data['old']['domain'].'.conf', LOGLEVEL_DEBUG);
		}
	}

	public function eventUpdate($event_name, $data, $server_type = 'apache') {
		global $app, $conf;

		if($server_type === 'nginx') {
			//* Check if the apache plugin is enabled
			if(@is_link('/usr/local/ispconfig/server/plugins-enabled/apache2_plugin.inc.php')) {
				$app->log('The nginx plugin cannot be used together with the apache2 plugin.', LOGLEVEL_WARN);
				return 0;
			}
		}
		
		if($this->action != 'insert') $this->action = 'update';

		if($data['new']['type'] != 'vhost' && $data['new']['type'] != 'vhostsubdomain' && $data['new']['type'] != 'vhostalias' && $data['new']['parent_domain_id'] > 0) {

			$old_parent_domain_id = intval($data['old']['parent_domain_id']);
			$new_parent_domain_id = intval($data['new']['parent_domain_id']);

			// If the parent_domain_id has been changed, we will have to update the old site as well.
			if($this->action == 'update' && $data['new']['parent_domain_id'] != $data['old']['parent_domain_id']) {
				$tmp = $app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id = ? AND active = ?', $old_parent_domain_id, 'y');
				$data['new'] = $tmp;
				$data['old'] = $tmp;
				$this->action = 'update';
				$this->update($event_name, $data);
			}

			// This is not a vhost, so we need to update the parent record instead.
			$tmp = $app->db->queryOneRecord('SELECT * FROM web_domain WHERE domain_id = ? AND active = ?', $new_parent_domain_id, 'y');
			$data['new'] = $tmp;
			$data['old'] = $tmp;
			$this->action = 'update';
			$this->update_letsencrypt = true;
		}

		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		//* Check if this is a chrooted setup
		if($web_config['website_basedir'] != '' && @is_file($web_config['website_basedir'].'/etc/passwd')) {
			$is_chrooted = true;
			$app->log('Info: Webserver is chrooted.', LOGLEVEL_DEBUG);
		} else {
			$is_chrooted = false;
		}

		if($data['new']['document_root'] == '') {
			if($data['new']['type'] == 'vhost' || $data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias') $app->log('document_root not set', LOGLEVEL_WARN);
			return 0;
		}
		if($app->system->is_allowed_user($data['new']['system_user'], $app->system->is_user($data['new']['system_user']), true) == false
			|| $app->system->is_allowed_group($data['new']['system_group'], $app->system->is_group($data['new']['system_group']), true) == false) {
			$app->log('Websites cannot be owned by the root user or group. User: '.$data['new']['system_user'].' Group: '.$data['new']['system_group'], LOGLEVEL_WARN);
			return 0;
		}
		if(trim($data['new']['domain']) == '') {
			$app->log('domain is empty', LOGLEVEL_WARN);
			return 0;
		}

		$web_folder = $this->getWebFolder($data, 'web', false);
		$log_folder = $this->getWebFolder($data, 'log', false);
		$old_web_folder = $this->getWebFolder($data, 'web', true);
		$old_log_folder = $this->getWebFolder($data, 'log', true);
		
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
			if($is_chrooted) $app->system->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' groupadd '.$groupname);
			$app->log('Adding the group: '.$groupname, LOGLEVEL_DEBUG);
		}

		$username = escapeshellcmd($data['new']['system_user']);
		if($data['new']['system_user'] != '' && !$app->system->is_user($data['new']['system_user'])) {
			if($web_config['add_web_users_to_sshusers_group'] == 'y') {
				exec('useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param -G sshusers $username -s /bin/false");
				if($is_chrooted) $app->system->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param -G sshusers $username -s /bin/false");
			} else {
				exec('useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param $username -s /bin/false");
				if($is_chrooted) $app->system->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' useradd -d '.escapeshellcmd($data['new']['document_root'])." -g $groupname $fixed_uid_param $username -s /bin/false");
			}
			$app->log('Adding the user: '.$username, LOGLEVEL_DEBUG);
		}

		//* If the client of the site has been changed, we have a change of the document root
		if($this->action == 'update' && $data['new']['document_root'] != $data['old']['document_root']) {

			//* Get the old client ID
			$old_client = $app->dbmaster->queryOneRecord('SELECT client_id FROM sys_group WHERE sys_group.groupid = ?', $data['old']['sys_groupid']);
			$old_client_id = intval($old_client['client_id']);
			unset($old_client);

			//* Remove the old symlinks
			$tmp_symlinks_array = explode(':', $web_config['website_symlinks']);
			if(is_array($tmp_symlinks_array)) {
				foreach($tmp_symlinks_array as $tmp_symlink) {
					$tmp_symlink = str_replace('[client_id]', $old_client_id, $tmp_symlink);
					$tmp_symlink = str_replace('[website_domain]', $data['old']['domain'], $tmp_symlink);
					// Remove trailing slash
					if(substr($tmp_symlink, -1, 1) == '/') $tmp_symlink = substr($tmp_symlink, 0, -1);
					// create the symlinks, if not exist
					if(is_link($tmp_symlink)) {
						exec('rm -f '.escapeshellcmd($tmp_symlink));
						$app->log('Removed symlink: rm -f '.$tmp_symlink, LOGLEVEL_DEBUG);
					}
				}
			}

			//* Remove protection of old folders
			$app->system->web_folder_protection($data['old']['document_root'], false);

			if($data["new"]["type"] != "vhostsubdomain" && $data["new"]["type"] != "vhostalias") {
				//* Move the site data
				$tmp_docroot = explode('/', $data['new']['document_root']);
				unset($tmp_docroot[count($tmp_docroot)-1]);
				$new_dir = implode('/', $tmp_docroot);

				$tmp_docroot = explode('/', $data['old']['document_root']);
				unset($tmp_docroot[count($tmp_docroot)-1]);
				$old_dir = implode('/', $tmp_docroot);

				//* Check if there is already some data in the new docroot and rename it as we need a clean path to move the existing site to the new path
				if(@is_dir($data['new']['document_root'])) {
					$app->system->web_folder_protection($data['new']['document_root'], false);
					$app->system->rename($data['new']['document_root'], $data['new']['document_root'].'_bak_'.date('Y_m_d_H_i_s'));
					$app->log('Renaming existing directory in new docroot location. mv '.$data['new']['document_root'].' '.$data['new']['document_root'].'_bak_'.date('Y_m_d_H_i_s'), LOGLEVEL_DEBUG);
				}
				
				//* Unmount the old log directory bfore we move the log dir
				exec('umount -l '.escapeshellcmd($old_dir.'/log'));

				//* Create new base directory, if it does not exist yet
				if(!is_dir($new_dir)) $app->system->mkdirpath($new_dir);
				$app->system->web_folder_protection($data['old']['document_root'], false);
				exec('mv '.escapeshellarg($data['old']['document_root']).' '.escapeshellarg($new_dir));
				//$app->system->rename($data['old']['document_root'],$new_dir);
				$app->log('Moving site to new document root: mv '.$data['old']['document_root'].' '.$new_dir, LOGLEVEL_DEBUG);

				// Handle the change in php_open_basedir
				$data['new']['php_open_basedir'] = str_replace($data['old']['document_root'], $data['new']['document_root'], $data['old']['php_open_basedir']);

				//* Change the owner of the website files to the new website owner
				exec('chown --recursive --from='.escapeshellcmd($data['old']['system_user']).':'.escapeshellcmd($data['old']['system_group']).' '.escapeshellcmd($data['new']['system_user']).':'.escapeshellcmd($data['new']['system_group']).' '.$new_dir);

				//* Change the home directory and group of the website user
				$command = 'killall -u '.escapeshellcmd($data['new']['system_user']).' ; usermod';
				$command .= ' --home '.escapeshellcmd($data['new']['document_root']);
				$command .= ' --gid '.escapeshellcmd($data['new']['system_group']);
				$command .= ' '.escapeshellcmd($data['new']['system_user']).' 2>/dev/null';
				exec($command);
			}

			if($is_chrooted) $app->system->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' '.$command);

			//* Change the log mount
			/*
			$fstab_line = '/var/log/ispconfig/httpd/'.$data['old']['domain'].' '.$data['old']['document_root'].'/'.$old_log_folder.'    none    bind';
			$app->system->removeLine('/etc/fstab', $fstab_line);
			$fstab_line = '/var/log/ispconfig/httpd/'.$data['old']['domain'].' '.$data['old']['document_root'].'/'.$old_log_folder.'    none    bind,nobootwait';
			$app->system->removeLine('/etc/fstab', $fstab_line);
			$fstab_line = '/var/log/ispconfig/httpd/'.$data['old']['domain'].' '.$data['old']['document_root'].'/'.$old_log_folder.'    none    bind,nobootwait';
			$app->system->removeLine('/etc/fstab', $fstab_line);
			*/
			
			$fstab_line_old = '/var/log/ispconfig/httpd/'.$data['old']['domain'].' '.$data['old']['document_root'].'/'.$old_log_folder.'    none    bind';
			
			if($web_config['network_filesystem'] == 'y') {
				$fstab_line = '/var/log/ispconfig/httpd/'.$data['new']['domain'].' '.$data['new']['document_root'].'/'.$log_folder.'    none    bind,nofail,_netdev    0 0';
				$app->system->replaceLine('/etc/fstab', $fstab_line_old, $fstab_line, 0, 1);
			} else {
				$fstab_line = '/var/log/ispconfig/httpd/'.$data['new']['domain'].' '.$data['new']['document_root'].'/'.$log_folder.'    none    bind,nofail    0 0';
				$app->system->replaceLine('/etc/fstab', $fstab_line_old, $fstab_line, 0, 1);
			}
			
			exec('mount --bind '.escapeshellarg('/var/log/ispconfig/httpd/'.$data['new']['domain']).' '.escapeshellarg($data['new']['document_root'].'/'.$log_folder));

		}

		//print_r($data);

		// Check if the directories are there and create them if necessary.
		$app->system->web_folder_protection($data['new']['document_root'], false);

		if(!is_dir($data['new']['document_root'].'/' . $web_folder)) $app->system->mkdirpath($data['new']['document_root'].'/' . $web_folder);
		if(!is_dir($data['new']['document_root'].'/' . $web_folder . '/error') and $data['new']['errordocs']) $app->system->mkdirpath($data['new']['document_root'].'/' . $web_folder . '/error');
		if($data['new']['stats_type'] != '' && !is_dir($data['new']['document_root'].'/' . $web_folder . '/stats')) $app->system->mkdirpath($data['new']['document_root'].'/' . $web_folder . '/stats');
		//if(!is_dir($data['new']['document_root'].'/'.$log_folder)) exec('mkdir -p '.$data['new']['document_root'].'/'.$log_folder);
		if(!is_dir($data['new']['document_root'].'/ssl')) $app->system->mkdirpath($data['new']['document_root'].'/ssl');
		if(!is_dir($data['new']['document_root'].'/cgi-bin')) $app->system->mkdirpath($data['new']['document_root'].'/cgi-bin');
		if(!is_dir($data['new']['document_root'].'/tmp')) $app->system->mkdirpath($data['new']['document_root'].'/tmp');
		if($server_type === 'apache') {
			if(!is_dir($data['new']['document_root'].'/webdav')) $app->system->mkdirpath($data['new']['document_root'].'/webdav');
		}
		
		if(!is_dir($data['new']['document_root'].'/.ssh')) {
			$app->system->mkdirpath($data['new']['document_root'].'/.ssh');
			$app->system->chmod($data['new']['document_root'].'/.ssh', 0700);
			$app->system->chown($data['new']['document_root'].'/.ssh', $username);
			$app->system->chgrp($data['new']['document_root'].'/.ssh', $groupname);
		}

		//* Create the new private directory
		if(!is_dir($data['new']['document_root'].'/private')) {
			$app->system->mkdirpath($data['new']['document_root'].'/private');
			$app->system->chmod($data['new']['document_root'].'/private', 0710);
			$app->system->chown($data['new']['document_root'].'/private', $username);
			$app->system->chgrp($data['new']['document_root'].'/private', $groupname);
		}


		// Remove the symlink for the site, if site is renamed
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			if(is_dir('/var/log/ispconfig/httpd/'.$data['old']['domain'])) exec('rm -rf /var/log/ispconfig/httpd/'.$data['old']['domain']);
			if(is_link($data['old']['document_root'].'/'.$old_log_folder)) $app->system->unlink($data['old']['document_root'].'/'.$old_log_folder);

			//* remove old log mount
			$fstab_line = '/var/log/ispconfig/httpd/'.$data['old']['domain'].' '.$data['old']['document_root'].'/'.$old_log_folder.'    none    bind';
			$app->system->removeLine('/etc/fstab', $fstab_line);

			//* Unmount log directory
			//exec('fuser -km '.escapeshellarg($data['old']['document_root'].'/'.$old_log_folder));
			exec('umount -l '.escapeshellarg($data['old']['document_root'].'/'.$old_log_folder));
		}

		//* Create the log dir if nescessary and mount it
		if(!is_dir($data['new']['document_root'].'/'.$log_folder) || !is_dir('/var/log/ispconfig/httpd/'.$data['new']['domain']) || is_link($data['new']['document_root'].'/'.$log_folder)) {
			if(is_link($data['new']['document_root'].'/'.$log_folder)) unlink($data['new']['document_root'].'/'.$log_folder);
			if(!is_dir('/var/log/ispconfig/httpd/'.$data['new']['domain'])) exec('mkdir -p /var/log/ispconfig/httpd/'.$data['new']['domain']);
			$app->system->mkdirpath($data['new']['document_root'].'/'.$log_folder);
			$app->system->chown($data['new']['document_root'].'/'.$log_folder, 'root');
			$app->system->chgrp($data['new']['document_root'].'/'.$log_folder, 'root');
			$app->system->chmod($data['new']['document_root'].'/'.$log_folder, 0755);
			exec('mount --bind '.escapeshellarg('/var/log/ispconfig/httpd/'.$data['new']['domain']).' '.escapeshellarg($data['new']['document_root'].'/'.$log_folder));
			//* add mountpoint to fstab
			$fstab_line = '/var/log/ispconfig/httpd/'.$data['new']['domain'].' '.$data['new']['document_root'].'/'.$log_folder.'    none    bind,nobootwait';
			$fstab_line .= @($web_config['network_filesystem'] == 'y')?',_netdev    0 0':'    0 0';
			$app->system->replaceLine('/etc/fstab', $fstab_line, $fstab_line, 1, 1);
		}

		$app->system->web_folder_protection($data['new']['document_root'], true);

		// Get the client ID
		$client = $app->dbmaster->queryOneRecord('SELECT client_id FROM sys_group WHERE sys_group.groupid = ?', $data['new']['sys_groupid']);
		$client_id = intval($client['client_id']);
		unset($client);

		// Remove old symlinks, if site is renamed
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			$tmp_symlinks_array = explode(':', $web_config['website_symlinks']);
			if(is_array($tmp_symlinks_array)) {
				foreach($tmp_symlinks_array as $tmp_symlink) {
					$tmp_symlink = str_replace('[client_id]', $client_id, $tmp_symlink);
					$tmp_symlink = str_replace('[website_domain]', $data['old']['domain'], $tmp_symlink);
					// Remove trailing slash
					if(substr($tmp_symlink, -1, 1) == '/') $tmp_symlink = substr($tmp_symlink, 0, -1);
					// remove the symlinks, if not exist
					if(is_link($tmp_symlink)) {
						exec('rm -f '.escapeshellcmd($tmp_symlink));
						$app->log('Removed symlink: rm -f '.$tmp_symlink, LOGLEVEL_DEBUG);
					}
				}
			}
		}

		// Create the symlinks for the sites
		$tmp_symlinks_array = explode(':', $web_config['website_symlinks']);
		if(is_array($tmp_symlinks_array)) {
			foreach($tmp_symlinks_array as $tmp_symlink) {
				$tmp_symlink = str_replace('[client_id]', $client_id, $tmp_symlink);
				$tmp_symlink = str_replace('[website_domain]', $data['new']['domain'], $tmp_symlink);
				// Remove trailing slash
				if(substr($tmp_symlink, -1, 1) == '/') $tmp_symlink = substr($tmp_symlink, 0, -1);
				//* Remove symlink if target folder has been changed.
				if($data['old']['document_root'] != '' && $data['old']['document_root'] != $data['new']['document_root'] && is_link($tmp_symlink)) {
					$app->system->unlink($tmp_symlink);
				}
				// create the symlinks, if not exist
				if(!is_link($tmp_symlink)) {
					//     exec("ln -s ".escapeshellcmd($data["new"]["document_root"])."/ ".escapeshellcmd($tmp_symlink));
					if ($web_config["website_symlinks_rel"] == 'y') {
						$app->system->create_relative_link(escapeshellcmd($data["new"]["document_root"]), escapeshellcmd($tmp_symlink));
					} else {
						exec("ln -s ".escapeshellcmd($data["new"]["document_root"])."/ ".escapeshellcmd($tmp_symlink));
					}

					$app->log('Creating symlink: ln -s '.$data['new']['document_root'].'/ '.$tmp_symlink, LOGLEVEL_DEBUG);
				}
			}
		}



		// Install the Standard or Custom Error, Index and other related files
		// /usr/local/ispconfig/server/conf is for the standard files
		// /usr/local/ispconfig/server/conf-custom is for the custom files
		// setting a local var here

		// normally $conf['templates'] = "/usr/local/ispconfig/server/conf";
		if($this->action == 'insert' && ($data['new']['type'] == 'vhost' || $data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias')) {

			// Copy the error pages
			if($data['new']['errordocs']) {
				$error_page_path = escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/error/';
				if (file_exists($conf['rootpath'] . '/conf-custom/error/'.substr(escapeshellcmd($conf['language']), 0, 2))) {
					exec('cp ' . $conf['rootpath'] . '/conf-custom/error/'.substr(escapeshellcmd($conf['language']), 0, 2).'/* '.$error_page_path);
				}
				else {
					if (file_exists($conf['rootpath'] . '/conf-custom/error/400.html')) {
						exec('cp '. $conf['rootpath'] . '/conf-custom/error/*.html '.$error_page_path);
					}
					else {
						exec('cp ' . $conf['rootpath'] . '/conf/error/'.substr(escapeshellcmd($conf['language']), 0, 2).'/* '.$error_page_path);
					}
				}
				exec('chmod -R a+r '.$error_page_path);
			}

			//* Copy the web skeleton files only when there is no index.ph or index.html file yet
			if(!file_exists($data['new']['document_root'].'/'.$web_folder.'/index.html') && !file_exists($data['new']['document_root'].'/'.$web_folder.'/index.php')) {
				if (file_exists($conf['rootpath'] . '/conf-custom/index/standard_index.html_'.substr(escapeshellcmd($conf['language']), 0, 2))) {
					if(!file_exists(escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/index.html')) exec('cp ' . $conf['rootpath'] . '/conf-custom/index/standard_index.html_'.substr(escapeshellcmd($conf['language']), 0, 2).' '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/index.html');

					if(is_file($conf['rootpath'] . '/conf-custom/index/favicon.ico')) {
						if(!file_exists(escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/favicon.ico')) exec('cp ' . $conf['rootpath'] . '/conf-custom/index/favicon.ico '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
					}
					if(is_file($conf['rootpath'] . '/conf-custom/index/robots.txt')) {
						if(!file_exists(escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/robots.txt')) exec('cp ' . $conf['rootpath'] . '/conf-custom/index/robots.txt '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
					}
					//if(is_file($conf['rootpath'] . '/conf-custom/index/.htaccess')) {
					//	exec('cp ' . $conf['rootpath'] . '/conf-custom/index/.htaccess '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
					//}
				} else {
					if (file_exists($conf['rootpath'] . '/conf-custom/index/standard_index.html')) {
						if(!file_exists(escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/index.html')) exec('cp ' . $conf['rootpath'] . '/conf-custom/index/standard_index.html '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/index.html');
					} else {
						if(!file_exists(escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/index.html')) exec('cp ' . $conf['rootpath'] . '/conf/index/standard_index.html_'.substr(escapeshellcmd($conf['language']), 0, 2).' '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/index.html');
						if(is_file($conf['rootpath'] . '/conf/index/favicon.ico')){
							if(!file_exists(escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/favicon.ico')) exec('cp ' . $conf['rootpath'] . '/conf/index/favicon.ico '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
						}
						if(is_file($conf['rootpath'] . '/conf/index/robots.txt')){
							if(!file_exists(escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/robots.txt')) exec('cp ' . $conf['rootpath'] . '/conf/index/robots.txt '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
						}
						//if(is_file($conf['rootpath'] . '/conf/index/.htaccess')) exec('cp ' . $conf['rootpath'] . '/conf/index/.htaccess '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');
					}
				}
			}
			exec('chmod -R a+r '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/');

			//** Copy the error documents on update when the error document checkbox has been activated and was deactivated before
		} elseif ($this->action == 'update' && ($data['new']['type'] == 'vhost' || $data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias') && $data['old']['errordocs'] == 0 && $data['new']['errordocs'] == 1) {

			$error_page_path = escapeshellcmd($data['new']['document_root']).'/' . $web_folder . '/error/';
			if (file_exists($conf['rootpath'] . '/conf-custom/error/'.substr(escapeshellcmd($conf['language']), 0, 2))) {
				exec('cp ' . $conf['rootpath'] . '/conf-custom/error/'.substr(escapeshellcmd($conf['language']), 0, 2).'/* '.$error_page_path);
			}
			else {
				if (file_exists($conf['rootpath'] . '/conf-custom/error/400.html')) {
					exec('cp ' . $conf['rootpath'] . '/conf-custom/error/*.html '.$error_page_path);
				}
				else {
					exec('cp ' . $conf['rootpath'] . '/conf/error/'.substr(escapeshellcmd($conf['language']), 0, 2).'/* '.$error_page_path);
				}
			}
			exec('chmod -R a+r '.$error_page_path);
			exec('chown -R '.$data['new']['system_user'].':'.$data['new']['system_group'].' '.$error_page_path);
		}  // end copy error docs

		// Set the quota for the user, but only for vhosts, not vhostsubdomains or vhostalias
		if($username != '' && $app->system->is_user($username) && $data['new']['type'] == 'vhost') {
			if($data['new']['hd_quota'] > 0) {
				$blocks_soft = $data['new']['hd_quota'] * 1024;
				$blocks_hard = $blocks_soft + 1024;
				$mb_soft = $data['new']['hd_quota'];
				$mb_hard = $mb_soft + 1;
			} else {
				$mb_soft = $mb_hard = $blocks_soft = $blocks_hard = 0;
			}

			// get the primitive folder for document_root and the filesystem, will need it later.
			$df_output=explode(" ", exec("df -T " . escapeshellarg($data['new']['document_root']) . "|awk 'END{print \$2,\$NF}'"));
			$file_system = $df_output[0];
			$primitive_root = $df_output[1];

			if($file_system == 'xfs') {
				exec("xfs_quota -x -c " . escapeshellarg("limit -u bsoft=$mb_soft" . 'm'. " bhard=$mb_hard" . 'm'. " " . $username) . " " . escapeshellarg($primitive_root));

				// xfs only supports timers globally, not per user.
				exec("xfs_quota -x -c 'timer -bir -i 604800' " . escapeshellarg($primitive_root));

				unset($primitive_root, $df_output, $mb_hard, $mb_soft);
			} else {
				if($app->system->is_installed('setquota')) {
					exec('setquota -u '. $username . ' ' . $blocks_soft . ' ' . $blocks_hard . ' 0 0 -a &> /dev/null');
					exec('setquota -T -u '.$username.' 604800 604800 -a &> /dev/null');
				}
			}
		}

		if($this->action == 'insert' || $data["new"]["system_user"] != $data["old"]["system_user"]) {
			// Chown and chmod the directories below the document root
			$app->system->_exec('chown -R '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder);
			// The document root itself has to be owned by root in normal level and by the web owner in security level 20
			if($web_config['security_level'] == 20) {
				$app->system->_exec('chown '.$username.':'.$groupname.' '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder);
			} else {
				$app->system->_exec('chown root:root '.escapeshellcmd($data['new']['document_root']).'/' . $web_folder);
			}
		}

		//* add the user to the client group if this is a vhost and security level is set to high, no matter if this is an insert or update and regardless of set_folder_permissions_on_update
		$user_config_key = 'user';
		if($server_type === 'nginx') {
			$user_config_key = 'nginx_user';
		}
		if($data['new']['type'] == 'vhost' && $web_config['security_level'] == 20) $app->system->add_user_to_group($groupname, escapeshellcmd($web_config[$user_config_key]));

		//* If the security level is set to high
		if(($this->action == 'insert' && $data['new']['type'] == 'vhost') or ($web_config['set_folder_permissions_on_update'] == 'y' && $data['new']['type'] == 'vhost') or ($web_folder != $old_web_folder && $data['new']['type'] == 'vhost')) {

			$app->system->web_folder_protection($data['new']['document_root'], false);

			//* Check if we have the new private folder and create it if nescessary
			if(!is_dir($data['new']['document_root'].'/private')) $app->system->mkdir($data['new']['document_root'].'/private');

			if($web_config['security_level'] == 20) {

				$app->system->chmod($data['new']['document_root'], 0755);
				$app->system->chmod($data['new']['document_root'].'/web', 0711);
				if($server_type === 'apache') {
					$app->system->chmod($data['new']['document_root'].'/webdav', 0710);
				}
				$app->system->chmod($data['new']['document_root'].'/private', 0710);
				$app->system->chmod($data['new']['document_root'].'/ssl', 0755);
				if($web_folder != 'web') $app->system->chmod($data['new']['document_root'].'/'.$web_folder, 0751);

				// make tmp directory writable for webserver and the website users
				$app->system->chmod($data['new']['document_root'].'/tmp', 0770);

				// Set Log directory to 755 to make the logs accessible by the FTP user
				if(realpath($data['new']['document_root'].'/'.$log_folder . '/error.log') == '/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log') {
					$app->system->chmod($data['new']['document_root'].'/'.$log_folder, 0755);
				}

				if($web_config['add_web_users_to_sshusers_group'] == 'y') {
					$command = 'usermod';
					$command .= ' --groups sshusers';
					$command .= ' '.escapeshellcmd($data['new']['system_user']).' 2>/dev/null';
					$app->system->_exec($command);
				}

				//* if we have a chrooted environment
				if($is_chrooted) {
					$app->system->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' '.$command);

					//* add the user to the client group in the chroot environment
					$tmp_groupfile = $app->system->server_conf['group_datei'];
					$app->system->server_conf['group_datei'] = $web_config['website_basedir'].'/etc/group';
					$app->system->add_user_to_group($groupname, escapeshellcmd($web_config[$user_config_key]));
					$app->system->server_conf['group_datei'] = $tmp_groupfile;
					unset($tmp_groupfile);
				}

				//* Chown all default directories
				$app->system->chown($data['new']['document_root'], 'root');
				$app->system->chgrp($data['new']['document_root'], 'root');
				$app->system->chown($data['new']['document_root'].'/cgi-bin', $username);
				$app->system->chgrp($data['new']['document_root'].'/cgi-bin', $groupname);
				if(realpath($data['new']['document_root'].'/'.$log_folder . '/error.log') == '/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log') {
					$app->system->chown($data['new']['document_root'].'/'.$log_folder, 'root', false);
					$app->system->chgrp($data['new']['document_root'].'/'.$log_folder, $groupname, false);
				}
				$app->system->chown($data['new']['document_root'].'/ssl', 'root');
				$app->system->chgrp($data['new']['document_root'].'/ssl', 'root');
				$app->system->chown($data['new']['document_root'].'/tmp', $username);
				$app->system->chgrp($data['new']['document_root'].'/tmp', $groupname);
				$app->system->chown($data['new']['document_root'].'/web', $username);
				$app->system->chgrp($data['new']['document_root'].'/web', $groupname);
				$app->system->chown($data['new']['document_root'].'/web/error', $username);
				$app->system->chgrp($data['new']['document_root'].'/web/error', $groupname);
				if($data['new']['stats_type'] != '') {
					$app->system->chown($data['new']['document_root'].'/web/stats', $username);
					$app->system->chgrp($data['new']['document_root'].'/web/stats', $groupname);
				}
				if($server_type === 'apache') {
					$app->system->chown($data['new']['document_root'].'/webdav', $username);
					$app->system->chgrp($data['new']['document_root'].'/webdav', $groupname);
				}
				$app->system->chown($data['new']['document_root'].'/private', $username);
				$app->system->chgrp($data['new']['document_root'].'/private', $groupname);
				
				if($web_folder != 'web'){
					$app->system->chown($data['new']['document_root'].'/'.$web_folder, $username);
					$app->system->chgrp($data['new']['document_root'].'/'.$web_folder, $groupname);
				}

				// If the security Level is set to medium
			} else {

				$app->system->chmod($data['new']['document_root'], 0755);
				$app->system->chmod($data['new']['document_root'].'/web', 0755);
				if($server_type === 'apache') {
					$app->system->chmod($data['new']['document_root'].'/webdav', 0755);
				}
				$app->system->chmod($data['new']['document_root'].'/ssl', 0755);
				$app->system->chmod($data['new']['document_root'].'/cgi-bin', 0755);
				if($web_folder != 'web') $app->system->chmod($data['new']['document_root'].'/'.$web_folder, 0755);

				// make temp directory writable for webserver and the website users
				$app->system->chmod($data['new']['document_root'].'/tmp', 0770);

				// Set Log directory to 755 to make the logs accessible by the FTP user
				if(realpath($data['new']['document_root'].'/'.$log_folder . '/error.log') == '/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log') {
					$app->system->chmod($data['new']['document_root'].'/'.$log_folder, 0755);
				}

				$app->system->chown($data['new']['document_root'], 'root');
				$app->system->chgrp($data['new']['document_root'], 'root');
				$app->system->chown($data['new']['document_root'].'/cgi-bin', $username);
				$app->system->chgrp($data['new']['document_root'].'/cgi-bin', $groupname);
				if(realpath($data['new']['document_root'].'/'.$log_folder . '/error.log') == '/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log') {
					$app->system->chown($data['new']['document_root'].'/'.$log_folder, 'root', false);
					$app->system->chgrp($data['new']['document_root'].'/'.$log_folder, $groupname, false);
				}

				$app->system->chown($data['new']['document_root'].'/ssl', 'root');
				$app->system->chgrp($data['new']['document_root'].'/ssl', 'root');
				$app->system->chown($data['new']['document_root'].'/tmp', $username);
				$app->system->chgrp($data['new']['document_root'].'/tmp', $groupname);
				$app->system->chown($data['new']['document_root'].'/web', $username);
				$app->system->chgrp($data['new']['document_root'].'/web', $groupname);
				$app->system->chown($data['new']['document_root'].'/web/error', $username);
				$app->system->chgrp($data['new']['document_root'].'/web/error', $groupname);
				if($data['new']['stats_type'] != '') {
					$app->system->chown($data['new']['document_root'].'/web/stats', $username);
					$app->system->chgrp($data['new']['document_root'].'/web/stats', $groupname);
				}
				if($server_type === 'apache') {
					$app->system->chown($data['new']['document_root'].'/webdav', $username);
					$app->system->chgrp($data['new']['document_root'].'/webdav', $groupname);
				}
				if($web_folder != 'web'){
					$app->system->chown($data['new']['document_root'].'/'.$web_folder, $username);
					$app->system->chgrp($data['new']['document_root'].'/'.$web_folder, $groupname);
				}
			}
		} elseif((($data['new']['type'] == 'vhostsubdomain') || ($data['new']['type'] == 'vhostalias')) &&
				 (($this->action == 'insert') || ($web_config['set_folder_permissions_on_update'] == 'y'))) {

			if($web_config['security_level'] == 20) {
				$app->system->chmod($data['new']['document_root'].'/' . $web_folder, 0710);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder, $username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder, $groupname);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder . '/error', $username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder . '/error', $groupname);
				if($data['new']['stats_type'] != '') {
					$app->system->chown($data['new']['document_root'].'/' . $web_folder . '/stats', $username);
					$app->system->chgrp($data['new']['document_root'].'/' . $web_folder . '/stats', $groupname);
				}
			} else {
				$app->system->chmod($data['new']['document_root'].'/' . $web_folder, 0755);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder, $username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder, $groupname);
				$app->system->chown($data['new']['document_root'].'/' . $web_folder . '/error', $username);
				$app->system->chgrp($data['new']['document_root'].'/' . $web_folder . '/error', $groupname);
				if($data['new']['stats_type'] != '') {
					$app->system->chown($data['new']['document_root'].'/' . $web_folder . '/stats', $username);
					$app->system->chgrp($data['new']['document_root'].'/' . $web_folder . '/stats', $groupname);
				}
			}
		}

		//* Protect web folders
		$app->system->web_folder_protection($data['new']['document_root'], true);

		if($data['new']['type'] == 'vhost') {
			// Change the ownership of the error log to the root user
			if(!@is_file('/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log')) exec('touch '.escapeshellcmd('/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log'));
			$app->system->chown('/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log', 'root');
			$app->system->chgrp('/var/log/ispconfig/httpd/'.$data['new']['domain'].'/error.log', 'root');
		}

		//* Write the custom php.ini file, if custom_php_ini field is not empty
		$custom_php_ini_dir = $web_config['website_basedir'].'/conf/'.$data['new']['system_user'];
		if($data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias') $custom_php_ini_dir .= '_' . $web_folder;
		if(!is_dir($web_config['website_basedir'].'/conf')) $app->system->mkdir($web_config['website_basedir'].'/conf');

		if(trim($data['new']['fastcgi_php_version']) != ''){
			// $custom_fastcgi_php_name, $custom_fastcgi_php_executable
			list(,, $custom_fastcgi_php_ini_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
			if(is_file($custom_fastcgi_php_ini_dir)) $custom_fastcgi_php_ini_dir = dirname($custom_fastcgi_php_ini_dir);
			if(substr($custom_fastcgi_php_ini_dir, -1) == '/') $custom_fastcgi_php_ini_dir = substr($custom_fastcgi_php_ini_dir, 0, -1);
		}

		//* Create custom php.ini
		if(trim($data['new']['custom_php_ini']) != '') {
			$has_custom_php_ini = true;
			if(!is_dir($custom_php_ini_dir)) $app->system->mkdirpath($custom_php_ini_dir);
			
			$php_ini_content = $this->get_master_php_ini_content($data['new']);
			$php_ini_content .= str_replace("\r", '', trim($data['new']['custom_php_ini']));
			
			if(intval($data['new']['directive_snippets_id']) > 0){
				$snippet = $app->db->queryOneRecord("SELECT * FROM directive_snippets WHERE directive_snippets_id = ? AND type = 'apache' AND active = 'y' AND customer_viewable = 'y'", intval($data['new']['directive_snippets_id']));
				if(isset($snippet['required_php_snippets']) && trim($snippet['required_php_snippets']) != ''){
					$required_php_snippets = explode(',', trim($snippet['required_php_snippets']));
					if(is_array($required_php_snippets) && !empty($required_php_snippets)){
						foreach($required_php_snippets as $required_php_snippet){
							$required_php_snippet = intval($required_php_snippet);
							if($required_php_snippet > 0){
								$php_snippet = $app->db->queryOneRecord("SELECT * FROM directive_snippets WHERE directive_snippets_id = ? AND type = 'php' AND active = 'y'", $required_php_snippet);
								$php_snippet['snippet'] = trim($php_snippet['snippet']);
								if($php_snippet['snippet'] != ''){
									$php_ini_content .= "\n".$php_snippet['snippet'];
								}
							}
						}
					}
				}
			}
		
			$app->system->file_put_contents($custom_php_ini_dir.'/php.ini', $php_ini_content);
		} else {
			$has_custom_php_ini = false;
			if(is_file($custom_php_ini_dir.'/php.ini')) $app->system->unlink($custom_php_ini_dir.'/php.ini');
		}

		$vhost_template_name = 'vhost.conf.master';
		if($server_type === 'nginx') {
			$vhost_template_name = 'nginx_vhost.conf.master';
		}
		
		//* Create the vhost config file
		$app->load('tpl');

		$tpl = new tpl();
		$tpl->newTemplate($vhost_template_name);

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
		$vhost_data['logging'] = $web_config['logging'];

		// IPv6
		if($data['new']['ipv6_address'] != ''){
			$tpl->setVar('ipv6_enabled', 1);
			if ($conf['serverconfig']['web']['vhost_rewrite_v6'] == 'y') {
				if (isset($conf['serverconfig']['server']['v6_prefix']) && $conf['serverconfig']['server']['v6_prefix'] <> '') {
					$explode_v6prefix=explode(':', $conf['serverconfig']['server']['v6_prefix']);
					$explode_v6=explode(':', $data['new']['ipv6_address']);

					for ( $i = 0; $i <= count($explode_v6prefix)-1; $i++ ) {
						$explode_v6[$i] = $explode_v6prefix[$i];
					}
					$data['new']['ipv6_address'] = implode(':', $explode_v6);
					$vhost_data['ipv6_address'] = $data['new']['ipv6_address'];
				}
			}
		}
		
		if($server_type === 'nginx') {
			$app->plugin_webserver_nginx->processPhpFpm($tpl, $data, $vhost_data);
			$app->plugin_webserver_nginx->processRewriteRules($tpl, $data, $vhost_data);
		} else {
			$app->plugin_webserver_apache->processCustomDirectives($tpl, $data, $vhost_data);
		}
		
		// Custom Apache directives
		if(intval($data['new']['directive_snippets_id']) > 0){
			$snippet = $app->db->queryOneRecord("SELECT * FROM directive_snippets WHERE directive_snippets_id = ? AND type = 'apache' AND active = 'y' AND customer_viewable = 'y'", $data['new']['directive_snippets_id']);
			if(isset($snippet['snippet'])){
				$vhost_data['apache_directives'] = $snippet['snippet'];
			}
		}
		// Make sure we only have Unix linebreaks
		$vhost_data['apache_directives'] = str_replace("\r\n", "\n", $vhost_data['apache_directives']);
		$vhost_data['apache_directives'] = str_replace("\r", "\n", $vhost_data['apache_directives']);
		$trans = array(
			'{DOCROOT}' => $vhost_data['web_document_root_www'],
			'{DOCROOT_CLIENT}' => $vhost_data['web_document_root']
		);
		$vhost_data['apache_directives'] = strtr($vhost_data['apache_directives'], $trans);
		
		$app->uses('letsencrypt');
		// Check if a SSL cert exists
		$tmp = $app->letsencrypt->get_website_certificate_paths($data);
		$domain = $tmp['domain'];
		$key_file = $tmp['key'];
		$key_file2 = $tmp['key2'];
		$csr_file = $tmp['csr'];
		$crt_file = $tmp['crt'];
		$bundle_file = $tmp['bundle'];
		unset($tmp);

		$data['new']['ssl_domain'] = $domain;
		$vhost_data['ssl_domain'] = $domain;
		$vhost_data['ssl_crt_file'] = $crt_file;
		$vhost_data['ssl_key_file'] = $key_file;
		$vhost_data['ssl_bundle_file'] = $bundle_file;

		//* Generate Let's Encrypt SSL certificat
		if($data['new']['ssl'] == 'y' && $data['new']['ssl_letsencrypt'] == 'y' && $conf['mirror_server_id'] == 0 && ( // ssl and let's encrypt is active and no mirror server
			($data['old']['ssl'] == 'n' || $data['old']['ssl_letsencrypt'] == 'n') // we have new let's encrypt configuration
			|| ($data['old']['domain'] != $data['new']['domain']) // we have domain update
			|| ($data['old']['subdomain'] != $data['new']['subdomain']) // we have new or update on "auto" subdomain
			|| $this->update_letsencrypt == true
		)) {
			
			$success = $app->letsencrypt->request_certificates($data, $server_type);
			if($success) {
 				/* we don't need to store it.
 				/* Update the DB of the (local) Server */
				$app->db->query("UPDATE web_domain SET ssl_request = '', ssl_cert = '', ssl_key = '' WHERE domain = ?", $data['new']['domain']);
				$app->db->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
 				/* Update also the master-DB of the Server-Farm */
 				$app->dbmaster->query("UPDATE web_domain SET ssl_request = '', ssl_cert = '', ssl_key = '' WHERE domain = ?", $data['new']['domain']);
 				$app->dbmaster->query("UPDATE web_domain SET ssl_action = '' WHERE domain = ?", $data['new']['domain']);
			} else {
				$data['new']['ssl_letsencrypt'] = 'n';
				if($data['old']['ssl'] == 'n') $data['new']['ssl'] = 'n';
				/* Update the DB of the (local) Server */
				$app->db->query("UPDATE web_domain SET `ssl` = ?, `ssl_letsencrypt` = ? WHERE `domain` = ?", $data['new']['ssl'], 'n', $data['new']['domain']);
				/* Update also the master-DB of the Server-Farm */
				$app->dbmaster->query("UPDATE web_domain SET `ssl` = ?, `ssl_letsencrypt` = ? WHERE `domain` = ? AND `server_id` = ?", $data['new']['ssl'], 'n', $data['new']['domain'], $conf['server_id']);
 			}
		}

		if(@is_file($bundle_file)) $vhost_data['has_bundle_cert'] = 1;

		// HTTP/2.0 ?
		$vhost_data['enable_http2']  = 'n';
		if($vhost_data['enable_spdy'] == 'y'){
			$tmp_output = null;
			$tmp_retval = 0;
			if($server_type === 'apache') {
				// check if apache supports http_v2
				exec("2>&1 apachectl -M | grep http2_module", $tmp_output, $tmp_retval);
			} else {
				// check if nginx support http_v2; if so, use that instead of spdy
				exec("2>&1 nginx -V | tr -- - '\n' | grep http_v2_module", $tmp_output, $tmp_retval);
			}
			if($tmp_retval == 0){
				$vhost_data['enable_http2']  = 'y';
				$vhost_data['enable_spdy'] = 'n';
			}
			unset($tmp_output, $tmp_retval);
		}

		// Set SEO Redirect
		if($data['new']['seo_redirect'] != ''){
			$vhost_data['seo_redirect_enabled'] = 1;
			$tmp_seo_redirects = $this->get_seo_redirects($data['new'], '', false, $server_type);
			if(is_array($tmp_seo_redirects) && !empty($tmp_seo_redirects)){
				foreach($tmp_seo_redirects as $key => $val){
					$vhost_data[$key] = $val;
				}
			} else {
				$vhost_data['seo_redirect_enabled'] = 0;
			}
		} else {
			$vhost_data['seo_redirect_enabled'] = 0;
		}
		
		if($server_type === 'nginx') {
			if($domain!='' && $data['new']['ssl'] == 'y' && @is_file($crt_file) && @is_file($key_file) && (@filesize($crt_file)>0)  && (@filesize($key_file)>0)) {
				$vhost_data['ssl_enabled'] = 1;
				$app->log('Enable SSL for: '.$domain, LOGLEVEL_DEBUG);
			} else {
				$vhost_data['ssl_enabled'] = 0;
				$app->log('SSL Disabled. '.$domain, LOGLEVEL_DEBUG);
			}
		}

		// set logging variable
		$vhost_data['logging'] = $web_config['logging'];

		$tpl->setVar($vhost_data);

		$config_prefix = '';
		if($server_type === 'apache') {
			$ssl_data = array(
				'crt_file' => $crt_file,
				'key_file' => $key_file,
			);
			
			$tpl->setVar('apache_version', $app->system->getapacheversion());
			$tpl->setVar('apache_full_version', $app->system->getapacheversion(true));
			$app->plugin_webserver_apache->processRewriteRules($tpl, $data, $vhost_data);
			$app->plugin_webserver_apache->processPhpStarters($tpl, $data, $vhost_data);
			$app->plugin_webserver_apache->processVhosts($tpl, $data, $vhost_data, $ssl_data);
		} elseif($server_type === 'nginx') {
			$app->plugin_webserver_nginx->processStatsAuth($tpl, $data, $vhost_data);
			$config_prefix = 'nginx_';
		}
		

		$vhost_file = escapeshellcmd($web_config[$config_prefix.'vhost_conf_dir'].'/'.$data['new']['domain'].'.vhost');
		//* Make a backup copy of vhost file
		if(file_exists($vhost_file)) $app->system->copy($vhost_file, $vhost_file.'~');

		//* Write vhost file
		if($server_type === 'apache') {
			$app->system->file_put_contents($vhost_file, $tpl->grab());
		} else {
			$app->system->file_put_contents($vhost_file, $this->nginx_merge_locations($tpl->grab()));
		}
		$app->log('Writing the vhost file: '.$vhost_file, LOGLEVEL_DEBUG);
		unset($tpl);

		if($server_type === 'apache') {
			/*
			 * maybe we have some webdav - user. If so, add them...
			*/
			$this->_patchVhostWebdav($vhost_file, $data['new']['document_root'] . '/webdav');
		}

		//* Set the symlink to enable the vhost
		//* First we check if there is a old type of symlink and remove it
		$vhost_symlink = escapeshellcmd($web_config[$config_prefix.'vhost_conf_enabled_dir'].'/'.$data['new']['domain'].'.vhost');
		if(is_link($vhost_symlink)) $app->system->unlink($vhost_symlink);

		//* Remove old or changed symlinks
		if($data['new']['subdomain'] != $data['old']['subdomain'] or $data['new']['active'] == 'n') {
			$vhost_symlink = escapeshellcmd($web_config[$config_prefix.'vhost_conf_enabled_dir'].'/900-'.$data['new']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config[$config_prefix.'vhost_conf_enabled_dir'].'/100-'.$data['new']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
			}
		}

		//* New symlink
		if($data['new']['subdomain'] == '*') {
			$vhost_symlink = escapeshellcmd($web_config[$config_prefix.'vhost_conf_enabled_dir'].'/900-'.$data['new']['domain'].'.vhost');
		} else {
			$vhost_symlink = escapeshellcmd($web_config[$config_prefix.'vhost_conf_enabled_dir'].'/100-'.$data['new']['domain'].'.vhost');
		}
		if($data['new']['active'] == 'y' && !is_link($vhost_symlink)) {
			symlink($vhost_file, $vhost_symlink);
			$app->log('Creating symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
		}

		// remove old symlink and vhost file, if domain name of the site has changed
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			$vhost_symlink = escapeshellcmd($web_config[$config_prefix.'vhost_conf_enabled_dir'].'/900-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config[$config_prefix.'vhost_conf_enabled_dir'].'/100-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config[$config_prefix.'vhost_conf_enabled_dir'].'/'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)) {
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
			}
			$vhost_file = escapeshellcmd($web_config[$config_prefix.'vhost_conf_dir'].'/'.$data['old']['domain'].'.vhost');
			$app->system->unlink($vhost_file);
			$app->log('Removing file: '.$vhost_file, LOGLEVEL_DEBUG);
		}

		if($server_type === 'apache') {
			//* Create .htaccess and .htpasswd file for website statistics
			if(!is_dir($data['new']['document_root'].'/' . $web_folder . '/stats')) $app->system->mkdir($data['new']['document_root'].'/' . $web_folder . '/stats');
			$ht_file = "AuthType Basic\nAuthName \"Members Only\"\nAuthUserFile ".$data['new']['document_root']."/web/stats/.htpasswd_stats\nrequire valid-user";
			$app->system->file_put_contents($data['new']['document_root'].'/' . $web_folder . '/stats/.htaccess', $ht_file);
			$app->system->chmod($data['new']['document_root'].'/' . $web_folder . '/stats/.htaccess', 0755);
			unset($ht_file);

			if(!is_file($data['new']['document_root'].'/web/stats/.htpasswd_stats') || $data['new']['stats_password'] != $data['old']['stats_password']) {
				if(trim($data['new']['stats_password']) != '') {
					$htp_file = 'admin:'.trim($data['new']['stats_password']);
					$app->system->web_folder_protection($data['new']['document_root'], false);
					$app->system->file_put_contents($data['new']['document_root'].'/web/stats/.htpasswd_stats', $htp_file);
					$app->system->web_folder_protection($data['new']['document_root'], true);
					$app->system->chmod($data['new']['document_root'].'/web/stats/.htpasswd_stats', 0755);
					unset($htp_file);
				}
			}
		} else {
			// create password file for stats directory
			$stats_web_folder = $app->plugin_webserver_nginx->getStatsFolder($data);
			
			if(!is_file($data['new']['document_root'].'/' . $stats_web_folder . '/stats/.htpasswd_stats') || $data['new']['stats_password'] != $data['old']['stats_password']) {
				if(trim($data['new']['stats_password']) != '') {
					$htp_file = 'admin:'.trim($data['new']['stats_password']);
					$app->system->file_put_contents($data['new']['document_root'].'/' . $stats_web_folder . '/stats/.htpasswd_stats', $htp_file);
					$app->system->chmod($data['new']['document_root'].'/' . $stats_web_folder . '/stats/.htpasswd_stats', 0755);
					unset($htp_file);
				}
			}
		}

		//* Create awstats configuration
		if($data['new']['stats_type'] == 'awstats' && ($data['new']['type'] == 'vhost' || $data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias')) {
			$app->plugin_webserver_base->awstats_update($data, $web_config);
		}

		/**
		 * PHP-FPM
		 */
		// Support for multiple PHP versions
		if($data['new']['php'] == 'php-fpm'){
			if(trim($data['new']['fastcgi_php_version']) != ''){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir, -1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		} else {
			if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] == 'php-fpm'){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['old']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir, -1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		}

		if($default_php_fpm){
			$pool_dir = escapeshellcmd($web_config['php_fpm_pool_dir']);
		} else {
			$pool_dir = $custom_php_fpm_pool_dir;
		}
		$pool_dir = trim($pool_dir);
		if(substr($pool_dir, -1) != '/') $pool_dir .= '/';
		$pool_name = 'web'.$data['new']['domain_id'];
		$socket_dir = escapeshellcmd($web_config['php_fpm_socket_dir']);
		if(substr($socket_dir, -1) != '/') $socket_dir .= '/';
		
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
		$tpl->setVar('fpm_port', $web_config['php_fpm_start_port'] + $data['new']['domain_id'] - 1);
		
		$this->php_fpm_pool_update($data, $web_config, $pool_dir, $pool_name, $socket_dir, $server_type);
		if($server_type === 'nginx') {
			$fpm_data = array(
				'use_tcp' => $use_tcp,
				'use_socket' => $use_socket,
				'socket_dir' => $socket_dir,
				'fpm_socket' => $fpm_socket,
				'fpm_port' => $web_config['php_fpm_start_port'] + $data['new']['domain_id'] - 1
			);
			$app->plugin_webserver_nginx->processCustomDirectives($tpl, $data, $vhost_data, $fpm_data);
		}

		if($web_config['check_apache_config'] == 'y') {
			//* Test if server starts with the new configuration file
			$online_status_before_restart = $this->_checkTcp('localhost', 80);
			$app->log('web server status is: '.($online_status_before_restart === true? 'running' : 'down'), LOGLEVEL_DEBUG);

			$retval = $app->services->restartService('httpd', 'restart'); // $retval['retval'] is 0 on success and > 0 on failure
			$app->log('web server restart return value is: '.$retval['retval'], LOGLEVEL_DEBUG);

			// wait a few seconds, before we test the status again
			$online_status_after_restart = false;
			sleep(2);
			for($i = 0; $i < 5; $i++) {
				$online_status_after_restart = $this->_checkTcp('localhost', 80);
				if($online_status_after_restart) break;
				sleep(1);
			}
			//* Check if server restarted successfully if it was online before
			$app->log('web server online status after restart is: '.($online_status_after_restart === true? 'running' : 'down'), LOGLEVEL_DEBUG);
			if($online_status_before_restart && !$online_status_after_restart || $retval['retval'] > 0) {
				$app->log('web server did not restart after the configuration change for website '.$data['new']['domain'].'. Reverting the configuration. Saved non-working config as '.$vhost_file.'.err', LOGLEVEL_WARN);
				if(is_array($retval['output']) && !empty($retval['output'])){
					$app->log('Reason for web server restart failure: '.implode("\n", $retval['output']), LOGLEVEL_WARN);
					$app->dbmaster->datalogError(implode("\n", $retval['output']));
				} else {
					$app->plugin_webserver_apache->testWebserverConfig();
				}
				$app->system->copy($vhost_file, $vhost_file.'.err');

				if(is_file($vhost_file.'~')) {
					//* Copy back the last backup file
					$app->system->copy($vhost_file.'~', $vhost_file);
				} else {
					//* There is no backup file, so we create a empty vhost file with a warning message inside
					$app->system->file_put_contents($vhost_file, "# web server did not start after modifying this vhost file.\n# Please check file $vhost_file.err for syntax errors.");
				}

				if($this->ssl_certificate_changed === true) {
					if($server_type === 'nginx') {
						/* TODO: check if needed! */
						$ssl_dir = $data['new']['document_root'].'/ssl';
						$domain = $data['new']['ssl_domain'];
						$key_file = $ssl_dir.'/'.$domain.'.key.org';
						$key_file2 = $ssl_dir.'/'.$domain.'.key';
						$csr_file = $ssl_dir.'/'.$domain.'.csr';
						$crt_file = $ssl_dir.'/'.$domain.'.crt';
					}
					
					//* Backup the files that might have caused the error
					if(is_file($key_file)){
						$app->system->copy($key_file, $key_file.'.err');
						$app->system->chmod($key_file.'.err', 0400);
					}
					if(is_file($key_file2)){
						$app->system->copy($key_file2, $key_file2.'.err');
						$app->system->chmod($key_file2.'.err', 0400);
					}
					if(is_file($csr_file)) $app->system->copy($csr_file, $csr_file.'.err');
					if(is_file($crt_file)) $app->system->copy($crt_file, $crt_file.'.err');
					if(is_file($bundle_file)) $app->system->copy($bundle_file, $bundle_file.'.err');

					//* Restore the ~ backup files
					if(is_file($key_file.'~')) $app->system->copy($key_file.'~', $key_file);
					if(is_file($key_file2.'~')) $app->system->copy($key_file2.'~', $key_file2);
					if(is_file($crt_file.'~')) $app->system->copy($crt_file.'~', $crt_file);
					if(is_file($csr_file.'~')) $app->system->copy($csr_file.'~', $csr_file);
					if(is_file($bundle_file.'~')) $app->system->copy($bundle_file.'~', $bundle_file);

					$app->log('web server did not restart after the configuration change for website '.$data['new']['domain'].' Reverting the SSL configuration. Saved non-working SSL files with .err extension.', LOGLEVEL_WARN);
				}

				$app->services->restartService('httpd', 'restart');
			}
		} else {
			//* We do not check the web server config after changes (is faster)
			if($is_chrooted) {
				$app->services->restartServiceDelayed('httpd', 'restart');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd', 'reload');
			}
		}

		//* The vhost is written and apache has been restarted, so we
		// can reset the ssl changed var to false and cleanup some files
		$this->ssl_certificate_changed = false;

		if(@is_file($key_file.'~')) $app->system->unlink($key_file.'~');
		if(@is_file($key_file2.'~')) $app->system->unlink($key_file2.'~');
		if(@is_file($crt_file.'~')) $app->system->unlink($crt_file.'~');
		if(@is_file($csr_file.'~')) $app->system->unlink($csr_file.'~');
		if(@is_file($bundle_file.'~')) $app->system->unlink($bundle_file.'~');

		// Remove the backup copy of the config file.
		if(@is_file($vhost_file.'~')) $app->system->unlink($vhost_file.'~');

		//* Unset action to clean it for next processed vhost.
		$this->action = '';
	}
	
	public function eventDelete($event_name, $data,  $server_type = 'apache') {
		global $app, $conf;

		// load the server configuration options
		$app->uses('getconf');
		$app->uses('system');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		$fastcgi_config = $app->getconf->get_server_config($conf['server_id'], 'fastcgi');

		if($data['old']['type'] == 'vhost' || $data['old']['type'] == 'vhostsubdomain' || $data['old']['type'] == 'vhostalias') {
			$parent_domain_id = intval($data['old']['parent_domain_id']);
			$tmp = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ? AND active = 'y'", $parent_domain_id);
			$app->system->web_folder_protection($tmp['document_root'], false);
		}

		//* Check if this is a chrooted setup
		if($web_config['website_basedir'] != '' && @is_file($web_config['website_basedir'].'/etc/passwd')) {
			$is_chrooted = true;
		} else {
			$is_chrooted = false;
		}

		//* Remove the mounts
		$log_folder = 'log';
		$web_folder = '';
		if($data['old']['type'] == 'vhostsubdomain' || $data['old']['type'] == 'vhostalias') {
			$tmp = $app->db->queryOneRecord('SELECT `domain`,`document_root` FROM web_domain WHERE domain_id = ?', $data['old']['parent_domain_id']);
			if($tmp['domain'] != ''){
				$subdomain_host = preg_replace('/^(.*)\.' . preg_quote($tmp['domain'], '/') . '$/', '$1', $data['old']['domain']);
			} else {
				// we are deleting the parent domain, so we can delete everything in the log directory
				$subdomain_hosts = array();
				$files = array_diff(scandir($data['old']['document_root'].'/'.$log_folder), array('.', '..'));
				if(is_array($files) && !empty($files)){
					foreach($files as $file){
						if(is_dir($data['old']['document_root'].'/'.$log_folder.'/'.$file)){
							$subdomain_hosts[] = $file;
						}
					}
				}
			}
			if(is_array($subdomain_hosts) && !empty($subdomain_hosts)){
				$log_folders = array();
				foreach($subdomain_hosts as $subdomain_host){
					$log_folders[] = $log_folder.'/'.$subdomain_host;
				}
			} else {
				if($subdomain_host == '') $subdomain_host = 'web'.$data['old']['domain_id'];
				$log_folder .= '/' . $subdomain_host;
			}
			$web_folder = $data['old']['web_folder'];
			unset($tmp);
			unset($subdomain_hosts);
		}

		if($data['old']['type'] == 'vhost' || $data['old']['type'] == 'vhostsubdomain' || $data['old']['type'] == 'vhostalias'){
			if(is_array($log_folders) && !empty($log_folders)){
				foreach($log_folders as $log_folder){
					exec('umount -l '.escapeshellarg($data['old']['document_root'].'/'.$log_folder) . ' 2>/dev/null');
				}
			} else {
				exec('umount -l '.escapeshellarg($data['old']['document_root'].'/'.$log_folder) . ' 2>/dev/null');
			}
			
			//try umount mysql
			if(file_exists($data['old']['document_root'].'/var/run/mysqld')) {
				$fstab_line = '/var/run/mysqld ' . $data['old']['document_root'] . '/var/run/mysqld    none    bind,nobootwait    0    0';
				$app->system->removeLine('/etc/fstab', $fstab_line);
				$command = 'umount ' . escapeshellarg($data['old']['document_root']) . '/var/run/mysqld/';
				exec($command);
			}
			
			// remove letsencrypt if it exists (renew will always fail otherwise)
			
			$old_domain = $data['old']['domain'];
			if(substr($old_domain, 0, 2) === '*.') {
				// wildcard domain not yet supported by letsencrypt!
				$old_domain = substr($old_domain, 2);
			}
			@rename('/etc/letsencrypt/renewal/' . $old_domain . '.conf', '/etc/letsencrypt/renewal/' . $old_domain . '.conf~backup');
		}

		//* remove mountpoint from fstab
		if(is_array($log_folders) && !empty($log_folders)){
			foreach($log_folders as $log_folder){
				$fstab_line = '/var/log/ispconfig/httpd/'.$data['old']['domain'].' '.$data['old']['document_root'].'/'.$log_folder.'    none    bind';
				$app->system->removeLine('/etc/fstab', $fstab_line);
			}
		} else {
			$fstab_line = '/var/log/ispconfig/httpd/'.$data['old']['domain'].' '.$data['old']['document_root'].'/'.$log_folder.'    none    bind';
			$app->system->removeLine('/etc/fstab', $fstab_line);
		}
		unset($log_folders);

		if($data['old']['type'] != 'vhost' && $data['old']['type'] != 'vhostsubdomain' && $data['old']['type'] != 'vhostalias' && $data['old']['parent_domain_id'] > 0) {
			//* This is a alias domain or subdomain, so we have to update the website instead
			$parent_domain_id = intval($data['old']['parent_domain_id']);
			$tmp = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ? AND active = 'y'", $parent_domain_id);
			$data['new'] = $tmp;
			$data['old'] = $tmp;
			$this->action = 'update';
			$this->update_letsencrypt = true;
			// just run the update function
			$this->update($event_name, $data);

		} else {
			$conf_prefix = '';
			if($server_type === 'nginx') {
				$conf_prefix = 'nginx_';
			}
			
			//* This is a website
			// Deleting the vhost file, symlink and the data directory
			$vhost_file = escapeshellcmd($web_config[$conf_prefix.'vhost_conf_dir'].'/'.$data['old']['domain'].'.vhost');

			$vhost_symlink = escapeshellcmd($web_config[$conf_prefix.'vhost_conf_enabled_dir'].'/'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)){
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config[$conf_prefix.'vhost_conf_enabled_dir'].'/900-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)){
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
			}
			$vhost_symlink = escapeshellcmd($web_config[$conf_prefix.'vhost_conf_enabled_dir'].'/100-'.$data['old']['domain'].'.vhost');
			if(is_link($vhost_symlink)){
				$app->system->unlink($vhost_symlink);
				$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
			}

			$app->system->unlink($vhost_file);
			$app->log('Removing vhost file: '.$vhost_file, LOGLEVEL_DEBUG);

			if($data['old']['type'] == 'vhost' || $data['old']['type'] == 'vhostsubdomain' || $data['old']['type'] == 'vhostalias') {
				$docroot = escapeshellcmd($data['old']['document_root']);
				if($docroot != '' && !stristr($docroot, '..')) {
					if($data['old']['type'] == 'vhost') {
						// this is a vhost - we delete everything in here.
						exec('rm -rf '.$docroot);
					} elseif(!stristr($data['old']['web_folder'], '..')) {
						// this is a vhost subdomain
						// IMPORTANT: do some folder checks before we delete this!
						$do_delete = true;
						$delete_folder = preg_replace('/[\/]{2,}/', '/', $web_folder); // replace / occuring multiple times
						if(substr($delete_folder, 0, 1) === '/') $delete_folder = substr($delete_folder, 1);
						if(substr($delete_folder, -1) === '/') $delete_folder = substr($delete_folder, 0, -1);

						$path_elements = explode('/', $delete_folder);

						if($path_elements[0] == 'web' || $path_elements[0] === '') {
							// paths beginning with /web should NEVER EVER be deleted, empty paths should NEVER occur - but for safety reasons we check it here!
							// we use strict check as otherwise directories named '0' may not be deleted
							$do_delete = false;
						} else {
							// read all vhost subdomains and alias with same parent domain
							$used_paths = array();
							$tmp = $app->db->queryAllRecords("SELECT `web_folder` FROM web_domain WHERE (type = 'vhostsubdomain' OR type = 'vhostalias') AND parent_domain_id = ? AND domain_id != ?", $data['old']['parent_domain_id'], $data['old']['domain_id']);
							foreach($tmp as $tmprec) {
								// we normalize the folder entries because we need to compare them
								$tmp_folder = preg_replace('/[\/]{2,}/', '/', $tmprec['web_folder']); // replace / occuring multiple times
								if(substr($tmp_folder, 0, 1) === '/') $tmp_folder = substr($tmp_folder, 1);
								if(substr($tmp_folder, -1) === '/') $tmp_folder = substr($tmp_folder, 0, -1);

								// add this path and it's parent paths to used_paths array
								while(strpos($tmp_folder, '/') !== false) {
									if(in_array($tmp_folder, $used_paths) == false) $used_paths[] = $tmp_folder;
									$tmp_folder = substr($tmp_folder, 0, strrpos($tmp_folder, '/'));
								}
								if(in_array($tmp_folder, $used_paths) == false) $used_paths[] = $tmp_folder;
							}
							unset($tmp);

							// loop and check if the path is still used and stop at first used one
							// set do_delete to false so nothing gets deleted if the web_folder itself is still used
							$do_delete = false;
							while(count($path_elements) > 0) {
								$tmp_folder = implode('/', $path_elements);
								if(in_array($tmp_folder, $used_paths) == true) break;

								// this path is not used - set it as path to delete, strip the last element from the array and set do_delete to true
								$delete_folder = $tmp_folder;
								$do_delete = true;
								array_pop($path_elements);
							}
							unset($tmp_folder);
							unset($used_paths);
						}

						if($do_delete === true && $delete_folder !== '') exec('rm -rf '.$docroot.'/'.$delete_folder);

						unset($delete_folder);
						unset($path_elements);
					}
				}

				//remove the php fastgi starter script if available
				if ($data['old']['php'] == 'fast-cgi') {
					$fastcgi_starter_path = str_replace('[system_user]', $data['old']['system_user'], $fastcgi_config['fastcgi_starter_path']);
					if($data['old']['type'] == 'vhost') {
						if (is_dir($fastcgi_starter_path)) {
							exec('rm -rf '.$fastcgi_starter_path);
						}
					} else {
						$fcgi_starter_script = $fastcgi_starter_path.$fastcgi_config['fastcgi_starter_script'].'_web'.$data['old']['domain_id'];
						if (file_exists($fcgi_starter_script)) {
							exec('rm -f '.$fcgi_starter_script);
						}
					}
				}

				// remove PHP-FPM pool
				if ($data['old']['php'] == 'php-fpm') {
					$this->php_fpm_pool_delete($data, $web_config, $server_type);
				}

				$app->log('Removing website: '.$docroot, LOGLEVEL_DEBUG);

				// Delete the symlinks for the sites
				$client = $app->db->queryOneRecord('SELECT client_id FROM sys_group WHERE sys_group.groupid = ?', $data['old']['sys_groupid']);
				$client_id = intval($client['client_id']);
				unset($client);
				$tmp_symlinks_array = explode(':', $web_config['website_symlinks']);
				if(is_array($tmp_symlinks_array)) {
					foreach($tmp_symlinks_array as $tmp_symlink) {
						$tmp_symlink = str_replace('[client_id]', $client_id, $tmp_symlink);
						$tmp_symlink = str_replace('[website_domain]', $data['old']['domain'], $tmp_symlink);
						// Remove trailing slash
						if(substr($tmp_symlink, -1, 1) == '/') $tmp_symlink = substr($tmp_symlink, 0, -1);
						// delete the symlink
						if(is_link($tmp_symlink)) {
							$app->system->unlink($tmp_symlink);
							$app->log('Removing symlink: '.$tmp_symlink, LOGLEVEL_DEBUG);
						}
					}
				}
				// end removing symlinks
			}

			// Delete the log file directory
			$vhost_logfile_dir = escapeshellcmd('/var/log/ispconfig/httpd/'.$data['old']['domain']);
			if($data['old']['domain'] != '' && !stristr($vhost_logfile_dir, '..')) exec('rm -rf '.$vhost_logfile_dir);
			$app->log('Removing website logfile directory: '.$vhost_logfile_dir, LOGLEVEL_DEBUG);

			if($data['old']['type'] == 'vhost') {
				//delete the web user
				$command = 'killall -u '.escapeshellcmd($data['old']['system_user']).' ; userdel';
				$command .= ' '.escapeshellcmd($data['old']['system_user']);
				exec($command);
				if($is_chrooted) $app->system->_exec('chroot '.escapeshellcmd($web_config['website_basedir']).' '.$command);

			}

			//* Remove the awstats configuration file
			if($data['old']['stats_type'] == 'awstats') {
				$this->awstats_delete($data, $web_config);
			}

			if($data['old']['type'] == 'vhostsubdomain' || $data['old']['type'] == 'vhostalias') {
				$parent_domain_id = intval($data['old']['parent_domain_id']);
				$tmp = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ? AND active = 'y'", $parent_domain_id);				$app->system->web_folder_protection($tmp['document_root'], true);
			}

			if($is_chrooted) {
				$app->services->restartServiceDelayed('httpd', 'restart');
			} else {
				// request a httpd reload when all records have been processed
				$app->services->restartServiceDelayed('httpd', 'reload');
			}

			//* Delete the web-backups
			if($data['old']['type'] == 'vhost') {
				$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
				$backup_dir = $server_config['backup_dir'];
				$mount_backup = true;
				if($server_config['backup_dir'] != '' && $server_config['backup_delete'] == 'y') {
					//* mount backup directory, if necessary
					if( $server_config['backup_dir_is_mount'] == 'y' && !$app->system->mount_backup_dir($backup_dir) ) $mount_backup = false;

					if($mount_backup){
						$web_backup_dir = $backup_dir.'/web'.$data['old']['domain_id'];
						//** do not use rm -rf $web_backup_dir because database(s) may exits
						exec(escapeshellcmd('rm -f '.$web_backup_dir.'/web'.$data['old']['domain_id'].'_').'*');
						//* cleanup database
						$sql = "DELETE FROM web_backup WHERE server_id = ? AND parent_domain_id = ? AND filename LIKE ?";
						$app->db->query($sql, $conf['server_id'], $data['old']['domain_id'], "web".$data['old']['domain_id']."_%");
						if($app->db->dbHost != $app->dbmaster->dbHost) $app->dbmaster->query($sql, $conf['server_id'], $data['old']['domain_id'], "web".$data['old']['domain_id']."_%");

						$app->log('Deleted the web backup files', LOGLEVEL_DEBUG);
					}
				}
			}
		}
		if($data['old']['type'] != 'vhost') $app->system->web_folder_protection($data['old']['document_root'], true);
	}

	//* This function is called when a IP on the server is inserted, updated or deleted or when anon_ip setting is altered
	function eventServerIp($event_name, $data, $server_type = 'apache') {
		global $app, $conf;

		if($server_type === 'nginx') {
			// not yet implemented
			return;
		}
		
		// load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		$app->load('tpl');

		$tpl = new tpl();
		$tpl->newTemplate('apache_ispconfig.conf.master');
		$tpl->setVar('apache_version', $app->system->getapacheversion());
		$tpl->setVar('logging', $web_config['logging']);
		$tpl->setVar('apache_full_version', $app->system->getapacheversion(true));
		$records = $app->db->queryAllRecords("SELECT * FROM server_ip WHERE server_id = ? AND virtualhost = 'y'", $conf['server_id']);

		$records_out= array();
		if(is_array($records)) {
			foreach($records as $rec) {
				if($rec['ip_type'] == 'IPv6') {
					$ip_address = '['.$rec['ip_address'].']';
				} else {
					$ip_address = $rec['ip_address'];
				}
				$ports = explode(',', $rec['virtualhost_port']);
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
			$tpl->setLoop('ip_adresses', $records_out);
		}

		$vhost_file = escapeshellcmd($web_config['vhost_conf_dir'].'/ispconfig.conf');
		$app->system->file_put_contents($vhost_file, $tpl->grab());
		$app->log('Writing the conf file: '.$vhost_file, LOGLEVEL_DEBUG);
		unset($tpl);

	}

	//* Create or update the .htaccess folder protection
	public function eventWebFolderUser($event_name, $data, $server_type = 'apache') {
		global $app;

		$app->uses('system');

		if($event_name == 'web_folder_user_delete') {
			$folder_id = $data['old']['web_folder_id'];
		} else {
			$folder_id = $data['new']['web_folder_id'];
		}

		$folder = $app->db->queryOneRecord("SELECT * FROM web_folder WHERE web_folder_id = ?", $folder_id);
		$website = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $folder['parent_domain_id']);

		if(!is_array($folder) or !is_array($website)) {
			$app->log('Not able to retrieve folder or website record.', LOGLEVEL_DEBUG);
			return false;
		}

		$web_folder = 'web';
		if($website['type'] == 'vhostsubdomain' || $website['type'] == 'vhostalias') $web_folder = $website['web_folder'];

		//* Get the folder path.
		if(substr($folder['path'], 0, 1) == '/') $folder['path'] = substr($folder['path'], 1);
		if(substr($folder['path'], -1) == '/') $folder['path'] = substr($folder['path'], 0, -1);
		$folder_path = escapeshellcmd($website['document_root'].'/' . $web_folder . '/'.$folder['path']);
		if(substr($folder_path, -1) != '/') $folder_path .= '/';

		//* Check if the resulting path is inside the docroot
		if(stristr($folder_path, '..') || stristr($folder_path, './') || stristr($folder_path, '\\')) {
			$app->log('Folder path "'.$folder_path.'" contains .. or ./.', LOGLEVEL_DEBUG);
			return false;
		}

		//* Create the folder path, if it does not exist
		if(!is_dir($folder_path)) {
			$app->system->mkdirpath($folder_path, 0755, $website['system_user'], $website['system_group']);
		}

		//* Create empty .htpasswd file, if it does not exist
		if(!is_file($folder_path.'.htpasswd')) {
			$app->system->touch($folder_path.'.htpasswd');
			$app->system->chmod($folder_path.'.htpasswd', 0751);
			$app->system->chown($folder_path.'.htpasswd', $website['system_user']);
			$app->system->chgrp($folder_path.'.htpasswd', $website['system_group']);
			$app->log('Created file '.$folder_path.'.htpasswd', LOGLEVEL_DEBUG);
		}

		if(($data['new']['username'] != $data['old']['username'] || $data['new']['active'] == 'n') && $data['old']['username'] != '') {
			$app->system->removeLine($folder_path.'.htpasswd', $data['old']['username'].':');
			$app->log('Removed user: '.$data['old']['username'], LOGLEVEL_DEBUG);
		}

		//* Add or remove the user from .htpasswd file
		if($event_name == 'web_folder_user_delete') {
			$app->system->removeLine($folder_path.'.htpasswd', $data['old']['username'].':');
			$app->log('Removed user: '.$data['old']['username'], LOGLEVEL_DEBUG);
		} else {
			if($data['new']['active'] == 'y') {
				$app->system->replaceLine($folder_path.'.htpasswd', $data['new']['username'].':', $data['new']['username'].':'.$data['new']['password'], 0, 1);
				$app->log('Added or updated user: '.$data['new']['username'], LOGLEVEL_DEBUG);
			}
		}

		if($server_type === 'apache') {
			//* Create the .htaccess file
			//if(!is_file($folder_path.'.htaccess')) {
			$begin_marker = '### ISPConfig folder protection begin ###';
			$end_marker = "### ISPConfig folder protection end ###\n\n";
			$ht_file = $begin_marker."\nAuthType Basic\nAuthName \"Members Only\"\nAuthUserFile ".$folder_path.".htpasswd\nrequire valid-user\n".$end_marker;

			if(file_exists($folder_path.'.htaccess')) {
				$old_content = $app->system->file_get_contents($folder_path.'.htaccess');

				$matches = array();
				if(preg_match('/' . preg_quote($begin_marker, '/') . '(.*?)' . preg_quote($end_marker, '/') . '/s', $old_content, $matches)) {
					$ht_file = str_replace($matches[0], $ht_file, $old_content);
				} else {
					$ht_file .= $old_content;
				}
			}
			unset($old_content);

			$app->system->file_put_contents($folder_path.'.htaccess', $ht_file);
			$app->system->chmod($folder_path.'.htaccess', 0751);
			$app->system->chown($folder_path.'.htaccess', $website['system_user']);
			$app->system->chgrp($folder_path.'.htaccess', $website['system_group']);
			$app->log('Created/modified file '.$folder_path.'.htaccess', LOGLEVEL_DEBUG);
		} else {
			// write basic auth configuration to vhost file because nginx does not support .htaccess
			$webdata['new'] = $webdata['old'] = $website;
			$this->update('web_domain_update', $webdata);
		}

	}

	//* Remove .htaccess and .htpasswd file, when folder protection is removed
	public function eventWebFolderDelete($event_name, $data, $server_type = 'apache') {
		global $app;

		$folder = $data['old'];
		$website = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $folder['parent_domain_id']);

		if(!is_array($folder) or !is_array($website)) {
			$app->log('Not able to retrieve folder or website record.', LOGLEVEL_DEBUG);
			return false;
		}

		$web_folder = 'web';
		if($website['type'] == 'vhostsubdomain' || $website['type'] == 'vhostalias') $web_folder = $website['web_folder'];

		//* Get the folder path.
		if(substr($folder['path'], 0, 1) == '/') $folder['path'] = substr($folder['path'], 1);
		if(substr($folder['path'], -1) == '/') $folder['path'] = substr($folder['path'], 0, -1);
		$folder_path = realpath($website['document_root'].'/' . $web_folder . '/'.$folder['path']);
		if(substr($folder_path, -1) != '/') $folder_path .= '/';

		//* Check if the resulting path is inside the docroot
		if(substr($folder_path, 0, strlen($website['document_root'])) != $website['document_root']) {
			$app->log('Folder path is outside of docroot.', LOGLEVEL_DEBUG);
			return false;
		}

		//* Remove .htpasswd file
		if(is_file($folder_path.'.htpasswd')) {
			$app->system->unlink($folder_path.'.htpasswd');
			$app->log('Removed file '.$folder_path.'.htpasswd', LOGLEVEL_DEBUG);
		}

		if($server_type === 'apache') {
			//* Remove .htaccess file
			if(is_file($folder_path.'.htaccess')) {
				$begin_marker = '### ISPConfig folder protection begin ###';
				$end_marker = "### ISPConfig folder protection end ###\n\n";

				$ht_file = $app->system->file_get_contents($folder_path.'.htaccess');

				$matches = array();
				if(preg_match('/' . preg_quote($begin_marker, '/') . '(.*?)' . preg_quote($end_marker, '/') . '/s', $ht_file, $matches)) {
					$ht_file = str_replace($matches[0], '', $ht_file);
				} else {
					$ht_file = str_replace("AuthType Basic\nAuthName \"Members Only\"\nAuthUserFile ".$folder_path.".htpasswd\nrequire valid-user", '', $ht_file);
				}

				if(trim($ht_file) == '') {
					$app->system->unlink($folder_path.'.htaccess');
					$app->log('Removed file '.$folder_path.'.htaccess', LOGLEVEL_DEBUG);
				} else {
					$app->system->file_put_contents($folder_path.'.htaccess', $ht_file);
					$app->log('Removed protection content from file '.$folder_path.'.htaccess', LOGLEVEL_DEBUG);
				}
			}
		} else {
			// write basic auth configuration to vhost file because nginx does not support .htaccess
			$webdata['new'] = $webdata['old'] = $website;
			$this->update('web_domain_update', $webdata);
		}
	}

	//* Update folder protection, when path has been changed
	public function eventWebFolderUpdate($event_name, $data, $server_type = 'apache') {
		global $app;

		$website = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $data['new']['parent_domain_id']);

		if(!is_array($website)) {
			$app->log('Not able to retrieve folder or website record.', LOGLEVEL_DEBUG);
			return false;
		}

		$web_folder = 'web';
		if($website['type'] == 'vhostsubdomain' || $website['type'] == 'vhostalias') $web_folder = $website['web_folder'];

		//* Get the folder path.
		if(substr($data['old']['path'], 0, 1) == '/') $data['old']['path'] = substr($data['old']['path'], 1);
		if(substr($data['old']['path'], -1) == '/') $data['old']['path'] = substr($data['old']['path'], 0, -1);
		$old_folder_path = realpath($website['document_root'].'/' . $web_folder . '/'.$data['old']['path']);
		if(substr($old_folder_path, -1) != '/') $old_folder_path .= '/';

		if(substr($data['new']['path'], 0, 1) == '/') $data['new']['path'] = substr($data['new']['path'], 1);
		if(substr($data['new']['path'], -1) == '/') $data['new']['path'] = substr($data['new']['path'], 0, -1);
		$new_folder_path = escapeshellcmd($website['document_root'].'/' . $web_folder . '/'.$data['new']['path']);
		if(substr($new_folder_path, -1) != '/') $new_folder_path .= '/';

		//* Check if the resulting path is inside the docroot
		if(stristr($new_folder_path, '..') || stristr($new_folder_path, './') || stristr($new_folder_path, '\\')) {
			$app->log('Folder path "'.$new_folder_path.'" contains .. or ./.', LOGLEVEL_DEBUG);
			return false;
		}
		if(stristr($old_folder_path, '..') || stristr($old_folder_path, './') || stristr($old_folder_path, '\\')) {
			$app->log('Folder path "'.$old_folder_path.'" contains .. or ./.', LOGLEVEL_DEBUG);
			return false;
		}

		//* Check if the resulting path is inside the docroot
		if(substr($old_folder_path, 0, strlen($website['document_root'])) != $website['document_root']) {
			$app->log('Old folder path '.$old_folder_path.' is outside of docroot.', LOGLEVEL_DEBUG);
			return false;
		}
		if(substr($new_folder_path, 0, strlen($website['document_root'])) != $website['document_root']) {
			$app->log('New folder path '.$new_folder_path.' is outside of docroot.', LOGLEVEL_DEBUG);
			return false;
		}

		//* Create the folder path, if it does not exist
		if(!is_dir($new_folder_path)) $app->system->mkdirpath($new_folder_path);

		$begin_marker = '### ISPConfig folder protection begin ###';
		$end_marker = "### ISPConfig folder protection end ###\n\n";

		if($data['old']['path'] != $data['new']['path']) {


			//* move .htpasswd file
			if(is_file($old_folder_path.'.htpasswd')) {
				$app->system->rename($old_folder_path.'.htpasswd', $new_folder_path.'.htpasswd');
				$app->log('Moved file '.$old_folder_path.'.htpasswd to '.$new_folder_path.'.htpasswd', LOGLEVEL_DEBUG);
			}

			if($server_type === 'apache') {
				//* delete old .htaccess file
				if(is_file($old_folder_path.'.htaccess')) {
					$ht_file = $app->system->file_get_contents($old_folder_path.'.htaccess');

					$matches = array();
					if(preg_match('/' . preg_quote($begin_marker, '/') . '(.*?)' . preg_quote($end_marker, '/') . '/s', $ht_file, $matches)) {
						$ht_file = str_replace($matches[0], '', $ht_file);
					} else {
						$ht_file = str_replace("AuthType Basic\nAuthName \"Members Only\"\nAuthUserFile ".$old_folder_path.".htpasswd\nrequire valid-user", '', $ht_file);
					}

					if(trim($ht_file) == '') {
						$app->system->unlink($old_folder_path.'.htaccess');
						$app->log('Removed file '.$old_folder_path.'.htaccess', LOGLEVEL_DEBUG);
					} else {
						$app->system->file_put_contents($old_folder_path.'.htaccess', $ht_file);
						$app->log('Removed protection content from file '.$old_folder_path.'.htaccess', LOGLEVEL_DEBUG);
					}
				}
			}

		}

		if($server_type === 'apache') {
			//* Create the .htaccess file
			if($data['new']['active'] == 'y') {
				$ht_file = $begin_marker."\nAuthType Basic\nAuthName \"Members Only\"\nAuthUserFile ".$new_folder_path.".htpasswd\nrequire valid-user\n".$end_marker;

				if(file_exists($new_folder_path.'.htaccess')) {
					$old_content = $app->system->file_get_contents($new_folder_path.'.htaccess');

					if(preg_match('/' . preg_quote($begin_marker, '/') . '(.*?)' . preg_quote($end_marker, '/') . '/s', $old_content, $matches)) {
						$ht_file = str_replace($matches[0], $ht_file, $old_content);
					} else {
						$ht_file .= $old_content;
					}
				}

				$app->system->file_put_contents($new_folder_path.'.htaccess', $ht_file);
				$app->system->chmod($new_folder_path.'.htaccess', 0751);
				$app->system->chown($new_folder_path.'.htaccess', $website['system_user']);
				$app->system->chgrp($new_folder_path.'.htaccess', $website['system_group']);
				$app->log('Created/modified file '.$new_folder_path.'.htaccess', LOGLEVEL_DEBUG);

				//* Create empty .htpasswd file, if it does not exist
				if(!is_file($new_folder_path.'.htpasswd')) {
					$app->system->touch($new_folder_path.'.htpasswd');
					$app->system->chmod($new_folder_path.'.htpasswd', 0751);
					$app->system->chown($new_folder_path.'.htpasswd', $website['system_user']);
					$app->system->chgrp($new_folder_path.'.htpasswd', $website['system_group']);
					$app->log('Created file '.$new_folder_path.'.htpasswd', LOGLEVEL_DEBUG);
				}
			}

			//* Remove .htaccess file
			if($data['new']['active'] == 'n' && is_file($new_folder_path.'.htaccess')) {
				$ht_file = $app->system->file_get_contents($new_folder_path.'.htaccess');

				if(preg_match('/' . preg_quote($begin_marker, '/') . '(.*?)' . preg_quote($end_marker, '/') . '/s', $ht_file, $matches)) {
					$ht_file = str_replace($matches[0], '', $ht_file);
				} else {
					$ht_file = str_replace("AuthType Basic\nAuthName \"Members Only\"\nAuthUserFile ".$new_folder_path.".htpasswd\nrequire valid-user", '', $ht_file);
				}

				if(trim($ht_file) == '') {
					$app->system->unlink($new_folder_path.'.htaccess');
					$app->log('Removed file '.$new_folder_path.'.htaccess', LOGLEVEL_DEBUG);
				} else {
					$app->system->file_put_contents($new_folder_path.'.htaccess', $ht_file);
					$app->log('Removed protection content from file '.$new_folder_path.'.htaccess', LOGLEVEL_DEBUG);
				}
			}
		} else {
			// write basic auth configuration to vhost file because nginx does not support .htaccess
			$webdata['new'] = $webdata['old'] = $website;
			$this->update('web_domain_update', $webdata);
		}
	}

	
	//* Update the PHP-FPM pool configuration file
	private function php_fpm_pool_update($data, $web_config, $pool_dir, $pool_name, $socket_dir, $server_type = 'apache') {
		global $app, $conf;
		$pool_dir = trim($pool_dir);
		$rh_releasefiles = array('/etc/centos-release', '/etc/redhat-release');
		
		if($data['new']['php'] == 'php-fpm'){
			if(trim($data['new']['fastcgi_php_version']) != ''){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir, -1) != '/') $custom_php_fpm_ini_dir .= '/';
			} else {
				$default_php_fpm = true;
			}
		} else {
			if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] == 'php-fpm'){
				$default_php_fpm = false;
				list($custom_php_fpm_name, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['old']['fastcgi_php_version']));
				if(substr($custom_php_fpm_ini_dir, -1) != '/') $custom_php_fpm_ini_dir .= '/';
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
					$app->services->restartService('php-fpm', 'reload:'.$custom_php_fpm_init_script);
				} else {
					$app->services->restartService('php-fpm', 'reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
				}
			}
			return;
		}

		$app->load('tpl');
		$tpl = new tpl();
		$tpl->newTemplate('php_fpm_pool.conf.master');

		if($data['new']['php_fpm_chroot'] == 'y'){
			$php_fpm_chroot = 1;
		} else {
			$php_fpm_chroot = 0;
		}
		if($server_type === 'apache') {
			$tpl->setVar('apache_version', $app->system->getapacheversion());
			$tpl->setVar('apache_full_version', $app->system->getapacheversion(true));
		}
		
		$tpl->setVar('php_fpm_chroot', $php_fpm_chroot);

		$fpm_socket = $socket_dir.$pool_name.'.sock';
		$tpl->setVar('fpm_socket', $fpm_socket);
		$tpl->setVar('fpm_listen_mode', '0660');

		$tpl->setVar('fpm_pool', $pool_name);
		$tpl->setVar('fpm_port', $web_config['php_fpm_start_port'] + $data['new']['domain_id'] - 1);
		$tpl->setVar('fpm_user', $data['new']['system_user']);
		
		//Red Hat workaround for group ownership of socket files
		foreach($rh_releasefiles as $rh_file) {
			if(file_exists($rh_file) && (filesize($rh_file) > 0)) {
				$tmp = file_get_contents($rh_file);
				if(preg_match('/[67]+\.[0-9]+/m', $tmp)) {
					$tpl->setVar('fpm_group', $data['new']['system_group']);
					$tpl->setVar('fpm_listen_group', $data['new']['system_group']);
				}
				unset($tmp);
			} elseif(!file_exists($rh_file)) {
				//OS seems to be not Red Hat'ish
				$tpl->setVar('fpm_group', $data['new']['system_group']);
				$tpl->setVar('fpm_listen_group', $web_config['group']);
			}
			break;
		}
		
		$tpl->setVar('fpm_listen_user', $data['new']['system_user']);
		$tpl->setVar('fpm_domain', $data['new']['domain']);
		$tpl->setVar('pm', $data['new']['pm']);
		$tpl->setVar('pm_max_children', $data['new']['pm_max_children']);
		$tpl->setVar('pm_start_servers', $data['new']['pm_start_servers']);
		$tpl->setVar('pm_min_spare_servers', $data['new']['pm_min_spare_servers']);
		$tpl->setVar('pm_max_spare_servers', $data['new']['pm_max_spare_servers']);
		$tpl->setVar('pm_process_idle_timeout', $data['new']['pm_process_idle_timeout']);
		$tpl->setVar('pm_max_requests', $data['new']['pm_max_requests']);
		$tpl->setVar('document_root', $data['new']['document_root']);
		$tpl->setVar('security_level', $web_config['security_level']);
		$tpl->setVar('domain', $data['new']['domain']);
		$php_open_basedir = ($data['new']['php_open_basedir'] == '')?escapeshellcmd($data['new']['document_root']):escapeshellcmd($data['new']['php_open_basedir']);
		if($php_fpm_chroot){
			$document_root = $data['new']['document_root'];
			$domain = $data['new']['domain'];
			$php_open_basedir = str_replace(":/srv/www/$domain/web",'',$php_open_basedir);
			$php_open_basedir = str_replace(":/var/www/$domain/web",'',$php_open_basedir);
			$php_open_basedir = str_replace("$document_root",'',$php_open_basedir);
		}
		$tpl->setVar('php_open_basedir', $php_open_basedir);
		if($php_open_basedir != ''){
			$tpl->setVar('enable_php_open_basedir', '');
		} else {
			$tpl->setVar('enable_php_open_basedir', ';');
		}

		// Custom php.ini settings
		$final_php_ini_settings = array();
		$custom_php_ini_settings = trim($data['new']['custom_php_ini']);
		
		if(intval($data['new']['directive_snippets_id']) > 0){
			$snippet = $app->db->queryOneRecord("SELECT * FROM directive_snippets WHERE directive_snippets_id = ? AND type = ? AND active = 'y' AND customer_viewable = 'y'", intval($data['new']['directive_snippets_id']), $server_type);
			if(isset($snippet['required_php_snippets']) && trim($snippet['required_php_snippets']) != ''){
				$required_php_snippets = explode(',', trim($snippet['required_php_snippets']));
				if(is_array($required_php_snippets) && !empty($required_php_snippets)){
					foreach($required_php_snippets as $required_php_snippet){
						$required_php_snippet = intval($required_php_snippet);
						if($required_php_snippet > 0){
							$php_snippet = $app->db->queryOneRecord("SELECT * FROM directive_snippets WHERE ".($snippet['master_directive_snippets_id'] > 0 ? 'master_' : '')."directive_snippets_id = ? AND type = 'php' AND active = 'y'", $required_php_snippet);
							$php_snippet['snippet'] = trim($php_snippet['snippet']);
							if($php_snippet['snippet'] != ''){
								$custom_php_ini_settings .= "\n".$php_snippet['snippet'];
							}
						}
					}
				}
			}
		}
		
		$custom_session_save_path = false;
		if($custom_php_ini_settings != ''){
			// Make sure we only have Unix linebreaks
			$custom_php_ini_settings = str_replace("\r\n", "\n", $custom_php_ini_settings);
			$custom_php_ini_settings = str_replace("\r", "\n", $custom_php_ini_settings);
			$ini_settings = explode("\n", $custom_php_ini_settings);
			if(is_array($ini_settings) && !empty($ini_settings)){
				foreach($ini_settings as $ini_setting){
					$ini_setting = trim($ini_setting);
					if(substr($ini_setting, 0, 1) == ';') continue;
					if(substr($ini_setting, 0, 1) == '#') continue;
					if(substr($ini_setting, 0, 2) == '//') continue;
					list($key, $value) = explode('=', $ini_setting, 2);
					$value = trim($value);
					if($value != ''){
						$key = trim($key);
						if($key == 'session.save_path') $custom_session_save_path = true;
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

		$tpl->setVar('custom_session_save_path', ($custom_session_save_path ? 'y' : 'n'));

		$tpl->setLoop('custom_php_ini_settings', $final_php_ini_settings);

		$app->system->file_put_contents($pool_dir.$pool_name.'.conf', $tpl->grab());
		$app->log('Writing the PHP-FPM config file: '.$pool_dir.$pool_name.'.conf', LOGLEVEL_DEBUG);
		unset($tpl);

		// delete pool in all other PHP versions
		$default_pool_dir = trim(escapeshellcmd($web_config['php_fpm_pool_dir']));
		if(substr($default_pool_dir, -1) != '/') $default_pool_dir .= '/';
		if($default_pool_dir != $pool_dir){
			if ( @is_file($default_pool_dir.$pool_name.'.conf') ) {
				$app->system->unlink($default_pool_dir.$pool_name.'.conf');
				$app->log('Removed PHP-FPM config file: '.$default_pool_dir.$pool_name.'.conf', LOGLEVEL_DEBUG);
				$app->services->restartService('php-fpm', 'reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
			}
		}
		$php_versions = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ?", $conf["server_id"]);
		if(is_array($php_versions) && !empty($php_versions)){
			foreach($php_versions as $php_version){
				$php_version['php_fpm_pool_dir'] = trim($php_version['php_fpm_pool_dir']);
				if(substr($php_version['php_fpm_pool_dir'], -1) != '/') $php_version['php_fpm_pool_dir'] .= '/';
				if($php_version['php_fpm_pool_dir'] != $pool_dir){
					if ( @is_file($php_version['php_fpm_pool_dir'].$pool_name.'.conf') ) {
						$app->system->unlink($php_version['php_fpm_pool_dir'].$pool_name.'.conf');
						$app->log('Removed PHP-FPM config file: '.$php_version['php_fpm_pool_dir'].$pool_name.'.conf', LOGLEVEL_DEBUG);
						$app->services->restartService('php-fpm', 'reload:'.$php_version['php_fpm_init_script']);
					}
				}
			}
		}
		// Reload current PHP-FPM after all others
		sleep(1);
		if(!$default_php_fpm){
			$app->services->restartService('php-fpm', 'reload:'.$custom_php_fpm_init_script);
		} else {
			$app->services->restartService('php-fpm', 'reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
		}
	}

	//* Delete the PHP-FPM pool configuration file
	private function php_fpm_pool_delete ($data, $web_config, $server_type = 'apache') {
		global $app, $conf;

		if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] == 'php-fpm'){
			$default_php_fpm = false;
			// $custom_php_fpm_name
			list(, $custom_php_fpm_init_script, $custom_php_fpm_ini_dir, $custom_php_fpm_pool_dir) = explode(':', trim($data['old']['fastcgi_php_version']));
			if(substr($custom_php_fpm_ini_dir, -1) != '/') $custom_php_fpm_ini_dir .= '/';
		} else {
			$default_php_fpm = true;
		}

		if($default_php_fpm){
			$pool_dir = escapeshellcmd($web_config['php_fpm_pool_dir']);
		} else {
			$pool_dir = $custom_php_fpm_pool_dir;
		}
		$pool_dir = trim($pool_dir);

		if(substr($pool_dir, -1) != '/') $pool_dir .= '/';
		$pool_name = 'web'.$data['old']['domain_id'];

		if ( @is_file($pool_dir.$pool_name.'.conf') ) {
			$app->system->unlink($pool_dir.$pool_name.'.conf');
			$app->log('Removed PHP-FPM config file: '.$pool_dir.$pool_name.'.conf', LOGLEVEL_DEBUG);
		}

		// delete pool in all other PHP versions
		$default_pool_dir = trim(escapeshellcmd($web_config['php_fpm_pool_dir']));
		if(substr($default_pool_dir, -1) != '/') $default_pool_dir .= '/';
		if($default_pool_dir != $pool_dir){
			if ( @is_file($default_pool_dir.$pool_name.'.conf') ) {
				$app->system->unlink($default_pool_dir.$pool_name.'.conf');
				$app->log('Removed PHP-FPM config file: '.$default_pool_dir.$pool_name.'.conf', LOGLEVEL_DEBUG);
				$app->services->restartService('php-fpm', 'reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
			}
		}
		$php_versions = $app->db->queryAllRecords("SELECT * FROM server_php WHERE php_fpm_init_script != '' AND php_fpm_ini_dir != '' AND php_fpm_pool_dir != '' AND server_id = ?", $data['old']['server_id']);
		if(is_array($php_versions) && !empty($php_versions)){
			foreach($php_versions as $php_version){
				$php_version['php_fpm_pool_dir'] = trim($php_version['php_fpm_pool_dir']);
				if(substr($php_version['php_fpm_pool_dir'], -1) != '/') $php_version['php_fpm_pool_dir'] .= '/';
				if($php_version['php_fpm_pool_dir'] != $pool_dir){
					if ( @is_file($php_version['php_fpm_pool_dir'].$pool_name.'.conf') ) {
						$app->system->unlink($php_version['php_fpm_pool_dir'].$pool_name.'.conf');
						$app->log('Removed PHP-FPM config file: '.$php_version['php_fpm_pool_dir'].$pool_name.'.conf', LOGLEVEL_DEBUG);
						$app->services->restartService('php-fpm', 'reload:'.$php_version['php_fpm_init_script']);
					}
				}
			}
		}

		// Reload current PHP-FPM after all others
		sleep(1);
		if(!$default_php_fpm){
			$app->services->restartService('php-fpm', 'reload:'.$custom_php_fpm_init_script);
		} else {
			$app->services->restartService('php-fpm', 'reload:'.$conf['init_scripts'].'/'.$web_config['php_fpm_init_script']);
		}
	}	

	public function eventClientDelete($event_name, $data, $server_type = 'apache') {
		global $app, $conf;

		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');

		$client_id = intval($data['old']['client_id']);
		if($client_id > 0) {

			$client_dir = $web_config['website_basedir'].'/clients/client'.$client_id;
			if(is_dir($client_dir) && !stristr($client_dir, '..')) {
				// remove symlinks from $client_dir
				$files = array_diff(scandir($client_dir), array('.', '..'));
				if(is_array($files) && !empty($files)){
					foreach($files as $file){
						if(is_link($client_dir.'/'.$file)){
							unlink($client_dir.'/'.$file);
							$app->log('Removed symlink: '.$client_dir.'/'.$file, LOGLEVEL_DEBUG);
						}
					}
				}

				@rmdir($client_dir);
				$app->log('Removed client directory: '.$client_dir, LOGLEVEL_DEBUG);
			}

			if($app->system->is_group('client'.$client_id)){
				$app->system->_exec('groupdel client'.$client_id);
				$app->log('Removed group client'.$client_id, LOGLEVEL_DEBUG);
			}
		}

	}

	private function get_seo_redirects($web, $prefix = '', $force_subdomain = false, $server_type = 'apache'){
		$seo_redirects = array();

		if(substr($web['domain'], 0, 2) === '*.') $web['subdomain'] = '*';
		
		if($server_type === 'apache') {
			if($web['subdomain'] == 'www' || $web['subdomain'] == '*'){
				$domain = str_replace('.', '\.', $web['domain']);
				if($web['seo_redirect'] == 'non_www_to_www'){
					$seo_redirects[$prefix.'seo_redirect_origin_domain'] = $domain;
					$seo_redirects[$prefix.'seo_redirect_target_domain'] = 'www.'.$web['domain'];
					$seo_redirects[$prefix.'seo_redirect_operator'] = '';
				}
				if($web['seo_redirect'] == '*_domain_tld_to_www_domain_tld'){
					$seo_redirects[$prefix.'seo_redirect_origin_domain'] = $domain.'|.*\.'.$domain.'(?<!^www\.'.$domain.')';
					$seo_redirects[$prefix.'seo_redirect_target_domain'] = 'www.'.$web['domain'];
					$seo_redirects[$prefix.'seo_redirect_operator'] = '';
				}
				if($web['seo_redirect'] == '*_to_www_domain_tld'){
					$seo_redirects[$prefix.'seo_redirect_origin_domain'] = 'www\.'.$domain;
					$seo_redirects[$prefix.'seo_redirect_target_domain'] = 'www.'.$web['domain'];
					$seo_redirects[$prefix.'seo_redirect_operator'] = '!';
				}
			}
			if($web['seo_redirect'] == 'www_to_non_www'){
				$seo_redirects[$prefix.'seo_redirect_origin_domain'] = 'www\.'.$domain;
				$seo_redirects[$prefix.'seo_redirect_target_domain'] = $web['domain'];
				$seo_redirects[$prefix.'seo_redirect_operator'] = '';
			}
			if($web['seo_redirect'] == '*_domain_tld_to_domain_tld'){
				$seo_redirects[$prefix.'seo_redirect_origin_domain'] = '.*\.'.$domain;
				$seo_redirects[$prefix.'seo_redirect_target_domain'] = $web['domain'];
				$seo_redirects[$prefix.'seo_redirect_operator'] = '';
			}
			if($web['seo_redirect'] == '*_to_domain_tld'){
				$seo_redirects[$prefix.'seo_redirect_origin_domain'] = $domain;
				$seo_redirects[$prefix.'seo_redirect_target_domain'] = $web['domain'];
				$seo_redirects[$prefix.'seo_redirect_operator'] = '!';
			}
		} else {
			if(($web['subdomain'] == 'www' || $web['subdomain'] == '*') && $force_subdomain != 'www'){
				if($web['seo_redirect'] == 'non_www_to_www'){
					$seo_redirects[$prefix.'seo_redirect_origin_domain'] = $web['domain'];
					$seo_redirects[$prefix.'seo_redirect_target_domain'] = 'www.'.$web['domain'];
					$seo_redirects[$prefix.'seo_redirect_operator'] = '=';
				}
				if($web['seo_redirect'] == '*_domain_tld_to_www_domain_tld'){
					// ^(example\.com|(?!\bwww\b)\.example\.com)$
					// ^(example\.com|((?:\w+(?:-\w+)*\.)*)((?!www\.)\w+(?:-\w+)*)(\.example\.com))$
					$seo_redirects[$prefix.'seo_redirect_origin_domain'] = '^('.str_replace('.', '\.', $web['domain']).'|((?:\w+(?:-\w+)*\.)*)((?!www\.)\w+(?:-\w+)*)(\.'.str_replace('.', '\.', $web['domain']).'))$';
					$seo_redirects[$prefix.'seo_redirect_target_domain'] = 'www.'.$web['domain'];
					$seo_redirects[$prefix.'seo_redirect_operator'] = '~*';
				}
				if($web['seo_redirect'] == '*_to_www_domain_tld'){
					$seo_redirects[$prefix.'seo_redirect_origin_domain'] = 'www.'.$web['domain'];
					$seo_redirects[$prefix.'seo_redirect_target_domain'] = 'www.'.$web['domain'];
					$seo_redirects[$prefix.'seo_redirect_operator'] = '!=';
				}
			}
			if($force_subdomain != 'none'){
				if($web['seo_redirect'] == 'www_to_non_www'){
					$seo_redirects[$prefix.'seo_redirect_origin_domain'] = 'www.'.$web['domain'];
					$seo_redirects[$prefix.'seo_redirect_target_domain'] = $web['domain'];
					$seo_redirects[$prefix.'seo_redirect_operator'] = '=';
				}
				if($web['seo_redirect'] == '*_domain_tld_to_domain_tld'){
					// ^(.+)\.example\.com$
					$seo_redirects[$prefix.'seo_redirect_origin_domain'] = '^(.+)\.'.str_replace('.', '\.', $web['domain']).'$';
					$seo_redirects[$prefix.'seo_redirect_target_domain'] = $web['domain'];
					$seo_redirects[$prefix.'seo_redirect_operator'] = '~*';
				}
				if($web['seo_redirect'] == '*_to_domain_tld'){
					$seo_redirects[$prefix.'seo_redirect_origin_domain'] = $web['domain'];
					$seo_redirects[$prefix.'seo_redirect_target_domain'] = $web['domain'];
					$seo_redirects[$prefix.'seo_redirect_operator'] = '!=';
				}
			}
		}
		
		return $seo_redirects;
	}

	private function _checkTcp ($host, $port) {

		$errno = 0;
		$errstr = '';
		$fp = @fsockopen($host, $port, $errno, $errstr, 2);

		if ($fp) {
			fclose($fp);
			return true;
		} else {
			return false;
		}
	}

	private function _rewrite_quote($string) {
		return str_replace(array('.', '*', '?', '+'), array('\\.', '\\*', '\\?', '\\+'), $string);
	}

	private function _is_url($string) {
		return preg_match('/^(f|ht)tp(s)?:\/\//i', $string);
	}
	
	private function url_is_local($hostname, $domain_id){
		global $app;

		// ORDER BY clause makes sure wildcard subdomains (*) are listed last in the result array so that we can find direct matches first
		$webs = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE active = 'y' ORDER BY subdomain ASC");
		if(is_array($webs) && !empty($webs)){
			foreach($webs as $web){
				// web domain doesn't match hostname
				if(substr($hostname, -strlen($web['domain'])) != $web['domain']) continue;
				// own vhost and therefore server {} container of its own
				//if($web['type'] == 'vhostsubdomain' || $web['type'] == 'vhostalias') continue;
				// alias domains/subdomains using rewrites and therefore a server {} container of their own
				//if(($web['type'] == 'alias' || $web['type'] == 'subdomain') && $web['redirect_type'] != '' && $web['redirect_path'] != '') continue;

				if($web['subdomain'] == '*'){
					$pattern = '/\.?'.str_replace('.', '\.', $web['domain']).'$/i';
				}
				if($web['subdomain'] == 'none'){
					if($web['domain'] == $hostname){
						if($web['domain_id'] == $domain_id || $web['parent_domain_id'] == $domain_id){
							// own vhost and therefore server {} container of its own
							if($web['type'] == 'vhostsubdomain' || $web['type'] == 'vhostalias') return false;
							// alias domains/subdomains using rewrites and therefore a server {} container of their own
							if(($web['type'] == 'alias' || $web['type'] == 'subdomain') && $web['redirect_type'] != '' && $web['redirect_path'] != '') return false;
							return true;
						} else {
							return false;
						}
					}
					$pattern = '/^'.str_replace('.', '\.', $web['domain']).'$/i';
				}
				if($web['subdomain'] == 'www'){
					if($web['domain'] == $hostname || $web['subdomain'].'.'.$web['domain'] == $hostname){
						if($web['domain_id'] == $domain_id || $web['parent_domain_id'] == $domain_id){
							// own vhost and therefore server {} container of its own
							if($web['type'] == 'vhostsubdomain' || $web['type'] == 'vhostalias') return false;
							// alias domains/subdomains using rewrites and therefore a server {} container of their own
							if(($web['type'] == 'alias' || $web['type'] == 'subdomain') && $web['redirect_type'] != '' && $web['redirect_path'] != '') return false;
							return true;
						} else {
							return false;
						}
					}
					$pattern = '/^(www\.)?'.str_replace('.', '\.', $web['domain']).'$/i';
				}
				if(preg_match($pattern, $hostname)){
					if($web['domain_id'] == $domain_id || $web['parent_domain_id'] == $domain_id){
						// own vhost and therefore server {} container of its own
						if($web['type'] == 'vhostsubdomain' || $web['type'] == 'vhostalias') return false;
						// alias domains/subdomains using rewrites and therefore a server {} container of their own
						if(($web['type'] == 'alias' || $web['type'] == 'subdomain') && $web['redirect_type'] != '' && $web['redirect_path'] != '') return false;
						return true;
					} else {
						return false;
					}
				}
			}
		}

		return false;
	}

	private function nginx_replace($matches){
		$location = 'location'.($matches[1] != '' ? ' '.$matches[1] : '').' '.$matches[2].' '.$matches[3];
		if($matches[4] == '##merge##' || $matches[7] == '##merge##') $location .= ' ##merge##';
		if($matches[4] == '##delete##' || $matches[7] == '##delete##') $location .= ' ##delete##';
		$location .= "\n";
		$location .= $matches[5]."\n";
		$location .= $matches[6];
		return $location;
	}
	
	private function nginx_merge_locations($vhost_conf) {
		global $app, $conf;

		$subroot = array();
        if(preg_match('/##subroot (.+?)\s*##/', $vhost_conf, $subroot)) {
            if(!preg_match('/^(?:[a-z0-9\/_-]|\.(?!\.))+$/iD', $subroot[1])) {
                $app->log('Token ##subroot is unsecure (server ID: '.$conf['server_id'].').', LOGLEVEL_WARN);
            } else {
                $insert_pos = strpos($vhost_conf, ';', strpos($vhost_conf, 'root '));
                $vhost_conf = substr_replace($vhost_conf, ltrim($subroot[1], '/'), $insert_pos, 0);
            }
        }

		$lines = explode("\n", $vhost_conf);

		// if whole location block is in one line, split it up into multiple lines
		if(is_array($lines) && !empty($lines)){
			$linecount = sizeof($lines);
			for($h=0;$h<$linecount;$h++){
				// remove comments
				if(substr(trim($lines[$h]), 0, 1) == '#'){
					unset($lines[$h]);
					continue;
				}

				$lines[$h] = rtrim($lines[$h]);
				/*
				if(substr(ltrim($lines[$h]), 0, 8) == 'location' && strpos($lines[$h], '{') !== false && strpos($lines[$h], ';') !== false){
					$lines[$h] = str_replace("{", "{\n", $lines[$h]);
					$lines[$h] = str_replace(";", ";\n", $lines[$h]);
					if(strpos($lines[$h], '##merge##') !== false){
						$lines[$h] = str_replace('##merge##', '', $lines[$h]);
						$lines[$h] = substr($lines[$h],0,strpos($lines[$h], '{')).' ##merge##'.substr($lines[$h],strpos($lines[$h], '{')+1);
					}
				}
				if(substr(ltrim($lines[$h]), 0, 8) == 'location' && strpos($lines[$h], '{') !== false && strpos($lines[$h], '}') !== false && strpos($lines[$h], ';') === false){
					$lines[$h] = str_replace("{", "{\n", $lines[$h]);
					if(strpos($lines[$h], '##merge##') !== false){
						$lines[$h] = str_replace('##merge##', '', $lines[$h]);
						$lines[$h] = substr($lines[$h],0,strpos($lines[$h], '{')).' ##merge##'.substr($lines[$h],strpos($lines[$h], '{')+1);
					}
				}
				*/
				$pattern = '/^[^\S\n]*location[^\S\n]+(?:(.+)[^\S\n]+)?(.+)[^\S\n]*(\{)[^\S\n]*(##merge##|##delete##)?[^\S\n]*(.+)[^\S\n]*(\})[^\S\n]*(##merge##|##delete##)?[^\S\n]*$/';
				$lines[$h] = preg_replace_callback($pattern, array($this, 'nginx_replace') , $lines[$h]);
			}
		}
		$vhost_conf = implode("\n", $lines);
		unset($lines);
		unset($linecount);

		$lines = explode("\n", $vhost_conf);

		if(is_array($lines) && !empty($lines)){
			$locations = array();
			$locations_to_delete = array();
			$islocation = false;
			$linecount = sizeof($lines);
			$server_count = 0;

			for($i=0;$i<$linecount;$i++){
				$l = trim($lines[$i]);
				if(substr($l, 0, 8) == 'server {') $server_count += 1;
				if($server_count > 1) break;
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
					if(substr($l, -9) == '##merge##') $locations[$location]['action'] = 'merge';
					if(substr($l, -10) == '##delete##') $locations[$location]['action'] = 'delete';

					if(!isset($locations[$location]['open_tag'])) $locations[$location]['open_tag'] = '        location '.$location.' {';
					if(!isset($locations[$location]['location']) || $locations[$location]['action'] == 'replace') $locations[$location]['location'] = '';
					if($locations[$location]['action'] == 'delete') $locations_to_delete[] = $location;
					if(!isset($locations[$location]['end_tag'])) $locations[$location]['end_tag'] = '        }';
					if(!isset($locations[$location]['start_line'])) $locations[$location]['start_line'] = $i;

					unset($lines[$i]);

				} else {

					if($islocation){
						$openingbracketpos = strrpos($l, '{');
						if($openingbracketpos !== false){
							$level += 1;
						}
						$closingbracketpos = strrpos($l, '}');
						if($closingbracketpos !== false && $level > 0 && $closingbracketpos >= intval($openingbracketpos)){
							$level -= 1;
							$locations[$location]['location'] .= $lines[$i]."\n";
						} elseif($closingbracketpos !== false && $level == 0 && $closingbracketpos >= intval($openingbracketpos)){
							$islocation = false;
						} else {
							$locations[$location]['location'] .= $lines[$i]."\n";
						}
						unset($lines[$i]);
					}

				}
			}

			if(is_array($locations) && !empty($locations)){
				if(is_array($locations_to_delete) && !empty($locations_to_delete)){
					foreach($locations_to_delete as $location_to_delete){
						if(isset($locations[$location_to_delete])) unset($locations[$location_to_delete]);
					}
				}

				foreach($locations as $val){
					$new_location = $val['open_tag']."\n".$val['location'].$val['end_tag'];
					$lines[$val['start_line']] = $new_location;
				}
			}
			ksort($lines);
			$vhost_conf = implode("\n", $lines);
		}

		return trim($vhost_conf);
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
		//* read line by line and search for the username and authname
		while ($line = fgets($in)) {
			//* is the "replace-comment" found...
			if (trim($line) == '# WEBDAV BEGIN') {
				//* The begin of the webdav - section is found, so ignore all lines til the end  is found
				$inWebdavSection = true;
				$output .= "# WEBDAV BEGIN\n";
				//* add all the webdav-dirs to the webdav-section
				$files = @scandir($webdavRoot);
				if(is_array($files)) {
					foreach($files as $file) {
						if (substr($file, strlen($file) - strlen('.htdigest')) == '.htdigest' && preg_match("/^[a-zA-Z0-9\-_\.]*$/", $file)) {
							//* found a htdigest - file, so add it to webdav
							$fn = substr($file, 0, strlen($file) - strlen('.htdigest'));
							$output .= "\n";
							$output .= "Alias /webdav/$fn $webdavRoot/$fn\n";
							$output .= "<Location /webdav/$fn>\n";
							$output .= "DAV On\n";
							$output .= "BrowserMatch MSIE AuthDigestEnableQueryStringHack=On\n";
							$output .= "AuthType Digest\n";
							if($fn != '' && $fn != '/') {
								$output .= "AuthName \"" . $fn . "\"\n";
							} else {
								$output .= "AuthName \"Restricted Area\"\n";
							}
							$output .= "AuthUserFile $webdavRoot/$file\n";
							$output .= "Require valid-user\n";
							$output .= "Options +Indexes\n";
							$output .= "</Location>\n";
						}
					}
				}
			}
			//*  is the "replace-comment-end" found...
			if (trim($line) == '# WEBDAV END') {
				//* The end of the webdav - section is found, so stop ignoring
				$inWebdavSection = false;
			}
			//* Write the line to the output, if it is not in the section
			if (!$inWebdavSection) {
				$output .= $line;
			}
		}
		fclose($in);
		//* Now lets write the new file
		$app->system->file_put_contents($fileName, $output);
	}
	
	public function getWebFolder(&$data, $type, $use_old = false) {
		global $app;
		
		$folder = $type;
		
		if($type === 'web' && $data['new']['type'] == 'vhost'){
			if($use_old === true) {
				if($data['old']['web_folder'] != ''){
					if(substr($data['old']['web_folder'],0,1) == '/') $data['old']['web_folder'] = substr($data['old']['web_folder'],1);
					if(substr($data['old']['web_folder'],-1) == '/') $data['old']['web_folder'] = substr($data['old']['web_folder'],0,-1);
				}
				$folder .= '/'.$data['old']['web_folder'];
			} else {
				if($data['new']['web_folder'] != ''){
					if(substr($data['new']['web_folder'],0,1) == '/') $data['new']['web_folder'] = substr($data['new']['web_folder'],1);
					if(substr($data['new']['web_folder'],-1) == '/') $data['new']['web_folder'] = substr($data['new']['web_folder'],0,-1);
				}
				$folder .= '/'.$data['new']['web_folder'];
			}
		} elseif($data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias') {
			if($use_old === true) {
				if(isset($data['old']['parent_domain_id'])) {
					// old one
					$tmp = $app->db->queryOneRecord('SELECT `domain` FROM web_domain WHERE domain_id = ?', $data['old']['parent_domain_id']);
					$subdomain_host = preg_replace('/^(.*)\.' . preg_quote($tmp['domain'], '/') . '$/', '$1', $data['old']['domain']);
					if($subdomain_host == '') $subdomain_host = 'web'.$data['old']['domain_id'];
					if($type === 'web') {
						$folder = $data['old']['web_folder'];
					} else {
						$folder .= '/' . $subdomain_host;
					}
					unset($tmp);
				}
			} else {
				// new one
				$tmp = $app->db->queryOneRecord('SELECT `domain` FROM web_domain WHERE domain_id = ?', $data['new']['parent_domain_id']);
				$subdomain_host = preg_replace('/^(.*)\.' . preg_quote($tmp['domain'], '/') . '$/', '$1', $data['new']['domain']);
				if($subdomain_host == '') $subdomain_host = 'web'.$data['new']['domain_id'];
				if($type === 'web') {
					$folder = $data['new']['web_folder'];
				} else {
					$folder .= '/' . $subdomain_host;
				}
				unset($tmp);
			}
		}
	}
}
