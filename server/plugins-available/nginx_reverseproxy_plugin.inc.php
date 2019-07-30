<?php

class nginx_reverseproxy_plugin {

	var $plugin_name = 'nginx_reverseproxy_plugin';
	var $class_name = 'nginx_reverseproxy_plugin';

	// private variables
	var $action = '';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if(isset($conf['services']['proxy']) && $conf['services']['proxy'] == true && isset($conf['nginx']['installed']) && $conf['nginx']['installed'] == true) {
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

		$app->plugins->registerEvent('web_domain_insert', $this->plugin_name, 'ssl');
		$app->plugins->registerEvent('web_domain_update', $this->plugin_name, 'ssl');
		$app->plugins->registerEvent('web_domain_delete', $this->plugin_name, 'ssl');

		$app->plugins->registerEvent('web_domain_insert', $this->plugin_name, 'insert');
		$app->plugins->registerEvent('web_domain_update', $this->plugin_name, 'update');
		$app->plugins->registerEvent('web_domain_delete', $this->plugin_name, 'delete');

		// $app->plugins->registerEvent('proxy_reverse_insert',$this->plugin_name,'rewrite_insert');
		// $app->plugins->registerEvent('proxy_reverse_update',$this->plugin_name,'rewrite_update');
		// $app->plugins->registerEvent('proxy_reverse_delete',$this->plugin_name,'rewrite_delete');



	}


	function insert($event_name, $data) {
		global $app, $conf;

		// just run the update function
		$this->update($event_name, $data);
	}


