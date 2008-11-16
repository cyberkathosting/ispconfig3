<?php

/*
Copyright (c) 2007-2008, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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
$app->auth->check_module_permissions('monitor');

// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/show_log.htm');

// Importing the GET values
$refresh = (isset($_GET["refresh"]))?intval($_GET["refresh"]):0;
$logParam = $_GET["log"];


/*
 Setting the db-type and the caption
 */
switch($logParam) {
	case 'log_mail':
		$logId = 'log_mail';
		$title = 'Mail - Log (Server: ' . $_SESSION['monitor']['server_name'] . ')';
		break;
	case 'log_mail_warn':
		$logId = 'log_mail_warn';
		$title = 'Mail-Warn - Log (Server: ' . $_SESSION['monitor']['server_name'] . ')';
		break;
	case 'log_mail_err':
		$logId = 'log_mail_err';
		$title = 'Mail-Error - Log (Server: ' . $_SESSION['monitor']['server_name'] . ')';
		break;
	case 'log_messages':
		$logId = 'log_messages';
		$title = 'Messages (Server: ' . $_SESSION['monitor']['server_name'] . ')';
		break;
	case 'log_freshclam':
		$logId = 'log_freshclam';
		$title = 'Freshclam - Log (Server: ' . $_SESSION['monitor']['server_name'] . ')';
		break;
	case 'log_clamav':
		$logId = 'log_clamav';
		$title = 'Clamav - Log (Server: ' . $_SESSION['monitor']['server_name'] . ')';
		break;
	case 'log_ispconfig':
		$logId = 'log_ispconfig';
		$title = 'ISP Config - Log (Server: ' . $_SESSION['monitor']['server_name'] . ')';
		break;
	default:
		$logId = '???';
		$title = '???';
		break;
}


/*
 Creating the array with the refresh intervals
 Attention: the core-moule ist triggered every 5 minutes, 
            so reload every 2 minutes is impossible!
*/
$refresh_values = array('0' => '- '.$app->lng("No Refresh").' -','5' => '5 '.$app->lng("minutes"),'10' => '10 '.$app->lng("minutes"),'15' => '15 '.$app->lng("minutes"),'30' => '30 '.$app->lng("minutes"),'60 ' => '60'.$app->lng("minutes"));
$tmp = '';
foreach($refresh_values as $key => $val) {
	if($key == $refresh) {
		$tmp .= "<option value='$key' SELECTED>$val</option>";
	} else {
		$tmp .= "<option value='$key'>$val</option>";
	}
}
$app->tpl->setVar("refresh",$tmp);


/* fetch the Data from the DB */
$record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = '" . $app->db->quote($logId) . "' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

if(isset($record['data'])) {
	$data = unserialize($record['data']);

	$logData = nl2br($data);

	$app->tpl->setVar("log_data", $logData);
} else {
	$app->tpl->setVar("log_data", $app->lng("no_logdata_txt"));
}

$app->tpl->setVar("title", $title);
$app->tpl->setVar("log_id",$logId);


$app->tpl_defaults();
$app->tpl->pparse();
?>