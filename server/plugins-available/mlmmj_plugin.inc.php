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
	const ML_ALIAS     = 0;
	const ML_TRANSPORT = 1;
	const ML_VIRTUAL   = 2;

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
		$app->plugins->registerEvent('mail_mailinglist_insert', 'mlmmj_plugin', 'insert');
		$app->plugins->registerEvent('mail_mailinglist_update', 'mlmmj_plugin', 'update');
		$app->plugins->registerEvent('mail_mailinglist_delete', 'mlmmj_plugin', 'delete');
	}

	function insert($event_name, $data) {
		global $app;

		/*[new] => Array
        (
            [mailinglist_id] => 8
            [sys_userid] => 1
            [sys_groupid] => 26
            [sys_perm_user] => riud
            [sys_perm_group] => ru
            [sys_perm_other] =>
            [server_id] => 0
            [domain] => 10100.to
            [listname] => merda
            [email] => michele@10100.to
            [password] => vbhXvWMK!1
        )*/

		$mlConf = $this->getMlConfig();
		$listDomain     = $data['new']['domain'];
		$listName = $data['new']['listname'];
		$listDir  = $mlConf['spool_dir']."/$listDomain/$listName";
		$lang     = 'en';
		$owner    = $data['new']['email'];

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

		// Saving ML base data
		file_put_contents("$listDir/control/owner", $owner);
		file_put_contents("$listDir/control/listaddress", "$listName@$listDomain");

		// Copying language translations
		if(!is_dir("/usr/share/mlmmj/text.skel/$lang")) $lang = 'en';
		foreach (glob("/usr/share/mlmmj/text.skel/$lang/*") as $filename)
			copy($filename, "$listDir/text/".basename($filename));

		// The mailinglist directory have to be owned by the user running the mailserver
		$this->chmodR($listDir);

		// Creating alias entry
		$this->addMapEntry("$listName:  \"|/usr/bin/mlmmj-recieve -L $listDir/\"", self::ML_ALIAS);

		// Creating transport entry
		$this->addMapEntry("$listDomain--$listName@localhost.mlmmj   mlmmj:$listDomain/$listName", self::ML_TRANSPORT);

		// Creating virtual entry
		$this->addMapEntry("$listName@$listDomain    $listDomain--$listName@localhost.mlmmj", self::ML_VIRTUAL);

		$mlmmjmaintd='/usr/bin/mlmmj-maintd';
// CRONENTRY="0 */2 * * * \"$MLMMJMAINTD -F -L $SPOOLDIR/$FQDN/$LISTNAME/\""

// 		/usr/sbin/postfix reload
		$app->db->query("UPDATE mail_mailinglist SET password = '' WHERE mailinglist_id = ".$app->db->quote($data["new"]['mailinglist_id']));
	}

	// The purpose of this plugin is to rewrite the main.cf file
	function update($event_name, $data) {
		global $app, $conf;

// 		$this->update_config();
//
// 		if($data["new"]["password"] != $data["old"]["password"] && $data["new"]["password"] != '') {
// 			exec("nohup /usr/lib/mailman/bin/change_pw -l ".escapeshellcmd($data["new"]["listname"])." -p ".escapeshellcmd($data["new"]["password"])." >/dev/null 2>&1 &");
// 			exec('nohup '.$conf['init_scripts'] . '/' . 'mailman reload >/dev/null 2>&1 &');
// 			$app->db->query("UPDATE mail_mailinglist SET password = '' WHERE mailinglist_id = ".$app->db->quote($data["new"]['mailinglist_id']));
// 		}
//
// 		if(is_file('/var/lib/mailman/data/virtual-mailman')) exec('postmap /var/lib/mailman/data/virtual-mailman');
// 		if(is_file('/var/lib/mailman/data/transport-mailman')) exec('postmap /var/lib/mailman/data/transport-mailman');
	}

	function delete($event_name, $data) {
		global $app;

		$mlConf = $this->getMlConfig();
		$listDomain     = $data['old']['domain'];
		$listName = $data['old']['listname'];
		$listDir  = $mlConf['spool_dir']."/$listDomain/$listName";
		$lang     = 'en';
		$owner    = $data['old']['email'];

		// Removing alias entry
		$this->delMapEntry("$listName:  \"|/usr/bin/mlmmj-recieve -L $listDir/\"", self::ML_ALIAS);

		// Removing transport entry
		$this->delMapEntry("$listDomain--$listName@localhost.mlmmj   mlmmj:$listDomain/$listName", self::ML_TRANSPORT);

		// Removing virtual entry
		$this->delMapEntry("$listName@$listDomain    $listDomain--$listName@localhost.mlmmj", self::ML_VIRTUAL);


		$this->rmdirR($listDir);

		// Remove parent folder if is empty (means there aren't other ML for the domain)
		@rmdir($mlConf['spool_dir']."/$listDomain");

		$mlmmjmaintd='/usr/bin/mlmmj-maintd';
// CRONENTRY="0 */2 * * * \"$MLMMJMAINTD -F -L $SPOOLDIR/$FQDN/$LISTNAME/\""
	}

	private function getMlConfig() {
		$mlConfig = parse_ini_file($this->mlmmj_config_dir.'mlmmj.conf');

		// Force PHP7 to use # to mark comments
		if(PHP_MAJOR_VERSION >= 7)
			$mlConfig = array_filter($mlConfig, function($v){return(substr($v,0,1)!=='#');}, ARRAY_FILTER_USE_KEY);

		return $mlConfig;
	}

	private function chmodR($dir) {
		if($objs = glob($dir."/*")) {
			foreach($objs as $obj) {
				chown($obj, 'mlmmj');
				chgrp($obj, 'mlmmj');
				if(is_dir($obj)) $this->chmodR($obj);
			}
		}

		return chown($dir, 'mlmmj') && chgrp($dir, 'mlmmj');
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
