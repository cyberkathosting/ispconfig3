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

// Checke Berechtigungen für Modul
if(!stristr($_SESSION["s"]["user"]["modules"],'monitor')) {
	header("Location: ../index.php");
	exit;
}

$mod = $_GET["mod"];
$output = '';

switch($mod) {
	case 'load':
		$template = 'templates/system.htm';
		$output .= show_load();
		$title = 'System Load';
		$description = '';
	break;
	case 'disk':
		$template = 'templates/system.htm';
		$output .= show_disk();
		$title = 'Disk usage';
		$description = '';
	break;
	case 'memusage':
		$template = 'templates/system.htm';
		$output .= show_memusage();
		$title = 'Memory usage';
		$description = '';
	break;
	case 'cpu':
		$template = 'templates/system.htm';
		$output .= show_cpu();
		$title = 'CPU Info';
		$description = '';
	break;
	case 'services':
		$template = 'templates/system.htm';
		$output .= show_services();
		$title = 'Status of services';
		$description = '';
	break;
	case 'index':
		$template = 'templates/system.htm';
		$output .= show_load();
		$output .= '&nbsp;'. show_disk();
		$output .= '&nbsp;'.show_services();
		$title = 'System Monitor';
		$description = '';
	break;
	default:
		$template = '';
	break;
}


// Loading the template
$app->uses('tpl');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl',$template);

$app->tpl->setVar("output",$output);
$app->tpl->setVar("title",$title);
$app->tpl->setVar("description",$description);


$app->tpl_defaults();
$app->tpl->pparse();




function show_load(){
	global $app;

    $html_out .= '<div align="left"><table width="400" border="0" cellspacing="1" cellpadding="4" bgcolor="#CCCCCC">';

    $fd = popen ("uptime", "r");
    while (!feof($fd)) {
        $buffer .= fgets($fd, 4096);
    }

        $uptime = split(",",strrev($buffer));

    $online = split("  ",strrev($uptime[4]));

    $proc_uptime = shell_exec("cat /proc/uptime | cut -f1 -d' '");
    $days = floor($proc_uptime/86400);
    $hours = floor(($proc_uptime-$days*86400)/3600);
    $minutes = str_pad(floor(($proc_uptime-$days*86400-$hours*3600)/60), 2, "0", STR_PAD_LEFT);

    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$app->lng("Server Online seit").':</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$days.'d, '.$hours.':'.$minutes.'h</font></center></td>
     </tr>';

    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$app->lng("User Online").':</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.strrev($uptime[3]).'</font></center></td>
     </tr>';

     $ausl = split(":",strrev($uptime[2]));
     $ausl1 = $ausl[1];


     $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$app->lng("System Load 1 Minute").':</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$ausl1.'</font></center></td>
     </tr>';

     $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$app->lng("System Load 5 Minuten").':</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.strrev($uptime[1]).'</font></center></td>
     </tr>';

     $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$app->lng("System Load 15 Minuten").':</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.strrev($uptime[0]).'</font></center></td>
     </tr>';

    $html_out .= '</table></div>';


    return $html_out;
}

function show_disk () {
    global $app;


    $html_out .= '<div align="left"><table width="400" border="0" cellspacing="1" cellpadding="4" bgcolor="#CCCCCC">';

    $fd = popen ("df -h", "r");
    while (!feof($fd)) {
        $buffer .= fgets($fd, 4096);
    }

        $df_out = split("\n",$buffer);
        $df_num = sizeof($df_out);
        for($i=0;$i<$df_num;$i++){
          if(ltrim($df_out[$i]) != $df_out[$i]){
            if(isset($df_out[($i-1)])){
              $df_out[($i-1)] .= $df_out[$i];
              unset($df_out[$i]);
            }
          }
        }

        $html_out .= '<tr>';
        $mrow = 0;
        foreach($df_out as $df_line) {
        $values = preg_split ("/[\s]+/", $df_line);
        $mln = 0;
        $font_class = 'normal_bold';
        if($mrow > 0) $font_class = 'normal';
        foreach($values as $value) {
        $align = 'left';
        if($mln > 0 and $mln < 5) $align = 'right';
        if($mln < 6 and $value != "") $html_out .= '<td bgcolor="#FFFFFF" class="frmText11" align="'.$align.'">'.$value.'</td>';
        $mln++;
        }
        $mrow++;

        $html_out .= '</tr>';
        }


    $html_out .= '</table></div>';


    return $html_out;
}


