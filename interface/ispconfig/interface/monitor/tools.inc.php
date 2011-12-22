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
function showServerLoad(){
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'server_load' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);

        /*
        Format the data
        */
		if (strlen($data['up_minutes']) == "1") $data['up_minutes'] = "0".$data['up_minutes'];
        $html =
           '<div class="systemmonitor-state state-'.$record['state'].'">
            <div class="systemmonitor-content icons32 ico-'.$record['state'].'">
            <table>
            <tr>
            <td>' . $app->lng("Server online since").':</td>
            <td>' . $data['up_days'] . ' days, ' . $data['up_hours'] . ':' . $data['up_minutes'] . ' hours</center></td>
            </tr>
            <tr>
            <td>' . $app->lng("Users online").':</td>
            <td>' . $data['user_online'] . '</td>
            </tr>' .
            '<tr>
            <td>' . $app->lng("System load 1 minute") . ':</td>
            <td>' . $data['load_1'] . '</td>
            </tr>
            <tr>
            <td>' . $app->lng("System load 5 minutes") . ':</td>
            <td>' . $data['load_5'] . '</td>
            </tr>
            <tr>
            <td>'.$app->lng("System load 15 minutes").':</td>
            <td>' . $data['load_15'] . '</td>
            </tr>
            </table>
            </div>
            </div>';
    } else {
        $html = '<p>'.$app->lng("no_data_serverload_txt").'</p>';
    }

    return $html;
}

function showDiskUsage () {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'disk_usage' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);

        /*
        Format the data
        */
        $html =
           '<div class="systemmonitor-state state-'.$record['state'].'">
            <div class="systemmonitor-content icons32 ico-'.$record['state'].'">
            <table>
            <tr>
            <td>'.$app->lng("monitor_diskusage_filesystem_txt").'</td>
	    <td>'.$app->lng("monitor_diskusage_type_txt").'</td>
            <td>'.$app->lng("monitor_diskusage_size_txt").'</td>
            <td>'.$app->lng("monitor_diskusage_used_txt").'</td>
            <td>'.$app->lng("monitor_diskusage_available_txt").'</td>
            <td>'.$app->lng("monitor_diskusage_usage_txt").'</td>
            <td>'.$app->lng("monitor_diskusage_mounted_txt").'</td>
            </tr>';
        foreach($data as $line) {
            $html .= '<tr>';
            foreach ($line as $item) {
                $html .= '<td>' . $item . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '</div></div>';
    } else {
        $html = '<p>'.$app->lng("no_data_diskusage_txt").'</p>';
    }


    return $html;
}

function showMemUsage () {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'mem_usage' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);

        /*
        Format the data
        */
        $html =
           '<div class="systemmonitor-state state-'.$record['state'].'">
            <div class="systemmonitor-content icons32 ico-'.$record['state'].'">
            <table>';

        foreach($data as $key => $value){
            if ($key != '') {
                $html .= '<tr>
                    <td>' . $key . ':</td>
                    <td>' . $value . '</td>
                    </tr>';
            }
        }
        $html .= '</table>';
        $html .= '</div></div>';

    } else {
        $html = '<p>'.$app->lng("no_data_memusage_txt").'</p>';
    }

    return $html;
}

function showCpuInfo () {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'cpu_info' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);

        /*
        Format the data
        */
        $html = 
           '<div class="systemmonitor-state state-'.$record['state'].'">
            <div class="systemmonitor-content icons32 ico-'.$record['state'].'">
            <table>';
        foreach($data as $key => $value){
            if ($key != '') {
                $html .= '<tr>
                    <td>' . $key . ':</td>
                    <td>' . $value . '</td>
                    </tr>';
            }
        }
        $html .= '</table>';
        $html .= '</div></div>';
    } else {
        $html = '<p>'.$app->lng("no_data_cpuinfo_txt").'</p>';
    }

    return $html;
}

