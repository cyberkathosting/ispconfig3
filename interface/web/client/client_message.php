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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('client');

//* This function is not available in demo mode
if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

$app->uses('tpl');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/client_message.htm');

//* load language file 
$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_client_message.lng';
include($lng_file);
$app->tpl->setVar($wb);

$msg = '';
$error = '';

//* Save data
if(isset($_POST) && count($_POST) > 1) {
	
	//* Check values
	if(!preg_match("/^\w+[\w\.\-\+]*\w{0,}@\w+[\w.-]*\w+\.[a-zA-Z0-9\-]{2,30}$/i", $_POST['sender'])) $error .= $wb['sender_invalid_error'].'<br />';
	if(empty($_POST['subject'])) $error .= $wb['subject_invalid_error'].'<br />';
	if(empty($_POST['message'])) $error .= $wb['message_invalid_error'].'<br />';
	
	//* Send message
	if($error == '') {
		if(intval($_POST['recipient']) > 0){
			$circle = $app->db->queryOneRecord("SELECT client_ids FROM client_circle WHERE active = 'y' AND circle_id = ".intval($_POST['recipient']));
			if(isset($circle['client_ids']) && $circle['client_ids'] != ''){
				$tmp_client_ids = explode(',',$circle['client_ids']);
				$where = array();
				foreach($tmp_client_ids as $tmp_client_id){
					$where[] = 'client_id = '.$tmp_client_id;
				}
				if(!empty($where)) $where_clause = ' AND ('.implode(' OR ', $where).')';
				$sql = "SELECT * FROM client WHERE email != ''".$where_clause;
			} else {
				$sql = "SELECT * FROM client WHERE 0";
			}
		} else {
			//* Select all clients and resellers
			if($_SESSION["s"]["user"]["typ"] == 'admin'){
				$sql = "SELECT * FROM client WHERE email != ''";
			} else {
				$client_id = intval($_SESSION['s']['user']['client_id']);
				if($client_id == 0) die('Invalid Client ID.');
				$sql = "SELECT * FROM client WHERE email != '' AND parent_client_id = '$client_id'";
			}
		}
		
		//* Get clients
		$clients = $app->db->queryAllRecords($sql);
		if(is_array($clients)) {
			$msg = $wb['email_sent_to_txt'].' ';
			foreach($clients as $client) {
				//* Parse cleint details into message
				$message = $_POST['message'];
				foreach($client as $key => $val) {
					$message = str_replace('{'.$key.'}', $val, $message);
				}
				
				//* Send the email
				$app->functions->mail($client['email'], $_POST['subject'], $message, $_POST['sender']);
				$msg .= $client['email'].', ';
			}
			$msg = substr($msg,0,-2);
		}
		
	} else {
		$app->tpl->setVar('sender',$_POST['sender']);
		$app->tpl->setVar('subject',$_POST['subject']);
		$app->tpl->setVar('message',$_POST['message']);
	}
}

// Recipient Drop-Down
$recipient = '<option value="0">'.$wb['all_clients_resellers_txt'].'</option>';
$sql = "SELECT * FROM client_circle WHERE active = 'y'";
$circles = $app->db->queryAllRecords($sql);
if(is_array($circles) && !empty($circles)){
	foreach($circles as $circle){
		$recipient .= '<option value="'.$circle['circle_id'].'">'.$circle['circle_name'].'</option>';
	}
}
$app->tpl->setVar('recipient',$recipient);

if($_SESSION["s"]["user"]["typ"] == 'admin'){
	$app->tpl->setVar('form_legend_txt',$wb['form_legend_admin_txt']);
} else {
	$app->tpl->setVar('form_legend_txt',$wb['form_legend_client_txt']);
}

$app->tpl->setVar('okmsg',$msg);
$app->tpl->setVar('error',$error);

$app->tpl_defaults();
$app->tpl->pparse();


?>
