<?php
/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

$filesync_list = $app->db->queryAllRecords("SELECT * FROM sys_filesync WHERE active = 1");

if(is_array($filesync_list)) {
	foreach($filesync_list as $filesync) {
		
		$ftp_host = escapeshellcmd($filesync['ftp_host']);
		$ftp_path = escapeshellcmd($filesync['ftp_path']);
		$ftp_username = escapeshellcmd($filesync['ftp_username']);
		$ftp_password = escapeshellcmd($filesync['ftp_password']);
		$local_path = escapeshellcmd($filesync['local_path']);
		$wput_options = escapeshellcmd($filesync['wput_options']);
		
		if(substr($ftp_path,0,1) == "/") $ftp_path = substr($ftp_path,1);
		
		$exec_string = $conf["programs"]["wput"] ." $wput_options $local_path ftp://$ftp_username:$ftp_password@$ftp_host/$ftp_path";
		$handle = popen($exec_string, 'r');
		echo "<pre>";
		while($read = fread($handle, 2096)) { 
			echo $read;
		}
		echo "</pre>";
		pclose($handle); 
	}
}

?>