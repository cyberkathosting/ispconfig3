<?php

/*
Copyright (c) 2017, Marius Burkard, projektfarm Gmbh
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

class letsencrypt {

	/**
	 * Construct for this class
	 *
	 * @return system
	 */
	private $base_path = '/etc/letsencrypt';
	private $renew_config_path = '/etc/letsencrypt/renewal';


	public function __construct(){

	}

	public function get_letsencrypt_certificate_paths($domains = array()) {
		global $app;
		
		if(empty($domains)) return false;
		if(!is_dir($this->renew_config_path)) return false;
		
		$dir = opendir($this->renew_config_path);
		if(!$dir) return false;
		
		$path_scores = array();
		
		$main_domain = reset($domains);
		sort($domains);
		$min_diff = false;
		
		while($file = readdir($dir)) {
			if($file === '.' || $file === '..' || substr($file, -5) !== '.conf')  continue;
			$file_path = $this->renew_config_path . '/' . $file;
			if(!is_file($file_path) || !is_readable($file_path)) continue;
			
			$fp = fopen($file_path, 'r');
			if(!$fp) continue;
			
			$path_scores[$file_path] = array(
				'domains' => array(),
				'diff' => 0,
				'has_main_domain' => false,
				'cert_paths' => array(
					'cert' => '',
					'privkey' => '',
					'chain' => '',
					'fullchain' => ''
				)
			);
			$in_list = false;
			while(!feof($fp) && $line = fgets($fp)) {
				$line = trim($line);
				if($line === '') continue;
				elseif(!$in_list) {
					if($line == '[[webroot_map]]') $in_list = true;
					
					$tmp = explode('=', $line, 2);
					if(count($tmp) != 2) continue;
					$key = trim($tmp[0]);
					if($key == 'cert' || $key == 'privkey' || $key == 'chain' || $key == 'fullchain') {
						$path_scores[$file_path]['cert_paths'][$key] = trim($tmp[1]);
					}
					
					continue;
				}
				
				$tmp = explode('=', $line, 2);
				if(count($tmp) != 2) continue;
				
				$domain = trim($tmp[0]);
				if($domain == $main_domain) $path_scores[$file_path]['has_main_domain'] = true;
				$path_scores[$file_path]['domains'][] = $domain;
			}
			fclose($fp);
			
			sort($path_scores[$file_path]['domains']);
			if(count(array_intersect($domains, $path_scores[$file_path]['domains'])) < 1) {
				$path_scores[$file_path]['diff'] = false;
			} else {
				// give higher diff value to missing domains than to those that are too much in there
				$path_scores[$file_path]['diff'] = (count(array_diff($domains, $path_scores[$file_path]['domains'])) * 1.5) + count(array_diff($path_scores[$file_path]['domains'], $domains));
			}
			 
			if($min_diff === false || $path_scores[$file_path]['diff'] < $min_diff) $min_diff = $path_scores[$file_path]['diff'];
		}
		closedir($dir);

		if($min_diff === false) return false;
		
		$cert_paths = false;
		$used_path = false;
		foreach($path_scores as $path => $data) {
			if($data['diff'] === $min_diff) {
				$used_path = $path;
				$cert_paths = $data['cert_paths'];
				if($data['has_main_domain'] == true) break;
			}
		}
		
		$app->log("Let's Encrypt Cert config path is: " . ($used_path ? $used_path : "not found") . ".", LOGLEVEL_DEBUG);
		
		return $cert_paths;
	}
	
	private function get_ssl_domain($data) {
		$domain = $data['new']['ssl_domain'];
		if(!$domain) $domain = $data['new']['domain'];
		
		if($data['new']['ssl'] == 'y' && $data['new']['ssl_letsencrypt'] == 'y') {
			$domain = $data['new']['domain'];
			if(substr($domain, 0, 2) === '*.') {
				// wildcard domain not yet supported by letsencrypt!
				$app->log('Wildcard domains not yet supported by letsencrypt, so changing ' . $domain . ' to ' . substr($domain, 2), LOGLEVEL_WARN);
				$domain = substr($domain, 2);
			}
		}
		
		return $domain;
	}
	
	public function get_website_certificate_paths($data) {
		global $app;
		
		$ssl_dir = $data['new']['document_root'].'/ssl';
		$domain = $this->get_ssl_domain($data);
		
		$cert_paths = array(
			'domain' => $domain,
			'key' => $ssl_dir.'/'.$domain.'.key',
			'key2' => $ssl_dir.'/'.$domain.'.key.org',
			'csr' => $ssl_dir.'/'.$domain.'.csr',
			'crt' => $ssl_dir.'/'.$domain.'.crt',
			'bundle' => $ssl_dir.'/'.$domain.'.bundle'
		);
		
		if($data['new']['ssl'] == 'y' && $data['new']['ssl_letsencrypt'] == 'y') {
			$cert_paths = array(
				'domain' => $domain,
				'key' => $ssl_dir.'/'.$domain.'-le.key',
				'key2' => $ssl_dir.'/'.$domain.'-le.key.org',
				'crt' => $ssl_dir.'/'.$domain.'-le.crt',
				'bundle' => $ssl_dir.'/'.$domain.'-le.bundle'
			);
		}
		
		return $cert_paths;
	}
	
	public function request_certificates($data, $server_type = 'apache') {
		global $app, $conf;
		
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
		
		$tmp = $app->letsencrypt->get_website_certificate_paths($data);
		$domain = $tmp['domain'];
		$key_file = $tmp['key'];
		$key_file2 = $tmp['key2'];
		$csr_file = $tmp['csr'];
		$crt_file = $tmp['crt'];
		$bundle_file = $tmp['bundle'];
		
		// default values
		$temp_domains = array($domain);
		$cli_domain_arg = '';
		$subdomains = null;
		$aliasdomains = null;

		//* be sure to have good domain
		if(substr($domain,0,4) != 'www.' && ($data['new']['subdomain'] == "www" || $data['new']['subdomain'] == "*")) {
			$temp_domains[] = "www." . $domain;
		}

		//* then, add subdomain if we have
		$subdomains = $app->db->queryAllRecords('SELECT domain FROM web_domain WHERE parent_domain_id = '.intval($data['new']['domain_id'])." AND active = 'y' AND type = 'subdomain' AND ssl_letsencrypt_exclude != 'y'");
		if(is_array($subdomains)) {
			foreach($subdomains as $subdomain) {
				$temp_domains[] = $subdomain['domain'];
			}
		}
		
		//* then, add alias domain if we have
		$aliasdomains = $app->db->queryAllRecords('SELECT domain,subdomain FROM web_domain WHERE parent_domain_id = '.intval($data['new']['domain_id'])." AND active = 'y' AND type = 'alias' AND ssl_letsencrypt_exclude != 'y'");
		if(is_array($aliasdomains)) {
			foreach($aliasdomains as $aliasdomain) {
				$temp_domains[] = $aliasdomain['domain'];
				if(isset($aliasdomain['subdomain']) && substr($aliasdomain['domain'],0,4) != 'www.' && ($aliasdomain['subdomain'] == "www" OR $aliasdomain['subdomain'] == "*")) {
					$temp_domains[] = "www." . $aliasdomain['domain'];
				}
			}
		}

		// prevent duplicate
		$temp_domains = array_unique($temp_domains);

		// check if domains are reachable to avoid letsencrypt verification errors
		$le_rnd_file = uniqid('le-') . '.txt';
		$le_rnd_hash = md5(uniqid('le-', true));
		if(!is_dir('/usr/local/ispconfig/interface/acme/.well-known/acme-challenge/')) {
			$app->system->mkdir('/usr/local/ispconfig/interface/acme/.well-known/acme-challenge/', false, 0755, true);
		}
		file_put_contents('/usr/local/ispconfig/interface/acme/.well-known/acme-challenge/' . $le_rnd_file, $le_rnd_hash);

		$le_domains = array();
		foreach($temp_domains as $temp_domain) {
			if((isset($web_config['skip_le_check']) && $web_config['skip_le_check'] == 'y') || (isset($server_config['migration_mode']) && $server_config['migration_mode'] == 'y')) {
				$le_domains[] = $temp_domain;
			} else {
				$le_hash_check = trim(@file_get_contents('http://' . $temp_domain . '/.well-known/acme-challenge/' . $le_rnd_file));
				if($le_hash_check == $le_rnd_hash) {
					$le_domains[] = $temp_domain;
					$app->log("Verified domain " . $temp_domain . " should be reachable for letsencrypt.", LOGLEVEL_DEBUG);
				} else {
					$app->log("Could not verify domain " . $temp_domain . ", so excluding it from letsencrypt request.", LOGLEVEL_WARN);
				}
			}
		}
		$temp_domains = $le_domains;
		unset($le_domains);
		@unlink('/usr/local/ispconfig/interface/acme/.well-known/acme-challenge/' . $le_rnd_file);

		$le_domain_count = count($temp_domains);
		if($le_domain_count > 100) {
			$temp_domains = array_slice($temp_domains, 0, 100);
			$app->log("There were " . $le_domain_count . " domains in the domain list. LE only supports 100, so we strip the rest.", LOGLEVEL_WARN);
		}

		// generate cli format
		foreach($temp_domains as $temp_domain) {
			$cli_domain_arg .= (string) " --domains " . $temp_domain;
		}

		// unset useless data
		unset($subdomains);
		unset($aliasdomains);
		
		$letsencrypt_cmd = '';
		$success = false;
		if(!empty($cli_domain_arg)) {
			if(!isset($server_config['migration_mode']) || $server_config['migration_mode'] != 'y') {
				$app->log("Create Let's Encrypt SSL Cert for: $domain", LOGLEVEL_DEBUG);
				$app->log("Let's Encrypt SSL Cert domains: $cli_domain_arg", LOGLEVEL_DEBUG);
			
				$letsencrypt = explode("\n", shell_exec('which letsencrypt certbot /root/.local/share/letsencrypt/bin/letsencrypt /opt/eff.org/certbot/venv/bin/certbot'));
				$letsencrypt = reset($letsencrypt);
				if(is_executable($letsencrypt)) {
					$letsencrypt_cmd = $letsencrypt . " certonly -n --text --agree-tos --expand --authenticator webroot --server https://acme-v01.api.letsencrypt.org/directory --rsa-key-size 4096 --email postmaster@$domain $cli_domain_arg --webroot-path /usr/local/ispconfig/interface/acme";
					$success = $app->system->_exec($letsencrypt_cmd);
				}
			} else {
				$app->log("Migration mode active, skipping Let's Encrypt SSL Cert creation for: $domain", LOGLEVEL_DEBUG);
				$success = true;
			}
		}
		
		$le_files = $this->get_letsencrypt_certificate_paths($temp_domains);
		unset($temp_domains);
		
		if($server_type != 'apache' || version_compare($app->system->getapacheversion(true), '2.4.8', '>=')) {
			$crt_tmp_file = $le_files['fullchain'];
		} else {
			$crt_tmp_file = $le_files['cert'];
		}
		
		$key_tmp_file = $le_files['privkey'];
		$bundle_tmp_file = $le_files['chain'];
		
		if(!$success) {
			// error issuing cert
			$app->log('Let\'s Encrypt SSL Cert for: ' . $domain . ' could not be issued.', LOGLEVEL_WARN);
			$app->log($letsencrypt_cmd, LOGLEVEL_WARN);
			
			// if cert already exists, dont remove it. Ex. expired/misstyped/noDnsYet alias domain, api down...
			if(!file_exists($crt_tmp_file)) {
				return false;
			}
		}
			
		//* check is been correctly created
		if(file_exists($crt_tmp_file)) {
			$app->log("Let's Encrypt Cert file: $crt_tmp_file exists.", LOGLEVEL_DEBUG);
			$date = date("YmdHis");
			
			//* TODO: check if is a symlink, if target same keep it, either remove it
			if(is_file($key_file)) {
				$app->system->copy($key_file, $key_file.'.old.'.$date);
				$app->system->chmod($key_file.'.old.'.$date, 0400);
				$app->system->unlink($key_file);
			}

			if ($web_config["website_symlinks_rel"] == 'y') {
				$app->system->create_relative_link(escapeshellcmd($key_tmp_file), escapeshellcmd($key_file));
			} else {
				if(@is_link($key_file)) $app->system->unlink($key_file);
				if(@file_exists($key_tmp_file)) exec("ln -s ".escapeshellcmd($key_tmp_file)." ".escapeshellcmd($key_file));
			}

			if(is_file($crt_file)) {
				$app->system->copy($crt_file, $crt_file.'.old.'.$date);
				$app->system->chmod($crt_file.'.old.'.$date, 0400);
				$app->system->unlink($crt_file);
			}

			if($web_config["website_symlinks_rel"] == 'y') {
				$app->system->create_relative_link(escapeshellcmd($crt_tmp_file), escapeshellcmd($crt_file));
			} else {
				if(@is_link($crt_file)) $app->system->unlink($crt_file);
				if(@file_exists($crt_tmp_file))exec("ln -s ".escapeshellcmd($crt_tmp_file)." ".escapeshellcmd($crt_file));
			}

			if(is_file($bundle_file)) {
				$app->system->copy($bundle_file, $bundle_file.'.old.'.$date);
				$app->system->chmod($bundle_file.'.old.'.$date, 0400);
				$app->system->unlink($bundle_file);
			}

			if($web_config["website_symlinks_rel"] == 'y') {
				$app->system->create_relative_link(escapeshellcmd($bundle_tmp_file), escapeshellcmd($bundle_file));
			} else {
				if(@is_link($bundle_file)) $app->system->unlink($bundle_file);
				if(@file_exists($bundle_tmp_file)) exec("ln -s ".escapeshellcmd($bundle_tmp_file)." ".escapeshellcmd($bundle_file));
			}
			
			return true;
		} else {
			$app->log("Let's Encrypt Cert file: $crt_tmp_file does not exist.", LOGLEVEL_DEBUG);
			return false;
		}
	}
}

?>