function showServices () {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'services' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);

        /*
        Format the data
        */
        $html =
           '<div class="systemmonitor-state state-'.$record['state'].'">
            <div class="systemmonitor-content icons32 ico-'.$record['state'].'">
            <table>';

        if($data['webserver'] != -1) {
            if($data['webserver'] == 1) {
                $status = '<span class="online">'.$app->lng("monitor_services_online_txt").'</span>';
            } else {
                $status = '<span class="offline">'.$app->lng("monitor_services_offline_txt").'</span>';
            }
            $html .= '<tr>
            <td>'.$app->lng("monitor_services_web_txt").'</td>
            <td>'.$status.'</td>
            </tr>';
        }


        if($data['ftpserver'] != -1) {
            if($data['ftpserver'] == 1) {
                $status = '<span class="online">'.$app->lng("monitor_services_online_txt").'</span>';
            } else {
                $status = '<span class="offline">'.$app->lng("monitor_services_offline_txt").'</span>';
            }
            $html .= '<tr>
            <td>'.$app->lng("monitor_services_ftp_txt").'</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['smtpserver'] != -1) {
            if($data['smtpserver'] == 1) {
                $status = '<span class="online">'.$app->lng("monitor_services_online_txt").'</span>';
            } else {
                $status = '<span class="offline">'.$app->lng("monitor_services_offline_txt").'</span>';
            }
            $html .= '<tr>
            <td>'.$app->lng("monitor_services_smtp_txt").'</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['pop3server'] != -1) {
            if($data['pop3server'] == 1) {
                $status = '<span class="online">'.$app->lng("monitor_services_online_txt").'</span>';
            } else {
                $status = '<span class="offline">'.$app->lng("monitor_services_offline_txt").'</span>';
            }
            $html .= '<tr>
            <td>'.$app->lng("monitor_services_pop_txt").'</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['imapserver'] != -1) {
            if($data['imapserver'] == 1) {
                $status = '<span class="online">'.$app->lng("monitor_services_online_txt").'</span>';
            } else {
                $status = '<span class="offline">'.$app->lng("monitor_services_offline_txt").'</span>';
            }
            $html .= '<tr>
            <td>'.$app->lng("monitor_services_imap_txt").'</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['bindserver'] != -1) {
            if($data['bindserver'] == 1) {
                $status = '<span class="online">'.$app->lng("monitor_services_online_txt").'</span>';
            } else {
                $status = '<span class="offline">'.$app->lng("monitor_services_offline_txt").'</span>';
            }
            $html .= '<tr>
            <td>'.$app->lng("monitor_services_mydns_txt").'</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['mysqlserver'] != -1) {
            if($data['mysqlserver'] == 1) {
                $status = '<span class="online">'.$app->lng("monitor_services_online_txt").'</span>';
            } else {
                $status = '<span class="offline">'.$app->lng("monitor_services_offline_txt").'</span>';
            }
            $html .= '<tr>
            <td>'.$app->lng("monitor_services_mysql_txt").'</td>
            <td>'.$status.'</td>
            </tr>';
        }


        $html .= '</table></div></div>';
    } else {
        $html = '<p>'.$app->lng("no_data_services_txt").'</p>';
    }


    return $html;
}

function showSystemUpdate() {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'system_update' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $html =
           '<div class="systemmonitor-state state-'.$record['state'].'">
            <div class="systemmonitor-content icons32 ico-'.$record['state'].'">';
        /*
         * First, we have to detect, if there is any monitoring-data.
         * If not (because the destribution is not supported) show this.
         */
        if ($record['state'] == 'no_state'){
            $html .= '<p>'.$app->lng("monitor_updates_nosupport_txt").'</p>';
        }
        else {
            $data = unserialize($record['data']);
            $html .= nl2br($data['output']);
        }
        $html .= '</div></div>';
    } else {
        $html = '<p>'.$app->lng("no_data_updates_txt").'</p>';
    }

    return $html;
}

function showRaidState() {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'raid_state' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $html =
           '<div class="systemmonitor-state state-'.$record['state'].'">
            <div class="systemmonitor-content icons32 ico-'.$record['state'].'">';

        /*
         * First, we have to detect, if there is any monitoring-data.
         * If not (because the destribution is not supported) show this.
         */
        if ($record['state'] == 'no_state'){
            $html .= '<p>'.$app->lng("monitor_nomdadm_txt").'</p>';
        }
        else {
            $data = unserialize($record['data']);
            $html .= nl2br($data['output']);
        }
        $html .= '</div></div>';

    } else {
        $html = '<p>'.$app->lng("no_data_raid_txt").'</p>';
    }

    return $html;
}

function showRKHunter() {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'rkhunter' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $html =
           '<div class="systemmonitor-state state-'.$record['state'].'">
            <div class="systemmonitor-content icons32 ico-'.$record['state'].'">';

        /*
         * First, we have to detect, if there is any monitoring-data.
         * If not (because rkhunter is not installed) show this.
         */
        $data = unserialize($record['data']);
        if ($data['output'] == ''){
            $html .= '<p>'.$app->lng("monitor_norkhunter_txt").'</p>';
        }
        else {
            $html .= nl2br($data['output']);
        }
        $html .= '</div></div>';

    } else {
        $html = '<p>'.$app->lng("no_data_rkhunter_txt").'</p>';
    }

    return $html;
}

function showFail2ban() {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'log_fail2ban' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $html =
           '<div class="systemmonitor-state state-'.$record['state'].'">
            <div class="systemmonitor-content icons32 ico-'.$record['state'].'">';

        /*
         * First, we have to detect, if there is any monitoring-data.
         * If not (because fail2ban is not installed) show this.
         */
        $data = unserialize($record['data']);
        if ($data == ''){
            $html .= '<p>'.
			'fail2ban is not installed at this server.<br />' .
			'See more (for debian) <a href="http://www.howtoforge.com/fail2ban_debian_etch" target="htf">here...</a>'.
			'</p>';
        }
        else {
            $html .= nl2br($data);
        }
        $html .= '</div></div>';

    } else {
        $html = '<p>There is no data available at the moment.</p>';
    }

    return $html;
}

function showMailq() {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'mailq' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);
        $html = nl2br($data['output']);
    } else {
        $html = '<p>'.$app->lng("no_data_mailq_txt").'</p>';
    }

    return $html;
}

function getDataTime($type) {
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT created FROM monitor_data WHERE type = '" . $type . "' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    /* TODO: datetimeformat should be set somewhat other way */
    $dateTimeFormat = $app->lng("monitor_settings_datetimeformat_txt");

    if(isset($record['created'])) {
//        $res = date('Y-m-d H:i', $record['created']);
        $res = date($dateTimeFormat, $record['created']);
    } else {
        $res = '????-??-?? ??:??';
    }
    return $res;
}
?>
