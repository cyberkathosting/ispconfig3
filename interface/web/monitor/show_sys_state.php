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
$app->tpl->setVar("list_head_txt",$title);
$app->tpl->setVar("list_desc_txt",$description);
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
    //        $res .= sizeof($messages[$app->lng("monitor_serverstate_listok_txt")]) . ' ok | ';
    $res .= sizeof($messages[$app->lng("monitor_serverstate_listunknown_txt")]) . ' ' . $app->lng("monitor_serverstate_unknown_txt") . ' | ';
    $res .= sizeof($messages[$app->lng("monitor_serverstate_listinfo_txt")]) . ' ' . $app->lng("monitor_serverstate_info_txt") . ' | ';
    $res .= sizeof($messages[$app->lng("monitor_serverstate_listwarning_txt")]) . ' ' . $app->lng("monitor_serverstate_warning_txt") . ' | ';
    $res .= sizeof($messages[$app->lng("monitor_serverstate_listcritical_txt")]) . ' ' . $app->lng("monitor_serverstate_critical_txt") . ' | ';
    $res .= sizeof($messages[$app->lng("monitor_serverstate_listerror_txt")]) . ' ' . $app->lng("monitor_serverstate_error_txt") . '<br />';
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

    /*
     * The message depands on the type and the state
     */
    if ($type == 'cpu_info'){
        /* this type has no state */
    }
    if ($type == 'disk_usage'){
        switch ($record['state']) {
            case 'ok':
                $messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_hdok_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'info':
                $messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_hdgoingfull_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'warning':
                $messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_hdnearlyfull_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'critical':
                $messages[$app->lng("monitor_serverstate_listcritical_txt")][] = $app->lng("monitor_serverstate_hdveryfull_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'error':
                $messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_hdfull_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;

            default:
                $messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_hdunknown_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=disk_usage\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
        }
    }
    if ($type == 'mem_usage'){
        /* this type has no state */
    }
    if ($type == 'server_load'){
        switch ($record['state']) {
            case 'ok':
                $messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_loadok_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'info':
                $messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_loadheavy_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'warning':
                $messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_loadhigh_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'critical':
                $messages[$app->lng("monitor_serverstate_listcritical_txt")][] = $app->lng("monitor_serverstate_loadhigher_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'error':
                $messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_loadhighest_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            default:
                $messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_loadunknown_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=server_load\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
        }
    }
    if ($type == 'services'){
        switch ($record['state']) {
            case 'ok':
                $messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_servicesonline_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=services\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";

                break;
            case 'error':
                $messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_servicesoffline_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=services\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            default:
                $messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_servicesunknown_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=services\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
        }
    }
    if ($type == 'system_update'){
        switch ($record['state']) {
            case 'ok':
                $messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_updatesok_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=system_update\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";

                break;
            case 'warning':
                $messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_updatesneeded_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=system_update\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'no_state':
                /*
                 *  not debian and not Ubuntu, so the state could not be monitored...
                 */
                break;
            default:
                $messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_updatesunknown_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=system_update\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
        }
    }

    if ($type == 'raid_state'){
        switch ($record['state']) {
            case 'ok':
                $messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_raidok_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'info':
                $messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_raidresync_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'critical':
                $messages[$app->lng("monitor_serverstate_listcritical_txt")][] = $app->lng("monitor_serverstate_raidfault_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'error':
                $messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_raiderror_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'no_state':
                /*
                 *  mdadm is not installed or the RAID is not supported...
                 */
                break;
            default:
                $messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_raidunknown_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=raid_state\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
        }
    }


    if ($type == 'mailq'){
        switch ($record['state']) {
            case 'ok':
                $messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_mailqok_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'info':
                $messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_mailqheavy_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'warning':
                $messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_mailqhigh_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'critical':
                $messages[$app->lng("monitor_serverstate_listcritical_txt")][] = $app->lng("monitor_serverstate_mailqhigher_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'error':
                $messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_mailqhighest_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            default:
                $messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_mailqunknown_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_data.php?type=mailq\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
        }
    }

    if ($type == 'sys_log'){
        switch ($record['state']) {
            case 'ok':
                $messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_syslogok_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/log_list.php\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'warning':
                $messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_syslogwarning_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/log_list.php\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'error':
                $messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_syslogerror_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/log_list.php\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            default:
                $messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_syslogunknown_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/log_list.php\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
        }
    }

    if ($type == 'log_clamav'){
        /* this type has no state */
    }

	if ($type == 'log_freshclam'){
        switch ($record['state']) {
            case 'ok':
                $messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_fclamok_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_log.php?log=log_freshclam\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            case 'warning':
                $messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_fclamoutdated_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_log.php?log=log_freshclam\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
                break;
            default:
                $messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_fclamunknown_txt") . ' ' .
                                    "<a href='#' onclick='loadContent(\"monitor/show_log.php?log=log_freshclam\");'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
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
