<?php
/*
Copyright (c) 2007-2010, Till Brehm, projektfarm Gmbh and Oliver Vogel www.muv.com
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

/* Check permissions for module */
$app->auth->check_module_permissions('monitor');

/* Change the Server if needed */
if (isset($_GET['server'])) {
	$server = explode('|', $_GET['server'], 2);
	$_SESSION['monitor']['server_id'] = $server[0];
	$_SESSION['monitor']['server_name'] = $server[1];
}

/*
 *  Loading the template
*/
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl', 'templates/show_sys_state.htm');

/* Get some translations */
$monTransRefreshsq = $app->lng("monitor_settings_refreshsq_txt");

/*
 * setting the content
*/
if ($_GET['state'] == 'server') {
	$res = _getServerState($_SESSION['monitor']['server_id'], $_SESSION['monitor']['server_name'], true);
	$output = $res['html_verbose'];
	$title = $app->lng("monitor_general_serverstate_txt");
	$stateType = 'server';
}
else {
	$output = _getSysState();
	$title = $app->lng("monitor_general_systemstate_txt");
	$stateType = 'system';
}

$app->tpl->setVar("state_data", $output);
$app->tpl->setVar("state_type", $stateType);
$app->tpl->setVar("list_head_txt", $title);
$app->tpl->setVar("list_desc_txt", (isset($description) ? $description : ''));
$app->tpl->setVar("monTransRefreshsq", $monTransRefreshsq);

/*
 Creating the array with the refresh intervals
 Attention: the core-module ist triggered every 5 minutes,
            so reload every 2 minutes is impossible!
*/
$refresh = (isset($_GET["refresh"]))?$app->functions->intval($_GET["refresh"]):0;

$refresh_values = array('0' => '- '.$app->lng("No Refresh").' -', '5' => '5 '.$app->lng("minutes"), '10' => '10 '.$app->lng("minutes"), '15' => '15 '.$app->lng("minutes"), '30' => '30 '.$app->lng("minutes"), '60' => '60 '.$app->lng("minutes"));
$tmp = '';
foreach($refresh_values as $key => $val) {
	if($key == $refresh) {
		$tmp .= "<option value='$key' SELECTED>$val</option>";
	} else {
		$tmp .= "<option value='$key'>$val</option>";
	}
}
$app->tpl->setVar("refresh", $tmp);

/*
 * doing the output
*/
$app->tpl_defaults();
$app->tpl->pparse();


/*
 * Creates HTML representing the state of the system (of all servers)
*/
function _getSysState() {
	global $app;

	/** The data of all Servers as (sorted by name) array */


	$serverData = array();

	/*
     * Get all servers and calculate the state of them
	*/
	$servers = $app->db->queryAllRecords("SELECT server_id, server_name FROM server order by server_name");
	foreach ($servers as $server) {
		$serverData[] = _getServerState($server['server_id'], $server['server_name'], false);
	}

	/*
	 * Now we have a array with all servers. Some of them are normal servers, some of them
	 * are OpenVz-Hosts and some are OpenVz-VE's. Next we need to know which of them are
	 * OpenVz-VE's inside a OpenVz-Host (managed by the Monitor). If there is a OpenVZ-VE
	 * inside a OpenVz-Host which is NOT in the Server-Farm and so not handled by the monitor,
	 * we handle it like a "normal" server (in the output of the system-state)
	*/
	foreach ($serverData as $data) {
		/* get all VE's of this server */
		$veInfo = $data['ve_info'];

		/*
		 * if we found some, mark them all as VE's
		*/
		if (is_array($veInfo)) {
			foreach ($veInfo as $info) {
				for ($i = 0; $i < sizeof($serverData); $i++) {
					if ($serverData[$i]['server_name'] == $info['hostname']) {
						$serverData[$i]['is_ve'] = true;
					}
				}
			}
		}
	}

	/*
	 * Now we have to output all "normal" server or all OpenVZ-Hosts (or all OpenVZ-VE's without
	 * a OpenVZ-Host managed by ISPConfig). The OpenVz-VE's are then included in them...
	*/
	$html = '';

	foreach ($serverData as $data) {
		if (!isset($data['is_ve'])) {
			/*
			 * it is NOT a Ve, so do the output of this server and off all VE's included in them
			*/
			$html .= $data['html_server'];
			/* get all VE's of this server */
			$veInfo = $data['ve_info'];
			if(is_array($veInfo)) {
				foreach ($veInfo as $info) {
					for ($i = 0; $i < sizeof($serverData); $i++) {
						if ($serverData[$i]['server_name'] == $info['hostname']) {
							$html = str_replace('##VE_INFO##', $serverData[$i]['html_ve'] . '##VE_INFO##', $html);
						}
					}
				}
			}
			$html = str_replace('##VE_INFO##', '', $html);
		}
	}
	return $html;
}


