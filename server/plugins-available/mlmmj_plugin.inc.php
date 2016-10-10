<?php

/*
Copyright (c) 2016, Michele Roncaglione Tet <michele@10100.to> 10100 Srl
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

/*
templates/mail_mailinglist_list.htm
templates/mail_mailinglist_edit.htm

list/mail_mailinglist.list.php

form/mail_mailinglist.tform.php

mailinglist.php
mail_mailinglist_list.php
mail_mailinglist_edit.php
mail_mailinglist_del.php
*/

class mlmmj_plugin {
	const ML_ALIAS       = 0;
	const ML_TRANSPORT   = 1;
	const ML_VIRTUAL     = 2;
	const SECONDS_IN_DAY = 86400;

	private $plugin_name = 'mlmmj_plugin';
	private $class_name = 'mlmmj_plugin';
	private $mlmmj_config_dir = '/etc/mlmmj/';

	/*
	 This function is called during ispconfig installation to determine
	 if a symlink shall be created for this plugin.
	 */
	function onInstall() {
		global $conf;

		if($conf['services']['mail'] == true) return true;
		else return false;
	}

	//This function is called when the plugin is loaded
	function onLoad() {
		global $app;

		// Register for the events
		$app->plugins->registerEvent('mail_mailinglist_insert', $this->plugin_name, 'list_insert');
		$app->plugins->registerEvent('mail_mailinglist_update', $this->plugin_name, 'list_update');
		$app->plugins->registerEvent('mail_mailinglist_delete', $this->plugin_name, 'list_delete');

		$app->plugins->registerEvent('mail_ml_member_insert', $this->plugin_name, 'member_insert');
		$app->plugins->registerEvent('mail_ml_member_update', $this->plugin_name, 'member_update');
		$app->plugins->registerEvent('mail_ml_member_delete', $this->plugin_name, 'member_delete');

	}

	function list_insert($event_name, $data) {
		global $app, $conf;

		$mlManager = $app->getconf->get_server_config($conf['server_id'], 'mail')['mailinglist_manager'];

		if($mlManager == 'mlmmj') {
			$mlConf = $this->getMlConfig();
			$rec = $data['new'];
			$listDomain     = $rec['domain'];
			$listName = $rec['listname'];
			$listDir  = $mlConf['spool_dir']."/$listDomain/$listName";
			$lang     = 'en';
			$owner    = $rec['email'];

			// Creating ML directories structure
			mkdir("$listDir/incoming", 0755, true);
			mkdir("$listDir/queue/discarded", 0755, true);
			mkdir("$listDir/archive", 0755, true);
			mkdir("$listDir/text", 0755, true);
			mkdir("$listDir/subconf", 0755, true);
			mkdir("$listDir/unsubconf", 0755, true);
			mkdir("$listDir/bounce", 0755, true);
			mkdir("$listDir/control", 0755, true);
			mkdir("$listDir/moderation", 0755, true);
			mkdir("$listDir/subscribers.d", 0755, true);
			mkdir("$listDir/digesters.d", 0755, true);
			mkdir("$listDir/requeue", 0755, true);
			mkdir("$listDir/nomailsubs.d", 0755, true);

			// Creating ML index file
			touch("$listDir/index");

			// Creating default control files
			// WARNING: Edit this section if default DB values will be modified!
			touch("$listDir/control/nodigestsub");
			touch("$listDir/control/noarchive");
			file_put_contents("$listDir/control/owner", $owner);
			file_put_contents("$listDir/control/listaddress", "$listName@$listDomain");
			file_put_contents("$listDir/control/prefix", "[$listName]");

			// Copying language translations
			if(!is_dir("/usr/share/mlmmj/text.skel/$lang")) $lang = 'en';
			foreach (glob("/usr/share/mlmmj/text.skel/$lang/*") as $filename)
				copy($filename, "$listDir/text/".basename($filename));

			// The mailinglist directory have to be owned by the user running the mailserver
			$this->changeOwnership($listDir);

			// Creating alias entry
			$this->addMapEntry("$listName:  \"|/usr/bin/mlmmj-recieve -L $listDir/\"", self::ML_ALIAS);

			// Creating transport entry
			$this->addMapEntry("$listDomain--$listName@localhost.mlmmj   mlmmj:$listDomain/$listName", self::ML_TRANSPORT);

			// Creating virtual entry
			$this->addMapEntry("$listName@$listDomain    $listDomain--$listName@localhost.mlmmj", self::ML_VIRTUAL);
		}
	}

