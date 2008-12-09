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

require("lib/config.inc.php");
require("lib/app.inc.php");

set_time_limit(0);

// make sure server_id is always an int
$conf["server_id"] = intval($conf["server_id"]);

	
// Load required base-classes
$app->uses('ini_parser,file,services');


#######################################################################################################
// store the mailbox statistics in the database
#######################################################################################################

$sql = "SELECT mailuser_id,maildir FROM mail_user WHERE server_id = ".$conf["server_id"];
$records = $app->db->queryAllRecords($sql);
foreach($records as $rec) {
	if(@is_file($rec["maildir"].'.ispconfig_mailsize')) {
		
		// rename file
		rename($rec["maildir"].'.ispconfig_mailsize',$rec["maildir"].'.ispconfig_mailsize_save');
		
		// Read the file
		$lines = file($rec["maildir"].'.ispconfig_mailsize_save');
		$mail_traffic = 0;
		foreach($lines as $line) {
			$mail_traffic += intval($line);
		}
		unset($lines);
		
		// Delete backup file
		if(@is_file($rec["maildir"].'.ispconfig_mailsize_save')) unlink($rec["maildir"].'.ispconfig_mailsize_save');
		
		// Save the traffic stats in the sql database
		$tstamp = date("Y-m");
		
		$sql = "SELECT * FROM mail_traffic WHERE month = '$tstamp' AND mailuser_id = ".$rec["mailuser_id"];
		$tr = $app->db->queryOneRecord($sql);
		
		$mail_traffic += $tr["traffic"];
		if($tr["traffic_id"] > 0) {
			$sql = "UPDATE mail_traffic SET traffic = $mail_traffic WHERE traffic_id = ".$tr["traffic_id"];
		} else {
			$sql = "INSERT INTO mail_traffic (month,mailuser_id,traffic) VALUES ('$tstamp',".$rec["mailuser_id"].",$mail_traffic)";
		}
		$app->db->query($sql);
		echo $sql;
		
	}
	
}

#######################################################################################################
// Create webalizer statistics
#######################################################################################################


$sql = "SELECT domain_id, domain, document_root FROM web_domain WHERE server_id = ".$conf["server_id"];
$records = $app->db->queryAllRecords($sql);
foreach($records as $rec) {
	$yesterday = date("mdY",time() - 86400);
	$logfile = escapeshellcmd($rec["document_root"].'/log/'.$yesterday.'-access.log');
	if(@is_file($logfile)) {
		$domain = escapeshellcmd($rec["domain"]);
		$statsdir = escapeshellcmd($rec["document_root"].'/web/stats');
		$webalizer = '/usr/bin/webalizer';
		$webalizer_conf = '/etc/webalizer/webalizer.conf';
		if(!@is_dir($statsdir)) mkdir($statsdir);
		exec("$webalizer -c $webalizer_conf -n $domain -s $domain -r $domain -q -T -o $statsdir $logfile");
	}
}

#######################################################################################################
// Manage and compress web logfiles
#######################################################################################################

$sql = "SELECT domain_id, domain, document_root FROM web_domain WHERE server_id = ".$conf["server_id"];
$records = $app->db->queryAllRecords($sql);
foreach($records as $rec) {
	$yesterday = date("mdY",time() - 86400);
	$logfile = escapeshellcmd($rec["document_root"].'/log/'.$yesterday.'-access.log');
	if(@is_file($logfile)) {
		// Compress yesterdays logfile
		exec("gzip -c $logfile > $logfile.gz");
	}
	
	// delete logfiles after 30 days
	$month_ago = date("mdY",time() - 86400 * 30);
	$logfile = escapeshellcmd($rec["document_root"].'/log/'.$yesterday.'-access.log.gz');
	if(@is_file($logfile)) {
		unlink($logfile);
	}
}

#######################################################################################################
// Cleanup logs in database
#######################################################################################################
//* Keep 7 days in sys_log
$tstamp = time() - (60*60*24*7);
$sql = "DELETE FROM sys_log WHERE tstamp < $tstamp";
$app->db->query($sql);

//* Keep 7 days in sys_datalog
$tstamp = time() - (60*60*24*7);
$sql = "DELETE FROM sys_datalog WHERE tstamp < $tstamp";
$app->db->query($sql);


die("finished.\n");
?>