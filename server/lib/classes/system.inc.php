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

class system{

	var $FILE = '/root/ispconfig/scripts/lib/classes/ispconfig_system.lib.php';
	var $server_id;
	var $server_conf;
	var $data;
	
	/**
	 * Construct for this class
	 *
	 * @return system
	 */
	public function system(){
		//global $go_info;
	  	//$this->server_id = $go_info['isp']['server_id'];
	  	//$this->server_conf = $go_info['isp']['server_conf'];
	  	$this->server_conf['passwd_datei'] = '/etc/passwd';
	  	$this->server_conf['shadow_datei'] = '/etc/shadow';
	  	$this->server_conf['group_datei'] = '/etc/group';
	}
	
	/**
	 * Get the hostname from the server
	 *
	 * @return string
	 */
	public function hostname(){
		$dist = $this->server_conf['dist'];
	
	 	ob_start();
	  	passthru('hostname');
	  	$hostname = ob_get_contents();
	  	ob_end_clean();
		$hostname = trim($hostname);
	  	ob_start();
	  	if(!strstr($dist, 'freebsd')){
	    	passthru('dnsdomainname');
	  	} else {
	    	passthru('domainname');
	  	}
	  	$domainname = ob_get_contents();
	  	ob_end_clean();
	  	$domainname = trim($domainname);
	  	if($domainname != ""){
		    if(!strstr($hostname, $domainname)) $hostname .= ".".$domainname;
	  	}
	  	return $hostname;
	}
	
	/**
	 * Add an user to the system
	 * 
	 */
	public function adduser($user_username, $uid, $gid, $username, $homedir, $shell, $passwort = '*'){
		global $app;
	  	if($this->is_user($user_username)){
	    	return false;
	  	} else {
	    	if(trim($user_username) != '') {
	        	$user_datei = $this->server_conf['passwd_datei'];
	            $shadow_datei = $this->server_conf['shadow_datei'];
	            $shell = realpath($shell);
	            if(trim($passwort) == '') $passwort = '*';
	            $new_user = "\n$user_username:x:$uid:$gid:$username:$homedir:$shell\n";
	            $app->log->msg('USER: '.$new_user);
	            $app->file->af($user_datei, $new_user);
	            if($shadow_datei == '/etc/shadow'){
	            	$datum = time();
	                $tage = floor($datum/86400);
	                $new_passwd = "\n$user_username:$passwort:$tage:0:99999:7:::\n";
	            } else {
	                $new_passwd = "\n$user_username:$passwort:$uid:$gid::0:0:$username:$homedir:$shell\n";
	            }
	            $app->file->af($shadow_datei, $new_passwd);
				// TB: leere Zeilen entfernen
	            $app->file->remove_blank_lines($shadow_datei);
	            $app->file->remove_blank_lines($user_datei);
	            // TB: user Sortierung deaktiviert
	            //$this->order_users_groups();
	            if($shadow_datei != '/etc/shadow'){
	            	$app->file->af($shadow_datei, "\n");
					// TB: leere Zeilen entfernen
	                $app->file->remove_blank_lines($shadow_datei);
					$app->log->caselog("pwd_mkdb $shadow_datei &> /dev/null", $this->FILE, __LINE__);
	            }
	            return true;
	        }
	  }
	}
	
	/**
	 * Update users when someone edit it
	 *
	 */
	function updateuser($user_username, $uid, $gid, $username, $homedir, $shell, $passwort = '*'){
		//* First delete the users
		$this->deluser($user_username);
		//* Add the user again
	  	$this->adduser($user_username, $uid, $gid, $username, $homedir, $shell, $passwort);
	}
	
	/**
	 * Lock the user
	 *
	 */
	function deactivateuser($user_username){
		$passwort = str_rot13($this->getpasswd($user_username));
	  	$user_attr = $this->get_user_attributes($user_username);
	  	$uid = $user_attr['uid'];
	  	$gid = $user_attr['gid'];
	  	$username = $user_attr['name'];
	  	$homedir = $user_attr['homedir'];
	  	$shell = '/dev/null';
	  	$this->deluser($user_username);
	  	$this->adduser($user_username, $uid, $gid, $username, $homedir, $shell, $passwort);
	}
	/**
	 * Delete a user from the system
	 *
	 */
	function deluser($user_username){
		global $app;
	  	if($this->is_user($user_username)){
	    	$user_datei = $this->server_conf['passwd_datei'];
	    	$shadow_datei = $this->server_conf['shadow_datei'];
	    	$users = $app->file->rf($user_datei);
	    	$lines = explode("\n", $users);
	    	if(is_array($lines)){
	      		$num_lines = sizeof($lines);
	      		for($i=0;$i<$num_lines;$i++){
	        		if(trim($lines[$i]) != ''){
	          			list($f1,) = explode(':', $lines[$i]);
	          			if($f1 != $user_username) $new_lines[] = $lines[$i];
	        		}
	      		}
	      		$new_users = implode("\n", $new_lines);
	      		$app->file->wf($user_datei, $new_users);
	      		unset($new_lines);
	      		unset($lines);
	      		unset($new_users);
	    	}
	    	$app->file->remove_blank_lines($user_datei);
	
	    	$passwds = $app->file->rf($shadow_datei);
	    	$lines = explode("\n", $passwds);
	    	if(is_array($lines)){
	      		$num_lines = sizeof($lines);
	      		for($i=0;$i<$num_lines;$i++){
	        		if(trim($lines[$i]) != ''){
	          			list($f1,) = explode(':', $lines[$i]);
	          			if($f1 != $user_username) $new_lines[] = $lines[$i];
	        		}
	      		}
	      		$new_passwds = implode("\n", $new_lines);
	      		$app->file->wf($shadow_datei, $new_passwds);
	      		unset($new_lines);
	      		unset($lines);
	      		unset($new_passwds);
	    	}
	    	$app->file->remove_blank_lines($shadow_datei);
	
	    	$group_file = $app->file->rf($this->server_conf['group_datei']);
	    	$group_file_lines = explode("\n", $group_file);
	    	foreach($group_file_lines as $group_file_line){
	      		if(trim($group_file_line) != ''){
	        		list($f1, $f2, $f3, $f4) = explode(':', $group_file_line);
	        		$group_users = explode(',', str_replace(' ', '', $f4));
	        		if(in_array($user_username, $group_users)){
	          			$g_users = array();
	          			foreach($group_users as $group_user){
	            			if($group_user != $user_username) $g_users[] = $group_user;
	          			}
	          			$f4 = implode(',', $g_users);
	        		}
	        		$new_group_file[] = $f1.':'.$f2.':'.$f3.':'.$f4;
	      		}
	    	}
	    	$new_group_file = implode("\n", $new_group_file);
	    	$app->file->wf($this->server_conf['group_datei'], $new_group_file);
	    	// TB: auskommentiert
	        //$this->order_users_groups();
	
	    	if($shadow_datei != '/etc/shadow'){
	      		$app->file->af($shadow_datei, "\n");
	      		$app->log->caselog("pwd_mkdb $shadow_datei &> /dev/null", $this->FILE, __LINE__);
	    	}
	    	return true;
	  	} else {
	    	return false;
	  	}
	}
	
