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
require_once('tools.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('monitor');


/* Get the dataType to show */
$dataType = $_GET["type"];

/* Get some translations */
$monTransDate = $app->lng("monitor_settings_datafromdate_txt");
$monTransSrv = $app->lng("monitor_settings_server_txt");


$output = '';

switch($dataType) {
    case 'server_load':
        $template = 'templates/show_data.htm';
        $output .= showServerLoad();
        $time = getDataTime('server_load');
        $title = $app->lng("Server Load").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    case 'disk_usage':
        $template = 'templates/show_data.htm';
        $output .= showDiskUsage();
        $time = getDataTime('disk_usage');
        $title = $app->lng("Disk usage").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    case 'mem_usage':
        $template = 'templates/show_data.htm';
        $output .= showMemUsage();
        $time = getDataTime('mem_usage');
        $title = $app->lng("Memory usage").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    case 'cpu_info':
        $template = 'templates/show_data.htm';
        $output .= showCpuInfo();
        $time = getDataTime('cpu_info');
        $title = $app->lng("monitor_title_cpuinfo_txt").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    case 'services':
        $template = 'templates/show_data.htm';
        $output .= showServices();
        $time = getDataTime('services');
        $title = $app->lng("Status of services").' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    case 'system_update':
        $template = 'templates/show_data.htm';
        $output .= showSystemUpdate();
        $time = getDataTime('system_update');
        $title = $app->lng("monitor_title_updatestate_txt"). ' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    case 'mailq':
        $template = 'templates/show_data.htm';
        $output .= showMailq();
        $time = getDataTime('mailq');
        $title = $app->lng("monitor_title_mailq_txt"). ' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    case 'raid_state':
        $template = 'templates/show_data.htm';
        $output .= showRaidState();
        $time = getDataTime('raid_state');
        $title = $app->lng("monitor_title_raidstate_txt"). ' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    case 'rkhunter':
        $template = 'templates/show_data.htm';
        $output .= showRKHunter();
        $time = getDataTime('rkhunter');
        $title = $app->lng("monitor_title_rkhunterlog_txt"). ' ('. $monTransSrv .' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    case 'fail2ban':
        $template = 'templates/show_data.htm';
        $output .= showFail2ban();
        $time = getDataTime('fail2ban');
        $title = 'fail2ban - Log (' . $monTransSrv . ' : ' . $_SESSION['monitor']['server_name'] . ')';
        $description = '';
        break;
    default:
        $template = '';
        break;
}


// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', $template);

$app->tpl->setVar("output", $output);
$app->tpl->setVar("title", $title);
$app->tpl->setVar("description", $description);
$app->tpl->setVar("time", $time);
$app->tpl->setVar("monTransDate", $monTransDate);

$app->tpl_defaults();
$app->tpl->pparse();
?>
