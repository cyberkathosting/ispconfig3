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
        $html =
        '<table id="system_load">
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
            </table>';
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
        $html = '<table id="system_disk">';
        foreach($data as $line) {
            $html .= '<tr>';
            foreach ($line as $item) {
                $html .= '<td>' . $item . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
    } else {
        $html = '<p>'.$app->lng("no_data_diskusage_txt").'</p>';
    }


    return $html;
}


function showMemUsage ()
{
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'mem_usage' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);

        /*
        Format the data
        */
        $html = '<table id="system_memusage">';

        foreach($data as $key => $value){
            if ($key != '') {
                $html .= '<tr>
                    <td>' . $key . ':</td>
                    <td>' . $value . '</td>
                    </tr>';
            }
        }
        $html .= '</table>';
    } else {
        $html = '<p>'.$app->lng("no_data_memusage_txt").'</p>';
    }

    return $html;
}

function showCpuInfo ()
{
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'cpu_info' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);

        /*
        Format the data
        */
        $html = '<table id="system_cpu">';
        foreach($data as $key => $value){
            if ($key != '') {
                $html .= '<tr>
                    <td>' . $key . ':</td>
                    <td>' . $value . '</td>
                    </tr>';
            }
        }
        $html .= '</table>';
    } else {
        $html = '<p>'.$app->lng("no_data_cpuinfo_txt").'</p>';
    }

    return $html;
}

function showServices ()
{
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'services' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);

        /*
        Format the data
        */
        $html = '<table id="system_services">';

        if($data['webserver'] != -1) {
            if($data['webserver'] == 1) {
                $status = '<span class="online">Online</span>';
            } else {
                $status = '<span class="offline">Offline</span>';
            }
            $html .= '<tr>
            <td>Web-Server:</td>
            <td>'.$status.'</td>
            </tr>';
        }


        if($data['ftpserver'] != -1) {
            if($data['ftpserver'] == 1) {
                $status = '<span class="online">Online</span>';
            } else {
                $status = '<span class="offline">Offline</span>';
            }
            $html .= '<tr>
            <td>FTP-Server:</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['smtpserver'] != -1) {
            if($data['smtpserver'] == 1) {
                $status = '<span class="online">Online</span>';
            } else {
                $status = '<span class="offline">Offline</span>';
            }
            $html .= '<tr>
            <td>SMTP-Server:</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['pop3server'] != -1) {
            if($data['pop3server'] == 1) {
                $status = '<span class="online">Online</span>';
            } else {
                $status = '<span class="offline">Offline</span>';
            }
            $html .= '<tr>
            <td>POP3-Server:</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['imapserver'] != -1) {
            if($data['imapserver'] == 1) {
                $status = '<span class="online">Online</span>';
            } else {
                $status = '<span class="offline">Offline</span>';
            }
            $html .= '<tr>
            <td>IMAP-Server:</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['bindserver'] != -1) {
            if($data['bindserver'] == 1) {
                $status = '<span class="online">Online</span>';
            } else {
                $status = '<span class="offline">Offline</span>';
            }
            $html .= '<tr>
            <td>DNS-Server:</td>
            <td>'.$status.'</td>
            </tr>';
        }

        if($data['mysqlserver'] != -1) {
            if($data['mysqlserver'] == 1) {
                $status = '<span class="online">Online</span>';
            } else {
                $status = '<span class="offline">Offline</span>';
            }
            $html .= '<tr>
            <td>mySQL-Server:</td>
            <td>'.$status.'</td>
            </tr>';
        }


        $html .= '</table></div>';
    } else {
        $html = '<p>'.$app->lng("no_data_services_txt").'</p>';
    }


    return $html;
}

function showSystemUpdate()
{
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'system_update' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        /*
         * First, we have to detect, if there is any monitoring-data.
         * If not (because the destribution is not supported) show this.
         */
        if ($record['state'] == 'no_state'){
            $html = '<p>' . "Your distribution is not supported for this monitoring" . '</p>';
        }
        else {
            $data = unserialize($record['data']);
            $html = nl2br($data['output']);
        }
    } else {
        $html = '<p>' . "No Update-Data available" . '</p>';
    }

    return $html;
}

function showRaidState()
{
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'raid_state' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        /*
         * First, we have to detect, if there is any monitoring-data.
         * If not (because the destribution is not supported) show this.
         */
        if ($record['state'] == 'no_state'){
            $html = '<p>' . "mdadm ist not installed or your Server has no supported RAID" . '</p>';
        }
        else {
            $data = unserialize($record['data']);
            $html = nl2br($data['output']);
        }
    } else {
        $html = '<p>' . "No RAID-Data available" . '</p>';
    }

    return $html;
}

function showMailq()
{
    global $app;

    /* fetch the Data from the DB */
    $record = $app->db->queryOneRecord("SELECT data, state FROM monitor_data WHERE type = 'mailq' and server_id = " . $_SESSION['monitor']['server_id'] . " order by created desc");

    if(isset($record['data'])) {
        $data = unserialize($record['data']);
        $html = nl2br($data['output']);
    } else {
        $html = '<p>' . "No Mailq-Data available" . '</p>';
    }

    return $html;
}
?>
