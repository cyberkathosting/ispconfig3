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

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('monitor');

// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/logview.htm');

// Importing the GET values
$refresh = intval($_GET["refresh"]);
$logfile_id = $_GET["log"];

// Creating the array with the refresh intervals
$refresh_values = array('0' => '- No Refresh -','2' => '2','5' => '5','10' => '10','15' => '15','30' => '30','60' => '60');
$tmp = '';
foreach($refresh_values as $key => $val) {
	if($key == $refresh) {
		$tmp .= "<option value='$key' SELECTED>$val</option>";
	} else {
		$tmp .= "<option value='$key'>$val</option>";
	}
}
$app->tpl->setVar("refresh",$tmp);

// Selecting the logfile
switch($logfile_id) {
	case 'mail_log':
		$logfile = '/var/log/mail.log';
	break;
	case 'mail_warn':
		$logfile = '/var/log/mail.warn';
	break;
	case 'mail_err':
		$logfile = '/var/log/mail.err';
	break;
	case 'messages':
		$logfile = '/var/log/messages';
	break;
	case 'freshclam':
		$logfile = '/var/log/clamav/freshclam.log';
	break;
	case 'clamav':
		$logfile = '/var/log/clamav/clamav.log';
	break;
	case 'ispconfig':
		$logfile = '/var/log/ispconfig/ispconfig.log';
	break;
	default:
		$logfile = '';
	break;
}

// Getting the logfile content
if($logfile != '') {
	$logfile = escapeshellcmd($logfile);
	if(stristr($logfile,';')) die('Logfile path error.');
	
	$log = '';
	if(is_readable($logfile)) {
		if($fd = popen("tail -n 30 $logfile", 'r')) {
			while (!feof($fd)) {
				$log .= fgets($fd, 4096);
				$n++;
				if($n > 1000) break;
			}
		fclose($fd);
		}
	} else {
		$log = 'Unable to read '.$logfile;
	}
}

$log = nl2br($log);

$app->tpl->setVar("log",$log);
$app->tpl->setVar("logfile",$logfile);
$app->tpl->setVar("logfile_id",$logfile_id);


$app->tpl_defaults();
$app->tpl->pparse();
?>