<?php

/*
Copyright (c) 2007, Till Brehm, projektfarm Gmbh
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

class mailman_plugin {

	var $plugin_name = 'mailman_plugin';
	var $class_name = 'mailman_plugin';


	var $mailman_config_dir = '/etc/mailman/';

	//* This function is called during ispconfig installation to determine
	//  if a symlink shall be created for this plugin.
	function onInstall() {
		global $conf;

		if($conf['services']['mail'] == true) {
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

		$app->plugins->registerEvent('mail_mailinglist_insert', 'mailman_plugin', 'insert');
		$app->plugins->registerEvent('mail_mailinglist_update', 'mailman_plugin', 'update');
		$app->plugins->registerEvent('mail_mailinglist_delete', 'mailman_plugin', 'delete');



	}

	function insert($event_name, $data) {
		global $app, $conf;

		$this->update_config();

		$pid = $app->system->exec_safe("nohup /usr/lib/mailman/bin/newlist -u ? -e ? ? ? ? >/dev/null 2>&1 & echo $!;", $data["new"]["domain"], $data["new"]["domain"], $data["new"]["listname"], $data["new"]["email"], $data["new"]["password"]);
		// wait for /usr/lib/mailman/bin/newlist-call
		$running = true;
		do {
			exec('ps -p '.intval($pid), $out);
			if (count($out) ==1) $running=false; else sleep(1);
			unset($out);
		} while ($running);
		unset($out);
		if(is_file('/etc/mailman/virtual-mailman') && !is_link('/var/lib/mailman/data/virtual-mailman')) {
			symlink('/etc/mailman/virtual-mailman','/var/lib/mailman/data/virtual-mailman');
		}
		if(is_file('/var/lib/mailman/data/virtual-mailman')) exec('postmap /var/lib/mailman/data/virtual-mailman');
		if(is_file('/var/lib/mailman/data/transport-mailman')) exec('postmap /var/lib/mailman/data/transport-mailman');
		
		exec('nohup '.$conf['init_scripts'] . '/' . 'mailman reload >/dev/null 2>&1 &');
		
		// Fix list URL
		$app->system->exec_safe('/usr/sbin/withlist -l -r fix_url ?', $data["new"]["listname"]);

		$app->db->query("UPDATE mail_mailinglist SET password = '' WHERE mailinglist_id = ?", $data["new"]['mailinglist_id']);

	}

	// The purpose of this plugin is to rewrite the main.cf file
	function update($event_name, $data) {
		global $app, $conf;
		
		$this->update_config();

		if($data["new"]["password"] != $data["old"]["password"] && $data["new"]["password"] != '') {
			$app->system->exec_safe("nohup /usr/lib/mailman/bin/change_pw -l ? -p ? >/dev/null 2>&1 &", $data["new"]["listname"], $data["new"]["password"]);
			exec('nohup '.$conf['init_scripts'] . '/' . 'mailman reload >/dev/null 2>&1 &');
			$app->db->query("UPDATE mail_mailinglist SET password = '' WHERE mailinglist_id = ?", $data["new"]['mailinglist_id']);
		}
		
		if(is_file('/var/lib/mailman/data/virtual-mailman')) exec('postmap /var/lib/mailman/data/virtual-mailman');
		if(is_file('/var/lib/mailman/data/transport-mailman')) exec('postmap /var/lib/mailman/data/transport-mailman');
	}

	function delete($event_name, $data) {
		global $app, $conf;

		$this->update_config();

		$app->system->exec_safe("nohup /usr/lib/mailman/bin/rmlist -a ? >/dev/null 2>&1 &", $data["old"]["listname"]);

		exec('nohup '.$conf['init_scripts'] . '/' . 'mailman reload >/dev/null 2>&1 &');
		
		if(is_file('/var/lib/mailman/data/virtual-mailman')) exec('postmap /var/lib/mailman/data/virtual-mailman');
		if(is_file('/var/lib/mailman/data/transport-mailman')) exec('postmap /var/lib/mailman/data/transport-mailman');

	}

	function update_config() {
		global $app, $conf;

		copy($this->mailman_config_dir.'mm_cfg.py', $this->mailman_config_dir.'mm_cfg.py~');

		// load the server configuration options
		$app->uses('getconf');
		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');

		// load files
		if(file_exists($conf["rootpath"]."/conf-custom/mm_cfg.py.master")) {
			$content = file_get_contents($conf["rootpath"]."/conf-custom/mm_cfg.py.master");
		} else {
			$content = file_get_contents($conf["rootpath"]."/conf/mm_cfg.py.master");
		}
		$old_file = file_get_contents($this->mailman_config_dir."/mm_cfg.py");

		$old_options = array();
		$lines = explode("\n", $old_file);
		foreach ($lines as $line)
		{
			if (strlen($line) && substr($line, 0, 1) != '#')
			{
				list($key, $value) = explode("=", $line);
				if ($value && $value !== '')
				{
					$key = rtrim($key);
					$old_options[$key] = trim($value);
				}
			}
		}

		// create virtual_domains list
		$domainAll = $app->db->queryAllRecords("SELECT domain FROM mail_mailinglist GROUP BY domain");
		$virtual_domains = '';
		foreach($domainAll as $domain)
		{
			if ($domainAll[0]['domain'] == $domain['domain'])
				$virtual_domains .= "'".$domain['domain']."'";
			else
				$virtual_domains .= ", '".$domain['domain']."'";
		}

		$content = str_replace('{hostname}', $server_config['hostname'], $content);
		$content = str_replace('{default_language}', $old_options['DEFAULT_SERVER_LANGUAGE'], $content);
		$content = str_replace('{virtual_domains}', $virtual_domains, $content);

		file_put_contents($this->mailman_config_dir."/mm_cfg.py", $content);
	}

} // end class

?>
