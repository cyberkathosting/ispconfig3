<?php

/*
Copyright (c) 2014, Till Brehm, ISPConfig UG
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

require "/usr/local/ispconfig/server/lib/config.inc.php";
require "/usr/local/ispconfig/server/lib/app.inc.php";

set_time_limit(0);
ini_set('error_reporting', E_ALL & ~E_NOTICE);

// make sure server_id is always an int
$conf['server_id'] = intval($conf['server_id']);


// Load required base-classes
$app->uses('ini_parser,file,services,getconf,system');

// get security config
$security_config = $app->getconf->get_security_config('systemcheck');

$alert = '';
$data_dir = '/usr/local/ispconfig/security/data';


// Check if a new ispconfig user has been added
if($security_config['warn_new_admin'] == 'yes') {
	$data_file = $data_dir.'/admincount';
	//get number of admins
	$tmp = $app->db->queryOneRecord("SELECT count(userid) AS number FROM sys_user WHERE typ = 'admin'");
	if($tmp) {
		$admin_user_count_new = intval($tmp['number']);
		
		if(is_file($data_file)) {
			$admin_user_count_old = intval(file_get_contents($data_file));
			if($admin_user_count_new != $admin_user_count_old) {
				$alert .= "The number of ISPConfig administrator users has changed. Old: $admin_user_count_old New: $admin_user_count_new \n";
				file_put_contents($data_file,$admin_user_count_new);
			}
		} else {
			// first run, so we save the current count
			file_put_contents($data_file,$admin_user_count_new);
			chmod($data_file,0700);
		}
	}
}

// Check if /etc/passwd file has been changed
if($security_config['warn_passwd_change'] == 'yes') {
	$data_file = $data_dir.'/passwd.md5';
	$md5sum_new = md5_file('/etc/passwd');
	
	if(is_file($data_file)) {
		$md5sum_old = trim(file_get_contents($data_file));
		if($md5sum_new != $md5sum_old) {
			$alert .= "The file /etc/passwd has been changed.\n";
			file_put_contents($data_file,$md5sum_new);
		}
	} else {
		file_put_contents($data_file,$md5sum_new);
		chmod($data_file,0700);
	}
}

// Check if /etc/shadow file has been changed
if($security_config['warn_shadow_change'] == 'yes') {
	$data_file = $data_dir.'/shadow.md5';
	$md5sum_new = md5_file('/etc/shadow');
	
	if(is_file($data_file)) {
		$md5sum_old = trim(file_get_contents($data_file));
		if($md5sum_new != $md5sum_old) {
			$alert .= "The file /etc/shadow has been changed.\n";
			file_put_contents($data_file,$md5sum_new);
		}
	} else {
		file_put_contents($data_file,$md5sum_new);
		chmod($data_file,0700);
	}
}

// Check if /etc/group file has been changed
if($security_config['warn_group_change'] == 'yes') {
	$data_file = $data_dir.'/group.md5';
	$md5sum_new = md5_file('/etc/group');
	
	if(is_file($data_file)) {
		$md5sum_old = trim(file_get_contents($data_file));
		if($md5sum_new != $md5sum_old) {
			$alert .= "The file /etc/group has been changed.\n";
			file_put_contents($data_file,$md5sum_new);
		}
	} else {
		file_put_contents($data_file,$md5sum_new);
		chmod($data_file,0700);
	}
}


if($alert != '') {
	$admin_email = $security_config['security_admin_email'];
	$admin_email_subject = $security_config['security_admin_email_subject'];
	mail($admin_email, $admin_email_subject, $alert);
	//$app->log(str_replace("\n"," -- ",$alert),1);
	echo str_replace("\n"," -- ",$alert)."\n";
}
























?>