	/**
	 * Add a usergroup to the system
	 *
	 */
	function addgroup($group, $gid, $members = ''){
		global $app;
	  	if($this->is_group($group)){
		    return false;
	  	} else {
	    	$group_datei = $this->server_conf['group_datei'];
	    	$shadow_datei = $this->server_conf['shadow_datei'];
	    	$new_group = "\n$group:x:$gid:$members\n";
	    	$app->file->af($group_datei, $new_group);
	
	        // TB: auskommentiert
	        //$this->order_users_groups();
	    	if($shadow_datei != '/etc/shadow'){
	      		$app->log->caselog("pwd_mkdb $shadow_datei &> /dev/null", $this->FILE, __LINE__);
	    	}
	    	return true;
	  	}
	}
	
	/**
	 * Update usersgroup in way to delete and add it again
	 *
	 */
	function updategroup($group, $gid, $members = ''){
		$this->delgroup($group);
	  	$this->addgroup($group, $gid, $members);
	}
	
	/**
	 * Delete a usergroup from the system
	 *
	 */
	function delgroup($group){
		global $app;
	  	if($this->is_group($group)){
	    	$group_datei = $this->server_conf['group_datei'];
	    	$shadow_datei = $this->server_conf['shadow_datei'];
	    	$groups = $app->file->rf($group_datei);
	    	$lines = explode("\n", $groups);
	    	if(is_array($lines)){
	      		$num_lines = sizeof($lines);
	      		for($i=0;$i<$num_lines;$i++){
	        		if(trim($lines[$i]) != ''){
	          			list($f1,) = explode(':', $lines[$i]);
	          			if($f1 != $group) $new_lines[] = $lines[$i];
	        		}
	      		}
	      		$new_groups = implode("\n", $new_lines);
	      		$app->file->wf($group_datei, $new_groups);
	      		unset($new_lines);
	      		unset($lines);
	      		unset($new_groups);
	    	}
	        // TB: auskommentiert
	    	//$this->order_users_groups();
	    	if($shadow_datei != '/etc/shadow'){
	      		$app->log->caselog("pwd_mkdb $shadow_datei &> /dev/null", $this->FILE, __LINE__);
	    	}
	    	return true;
	  	} else {
	    	return false;
	  	}
	}
	/**
	 * Order usergroups
	 *
	 */
	function order_users_groups(){
		global $app;
	  	$user_datei = $this->server_conf['passwd_datei'];
	  	$shadow_datei = $this->server_conf['shadow_datei'];
	  	$group_datei = $this->server_conf['group_datei'];
	
	  	$groups = $app->file->no_comments($group_datei);
	  	$lines = explode("\n", $groups);
	  	if(is_array($lines)){
		    foreach($lines as $line){
	      		if(trim($line) != ''){
	        		list($f1, $f2, $f3, $f4) = explode(':', $line);
	        		$arr[$f3] = $line;
	      		}
	    	}
	  	}
	  	ksort($arr);
	  	reset($arr);
	  	if($shadow_datei != '/etc/shadow'){
	    	$app->file->wf($group_datei, $app->file->remove_blank_lines(implode("\n", $arr), 0)."\n");
	  	}else {
		    $app->file->wf($group_datei, $app->file->remove_blank_lines(implode("\n", $arr), 0));
	  	}
	  	unset($arr);
	
	  	$users = $app->file->no_comments($user_datei);
	  	$lines = explode("\n", $users);
	  	if(is_array($lines)){
		    foreach($lines as $line){
	      		if(trim($line) != ""){
	        		list($f1, $f2, $f3,) = explode(':', $line);
	        		if($f1 != 'toor'){
	          			$arr[$f3] = $line;
	        		} else {
	          			$arr[70000] = $line;
	        		}
	      		}
	    	}
	  	}
	  	ksort($arr);
	  	reset($arr);
	  	$app->file->wf($user_datei, $app->file->remove_blank_lines(implode("\n", $arr), 0));
	  	unset($arr);
	
	  	$passwds = $app->file->no_comments($shadow_datei);
	  	$lines = explode("\n", $passwds);
	  	if(is_array($lines)){
		    foreach($lines as $line){
	    		if(trim($line) != ''){
	        		list($f1, $f2, $f3,) = explode(':', $line);
	        		if($f1 != 'toor'){
	          			$uid = $this->getuid($f1);
	          			if(!is_bool($uid)) $arr[$uid] = $line;
	        		} else {
	          			$arr[70000] = $line;
	        		}
	      		}
	    	}
	  	}
	  	ksort($arr);
	  	reset($arr);
	  	$app->file->wf($shadow_datei, $app->file->remove_blank_lines(implode("\n", $arr), 0));
	  	unset($arr);
	}
	
	/**
	 * Find a user / group id
	 *
	 */
	function find_uid_gid($min, $max){
		global $app;
	  	if($min < $max && $min >= 0 && $max >= 0 && $min <= 65536 && $max <= 65536 && is_int($min) && is_int($max)){
	    	for($i=$min;$i<=$max;$i++){
	      		$uid_arr[$i] = $gid_arr[$i] = 1;
	    	}
	    	$user_datei = $this->server_conf['passwd_datei'];
	    	$group_datei = $this->server_conf['group_datei'];
	
	    	$users = $app->file->no_comments($user_datei);
	    	$lines = explode("\n", $users);
	    	if(is_array($lines)){
	      		foreach($lines as $line){
	        		if(trim($line) != ''){
	          			list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = explode(':', $line);
	          			if($f3 >= $min && $f3 <= $max) unset($uid_arr[$f3]);
	        		}
	      		}
	      		if(!empty($uid_arr)){
	        		foreach($uid_arr as $key => $val){
	          			$uids[] = $key;
	        		}
	        		$min_uid = min($uids);
	        		unset($uid_arr);
	      		} else {
	        		return false;
	      		}
	    	}
	
	    	$groups = $app->file->no_comments($group_datei);
	    	$lines = explode("\n", $groups);
	    	if(is_array($lines)){
	      		foreach($lines as $line){
	        		if(trim($line) != ''){
	          			list($f1, $f2, $f3, $f4) = explode(':', $line);
	          			if($f3 >= $min && $f3 <= $max) unset($gid_arr[$f3]);
	        		}
	      		}
	      		if(!empty($gid_arr)){
	        		foreach($gid_arr as $key => $val){
	          			$gids[] = $key;
	        		}
	        		$min_gid = min($gids);
	        		unset($gid_arr);
	      		} else {
	        		return false;
	      		}
	    	}
	
	    	$result = array_intersect($uids, $gids);
	    	$new_id = (max($result));
	    	unset($uids);
	    	unset($gids);
	    	unset($result);
	    	if($new_id <= $max){
	      		return $new_id;
	    	} else {
	      		return false;
	    	}
	  	} else {
	    	return false;
	  	}
	}
	
