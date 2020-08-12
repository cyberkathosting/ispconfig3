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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

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
$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_import_vpopmail.lng';
include $lng_file;
$app->tpl->setVar($wb);

if(isset($_POST['db_hostname']) && $_POST['db_hostname'] != '') {

	//* Set external db client flags
	$db_client_flags = 0;
	if(isset($_POST['db_ssl']) && $_POST['db_ssl'] == 1) $db_client_flags |= MYSQLI_CLIENT_SSL;

	//* create new db object with external login details
	try {
		$exdb = new db($_POST['db_hostname'], $_POST['db_user'], $_POST['db_password'], $_POST['db_name'], 3306, $db_client_flags);
	} catch (Exception $e) {
		$error .= "Error connecting to database" . ($e->getMessage() ? ": " . $e->getMessage() : '.') . "<br />\n";
		$exdb = false;
	}

	if($exdb !== false) {
		$msg .= 'Databse connection succeeded<br />';

		$local_server_id = intval($_POST['local_server_id']);
		$tmp = $app->db->queryOneRecord("SELECT mail_server FROM server WHERE server_id = ?", $local_server_id);

		if($tmp['mail_server'] == 1) {
			start_import();
		} else {
			$msg .= 'The server with the ID $local_server_id is not a mail server.<br />';
		}
	}

} else {
	$_POST['local_server_id'] = 1;
}

$app->tpl->setVar('db_hostname', $_POST['db_hostname'], true);
$app->tpl->setVar('db_user', $_POST['db_user'], true);
$app->tpl->setVar('db_password', $_POST['db_password'], true);
$app->tpl->setVar('db_name', $_POST['db_name'], true);
$app->tpl->setVar('db_ssl', 'true', true);
$app->tpl->setVar('local_server_id', $_POST['local_server_id'], true);
$app->tpl->setVar('msg', $msg);
$app->tpl->setVar('error', $error);

$app->tpl_defaults();
$app->tpl->pparse();

//##########################################################