	// The purpose of this plugin is to rewrite the main.cf file
	function list_update($event_name, $data) {
		global $app, $conf;

		$mlManager = $app->getconf->get_server_config($conf['server_id'], 'mail')['mailinglist_manager'];

		if($mlManager == 'mlmmj') {
			$rec = $data['new'];
			$mlConf = $this->getMlConfig();

			$controlDir  = "{$mlConf['spool_dir']}/{$rec['domain']}/{$rec['listname']}/control";

			// Doesn't matter if the list is open or close, members can ALWAYS unsubscribe
			if($rec['list_type'] == 'open') {
				switch($rec['subscribe_policy']) {
					case 'disabled':
						touch("$controlDir/closedlistsub");
						@unlink("$controlDir/submod");
						@unlink("$controlDir/nosubconfirm");
						break;
					case 'both':
						touch("$controlDir/submod");
					case 'approval':
						touch("$controlDir/nosubconfirm");
						@unlink("$controlDir/closedlistsub");
						break;
					case 'none':
						touch("$controlDir/nosubconfirm");
						@unlink("$controlDir/closedlistsub");
						break;
					case 'confirm':
						@unlink("$controlDir/nosubconfirm");
						@unlink("$controlDir/closedlistsub");
						@unlink("$controlDir/submod");
						break;
				}

				switch($rec['posting_policy']) {
					case 'closed':
						touch("$controlDir/subonlypost");
						break;
					case 'moderated':
						touch("$controlDir/modnonsubposts");
						break;
					case 'free':
						@unlink("$controlDir/modnonsubposts");
						@unlink("$controlDir/subonlypost");
						break;
				}
			} elseif($rec['list_type'] == 'closed') {
				touch("$controlDir/closedlistsub");
				touch("$controlDir/subonlypost");
				@unlink("$controlDir/modnonsubposts");
				@unlink("$controlDir/submod");
				@unlink("$controlDir/nosubconfirm");
			}

			if($rec['digestsub'] == 'y') {
				@unlink("$controlDir/nodigestsub");
				if($rec['digesttext'] == 'y') @unlink("$controlDir/nodigesttext");
				else touch("$controlDir/nodigesttext");
				file_put_contents("$controlDir/digestinterval", $rec['digestinterval']*self::SECONDS_IN_DAY);
				file_put_contents("$controlDir/digestmaxmails", $rec['digestmaxmails']);
			} else {
				touch("$controlDir/nodigestsub");
				@unlink("$controlDir/nodigesttext");
				@unlink("$controlDir/digestinterval");
				@unlink("$controlDir/digestmaxmails");
			}

			if($rec['subject_prefix'])
				file_put_contents("$controlDir/prefix", $rec['subject_prefix']);
			else @unlink("$controlDir/prefix");

			if($rec['mail_footer'])
				file_put_contents("$controlDir/footer", $rec['mail_footer']);
			else @unlink("$controlDir/footer");

			if($rec['archive'] == 'y')
				@unlink("$controlDir/noarchive");
			else
				touch("$controlDir/noarchive");

			$this->changeOwnership("$controlDir/*");
		}
	}

	function list_delete($event_name, $data) {
		global $app, $conf;

		$mlManager = $app->getconf->get_server_config($conf['server_id'], 'mail')['mailinglist_manager'];

		if($mlManager == 'mlmmj') {
			$mlConf = $this->getMlConfig();
			$rec = $data['old'];
			$listDomain     = $rec['domain'];
			$listName = $rec['listname'];
			$listDir  = $mlConf['spool_dir']."/$listDomain/$listName";

			// Remove ML directory structure
			$this->rmdirR($listDir);
			@rmdir($mlConf['spool_dir']."/$listDomain");
			
			// Removing alias entry
			$this->delMapEntry("$listName:  \"|/usr/bin/mlmmj-recieve -L $listDir/\"", self::ML_ALIAS);

			// Removing transport entry
			$this->delMapEntry("$listDomain--$listName@localhost.mlmmj   mlmmj:$listDomain/$listName", self::ML_TRANSPORT);

			// Removing virtual entry
			$this->delMapEntry("$listName@$listDomain    $listDomain--$listName@localhost.mlmmj", self::ML_VIRTUAL);
		}
	}

	function member_insert($event_name, $data) {
		global $app, $conf;

		$mlManager = $app->getconf->get_server_config($conf['server_id'], 'mail')['mailinglist_manager'];

		if($mlManager == 'mlmmj') {
			$mlConf = $this->getMlConfig();

			$rec = $data['new'];

			$sql = "SELECT * FROM mail_mailinglist WHERE mailinglist_id = ?";
			$ml = $app->db->queryOneRecord($sql, $rec['mailinglist_id']);
			if($ml['mailinglist_id']) {
				$listDomain     = $ml['domain'];
				$listName = $ml['listname'];
				$listDir  = $mlConf['spool_dir']."/$listDomain/$listName";
			}

			$welcome = ($rec['welcome_msg'] == 'y')?'-c':'';
			$command = "/usr/bin/mlmmj-sub -q -s -L $listDir $welcome -a ".$rec['email'];
			exec("nohup $command>/dev/null 2>&1");
		}
	}