	/**
	 * Check if the users is really a user into the system
	 *
	 */
	function is_user($user){
		global $app;
	  	$user_datei = $this->server_conf['passwd_datei'];
	  	$users = $app->file->no_comments($user_datei);
	  	$lines = explode("\n", $users);
	  	if(is_array($lines)){
			foreach($lines as $line){
	      		if(trim($line) != ''){
	        		list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = explode(':', $line);
	        		if($f1 == $user) return true;
	      		}
	    	}
	  	}
	  	return false;
	}
	
	/**
	 * Check if the group is on this system
	 *
	 */
	function is_group($group){
		global $app;
	  	$group_datei = $this->server_conf['group_datei'];
	  	$groups = $app->file->no_comments($group_datei);
	  	$lines = explode("\n", $groups);
	  	if(is_array($lines)){
		    foreach($lines as $line){
	      		if(trim($line) != ""){
	        		list($f1, $f2, $f3, $f4) = explode(':', $line);
	        		if($f1 == $group) return true;
	      		}
	    	}
	  	}
	  	return false;
	}
	
	/*
	// Alternative implementation of the is_group function. Should be faster then the old one To be tested.
	function is_group($group) {
	$groupfile = '/etc/group';
	if(is_file($groupfile)) {
		$handle = fopen ($groupfile, "r");
		while (!feof($handle)) {
			$line = trim(fgets($handle, 4096));
			if($line != ""){
				$parts = explode(":", $line);
	        	if($parts[0] == $group) {
					fclose ($handle);
					return true;
				}
			}
		}
		fclose ($handle);
	}
	return false;
	}
	*/
	
	function root_group(){
		global $app;
	  	$group_datei = $this->server_conf['group_datei'];
	  	$groups = $app->file->no_comments($group_datei);
	  	$lines = explode("\n", $groups);
	  	if(is_array($lines)){
		    foreach($lines as $line){
	      		if(trim($line) != ''){
	        		list($f1, $f2, $f3, $f4) = explode(':', $line);
	        		if($f3 == 0) return $f1;
	      		}
	    	}
	  	}
	  	return false;
	}
	
	/**
	 * Get the groups of an user
	 *
	 */
	function get_user_groups($username){
		global $app;
	  	$user_groups = array();
	  	$group_datei = $this->server_conf['group_datei'];
	  	$groups = $app->file->no_comments($group_datei);
	  	$lines = explode("\n", $groups);
	  	if(is_array($lines)){
	    	foreach($lines as $line){
	      		if(trim($line) != ''){
	        		list($f1, $f2, $f3, $f4) = explode(':', $line);
	        		if(intval($f3) < intval($this->server_conf['groupid_von']) && trim($f1) != 'users'){
	          			$tmp_group_users = explode(',', str_replace(' ', '', $f4));
	          			if(in_array($username, $tmp_group_users) && trim($f1) != '') $user_groups[] = $f1;
	          			unset($tmp_group_users);
	        		}
	      		}
	    	}
	  	}
	  	if(!empty($user_groups)) return implode(',', $user_groups);
	  	return '';
	}
	
	/**
	 * Get a user password
	 *
	 */
	function getpasswd($user){
		global $app;
	  	if($this->is_user($user)){
		    $shadow_datei = $this->server_conf['shadow_datei'];
	    	$passwds = $app->file->no_comments($shadow_datei);
	    	$lines = explode("\n", $passwds);
	    	if(is_array($lines)){
	      		foreach($lines as $line){
	        		if(trim($line) != ''){
	          			list($f1, $f2,) = explode(':', $line);
	          			if($f1 == $user) return $f2;
	        		}
	      		}
	    	}
	  	} else {
		    return false;
	  	}
	}
	
	/**
	 * Get the user id from an user
	 *
	 */
	function getuid($user){
		global $app;
	  	if($this->is_user($user)){
		    $user_datei = $this->server_conf['passwd_datei'];
	    	$users = $app->file->no_comments($user_datei);
	    	$lines = explode("\n", $users);
	    	if(is_array($lines)){
	      		foreach($lines as $line){
	        		if(trim($line) != ''){
	          			list($f1, $f2, $f3,) = explode(':', $line);
	          			if($f1 == $user) return $f3;
	        		}
	      		}
	    	}
	  	} else {
		    return false;
	  	}
	}
	
	/**
	 * Get all information from a user
	 *
	 */
	function get_user_attributes($user){
		global $app;
	  	if($this->is_user($user)){
	    	$user_datei = $this->server_conf['passwd_datei'];
	    	$users = $app->file->no_comments($user_datei);
	    	$lines = explode("\n", $users);
	    	if(is_array($lines)){
	      		foreach($lines as $line){
	        		if(trim($line) != ''){
	          			list($f1, $f2, $f3, $f4, $f5, $f6, $f7) = explode(':', $line);
	          			if($f1 == $user){
	            			$user_attr['username'] = $f1;
	            			$user_attr['x'] = $f2;
	            			$user_attr['uid'] = $f3;
	            			$user_attr['gid'] = $f4;
	            			$user_attr['name'] = $f5;
	            			$user_attr['homedir'] = $f6;
	            			$user_attr['shell'] = $f7;
	            			return $user_attr;
	          			}
	        		}
	      		}
	    	}
	  	} else {
	    	return false;
	  	}
	}
	
	/**
	 * Edit the owner of a file
	 *
	 */
	function chown($file, $owner, $allow_symlink = false){
	  global $app;
	  if($allow_symlink == false && $this->checkpath($file) == false) {
		$app->log("Action aborted, file is a symlink: $file",LOGLEVEL_WARN);
		return false;
	  }
	  if(file_exists($file)) {
		if(@chown($file, $owner)) {
			return true;
		} else {
			$app->log("chown failed: $file : $owner",LOGLEVEL_DEBUG);
			return false;
		}
	  }
	}
	
	function chgrp($file, $group = '', $allow_symlink = false){
	  global $app;
	  if($allow_symlink == false && $this->checkpath($file) == false) {
		$app->log("Action aborted, file is a symlink: $file",LOGLEVEL_WARN);
		return false;
	  }
	  if(file_exists($file)) {
		if(@chgrp($file, $group)) {
			return true;
		} else {
			$app->log("chgrp failed: $file : $group",LOGLEVEL_DEBUG);
			return false;
		}
	  }
	}
	
