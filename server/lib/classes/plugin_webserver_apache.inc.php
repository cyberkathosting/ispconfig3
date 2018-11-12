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

class plugin_webserver_apache {
	private $rewrite_rules = array();
	private $alias_seo_redirects = array();
	
	/**
	 * This method may alter the $tpl template as well as $data and/or $vhost_data array!
	 * 
	 * @param tpl $tpl
	 * @param array $data
	 * @param array $vhost_data
	 */
	public function processRewriteRules(&$tpl, &$data, &$vhost_data) {
		global $app, $conf;
		
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		// Rewrite rules
		$rewrite_rules = array();
		$rewrite_wildcard_rules = array();
		if($data['new']['redirect_type'] != '' && $data['new']['redirect_path'] != '') {
			if(substr($data['new']['redirect_path'], -1) != '/' && !preg_match('/^(https?|\[scheme\]):\/\//', $data['new']['redirect_path'])) $data['new']['redirect_path'] .= '/';
			if(substr($data['new']['redirect_path'], 0, 8) == '[scheme]'){
				$rewrite_target = 'http'.substr($data['new']['redirect_path'], 8);
				$rewrite_target_ssl = 'https'.substr($data['new']['redirect_path'], 8);
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
				$rewrite_rules[] = array( 'rewrite_domain'  => '^'.$this->_rewrite_quote($data['new']['domain']),
					'rewrite_type'   => ($data['new']['redirect_type'] == 'no')?'':'['.$data['new']['redirect_type'].']',
					'rewrite_target'  => $rewrite_target,
					'rewrite_target_ssl' => $rewrite_target_ssl,
					'rewrite_is_url'    => ($this->_is_url($rewrite_target) ? 'y' : 'n'),
					'rewrite_add_path' => (substr($rewrite_target, -1) == '/' ? 'y' : 'n'));
				$rewrite_rules[] = array( 'rewrite_domain'  => '^' . $this->_rewrite_quote('www.'.$data['new']['domain']),
					'rewrite_type'   => ($data['new']['redirect_type'] == 'no')?'':'['.$data['new']['redirect_type'].']',
					'rewrite_target'  => $rewrite_target,
					'rewrite_target_ssl' => $rewrite_target_ssl,
					'rewrite_is_url'    => ($this->_is_url($rewrite_target) ? 'y' : 'n'),
					'rewrite_add_path' => (substr($rewrite_target, -1) == '/' ? 'y' : 'n'));
				break;
			case '*':
				$rewrite_wildcard_rules[] = array( 'rewrite_domain'  => '(^|\.)'.$this->_rewrite_quote($data['new']['domain']),
					'rewrite_type'   => ($data['new']['redirect_type'] == 'no')?'':'['.$data['new']['redirect_type'].']',
					'rewrite_target'  => $rewrite_target,
					'rewrite_target_ssl' => $rewrite_target_ssl,
					'rewrite_is_url'    => ($this->_is_url($rewrite_target) ? 'y' : 'n'),
					'rewrite_add_path' => (substr($rewrite_target, -1) == '/' ? 'y' : 'n'));
				break;
			default:
				$rewrite_rules[] = array( 'rewrite_domain'  => '^'.$this->_rewrite_quote($data['new']['domain']),
					'rewrite_type'   => ($data['new']['redirect_type'] == 'no')?'':'['.$data['new']['redirect_type'].']',
					'rewrite_target'  => $rewrite_target,
					'rewrite_target_ssl' => $rewrite_target_ssl,
					'rewrite_is_url'    => ($this->_is_url($rewrite_target) ? 'y' : 'n'),
					'rewrite_add_path' => (substr($rewrite_target, -1) == '/' ? 'y' : 'n'));
			}
		}
		
		$server_alias = array();
		$client = $app->dbmaster->queryOneRecord('SELECT client_id FROM sys_group WHERE sys_group.groupid = ?', $data['new']['sys_groupid']);
		$client_id = intval($client['client_id']);
		unset($client);

		// get autoalias
		$auto_alias = $web_config['website_autoalias'];
		if($auto_alias != '') {
			// get the client username
			$client = $app->db->queryOneRecord("SELECT `username` FROM `client` WHERE `client_id` = ?", $client_id);
			$aa_search = array('[client_id]', '[website_id]', '[client_username]', '[website_domain]');
			$aa_replace = array($client_id, $data['new']['domain_id'], $client['username'], $data['new']['domain']);
			$auto_alias = str_replace($aa_search, $aa_replace, $auto_alias);
			unset($client);
			unset($aa_search);
			unset($aa_replace);
			$server_alias[] .= $auto_alias;
		}

		// get alias domains (co-domains and subdomains)
		$aliases = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE parent_domain_id = ? AND active = 'y' AND (type != 'vhostsubdomain' AND type != 'vhostalias')", $data['new']['domain_id']);
		$alias_seo_redirects = array();
		switch($data['new']['subdomain']) {
		case 'www':
			$server_alias[] = 'www.'.$data['new']['domain'];
			break;
		case '*':
			$server_alias[] = '*.'.$data['new']['domain'];
			break;
		}
		if(is_array($aliases)) {
			foreach($aliases as $alias) {
				switch($alias['subdomain']) {
				case 'www':
					$server_alias[] .= 'www.'.$alias['domain'].' '.$alias['domain'];
					break;
				case '*':
					$server_alias[] .= '*.'.$alias['domain'].' '.$alias['domain'];
					break;
				default:
					$server_alias[] .= $alias['domain'];
					break;
				}
				$app->log('Add server alias: '.$alias['domain'], LOGLEVEL_DEBUG);

				// Add SEO redirects for alias domains
				if($alias['seo_redirect'] != '' && $data['new']['seo_redirect'] != '*_to_www_domain_tld' && $data['new']['seo_redirect'] != '*_to_domain_tld' && ($alias['type'] == 'alias' || ($alias['type'] == 'subdomain' && $data['new']['seo_redirect'] != '*_domain_tld_to_www_domain_tld' && $data['new']['seo_redirect'] != '*_domain_tld_to_domain_tld'))){
					$tmp_seo_redirects = $this->get_seo_redirects($alias, 'alias_', false, 'apache');
					if(is_array($tmp_seo_redirects) && !empty($tmp_seo_redirects)){
						$alias_seo_redirects[] = $tmp_seo_redirects;
					}
				}

				// Rewriting
				if($alias['redirect_type'] != '' && $alias['redirect_path'] != '') {
					if(substr($alias['redirect_path'], -1) != '/' && !preg_match('/^(https?|\[scheme\]):\/\//', $alias['redirect_path'])) $alias['redirect_path'] .= '/';
					if(substr($alias['redirect_path'], 0, 8) == '[scheme]'){
						$rewrite_target = 'http'.substr($alias['redirect_path'], 8);
						$rewrite_target_ssl = 'https'.substr($alias['redirect_path'], 8);
					} else {
						$rewrite_target = $alias['redirect_path'];
						$rewrite_target_ssl = $alias['redirect_path'];
					}

					switch($alias['subdomain']) {
					case 'www':
						$rewrite_rules[] = array( 'rewrite_domain'  => '^'.$this->_rewrite_quote($alias['domain']),
							'rewrite_type'   => ($alias['redirect_type'] == 'no')?'':'['.$alias['redirect_type'].']',
							'rewrite_target'  => $rewrite_target,
							'rewrite_target_ssl' => $rewrite_target_ssl,
							'rewrite_is_url'    => ($this->_is_url($rewrite_target) ? 'y' : 'n'),
							'rewrite_add_path' => (substr($rewrite_target, -1) == '/' ? 'y' : 'n'));
						$rewrite_rules[] = array( 'rewrite_domain'  => '^' . $this->_rewrite_quote('www.'.$alias['domain']),
							'rewrite_type'   => ($alias['redirect_type'] == 'no')?'':'['.$alias['redirect_type'].']',
							'rewrite_target'  => $rewrite_target,
							'rewrite_target_ssl' => $rewrite_target_ssl,
							'rewrite_is_url'    => ($this->_is_url($rewrite_target) ? 'y' : 'n'),
							'rewrite_add_path' => (substr($rewrite_target, -1) == '/' ? 'y' : 'n'));
						break;
					case '*':
						$rewrite_wildcard_rules[] = array( 'rewrite_domain'  => '(^|\.)'.$this->_rewrite_quote($alias['domain']),
							'rewrite_type'   => ($alias['redirect_type'] == 'no')?'':'['.$alias['redirect_type'].']',
							'rewrite_target'  => $rewrite_target,
							'rewrite_target_ssl' => $rewrite_target_ssl,
							'rewrite_is_url'    => ($this->_is_url($rewrite_target) ? 'y' : 'n'),
							'rewrite_add_path' => (substr($rewrite_target, -1) == '/' ? 'y' : 'n'));
						break;
					default:
						if(substr($alias['domain'], 0, 2) === '*.') $domain_rule = '(^|\.)'.$this->_rewrite_quote(substr($alias['domain'], 2));
						else $domain_rule = '^'.$this->_rewrite_quote($alias['domain']);
						$rewrite_rules[] = array( 'rewrite_domain'  => $domain_rule,
							'rewrite_type'   => ($alias['redirect_type'] == 'no')?'':'['.$alias['redirect_type'].']',
							'rewrite_target'  => $rewrite_target,
							'rewrite_target_ssl' => $rewrite_target_ssl,
							'rewrite_is_url'    => ($this->_is_url($rewrite_target) ? 'y' : 'n'),
							'rewrite_add_path' => (substr($rewrite_target, -1) == '/' ? 'y' : 'n'));
					}
				}
			}
		}

		//* If we have some alias records
		if($server_alias) {
			//* begin a new ServerAlias line after 32 alias domains to avoid apache bugs
			$server_alias_str = 'ServerAlias '.$server_alias[0];
			for($n=1;$n<count($server_alias);++$n)
				$server_alias_str .= ($n % 32?' ':"\nServerAlias ").$server_alias[$n];
			$tpl->setVar('alias', $server_alias_str);
			unset($server_alias_str);
			unset($n);
		} else {
			$tpl->setVar('alias', '');
		}

		if (count($rewrite_wildcard_rules) > 0) $rewrite_rules = array_merge($rewrite_rules, $rewrite_wildcard_rules); // Append wildcard rules to the end of rules

		if(count($rewrite_rules) > 0 || $vhost_data['seo_redirect_enabled'] > 0 || count($alias_seo_redirects) > 0 || $data['new']['rewrite_to_https'] == 'y') {
			$tpl->setVar('rewrite_enabled', 1);
		} else {
			$tpl->setVar('rewrite_enabled', 0);
		}

		//$tpl->setLoop('redirects',$rewrite_rules);
		$this->rewrite_rules = $rewrite_rules;
		$this->alias_seo_redirects = $alias_seo_redirects;
		
		return;
	}