	function member_update($event_name, $data) {
		//TODO: Implement something usefull on update event (e.g. change to digest subscription)
	}

	function member_delete($event_name, $data) {
		global $app, $conf;

		$mlManager = $app->getconf->get_server_config($conf['server_id'], 'mail')['mailinglist_manager'];

		if($mlManager == 'mlmmj') {
			$mlConf = $this->getMlConfig();

			$rec = $data['old'];

			$sql = "SELECT * FROM mail_mailinglist WHERE mailinglist_id = ?";
			$ml = $app->db->queryOneRecord($sql, $rec['mailinglist_id']);
			if($ml['mailinglist_id']) {
				$listDomain     = $ml['domain'];
				$listName = $ml['listname'];
				$listDir  = $mlConf['spool_dir']."/$listDomain/$listName";
			}

			$goodbye = ($rec['goodbye_msg'] == 'y')?'-c':'';
			$command = "/usr/bin/mlmmj-unsub -q -s -L $listDir $goodbye -a ".$rec['email'];
			exec("nohup $command>/dev/null 2>&1");
		}
	}

	private function getMlConfig() {
		$mlConfig = @parse_ini_file($this->mlmmj_config_dir.'mlmmj.conf');

		// Force PHP7 to use # to mark comments
		if(PHP_MAJOR_VERSION >= 7 && is_array($mlConfig))
			$mlConfig = array_filter($mlConfig, function($v){return(substr($v,0,1)!=='#');}, ARRAY_FILTER_USE_KEY);

		return $mlConfig;
	}

	private function changeOwnership($path, $recursive=true) {
		if(basename($path) == '*') $path = dirname($path);

		if(is_dir($path)) {
			if($objs = glob($path."/*")) {
				foreach($objs as $obj) {
					chown($obj, 'mlmmj');
					chgrp($obj, 'mlmmj');
					if(is_dir($obj) && $recursive) $this->changeOwnership($obj);
				}
			}
		}

		return chown($path, 'mlmmj') && chgrp($path, 'mlmmj');
	}

	private function rmdirR($path) {
		if(is_dir($path) === true) {
			$files = array_diff(scandir($path), array('.', '..'));
			foreach($files as $file) $this->rmdirR(realpath($path) . '/' . $file);

			return rmdir($path);
		} elseif(is_file($path) === true) return unlink($path);

		return false;
	}

	private function addMapEntry($directive, $type) {

		$destFile = $this->mlmmj_config_dir;
		switch($type) {
			case self::ML_ALIAS:
				$destFile .= 'aliases';
				$command = 'postalias';
				break;
			case self::ML_TRANSPORT:
				$destFile .= 'transport';
				$command = 'postmap';
				break;
			case self::ML_VIRTUAL:
				$destFile .= 'virtual';
				$command = 'postmap';
				break;
		}

		$lines = file($destFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
		$lines[] = $directive;

		file_put_contents($destFile, implode("\n", array_unique($lines)));
		exec("nohup /usr/sbin/$command $destFile >/dev/null 2>&1 &");
	}

	private function delMapEntry($directive, $type) {

		$destFile = $this->mlmmj_config_dir;
		switch($type) {
			case self::ML_ALIAS:
				$destFile .= 'aliases';
				$command = 'postalias';
				break;
			case self::ML_TRANSPORT:
				$destFile .= 'transport';
				$command = 'postmap';
				break;
			case self::ML_VIRTUAL:
				$destFile .= 'virtual';
				$command = 'postmap';
				break;
		}

		$lines = file($destFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);

		foreach(array_keys($lines, $directive) as $key) unset($lines[$key]);

		file_put_contents($destFile, implode("\n", array_unique($lines)));
		exec("nohup /usr/sbin/$command $destFile >/dev/null 2>&1 &");
	}

	private function checkSys() {
		if(!is_dir($this->mlmmj_config_dir)) mkdir($this->mlmmj_config_dir, 0755);
		if(!file_exists($this->mlmmj_config_dir.'mlmmj.conf')) {
			file_put_contents($this->mlmmj_config_dir.'mlmmj.conf', 'skel_dir = /usr/share/mlmmj/text.skel');
			file_put_contents($this->mlmmj_config_dir.'mlmmj.conf', 'spool_dir = /var/spool/mlmmj', FILE_APPEND);
		}
		if(!file_exists($this->mlmmj_config_dir.'aliases')) touch($this->mlmmj_config_dir.'aliases');
		if(!file_exists($this->mlmmj_config_dir.'transport')) touch($this->mlmmj_config_dir.'transport');
		if(!file_exists($this->mlmmj_config_dir.'virtual')) touch($this->mlmmj_config_dir.'virtual');
	}
} // end class

?>
