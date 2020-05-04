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
	private $certbot_use_certcommand = false;

	public function __construct(){

	}

	public function get_acme_script() {
		$acme = explode("\n", shell_exec('which /usr/local/ispconfig/server/scripts/acme.sh /root/.acme.sh/acme.sh'));
		$acme = reset($acme);
		if(is_executable($acme)) {
			return $acme;
		} else {
			return false;
		}
	}

	public function get_acme_command($domains, $key_file, $bundle_file, $cert_file, $server_type = 'apache') {
		global $app;

		$letsencrypt = $this->get_acme_script();

		$cmd = '';
		// generate cli format
		foreach($domains as $domain) {
			$cmd .= (string) " -d " . $domain;
		}

		if($cmd == '') {
			return false;
		}

		if($server_type != 'apache' || version_compare($app->system->getapacheversion(true), '2.4.8', '>=')) {
			$cert_arg = '--fullchain-file ' . escapeshellarg($cert_file);
		} else {
			$cert_arg = '--fullchain-file ' . escapeshellarg($bundle_file) . ' --cert-file ' . escapeshellarg($cert_file);
		}

		$cmd = 'R=0 ; C=0 ; ' . $letsencrypt . ' --issue ' . $cmd . ' -w /usr/local/ispconfig/interface/acme ; R=$? ; if [[ $R -eq 0 || $R -eq 2 ]] ; then ' . $letsencrypt . ' --install-cert ' . $cmd . ' --key-file ' . escapeshellarg($key_file) . ' ' . $cert_arg . ' --reloadcmd ' . escapeshellarg($this->get_reload_command()) . '; C=$? ; fi ; if [[ $C -eq 0 ]] ; then exit $R ; else exit $C  ; fi';

		return $cmd;
	}

	public function get_certbot_script() {
		$letsencrypt = explode("\n", shell_exec('which letsencrypt certbot /root/.local/share/letsencrypt/bin/letsencrypt /opt/eff.org/certbot/venv/bin/certbot'));
		$letsencrypt = reset($letsencrypt);
		if(is_executable($letsencrypt)) {
			return $letsencrypt;
		} else {
			return false;
		}
	}

	private function install_acme() {
		$install_cmd = 'wget -O -  https://get.acme.sh | sh';
		$ret = null;
		$val = 0;
		exec($install_cmd . ' 2>&1', $ret, $val);

		return ($val == 0 ? true : false);
	}

	private function get_reload_command() {
		global $app, $conf;

		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		$daemon = '';
		switch ($web_config['server_type']) {
			case 'nginx':
				$daemon = $web_config['server_type'];
				break;
			default:
				if(is_file($conf['init_scripts'] . '/' . 'httpd24-httpd') || is_dir('/opt/rh/httpd24/root/etc/httpd')) {
					$daemon = 'httpd24-httpd';
				} elseif(is_file($conf['init_scripts'] . '/' . 'httpd') || is_dir('/etc/httpd')) {
					$daemon = 'httpd';
				} else {
					$daemon = 'apache2';
				}
		}

		$cmd = $app->system->getinitcommand($daemon, 'force-reload');
		return $cmd;
	}

	public function get_certbot_command($domains) {
		global $app;

		$letsencrypt = $this->get_certbot_script();

		$cmd = '';
		// generate cli format
		foreach($domains as $domain) {
			$cmd .= (string) " --domains " . $domain;
		}

		if($cmd == '') {
			return false;
		}

		$matches = array();
		$ret = null;
		$val = 0;

		$letsencrypt_version = exec($letsencrypt . ' --version  2>&1', $ret, $val);
		if(preg_match('/^(\S+|\w+)\s+(\d+(\.\d+)+)$/', $letsencrypt_version, $matches)) {
			$letsencrypt_version = $matches[2];
		}
		if (version_compare($letsencrypt_version, '0.22', '>=')) {
			$acme_version = 'https://acme-v02.api.letsencrypt.org/directory';
		} else {
			$acme_version = 'https://acme-v01.api.letsencrypt.org/directory';
		}
		if (version_compare($letsencrypt_version, '0.30', '>=')) {
			$app->log("LE version is " . $letsencrypt_version . ", so using certificates command", LOGLEVEL_DEBUG);
			$this->certbot_use_certcommand = true;
			$webroot_map = array();
			for($i = 0; $i < count($domains); $i++) {
				$webroot_map[$domains[$i]] = '/usr/local/ispconfig/interface/acme';
			}
			$webroot_args = "--webroot-map " . escapeshellarg(str_replace(array("\r", "\n"), '', json_encode($webroot_map)));
		} else {
			$webroot_args = "$cmd --webroot-path /usr/local/ispconfig/interface/acme";
		}

		$cmd = $letsencrypt . " certonly -n --text --agree-tos --expand --authenticator webroot --server $acme_version --rsa-key-size 4096 --email postmaster@$domain $cmd --webroot-path /usr/local/ispconfig/interface/acme";

		return $cmd;
	}

	public function get_letsencrypt_certificate_paths($domains = array()) {
		global $app;

		if($this->get_acme_script()) {
			return false;
		}

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
		global $app;

		$domain = $data['new']['ssl_domain'];
		if(!$domain) {
			$domain = $data['new']['domain'];
		}

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

		$use_acme = false;
		if($this->get_acme_script()) {
			$use_acme = true;
		} elseif(!$this->get_certbot_script()) {
			// acme and le missing
			$this->install_acme();
		}

		$tmp = $app->letsencrypt->get_website_certificate_paths($data);
		$domain = $tmp['domain'];
		$key_file = $tmp['key'];
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

		// unset useless data
		unset($subdomains);
		unset($aliasdomains);

		$this->certbot_use_certcommand = false;
		$letsencrypt_cmd = '';
		$allow_return_codes = null;
		if($use_acme) {
			$letsencrypt_cmd = $this->get_acme_command($temp_domains, $key_file, $bundle_file, $crt_file, $server_type);
			$allow_return_codes = array(2);
		} else {
			$letsencrypt_cmd = $this->get_certbot_command($temp_domains);
		}

		$success = false;
		if($letsencrypt_cmd) {
			if(!isset($server_config['migration_mode']) || $server_config['migration_mode'] != 'y') {
				$app->log("Create Let's Encrypt SSL Cert for: $domain", LOGLEVEL_DEBUG);
				$app->log("Let's Encrypt SSL Cert domains: $cli_domain_arg", LOGLEVEL_DEBUG);

				$success = $app->system->_exec($letsencrypt_cmd, $allow_return_codes);
			} else {
				$app->log("Migration mode active, skipping Let's Encrypt SSL Cert creation for: $domain", LOGLEVEL_DEBUG);
				$success = true;
			}
		}

		if($use_acme === true) {
			if(!$success) {
				$app->log('Let\'s Encrypt SSL Cert for: ' . $domain . ' could not be issued.', LOGLEVEL_WARN);
				$app->log($letsencrypt_cmd, LOGLEVEL_WARN);
				return false;
			} else {
				return true;
			}
		}

		$le_files = array();
		if($this->certbot_use_certcommand === true && $letsencrypt_cmd) {
			$cli_domain_arg = '';
			// generate cli format
			foreach($temp_domains as $temp_domain) {
				$cli_domain_arg .= (string) " --domains " . $temp_domain;
			}


			$letsencrypt_cmd = $this->get_certbot_script() . " certificates " . $cli_domain_arg;
			$output = explode("\n", shell_exec($letsencrypt_cmd . " 2>/dev/null | grep -v '^\$'"));
			$le_path = '';
			$skip_to_next = true;
			$matches = null;
			foreach($output as $outline) {
				$outline = trim($outline);
				$app->log("LE CERT OUTPUT: " . $outline, LOGLEVEL_DEBUG);

				if($skip_to_next === true && !preg_match('/^\s*Certificate Name/', $outline)) {
					continue;
				}
				$skip_to_next = false;

				if(preg_match('/^\s*Expiry.*?VALID:\s+\D/', $outline)) {
					$app->log("Found LE path is expired or invalid: " . $matches[1], LOGLEVEL_DEBUG);
					$skip_to_next = true;
					continue;
				}

				if(preg_match('/^\s*Certificate Path:\s*(\/.*?)\s*$/', $outline, $matches)) {
					$app->log("Found LE path: " . $matches[1], LOGLEVEL_DEBUG);
					$le_path = dirname($matches[1]);
					if(is_dir($le_path)) {
						break;
					} else {
						$le_path = false;
					}
				}
			}

			if($le_path) {
				$le_files = array(
					'privkey' => $le_path . '/privkey.pem',
					'chain' => $le_path . '/chain.pem',
					'cert' => $le_path . '/cert.pem',
					'fullchain' => $le_path . '/fullchain.pem'
				);
			}
		}
		if(empty($le_files)) {
			$le_files = $this->get_letsencrypt_certificate_paths($temp_domains);
		}
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

			if(@is_link($key_file)) $app->system->unlink($key_file);
			if(@file_exists($key_tmp_file)) $app->system->exec_safe("ln -s ? ?", $key_tmp_file, $key_file);

			if(is_file($crt_file)) {
				$app->system->copy($crt_file, $crt_file.'.old.'.$date);
				$app->system->chmod($crt_file.'.old.'.$date, 0400);
				$app->system->unlink($crt_file);
			}

			if(@is_link($crt_file)) $app->system->unlink($crt_file);
			if(@file_exists($crt_tmp_file))$app->system->exec_safe("ln -s ? ?", $crt_tmp_file, $crt_file);

			if(is_file($bundle_file)) {
				$app->system->copy($bundle_file, $bundle_file.'.old.'.$date);
				$app->system->chmod($bundle_file.'.old.'.$date, 0400);
				$app->system->unlink($bundle_file);
			}

			if(@is_link($bundle_file)) $app->system->unlink($bundle_file);
			if(@file_exists($bundle_tmp_file)) $app->system->exec_safe("ln -s ? ?", $bundle_tmp_file, $bundle_file);

			return true;
		} else {
			$app->log("Let's Encrypt Cert file: $crt_tmp_file does not exist.", LOGLEVEL_DEBUG);
			return false;
		}
	}
}