	/**
	 * This method may alter the $tpl template as well as $data and/or $vhost_data array!
	 * 
	 * @param tpl $tpl
	 * @param array $data
	 * @param array $vhost_data
	 */
	public function processCustomDirectives(&$tpl, &$data, &$vhost_data) {
		global $app;
		
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
		
		return;
	}
	
	/**
	 * This method may alter the $tpl template as well as $data and/or $vhost_data array!
	 * 
	 * @param tpl $tpl
	 * @param array $data
	 * @param array $vhost_data
	 */
	public function processPhpStarters(&$tpl, &$data, &$vhost_data) {
		global $app, $conf;
		
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		$fastcgi_config = $app->getconf->get_server_config($conf['server_id'], 'fastcgi');
		
		/**
		 * install fast-cgi starter script and add script aliasd config
		 * first we create the script directory if not already created, then copy over the starter script
		 * settings are copied over from the server ini config for now
		 * TODO: Create form for fastcgi configs per site.
		 */

		$client = $app->dbmaster->queryOneRecord('SELECT client_id FROM sys_group WHERE sys_group.groupid = ?', $data['new']['sys_groupid']);
		$client_id = intval($client['client_id']);
		unset($client);

		if ($data['new']['php'] == 'fast-cgi') {

			$fastcgi_starter_path = str_replace('[system_user]', $data['new']['system_user'], $fastcgi_config['fastcgi_starter_path']);
			$fastcgi_starter_path = str_replace('[client_id]', $client_id, $fastcgi_starter_path);

			if (!is_dir($fastcgi_starter_path)) {
				$app->system->mkdirpath($fastcgi_starter_path);
				$app->log('Creating fastcgi starter script directory: '.$fastcgi_starter_path, LOGLEVEL_DEBUG);
			}

			$app->system->chown($fastcgi_starter_path, $data['new']['system_user']);
			$app->system->chgrp($fastcgi_starter_path, $data['new']['system_group']);
			if($web_config['security_level'] == 10) {
				$app->system->chmod($fastcgi_starter_path, 0755);
			} else {
				$app->system->chmod($fastcgi_starter_path, 0550);
			}

			$fcgi_tpl = new tpl();
			$fcgi_tpl->newTemplate('php-fcgi-starter.master');
			$fcgi_tpl->setVar('apache_version', $app->system->getapacheversion());
			$fcgi_tpl->setVar('apache_full_version', $app->system->getapacheversion(true));

			if(trim($data['new']['fastcgi_php_version']) != ''){
				// $custom_fastcgi_php_name
				list(, $custom_fastcgi_php_executable, $custom_fastcgi_php_ini_dir) = explode(':', trim($data['new']['fastcgi_php_version']));
				if(is_file($custom_fastcgi_php_ini_dir)) $custom_fastcgi_php_ini_dir = dirname($custom_fastcgi_php_ini_dir);
				if(substr($custom_fastcgi_php_ini_dir, -1) == '/') $custom_fastcgi_php_ini_dir = substr($custom_fastcgi_php_ini_dir, 0, -1);
			}
			
			if(trim($data['new']['custom_php_ini']) != '') {
				$has_custom_php_ini = true;
			} else {
				$has_custom_php_ini = false;
			}
			
			$web_folder = $app->plugin_webserver_base->getWebFolder($data, 'web', false);
			
			$custom_php_ini_dir = $web_config['website_basedir'].'/conf/'.$data['new']['system_user'];
			if($data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias') $custom_php_ini_dir .= '_' . $web_folder;
			if(!is_dir($web_config['website_basedir'].'/conf')) $app->system->mkdir($web_config['website_basedir'].'/conf');
			
			// Support for multiple PHP versions (FastCGI)
			if(trim($data['new']['fastcgi_php_version']) != ''){
				$default_fastcgi_php = false;
				if(substr($custom_fastcgi_php_ini_dir, -1) != '/') $custom_fastcgi_php_ini_dir .= '/';
			} else {
				$default_fastcgi_php = true;
			}

			if($has_custom_php_ini) {
				$fcgi_tpl->setVar('php_ini_path', escapeshellcmd($custom_php_ini_dir));
			} else {
				if($default_fastcgi_php){
					$fcgi_tpl->setVar('php_ini_path', escapeshellcmd($fastcgi_config['fastcgi_phpini_path']));
				} else {
					$fcgi_tpl->setVar('php_ini_path', escapeshellcmd($custom_fastcgi_php_ini_dir));
				}
			}
			$fcgi_tpl->setVar('document_root', escapeshellcmd($data['new']['document_root']));
			$fcgi_tpl->setVar('php_fcgi_children', escapeshellcmd($fastcgi_config['fastcgi_children']));
			$fcgi_tpl->setVar('php_fcgi_max_requests', escapeshellcmd($fastcgi_config['fastcgi_max_requests']));
			if($default_fastcgi_php){
				$fcgi_tpl->setVar('php_fcgi_bin', escapeshellcmd($fastcgi_config['fastcgi_bin']));
			} else {
				$fcgi_tpl->setVar('php_fcgi_bin', escapeshellcmd($custom_fastcgi_php_executable));
			}
			$fcgi_tpl->setVar('security_level', intval($web_config['security_level']));
			$fcgi_tpl->setVar('domain', escapeshellcmd($data['new']['domain']));

			$php_open_basedir = ($data['new']['php_open_basedir'] == '')?$data['new']['document_root']:$data['new']['php_open_basedir'];
			$fcgi_tpl->setVar('open_basedir', escapeshellcmd($php_open_basedir));

			$fcgi_starter_script = escapeshellcmd($fastcgi_starter_path.$fastcgi_config['fastcgi_starter_script'].(($data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias') ? '_web' . $data['new']['domain_id'] : ''));
			$app->system->file_put_contents($fcgi_starter_script, $fcgi_tpl->grab());
			unset($fcgi_tpl);

			$app->log('Creating fastcgi starter script: '.$fcgi_starter_script, LOGLEVEL_DEBUG);

			if($web_config['security_level'] == 10) {
				$app->system->chmod($fcgi_starter_script, 0755);
			} else {
				$app->system->chmod($fcgi_starter_script, 0550);
			}
			$app->system->chown($fcgi_starter_script, $data['new']['system_user']);
			$app->system->chgrp($fcgi_starter_script, $data['new']['system_group']);

			$tpl->setVar('fastcgi_alias', $fastcgi_config['fastcgi_alias']);
			$tpl->setVar('fastcgi_starter_path', $fastcgi_starter_path);
			$tpl->setVar('fastcgi_starter_script', $fastcgi_config['fastcgi_starter_script'].(($data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias') ? '_web' . $data['new']['domain_id'] : ''));
			$tpl->setVar('fastcgi_config_syntax', $fastcgi_config['fastcgi_config_syntax']);
			$tpl->setVar('fastcgi_max_requests', $fastcgi_config['fastcgi_max_requests']);

		} else {
			//remove the php fastgi starter script if available
			$fastcgi_starter_script = $fastcgi_config['fastcgi_starter_script'].($data['old']['type'] == 'vhostsubdomain' ? '_web' . $data['old']['domain_id'] : '');
			if ($data['old']['php'] == 'fast-cgi') {
				$fastcgi_starter_path = str_replace('[system_user]', $data['old']['system_user'], $fastcgi_config['fastcgi_starter_path']);
				$fastcgi_starter_path = str_replace('[client_id]', $client_id, $fastcgi_starter_path);
				if($data['old']['type'] == 'vhost') {
					if(is_file($fastcgi_starter_script)) @unlink($fastcgi_starter_script);
					if (is_dir($fastcgi_starter_path)) @rmdir($fastcgi_starter_path);
				} else {
					if(is_file($fastcgi_starter_script)) @unlink($fastcgi_starter_script);
				}
			}
		}

		return;
	}
	
	/**
	 * This method may alter the $tpl template as well as $data and/or $vhost_data array!
	 * 
	 * @param tpl $tpl
	 * @param array $data
	 * @param array $vhost_data
	 */
	public function processVhosts(&$tpl, &$data, &$vhost_data, $ssl_data) {
		global $app, $conf;
		
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		
		//* create empty vhost array
		$vhosts = array();

		//* Add vhost for ipv4 IP

		//* use ip-mapping for web-mirror
		if($data['new']['ip_address'] != '*' && $conf['mirror_server_id'] > 0) {
			$sql = "SELECT destination_ip FROM server_ip_map WHERE server_id = ? AND source_ip = ?";
			$newip = $app->db->queryOneRecord($sql, $conf['server_id'], $data['new']['ip_address']);
			$data['new']['ip_address'] = $newip['destination_ip'];
			unset($newip);
		}

		$tmp_vhost_arr = array('ip_address' => $data['new']['ip_address'], 'ssl_enabled' => 0, 'port' => 80);
		if(count($this->rewrite_rules) > 0)  $tmp_vhost_arr = $tmp_vhost_arr + array('redirects' => $this->rewrite_rules);
		if(count($this->alias_seo_redirects) > 0) $tmp_vhost_arr = $tmp_vhost_arr + array('alias_seo_redirects' => $this->alias_seo_redirects);
		$vhosts[] = $tmp_vhost_arr;
		unset($tmp_vhost_arr);

		//* Add vhost for ipv4 IP with SSL
		if($data['new']['ssl_domain'] != '' && $data['new']['ssl'] == 'y' && @is_file($ssl_data['crt_file']) && @is_file($ssl_data['key_file']) && (@filesize($ssl_data['crt_file'])>0)  && (@filesize($ssl_data['key_file'])>0)) {
			$tmp_vhost_arr = array('ip_address' => $data['new']['ip_address'], 'ssl_enabled' => 1, 'port' => '443');
			if(count($this->rewrite_rules) > 0)  $tmp_vhost_arr = $tmp_vhost_arr + array('redirects' => $this->rewrite_rules);
			$ipv4_ssl_alias_seo_redirects = $this->alias_seo_redirects;
			if(is_array($ipv4_ssl_alias_seo_redirects) && !empty($ipv4_ssl_alias_seo_redirects)){
				for($i=0;$i<count($ipv4_ssl_alias_seo_redirects);$i++){
					$ipv4_ssl_alias_seo_redirects[$i]['ssl_enabled'] = 1;
				}
			}
			if(count($ipv4_ssl_alias_seo_redirects) > 0) $tmp_vhost_arr = $tmp_vhost_arr + array('alias_seo_redirects' => $ipv4_ssl_alias_seo_redirects);
			$vhosts[] = $tmp_vhost_arr;
			unset($tmp_vhost_arr, $ipv4_ssl_alias_seo_redirects);
		}

		//* Add vhost for IPv6 IP
		if($data['new']['ipv6_address'] != '') {
			//* rewrite ipv6 on mirrors
			/* chang $conf to $web_config */
			if ($web_config['serverconfig']['web']['vhost_rewrite_v6'] == 'y') {
				if (isset($web_config['serverconfig']['server']['v6_prefix']) && $web_config['serverconfig']['server']['v6_prefix'] <> '') {
					$explode_v6prefix=explode(':', $web_config['serverconfig']['server']['v6_prefix']);
					$explode_v6=explode(':', $data['new']['ipv6_address']);

					for ( $i = 0; $i <= count($explode_v6prefix)-1; $i++ ) {
						$explode_v6[$i] = $explode_v6prefix[$i];
					}
					$data['new']['ipv6_address'] = implode(':', $explode_v6);
				}
			}
			if($data['new']['ipv6_address'] == '*') $data['new']['ipv6_address'] = '::';
			$tmp_vhost_arr = array('ip_address' => '['.$data['new']['ipv6_address'].']', 'ssl_enabled' => 0, 'port' => 80);
			if(count($this->rewrite_rules) > 0)  $tmp_vhost_arr = $tmp_vhost_arr + array('redirects' => $this->rewrite_rules);
			if(count($this->alias_seo_redirects) > 0) $tmp_vhost_arr = $tmp_vhost_arr + array('alias_seo_redirects' => $this->alias_seo_redirects);
			$vhosts[] = $tmp_vhost_arr;
			unset($tmp_vhost_arr);

			//* Add vhost for ipv6 IP with SSL
			if($data['new']['ssl_domain'] != '' && $data['new']['ssl'] == 'y' && @is_file($ssl_data['crt_file']) && @is_file($ssl_data['key_file']) && (@filesize($ssl_data['crt_file'])>0)  && (@filesize($ssl_data['key_file'])>0)) {
				$tmp_vhost_arr = array('ip_address' => '['.$data['new']['ipv6_address'].']', 'ssl_enabled' => 1, 'port' => '443');
				if(count($this->rewrite_rules) > 0)  $tmp_vhost_arr = $tmp_vhost_arr + array('redirects' => $this->rewrite_rules);
				$ipv6_ssl_alias_seo_redirects = $this->alias_seo_redirects;
				if(is_array($ipv6_ssl_alias_seo_redirects) && !empty($ipv6_ssl_alias_seo_redirects)){
					for($i=0;$i<count($ipv6_ssl_alias_seo_redirects);$i++){
						$ipv6_ssl_alias_seo_redirects[$i]['ssl_enabled'] = 1;
					}
				}
				if(count($ipv6_ssl_alias_seo_redirects) > 0) $tmp_vhost_arr = $tmp_vhost_arr + array('alias_seo_redirects' => $ipv6_ssl_alias_seo_redirects);
				$vhosts[] = $tmp_vhost_arr;
				unset($tmp_vhost_arr, $ipv6_ssl_alias_seo_redirects);
			}
		}

		//* Set the vhost loop
		$tpl->setLoop('vhosts', $vhosts);
		return;
	}
	
	public function testWebserverConfig() {
		global $app;
		// if no output is given, check again
		$webserver_binary = '';
		$webserver_check_output = null;
		$webserver_check_retval = 0;
		exec('which apache2ctl apache2 httpd2 httpd apache 2>/dev/null', $webserver_check_output, $webserver_check_retval);
		if($webserver_check_retval == 0){
			$webserver_binary = reset($webserver_check_output);
		}
		if($webserver_binary != ''){
			$tmp_output = null;
			$tmp_retval = 0;
			exec($webserver_binary.' -t 2>&1', $tmp_output, $tmp_retval);
			if($tmp_retval > 0 && is_array($tmp_output) && !empty($tmp_output)){
				$app->log('Reason for Apache restart failure: '.implode("\n", $tmp_output), LOGLEVEL_WARN);
				$app->dbmaster->datalogError(implode("\n", $tmp_output));
			}
			unset($tmp_output, $tmp_retval);
		}
	}
}