	//* Change the mode of a file
	function chmod($file, $mode, $allow_symlink = false) {
		global $app;
		if($allow_symlink == false && $this->checkpath($file) == false) {
			$app->log("Action aborted, file is a symlink: $file",LOGLEVEL_WARN);
			return false;
		}
		if(@chmod($file, $mode)) {
			return true;
		} else {
			$app->log("chmod failed: $file : $mode",LOGLEVEL_DEBUG);
			return false;
		}
	}
	
	function file_put_contents($filename, $data, $allow_symlink = false) {
		global $app;
		if($allow_symlink == false && $this->checkpath($filename) == false) {
			$app->log("Action aborted, file is a symlink: $filename",LOGLEVEL_WARN);
			return false;
		}
		if(file_exists($filename)) unlink($filename);
		return file_put_contents($filename, $data);
	}
	
	function file_get_contents($filename, $allow_symlink = false) {
		global $app;
		if($allow_symlink == false && $this->checkpath($filename) == false) {
			$app->log("Action aborted, file is a symlink: $filename",LOGLEVEL_WARN);
			return false;
		}
		return file_put_contents($filename, $data);
	}
	
	function rename($filename, $new_filename, $allow_symlink = false) {
		global $app;
		if($allow_symlink == false && $this->checkpath($filename) == false) {
			$app->log("Action aborted, file is a symlink: $filename",LOGLEVEL_WARN);
			return false;
		}
		return rename($filename, $new_filename);
	}
	
	function mkdir($dirname, $allow_symlink = false) {
		global $app;
		if($allow_symlink == false && $this->checkpath($dirname) == false) {
			$app->log("Action aborted, file is a symlink: $dirname",LOGLEVEL_WARN);
			return false;
		}
		if(@mkdir($dirname)) {
			return true;
		} else {
			$app->log("mkdir failed: $dirname",LOGLEVEL_DEBUG);
			return false;
		}
	}
	
	function unlink($filename) {
		if(file_exists($filename)) {
			return unlink($filename);
		}
	}
	
	function copy($file1,$file2) {
		return copy($file1,$file2);
	}
	
	function checkpath($path) {
		$path = trim($path);
		//* We allow only absolute paths
		if(substr($path,0,1) != '/') return false;
		
		//* We allow only some characters in the path
		if(!preg_match('/[a-zA-Z0-9_\.\-]{1,}/',$path)) return false;
		
		//* Check path for symlinks
		$path_parts = explode('/',$path);
		$testpath = '';
		foreach($path_parts as $p) {
			$testpath .= '/'.$p;
			if(is_link($testpath)) return false;
		}
		
		return true;
	}
	
	/**
	 * Add an user to a specific group
	 *
	 */
	function add_user_to_group($group, $user = 'admispconfig'){
		global $app;
	  	$group_file = $app->file->rf($this->server_conf['group_datei']);
	  	$group_file_lines = explode("\n", $group_file);
	  	foreach($group_file_lines as $group_file_line){
		    list($group_name,$group_x,$group_id,$group_users) = explode(':',$group_file_line);
	    	if($group_name == $group){
	      		$group_users = explode(',', str_replace(' ', '', $group_users));
	      		if(!in_array($user, $group_users)){
	        		$group_users[] = $user;
	      		}
	      		$group_users = implode(',', $group_users);
	      		if(substr($group_users,0,1) == ',') $group_users = substr($group_users,1);
	      		$group_file_line = $group_name.':'.$group_x.':'.$group_id.':'.$group_users;
	    	}
	    	$new_group_file[] = $group_file_line;
	  	}
	  	$new_group_file = implode("\n", $new_group_file);
	  	$app->file->wf($this->server_conf['group_datei'], $new_group_file);
	  	$app->file->remove_blank_lines($this->server_conf['group_datei']);
	  	if($this->server_conf['shadow_datei'] != '/etc/shadow'){
		    $app->log->caselog('pwd_mkdb '.$this->server_conf['shadow_datei'].' &> /dev/null', $this->FILE, __LINE__);
	  	}
	}
	
	/*
	function usermod($user, $groups){
		global $app;
	  	if($this->is_user($user)){
		    $groups = explode(',', str_replace(' ', '', $groups));
	    	$group_file = $app->file->rf($this->server_conf['group_datei']);
	    	$group_file_lines = explode("\n", $group_file);
	    	foreach($group_file_lines as $group_file_line){
	      		if(trim($group_file_line) != ""){
	        		list($f1, $f2, $f3, $f4) = explode(':', $group_file_line);
	        		$group_users = explode(',', str_replace(' ', '', $f4));
	        		if(!in_array($f1, $groups)){
	          			if(in_array($user, $group_users)){
	            			$g_users = array();
	            			foreach($group_users as $group_user){
	              				if($group_user != $user) $g_users[] = $group_user;
	            			}
	            			$f4 = implode(',', $g_users);
	          			}
		        	} else {
	          			if(!in_array($user, $group_users)){
	            			if(trim($group_users[0]) == '') unset($group_users);
	            			$group_users[] = $user;
	          			}
	          			$f4 = implode(',', $group_users);
	        		}
	        		$new_group_file[] = $f1.':'.$f2.':'.$f3.':'.$f4;
	      		}
	    	}
	    	$new_group_file = implode("\n", $new_group_file);
	    	$app->file->wf($this->server_conf['group_datei'], $new_group_file);
	    	$app->file->remove_blank_lines($this->server_conf['group_datei']);
	    	if($this->server_conf['shadow_datei'] != '/etc/shadow'){
	      		$app->log->caselog('pwd_mkdb '.$this->server_conf['shadow_datei'].' &> /dev/null', $this->FILE, __LINE__);
	    	}
	    	return true;
	  	} else {
		    return false;
	  	}
	}
	*/
	
