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

/* Get the dataType to show */
$dataType = $_GET["type"];

/* Get some translations */
$monTransDate = $app->lng("monitor_settings_datafromdate_txt");
$monTransSrv = $app->lng("monitor_settings_server_txt");


$output = '';

switch($dataType) {
case 'server_load':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showServerLoad();
	$time = $app->tools_monitor->getDataTime('server_load');
	$title = $app->lng("Server Load").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	$add_padding = true;
	break;
case 'disk_usage':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showDiskUsage();
	$time = $app->tools_monitor->getDataTime('disk_usage');
	$title = $app->lng("Disk usage").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	break;
case 'database_size':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showDatabaseSize();
	$time = $app->tools_monitor->getDataTime('database_size');
	$title = $app->lng("Database size").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	break;
case 'mem_usage':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showMemUsage();
	$time = $app->tools_monitor->getDataTime('mem_usage');
	$title = $app->lng("Memory usage").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	break;
case 'cpu_info':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showCpuInfo();
	$time = $app->tools_monitor->getDataTime('cpu_info');
	$title = $app->lng("monitor_title_cpuinfo_txt").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	break;
case 'services':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showServices();
	$time = $app->tools_monitor->getDataTime('services');
	$title = $app->lng("Status of services").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	break;
case 'openvz_beancounter':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showOpenVzBeanCounter();
	$time = $app->tools_monitor->getDataTime('openvz_beancounter');
	$title = $app->lng("monitor_title_beancounter_txt") . ' (' . $monTransSrv . ' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	$add_padding = true;
	break;
case 'system_update':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showSystemUpdate();
	$time = $app->tools_monitor->getDataTime('system_update');
	$title = $app->lng("monitor_title_updatestate_txt"). ' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	$add_padding = true;
	break;
case 'mailq':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showMailq();
	$time = $app->tools_monitor->getDataTime('mailq');
	$title = $app->lng("monitor_title_mailq_txt"). ' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	$add_padding = true;
	break;
case 'raid_state':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showRaidState();
	$time = $app->tools_monitor->getDataTime('raid_state');
	$title = $app->lng("monitor_title_raidstate_txt"). ' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	$add_padding = true;
	break;
case 'rkhunter':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showRKHunter();
	$time = $app->tools_monitor->getDataTime('rkhunter');
	$title = $app->lng("monitor_title_rkhunterlog_txt"). ' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	$add_padding = true;
	break;
case 'fail2ban':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showFail2ban();
	$time = $app->tools_monitor->getDataTime('log_fail2ban');
	$title = $app->lng("monitor_title_fail2ban_txt") . ' (' . $monTransSrv . ' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	$add_padding = true;
	break;
/*
case 'mongodb':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showMongoDB();
	$time = $app->tools_monitor->getDataTime('log_mongodb');
	$title = $app->lng("monitor_title_mongodb_txt") . ' (' . $monTransSrv . ' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	break;
*/
case 'iptables':
	$template = 'templates/show_data.htm';
	$output .= $app->tools_monitor->showIPTables();
	$time = $app->tools_monitor->getDataTime('iptables_rules');
	$title = $app->lng("monitor_title_iptables_txt") . ' (' . $monTransSrv . ' : ' . $_SESSION['monitor']['server_name'] . ')';
	$description = '';
	$add_padding = true;
	break;
default:
	$template = '';
	break;
}

if($add_padding == true) {
$output = '<div style="padding:20px;">'.$output.'</div>';
}


// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', $template);

$app->tpl->setVar("output", $output);
$app->tpl->setVar("list_head_txt", $title);
$app->tpl->setVar("list_desc_txt", $description);
$app->tpl->setVar("time", $time);
$app->tpl->setVar("monTransDate", $monTransDate);

$app->tpl_defaults();
$app->tpl->pparse();
?>
