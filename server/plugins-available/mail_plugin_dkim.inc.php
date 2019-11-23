<?php

/**
 Copyright (c) 2007 - 2013, Till Brehm, projektfarm Gmbh
 Copyright (c) 2013, Florian Schaal, info@schaal-24.de
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

 @author Florian Schaal, info@schaal-24.de
 @author Marius Burkard, m.burkard@ispconfig.org (modified for rspamd)
 @copyright Florian Schaal, info@schaal-24.de
 */


class mail_plugin_dkim {

	var $plugin_name = 'mail_plugin_dkim';
	var $class_name = 'mail_plugin_dkim';

	// private variables
	var $action = '';

	/**
	 * This function is called during ispconfig installation to determine
	 * if a symlink shall be created for this plugin.
	 */
	function onInstall() {
		global $conf;

		if($conf['services']['mail'] == true) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * This function is called when the plugin is loaded
	 */
	public function onLoad() {
		global $app;
		/*
		Register for the events
		*/
		$app->plugins->registerEvent('mail_domain_delete', $this->plugin_name, 'domain_dkim_delete');
		$app->plugins->registerEvent('mail_domain_insert', $this->plugin_name, 'domain_dkim_insert');
		$app->plugins->registerEvent('mail_domain_update', $this->plugin_name, 'domain_dkim_update');
	}

	/**
	 * This function gets the amavisd-config file
	 * @return string path to the amavisd-config for dkim-keys
	 */
	private function get_amavis_config() {
		$pos_config=array(
			'/etc/amavisd.conf/50-user',
			'/etc/amavis/conf.d/50-user',
			'/etc/amavisd.conf',
			'/etc/amavisd/amavisd.conf'
		);
		$amavis_configfile='';
		foreach($pos_config as $conf) {
			if (is_file($conf)) {
				$amavis_configfile=$conf;
				break;
			}
		}
		if (substr_compare($amavis_configfile, '50-user', -7) === 0) {
			$amavis_configfile = str_replace('50-user', '60-dkim', $amavis_configfile);
		} else { //* fake 60-dkim on system with a monolithic amavis-config
			$amavis_configfile = str_replace('amavisd.conf', '60-dkim', $amavis_configfile);
		}
		return $amavis_configfile;
	}

	/**
	 * This function checks the relevant configs and disables dkim for the domain
	 * if the directory for dkim is not writeable or does not exist
	 * @param array $data mail-settings
	 * @return boolean - true when the amavis-config and the dkim-dir are writeable
	 */
	private function check_system() {
		global $app, $conf, $mail_config;

		$app->uses('getconf');
		$check = true;

		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		if($mail_config['content_filter'] != 'rspamd') {
			/* check for amavis-config */
			$amavis_configfile = $this->get_amavis_config();

			//* Create the file if it does not exists.
			if (substr_compare($amavis_configfile, '60-dkim', -7) === 0 && !file_exists($amavis_configfile)) {
				$app->system->touch($amavis_configfile);
			}
			
			if ( $amavis_configfile == '' || !is_writeable($amavis_configfile) ) {
				$app->log('Amavis-config not found or not writeable.', LOGLEVEL_ERROR);
				$check = false;
			}
		}

		/* dir for dkim-keys writeable? */
		if (isset($mail_config['dkim_path']) && !empty($mail_config['dkim_path']) && $mail_config['dkim_path'] != '/') {
            if (!is_dir($mail_config['dkim_path'])) {
                $app->log('DKIM Path '.$mail_config['dkim_path'].' not found - (re)created.', LOGLEVEL_DEBUG);
				if($app->system->is_user('amavis')) { 
					$amavis_user='amavis'; 
				} elseif($app->system->is_user('_rspamd')) {
					$amavis_user = '_rspamd';
				} elseif($app->system->is_user('rspamd')) {
					$amavis_user = 'rspamd';
				} elseif ($app->system->is_user('vscan')) { 
					$amavis_user = 'vscan'; 
				} else { 
					$amavis_user = ''; 
				}
				if($app->system->is_user('amavis')) { 
					$amavis_group='amavis'; 
				} elseif($app->system->is_user('_rspamd')) {
					$amavis_group = '_rspamd';
				} elseif($app->system->is_user('rspamd')) {
					$amavis_group = 'rspamd';
				} elseif ($app->system->is_user('vscan')) { 
					$amavis_group = 'vscan'; 
				} else { 
					$amavis_group = ''; 
				}
				
				if(!empty($amavis_user)) {
					$app->system->mkdirpath($mail_config['dkim_path'], 0750, $amavis_user, $amavis_group);
				} else {
					$app->system->mkdirpath($mail_config['dkim_path'], 0755);
					$app->log('No user amavis or vscan found - using root for '.$mail_config['dkim_path'], LOGLEVEL_WARNING);
				}
            } else {
				if (!$app->system->checkpath($mail_config['dkim_path'])) {
					$app->log('Unable to write DKIM settings - invalid DKIM-Path (symlink?)', LOGLEVEL_ERROR);
					$check=false;
				}
			}

			if (!is_writeable($mail_config['dkim_path'])) {
				$app->log('DKIM Path '.$mail_config['dkim_path'].' not writeable.', LOGLEVEL_ERROR);
				$check=false;
			}

			if ( !$app->system->checkpath($mail_config['dkim_path']) ) {
				$app->log('DKIM Path '.$mail_config['dkim_path'].' failed in checkpath.', LOGLEVEL_ERROR);
				$check = false;
			}

		} else {
			$app->log('Unable to write DKIM settings - no or invalid DKIM-Path defined', LOGLEVEL_ERROR);
			$check=false;
		}
		return $check;
	}

	/**
	 * This function restarts amavis
	 */
    private function restart_amavis() {
        global $app;
		$output = null;
		$initcommand = $app->system->getinitcommand(array('amavis', 'amavisd'), 'restart');
		$app->log('Restarting amavis: '.$initcommand.'.', LOGLEVEL_DEBUG);
		exec($initcommand, $output);
		foreach($output as $logline) {
			$app->log($logline, LOGLEVEL_DEBUG);
		}
    }

	/**
	 * This function writes the keyfiles (public and private)
	 * @param string $key_file full path to the key-file
	 * @param string $key_value private-key
	 * @param string $key_domain mail-domain
	 * @return bool - true when the private key was written to disk
	 */
	private function write_dkim_key($key_file, $key_value, $key_domain) {
		global $app;
		$success=false;
		if ($key_file == '' || $key_value  == '' || $key_domain == '') {
			$app->log('DKIM internal error for domain '.$key_domain, LOGLEVEL_ERROR);
			return $success;
		}
		if ( $app->system->file_put_contents($key_file.'.private', $key_value) ) {
			$app->log('Saved DKIM Private-key to '.$key_file.'.private', LOGLEVEL_DEBUG);
			$success=true;
			$pubkey = null;
			$result = 0;
			/* now we get the DKIM Public-key */
			$app->system->exec_safe('cat ?|openssl rsa -pubout 2> /dev/null', $key_file.'.private');
			$pubkey = $app->system->last_exec_out();
			$public_key='';
			foreach($pubkey as $values) {
				$public_key = $public_key . $values . "\n";
			}
			/* save the DKIM Public-key in dkim-dir */
			if($app->system->file_put_contents($key_file.'.public', $public_key)) {
				$app->log('Saved DKIM Public to '.$key_domain.'.', LOGLEVEL_DEBUG);
			} else {
				$app->log('Unable to save DKIM Public to '.$key_domain.'.', LOGLEVEL_DEBUG);
			}
		} else {
			$app->log('Unable to save DKIM Private-key to '.$key_file.'.private', LOGLEVEL_ERROR);
		}
		return $success;
	}

	/**
	 * This function removes the keyfiles
	 * @param string $key_file full path to the key-file
	 * @param string $key_domain mail-domain
	 */
	private function remove_dkim_key($key_file, $key_domain) {
		global $app;
		if (file_exists($key_file.'.private')) {
			$app->system->unlink($key_file.'.private');
			$app->log('Deleted the DKIM Private-key for '.$key_domain.'.', LOGLEVEL_DEBUG);
		} else {
			$app->log('Unable to delete the DKIM Private-key for '.$key_domain.' (not found).', LOGLEVEL_DEBUG);
		}
		if (file_exists($key_file.'.public')) {
			$app->system->unlink($key_file.'.public');
			$app->log('Deleted the DKIM Public-key for '.$key_domain.'.', LOGLEVEL_DEBUG);
		} else {
			$app->log('Unable to delete the DKIM Public-key for '.$key_domain.' (not found).', LOGLEVEL_DEBUG);
		}
	}

	/**
	 * This function adds the entry to the amavisd-config
	 * @param string $key_domain mail-domain
	 */
	private function add_to_amavis($key_domain, $selector, $old_selector) {
		global $app, $mail_config;

		if (empty($selector)) {
			$selector = 'default';
		}
		$restart = false;
		$amavis_configfile = $this->get_amavis_config();

		$search_regex = "/(\n|\r)?dkim_key\(\'".$key_domain."\',\ \'(".$selector."|".$old_selector."){1}?\'.*/";

		//* If we are using seperate config-files (no faked 60-dkim) with amavis remove existing keys from 50-user to avoid duplicate keys
		if (substr_compare($amavis_configfile, '60-dkim', -7) === 0) {
			$temp_configfile = str_replace('60-dkim', '50-user', $amavis_configfile);
			if(file_exists($temp_configfile)) {
				$temp_config = $app->system->file_get_contents($temp_configfile, true);
				if (preg_match($search_regex, $temp_config)) {
					$temp_config = preg_replace($search_regex, '', $temp_config)."\n";
					$app->system->file_put_contents($temp_configfile, $temp_config, true);
				}
				unset($temp_config);
			}
			unset($temp_configfile);
		}

		$key_value="dkim_key('".$key_domain."', '".$selector."', '".$mail_config['dkim_path']."/".$key_domain.".private');\n";
		$amavis_config = $app->system->file_get_contents($amavis_configfile, true);
		$amavis_config = preg_replace($search_regex, '', $amavis_config).$key_value;

		if ( $app->system->file_put_contents($amavis_configfile, $amavis_config, true) ) {
			$app->log('Adding DKIM Private-key to amavis-config.', LOGLEVEL_DEBUG);
			$restart = true;
		} else {
			$app->log('Unable to add DKIM Private-key for '.$key_domain.' to amavis-config.', LOGLEVEL_ERROR);
		}

		return $restart;
	}

	/**
	 * This function removes the entry from the amavisd-config
	 * @param string $key_domain mail-domain
	 */
	private function remove_from_amavis($key_domain) {
		global $app;

		$restart = false;
		$amavis_configfile = $this->get_amavis_config();
		$amavis_config = $app->system->file_get_contents($amavis_configfile, true);

		$search_regex = "/(\n|\r)?dkim_key.*".$key_domain.".*(\n|\r)?/";

		if (preg_match($search_regex, $amavis_config)) {
			$amavis_config = preg_replace($search_regex, '', $amavis_config);
			$app->system->file_put_contents($amavis_configfile, $amavis_config, true);
			$app->log('Deleted the DKIM settings from amavis-config for '.$key_domain.'.', LOGLEVEL_DEBUG);
			$restart = true;
		}

		//* If we are using seperate config-files (no faked 60-dkim) with amavis remove existing keys from 50-user to avoid duplicate keys
		if (substr_compare($amavis_configfile, '60-dkim', -7) === 0) {
			$temp_configfile = str_replace('60-dkim', '50-user', $amavis_configfile);
			if(file_exists($temp_configfile)) {
				$temp_config = $app->system->file_get_contents($temp_configfile, true);
				if (preg_match($search_regex, $temp_config)) {
					$temp_config = preg_replace($search_regex, '', $temp_config);
					$app->system->file_put_contents($temp_configfile, $temp_config, true);
					$restart = true;
				}
				unset($temp_configfile);
				unset($temp_config);
			}
		}

		return $restart;
	}

	/**
	 * This function controlls new key-files and amavisd-entries
	 * @param array $data mail-settings
	 */
	private function add_dkim($data) {
		global $app, $conf;
		if ($data['new']['active'] == 'y') {
			$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
			if ( substr($mail_config['dkim_path'], strlen($mail_config['dkim_path'])-1) == '/' ) {
				$mail_config['dkim_path'] = substr($mail_config['dkim_path'], 0, strlen($mail_config['dkim_path'])-1);
			}
			if ($this->write_dkim_key($mail_config['dkim_path']."/".$data['new']['domain'], $data['new']['dkim_private'], $data['new']['domain'])) {
				if($mail_config['content_filter'] == 'rspamd') {
					$app->system->replaceLine('/etc/rspamd/local.d/dkim_domains.map', 'REGEX:/^' . preg_quote($data['new']['domain'], '/') . ' /', $data['new']['domain'] . ' ' . $mail_config['dkim_path']."/".$data['new']['domain'] . '.private');
					$app->system->replaceLine('/etc/rspamd/local.d/dkim_selectors.map', 'REGEX:/^' . preg_quote($data['new']['domain'], '/') . ' /', $data['new']['domain'] . ' ' . $data['new']['dkim_selector']);
					
					$app->services->restartServiceDelayed('rspamd', 'reload');
				} elseif ($this->add_to_amavis($data['new']['domain'], $data['new']['dkim_selector'], $data['old']['dkim_selector'] )) {
					$this->restart_amavis();
				} else {
					$this->remove_dkim_key($mail_config['dkim_path']."/".$data['new']['domain'], $data['new']['domain']);
				}
			} else {
				$app->log('Error saving the DKIM Private-key for '.$data['new']['domain'].' - DKIM is not enabled for the domain.', LOGLEVEL_DEBUG);
			}
		}
	}

	/**
	 * This function controlls the removement of keyfiles (public and private)
	 * and the entry in the amavisd-config
	 * @param array $_data mail-settings
	 */
	private function remove_dkim($_data) {
		global $app, $conf;
		$mail_config = $app->getconf->get_server_config($conf['server_id'], 'mail');
		if ( substr($mail_config['dkim_path'], strlen($mail_config['dkim_path'])-1) == '/' ) {
			$mail_config['dkim_path'] = substr($mail_config['dkim_path'], 0, strlen($mail_config['dkim_path'])-1);
		}
		$this->remove_dkim_key($mail_config['dkim_path']."/".$_data['domain'], $_data['domain']);
		
		if($mail_config['content_filter'] == 'rspamd') {
			$app->system->removeLine('/etc/rspamd/local.d/dkim_domains.map', 'REGEX:/^' . preg_quote($_data['domain'], '/') . ' /');
			$app->system->removeLine('/etc/rspamd/local.d/dkim_selectors.map', 'REGEX:/^' . preg_quote($_data['domain'], '/') . ' /');
			$app->services->restartServiceDelayed('rspamd', 'reload');
		} elseif ($this->remove_from_amavis($_data['domain'])) {
			$this->restart_amavis();
		}
	}

	/**
	 * Function called by onLoad
	 * deletes dkim-keys
	 */
	public function domain_dkim_delete($event_name, $data) {
		if (isset($data['old']['dkim']) && $data['old']['dkim'] == 'y' && $data['old']['active'] == 'y') {
			$this->remove_dkim($data['old']);
		}
	}

	/**
	 * Function called by onLoad
	 * insert dkim-keys
	 */
	public function domain_dkim_insert($event_name, $data) {
		if (isset($data['new']['dkim']) && $data['new']['dkim']=='y' && $this->check_system()) {
			$this->add_dkim($data);
		}
	}

	/**
	 * Function called by onLoad
	 * chang dkim-settings
	 */
	public function domain_dkim_update($event_name, $data) {
		global $app;
		if($data['new']['dkim'] == 'y' || $data['old']['dkim'] == 'y'){
			if ($this->check_system()) {
				/* maildomain disabled */
				if ($data['new']['active'] == 'n' && $data['old']['active'] == 'y' && $data['new']['dkim']=='y') {
					$app->log('Maildomain '.$data['new']['domain'].' disabled - remove DKIM-settings', LOGLEVEL_DEBUG);
					$this->remove_dkim($data['new']);
				}
				/* maildomain re-enabled */
				if ($data['new']['active'] == 'y' && $data['old']['active'] == 'n' && $data['new']['dkim']=='y') {
					$this->add_dkim($data);
				}
				
				/* maildomain active - only dkim changes */
				if ($data['new']['active'] == 'y' && $data['old']['active'] == 'y') {
					/* dkim disabled */
					if ($data['new']['dkim'] != $data['old']['dkim'] && $data['new']['dkim'] == 'n') {
						$this->remove_dkim($data['new']);
					}
					/* dkim enabled
					 * or new private-key
					 * or new selector
					 * or new domain-name
					 */
					elseif (
							($data['new']['dkim'] != $data['old']['dkim'] && $data['new']['dkim'] == 'y')
							|| ($data['new']['dkim_private'] != $data['old']['dkim_private'] && $data['new']['dkim'] == 'y')
							|| ($data['new']['dkim_selector'] != $data['old']['dkim_selector'] && $data['new']['dkim'] == 'y')
							|| ($data['new']['domain'] != $data['old']['domain'])
							) {
						if ($data['new']['domain'] != $data['old']['domain']) {
							$this->remove_dkim($data['old']);
						}
						$this->add_dkim($data);
					}
					/* resync */
					elseif($data['new'] == $data['old'] && $data['new']['dkim']=='y') {
						$this->add_dkim($data);
					}
				}
			}
		}
	}
}
