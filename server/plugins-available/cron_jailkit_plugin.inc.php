<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
Modified 2009, Marius Cramer, pixcept KG
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

class cron_jailkit_plugin {

	//* $plugin_name and $class_name have to be the same then the name of this class
	var $plugin_name = 'cron_jailkit_plugin';
	var $class_name = 'cron_jailkit_plugin';

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

		$app->plugins->registerEvent('cron_insert', $this->plugin_name, 'insert');
		$app->plugins->registerEvent('cron_update', $this->plugin_name, 'update');
		$app->plugins->registerEvent('cron_delete', $this->plugin_name, 'delete');

	}

	//* This function is called, when a cron job is inserted in the database
	function insert($event_name, $data) {
		global $app, $conf;

		if($data["new"]["parent_domain_id"] == '') {
			$app->log("Parent domain not set", LOGLEVEL_WARN);
			return 0;
		}

		//* get data from web
		$parent_domain = $app->db->queryOneRecord("SELECT * FROM `web_domain` WHERE `domain_id` = ?", $data["new"]["parent_domain_id"]);
		if(!$parent_domain["domain_id"]) {
			$app->log("Parent domain not found", LOGLEVEL_WARN);
			return 0;
		}

		if(!$app->system->is_allowed_user($parent_domain['system_user'], true, true)
			|| !$app->system->is_allowed_group($parent_domain['system_group'], true, true)) {
			$app->log("Websites (and Crons) cannot be owned by the root user or group.", LOGLEVEL_WARN);
			return false;
		}


		$this->parent_domain = $parent_domain;

		$app->uses('system');

		if($app->system->is_user($parent_domain['system_user'])) {

			/**
			 * Setup Jailkit Chroot System If Enabled
			 */


			if ($data['new']['type'] == "chrooted")
			{
				// load the server configuration options
				$app->uses("getconf");
				$this->data = $data;
				$this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');
				foreach (array('jailkit_chroot_app_sections', 'jailkit_chroot_app_programs') as $section) {
					if (isset($parent_domain[$section]) && $parent_domain[$section] != '' ) {
						$this->jailkit_config[$section] = $parent_domain[$section];
					}
				}

				$this->_update_website_security_level();

				$app->system->web_folder_protection($parent_domain['document_root'], false);

				$this->_setup_jailkit_chroot();

				$this->_add_jailkit_user();

				$command .= 'usermod -U ? 2>/dev/null';
				$app->system->exec_safe($command, $parent_domain["system_user"]);

				$this->_update_website_security_level();

				$app->system->web_folder_protection($parent_domain['document_root'], true);
			}

			$app->log("Jailkit Plugin (Cron) -> insert username:".$parent_domain['system_user'], LOGLEVEL_DEBUG);

		} else {
			$app->log("Jailkit Plugin (Cron) -> insert username:".$parent_domain['system_user']." skipped, the user does not exist.", LOGLEVEL_WARN);
		}

	}

	//* This function is called, when a cron job is updated in the database
	function update($event_name, $data) {
		global $app, $conf;

		if($data["new"]["parent_domain_id"] == '') {
			$app->log("Parent domain not set", LOGLEVEL_WARN);
			return 0;
		}
		//* get data from web
		$parent_domain = $app->db->queryOneRecord("SELECT * FROM `web_domain` WHERE `domain_id` = ?", $data["new"]["parent_domain_id"]);
		if(!$parent_domain["domain_id"]) {
			$app->log("Parent domain not found", LOGLEVEL_WARN);
			return 0;
		}
		if(!$app->system->is_allowed_user($parent_domain['system_user'], true, true)
			|| !$app->system->is_allowed_group($parent_domain['system_group'], true, true)) {
			$app->log("Websites (and Crons) cannot be owned by the root user or group.", LOGLEVEL_WARN);
			return false;
		}

		$app->uses('system');

		$this->parent_domain = $parent_domain;

		if($app->system->is_user($parent_domain['system_user'])) {



			/**
			 * Setup Jailkit Chroot System If Enabled
			 */
			if ($data['new']['type'] == "chrooted")
			{
				$app->log("Jailkit Plugin (Cron) -> setting up jail", LOGLEVEL_DEBUG);
				// load the server configuration options
				$app->uses("getconf");
				$this->data = $data;
				$this->jailkit_config = $app->getconf->get_server_config($conf["server_id"], 'jailkit');
				foreach (array('jailkit_chroot_app_sections', 'jailkit_chroot_app_programs') as $section) {
					if (isset($parent_domain[$section]) && $parent_domain[$section] != '' ) {
						$this->jailkit_config[$section] = $parent_domain[$section];
					}
				}

				$this->_update_website_security_level();

				$app->system->web_folder_protection($parent_domain['document_root'], false);

				$this->_setup_jailkit_chroot();

				$this->_add_jailkit_user();

				$this->_update_website_security_level();

				$app->system->web_folder_protection($parent_domain['document_root'], true);
			}

			$app->log("Jailkit Plugin (Cron) -> update username:".$parent_domain['system_user'], LOGLEVEL_DEBUG);

		} else {
			$app->log("Jailkit Plugin (Cron) -> update username:".$parent_domain['system_user']." skipped, the user does not exist.", LOGLEVEL_WARN);
		}

	}

	//* This function is called, when a cron job is deleted in the database
	function delete($event_name, $data) {
		global $app, $conf;

		if($data["old"]["parent_domain_id"] == '') {
			$app->log("Parent domain not set", LOGLEVEL_WARN);
			return 0;
		}

		$app->uses('system');

		if ($data['old']['type'] == "chrooted")
		{
			$parent_domain = $app->db->queryOneRecord("SELECT * FROM `web_domain` WHERE `domain_id` = ?", $data['old']['parent_domain_id']);

			// should copy some _delete_homedir() functionality from shelluser_jailkit_plugin ?

			if (isset($parent_domain['delete_unused_jailkit']) && $parent_domain['delete_unused_jailkit'] == 'y') {
				$app->system->web_folder_protection($parent_domain['document_root'], false);
				$this->_delete_jailkit_if_unused($parent_domain['domain_id']);
				$app->system->web_folder_protection($parent_domain['document_root'], true);
			}
		}
	}

	function _setup_jailkit_chroot()
	{
		global $app;

		if (isset($this->jailkit_config) && isset($this->jailkit_config['jailkit_hardlinks'])) {
			if ($this->jailkit_config['jailkit_hardlinks'] == 'yes') {
				$options = array('hardlink');
			} elseif ($this->jailkit_config['jailkit_hardlinks'] == 'no') {
				$options = array();
			}
		} else {
			$options = array('allow_hardlink');
		}

		$last_updated = preg_split('/[\s,]+/', $this->jailkit_config['jailkit_chroot_app_sections']
						  .' '.$this->jailkit_config['jailkit_chroot_app_programs']
						  .' '.$this->jailkit_config['jailkit_chroot_cron_programs']);
		$last_updated = array_unique($last_updated, SORT_REGULAR);
		sort($last_updated, SORT_STRING);
		$update_hash = hash('md5', implode(' ', $last_updated));

		// should move return here if $update_hash == $parent_domain['last_jailkit_hash'] ?

		// check if the chroot environment is created yet if not create it with a list of program sections from the config
		if (!is_dir($this->parent_domain['document_root'].'/etc/jailkit'))
		{
			$app->system->create_jailkit_chroot($this->parent_domain['document_root'], $this->jailkit_config['jailkit_chroot_app_sections'], $options);
			$app->log("Added jailkit chroot", LOGLEVEL_DEBUG);

			$this->_add_jailkit_programs($options);

			$app->load('tpl');

			$tpl = new tpl();
			$tpl->newTemplate("bash.bashrc.master");

			$tpl->setVar('jailkit_chroot', true);
			$tpl->setVar('domain', $this->parent_domain['domain']);
			$tpl->setVar('home_dir', $this->_get_home_dir(""));

			$bashrc = $this->parent_domain['document_root'].'/etc/bash.bashrc';
			if(@is_file($bashrc) || @is_link($bashrc)) unlink($bashrc);

			$app->system->file_put_contents($bashrc, $tpl->grab());
			unset($tpl);

			$app->log('Added bashrc script: '.$bashrc, LOGLEVEL_DEBUG);

			$tpl = new tpl();
			$tpl->newTemplate('motd.master');

			$tpl->setVar('domain', $this->parent_domain['domain']);

			$motd = $this->parent_domain['document_root'].'/var/run/motd';
			if(@is_file($motd) || @is_link($motd)) unlink($motd);

			$app->system->file_put_contents($motd, $tpl->grab());

		} else {
			// force update existing jails
			$options[] = 'force';

			$sections = $this->jailkit_config['jailkit_chroot_app_sections'];
			$programs = $this->jailkit_config['jailkit_chroot_app_programs'] . ' '
				  . $this->jailkit_config['jailkit_chroot_cron_programs'];

			if ($update_hash == $parent_domain['last_jailkit_hash']) {
				return;
			}

			$records = $app->db->queryAllRecords('SELECT web_folder FROM `web_domain` WHERE `parent_domain_id` = ? AND `document_root` = ? AND web_folder != \'\' AND web_folder IS NOT NULL AND `server_id` = ?', $this->parent_domain['domain_id'], $this->parent_domain['document_root'], $conf['server_id']);
			foreach ($records as $record) {
				$options[] = 'skip='.$record['web_folder'];
			}

			$app->system->update_jailkit_chroot($this->parent_domain['document_root'], $sections, $programs, $options);
		}

		// this gets last_jailkit_update out of sync with master db, but that is ok,
		// as it is only used as a timestamp to moderate the frequency of updating on the slaves
		$app->db->query("UPDATE `web_domain` SET `last_jailkit_update` = NOW(), `last_jailkit_hash` = ? WHERE `document_root` = ?", $update_hash, $this->parent_domain['document_root']);
	}

	function _add_jailkit_programs($opts=array())
	{
		global $app;

		//copy over further programs and its libraries
		$app->system->create_jailkit_programs($this->parent_domain['document_root'], $this->jailkit_config['jailkit_chroot_app_programs'], $opts);
		$app->log("Added app programs to jailkit chroot", LOGLEVEL_DEBUG);

		$app->system->create_jailkit_programs($this->parent_domain['document_root'], $this->jailkit_config['jailkit_chroot_cron_programs'], $opts);
		$app->log("Added cron programs to jailkit chroot", LOGLEVEL_DEBUG);
	}

	function _add_jailkit_user()
	{
		global $app;

		// add the user to the chroot
		$jailkit_chroot_userhome = $this->_get_home_dir($this->parent_domain['system_user']);

		if(!is_dir($this->parent_domain['document_root'].'/etc')) $app->system->mkdir($this->parent_domain['document_root'].'/etc', 0755, true);
		if(!is_file($this->parent_domain['document_root'].'/etc/passwd')) $app->system->exec_safe('touch ?', $this->parent_domain['document_root'].'/etc/passwd');

		// IMPORTANT!
		// ALWAYS create the user. Even if the user was created before
		// if we check if the user exists, then a update (no shell -> jailkit) will not work
		// and the user has FULL ACCESS to the root of the server!
		$app->system->create_jailkit_user($this->parent_domain['system_user'], $this->parent_domain['document_root'], $jailkit_chroot_userhome);

		if(!is_dir($this->parent_domain['document_root'].$jailkit_chroot_userhome)) {
			$app->system->mkdir($this->parent_domain['document_root'].$jailkit_chroot_userhome, 0750, true);
		}

		$app->system->chown($this->parent_domain['document_root'].$jailkit_chroot_userhome, $this->parent_domain['system_user']);
		$app->system->chgrp($this->parent_domain['document_root'].$jailkit_chroot_userhome, $this->parent_domain['system_group']);

	}

	function _get_home_dir($username)
	{
		return str_replace("[username]", $username, $this->jailkit_config["jailkit_chroot_home"]);
	}

	//* Update the website root directory permissions depending on the security level
	function _update_website_security_level() {
		global $app, $conf;

		// load the server configuration options
		$app->uses("getconf");
		$web_config = $app->getconf->get_server_config($conf["server_id"], 'web');

		// Get the parent website of this shell user
		$web = $app->db->queryOneRecord("SELECT * FROM web_domain WHERE domain_id = ?", $this->data['new']['parent_domain_id']);

		//* If the security level is set to high
		if($web_config['security_level'] == 20 && is_array($web)) {
			$app->system->web_folder_protection($web["document_root"], false);
			$app->system->chmod($web["document_root"], 0755);
			$app->system->chown($web["document_root"], 'root');
			$app->system->chgrp($web["document_root"], 'root');
			$app->system->web_folder_protection($web["document_root"], true);
		}
	}

	private function _delete_jailkit_if_unused($parent_domain_id) {
		global $app, $conf;

		// get jail directory
		$parent_domain = $app->db->queryOneRecord("SELECT * FROM `web_domain` WHERE `domain_id` = ? OR `parent_domain_id` = ? AND `document_root` IS NOT NULL", $parent_domain_id, $parent_domain_id);
		if (!is_dir($parent_domain['document_root'])) {
			return;
		}

		// chroot is used by php-fpm
		if (isset($parent_domain['php_fpm_chroot']) && $parent_domain['php_fpm_chroot'] == 'y') {
			return;
		}

		// check for any shell_user using this jail
		$inuse = $app->db->queryOneRecord('SELECT shell_user_id FROM `shell_user` WHERE `parent_domain_id` = ? AND `chroot` = ?', $parent_domain_id, 'jailkit');
		if($inuse) {
			return;
		}

		// check for any cron job using this jail
		$inuse = $app->db->queryOneRecord('SELECT id FROM `cron` WHERE `parent_domain_id` = ? AND `type` = ?', $parent_domain_id, 'chrooted');
		if($inuse) {
			return;
		}

		$options = array();
		$records = $app->db->queryAllRecords('SELECT web_folder FROM `web_domain` WHERE `parent_domain_id` = ? AND `document_root` = ? AND web_folder != \'\' AND web_folder IS NOT NULL AND `server_id` = ?', $parent_domain_id, $parent_domain['document_root'], $conf['server_id']);
		foreach ($records as $record) {
			$options[] = 'skip='.$record['web_folder'];
		}

		$app->system->delete_jailkit_chroot($parent_domain['document_root'], $options);

		// this gets last_jailkit_update out of sync with master db, but that is ok,
		// as it is only used as a timestamp to moderate the frequency of updating on the slaves
		$app->db->query("UPDATE `web_domain` SET `last_jailkit_update` = NOW(), `last_jailkit_hash` = NULL WHERE `document_root` = ?", $parent_domain['document_root']);
	}

} // end class