	/**boot autostart etc
	 *
	 */
	function rc_edit($service, $rl, $action){
		// $action = "on|off";
	  	global $app;
	  	$dist_init_scripts = $app->system->server_conf['dist_init_scripts'];
	  	$dist_runlevel = $app->system->server_conf['dist_runlevel'];
	  	$dist = $app->system->server_conf['dist'];
	  	if(trim($dist_runlevel) == ''){ // falls es keine runlevel gibt (FreeBSD)
		    if($action == 'on'){
	    		@symlink($dist_init_scripts.'/'.$service, $dist_init_scripts.'/'.$service.'.sh');
	    	}
	    	if($action == 'off'){
	      		if(is_link($dist_init_scripts.'/'.$service.'.sh')){
	        		unlink($dist_init_scripts.'/'.$service.'.sh');
	      		} else {
	        		rename($dist_init_scripts.'/'.$service.'.sh',$dist_init_scripts.'/'.$service);
	      		}
	    	}
	  	} else { // Linux
	    	if(substr($dist, 0,4) == 'suse'){
	      		if($action == 'on'){
	        		exec("chkconfig --add $service &> /dev/null");
	      		}
	      		if($action == 'off'){
	        		exec("chkconfig --del $service &> /dev/null");
	      		}
	    	} else {
	      		$runlevels = explode(',', $rl);
	      		foreach($runlevels as $runlevel){
	        		$runlevel = trim($runlevel);
	        		if($runlevel != '' && is_dir($dist_runlevel.'/rc'.$runlevel.'.d')){
	          			$handle=opendir($dist_runlevel.'/rc'.$runlevel.'.d');
	          			while($file = readdir($handle)){
	            			if($file != '.' && $file != '..'){
	              				$target = @readlink($dist_runlevel.'/rc'.$runlevel.'.d/'.$file);
	              				if(strstr($file, $service) && strstr($target, $service) && substr($file,0,1) == 'S') $ln_arr[$runlevel][] = $dist_runlevel.'/rc'.$runlevel.'.d/'.$file;
	            			}
	          			}
	          			closedir($handle);
	        		}
	        		if($action == 'on'){
	          			if(!is_array($ln_arr[$runlevel])) @symlink($dist_init_scripts.'/'.$service, $dist_runlevel.'/rc'.$runlevel.'.d/S99'.$service);
	        		}
	        		if($action == 'off'){
	          			if(is_array($ln_arr[$runlevel])){
	            			foreach($ln_arr[$runlevel] as $link){
	              				unlink($link);
	            			}
	          			}
	        		}
	      		}
	    	}
	  	}
	}
	
	/**
	 * Filter information from the commands
	 *
	 */
	function grep($content, $string, $params = ''){
		global $app;
	  	// params: i, v, w
	  	$content = $app->file->unix_nl($content);
	  	$lines = explode("\n", $content);
	  	foreach($lines as $line){
	    	if(!strstr($params, 'w')){
	      		if(strstr($params, 'i')){
	        		if(strstr($params, 'v')){
	          			if(!stristr($line, $string)) $find[] = $line;
	        		} else {
	          			if(stristr($line, $string)) $find[] = $line;
	        		}
	      		} else {
	        		if(strstr($params, 'v')){
	          			if(!strstr($line, $string)) $find[] = $line;
	        		} else {
	          			if(strstr($line, $string)) $find[] = $line;
	        		}
	      		}
	    	} else {
	      		if(strstr($params, 'i')){
	        		if(strstr($params, 'v')){
	          			if(!$app->string->is_word($string, $line, 'i')) $find[] = $line;
	        		} else {
	          			if($app->string->is_word($string, $line, 'i')) $find[] = $line;
	        		}
	      		} else {
	        		if(strstr($params, 'v')){
	          			if(!$app->string->is_word($string, $line)) $find[] = $line;
	        		} else {
	          			if($app->string->is_word($string, $line)) $find[] = $line;
	        		}
	      		}
	    	}
	  	}
	  	if(is_array($find)){
		    $ret_val = implode("\n", $find);
	    	if(substr($ret_val,-1) != "\n") $ret_val .= "\n";
	    	$find = NULL;
	    	return $ret_val;
	  	} else {
		    return false;
	  	}
	}
	
	/**
	 * Strip content from fields
	 *
	 */
	function cut($content, $field, $delimiter = ':'){
		global $app;
	  	$content = $app->file->unix_nl($content);
	  	$lines = explode("\n", $content);
	  	foreach($lines as $line){
	    	$elms = explode($delimiter, $line);
	    	$find[] = $elms[($field-1)];
	  	}
	  	if(is_array($find)){
		    $ret_val = implode("\n", $find);
		    if(substr($ret_val,-1) != "\n") $ret_val .= "\n";
		    $find = NULL;
	    	return $ret_val;
	  	} else {
		    return false;
	  	}
	}
	
	/**
	 * Get the content off a file
	 *
	 */
	function cat($file){
		global $app;
	  	return $app->file->rf($file);
	}
	
	/**
	 * Control services to restart etc
	 *
	 */
	function daemon_init($daemon, $action){
		//* $action = start|stop|restart|reload
	  	global $app;
	  	$dist = $this->server_conf['dist'];
	  	$dist_init_scripts = $this->server_conf['dist_init_scripts'];
	  	if(!strstr($dist, 'freebsd')){
		    $app->log->caselog("$dist_init_scripts/$daemon $action &> /dev/null", $this->FILE, __LINE__);
	  	} else {
		    if(is_file($dist_init_scripts.'/'.$daemon.'.sh') || is_link($dist_init_scripts.'/'.$daemon.'.sh')){
	      		if($action == 'start' || $action == 'stop'){
	        		$app->log->caselog($dist_init_scripts.'/'.$daemon.'.sh '.$action.' &> /dev/null', $this->FILE, __LINE__);
	      		} else {
	        		$app->log->caselog($dist_init_scripts.'/'.$daemon.'.sh stop &> /dev/null', $this->FILE, __LINE__);
	        		sleep(3);
	        		$app->log->caselog($dist_init_scripts.'/'.$daemon.'.sh start &> /dev/null', $this->FILE, __LINE__);
	      		}
	    	} else {
	      		if(is_file($dist_init_scripts.'/'.$daemon) || is_link($dist_init_scripts.'/'.$daemon)){
	        		if($action == 'start' || $action == 'stop'){
	          			$app->log->caselog($dist_init_scripts.'/'.$daemon.' '.$action.' &> /dev/null', $this->FILE, __LINE__);
	        		} else {
	          			$app->log->caselog($dist_init_scripts.'/'.$daemon.' stop &> /dev/null', $this->FILE, __LINE__);
	          			sleep(3);
	          			$app->log->caselog($dist_init_scripts.'/'.$daemon.' start &> /dev/null', $this->FILE, __LINE__);
	        		}
	      		} else {
	        		if(is_file('/etc/rc.d/'.$daemon) || is_link('/etc/rc.d/'.$daemon)){
	          			if($action == 'start' || $action == 'stop'){
	            			$app->log->caselog('/etc/rc.d/'.$daemon.' '.$action.' &> /dev/null', $this->FILE, __LINE__);
	          			} else {
	            			$app->log->caselog('/etc/rc.d/'.$daemon.' stop &> /dev/null', $this->FILE, __LINE__);
	            			sleep(3);
	            			$app->log->caselog('/etc/rc.d/'.$daemon.' start &> /dev/null', $this->FILE, __LINE__);
	          			}
	        		}
		      	}
	    	}
	  	}
	}
	
	function netmask($netmask){
	  list($f1,$f2,$f3,$f4) = explode('.', trim($netmask));
	  $bin = str_pad(decbin($f1),8,'0',STR_PAD_LEFT).str_pad(decbin($f2),8,'0',STR_PAD_LEFT).str_pad(decbin($f3),8,'0',STR_PAD_LEFT).str_pad(decbin($f4),8,'0',STR_PAD_LEFT);
	  $parts = explode('0', $bin);
	  $bin = str_pad($parts[0], 32, '0', STR_PAD_RIGHT);
	  $bin = wordwrap($bin, 8, '.', 1);
	  list($f1,$f2,$f3,$f4) = explode('.', trim($bin));
	  return bindec($f1).'.'.bindec($f2).'.'.bindec($f3).'.'.bindec($f4);
	}
	
