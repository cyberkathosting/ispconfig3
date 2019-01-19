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

class plugin_webserver_nginx {
	
	/**
	 * This method may alter the $tpl template as well as $data and/or $vhost_data array!
	 * 
	 * @param tpl $tpl
	 * @param array $data
	 * @param array $vhost_data
	 */
	public function processPhpFpm(&$tpl, &$data, &$vhost_data) {
		global $app, $conf;
		
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		
		// PHP-FPM
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
			if(trim($data['old']['fastcgi_php_version']) != '' && $data['old']['php'] != 'no'){
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

        if($data['new']['php_fpm_chroot'] == 'y'){
            $php_fpm_chroot = 1;
            $php_fpm_nochroot = 0;
        } else {
            $php_fpm_chroot = 0;
            $php_fpm_nochroot = 1;
        }
		if($data['new']['php_fpm_use_socket'] == 'y'){
			$use_tcp = 0;
			$use_socket = 1;
		} else {
			$use_tcp = 1;
			$use_socket = 0;
		}
		$tpl->setVar('use_tcp', $use_tcp);
		$tpl->setVar('use_socket', $use_socket);
		$tpl->setVar('php_fpm_chroot', $php_fpm_chroot);
		$tpl->setVar('php_fpm_nochroot', $php_fpm_nochroot);
		$fpm_socket = $socket_dir.$pool_name.'.sock';
		$tpl->setVar('fpm_socket', $fpm_socket);
		$tpl->setVar('rnd_php_dummy_file', '/'.md5(uniqid(microtime(), 1)).'.htm');
		$vhost_data['fpm_port'] = $web_config['php_fpm_start_port'] + $data['new']['domain_id'] - 1;

		// backwards compatibility; since ISPConfig 3.0.5, the PHP mode for nginx is called 'php-fpm' instead of 'fast-cgi'. The following line makes sure that old web sites that have 'fast-cgi' in the database still get PHP-FPM support.
		if($vhost_data['php'] == 'fast-cgi') $vhost_data['php'] = 'php-fpm';
		
		return;
	}

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

		// Custom rewrite rules
		$final_rewrite_rules = array();

		if(isset($data['new']['rewrite_rules']) && trim($data['new']['rewrite_rules']) != '') {
			$custom_rewrite_rules = trim($data['new']['rewrite_rules']);
			$custom_rewrites_are_valid = true;
			// use this counter to make sure all curly brackets are properly closed
			$if_level = 0;
			// Make sure we only have Unix linebreaks
			$custom_rewrite_rules = str_replace("\r\n", "\n", $custom_rewrite_rules);
			$custom_rewrite_rules = str_replace("\r", "\n", $custom_rewrite_rules);
			$custom_rewrite_rule_lines = explode("\n", $custom_rewrite_rules);
			if(is_array($custom_rewrite_rule_lines) && !empty($custom_rewrite_rule_lines)){
				foreach($custom_rewrite_rule_lines as $custom_rewrite_rule_line){
					// ignore comments
					if(substr(ltrim($custom_rewrite_rule_line), 0, 1) == '#'){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					// empty lines
					if(trim($custom_rewrite_rule_line) == ''){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					// rewrite
					if(preg_match('@^\s*rewrite\s+(^/)?\S+(\$)?\s+\S+(\s+(last|break|redirect|permanent|))?\s*;\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					if(preg_match('@^\s*rewrite\s+(^/)?(\'[^\']+\'|"[^"]+")+(\$)?\s+(\'[^\']+\'|"[^"]+")+(\s+(last|break|redirect|permanent|))?\s*;\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					if(preg_match('@^\s*rewrite\s+(^/)?(\'[^\']+\'|"[^"]+")+(\$)?\s+\S+(\s+(last|break|redirect|permanent|))?\s*;\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					if(preg_match('@^\s*rewrite\s+(^/)?\S+(\$)?\s+(\'[^\']+\'|"[^"]+")+(\s+(last|break|redirect|permanent|))?\s*;\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					// if
					if(preg_match('@^\s*if\s+\(\s*\$\S+(\s+(\!?(=|~|~\*))\s+(\S+|\".+\"))?\s*\)\s*\{\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						$if_level += 1;
						continue;
					}
					// if - check for files, directories, etc.
					if(preg_match('@^\s*if\s+\(\s*\!?-(f|d|e|x)\s+\S+\s*\)\s*\{\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						$if_level += 1;
						continue;
					}
					// break
					if(preg_match('@^\s*break\s*;\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					// return code [ text ]
					if(preg_match('@^\s*return\s+\d\d\d.*;\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					// return code URL
					// return URL
					if(preg_match('@^\s*return(\s+\d\d\d)?\s+(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&%\$\-]+)*\@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|localhost|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&%\$#\=~_\-]+))*\s*;\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					// set
					if(preg_match('@^\s*set\s+\$\S+\s+\S+\s*;\s*$@', $custom_rewrite_rule_line)){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						continue;
					}
					// closing curly bracket
					if(trim($custom_rewrite_rule_line) == '}'){
						$final_rewrite_rules[] = array('rewrite_rule' => $custom_rewrite_rule_line);
						$if_level -= 1;
						continue;
					}
					$custom_rewrites_are_valid = false;
					break;
				}
			}
			if(!$custom_rewrites_are_valid || $if_level != 0){
				$final_rewrite_rules = array();
			}
		}
		$tpl->setLoop('rewrite_rules', $final_rewrite_rules);
		
		// Rewrite rules
		$own_rewrite_rules = array();
		$rewrite_rules = array();
		$local_rewrite_rules = array();
		if($data['new']['redirect_type'] != '' && $data['new']['redirect_path'] != '') {
			if(substr($data['new']['redirect_path'], -1) != '/') $data['new']['redirect_path'] .= '/';
			if(substr($data['new']['redirect_path'], 0, 8) == '[scheme]'){
				if($data['new']['redirect_type'] != 'proxy'){
					$data['new']['redirect_path'] = '$scheme'.substr($data['new']['redirect_path'], 8);
				} else {
					$data['new']['redirect_path'] = 'http'.substr($data['new']['redirect_path'], 8);
				}
			}

			// Custom proxy directives
			if($data['new']['redirect_type'] == 'proxy' && trim($data['new']['proxy_directives'] != '')){
				$final_proxy_directives = array();
				$proxy_directives = $data['new']['proxy_directives'];
				// Make sure we only have Unix linebreaks
				$proxy_directives = str_replace("\r\n", "\n", $proxy_directives);
				$proxy_directives = str_replace("\r", "\n", $proxy_directives);
				$proxy_directive_lines = explode("\n", $proxy_directives);
				if(is_array($proxy_directive_lines) && !empty($proxy_directive_lines)){
					foreach($proxy_directive_lines as $proxy_directive_line){
						$final_proxy_directives[] = array('proxy_directive' => $proxy_directive_line);
					}
				}
			} else {
				$final_proxy_directives = false;
			}

			switch($data['new']['subdomain']) {
			case 'www':
				$exclude_own_hostname = '';
				if(substr($data['new']['redirect_path'], 0, 1) == '/'){ // relative path
					if($data['new']['redirect_type'] == 'proxy'){
						$vhost_data['web_document_root_www_proxy'] = 'root '.$vhost_data['web_document_root_www'].';';
						$vhost_data['web_document_root_www'] .= substr($data['new']['redirect_path'], 0, -1);
						break;
					}
					$rewrite_exclude = '(?!/('.substr($data['new']['redirect_path'], 1, -1).(substr($data['new']['redirect_path'], 1, -1) != ''? '|': '').'stats'.($vhost_data['errordocs'] == 1 ? '|error' : '').'|\.well-known/acme-challenge))/';
				} else { // URL - check if URL is local
					$tmp_redirect_path = $data['new']['redirect_path'];
					if(substr($tmp_redirect_path, 0, 7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path, 7);
					$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
					if(($tmp_redirect_path_parts['host'] == $data['new']['domain'] || $tmp_redirect_path_parts['host'] == 'www.'.$data['new']['domain']) && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
						// URL is local
						if(substr($tmp_redirect_path_parts['path'], -1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'], 0, -1);
						if(substr($tmp_redirect_path_parts['path'], 0, 1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
						//$rewrite_exclude = '((?!'.$tmp_redirect_path_parts['path'].'))';
						if($data['new']['redirect_type'] == 'proxy'){
							$vhost_data['web_document_root_www_proxy'] = 'root '.$vhost_data['web_document_root_www'].';';
							$vhost_data['web_document_root_www'] .= $tmp_redirect_path_parts['path'];
							break;
						} else {
							$rewrite_exclude = '(?!/('.substr($tmp_redirect_path_parts['path'], 1).(substr($tmp_redirect_path_parts['path'], 1) != ''? '|': '').'stats'.($vhost_data['errordocs'] == 1 ? '|error' : '').'|\.well-known/acme-challenge))/';
							$exclude_own_hostname = $tmp_redirect_path_parts['host'];
						}
					} else {
						// external URL
						$rewrite_exclude = '(.?)/';
						if($data['new']['redirect_type'] == 'proxy'){
							$vhost_data['use_proxy'] = 'y';
							$rewrite_subdir = $tmp_redirect_path_parts['path'];
							if(substr($rewrite_subdir, 0, 1) == '/') $rewrite_subdir = substr($rewrite_subdir, 1);
							if(substr($rewrite_subdir, -1) != '/') $rewrite_subdir .= '/';
							if($rewrite_subdir == '/') $rewrite_subdir = '';
						}
					}
					unset($tmp_redirect_path);
					unset($tmp_redirect_path_parts);
				}
				$own_rewrite_rules[] = array( 'rewrite_domain'  => '^'.$this->_rewrite_quote($data['new']['domain']),
					'rewrite_type'   => ($data['new']['redirect_type'] == 'no')?'':$data['new']['redirect_type'],
					'rewrite_target'  => $data['new']['redirect_path'],
					'rewrite_exclude' => $rewrite_exclude,
					'rewrite_subdir' => $rewrite_subdir,
					'exclude_own_hostname' => $exclude_own_hostname,
					'proxy_directives' => $final_proxy_directives,
					'use_rewrite' => ($data['new']['redirect_type'] == 'proxy' ? false:true),
					'use_proxy' => ($data['new']['redirect_type'] == 'proxy' ? true:false));
				break;
			case '*':
				$exclude_own_hostname = '';
				if(substr($data['new']['redirect_path'], 0, 1) == '/'){ // relative path
					if($data['new']['redirect_type'] == 'proxy'){
						$vhost_data['web_document_root_www_proxy'] = 'root '.$vhost_data['web_document_root_www'].';';
						$vhost_data['web_document_root_www'] .= substr($data['new']['redirect_path'], 0, -1);
						break;
					}
					$rewrite_exclude = '(?!/('.substr($data['new']['redirect_path'], 1, -1).(substr($data['new']['redirect_path'], 1, -1) != ''? '|': '').'stats'.($vhost_data['errordocs'] == 1 ? '|error' : '').'|\.well-known/acme-challenge))/';
				} else { // URL - check if URL is local
					$tmp_redirect_path = $data['new']['redirect_path'];
					if(substr($tmp_redirect_path, 0, 7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path, 7);
					$tmp_redirect_path_parts = parse_url($tmp_redirect_path);

					//if($is_serveralias && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
					if($this->url_is_local($tmp_redirect_path_parts['host'], $data['new']['domain_id']) && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
						// URL is local
						if(substr($tmp_redirect_path_parts['path'], -1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'], 0, -1);
						if(substr($tmp_redirect_path_parts['path'], 0, 1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
						//$rewrite_exclude = '((?!'.$tmp_redirect_path_parts['path'].'))';
						if($data['new']['redirect_type'] == 'proxy'){
							$vhost_data['web_document_root_www_proxy'] = 'root '.$vhost_data['web_document_root_www'].';';
							$vhost_data['web_document_root_www'] .= $tmp_redirect_path_parts['path'];
							break;
						} else {
							$rewrite_exclude = '(?!/('.substr($tmp_redirect_path_parts['path'], 1).(substr($tmp_redirect_path_parts['path'], 1) != ''? '|': '').'stats'.($vhost_data['errordocs'] == 1 ? '|error' : '').'|\.well-known/acme-challenge))/';
							$exclude_own_hostname = $tmp_redirect_path_parts['host'];
						}
					} else {
						// external URL
						$rewrite_exclude = '(.?)/';
						if($data['new']['redirect_type'] == 'proxy'){
							$vhost_data['use_proxy'] = 'y';
							$rewrite_subdir = $tmp_redirect_path_parts['path'];
							if(substr($rewrite_subdir, 0, 1) == '/') $rewrite_subdir = substr($rewrite_subdir, 1);
							if(substr($rewrite_subdir, -1) != '/') $rewrite_subdir .= '/';
							if($rewrite_subdir == '/') $rewrite_subdir = '';
						}
					}
					unset($tmp_redirect_path);
					unset($tmp_redirect_path_parts);
				}
				$own_rewrite_rules[] = array( 'rewrite_domain'  => '(^|\.)'.$this->_rewrite_quote($data['new']['domain']),
					'rewrite_type'   => ($data['new']['redirect_type'] == 'no')?'':$data['new']['redirect_type'],
					'rewrite_target'  => $data['new']['redirect_path'],
					'rewrite_exclude' => $rewrite_exclude,
					'rewrite_subdir' => $rewrite_subdir,
					'exclude_own_hostname' => $exclude_own_hostname,
					'proxy_directives' => $final_proxy_directives,
					'use_rewrite' => ($data['new']['redirect_type'] == 'proxy' ? false:true),
					'use_proxy' => ($data['new']['redirect_type'] == 'proxy' ? true:false));
				break;
			default:
				if(substr($data['new']['redirect_path'], 0, 1) == '/'){ // relative path
					$exclude_own_hostname = '';
					if($data['new']['redirect_type'] == 'proxy'){
						$vhost_data['web_document_root_www_proxy'] = 'root '.$vhost_data['web_document_root_www'].';';
						$vhost_data['web_document_root_www'] .= substr($data['new']['redirect_path'], 0, -1);
						break;
					}
					$rewrite_exclude = '(?!/('.substr($data['new']['redirect_path'], 1, -1).(substr($data['new']['redirect_path'], 1, -1) != ''? '|': '').'stats'.($vhost_data['errordocs'] == 1 ? '|error' : '').'|\.well-known/acme-challenge))/';
				} else { // URL - check if URL is local
					$tmp_redirect_path = $data['new']['redirect_path'];
					if(substr($tmp_redirect_path, 0, 7) == '$scheme') $tmp_redirect_path = 'http'.substr($tmp_redirect_path, 7);
					$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
					if($tmp_redirect_path_parts['host'] == $data['new']['domain'] && ($tmp_redirect_path_parts['port'] == '80' || $tmp_redirect_path_parts['port'] == '443' || !isset($tmp_redirect_path_parts['port']))){
						// URL is local
						if(substr($tmp_redirect_path_parts['path'], -1) == '/') $tmp_redirect_path_parts['path'] = substr($tmp_redirect_path_parts['path'], 0, -1);
						if(substr($tmp_redirect_path_parts['path'], 0, 1) != '/') $tmp_redirect_path_parts['path'] = '/'.$tmp_redirect_path_parts['path'];
						//$rewrite_exclude = '((?!'.$tmp_redirect_path_parts['path'].'))';
						if($data['new']['redirect_type'] == 'proxy'){
							$vhost_data['web_document_root_www_proxy'] = 'root '.$vhost_data['web_document_root_www'].';';
							$vhost_data['web_document_root_www'] .= $tmp_redirect_path_parts['path'];
							break;
						} else {
							$rewrite_exclude = '(?!/('.substr($tmp_redirect_path_parts['path'], 1).(substr($tmp_redirect_path_parts['path'], 1) != ''? '|': '').'stats'.($vhost_data['errordocs'] == 1 ? '|error' : '').'|\.well-known/acme-challenge))/';
							$exclude_own_hostname = $tmp_redirect_path_parts['host'];
						}
					} else {
						// external URL
						$rewrite_exclude = '(.?)/';
						if($data['new']['redirect_type'] == 'proxy'){
							$vhost_data['use_proxy'] = 'y';
							$rewrite_subdir = $tmp_redirect_path_parts['path'];
							if(substr($rewrite_subdir, 0, 1) == '/') $rewrite_subdir = substr($rewrite_subdir, 1);
							if(substr($rewrite_subdir, -1) != '/') $rewrite_subdir .= '/';
							if($rewrite_subdir == '/') $rewrite_subdir = '';
						}
					}
					unset($tmp_redirect_path);
					unset($tmp_redirect_path_parts);
				}
				$own_rewrite_rules[] = array( 'rewrite_domain'  => '^'.$this->_rewrite_quote($data['new']['domain']),
					'rewrite_type'   => ($data['new']['redirect_type'] == 'no')?'':$data['new']['redirect_type'],
					'rewrite_target'  => $data['new']['redirect_path'],
					'rewrite_exclude' => $rewrite_exclude,
					'rewrite_subdir' => $rewrite_subdir,
					'exclude_own_hostname' => $exclude_own_hostname,
					'proxy_directives' => $final_proxy_directives,
					'use_rewrite' => ($data['new']['redirect_type'] == 'proxy' ? false:true),
					'use_proxy' => ($data['new']['redirect_type'] == 'proxy' ? true:false));
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

				if($alias['redirect_type'] == '' || $alias['redirect_path'] == '' || substr($alias['redirect_path'], 0, 1) == '/') {
					// Add SEO redirects for alias domains
					if($alias['seo_redirect'] != '' && $data['new']['seo_redirect'] != '*_to_www_domain_tld' && $data['new']['seo_redirect'] != '*_to_domain_tld' && ($alias['type'] == 'alias' || ($alias['type'] == 'subdomain' && $data['new']['seo_redirect'] != '*_domain_tld_to_www_domain_tld' && $data['new']['seo_redirect'] != '*_domain_tld_to_domain_tld'))){
						$tmp_seo_redirects = $this->get_seo_redirects($alias, 'alias_', false, 'nginx');
						if(is_array($tmp_seo_redirects) && !empty($tmp_seo_redirects)){
							$alias_seo_redirects[] = $tmp_seo_redirects;
						}
					}
				}

				// Custom proxy directives
				if($alias['redirect_type'] == 'proxy' && trim($alias['proxy_directives'] != '')){
					$final_proxy_directives = array();
					$proxy_directives = $alias['proxy_directives'];
					// Make sure we only have Unix linebreaks
					$proxy_directives = str_replace("\r\n", "\n", $proxy_directives);
					$proxy_directives = str_replace("\r", "\n", $proxy_directives);
					$proxy_directive_lines = explode("\n", $proxy_directives);
					if(is_array($proxy_directive_lines) && !empty($proxy_directive_lines)){
						foreach($proxy_directive_lines as $proxy_directive_line){
							$final_proxy_directives[] = array('proxy_directive' => $proxy_directive_line);
						}
					}
				} else {
					$final_proxy_directives = false;
				}


				// Local Rewriting (inside vhost server {} container)
				if($alias['redirect_type'] != '' && substr($alias['redirect_path'], 0, 1) == '/' && $alias['redirect_type'] != 'proxy') {  // proxy makes no sense with local path
					if(substr($alias['redirect_path'], -1) != '/') $alias['redirect_path'] .= '/';
					$rewrite_exclude = '(?!/('.substr($alias['redirect_path'], 1, -1).(substr($alias['redirect_path'], 1, -1) != ''? '|': '').'stats'.($vhost_data['errordocs'] == 1 ? '|error' : '').'|\.well-known/acme-challenge))/';
					switch($alias['subdomain']) {
					case 'www':
						// example.com
						$local_rewrite_rules[] = array( 'local_redirect_origin_domain'  => $alias['domain'],
							'local_redirect_operator' => '=',
							'local_redirect_exclude' => $rewrite_exclude,
							'local_redirect_target' => $alias['redirect_path'],
							'local_redirect_type' => ($alias['redirect_type'] == 'no')?'':$alias['redirect_type']);

						// www.example.com
						$local_rewrite_rules[] = array( 'local_redirect_origin_domain'  => 'www.'.$alias['domain'],
							'local_redirect_operator' => '=',
							'local_redirect_exclude' => $rewrite_exclude,
							'local_redirect_target' => $alias['redirect_path'],
							'local_redirect_type' => ($alias['redirect_type'] == 'no')?'':$alias['redirect_type']);
						break;
					case '*':
						$local_rewrite_rules[] = array( 'local_redirect_origin_domain'  => '^('.str_replace('.', '\.', $alias['domain']).'|.+\.'.str_replace('.', '\.', $alias['domain']).')$',
							'local_redirect_operator' => '~*',
							'local_redirect_exclude' => $rewrite_exclude,
							'local_redirect_target' => $alias['redirect_path'],
							'local_redirect_type' => ($alias['redirect_type'] == 'no')?'':$alias['redirect_type']);
						break;
					default:
						$local_rewrite_rules[] = array( 'local_redirect_origin_domain'  => $alias['domain'],
							'local_redirect_operator' => '=',
							'local_redirect_exclude' => $rewrite_exclude,
							'local_redirect_target' => $alias['redirect_path'],
							'local_redirect_type' => ($alias['redirect_type'] == 'no')?'':$alias['redirect_type']);
					}
				}

				// External Rewriting (extra server {} containers)
				if($alias['redirect_type'] != '' && $alias['redirect_path'] != '' && substr($alias['redirect_path'], 0, 1) != '/') {
					if(substr($alias['redirect_path'], -1) != '/') $alias['redirect_path'] .= '/';
					if(substr($alias['redirect_path'], 0, 8) == '[scheme]'){
						if($alias['redirect_type'] != 'proxy'){
							$alias['redirect_path'] = '$scheme'.substr($alias['redirect_path'], 8);
						} else {
							$alias['redirect_path'] = 'http'.substr($alias['redirect_path'], 8);
						}
					}

					switch($alias['subdomain']) {
					case 'www':
						if($alias['redirect_type'] == 'proxy'){
							$tmp_redirect_path = $alias['redirect_path'];
							$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
							$rewrite_subdir = $tmp_redirect_path_parts['path'];
							if(substr($rewrite_subdir, 0, 1) == '/') $rewrite_subdir = substr($rewrite_subdir, 1);
							if(substr($rewrite_subdir, -1) != '/') $rewrite_subdir .= '/';
							if($rewrite_subdir == '/') $rewrite_subdir = '';
						}

						if($alias['redirect_type'] != 'proxy'){
							if(substr($alias['redirect_path'], -1) == '/') $alias['redirect_path'] = substr($alias['redirect_path'], 0, -1);
						}
						// Add SEO redirects for alias domains
						$alias_seo_redirects2 = array();
						if($alias['seo_redirect'] != ''){
							$tmp_seo_redirects = $this->get_seo_redirects($alias, 'alias_', 'none', 'nginx');
							if(is_array($tmp_seo_redirects) && !empty($tmp_seo_redirects)){
								$alias_seo_redirects2[] = $tmp_seo_redirects;
							}
						}
						$rewrite_rules[] = array( 'rewrite_domain'  => $alias['domain'],
							'rewrite_type'   => ($alias['redirect_type'] == 'no')?'':$alias['redirect_type'],
							'rewrite_target'  => $alias['redirect_path'],
							'rewrite_subdir' => $rewrite_subdir,
							'proxy_directives' => $final_proxy_directives,
							'use_rewrite' => ($alias['redirect_type'] == 'proxy' ? false:true),
							'use_proxy' => ($alias['redirect_type'] == 'proxy' ? true:false),
							'alias_seo_redirects2' => (count($alias_seo_redirects2) > 0 ? $alias_seo_redirects2 : false));

						// Add SEO redirects for alias domains
						$alias_seo_redirects2 = array();
						if($alias['seo_redirect'] != ''){
							$tmp_seo_redirects = $this->get_seo_redirects($alias, 'alias_', 'www', 'nginx');
							if(is_array($tmp_seo_redirects) && !empty($tmp_seo_redirects)){
								$alias_seo_redirects2[] = $tmp_seo_redirects;
							}
						}
						$rewrite_rules[] = array( 'rewrite_domain'  => 'www.'.$alias['domain'],
							'rewrite_type'   => ($alias['redirect_type'] == 'no')?'':$alias['redirect_type'],
							'rewrite_target'  => $alias['redirect_path'],
							'rewrite_subdir' => $rewrite_subdir,
							'proxy_directives' => $final_proxy_directives,
							'use_rewrite' => ($alias['redirect_type'] == 'proxy' ? false:true),
							'use_proxy' => ($alias['redirect_type'] == 'proxy' ? true:false),
							'alias_seo_redirects2' => (count($alias_seo_redirects2) > 0 ? $alias_seo_redirects2 : false));
						break;
					case '*':
						if($alias['redirect_type'] == 'proxy'){
							$tmp_redirect_path = $alias['redirect_path'];
							$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
							$rewrite_subdir = $tmp_redirect_path_parts['path'];
							if(substr($rewrite_subdir, 0, 1) == '/') $rewrite_subdir = substr($rewrite_subdir, 1);
							if(substr($rewrite_subdir, -1) != '/') $rewrite_subdir .= '/';
							if($rewrite_subdir == '/') $rewrite_subdir = '';
						}

						if($alias['redirect_type'] != 'proxy'){
							if(substr($alias['redirect_path'], -1) == '/') $alias['redirect_path'] = substr($alias['redirect_path'], 0, -1);
						}
						// Add SEO redirects for alias domains
						$alias_seo_redirects2 = array();
						if($alias['seo_redirect'] != ''){
							$tmp_seo_redirects = $this->get_seo_redirects($alias, 'alias_', false, 'nginx');
							if(is_array($tmp_seo_redirects) && !empty($tmp_seo_redirects)){
								$alias_seo_redirects2[] = $tmp_seo_redirects;
							}
						}
						$rewrite_rules[] = array( 'rewrite_domain'  => $alias['domain'].' *.'.$alias['domain'],
							'rewrite_type'   => ($alias['redirect_type'] == 'no')?'':$alias['redirect_type'],
							'rewrite_target'  => $alias['redirect_path'],
							'rewrite_subdir' => $rewrite_subdir,
							'proxy_directives' => $final_proxy_directives,
							'use_rewrite' => ($alias['redirect_type'] == 'proxy' ? false:true),
							'use_proxy' => ($alias['redirect_type'] == 'proxy' ? true:false),
							'alias_seo_redirects2' => (count($alias_seo_redirects2) > 0 ? $alias_seo_redirects2 : false));
						break;
					default:
						if($alias['redirect_type'] == 'proxy'){
							$tmp_redirect_path = $alias['redirect_path'];
							$tmp_redirect_path_parts = parse_url($tmp_redirect_path);
							$rewrite_subdir = $tmp_redirect_path_parts['path'];
							if(substr($rewrite_subdir, 0, 1) == '/') $rewrite_subdir = substr($rewrite_subdir, 1);
							if(substr($rewrite_subdir, -1) != '/') $rewrite_subdir .= '/';
							if($rewrite_subdir == '/') $rewrite_subdir = '';
						}

						if($alias['redirect_type'] != 'proxy'){
							if(substr($alias['redirect_path'], -1) == '/') $alias['redirect_path'] = substr($alias['redirect_path'], 0, -1);
						}
						if(substr($alias['domain'], 0, 2) === '*.') $domain_rule = '*.'.substr($alias['domain'], 2);
						else $domain_rule = $alias['domain'];
						// Add SEO redirects for alias domains
						$alias_seo_redirects2 = array();
						if($alias['seo_redirect'] != ''){
							if(substr($alias['domain'], 0, 2) === '*.'){
								$tmp_seo_redirects = $this->get_seo_redirects($alias, 'alias_', false, 'nginx');
							} else {
								$tmp_seo_redirects = $this->get_seo_redirects($alias, 'alias_', 'none', 'nginx');
							}
							if(is_array($tmp_seo_redirects) && !empty($tmp_seo_redirects)){
								$alias_seo_redirects2[] = $tmp_seo_redirects;
							}
						}
						$rewrite_rules[] = array( 'rewrite_domain'  => $domain_rule,
							'rewrite_type'   => ($alias['redirect_type'] == 'no')?'':$alias['redirect_type'],
							'rewrite_target'  => $alias['redirect_path'],
							'rewrite_subdir' => $rewrite_subdir,
							'proxy_directives' => $final_proxy_directives,
							'use_rewrite' => ($alias['redirect_type'] == 'proxy' ? false:true),
							'use_proxy' => ($alias['redirect_type'] == 'proxy' ? true:false),
							'alias_seo_redirects2' => (count($alias_seo_redirects2) > 0 ? $alias_seo_redirects2 : false));
					}
				}
			}
		}

		//* If we have some alias records
		if(count($server_alias) > 0) {
			$server_alias_str = '';
			foreach($server_alias as $tmp_alias) {
				$server_alias_str .= ' ' . $tmp_alias;
			}
			unset($tmp_alias);

			$tpl->setVar('alias', trim($server_alias_str));
		} else {
			$tpl->setVar('alias', '');
		}

		if(count($rewrite_rules) > 0) {
			$tpl->setLoop('redirects', $rewrite_rules);
		}
		if(count($own_rewrite_rules) > 0) {
			$tpl->setLoop('own_redirects', $own_rewrite_rules);
		}
		if(count($local_rewrite_rules) > 0) {
			$tpl->setLoop('local_redirects', $local_rewrite_rules);
		}
		if(count($alias_seo_redirects) > 0) {
			$tpl->setLoop('alias_seo_redirects', $alias_seo_redirects);
		}
		return;
	}
	
	/**
	 * This method may alter the $tpl template as well as $data and/or $vhost_data array!
	 * 
	 * @param tpl $tpl
	 * @param array $data
	 * @param array $vhost_data
	 * @param array $fpm_data
	 */
	public function processCustomDirectives(&$tpl, &$data, &$vhost_data, $fpm_data) {
		global $app, $conf;
		
		// Custom nginx directives
		if(intval($data['new']['directive_snippets_id']) > 0){
			$snippet = $app->db->queryOneRecord("SELECT * FROM directive_snippets WHERE directive_snippets_id = ? AND type = 'nginx' AND active = 'y' AND customer_viewable = 'y'", $data['new']['directive_snippets_id']);
			if(isset($snippet['snippet'])){
				$nginx_directives = $snippet['snippet'];
			} else {
				$nginx_directives = $data['new']['nginx_directives'];
			}
		} else {
			$nginx_directives = $data['new']['nginx_directives'];
		}
		
		$final_nginx_directives = array();
		if($data['new']['enable_pagespeed'] == 'y'){
			// if PageSpeed is already enabled, don't add configuration again
			if(stripos($nginx_directives, 'pagespeed') !== false){
				$vhost_data['enable_pagespeed'] = false;
			} else {
				$vhost_data['enable_pagespeed'] = true;
			}
		} else {
			$vhost_data['enable_pagespeed'] = false;
		}
		
		$web_folder = $app->plugin_webserver_base->getWebFolder($data, 'web');
		$username = escapeshellcmd($data['new']['system_user']);
		$groupname = escapeshellcmd($data['new']['system_group']);
		
		// folder_directive_snippets
		if(trim($data['new']['folder_directive_snippets']) != ''){
			$data['new']['folder_directive_snippets'] = trim($data['new']['folder_directive_snippets']);
			$data['new']['folder_directive_snippets'] = str_replace("\r\n", "\n", $data['new']['folder_directive_snippets']);
			$data['new']['folder_directive_snippets'] = str_replace("\r", "\n", $data['new']['folder_directive_snippets']);
			$folder_directive_snippets_lines = explode("\n", $data['new']['folder_directive_snippets']);
			
			if(is_array($folder_directive_snippets_lines) && !empty($folder_directive_snippets_lines)){
				foreach($folder_directive_snippets_lines as $folder_directive_snippets_line){
					list($folder_directive_snippets_folder, $folder_directive_snippets_snippets_id) = explode(':', $folder_directive_snippets_line);
					
					$folder_directive_snippets_folder = trim($folder_directive_snippets_folder);
					$folder_directive_snippets_snippets_id = trim($folder_directive_snippets_snippets_id);
					
					if($folder_directive_snippets_folder  != '' && intval($folder_directive_snippets_snippets_id) > 0 && preg_match('@^((?!(.*\.\.)|(.*\./)|(.*//))[^/][\w/_\.\-]{1,100})?$@', $folder_directive_snippets_folder)){
						if(substr($folder_directive_snippets_folder, -1) != '/') $folder_directive_snippets_folder .= '/';
						if(substr($folder_directive_snippets_folder, 0, 1) == '/') $folder_directive_snippets_folder = substr($folder_directive_snippets_folder, 1);
						
						$master_snippet = $app->db->queryOneRecord("SELECT * FROM directive_snippets WHERE directive_snippets_id = ? AND type = 'nginx' AND active = 'y' AND customer_viewable = 'y'", intval($folder_directive_snippets_snippets_id));
						if(isset($master_snippet['snippet'])){
							$folder_directive_snippets_trans = array('{FOLDER}' => $folder_directive_snippets_folder, '{FOLDERMD5}' => md5($folder_directive_snippets_folder));
							$master_snippet['snippet'] = strtr($master_snippet['snippet'], $folder_directive_snippets_trans);
							$nginx_directives .= "\n\n".$master_snippet['snippet'];
							
							// create folder it it does not exist
							if(!is_dir($data['new']['document_root'].'/' . $web_folder.$folder_directive_snippets_folder)){
								$app->system->mkdirpath($data['new']['document_root'].'/' . $web_folder.$folder_directive_snippets_folder);
								$app->system->chown($data['new']['document_root'].'/' . $web_folder.$folder_directive_snippets_folder, $username);
								$app->system->chgrp($data['new']['document_root'].'/' . $web_folder.$folder_directive_snippets_folder, $groupname);
							}
						}
					}
				}
			}
		}
		
		// use vLib for template logic
		if(trim($nginx_directives) != '') {
			$nginx_directives_new = '';
			$ngx_conf_tpl = new tpl();
			$ngx_conf_tpl_tmp_file = tempnam($conf['temppath'], "ngx");
			file_put_contents($ngx_conf_tpl_tmp_file, $nginx_directives);
			$ngx_conf_tpl->newTemplate($ngx_conf_tpl_tmp_file);
			$ngx_conf_tpl->setVar('use_tcp', $fpm_data['use_tcp']);
			$ngx_conf_tpl->setVar('use_socket', $fpm_data['use_socket']);
			$ngx_conf_tpl->setVar('fpm_socket', $fpm_data['fpm_socket']);
			$ngx_conf_tpl->setVar($vhost_data);
			$nginx_directives_new = $ngx_conf_tpl->grab();
			if(is_file($ngx_conf_tpl_tmp_file)) unlink($ngx_conf_tpl_tmp_file);
			if($nginx_directives_new != '') $nginx_directives = $nginx_directives_new;
			unset($nginx_directives_new);
		}
		
		// Make sure we only have Unix linebreaks
		$nginx_directives = str_replace("\r\n", "\n", $nginx_directives);
		$nginx_directives = str_replace("\r", "\n", $nginx_directives);
		$nginx_directive_lines = explode("\n", $nginx_directives);
		if(is_array($nginx_directive_lines) && !empty($nginx_directive_lines)){
			$trans = array(
				'{DOCROOT}' => $vhost_data['web_document_root_www'],
				'{DOCROOT_CLIENT}' => $vhost_data['web_document_root'],
				'{FASTCGIPASS}' => 'fastcgi_pass '.($data['new']['php_fpm_use_socket'] == 'y'? 'unix:'.$fpm_data['fpm_socket'] : '127.0.0.1:'.$fpm_data['fpm_port']).';'
			);
			foreach($nginx_directive_lines as $nginx_directive_line){
				$final_nginx_directives[] = array('nginx_directive' => strtr($nginx_directive_line, $trans));
			}
		}
		$tpl->setLoop('nginx_directives', $final_nginx_directives);

		return;
	}

	
	public function getStatsFolder($data) {
		$stats_web_folder = 'web';
		if($data['new']['type'] == 'vhost'){
			if($data['new']['web_folder'] != ''){
				if(substr($data['new']['web_folder'], 0, 1) == '/') $data['new']['web_folder'] = substr($data['new']['web_folder'],1);
				if(substr($data['new']['web_folder'], -1) == '/') $data['new']['web_folder'] = substr($data['new']['web_folder'],0,-1);
			}
			$stats_web_folder .= '/'.$data['new']['web_folder'];
		} elseif($data['new']['type'] == 'vhostsubdomain' || $data['new']['type'] == 'vhostalias') {
			$stats_web_folder = $data['new']['web_folder'];
		}
		return $stats_web_folder;
	}
	
	/**
	 * This method may alter the $tpl template as well as $data and/or $vhost_data array!
	 * 
	 * @param tpl $tpl
	 * @param array $data
	 * @param array $vhost_data
	 */
	public function processStatsAuth(&$tpl, &$data, &$vhost_data) {
		
		$stats_web_folder = $this->getStatsFolder($data);
		
		//* Create basic http auth for website statistics
		$tpl->setVar('stats_auth_passwd_file', $data['new']['document_root']."/" . $stats_web_folder . "/stats/.htpasswd_stats");

		// Create basic http auth for other directories
		$basic_auth_locations = $this->_create_web_folder_auth_configuration($data['new']);
		if(is_array($basic_auth_locations) && !empty($basic_auth_locations)) $tpl->setLoop('basic_auth_locations', $basic_auth_locations);

		return;
	}
	
	private function _create_web_folder_auth_configuration($website){
		global $app;

		//* Create the domain.auth file which is included in the vhost configuration file

		$website_auth_locations = $app->db->queryAllRecords("SELECT * FROM web_folder WHERE active = 'y' AND parent_domain_id = ?", $website['domain_id']);
		$basic_auth_locations = array();
		if(is_array($website_auth_locations) && !empty($website_auth_locations)){
			foreach($website_auth_locations as $website_auth_location){
				if(substr($website_auth_location['path'], 0, 1) == '/') $website_auth_location['path'] = substr($website_auth_location['path'], 1);
				if(substr($website_auth_location['path'], -1) == '/') $website_auth_location['path'] = substr($website_auth_location['path'], 0, -1);
				if($website_auth_location['path'] != ''){
					$website_auth_location['path'] .= '/';
				}
				$basic_auth_locations[] = array('htpasswd_location' => '/'.$website_auth_location['path'],
					'htpasswd_path' => $website['document_root'].'/' . (($website['type'] == 'vhostsubdomain' || $website['type'] == 'vhostalias') ? $website['web_folder'] : 'web') . '/'.$website_auth_location['path']);
			}
		}
		return $basic_auth_locations;
	}
	
	public function testWebserverConfig() {
		global $app;
		
		// if no output is given, check again
		$tmp_output = null;
		$tmp_retval = 0;
		exec('nginx -t 2>&1', $tmp_output, $tmp_retval);
		if($tmp_retval > 0 && is_array($tmp_output) && !empty($tmp_output)){
			$app->log('Reason for nginx restart failure: '.implode("\n", $tmp_output), LOGLEVEL_WARN);
			$app->dbmaster->datalogError(implode("\n", $tmp_output));
		}
		unset($tmp_output, $tmp_retval);
	}
}
