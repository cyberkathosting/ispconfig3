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

/* Check permissions for module */
$app->auth->check_module_permissions('monitor');

/* Change the Server if needed */
if (isset($_GET['server'])){
    $server = explode('|', $_GET['server'], 2);
    $_SESSION['monitor']['server_id'] = $server[0];
    $_SESSION['monitor']['server_name'] = $server[1];
}

/*
 *  Loading the template
 */
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/show_sys_state.htm');

/* Get some translations */
$monTransRefreshsq = $app->lng("monitor_settings_refreshsq_txt");

/*
 * setting the content
 */
if ($_GET['state'] == 'server')
{
    $output = _getServerState($_SESSION['monitor']['server_id'], $_SESSION['monitor']['server_name'], true);
    $title = $app->lng("monitor_general_serverstate_txt");
    $stateType = 'server';
}
else
{
    $output = _getSysState();
    $title = $app->lng("monitor_general_systemstate_txt");
    $stateType = 'system';
}

$app->tpl->setVar("state_data",$output);
$app->tpl->setVar("state_type",$stateType);
$app->tpl->setVar("title",$title);
$app->tpl->setVar("description",$description);
$app->tpl->setVar("monTransRefreshsq", $monTransRefreshsq);

/*
 Creating the array with the refresh intervals
 Attention: the core-module ist triggered every 5 minutes,
            so reload every 2 minutes is impossible!
*/
$refresh = (isset($_GET["refresh"]))?intval($_GET["refresh"]):0;

$refresh_values = array('0' => '- '.$app->lng("No Refresh").' -','5' => '5 '.$app->lng("minutes"),'10' => '10 '.$app->lng("minutes"),'15' => '15 '.$app->lng("minutes"),'30' => '30 '.$app->lng("minutes"),'60' => '60 '.$app->lng("minutes"));
$tmp = '';
foreach($refresh_values as $key => $val) {
	if($key == $refresh) {
		$tmp .= "<option value='$key' SELECTED>$val</option>";
	} else {
		$tmp .= "<option value='$key'>$val</option>";
	}
}
$app->tpl->setVar("refresh",$tmp);

/*
 * doing the output
 */
$app->tpl_defaults();
$app->tpl->pparse();


function _getSysState(){
    global $app;

    /*
     * Get all Servers and calculate the state of them
     */
    $html = '';

    $servers = $app->db->queryAllRecords("SELECT server_id, server_name FROM server order by server_name");
    foreach ($servers as $server)
    {
        $html .= _getServerState($server['server_id'], $server['server_name'], false);
    }

    return $html;
}

/*
 * Calculates the State of ONE Server
 */
function _getServerState($serverId, $serverName, $showAll)
{
    global $app;

    /*  The State of the server */
    $serverState = 'ok';

    /** The Number of several infos, warnings, errors, ... */
    $count = array('unknown' => 0, 'info' => 0, 'warning' => 0, 'critical' => 0, 'error' => 0);

    /** The messages */
    $messages = array();

    /** The Result of the function */
    $res = '';

    /*
     * get all monitoring-data from the server als process then
     * (count them and set the server-state)
     */
    $records = $app->db->queryAllRecords("SELECT DISTINCT type FROM monitor_data WHERE server_id = " . $serverId);
    foreach($records as $record){
        _processDbState($record['type'], $serverId, &$serverState, &$messages);
    }

    $res .= '<div class="systemmonitor-state state-'.$serverState.'">';
    $res .= '<div class="systemmonitor-device device-server">';
    $res .= '<div class="systemmonitor-content icons32 ico-'.$serverState.'">';
    $res .= $app->lng("monitor_serverstate_server_txt") . ': ' . $serverName . '<br />';
    $res .= $app->lng("monitor_serverstate_state_txt") . ': ' . $serverState . '<br />';
    //        $res .= sizeof($messages['ok']) . ' ok | ';
    $res .= sizeof($messages['unknown']) . ' ' . $app->lng("monitor_serverstate_unknown_txt") . ' | ';
    $res .= sizeof($messages['info']) . ' ' . $app->lng("monitor_serverstate_info_txt") . ' | ';
    $res .= sizeof($messages['warning']) . ' ' . $app->lng("monitor_serverstate_warning_txt") . ' | ';
    $res .= sizeof($messages['critical']) . ' ' . $app->lng("monitor_serverstate_critical_txt") . ' | ';
    $res .= sizeof($messages['error']) . ' ' . $app->lng("monitor_serverstate_error_txt") . '<br />';
    $res .= '<br />';

    if ($showAll){
        /*
         * if we have to show all, then we do it...
         */

        /*
        * Show all messages
        */
        foreach($messages as $key => $state){
            /*
             * There is no need, to show the "ok" - messages
             */
//            if ($key != 'ok')
            {
                $res .= $key . ':<br />';
                foreach ($state as $msg)
                {
                    $res .= $msg . '<br />';
                }
                $res .= '<br />';
            }
        }
    }
    else
    {
        /*
         * if not, we only show a link to the server...
         */
        $res .= "<a href='#' onclick='loadContent(\"monitor/show_sys_state.php?state=server&server=" . $serverId . '|' . $serverName . "\");'>" . $app->lng("monitor_serverstate_moreinfo_txt") . "</a>";
    }
    $res .= '</div>';
    $res .= '</div>';
    $res .= '</div>';

    if ($showAll){
        /*
         * Show some state-info
         */
        //$res .= showServerLoad();
        //$res .= '&nbsp;'. showDiskUsage();
        //$res .= '&nbsp;'.showServices();
    }


    return $res;
}