	function update($event_name, $data) {
		global $app, $conf;

		if($this->action != 'insert') $this->action = 'update';

		if($data['new']['type'] != 'vhost' && $data['new']['type'] != 'vhostsubdomain' && $data['new']['type'] != 'vhostalias' && $data['new']['parent_domain_id'] > 0) {

			$old_parent_domain_id = intval($data['old']['parent_domain_id']);
			$new_parent_domain_id = intval($data['new']['parent_domain_id']);

			// If the parent_domain_id has been chenged, we will have to update the old site as well.
			if($this->action == 'update' && $data['new']['parent_domain_id'] != $data['old']['parent_domain_id']) {
				$tmp = $app->dbmaster->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ? AND active = 'y'", $old_parent_domain_id);
				$data['new'] = $tmp;
				$data['old'] = $tmp;
				$this->action = 'update';
				$this->update($event_name, $data);
			}

			// This is not a vhost, so we need to update the parent record instead.
			$tmp = $app->dbmaster->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ? AND active = 'y'", $new_parent_domain_id);
			$data['new'] = $tmp;
			$data['old'] = $tmp;
			$this->action = 'update';
		}




		// load the server configuration options
		$app->uses('getconf');
		$nginx_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		// Create group and user, if not exist
		$app->uses('system');

		//* Create the vhost config file
		$app->load('tpl');

		$tpl = new tpl();
		$tpl->newTemplate('nginx_reverseproxy_vhost.conf.master');

		$vhost_data = $data['new'];
		$vhost_data['config_dir'] = $config['nginx']['config_dir'];

		$vhost_data['ssl_domain'] = $data['new']['ssl_domain'];
		// Check if a SSL cert exists
		$ssl_dir = $config['nginx']['config_dir'].'/ssl';
		$domain = $data['new']['ssl_domain'];
		$key_file = $ssl_dir.'/'.$domain.'.key';
		$crt_file = $ssl_dir.'/'.$domain.'.crt';
		$bundle_file = $ssl_dir.'/'.$domain.'.bundle';

		$vhost_data['nginx_directives'] = preg_replace("/\[IP\]/", $vhost_data['ip_address'], $vhost_data['nginx_directives']);


		if($data['new']['ssl'] == 'y' && @is_file($crt_file) && @is_file($key_file)) {
			$vhost_data['ssl_enabled'] = 1;
			$app->log('Enable SSL for: '.$domain, LOGLEVEL_DEBUG);
		} else {
			$vhost_data['ssl_enabled'] = 0;
			$app->log('Disable SSL for: '.$domain, LOGLEVEL_DEBUG);
		}

		if(@is_file($bundle_file)) $vhost_data['has_bundle_cert'] = 1;


		$tpl->setVar($vhost_data);



		// get alias domains (co-domains and subdomains)
		$aliases = $app->dbmaster->queryAllRecords("SELECT * FROM web_domain WHERE parent_domain_id = ? AND (type != 'vhostsubdomain' OR type != 'vhostalias') AND active = 'y'", $data['new']['domain_id']);
		$server_alias = array();
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
				$app->log('Add server alias: '.$alias['domain'], LOGLEVEL_DEBUG);

			}
		}

		//* If we have some alias records
		if(count($server_alias) > 0) {
			$server_alias_str = '';
			$n = 0;

			// begin a new ServerAlias line after 30 alias domains
			foreach($server_alias as $tmp_alias) {
				if($n % 30 == 0) $server_alias_str .= " ";
				$server_alias_str .= $tmp_alias;
			}
			unset($tmp_alias);

			$tpl->setVar('alias', trim($server_alias_str));
		} else {
			$tpl->setVar('alias', '');
		}


		$vhost_file = $nginx_config['nginx_vhost_conf_dir'].'/'.$data['new']['domain'].'.vhost';
		//* Make a backup copy of vhost file
		copy($vhost_file, $vhost_file.'~');

		//* Write vhost file
		file_put_contents($vhost_file, $tpl->grab());
		$app->log('Writing the vhost file: '.$vhost_file, LOGLEVEL_DEBUG);
		unset($tpl);


		// Set the symlink to enable the vhost
		$vhost_symlink = $nginx_config['nginx_vhost_conf_enabled_dir'].'/'.$data['new']['domain'].'.vhost';
		if($data['new']['active'] == 'y' && !is_link($vhost_symlink)) {
			symlink($vhost_file, $vhost_symlink);
			$app->log('Creating symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
		}

		// Remove the symlink, if site is inactive
		if($data['new']['active'] == 'n' && is_link($vhost_symlink)) {
			unlink($vhost_symlink);
			$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
		}

		if(!is_dir('/var/log/ispconfig/nginx/'.$data['new']['domain'])) $app->system->exec_safe('mkdir -p ?', '/var/log/ispconfig/nginx/'.$data['new']['domain']);

		// remove old symlink and vhost file, if domain name of the site has changed
		if($this->action == 'update' && $data['old']['domain'] != '' && $data['new']['domain'] != $data['old']['domain']) {
			$vhost_symlink = $nginx_config['nginx_vhost_conf_enabled_dir'].'/'.$data['old']['domain'].'.vhost';
			unlink($vhost_symlink);
			$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);
			$vhost_file = $nginx_config['nginx_vhost_conf_dir'].'/'.$data['old']['domain'].'.vhost';
			unlink($vhost_file);
			$app->log('Removing file: '.$vhost_file, LOGLEVEL_DEBUG);

			if(is_dir('/var/log/ispconfig/nginx/'.$data['old']['domain'])) $app->system->exec_safe('rm -rf ?', '/var/log/ispconfig/nginx/'.$data['old']['domain']);
		}

		// request a httpd reload when all records have been processed
		$app->services->restartServiceDelayed('nginx', 'restart');

		// Remove the backup copy of the config file.
		if(@is_file($vhost_file.'~')) unlink($vhost_file.'~');


		//* Unset action to clean it for next processed vhost.
		$this->action = '';

	}




	// Handle the creation of SSL certificates
	function ssl($event_name, $data) {
		global $app, $conf;

		if(!is_dir($conf['nginx']['config_dir'].'/ssl')) $app->system->exec_safe('mkdir -p ?', $conf['nginx']['config_dir'].'/ssl');
		$ssl_dir = $conf['nginx']['config_dir'].'/ssl';
		$domain = $data['new']['ssl_domain'];
		$key_file = $ssl_dir.'/'.$domain.'.key.org';
		$key_file2 = $ssl_dir.'/'.$domain.'.key';
		$csr_file = $ssl_dir.'/'.$domain.'.csr';
		$crt_file = $ssl_dir.'/'.$domain.'.crt';


		//* Save a SSL certificate to disk
		if($data["new"]["ssl_action"] == 'save') {
			$web = $app->masterdb->queryOneRecord("select wd.document_root, sp.ip_address from web_domain wd INNER JOIN server_ip sp USING(server_id) WHERE domain = ?", $data['new']['domain']);

			$src_ssl_dir = $web["document_root"]."/ssl";
			//$domain = $data["new"]["ssl_domain"];
			//$csr_file = $ssl_dir.'/'.$domain.".csr";
			//$crt_file = $ssl_dir.'/'.$domain.".crt";
			//$bundle_file = $ssl_dir.'/'.$domain.".bundle";
			$app->system->exec_safe('rsync -v -e ssh root@?:? ?', $web['ip_address'], '~/'.$src_ssl_dir, $ssl_dir);

			$app->log('Syncing SSL Cert for: '.$domain, LOGLEVEL_DEBUG);
		}

		//* Delete a SSL certificate
		if($data['new']['ssl_action'] == 'del') {
			//$ssl_dir = $data['new']['document_root'].'/ssl';
			$domain = $data['new']['ssl_domain'];
			$csr_file = $ssl_dir.'/'.$domain.'.csr';
			$crt_file = $ssl_dir.'/'.$domain.'.crt';
			$bundle_file = $ssl_dir.'/'.$domain.'.bundle';
			unlink($csr_file);
			unlink($crt_file);
			unlink($bundle_file);
			$app->log('Deleting SSL Cert for: '.$domain, LOGLEVEL_DEBUG);
		}


	}


	function delete($event_name, $data) {
		global $app, $conf;

		// load the server configuration options
		$app->uses('getconf');
		$nginx_config = $app->getconf->get_server_config($conf['server_id'], 'web');


		if($data['old']['type'] == 'vhost' || $data['old']['type'] == 'vhostsubdomain' || $data['old']['type'] == 'vhostalias') {

			//* This is a website
			// Deleting the vhost file, symlink and the data directory
			$vhost_symlink = $nginx_config['nginx_vhost_conf_enabled_dir'].'/'.$data['old']['domain'].'.vhost';
			unlink($vhost_symlink);
			$app->log('Removing symlink: '.$vhost_symlink.'->'.$vhost_file, LOGLEVEL_DEBUG);

			$vhost_file = $nginx_config['nginx_vhost_conf_dir'].'/'.$data['old']['domain'].'.vhost';
			unlink($vhost_file);
			$app->log('Removing vhost file: '.$vhost_file, LOGLEVEL_DEBUG);



			// Delete the log file directory
			$vhost_logfile_dir = '/var/log/ispconfig/nginx/'.$data['old']['domain'];
			if($data['old']['domain'] != '' && !stristr($vhost_logfile_dir, '..')) $app->system->exec_safe('rm -rf ?', $vhost_logfile_dir);
			$app->log('Removing website logfile directory: '.$vhost_logfile_dir, LOGLEVEL_DEBUG);

		}
	}

	function rewrite_insert($event_name, $data) {
		global $app, $conf;

		// just run the update function
		$this->update($event_name, $data);
	}

	function rewrite_update($event_name, $data) {
		global $app, $conf;

		$rules = $this->_getRewriteRules($app);

		$app->uses('getconf');
		$nginx_config = $app->getconf->get_server_config($conf['server_id'], 'web');

		$app->load('tpl');
		$tpl = new tpl();
		$tpl->newTemplate("nginx_reverseproxy_rewrites.conf.master");
		if (!empty($rules))$tpl->setLoop('nginx_rewrite_rules', $rules);

		$rewrites_file = $nginx_config['nginx_vhost_conf_dir'].'/default.rewrites.conf';
		//* Make a backup copy of vhost file
		copy($rewrites_file, $rewrites_file.'~');

		//* Write vhost file
		file_put_contents($rewrites_file, $tpl->grab());
		$app->log('Writing the nginx rewrites file: '.$rewrites_file, LOGLEVEL_DEBUG);
		unset($tpl);


		// Set the symlink to enable the vhost
		$rewrite_symlink = $nginx_config['nginx_vhost_conf_enabled_dir'].'/default.rewrites.conf';

		if(!is_link($rewrite_symlink)) {
			symlink($rewrites_file, $rewrite_symlink);
			$app->log('Creating symlink for nginx rewrites: '.$rewrite_symlink.'->'.$rewrites_file, LOGLEVEL_DEBUG);
		}
	}

	function rewrite_delete($event_name, $data) {
		global $app, $conf;

		// just run the update function
		$this->rewrite_update($event_name, $data);
	}


	function _getRewriteRules($app)
	{
		$rules = array();
		$rules = $app->db->queryAllRecords("SELECT rewrite_url_src, rewrite_url_dst FROM proxy_reverse ORDER BY rewrite_id ASC");
		return $rules;
	}

} // end class

?>