function show_memusage ()
    {
    global $app;

    $html_out .= '<div align="left"><table width="400" border="0" cellspacing="1" cellpadding="4" bgcolor="#CCCCCC">';

    $fd = fopen ("/proc/meminfo", "r");
    while (!feof($fd)) {
        $buffer .= fgets($fd, 4096);
    }
    fclose($fd);

    $meminfo = split("\n",$buffer);

    foreach($meminfo as $mline){
    if($x > 2 and trim($mline) != "") {

    $mpart = split(":",$mline);

    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$mpart[0].':</font></td>
       <td width="30%" bgcolor="#FFFFFF" align="right"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$mpart[1].'</font></td>
     </tr>';
    }

    $x++;
    }
    $html_out .= '</table></div>';
    return $html_out;
}

function show_cpu ()
    {
    global $app;

    $html_out .= '<div align="left"><table width="400" border="0" cellspacing="1" cellpadding="4" bgcolor="#CCCCCC">';

        $n = 0;
        if(is_readable("/proc/cpuinfo")) {
            if($fd = fopen ("/proc/cpuinfo", "r")) {
                    while (!feof($fd)) {
                        $buffer .= fgets($fd, 4096);
                                $n++;
                                if($n > 100) break;
                    }
                    fclose($fd);
                }
    }

    $meminfo = split("\n",$buffer);

        if(is_array($meminfo)) {
    foreach($meminfo as $mline){
    if(trim($mline) != "") {

    $mpart = split(":",$mline);

    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$mpart[0].':</font></td>
       <td width="30%" bgcolor="#FFFFFF" align="center"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">'.$mpart[1].'</font></td>
     </tr>';
    }
        }

    $x++;
    }
    $html_out .= '</table></div>';


    return $html_out;
    }

function show_services ()
    {
    global $app;

    $html_out .= '<div align="left"><table width="400" border="0" cellspacing="1" cellpadding="4" bgcolor="#CCCCCC">';

    // Checke Webserver
    if(_check_tcp('localhost',80)) {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#006600"><b>Online</b></font>';
    } else {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#FF0000"><b>Offline</b></font>';
    }
    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">Web-Server:</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center>'.$status.'</center></td>
     </tr>';


    // Checke FTP-Server
    if(_check_ftp('localhost',21)) {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#006600"><b>Online</b></font>';
    } else {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#FF0000"><b>Offline</b></font>';
    }
    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">FTP-Server:</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center>'.$status.'</center></td>
     </tr>';

     // Checke SMTP-Server
    if(_check_tcp('localhost',25)) {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#006600"><b>Online</b></font>';
    } else {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#FF0000"><b>Offline</b></font>';
    }
    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">SMTP-Server:</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center>'.$status.'</center></td>
     </tr>';

     // Checke POP3-Server
    if(_check_tcp('localhost',110)) {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#006600"><b>Online</b></font>';
    } else {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#FF0000"><b>Offline</b></font>';
    }
    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">POP3-Server:</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center>'.$status.'</center></td>
     </tr>';

     // Checke BIND-Server
    if(_check_tcp('localhost',53)) {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#006600"><b>Online</b></font>';
    } else {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#FF0000"><b>Offline</b></font>';
    }
    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">DNS-Server:</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center>'.$status.'</center></td>
     </tr>';

      // Checke MYSQL-Server
    //if($this->_check_tcp('localhost',3306)) {
    $status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#006600"><b>Online</b></font>';
    //} else {
    //$status = '<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="#FF0000"><b>Offline</b></font>';
    //}
    $html_out .= '<tr>
       <td width="70%" bgcolor="#FFFFFF"><font face="Verdana, Arial, Helvetica, sans-serif" size="2">mySQL-Server:</font></td>
       <td width="30%" bgcolor="#FFFFFF"><center>'.$status.'</center></td>
     </tr>';


    $html_out .= '</table></div>';


    return $html_out;
}

function _check_tcp ($host,$port) {

        $fp = @fsockopen ($host, $port, &$errno, &$errstr, 2);

        if ($fp) {
            return true;
            fclose($fp);
        } else {
            return false;
            fclose($fp);
        }
}

function _check_udp ($host,$port) {

        $fp = @fsockopen ('udp://'.$host, $port, &$errno, &$errstr, 2);

        if ($fp) {
            return true;
            fclose($fp);
        } else {
            return false;
            fclose($fp);
        }
}

function _check_ftp ($host,$port){

      $conn_id = @ftp_connect($host, $port);

      if($conn_id){
        @ftp_close($conn_id);
        return true;
      } else {
        @ftp_close($conn_id);
        return false;
      }
}


?>