/*
 * gets the state from the db and process it
 */
function _processDbState($type, $serverId, &$serverState, &$messages)
{
    global $app;

   /*
    * Always the NEWEST record of each monitoring is responsible for the
    * state
    */
    // get the State from the DB
    $record = $app->db->queryOneRecord("SELECT state FROM monitor_data WHERE type = '" . $type . "' and server_id = " . $serverId . " order by created desc");
    // change the new state to the highest state
    $serverState = _setState($serverState, $record['state']);
    // count the states
    $count[$record['state']]+= 1;

    /*
     * The message depands on the type and the state
     */
    if ($type == 'cpu_info'){
        /* this type has no state */
    }
    if ($type == 'disk_usage'){
        switch ($record['state']) {
            case 'ok':
                $messages['ok'][] = 'The state of your Hard-Disk space is ok ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[more...]</a>";
                break;
            case 'info':
                $messages['info'][] = 'Your Hard-Disk space is going full ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[more...]</a>";
                break;
            case 'warning':
                $messages['warning'][] = 'Your Hard-Disk is nearly full ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[more...]</a>";
                break;
            case 'critical':
                $messages['critical'][] = 'Your Hard-Disk is very full '.
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[more...]</a>";
                break;
            case 'error':
                $messages['error'][] = 'Your Hard-Disk has no more space left ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[more...]</a>";
                break;

            default:
                $messages['unknown'][] = 'Hard-Disk: ??? ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[more...]</a>";
                break;
        }
    }
    if ($type == 'mem_usage'){
        /* this type has no state */
    }
    if ($type == 'server_load'){
        switch ($record['state']) {
            case 'ok':
                $messages['ok'][] = 'Your Server load is ok ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[more...]</a>";
                break;
            case 'info':
                $messages['info'][] = 'Your Server in under heavy load ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[more...]</a>";
                break;
            case 'warning':
                $messages['warning'][] = 'Your Server in under high load ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[more...]</a>";
                break;
            case 'critical':
                $messages['critical'][] = 'Your Server in under higher load ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[more...]</a>";
                break;
            case 'error':
                $messages['error'][] = 'Your Server in under highest load ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[more...]</a>";
                break;
            default:
                $messages['unknown'][] = 'Server Load: ??? ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[more...]</a>";
                break;
        }
    }
    if ($type == 'services'){
        switch ($record['state']) {
            case 'ok':
                $messages['ok'][] = 'All needed Services are online ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=services\");'>[more...]</a>";

                break;
            case 'error':
                $messages['error'][] = 'One or more needed Services are offline ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=services\");'>[more...]</a>";
                break;
            default:
                $messages['unknown'][] = 'services:??? ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=services\");'>[more...]</a>";
                break;
        }
    }
    if ($type == 'system_update'){
        switch ($record['state']) {
            case 'ok':
                $messages['ok'][] = 'Your System is up to date. ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=system_update\");'>[more...]</a>";

                break;
            case 'warning':
                $messages['warning'][] = 'One or more Components needs a update ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=system_update\");'>[more...]</a>";
                break;
            case 'no_state':
                /*
                 *  not debian and not Ubuntu, so the state could not be monitored...
                 */
                break;
            default:
                $messages['unknown'][] = 'System-Update:??? ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=system_update\");'>[more...]</a>";
                break;
        }
    }

    if ($type == 'raid_state'){
        switch ($record['state']) {
            case 'ok':
                $messages['ok'][] = 'Your RAID is ok ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[more...]</a>";
                break;
            case 'info':
                $messages['info'][] = 'Your RAID is in RESYNC mode ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[more...]</a>";
                break;
            case 'critical':
                $messages['critical'][] = 'Your RAID has one FAULT disk. Replace as soon as possible! '.
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[more...]</a>";
                break;
            case 'error':
                $messages['error'][] = 'Your RAID is not working anymore ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[more...]</a>";
                break;
            case 'no_state':
                /*
                 *  mdadm is not installed or the RAID is not supported...
                 */
                break;
            default:
                $messages['unknown'][] = 'RAID state: ??? ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[more...]</a>";
                break;
        }
    }


    if ($type == 'mailq'){
        switch ($record['state']) {
            case 'ok':
                $messages['ok'][] = 'Your Mailq load is ok ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[more...]</a>";
                break;
            case 'info':
                $messages['info'][] = 'Your Mailq in under heavy load ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[more...]</a>";
                break;
            case 'warning':
                $messages['warning'][] = 'Your Mailq in under high load ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[more...]</a>";
                break;
            case 'critical':
                $messages['critical'][] = 'Your Mailq in under higher load ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[more...]</a>";
                break;
            case 'error':
                $messages['error'][] = 'Your Mailq in under highest load ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[more...]</a>";
                break;
            default:
                $messages['unknown'][] = 'Mailq: ??? ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[more...]</a>";
                break;
        }
    }

	if ($type == 'sys_log'){
		switch ($record['state']) {
			case 'ok':
                $messages['ok'][] = 'The System-Log is O.K. ' .
                                    "<a href='#' onclick='loadContent(\"monitor/log_list.php\");'>[more...]</a>";
                break;
            case 'warning':
                $messages['warning'][] = 'There are some warnings in your System-Log ' .
                                    "<a href='#' onclick='loadContent(\"monitor/log_list.php\");'>[more...]</a>";
                break;
            case 'error':
                $messages['error'][] = 'There are errors in your System-Log ' .
                                    "<a href='#' onclick='loadContent(\"monitor/log_list.php\");'>[more...]</a>";
                break;
            default:
                $messages['unknown'][] = 'sys-log:??? ' .
                                    "<a href='#' onclick='loadContent(\"monitor/log_list.php\");'>[more...]</a>";
                break;
        }
    }

    if ($type == 'log_clamav'){
        /* this type has no state */
    }

	if ($type == 'log_freshclam'){
        switch ($record['state']) {
            case 'ok':
                $messages['ok'][] = 'Your Virus-protection is ok ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_log.php?log=log_freshclam\");'>[more...]</a>";
                break;
            case 'warning':
                $messages['warning'][] = 'Your Virus-protection is OUTDATED! ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_log.php?log=log_freshclam\");'>[more...]</a>";
                break;
            default:
                $messages['unknown'][] = 'Freshclam: ???! ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_log.php?log=log_freshclam\");'>[more...]</a>";
                break;
        }
    }

    if ($type == 'log_ispconfig'){
        /* this type has no state */
    }
    if ($type == 'log_mail'){
        /* this type has no state */
    }
    if ($type == 'log_mail_err'){
        /* this type has no state */
    }
    if ($type == 'log_mail_warn'){
        /* this type has no state */
    }
    if ($type == 'log_messages'){
        /* this type has no state */
    }
    if ($type == 'rkhunter'){
        /* this type has no state */
    }
}

 /*
  * Set the state to the given level (or higher, but not lesser).
  * * If the actual state is critical and you call the method with ok,
  *   then the state is critical.
  *
  * * If the actual state is critical and you call the method with error,
  *   then the state is error.
  */
function _setState($oldState, $newState)
{
   /*
    * Calculate the weight of the old state
    */
    switch ($oldState) {
        case 'no_state': $oldInt = 0;
            break;
        case 'ok': $oldInt = 1;
            break;
        case 'unknown': $oldInt = 2;
            break;
        case 'info': $oldInt = 3;
            break;
        case 'warning': $oldInt = 4;
            break;
        case 'critical': $oldInt = 5;
            break;
        case 'error': $oldInt = 6;
            break;
    }
        /*
         * Calculate the weight of the new state
         */
    switch ($newState) {
        case 'no_state': $newInt = 0 ;
            break;
        case 'ok': $newInt = 1 ;
            break;
        case 'unknown': $newInt = 2 ;
            break;
        case 'info': $newInt = 3 ;
            break;
        case 'warning': $newInt = 4 ;
            break;
        case 'critical': $newInt = 5 ;
            break;
        case 'error': $newInt = 6 ;
            break;
    }

   /*
    * Set to the higher level
    */
    if ($newInt > $oldInt){
        return $newState;
    }
    else
    {
        return $oldState;
    }
}

?>