	function binary_netmask($netmask){
	  list($f1,$f2,$f3,$f4) = explode('.', trim($netmask));
	  $bin = str_pad(decbin($f1),8,'0',STR_PAD_LEFT).str_pad(decbin($f2),8,'0',STR_PAD_LEFT).str_pad(decbin($f3),8,'0',STR_PAD_LEFT).str_pad(decbin($f4),8,'0',STR_PAD_LEFT);
	  $parts = explode('0', $bin);
	  return substr_count($parts[0], '1');
	}
	
	function network($ip, $netmask){
	  $netmask = $this->netmask($netmask);
	  list($f1,$f2,$f3,$f4) = explode('.', $netmask);
	  $netmask_bin = str_pad(decbin($f1),8,'0',STR_PAD_LEFT).str_pad(decbin($f2),8,'0',STR_PAD_LEFT).str_pad(decbin($f3),8,'0',STR_PAD_LEFT).str_pad(decbin($f4),8,'0',STR_PAD_LEFT);
	  list($f1,$f2,$f3,$f4) = explode('.', $ip);
	  $ip_bin = str_pad(decbin($f1),8,'0',STR_PAD_LEFT).str_pad(decbin($f2),8,'0',STR_PAD_LEFT).str_pad(decbin($f3),8,'0',STR_PAD_LEFT).str_pad(decbin($f4),8,'0',STR_PAD_LEFT);
	  for($i=0;$i<32;$i++){
	    $network_bin .= substr($netmask_bin,$i,1) * substr($ip_bin,$i,1);
	  }
	  $network_bin = wordwrap($network_bin, 8, '.', 1);
	  list($f1,$f2,$f3,$f4) = explode('.', trim($network_bin));
	  return bindec($f1).'.'.bindec($f2).'.'.bindec($f3).'.'.bindec($f4);
	}
	
	/**
	 * Make a broadcast address from an IP number in combination with netmask
	 *
	 */
	function broadcast($ip, $netmask){
		$netmask = $this->netmask($netmask);
	  	$binary_netmask = $this->binary_netmask($netmask);
	  	list($f1,$f2,$f3,$f4) = explode('.', $ip);
	  	$ip_bin = str_pad(decbin($f1),8,'0',STR_PAD_LEFT).str_pad(decbin($f2),8,'0',STR_PAD_LEFT).str_pad(decbin($f3),8,'0',STR_PAD_LEFT).str_pad(decbin($f4),8,'0',STR_PAD_LEFT);
	  	$broadcast_bin = str_pad(substr($ip_bin, 0, $binary_netmask),32,'1',STR_PAD_RIGHT);
	  	$broadcast_bin = wordwrap($broadcast_bin, 8, '.', 1);
	  	list($f1,$f2,$f3,$f4) = explode('.', trim($broadcast_bin));
	  	return bindec($f1).'.'.bindec($f2).'.'.bindec($f3).'.'.bindec($f4);
	}
	
	/**
	 * Get the network address information
	 *
	 */
	function network_info(){
		$dist = $this->server_conf['dist'];
	  	ob_start();
	  	passthru('ifconfig');
	  	$output = ob_get_contents();
	  	ob_end_clean();
	  	$lines = explode("\n", $output);
	  	foreach($lines as $line){
		    $elms = explode(' ', $line);
	    	if(trim($elms[0]) != '' && substr($elms[0],0,1) != "\t"){
	      		$elms[0] = trim($elms[0]);
	      		if(strstr($dist, 'freebsd')) $elms[0] = substr($elms[0],0,-1);
	      		$interfaces[] = $elms[0];
	    	}
	  	}
	  	if(!empty($interfaces)){
		    foreach($interfaces as $interface){
	    		ob_start();
	      		if(!strstr($dist, 'freebsd')){
	        		passthru('ifconfig '.$interface." | grep -iw 'inet' | cut -f2 -d: | cut -f1 -d' '");
	      		} else {
	        		passthru('ifconfig '.$interface." | grep -iw 'inet' | grep -iv 'inet6' | cut -f2 -d' '");
	      		}
	      		$output = trim(ob_get_contents());
	      		ob_end_clean();
	      		if($output != ''){
	        		$ifconfig['INTERFACE'][$interface] = $output;
	        		$ifconfig['IP'][$output] = $interface;
	      		}
	    	}
	    	if(!empty($ifconfig)){
	      		return $ifconfig;
	    	} else {
	      		return false;
	    	}
	  	} else {
	    	return false;
	  	}
	}
	
	/**
	 * Configure the network settings from the system
	 *
	 */
	function network_config(){
		$ifconfig = $this->network_info();
	  	if($ifconfig){
	    	$main_interface = $ifconfig['IP'][$this->server_conf['server_ip']];
	    	if(strstr($main_interface, ':')){
	      		$parts = explode(':', $main_interface);
	      		$main_interface = trim($parts[0]);
	    	}
	    	if($main_interface != ''){
	      		$ips = $this->data['isp_server_ip'];
	      		if(!empty($ips)){
	        		foreach($ips as $ip){
	          			if(!isset($ifconfig['IP'][$ip['server_ip']])){
	            			$to_set[] = $ip['server_ip'];
	          			} else {
	            			unset($ifconfig['IP'][$ip['server_ip']]);
	          			}
	        		}
	        		if(!empty($ifconfig['IP'])){
	          			foreach($ifconfig['IP'] as $key => $val){
	            			if(!strstr($val, 'lo') && !strstr($val, 'lp') && strstr($val, $main_interface)){
	              				exec('ifconfig '.$val.' down &> /dev/null');
	              				unset($ifconfig['INTERFACE'][$val]);
	            			}
	          			}
	        		}
	        		if(!empty($to_set)){
	         			foreach($to_set as $to){
	           				$i = 0;
	           				while($i >= 0){
	             				if(isset($ifconfig['INTERFACE'][$main_interface.':'.$i])){
	               					$i++;
					             } else {
	               					$new_interface = $main_interface.':'.$i;
	               					$i = -1;
	             				}
	           				}
	           				exec('ifconfig '.$new_interface.' '.$to.' netmask '.$this->server_conf['server_netzmaske'].' up &> /dev/null');
	           				$ifconfig['INTERFACE'][$new_interface] = $to;
	          			}
	        		}
	      		}
	    	}
	  	}
	}
	
