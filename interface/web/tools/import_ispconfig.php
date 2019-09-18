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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');

//* This is only allowed for administrators
if(!$app->auth->is_admin()) die('only allowed for administrators.');

$app->uses('tpl,validate_dns');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/import_ispconfig.htm');
$msg = '';
$error = '';

//* load language file
$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_import_ispconfig.lng';
include $lng_file;
$app->tpl->setVar($wb);

if(isset($_POST['connected'])) {
	
	//* CSRF Check
	$app->auth->csrf_token_check();
	
	$connected = $app->functions->intval($_POST['connected']);
	if($connected == 0) {

		//* Try to connect to remote server
		if(empty($_POST['remote_server'])) $error .= 'Remote Server is empty.</br>';
		if(empty($_POST['remote_user'])) $error .= 'Remote User is empty.</br>';
		if(empty($_POST['remote_password'])) $error .= 'Remote Password is empty.</br>';

		if($error == '') {
			try {
				
				//* Allow connections to self signed SSL certs
				$context = stream_context_create(
					array(
						'ssl' => array (
						'verify_peer' => false,
						'verify_peer_name' => false,
						'allow_self_signed' => true
						)
					)
				);
				
				$client = new SoapClient(null, array('location' => $_POST['remote_server'],
						'uri'      => $_POST['remote_server'].'/index.php',
						'trace' => 1,
						'exceptions' => 1,
						'stream_context' => $context));

				if($remote_session_id = $client->login($_POST['remote_user'], $_POST['remote_password'])) {
					$connected = 1;
					$msg .= 'Successfully connected to remote server.';
				}
			} catch (SoapFault $e) {
				//echo $client->__getLastResponse();
				$error .= $e->getMessage();
				$connected = 0;
			}
		}
	}

	if($connected == 1) {

		//* Fill the client select field
		$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0 ORDER BY name";
		$clients = $app->db->queryAllRecords($sql);
		$client_select = "";
		if(is_array($clients)) {
			foreach( $clients as $client) {
				$selected = @($client['groupid'] == $_POST['client_group_id'])?'SELECTED':'';
				$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
			}
		}
		$app->tpl->setVar("client_group_id", $client_select);

		//* Fill the mail server select field
		$sql = "SELECT server_id, server_name FROM server WHERE mail_server = 1 and mirror_server_id = 0";
		$mail_servers = $app->db->queryAllRecords($sql);
		$mail_server_select = "";
		if(is_array($mail_servers)) {
			foreach( $mail_servers as $m_server) {
				$selected = @($m_server['server_id'] == $_POST['local_server_id'])?'SELECTED':'';
				$mail_server_select .= "<option value='$m_server[server_id]' $selected>$m_server[server_name]</option>\r\n";
			}
		}
		$app->tpl->setVar("local_server_id", $mail_server_select);

		try {
			//* Allow connections to self signed SSL certs
			$context = stream_context_create(
				array(
					'ssl' => array (
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
					)
				)
			);
				
			$client = new SoapClient(null, array('location' => $_POST['remote_server'],
					'uri'      => $_POST['remote_server'].'/index.php',
					'trace' => 1,
					'exceptions' => 1,
					'stream_context' => $context));

			if(!isset($remote_session_id)) $remote_session_id = $_POST['remote_session_id'];

			//* Get all email domains
			$mail_domains = $client->mail_domain_get($remote_session_id, array('active' => 'y'));
			$mail_domain_select = '<option value="">-- select domain --</option>';
			if(is_array($mail_domains)) {
				foreach( $mail_domains as $mail_domain) {
					$selected = @($mail_domain['domain'] == $_POST['mail_domain'])?'SELECTED':'';
					$mail_domain_select .= "<option value='$mail_domain[domain]' $selected>$mail_domain[domain]</option>\r\n";
				}
			}
			$app->tpl->setVar("mail_domain", $mail_domain_select);

			//* Do the import
			if($_POST['mail_domain'] != '') start_domain_import($_POST['mail_domain']);



		} catch (SoapFault $e) {
			//echo $client->__getLastResponse();
			$error .= $e->getMessage();
			$connected = 0;
		}

	}

}

$app->tpl->setVar('remote_server', $_POST['remote_server'], true);
$app->tpl->setVar('remote_user', $_POST['remote_user'], true);
$app->tpl->setVar('remote_password', $_POST['remote_password'], true);
$app->tpl->setVar('connected', $connected);
$app->tpl->setVar('remote_session_id', $remote_session_id);
$app->tpl->setVar('msg', $msg);
$app->tpl->setVar('error', $error);
$app->tpl->setVar('local_server_id', $_POST['local_server_id'], true);

