<?php
/*
Copyright (c) 2012, Till Brehm, projektfarm Gmbh, ISPConfig UG
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

set_time_limit(0);

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

$app->uses('tpl,auth');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/import_vpopmail.htm');
$msg = '';
$error = '';

//* load language file
$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_import_vpopmail.lng';
include($lng_file);
$app->tpl->setVar($wb);

if(isset($_POST['db_hostname']) && $_POST['db_hostname'] != '') {
	
	//* Set external Login details
	$conf['imp_db_host'] = $_POST['db_hostname'];
	$conf['imp_db_database'] = $_POST['db_name'];
	$conf['imp_db_user'] = $_POST['db_user'];
	$conf['imp_db_password'] = $_POST['db_password'];
    $conf['imp_db_charset'] = 'utf8';
    $conf['imp_db_new_link'] = false;
    $conf['imp_db_client_flags'] = 0;
	
	//* create new db object
	$exdb = new db('imp');
	
	if($exdb !== false) {
		$msg .= 'Databse connection succeeded<br />';
		
		$local_server_id = intval($_POST['local_server_id']);
		$tmp = $app->db->queryOneRecord("SELECT mail_server FROM server WHERE server_id = $local_server_id");
		
		if($tmp['mail_server'] == 1) {
			start_import();
		} else {
			$msg .= 'The server with the ID $local_server_id is not a mail server.<br />';
		}
		
	} else {
		$msg .= 'Database connection failed<br />';
	}
	
} else {
	$_POST['local_server_id'] = 1;
}

$app->tpl->setVar('db_hostname',$_POST['db_hostname']);
$app->tpl->setVar('db_user',$_POST['db_user']);
$app->tpl->setVar('db_password',$_POST['db_password']);
$app->tpl->setVar('db_name',$_POST['db_name']);
$app->tpl->setVar('local_server_id',$_POST['local_server_id']);
$app->tpl->setVar('msg',$msg);
$app->tpl->setVar('error',$error);

$app->tpl_defaults();
$app->tpl->pparse();

###########################################################

function start_import() {
	global $app, $conf, $msg, $error, $exdb, $local_server_id;
	
	//* Import the clients
	$records = $exdb->queryAllRecords("SELECT * FROM vpopmail WHERE pw_name = 'postmaster'");	
	if(is_array($records)) {
		foreach($records as $rec) {
			$pw_domain = $rec['pw_domain'];
			//* Check if we have a client with that username already
			$tmp = $app->db->queryOneRecord("SELECT count(client_id) as number FROM client WHERE username = '$pw_domain'");
			if($tmp['number'] == 0) {
				$pw_crypt_password = $app->auth->crypt_password($rec['pw_clear_passwd']);
				$country = 'FI';
				
				//* add client
				$sql = "INSERT INTO `client` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `company_name`, `company_id`, `contact_name`, `customer_no`, `vat_id`, `street`, `zip`, `city`, `state`, `country`, `telephone`, `mobile`, `fax`, `email`, `internet`, `icq`, `notes`, `bank_account_owner`, `bank_account_number`, `bank_code`, `bank_name`, `bank_account_iban`, `bank_account_swift`, `default_mailserver`, `limit_maildomain`, `limit_mailbox`, `limit_mailalias`, `limit_mailaliasdomain`, `limit_mailforward`, `limit_mailcatchall`, `limit_mailrouting`, `limit_mailfilter`, `limit_fetchmail`, `limit_mailquota`, `limit_spamfilter_wblist`, `limit_spamfilter_user`, `limit_spamfilter_policy`, `default_webserver`, `limit_web_ip`, `limit_web_domain`, `limit_web_quota`, `web_php_options`, `limit_cgi`, `limit_ssi`, `limit_perl`, `limit_ruby`, `limit_python`, `force_suexec`, `limit_hterror`, `limit_wildcard`, `limit_ssl`, `limit_web_subdomain`, `limit_web_aliasdomain`, `limit_ftp_user`, `limit_shell_user`, `ssh_chroot`, `limit_webdav_user`, `limit_aps`, `default_dnsserver`, `limit_dns_zone`, `limit_dns_slave_zone`, `limit_dns_record`, `default_dbserver`, `limit_database`, `limit_cron`, `limit_cron_type`, `limit_cron_frequency`, `limit_traffic_quota`, `limit_client`, `limit_mailmailinglist`, `limit_openvz_vm`, `limit_openvz_vm_template_id`, `parent_client_id`, `username`, `password`, `language`, `usertheme`, `template_master`, `template_additional`, `created_at`, `id_rsa`, `ssh_rsa`) 
				VALUES(1, 1, 'riud', 'riud', '', '', '', '$pw_domain', '', '', '', '', '', '', '$country', '', '', '', '', 'http://', '', '', '', '', '', '', '', '', 1, -1, -1, -1, -1, -1, -1, 0, -1, -1, -1, 0, 0, 0, 1, NULL, -1, -1, 'no,fast-cgi,cgi,mod,suphp', 'n', 'n', 'n', 'n', 'n', 'y', 'n', 'n', 'n', -1, -1, -1, 0, 'no,jailkit', 0, 0, 1, -1, -1, -1, 1, -1, 0, 'url', 5, -1, 0, -1, 0, 0, 0, '$pw_domain', '$pw_crypt_password', '".$conf['language']."', 'default', 0, '', NOW(), '', '')";
				$app->db->query($sql);
				$client_id = $app->db->insertID();
				
				//* add sys_group
				$groupid = $app->db->datalogInsert('sys_group', "(name,description,client_id) VALUES ('".$app->db->quote($pw_domain)."','',".$client_id.")", 'groupid');
				$groups = $groupid;
				
				$username = $app->db->quote($pw_domain);
				$password = $pw_crypt_password;
				$modules = $conf['interface_modules_enabled'];
				$startmodule = 'dashboard';
				$usertheme = $app->db->quote('default');
				$type = 'user';
				$active = 1;
				$language = $app->db->quote($conf["language"]);
				//$password = $app->auth->crypt_password($password);
		
				// Create the controlpaneluser for the client
				//Generate ssh-rsa-keys
				exec('ssh-keygen -t rsa -C '.$username.'-rsa-key-'.time().' -f /tmp/id_rsa -N ""');
				$app->db->query("UPDATE client SET created_at = ".time().", id_rsa = '".$app->db->quote(@file_get_contents('/tmp/id_rsa'))."', ssh_rsa = '".$app->db->quote(@file_get_contents('/tmp/id_rsa.pub'))."' WHERE client_id = ".$client_id);
				exec('rm -f /tmp/id_rsa /tmp/id_rsa.pub');
		
				// Create the controlpaneluser for the client
				$sql = "INSERT INTO sys_user (username,passwort,modules,startmodule,app_theme,typ,active,language,groups,default_group,client_id)
				VALUES ('$username','$password','$modules','$startmodule','$usertheme','$type','$active','$language',$groups,$groupid,".$client_id.")";
				$app->db->query($sql);
		
				//* Set the default servers
				$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE mail_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
				$default_mailserver = $app->functions->intval($tmp['server_id']);
				$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE web_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
				$default_webserver = $app->functions->intval($tmp['server_id']);
				$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE dns_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
				$default_dnsserver = $app->functions->intval($tmp['server_id']);
				$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE db_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
				$default_dbserver = $app->functions->intval($tmp['server_id']);
		
				$sql = "UPDATE client SET default_mailserver = $default_mailserver, default_webserver = $default_webserver, default_dnsserver = $default_dnsserver, default_dbserver = $default_dbserver WHERE client_id = ".$client_id;
				$app->db->query($sql);
				
				$msg .= "Added Client $username.<br />";
			} else {
				$msg .= "Client $username exists, skipped.<br />";
			}
		}
	}
	
	//* Import the mail domains
	$records = $exdb->queryAllRecords("SELECT DISTINCT pw_domain FROM `vpopmail`");	
	if(is_array($records)) {
		foreach($records as $rec) {
			$domain = $rec['pw_domain'];
			
			//* Check if domain exists already
			$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM mail_domain WHERE domain = '$domain'");
			if($tmp['number'] == 0) {
				$user_rec = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE username = '$domain'");
				$sys_userid = ($user_rec['userid'] > 0)?$user_rec['userid']:1;
				$sys_groupid = ($user_rec['default_group'] > 0)?$user_rec['default_group']:1;
				
				$sql = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `domain`, `active`) 
				VALUES(".$sys_userid.", ".$sys_groupid.", 'riud', 'riud', '', $local_server_id, '$domain', 'y')";
				$app->db->datalogInsert('mail_domain', $sql, 'domain_id');
				$msg .= "Imported domain $domain <br />";
			} else {
				$msg .= "Skipped domain $domain <br />";
			}
		}
	}
	
	//* Import mailboxes
	$records = $exdb->queryAllRecords("SELECT * FROM `vpopmail`");	
	if(is_array($records)) {
		foreach($records as $rec) {
			$domain = $rec['pw_domain'];
			$email = $rec['pw_name'].'@'.$rec['pw_domain'];
			
			//* Check for duplicate mailboxes
			$tmp = $app->db->queryOneRecord("SELECT count(mailuser_id) as number FROM mail_user WHERE email = '".$app->db->quote($email)."'");
			
			if($tmp['number'] == 0) {
			
				//* get the mail domain for the mailbox
				$domain_rec = $app->db->queryOneRecord("SELECT * FROM mail_domain WHERE domain = '$domain'");
				
				if(is_array($domain_rec)) {
					$pw_crypt_password = $app->auth->crypt_password($rec['pw_clear_passwd']);
					$maildir_path = "/var/vmail/".$rec['pw_domain']."/".$rec['pw_name'];
				
					//* Insert the mailbox
					$sql = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `email`, `login`, `password`, `name`, `uid`, `gid`, `maildir`, `quota`, `cc`, `homedir`, `autoresponder`, `autoresponder_start_date`, `autoresponder_end_date`, `autoresponder_subject`, `autoresponder_text`, `move_junk`, `custom_mailfilter`, `postfix`, `access`, `disableimap`, `disablepop3`, `disabledeliver`, `disablesmtp`, `disablesieve`, `disablelda`, `disabledoveadm`) 
					VALUES(".$domain_rec['sys_userid'].", ".$domain_rec['sys_groupid'].", 'riud', 'riud', '', $local_server_id, '$email', '$email', '$pw_crypt_password', '$email', 5000, 5000, '$maildir_path', 0, '', '/var/vmail', 'n', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Out of office reply', '', 'n', '', 'y', 'n', 'n', 'n', 'n', 'n', 'n', 'n', 'n')";
					$app->db->datalogInsert('mail_user', $sql, 'mailuser_id');
					$msg .= "Imported mailbox $email <br />";
				}
			}else {
				$msg .= "Skipped mailbox $email <br />";
			}
		}
	}
	
	//* Import Aliases
	$records = $exdb->queryAllRecords("SELECT * FROM `valias`");	
	if(is_array($records)) {
		foreach($records as $rec) {
			
			$email = $rec['alias'].'@'.$rec['domain'];
			$target = '';
			
			if(stristr($rec['valias_line'],'|')) {
				//* Skipped
				$msg .= "Skipped $email as target is a script pipe.<br />";
			} elseif (substr(trim($rec['valias_line']),-9) == '/Maildir/') {
				$parts = explode('/',$rec['valias_line']);
				$target_user = $parts[count($parts)-3];
				$target_domain = $parts[count($parts)-4];
				$target = $target_user.'@'.$target_domain;
			} elseif (substr(trim($rec['valias_line']),0,1) == '&') {
				$target = substr(trim($rec['valias_line']),1);
			} elseif (stristr($rec['valias_line'],'@')) {
				$target = $rec['valias_line'];
			} else {
				//* Unknown
				$msg .= "Skipped $email as format of target ".$rec['valias_line']." is unknown.<br />";
			}
			
			//* Check for duplicate forwards
			$tmp = $app->db->queryOneRecord("SELECT count(forwarding_id) as number FROM mail_forwarding WHERE source = '".$app->db->quote($email)."' AND destination = '".$app->db->quote($target)."'");
			
			if($tmp['number'] == 0 && $target != '') {
				
				//* get the mail domain
				$domain_rec = $app->db->queryOneRecord("SELECT * FROM mail_domain WHERE domain = '".$rec['domain']."'");
				
				if(is_array($domain_rec)) {
					$sql = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `source`, `destination`, `type`, `active`) 
					VALUES(".$domain_rec['sys_userid'].", ".$domain_rec['sys_groupid'].", 'riud', 'riud', '', $local_server_id, '".$app->db->quote($email)."', '".$app->db->quote($target)."', 'forward', 'y')";
					$app->db->datalogInsert('mail_forwarding', $sql, 'forwarding_id');
				}
				$msg .= "Imported alias $email.<br />";
			} else {
				$msg .= "Skipped alias $email as it exists already.<br />";
			}
		}
	}
	
}


?>