	function quota_dirs(){
	  global $app;
	  $content = $app->file->unix_nl($app->file->no_comments('/etc/fstab'));
	  $lines = explode("\n", $content);
	  foreach($lines as $line){
	    $line = trim($line);
	    if($line != ''){
	      $elms = explode("\t", $line);
	      foreach($elms as $elm){
	        if(trim($elm) != '') $f[] = $elm;
	      }
	      if(!empty($f) && stristr($f[3], 'userquota') && stristr($f[3], 'groupquota')){
	        $q_dirs[] = trim($f[1]);
	      }
	      unset($f);
	    }
	  }
	  if(!empty($q_dirs)){
	    return $q_dirs;
	  } else {
	    return false;
	  }
	}
	
	/**
	 * Scan the trash for virusses infection
	 *
	 */
	function make_trashscan(){
		global $app;
	  	//trashscan erstellen
	  	// Template Öffnen
	  	$app->tpl->clear_all();
	  	$app->tpl->define( array(table    => 'trashscan.master'));
	
	  	if(!isset($this->server_conf['virusadmin']) || trim($this->server_conf['virusadmin']) == '') $this->server_conf['virusadmin'] = 'admispconfig@localhost';
	  	if(substr($this->server_conf['virusadmin'],0,1) == '#'){
		    $notify = 'no';
	  	} else {
		    $notify = 'yes';
	  	}
	
	  	// Variablen zuweisen
	  	$app->tpl->assign( array(VIRUSADMIN => $this->server_conf['virusadmin'],
	                           NOTIFICATION => $notify));
	
	  	$app->tpl->parse(TABLE, table);
	
	  	$trashscan_text = $app->tpl->fetch();
	
	  	$datei = '/home/admispconfig/ispconfig/tools/clamav/bin/trashscan';
	  	$app->file->wf($datei, $trashscan_text);
	
	  	chmod($datei, 0755);
	  	chown($datei,'admispconfig');
	  	chgrp($datei,'admispconfig');
	}
	
	/**
	 * Get the current time
	 *
	 */
	function get_time(){
	  $addr = 'http://www.ispconfig.org/';
	  $timeout = 1;
	  $url_parts = parse_url($addr);
	  $path = $url_parts['path'];
	  $port = 80;
	  $urlHandle = @fsockopen($url_parts['host'], $port, $errno, $errstr, $timeout);
	  if ($urlHandle){
	    socket_set_timeout($urlHandle, $timeout);
	
	    $urlString = 'GET '.$path." HTTP/1.0\r\nHost: ".$url_parts['host']."\r\nConnection: Keep-Alive\r\nUser-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)\r\n";
	    if ($user) $urlString .= 'Authorization: Basic '.base64_encode($user.':'.$pass)."\r\n";
	    $urlString .= "\r\n";
	    fputs($urlHandle, $urlString);
	
	    $month['Jan'] = '01';
	    $month['Feb'] = '02';
	    $month['Mar'] = '03';
	    $month['Apr'] = '04';
	    $month['May'] = '05';
	    $month['Jun'] = '06';
	    $month['Jul'] = '07';
	    $month['Aug'] = '08';
	    $month['Sep'] = '09';
	    $month['Oct'] = '10';
	    $month['Nov'] = '11';
	    $month['Dec'] = '12';
	    $c = 0;
	    $l = 0;
	    $startzeit = time();
	    while(!feof($urlHandle) && $c < 2 && $l == 0){
	      $line = trim(fgets($urlHandle,128));
	      $response .= $line;
	      $c = time() - $startzeit;
	      if($line == '' || substr($line, 0, 5) == 'Date:') $l += 1; // nur den Header auslesen
	      if(substr($line, 0, 5) == 'Date:'){
	        $parts = explode(' ', $line);
	        $tag = $parts[2];
	        $monat = $month[$parts[3]];
	        $jahr = $parts[4];
	        list($stunde, $minute, $sekunde) = explode(':', $parts[5]);
	        $timestamp = mktime($stunde,$minute,$sekunde,$monat,$tag,$jahr);
	      }
	    }
	
	    @fclose($urlHandle);
	
	    return $timestamp;
	  } else {
	    @fclose($urlHandle);
	    return false;
	  }
	}
	
	function replaceLine($filename,$search_pattern,$new_line,$strict = 0,$append = 1) {
		global $app;
		if($this->checkpath($filename) == false) {
			$app->log("Action aborted, file is a symlink: $filename",LOGLEVEL_WARN);
			return false;
		}
		$lines = @file($filename);
		$out = '';
		$found = 0;
		if(is_array($lines)) {
			foreach($lines as $line) {
				if($strict == 0) {
					if(stristr($line,$search_pattern)) {
						$out .= $new_line."\n";
						$found = 1;
					} else {
						$out .= $line;
					}
				} else {
					if(trim($line) == $search_pattern) {
						$out .= $new_line."\n";
						$found = 1;
					} else {
						$out .= $line;
					}
				}
			}
		}
		
		if($found == 0) {
			//* add \n if the last line does not end with \n or \r
			if(substr($out,-1) != "\n" && substr($out,-1) != "\r" && filesize($filename) > 0) $out .= "\n";
			//* add the new line at the end of the file
			if($append == 1) {
				$out .= $new_line."\n";
			}
		}
		file_put_contents($filename,$out);
	}
	
	function removeLine($filename,$search_pattern,$strict = 0) {
	global $app;
	if($this->checkpath($filename) == false) {
		$app->log("Action aborted, file is a symlink: $filename",LOGLEVEL_WARN);
		return false;
	}
	if($lines = @file($filename)) {
		$out = '';
		foreach($lines as $line) {
			if($strict == 0) {
				if(!stristr($line,$search_pattern)) {
					$out .= $line;
				}
			} else {
				if(!trim($line) == $search_pattern) {
					$out .= $line;
				}
			}
		}
		file_put_contents($filename,$out);
	}
	}
	