//* SET csrf token
$csrf_token = $app->auth->csrf_token_get('ispconfig_import');
$app->tpl->setVar('_csrf_id',$csrf_token['csrf_id']);
$app->tpl->setVar('_csrf_key',$csrf_token['csrf_key']);

$app->tpl_defaults();
$app->tpl->pparse();

//##########################################################

function start_domain_import($mail_domain) {
	global $app, $conf, $client, $msg, $error, $remote_session_id;

	//* Get the user and groupid for the new records
	$sys_groupid = $app->functions->intval($_POST['client_group_id']);
	$tmp = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE default_group = ?", $sys_groupid);
	$sys_userid = $app->functions->intval($tmp['userid']);
	unset($tmp);
	if($sys_groupid == 0) $error .= 'Inavlid groupid<br />';
	if($sys_userid == 0) $error .= 'Inavlid Userid<br />';

	//* Get the mail server ID
	$tmp = $app->db->queryOneRecord("SELECT server_id FROM server WHERE mail_server = 1 and mirror_server_id = 0 LIMIT 0,1");
	$server_id = intval($tmp['server_id']);
	unset($tmp);
	if($server_id == 0) $server_id = 1;
	
    if (isset($_POST['local_server_id']) && intval($_POST['local_server_id']) !== $server_id){
        $server_id = intval($_POST['local_server_id']);
    }
	//* get the mail domain record
	$mail_domain_rec = $client->mail_domain_get($remote_session_id, array('domain' => $mail_domain));
	if(is_array($mail_domain_rec)) {
		$mail_domain_rec = $mail_domain_rec[0];
		$tmp = $app->db->queryOneRecord("SELECT count(domain_id) as number FROM mail_domain WHERE domain = ?", $mail_domain);
		if($tmp['number'] > 0) $error .= 'Domain '.$mail_domain.' exists already in local database.<br />';
		unset($tmp);

		//* Change the record owner and remove the index field
		$mail_domain_rec['sys_userid'] = $sys_userid;
		$mail_domain_rec['sys_groupid'] = $sys_groupid;
		$mail_domain_rec['server_id'] = $server_id;
		unset($mail_domain_rec['domain_id']);

		//* Insert domain if no error occurred
		if($error == '') {
			$app->db->datalogInsert('mail_domain', $mail_domain_rec, 'domain_id');
			$msg .= "Imported mail domain ".$mail_domain_rec['domain']."<br />";
		} else {
			return false;
		}

		//* Import mailboxes
		if(isset($_POST['import_mailbox']) && $_POST['import_mailbox'] == 1) {
			$mail_users = $client->mail_user_get($remote_session_id, array('email' => '%@'.$mail_domain));
			if(is_array($mail_users)) {
				foreach($mail_users as $mail_user) {
					$tmp = $app->db->queryOneRecord("SELECT count(mailuser_id) as number FROM mail_user WHERE email = ?", $mail_user['email']);
					if($tmp['number'] == 0) {

						//* Prepare record
						$mail_user['sys_userid'] = $sys_userid;
						$mail_user['sys_groupid'] = $sys_groupid;
						$mail_user['server_id'] = $server_id;
						$remote_mailuser_id = $mail_user['mailuser_id'];
						unset($mail_user['mailuser_id']);
						if(!isset($_POST['import_user_filter'])) $mail_user['custom_mailfilter'] = '';

						//* Insert record in DB
						$local_mailuser_id = $app->db->datalogInsert('mail_user', $mail_user, 'mailuser_id');
						$msg .= "Imported mailbox ".$mail_user['email']."<br />";

						//* Import mail user filters
						if(isset($_POST['import_user_filter']) && $_POST['import_user_filter'] == 1 && $local_mailuser_id > 0) {

							$mail_user_filters = $client->mail_user_filter_get($remote_session_id, array('mailuser_id' => $remote_mailuser_id));

							if(is_array($mail_user_filters)) {
								foreach($mail_user_filters as $mail_user_filter) {
									$mail_user_filter['sys_userid'] = $sys_userid;
									$mail_user_filter['sys_groupid'] = $sys_groupid;
									$mail_user_filter['mailuser_id'] = $local_mailuser_id;
									$mail_user_filter['server_id'] = $server_id;
									unset($mail_user_filter['filter_id']);

									//* Insert record in DB
									$app->db->datalogInsert('mail_user_filter', $mail_user_filter, 'filter_id');
									$msg .= "Imported mailbox filter ".$mail_user['email'].": ".$mail_user_filter['rulename']."<br />";
								}
							}
						}
					} else {
						$error .= "Mailbox ".$mail_user['email']." exists in local database. Skipped import of mailbox<br />";
					}

				}
			}
		}

		//* Import email aliases
		if(isset($_POST['import_alias']) && $_POST['import_alias'] == 1) {
			$mail_aliases = $client->mail_alias_get($remote_session_id, array('type' => 'alias', 'destination' => '%@'.$mail_domain));
			if(is_array($mail_aliases)) {
				foreach($mail_aliases as $mail_alias) {
					$tmp = $app->db->queryOneRecord("SELECT count(forwarding_id) as number FROM mail_forwarding WHERE `type` = 'alias' AND source = ? AND destination = ?", $mail_alias['source'], $mail_alias['destination']);
					if($tmp['number'] == 0) {
						$mail_alias['sys_userid'] = $sys_userid;
						$mail_alias['sys_groupid'] = $sys_groupid;
						$mail_alias['server_id'] = $server_id;
						unset($mail_alias['forwarding_id']);
						$app->db->datalogInsert('mail_forwarding', $mail_alias, 'forwarding_id');
						$msg .= "Imported email alias ".$mail_alias['source']."<br />";
					} else {
						$error .= "Email alias ".$mail_alias['source']." exists in local database. Skipped import.<br />";
					}

				}
			}
		}

		//* Import domain aliases
		if(isset($_POST['import_aliasdomain']) && $_POST['import_aliasdomain'] == 1) {
			$mail_aliases = $client->mail_alias_get($remote_session_id, array('type' => 'aliasdomain', 'destination' => '@'.$mail_domain));
			if(is_array($mail_aliases)) {
				foreach($mail_aliases as $mail_alias) {
					$tmp = $app->db->queryOneRecord("SELECT count(forwarding_id) as number FROM mail_forwarding WHERE `type` = 'aliasdomain' AND source = ? AND destination = ?", $mail_alias['source'], $mail_alias['destination']);
					if($tmp['number'] == 0) {
						$mail_alias['sys_userid'] = $sys_userid;
						$mail_alias['sys_groupid'] = $sys_groupid;
						$mail_alias['server_id'] = $server_id;
						unset($mail_alias['forwarding_id']);
						$app->db->datalogInsert('mail_forwarding', $mail_alias, 'forwarding_id');
						$msg .= "Imported email aliasdomain ".$mail_alias['source']."<br />";
					} else {
						$error .= "Email aliasdomain ".$mail_alias['source']." exists in local database. Skipped import.<br />";
					}

				}
			}
		}

		//* Import email forward
		if(isset($_POST['import_forward']) && $_POST['import_forward'] == 1) {
			$mail_forwards = $client->mail_forward_get($remote_session_id, array('type' => 'forward', 'source' => '%@'.$mail_domain));
			if(is_array($mail_forwards)) {
				foreach($mail_forwards as $mail_forward) {
					$tmp = $app->db->queryOneRecord("SELECT count(forwarding_id) as number FROM mail_forwarding WHERE `type` = 'forward' AND source = ? AND destination = ?", $mail_forward['source'], $mail_forward['destination']);
					if($tmp['number'] == 0) {
						$mail_forward['sys_userid'] = $sys_userid;
						$mail_forward['sys_groupid'] = $sys_groupid;
						$mail_forward['server_id'] = $server_id;
						unset($mail_forward['forwarding_id']);
						$app->db->datalogInsert('mail_forwarding', $mail_forward, 'forwarding_id');
						$msg .= "Imported email forward ".$mail_forward['source']."<br />";
					} else {
						$error .= "Email forward ".$mail_forward['source']." exists in local database. Skipped import.<br />";
					}

				}
			}
		}

		//* Import spamfilter
		if(isset($_POST['import_spamfilter']) && $_POST['import_spamfilter'] == 1) {
			$mail_spamfilters = $client->mail_spamfilter_user_get($remote_session_id, array('email' => '%@'.$mail_domain));
			if(is_array($mail_spamfilters)) {
				foreach($mail_spamfilters as $mail_spamfilter) {
					$tmp = $app->db->queryOneRecord("SELECT count(id) as number FROM spamfilter_users WHERE email = ?", $mail_spamfilter['email']);
					if($tmp['number'] == 0) {
						$mail_spamfilter['sys_userid'] = $sys_userid;
						$mail_spamfilter['sys_groupid'] = $sys_groupid;
						$mail_spamfilter['server_id'] = $server_id;
						unset($mail_spamfilter['id']);
						$app->db->datalogInsert('spamfilter_users', $mail_spamfilter, 'id');
						$msg .= "Imported spamfilter user ".$mail_spamfilter['email']."<br />";
					} else {
						$error .= "Spamfilter user ".$mail_spamfilter['email']." exists in local database. Skipped import.<br />";
					}

				}
			}
		}

	}

}


?>