function start_import() {
	global $app, $conf, $msg, $error, $exdb, $local_server_id;

	//* Import the clients
	$records = $exdb->queryAllRecords("SELECT * FROM vpopmail WHERE pw_name = 'postmaster'");
	if(is_array($records)) {
		foreach($records as $rec) {
			$pw_domain = $rec['pw_domain'];
			//* Check if we have a client with that username already
			$tmp = $app->db->queryOneRecord("SELECT count(client_id) as number FROM client WHERE username = ?", $pw_domain);
			if($tmp['number'] == 0) {
				$pw_crypt_password = $app->auth->crypt_password($rec['pw_clear_passwd']);
				$country = 'FI';

				//* add client
				$sql = "INSERT INTO `client` (`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `company_name`, `company_id`, `contact_name`, `customer_no`, `vat_id`, `street`, `zip`, `city`, `state`, `country`, `telephone`, `mobile`, `fax`, `email`, `internet`, `icq`, `notes`, `bank_account_owner`, `bank_account_number`, `bank_code`, `bank_name`, `bank_account_iban`, `bank_account_swift`, `default_mailserver`, `limit_maildomain`, `limit_mailbox`, `limit_mailalias`, `limit_mailaliasdomain`, `limit_mailforward`, `limit_mailcatchall`, `limit_mailrouting`, `limit_mailfilter`, `limit_fetchmail`, `limit_mailquota`, `limit_spamfilter_wblist`, `limit_spamfilter_user`, `limit_spamfilter_policy`, `default_webserver`, `limit_web_ip`, `limit_web_domain`, `limit_web_quota`, `web_php_options`, `limit_cgi`, `limit_ssi`, `limit_perl`, `limit_ruby`, `limit_python`, `force_suexec`, `limit_hterror`, `limit_wildcard`, `limit_ssl`, `limit_web_subdomain`, `limit_web_aliasdomain`, `limit_ftp_user`, `limit_shell_user`, `ssh_chroot`, `limit_webdav_user`, `limit_aps`, `default_dnsserver`, `limit_dns_zone`, `limit_dns_slave_zone`, `limit_dns_record`, `default_dbserver`, `limit_database`, `limit_cron`, `limit_cron_type`, `limit_cron_frequency`, `limit_traffic_quota`, `limit_client`, `limit_mailmailinglist`, `limit_openvz_vm`, `limit_openvz_vm_template_id`, `parent_client_id`, `username`, `password`, `language`, `usertheme`, `template_master`, `template_additional`, `created_at`, `id_rsa`, `ssh_rsa`)
				VALUES(1, 1, 'riud', 'riud', '', '', '', ?, '', '', '', '', '', '', ?, '', '', '', '', 'http://', '', '', '', '', '', '', '', '', 1, -1, -1, -1, -1, -1, -1, 0, -1, -1, -1, 0, 0, 0, 1, NULL, -1, -1, 'no,fast-cgi,cgi,mod,suphp', 'n', 'n', 'n', 'n', 'n', 'y', 'n', 'n', 'n', -1, -1, -1, 0, 'no,jailkit', 0, 0, 1, -1, -1, -1, 1, -1, 0, 'url', 5, -1, 0, -1, 0, 0, 0, ?, ?, ?, 'default', 0, '', NOW(), '', '')";
				$app->db->query($sql, $pw_domain,$country, $pw_domain, $pw_crypt_password, $conf['language']);
				$client_id = $app->db->insertID();

				//* add sys_group
				$groupid = $app->db->datalogInsert('sys_group', array("name" => $pw_domain, "description" => '', "client_id" => $client_id), 'groupid');
				$groups = $groupid;

				$username = $pw_domain;
				$password = $pw_crypt_password;
				$modules = $conf['interface_modules_enabled'];
				$startmodule = 'dashboard';
				$usertheme = 'default';
				$type = 'user';
				$active = 1;
				$language = $conf["language"];
				//$password = $app->auth->crypt_password($password);

				// Create the controlpaneluser for the client
				//Generate ssh-rsa-keys
				$app->uses('functions');
				$app->functions->generate_ssh_key($client_id, $username);

				// Create the controlpaneluser for the client
				$sql = "INSERT INTO sys_user (username,passwort,modules,startmodule,app_theme,typ,active,language,groups,default_group,client_id)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
				$app->db->query($sql, $username,$password,$modules,$startmodule,$usertheme,$type,$active,$language,$groups,$groupid,$client_id);

				//* Set the default servers
				$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE mail_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
				$default_mailserver = $app->functions->intval($tmp['server_id']);
				$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE web_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
				$default_webserver = $app->functions->intval($tmp['server_id']);
				$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE dns_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
				$default_dnsserver = $app->functions->intval($tmp['server_id']);
				$tmp = $app->db->queryOneRecord('SELECT server_id FROM server WHERE db_server = 1 AND mirror_server_id = 0 LIMIT 0,1');
				$default_dbserver = $app->functions->intval($tmp['server_id']);

				$sql = "UPDATE client SET default_mailserver = ?, default_webserver = ?, default_dnsserver = ?, default_dbserver = ? WHERE client_id = ?";
				$app->db->query($sql, $default_mailserver, $default_webserver, $default_dnsserver, $default_dbserver, $client_id);

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
			$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM mail_domain WHERE domain = ?", $domain);
			if($tmp['number'] == 0) {
				$user_rec = $app->db->queryOneRecord("SELECT * FROM sys_user WHERE username = ?", $domain);
				$sys_userid = ($user_rec['userid'] > 0)?$user_rec['userid']:1;
				$sys_groupid = ($user_rec['default_group'] > 0)?$user_rec['default_group']:1;

				$sql = array(
					"sys_userid" => $sys_userid,
					"sys_groupid" => $sys_groupid,
					"sys_perm_user" => 'riud',
					"sys_perm_group" => 'riud',
					"sys_perm_other" => '',
					"server_id" => $local_server_id,
					"domain" => $domain,
					"active" => 'y'
				);
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
			$tmp = $app->db->queryOneRecord("SELECT count(mailuser_id) as number FROM mail_user WHERE email = ?", $email);

			if($tmp['number'] == 0) {

				//* get the mail domain for the mailbox
				$domain_rec = $app->db->queryOneRecord("SELECT * FROM mail_domain WHERE domain = ?", $domain);

				if(is_array($domain_rec)) {
					$pw_crypt_password = $app->auth->crypt_password($rec['pw_clear_passwd']);
					$maildir_path = "/var/vmail/".$rec['pw_domain']."/".$rec['pw_name'];

					//* Insert the mailbox
					$sql = array(
						"sys_userid" => $domain_rec['sys_userid'],
						"sys_groupid" => $domain_rec['sys_groupid'],
						"sys_perm_user" => 'riud',
						"sys_perm_group" => 'riud',
						"sys_perm_other" => '',
						"server_id" => $local_server_id,
						"email" => $email,
						"login" => $email,
						"password" => $pw_crypt_password,
						"name" => $email,
						"uid" => 5000,
						"gid" => 5000,
						"maildir" => $maildir_path,
						"quota" => 0,
						"cc" => '',
						"homedir" => '/var/vmail',
						"autoresponder" => 'n',
						"autoresponder_start_date" => null,
						"autoresponder_end_date" => null,
						"autoresponder_subject" => 'Out of office reply',
						"autoresponder_text" => '',
						"move_junk" => 'n',
						"custom_mailfilter" => '',
						"postfix" => 'y',
						"access" => 'n',
						"disableimap" => 'n',
						"disablepop3" => 'n',
						"disabledeliver" => 'n',
						"disablesmtp" => 'n',
						"disablesieve" => 'n',
						"disablelda" => 'n',
						"disablelmtp" => 'n',
						"disabledoveadm" => 'n'
					);
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

			if(stristr($rec['valias_line'], '|')) {
				//* Skipped
				$msg .= "Skipped $email as target is a script pipe.<br />";
			} elseif (substr(trim($rec['valias_line']), -9) == '/Maildir/') {
				$parts = explode('/', $rec['valias_line']);
				$target_user = $parts[count($parts)-3];
				$target_domain = $parts[count($parts)-4];
				$target = $target_user.'@'.$target_domain;
			} elseif (substr(trim($rec['valias_line']), 0, 1) == '&') {
				$target = substr(trim($rec['valias_line']), 1);
			} elseif (stristr($rec['valias_line'], '@')) {
				$target = $rec['valias_line'];
			} else {
				//* Unknown
				$msg .= "Skipped $email as format of target ".$rec['valias_line']." is unknown.<br />";
			}

			//* Check for duplicate forwards
			$tmp = $app->db->queryOneRecord("SELECT count(forwarding_id) as number FROM mail_forwarding WHERE source = ? AND destination = ?", $email, $target);

			if($tmp['number'] == 0 && $target != '') {

				//* get the mail domain
				$domain_rec = $app->db->queryOneRecord("SELECT * FROM mail_domain WHERE domain = ?", $rec['domain']);

				if(is_array($domain_rec)) {
					$sql = array(
						"sys_userid" => $domain_rec['sys_userid'],
						"sys_groupid" => $domain_rec['sys_groupid'],
						"sys_perm_user" => 'riud',
						"sys_perm_group" => 'riud',
						"sys_perm_other" => '',
						"server_id" => $local_server_id,
						"source" => $email,
						"destination" => $target,
						"type" => 'forward',
						"active" => 'y' 
					);
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