	function maildirmake($maildir_path, $user = '', $subfolder = '') {
		
		global $app;
		
		if($subfolder != '') {
			$dir = escapeshellcmd($maildir_path.'/.'.$subfolder);
		} else {
			$dir = escapeshellcmd($maildir_path);
		}
		
		if(!is_dir($dir)) mkdir($dir, 0700, true);

		if($user != '' && $user != 'root' && $this->is_user($user)) {
			$user = escapeshellcmd($user);
			// I assume that the name of the (vmail group) is the same as the name of the mail user in ISPConfig 3
			$group = $user;
			if(is_dir($dir)) $this->chown($dir,$user);
			if(is_dir($dir)) $this->chgrp($dir,$group);

			$chown_mdsub = true;
		}
		
		$maildirsubs = array('cur','new','tmp');

		foreach ($maildirsubs as $mdsub) {
			if(!is_dir($dir.'/'.$mdsub)) mkdir($dir.'/'.$mdsub, 0700, true);
			if ($chown_mdsub) {
				chown($dir.'/'.$mdsub, $user);
				chgrp($dir.'/'.$mdsub, $group);
			}
		}

		chmod($dir, 0700);
		
		/*
		if($user != '' && $this->is_user($user) && $user != 'root') {
			$user = escapeshellcmd($user);
			// I assume that the name of the (vmail group) is the same as the name of the mail user in ISPConfig 3
			$group = $user;
			exec("chown $user:$group $dir $dir_cur $dir_new $dir_tmp");
		}
		*/
		
		//* Add the subfolder to the subscriptions and courierimapsubscribed files
		if($subfolder != '') {
			// Courier
			if(!is_file($maildir_path.'/courierimapsubscribed')) {
				$tmp_file = escapeshellcmd($maildir_path.'/courierimapsubscribed');
				touch($tmp_file);
				chmod($tmp_file, 0744);
				chown($tmp_file,'vmail');
				chgrp($tmp_file,'vmail');
			}
			$this->replaceLine($maildir_path.'/courierimapsubscribed','INBOX.'.$subfolder,'INBOX.'.$subfolder,1,1);
			
			// Dovecot
			if(!is_file($maildir_path.'/subscriptions')) {
				$tmp_file = escapeshellcmd($maildir_path.'/subscriptions');
				touch($tmp_file);
				chmod($tmp_file, 0744);
				chown($tmp_file,'vmail');
				chgrp($tmp_file,'vmail');
			}
			$this->replaceLine($maildir_path.'/subscriptions',$subfolder,$subfolder,1,1);
		}
		
		$app->log('Created Maildir '.$maildir_path.' with subfolder: '.$subfolder,LOGLEVEL_DEBUG);
		
	}
	
	//* Function to create directory paths and chown them to a user and group
	function mkdirpath($path, $mode = 0755, $user = '', $group = '') {
		$path_parts = explode('/',$path);
		$new_path = '';
		if(is_array($path_parts)) {
			foreach($path_parts as $part) {
				$new_path .= '/'.$part;
				if(!@is_dir($new_path)) {
					$this->mkdir($new_path);
					$this->chmod($new_path,$mode);
					if($user != '') $this->chown($new_path,$user);
					if($group != '') $this->chgrp($new_path,$group);
				}
			}
		}
		
	}
	
	//* Check if a application is installed
	function is_installed($appname) {
		exec('which '.escapeshellcmd($appname).' 2> /dev/null',$out,$returncode);
		if(isset($out[0]) && stristr($out[0],$appname) && $returncode == 0) {
			return true;
		} else {
			return false;
		}
	}
	
	function web_folder_protection($document_root,$protect) {
		global $app,$conf;
		
		if($this->checkpath($document_root) == false) {
			$app->log("Action aborted, target is a symlink: $document_root",LOGLEVEL_DEBUG);
			return false;
		}
		
		//* load the server configuration options
		$app->uses('getconf');
		$web_config = $app->getconf->get_server_config($conf['server_id'], 'web');
		
		if($protect == true && $web_config['web_folder_protection'] == 'y') {
			//* Add protection
			if($document_root != '' && $document_root != '/' && strlen($document_root) > 6 && !stristr($document_root,'..')) exec('chattr +i '.escapeshellcmd($document_root));
		} else {
			//* Remove protection
			if($document_root != '' && $document_root != '/' && strlen($document_root) > 6 && !stristr($document_root,'..')) exec('chattr -i '.escapeshellcmd($document_root));
		}
	}
	
	function usermod($username, $uid = 0, $gid = 0, $home = '', $shell = '', $password = '', $login = '') {
		global $app;
		
		if($login == '') $login = $username;
		
		//* Change values in /etc/passwd
		$passwd_file_array = file('/etc/passwd');
		if(is_array($passwd_file_array)) {
			foreach($passwd_file_array as $line) {
				$line = trim($line);
				$parts = explode(':',$line);
				if($parts[0] == $username) {
					if(trim($login) != '' && trim($login) != trim($username)) $parts[0] = trim($login);
					if(!empty($uid)) $parts[2] = trim($uid);
					if(!empty($gid)) $parts[3] = trim($gid);
					if(trim($home) != '') $parts[5] = trim($home);
					if(trim($shell) != '') $parts[6] = trim($shell);
					$new_line = implode(':',$parts);
					copy('/etc/passwd','/etc/passwd~');
					chmod('/etc/passwd~',0600);
					$app->uses('system');
					$app->system->replaceLine('/etc/passwd',$line,$new_line,1,0);
				}
			}
			unset($passwd_file_array);
		}
		
		//* If username != login, change username in group and gshadow file
		if($username  != $login) {
			$group_file_array = file('/etc/group');
			if(is_array($group_file_array)) {
				foreach($group_file_array as $line) {
					$line = trim($line);
					$parts = explode(':',$line);
					if(strstr($parts[3],$username)) {
						$uparts = explode(',',$parts[3]);
						if(is_array($uparts)) {
							foreach($uparts as $key => $val) {
								if($val == $username) $uparts[$key] = $login;
							}
						}
						$parts[3] = implode(',',$uparts);
						$new_line = implode(':',$parts);
						copy('/etc/group','/etc/group~');
						chmod('/etc/group~',0600);
						$app->system->replaceLine('/etc/group',$line,$new_line,1,0);
					}
				}
			}
			unset($group_file_array);
			
			$gshadow_file_array = file('/etc/gshadow');
			if(is_array($gshadow_file_array)) {
				foreach($gshadow_file_array as $line) {
					$line = trim($line);
					$parts = explode(':',$line);
					if(strstr($parts[3],$username)) {
						$uparts = explode(',',$parts[3]);
						if(is_array($uparts)) {
							foreach($uparts as $key => $val) {
								if($val == $username) $uparts[$key] = $login;
							}
						}
						$parts[3] = implode(',',$uparts);
						$new_line = implode(':',$parts);
						copy('/etc/gshadow','/etc/gshadow~');
						chmod('/etc/gshadow~',0600);
						$app->system->replaceLine('/etc/gshadow',$line,$new_line,1,0);
					}
				}
			}
			unset($group_file_array);
		}
		
		
		//* When password or login name has been changed
		if($password != '' || $username  != $login) {
			$shadow_file_array = file('/etc/shadow');
			if(is_array($shadow_file_array)) {
				foreach($shadow_file_array as $line) {
					$line = trim($line);
					$parts = explode(':',$line);
					if($parts[0] == $username) {
						if(trim($login) != '' && trim($login) != trim($username)) $parts[0] = trim($login);
						if(trim($password) != '') $parts[1] = trim($password);
						$new_line = implode(':',$parts);
						copy('/etc/shadow','/etc/shadow~');
						chmod('/etc/shadow~',0600);
						$app->system->replaceLine('/etc/shadow',$line,$new_line,1,0);
					}
				}
			}
			unset($shadow_file_array);
		}
	}

}
?>
