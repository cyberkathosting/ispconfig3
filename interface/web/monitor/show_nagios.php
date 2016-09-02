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
$app->tpl->setInclude('content_tpl', 'templates/show_nagios.htm');

$monTransSrv = $app->lng("monitor_settings_server_txt");
$title = 'Nagios ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';

$app->tpl->setVar("list_head_txt", $title);

if($_SESSION["s"]["user"]["typ"] == 'admin'){

	$app->uses('getconf');
	$server_config = $app->getconf->get_server_config($_SESSION['monitor']['server_id'], 'server');

	$nagios_url = trim($server_config['nagios_url']);
	if($nagios_url != ''){
		$nagios_url = str_replace('[SERVERNAME]', $_SESSION['monitor']['server_name'], $nagios_url);
		$nagios_user = trim($server_config['nagios_user']);
		$nagios_password = trim($server_config['nagios_password']);
		$nagios_url_parts = parse_url($nagios_url);
		if (strpos($nagios_url, '/check_mk') !== false) {
			//** Check_MK
			if($nagios_user != ''){
				$nagios_url = $nagios_url_parts['scheme'].'://'.$auth_string.$nagios_url_parts['host'].(isset($nagios_url_parts['port']) ? ':' . $nagios_url_parts['port'] : '');
				$pathparts = explode('/check_mk', $nagios_url_parts['path'], 2);
				$nagios_url .= $pathparts[0].'/check_mk/login.py?_login=1&_password='.rawurlencode($nagios_password).'&_username='.rawurlencode($nagios_user);
				if (strlen(@$pathparts[1]) > 0) {
					if (substr($pathparts[1], 0, 1) == '/') $pathparts[1] = substr($pathparts[1], 1, strlen($pathparts[1])-1);
					$nagios_url .= '&_origtarget='.rawurlencode($pathparts[1]);
				}
				if (isset($nagios_url_parts['query'])) $nagios_url .= '?'.rawurlencode($nagios_url_parts['query']);
				
			} else {
				$nagios_url = $nagios_url_parts['scheme'].'://'.$auth_string.$nagios_url_parts['host'].(isset($nagios_url_parts['port']) ? ':' . $nagios_url_parts['port'] : '');
				$pathparts = explode('/check_mk', $nagios_url_parts['path'], 2);
				$nagios_url .= $pathparts[0].'/check_mk/login.py';
				if (strlen(@$pathparts[1]) > 0) {
					if (substr($pathparts[1], 0, 1) == '/') $pathparts[1] = substr($pathparts[1], 1, strlen($pathparts[1])-1);
					$nagios_url .= '?_origtarget='.rawurlencode($pathparts[1]);
				}
				if (isset($nagios_url_parts['query'])) $nagios_url .= '?'.rawurlencode($nagios_url_parts['query']);
			}

		} else {
			//** Nagios
			$auth_string = '';
			if($nagios_user != ''){
				$auth_string = rawurlencode($nagios_user);
			}
			if($nagios_user != '' && $nagios_password != ''){
				$auth_string .= ':'.rawurlencode($nagios_password);
			}
			if($auth_string != '') $auth_string .= '@';
			$nagios_url = $nagios_url_parts['scheme'].'://'.$auth_string.$nagios_url_parts['host'].(isset($nagios_url_parts['port']) ? ':' . $nagios_url_parts['port'] : '').(isset($nagios_url_parts['path']) ? $nagios_url_parts['path'] : '').(isset($nagios_url_parts['query']) ? '?' . $nagios_url_parts['query'] : '').(isset($nagios_url_parts['fragment']) ? '#' . $nagios_url_parts['fragment'] : '');
		}

		$app->tpl->setVar("nagios_url", $nagios_url);
	} else {
		$app->tpl->setVar("no_nagios_url_defined_txt", $app->lng("no_nagios_url_defined_txt"));
	}
} else {
	$app->tpl->setVar("no_permissions_to_view_nagios_txt", $app->lng("no_permissions_to_view_nagios_txt"));
}

$app->tpl_defaults();
$app->tpl->pparse();
?>