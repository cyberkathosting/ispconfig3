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

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('monitor');

$app->uses('tools_monitor');

// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/show_monit.htm');

$monTransSrv = $app->lng("monitor_settings_server_txt");
$title = 'Monit ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';

$app->tpl->setVar("list_head_txt", $title);

if($_SESSION["s"]["user"]["typ"] == 'admin'){

	$app->uses('getconf');
	$server_config = $app->getconf->get_server_config($_SESSION['monitor']['server_id'], 'server');

	$monit_url = trim($server_config['monit_url']);
	if($monit_url != ''){
		$monit_url = str_replace('[SERVERNAME]', $_SESSION['monitor']['server_name'], $monit_url);
		$monit_user = trim($server_config['monit_user']);
		$monit_password = trim($server_config['monit_password']);
		$auth_string = '';
		if($monit_user != ''){
			$auth_string = rawurlencode($monit_user);
		}
		if($monit_user != '' && $monit_password != ''){
			$auth_string .= ':'.rawurlencode($monit_password);
		}
		if($auth_string != '') $auth_string .= '@';

		$monit_url_parts = parse_url($monit_url);

		$monit_url = $monit_url_parts['scheme'].'://'.$auth_string.$monit_url_parts['host'].(isset($monit_url_parts['port']) ? ':' . $monit_url_parts['port'] : '').(isset($monit_url_parts['path']) ? $monit_url_parts['path'] : '').(isset($monit_url_parts['query']) ? '?' . $monit_url_parts['query'] : '').(isset($monit_url_parts['fragment']) ? '#' . $monit_url_parts['fragment'] : '');

		$app->tpl->setVar("monit_url", $monit_url);
	} else {
		$app->tpl->setVar("no_monit_url_defined_txt", $app->lng("no_monit_url_defined_txt"));
	}
} else {
	$app->tpl->setVar("no_permissions_to_view_monit_txt", $app->lng("no_permissions_to_view_monit_txt"));
}

$app->tpl_defaults();
$app->tpl->pparse();
?>