/**
 * returns the state and html of ONE Server
 * @param integer $serverId the id of the server
 * @param string $serverName the hostname (like server1.yourdomain.com)
 * @return array the state and representing html of the server
 */
function _getServerState($serverId, $serverName) {
	global $app;

	/*  The State of the server */
	$serverState = 'ok';

	/** The messages */
	$messages = array();

	/** The Result of the function */
	$res = array();

	/*
     * Get all monitoring-data from the server and process then
	*/
	$records = $app->db->queryAllRecords("SELECT DISTINCT type, data FROM monitor_data WHERE server_id = ?", $serverId);
	$osData = null;
	$veInfo = null;
	$ispcData = null;
	foreach($records as $record) {
		/* get the state from the db-data */
		$tmp = _processDbState($record['type'], $serverId, $serverState, $messages);
		$serverState = $tmp['serverState'];
		$messages = $tmp['messages'];

		/* if we have the os-info, get it */
		if ($record['type'] == 'os_info') {
			$osData = unserialize($record['data']);
		}
		/* if we have the ISPConfig-info, get it */
		if ($record['type'] == 'ispc_info') {
			$ispcData = unserialize($record['data']);
		}
		/* if we have the ve-info, get it */
		if ($record['type'] == 'openvz_veinfo') {
			$veInfo = unserialize($record['data']);
		}
	}

	/*
	 * We now have the state of the server. Lets now create the HTML representing this state.
	 * If we actually don't know, which type of verbose we need, let's create all
	*/

	/*
	 * Info of a VE inside a OpenVz-Host
	*/
	
	$alert_class = 'alert-info';
	if($serverState == 'ok') $alert_class = 'alert-success';
	if($serverState == 'warning') $alert_class = 'alert-warning';
	if($serverState == 'critical' || $serverState == 'error') $alert_class = 'alert-danger';
	
	//$html_ve  = '<div class="systemmonitor-ve state-' . $serverState . '-ve os-' . $osData['name'] . '">';
	//$html_ve  = '<div class="systemmonitor state-' . $serverState . ' os-' . $osData['name'] . '">';
	$html_server = '<div class="alert '.$alert_class.'" role="alert">';
	if ($osData != null) {
		$html_ve .= '<div class="icoDevice"><p class="status"></p></div>';
	}
	else {
		$html_ve .= '<div class="icoDevice"><p class="status"></p></div>';
	}
	$html_ve .= '<div class="statusDevice"><h3>' . $serverName;
	if ($osData != null) {
		$html_ve .= ' (' . $osData['name'] . ' ' . $osData['version'] . ') ';
	}
	if ($ispcData != null) {
		$html_ve .= $ispcData['name'] . ' ' . $ispcData['version'] . '</h3>';
	}
	else {
		$html_ve .= '</h3>';
	}
	$html_ve .= '<p>' . $app->lng("monitor_serverstate_state_txt") . ': ' . $serverState . '</p>';

	/*
	 * Info of a "normal" Server or a OpenVz-Host
	*/
	//$html_server = '<div class="systemmonitor-server state-' . $serverState . ' os-' . $osData['name'] . '">';
	
	/*
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
	*/
	$alert_class = 'alert-info';
	if($serverState == 'ok') $alert_class = 'alert-success';
	if($serverState == 'warning') $alert_class = 'alert-warning';
	if($serverState == 'critical' || $serverState == 'error') $alert_class = 'alert-danger';
	
	$html_server = '<div class="alert '.$alert_class.'" role="alert">';
	if ($osData != null) {
		//$html_server .= '<div class="icoDevice"><p class="status"></p></div>';
	}
	else {
		$html_server .= '<div class="icoDevice"><p class="status"></p></div>';
	}
	$html_server .= '<div class="statusDevice"><h3>' . $app->lng("monitor_serverstate_server_txt") . ': ' . $serverName;
	if ($osData != null) {
		$html_server .= ' (' . $osData['name'] . ' ' . $osData['version'] . ') ';
	}
	if ($ispcData != null) {
		$html_server .= $ispcData['name'] . ' ' . $ispcData['version'] . '</h3>';
	}
	else {
		$html_server .= '</h3>';
	}

	$html_server .= '<p>' . $app->lng("monitor_serverstate_state_txt") . ': ' . $serverState . ' (';
	$html_server .= sizeof((isset($messages[$app->lng("monitor_serverstate_listunknown_txt")]) ? $messages[$app->lng("monitor_serverstate_listunknown_txt")] : array())) . ' ' . $app->lng("monitor_serverstate_unknown_txt") . ', ';
	$html_server .= sizeof((isset($messages[$app->lng("monitor_serverstate_listinfo_txt")]) ? $messages[$app->lng("monitor_serverstate_listinfo_txt")] : array())) . ' ' . $app->lng("monitor_serverstate_info_txt") . ', ';
	$html_server .= sizeof((isset($messages[$app->lng("monitor_serverstate_listwarning_txt")]) ? $messages[$app->lng("monitor_serverstate_listwarning_txt")] : array())) . ' ' . $app->lng("monitor_serverstate_warning_txt") . ', ';
	$html_server .= sizeof((isset($messages[$app->lng("monitor_serverstate_listcritical_txt")]) ? $messages[$app->lng("monitor_serverstate_listcritical_txt")] : array())) . ' ' . $app->lng("monitor_serverstate_critical_txt") . ', ';
	$html_server .= sizeof((isset($messages[$app->lng("monitor_serverstate_listerror_txt")]) ? $messages[$app->lng("monitor_serverstate_listerror_txt")] : array())) . ' ' . $app->lng("monitor_serverstate_error_txt") . '';
	$html_server .= ')</p>';

	/*
	 * Verbose - Info
	*/
	$html_verbose = $html_server;
	foreach($messages as $key => $state) {
		$html_verbose .= '<strong>'.ucfirst($key) . '</strong><br />';
		foreach ($state as $msg) {
			$html_verbose .= $msg . '<br />';
		}
		$html_verbose .= '<br />';
	}

	/*
	 * The normal info also needs a link to the verbose info
	*/
	$html_ve .= "<a href='#' data-load-content='monitor/show_sys_state.php?state=server&server=" . $serverId . '|' . $serverName . "'>" . $app->lng("monitor_serverstate_moreinfo_txt") . "</a>";
	$html_server .= "<a href='#' data-load-content='monitor/show_sys_state.php?state=server&server=" . $serverId . '|' . $serverName . "'>" . $app->lng("monitor_serverstate_moreinfo_txt") . "</a>";

	/*
	 * Finish all html's
	*/
	$html_ve      .= '</div></div>';
	$html_server  .= '<div>##VE_INFO##</div></div></div>';
	$html_verbose .= '</div></div>';

	/*
	 * create and return the result
	*/
	$res['state'] = $serverState;
	$res['server_name'] = $serverName;
	$res['html_server'] = $html_server;
	$res['html_ve'] = $html_ve;
	$res['html_verbose'] = $html_verbose;
	$res['ve_info'] = $veInfo;
	return $res;
}

