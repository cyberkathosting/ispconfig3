<?php
/*
Copyright (c) 2012, Till Brehm, ISPConfig UG
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
$app->auth->check_module_permissions('client');

//* This function is not available in demo mode
if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

$app->uses('tpl,tform');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/client_message.htm');

//* load language file
$lng_file = 'lib/lang/'.$app->functions->check_language($_SESSION['s']['language']).'_client_message.lng';
include $lng_file;
$app->tpl->setVar($wb);

$msg = '';
$error = '';

//* Save data
if(isset($_POST) && count($_POST) > 1) {
	
	//* CSRF Check
	$app->auth->csrf_token_check();
	
	//* Check values
	if(!preg_match("/^\w+[\w\.\-\+]*\w{0,}@\w+[\w.-]*\w+\.[a-zA-Z0-9\-]{2,30}$/i", $_POST['sender'])) $error .= $wb['sender_invalid_error'].'<br />';
	if(empty($_POST['subject'])) $error .= $wb['subject_invalid_error'].'<br />';
	if(empty($_POST['message'])) $error .= $wb['message_invalid_error'].'<br />';

	//* Send message
	if($error == '') {
		if($app->functions->intval($_POST['recipient']) > 0){
			$circle = $app->db->queryOneRecord("SELECT client_ids FROM client_circle WHERE active = 'y' AND circle_id = ? AND ".$app->tform->getAuthSQL('r'), $_POST['recipient']);
			if(isset($circle['client_ids']) && $circle['client_ids'] != ''){
				$tmp_client_ids = explode(',', $circle['client_ids']);
				$where = array();
				foreach($tmp_client_ids as $tmp_client_id){
					$where[] = 'client_id = '.$app->functions->intval($tmp_client_id);
				}
				if(!empty($where)) $where_clause = ' AND ('.implode(' OR ', $where).')';
				$sql = "SELECT * FROM client WHERE canceled != 'y' AND email != ''".$where_clause;
			} else {
				$sql = "SELECT * FROM client WHERE canceled != 'y'";
			}
		} else {
			//* Select all clients and resellers
			if($_SESSION["s"]["user"]["typ"] == 'admin'){
				$sql = "SELECT * FROM client WHERE email != '' AND canceled != 'y'";
			} else {
				$client_id = $app->functions->intval($_SESSION['s']['user']['client_id']);
				if($client_id == 0) die('Invalid Client ID.');
				$sql = "SELECT * FROM client WHERE email != '' AND canceled != 'y' AND parent_client_id = '$client_id'";
			}
		}

		//* Get clients
		$clients = $app->db->queryAllRecords($sql);
		if(is_array($clients)) {
			$msg = $wb['email_sent_to_txt'].' ';
			foreach($clients as $client) {
				//* Parse client details into message
				$message = $_POST['message'];
				foreach($client as $key => $val) {
					switch ($key) {
					case 'password':
						$message = str_replace('{'.$key.'}', '---', $message);
						break;
					case 'gender':
						$message = str_replace('{salutation}', $wb['gender_'.$val.'_txt'], $message);
						break;
					default:
						$message = str_replace('{'.$key.'}', $val, $message);
					}
				}

				//* Send the email
				$app->functions->mail($client['email'], $_POST['subject'], $message, $_POST['sender']);
				$msg .= $client['email'].', ';
			}
			$msg = substr($msg, 0, -2);
		}

	} else {
		$app->tpl->setVar('sender', $_POST['sender'], true);
		$app->tpl->setVar('subject', $_POST['subject'], true);
		$app->tpl->setVar('message', $_POST['message'], true);
	}
} else {
	// pre-fill Sender field with reseller's email address
	if($_SESSION["s"]["user"]["typ"] != 'admin'){
		$client_id = $app->functions->intval($_SESSION['s']['user']['client_id']);
		if($client_id > 0){
			$sql = "SELECT email FROM client WHERE client_id = ?";
			$client = $app->db->queryOneRecord($sql, $client_id);
			if($client['email'] != '') $app->tpl->setVar('sender', $client['email']);
		}
	}
}

// Recipient Drop-Down
$recipient = '<option value="0"'.($app->functions->intval($_POST['recipient']) == 0 ? ' selected="selected"' : '').'>'.($_SESSION["s"]["user"]["typ"] == 'admin'? $wb['all_clients_resellers_txt'] : $wb['all_clients_txt']).'</option>';
$sql = "SELECT * FROM client_circle WHERE active = 'y' AND ".$app->tform->getAuthSQL('r');
$circles = $app->db->queryAllRecords($sql);
if(is_array($circles) && !empty($circles)){
	foreach($circles as $circle){
		$recipient .= '<option value="'.$circle['circle_id'].'"'.($app->functions->intval($_POST['recipient']) == $circle['circle_id'] ? ' selected="selected"' : '').'>'.$circle['circle_name'].'</option>';
	}
}
$app->tpl->setVar('recipient', $recipient);

if($_SESSION["s"]["user"]["typ"] == 'admin'){
	$app->tpl->setVar('form_legend_txt', $wb['form_legend_admin_txt']);
} else {
	$app->tpl->setVar('form_legend_txt', $wb['form_legend_client_txt']);
}

//message variables
$message_variables = '';
$sql = "SHOW COLUMNS FROM client WHERE Field NOT IN ('client_id', 'sys_userid', 'sys_groupid', 'sys_perm_user', 'sys_perm_group', 'sys_perm_other', 'password', 'parent_client_id', 'id_rsa', 'ssh_rsa', 'created_at', 'default_mailserver', 'default_webserver', 'web_php_options', 'ssh_chroot', 'default_dnsserver', 'default_dbserver', 'template_master', 'template_additional', 'force_suexec', 'default_slave_dnsserver', 'usertheme', 'locked', 'canceled', 'can_use_api', 'tmp_data', 'customer_no_template', 'customer_no_start', 'customer_no_counter', 'added_date', 'added_by') AND Field NOT LIKE 'limit_%'";
$field_names = $app->db->queryAllRecords($sql);
if(!empty($field_names) && is_array($field_names)){
	foreach($field_names as $field_name){
		if($field_name['Field'] != ''){
			if($field_name['Field'] == 'gender'){
				$message_variables .= '<a href="javascript:void(0);" class="addPlaceholder">{salutation}</a> ';
			} else {
				$message_variables .= '<a href="javascript:void(0);" class="addPlaceholder">{'.$field_name['Field'].'}</a> ';
			}
		}
	}
}
$app->tpl->setVar('message_variables', trim($message_variables));

//* SET csrf token
$csrf_token = $app->auth->csrf_token_get('client_message');
$app->tpl->setVar('_csrf_id',$csrf_token['csrf_id']);
$app->tpl->setVar('_csrf_key',$csrf_token['csrf_key']);

$app->tpl->setVar('okmsg', $msg);
$app->tpl->setVar('error', $error);

$app->tpl_defaults();
$app->tpl->pparse();


?>
