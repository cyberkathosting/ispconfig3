<?php

/*
Copyright (c) 2009, Till Brehm, projektfarm Gmbh
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

class apps_vhost_plugin {

	var $plugin_name = 'apps_vhost_plugin';
	var $class_name = 'apps_vhost_plugin';


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

		$app->plugins->registerEvent('server_insert', 'apps_vhost_plugin', 'insert');
		$app->plugins->registerEvent('server_update', 'apps_vhost_plugin', 'update');


	}

	function insert($event_name, $data) {
		global $app, $conf;

		$this->update($event_name, $data);

	}

	// The purpose of this plugin is to rewrite the main.cf file
	function update($event_name, $data) {
		global $app, $conf;

		// get the config
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');

		if($web_config['server_type'] == 'apache'){
			$app->load('tpl');

			$tpl = new tpl();
			$tpl->newTemplate('apache_apps.vhost.master');

			$tpl->setVar('apache_version', $app->system->getapacheversion());

			$vhost_conf_dir = $web_config['vhost_conf_dir'];
			$vhost_conf_enabled_dir = $web_config['vhost_conf_enabled_dir'];
			$apps_vhost_servername = ($web_config['apps_vhost_servername'] == '')?'':'ServerName '.$web_config['apps_vhost_servername'];

			$web_config['apps_vhost_port'] = (empty($web_config['apps_vhost_port']))?8081:$web_config['apps_vhost_port'];
			$web_config['apps_vhost_ip'] = (empty($web_config['apps_vhost_ip']))?'_default_':$web_config['apps_vhost_ip'];

			$tpl->setVar('apps_vhost_ip', $web_config['apps_vhost_ip']);
			$tpl->setVar('apps_vhost_port', $web_config['apps_vhost_port']);
			$tpl->setVar('apps_vhost_dir', $web_config['website_basedir'].'/apps');
			$tpl->setVar('apps_vhost_servername', $apps_vhost_servername);
			$tpl->setVar('apps_vhost_basedir', $web_config['website_basedir']);

			$vhost_port_listen = '';
			// comment out the listen directive if port is 80 or 443
			if($web_config['apps_vhost_port'] == 80 or $web_config['apps_vhost_port'] == 443) {
				$vhost_port_listen = '#';
			}
			$tpl->setVar('vhost_port_listen', $vhost_port_listen);
			
			$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
			if($mail_config['content_filter'] == 'rspamd'){
				$use_rspamd = true;
				exec('/usr/sbin/a2enmod proxy');
				exec('/usr/sbin/a2enmod proxy_http');
			} else {
				$use_rspamd = false;
			}
			$tpl->setVar('use_rspamd', $use_rspamd);

			$content = $tpl->grab();

			/* for backwards compatibility we replace the old syntax by hand now */
			$content = str_replace('{apps_vhost_ip}', $web_config['apps_vhost_ip'], $content);
			$content = str_replace('{apps_vhost_port}', $web_config['apps_vhost_port'], $content);
			$content = str_replace('{apps_vhost_dir}', $web_config['website_basedir'].'/apps', $content);
			$content = str_replace('{apps_vhost_servername}', $apps_vhost_servername, $content);
			$content = str_replace('{apps_vhost_basedir}', $web_config['website_basedir'], $content);
			$content = str_replace('{vhost_port_listen}', $vhost_port_listen, $content);
			/* end of backwards compatibility section */

			$app->system->file_put_contents("$vhost_conf_dir/apps.vhost", $content);

			// enabled / disable apps-vhost
			$vhost_symlink = $web_config['vhost_conf_enabled_dir'].'/000-apps.vhost';
			if(is_link($vhost_symlink) && $web_config['apps_vhost_enabled'] == 'n') {
				$app->system->unlink($vhost_symlink);
			}
			if(!@is_link($vhost_conf_enabled_dir.'/000-apps.vhost') && $web_config['apps_vhost_enabled'] == 'y') {
				symlink($vhost_conf_dir.'/apps.vhost', $vhost_conf_enabled_dir.'/000-apps.vhost');
			}

			$app->services->restartServiceDelayed('httpd', 'restart');
		}

		if($web_config['server_type'] == 'nginx'){
			// Dont just copy over the virtualhost template but add some custom settings
			if(file_exists($conf["rootpath"]."/conf-custom/nginx_apps.vhost.master")) {
				$content = file_get_contents($conf["rootpath"]."/conf-custom/nginx_apps.vhost.master");
			} else {
				$content = file_get_contents($conf["rootpath"]."/conf/nginx_apps.vhost.master");
			}

			$vhost_conf_dir = $web_config['nginx_vhost_conf_dir'];
			$vhost_conf_enabled_dir = $web_config['nginx_vhost_conf_enabled_dir'];
			$apps_vhost_servername = ($web_config['apps_vhost_servername'] == '')?'_':$web_config['apps_vhost_servername'];

			$apps_vhost_user = 'ispapps';
			$apps_vhost_group = 'ispapps';

			$web_config['apps_vhost_port'] = (empty($web_config['apps_vhost_port']))?8081:$web_config['apps_vhost_port'];
			$web_config['apps_vhost_ip'] = (empty($web_config['apps_vhost_ip']))?'_default_':$web_config['apps_vhost_ip'];

			if($web_config['apps_vhost_ip'] == '_default_'){
				$apps_vhost_ip = '';
			} else {
				$apps_vhost_ip = $web_config['apps_vhost_ip'].':';
			}

			$socket_dir = $web_config['php_fpm_socket_dir'];
			if(substr($socket_dir, -1) != '/') $socket_dir .= '/';
			if(!is_dir($socket_dir)) $app->system->exec_safe('mkdir -p ?', $socket_dir);
			$fpm_socket = $socket_dir.'apps.sock';
			$cgi_socket = $web_config['nginx_cgi_socket'];

			$content = str_replace('{apps_vhost_ip}', $apps_vhost_ip, $content);
			$content = str_replace('{apps_vhost_port}', $web_config['apps_vhost_port'], $content);
			$content = str_replace('{apps_vhost_dir}', $web_config['website_basedir'].'/apps', $content);
			$content = str_replace('{apps_vhost_servername}', $apps_vhost_servername, $content);
			//$content = str_replace('{fpm_port}', $web_config['php_fpm_start_port']+1, $content);
			$content = str_replace('{fpm_socket}', $fpm_socket, $content);
			$content = str_replace('{cgi_socket}', $cgi_socket, $content);
			if(	file_exists('/var/run/php5-fpm.sock')
				|| file_exists('/var/run/php/php7.0-fpm.sock')
				|| file_exists('/var/run/php/php7.1-fpm.sock')
				|| file_exists('/var/run/php/php7.2-fpm.sock')
				|| file_exists('/var/run/php/php7.3-fpm.sock')
			){
				$use_tcp = '#';
				$use_socket = '';
			} else {
				$use_tcp = '';
				$use_socket = '#';
			}
			$content = str_replace('{use_tcp}', $use_tcp, $content);
			$content = str_replace('{use_socket}', $use_socket, $content);
			
			$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
			if($mail_config['content_filter'] == 'rspamd'){
				$use_rspamd = '';
			} else {
				$use_rspamd = '#';
			}
			$content = str_replace('{use_rspamd}', $use_rspamd, $content);

			// Fix socket path on PHP 7 systems
			if(file_exists('/var/run/php/php7.0-fpm.sock'))	$content = str_replace('/var/run/php5-fpm.sock', '/var/run/php/php7.0-fpm.sock', $content);
			if(file_exists('/var/run/php/php7.1-fpm.sock'))	$content = str_replace('/var/run/php5-fpm.sock', '/var/run/php/php7.1-fpm.sock', $content);
			if(file_exists('/var/run/php/php7.2-fpm.sock'))	$content = str_replace('/var/run/php5-fpm.sock', '/var/run/php/php7.2-fpm.sock', $content);

			// PHP-FPM
			// Dont just copy over the php-fpm pool template but add some custom settings
			if(file_exists($conf["rootpath"]."/conf-custom/apps_php_fpm_pool.conf.master")) {
				$fpm_content = file_get_contents($conf["rootpath"]."/conf-custom/apps_php_fpm_pool.conf.master");
			} else {
				$fpm_content = file_get_contents($conf["rootpath"]."/conf/apps_php_fpm_pool.conf.master");
			}

			$fpm_content = str_replace('{fpm_pool}', 'apps', $fpm_content);
			//$fpm_content = str_replace('{fpm_port}', $web_config['php_fpm_start_port']+1, $fpm_content);
			$fpm_content = str_replace('{fpm_socket}', $fpm_socket, $fpm_content);
			$fpm_content = str_replace('{fpm_user}', $apps_vhost_user, $fpm_content);
			$fpm_content = str_replace('{fpm_group}', $apps_vhost_group, $fpm_content);
			file_put_contents($web_config['php_fpm_pool_dir'].'/apps.conf', $fpm_content);

			file_put_contents("$vhost_conf_dir/apps.vhost", $content);

			// enabled / disable apps-vhost
			$vhost_symlink = $web_config['vhost_conf_enabled_dir'].'/000-apps.vhost';
			if(is_link($vhost_symlink) && $web_config['apps_vhost_enabled'] == 'n') {
				$app->system->unlink($vhost_symlink);
			}
			if(!@is_link($vhost_conf_enabled_dir.'/000-apps.vhost') && $web_config['apps_vhost_enabled'] == 'y') {
				symlink($vhost_conf_dir.'/apps.vhost', $vhost_conf_enabled_dir.'/000-apps.vhost');
			}

			$app->services->restartServiceDelayed('httpd', 'reload');
		}
	}


} // end class