/*
* gets the state from the db and process it
*/
function _processDbState($type, $serverId, $serverState, $messages) {
	global $app;

	/*
    * Always the NEWEST record of each monitoring is responsible for the
    * state
	*/
	// get the State from the DB
	$record = $app->db->queryOneRecord("SELECT state FROM monitor_data WHERE type = ? and server_id = ? order by created desc", $type, $serverId);

	// change the new state to the highest state
	/*
	* Monitoring the user_beancounter of a VE is not as easy as i thought, so for now ignore
	* this state (if we have a better solution)
	*/
	if ($type != 'openvz_beancounter') {
		$serverState = _setState($serverState, $record['state']);
	}

	/*
     * The message depands on the type and the state
	*/
	if ($type == 'cpu_info') {
		/* this type has no state */
	}
	if ($type == 'disk_usage') {
		switch ($record['state']) {
		case 'ok':
			$messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_hdok_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=disk_usage'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'info':
			$messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_hdgoingfull_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=disk_usage'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'warning':
			$messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_hdnearlyfull_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=disk_usage'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'critical':
			$messages[$app->lng("monitor_serverstate_listcritical_txt")][] = $app->lng("monitor_serverstate_hdveryfull_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=disk_usage'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'error':
			$messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_hdfull_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=disk_usage'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;

		default:
			$messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_hdunknown_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=disk_usage'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		}
	}
	if ($type == 'mem_usage') {
		/* this type has no state */
	}
	if ($type == 'server_load') {
		switch ($record['state']) {
		case 'ok':
			$messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_loadok_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=server_load'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'info':
			$messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_loadheavy_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=server_load'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'warning':
			$messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_loadhigh_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=server_load'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'critical':
			$messages[$app->lng("monitor_serverstate_listcritical_txt")][] = $app->lng("monitor_serverstate_loadhigher_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=server_load'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'error':
			$messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_loadhighest_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=server_load'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		default:
			$messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_loadunknown_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=server_load'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		}
	}
	if ($type == 'services') {
		switch ($record['state']) {
		case 'ok':
			$messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_servicesonline_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=services'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";

			break;
		case 'error':
			$messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_servicesoffline_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=services'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		default:
			$messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_servicesunknown_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=services'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		}
	}
	if ($type == 'system_update') {
		switch ($record['state']) {
		case 'ok':
			$messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_updatesok_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=system_update'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";

			break;
		case 'info':
			$messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_updatesneeded_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=system_update'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'no_state':
			/*
                 *  not debian and not Ubuntu, so the state could not be monitored...
			*/
			break;
		default:
			$messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_updatesunknown_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=system_update'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		}
	}

	if ($type == 'raid_state') {
		switch ($record['state']) {
		case 'ok':
			$messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_raidok_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=raid_state'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'info':
			$messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_raidresync_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=raid_state'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'critical':
			$messages[$app->lng("monitor_serverstate_listcritical_txt")][] = $app->lng("monitor_serverstate_raidfault_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=raid_state'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'error':
			$messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_raiderror_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=raid_state'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'no_state':
			/*
                 *  mdadm is not installed or the RAID is not supported...
			*/
			break;
		default:
			$messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_raidunknown_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=raid_state'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		}
	}

	/*
	 * ignore, until we find a better solution
	 */
	// if ($type == 'openvz_beancounter') {
	//  switch ($record['state']) {
	//   case 'ok':
	//    $messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_beancounterok_txt") . ' ' .
	//      "<a href='#' data-load-content='monitor/show_data.php?type=openvz_beancounter'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
	//    break;
	//   case 'info':
	//    $messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_beancounterinfo_txt") . ' ' .
	//      "<a href='#' data-load-content='monitor/show_data.php?type=openvz_beancounter'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
	//    break;
	//   case 'warning':
	//    $messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_beancounterwarning_txt") . ' ' .
	//      "<a href='#' data-load-content='monitor/show_data.php?type=openvz_beancounter'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
	//    break;
	//   case 'critical':
	//    $messages[$app->lng("monitor_serverstate_listcritical_txt")][] = $app->lng("monitor_serverstate_beancountercritical_txt") . ' ' .
	//      "<a href='#' data-load-content='monitor/show_data.php?type=openvz_beancounter'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
	//    break;
	//   case 'error':
	//    $messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_beancountererror_txt") . ' ' .
	//      "<a href='#' data-load-content='monitor/show_data.php?type=openvz_beancounter'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
	//    break;
	//   default:
	//    break;
	//  }
	// }


	if ($type == 'mailq') {
		switch ($record['state']) {
		case 'ok':
			$messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_mailqok_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=mailq'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'info':
			$messages[$app->lng("monitor_serverstate_listinfo_txt")][] = $app->lng("monitor_serverstate_mailqheavy_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=mailq'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'warning':
			$messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_mailqhigh_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=mailq'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'critical':
			$messages[$app->lng("monitor_serverstate_listcritical_txt")][] = $app->lng("monitor_serverstate_mailqhigher_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=mailq'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'error':
			$messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_mailqhighest_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=mailq'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		default:
			$messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_mailqunknown_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_data.php?type=mailq'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		}
	}

	if ($type == 'sys_log') {
		switch ($record['state']) {
		case 'ok':
			$messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_syslogok_txt") . ' ' .
				"<a href='#' data-load-content='monitor/log_list.php'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'warning':
			$messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_syslogwarning_txt") . ' ' .
				"<a href='#' data-load-content='monitor/log_list.php'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'error':
			$messages[$app->lng("monitor_serverstate_listerror_txt")][] = $app->lng("monitor_serverstate_syslogerror_txt") . ' ' .
				"<a href='#' data-load-content='monitor/log_list.php'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		default:
			$messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_syslogunknown_txt") . ' ' .
				"<a href='#' data-load-content='monitor/log_list.php'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		}
	}

	if ($type == 'log_clamav') {
		/* this type has no state */
	}

	if ($type == 'log_freshclam') {
		switch ($record['state']) {
		case 'ok':
			$messages[$app->lng("monitor_serverstate_listok_txt")][] = $app->lng("monitor_serverstate_fclamok_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_log.php?log=log_freshclam'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		case 'info':
			$messages[$app->lng("monitor_serverstate_listwarning_txt")][] = $app->lng("monitor_serverstate_fclamoutdated_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_log.php?log=log_freshclam'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		default:
			$messages[$app->lng("monitor_serverstate_listunknown_txt")][] = $app->lng("monitor_serverstate_fclamunknown_txt") . ' ' .
				"<a href='#' data-load-content='monitor/show_log.php?log=log_freshclam'>[" . $app->lng("monitor_serverstate_more_txt") . "]</a>";
			break;
		}
	}

	if ($type == 'log_ispconfig') {
		/* this type has no state */
	}
	if ($type == 'log_mail') {
		/* this type has no state */
	}
	if ($type == 'log_mail_err') {
		/* this type has no state */
	}
	if ($type == 'log_mail_warn') {
		/* this type has no state */
	}
	if ($type == 'log_messages') {
		/* this type has no state */
	}
	if ($type == 'rkhunter') {
		/* this type has no state */
	}

	return array('serverState' => $serverState, 'messages' => $messages);
}

/*
  * Set the state to the given level (or higher, but not lesser).
  * * If the actual state is critical and you call the method with ok,
  *   then the state is critical.
  *
  * * If the actual state is critical and you call the method with error,
  *   then the state is error.
*/
function _setState($oldState, $newState) {
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
	if ($newInt > $oldInt) {
		return $newState;
	}
	else {
		return $oldState;
	}
}

?>
