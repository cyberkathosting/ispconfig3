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
	if(@is_file($rec["maildir"].'/ispconfig_mailsize')) {

		// rename file
		rename($rec["maildir"].'/ispconfig_mailsize',$rec["maildir"].'/ispconfig_mailsize_save');

		// Read the file
		$lines = file($rec["maildir"].'/ispconfig_mailsize_save');
		$mail_traffic = 0;
		foreach($lines as $line) {
			$mail_traffic += intval($line);
		}
		unset($lines);

		// Delete backup file
		if(@is_file($rec["maildir"].'/ispconfig_mailsize_save')) unlink($rec["maildir"].'/ispconfig_mailsize_save');

		// Save the traffic stats in the sql database
		$tstamp = date("Y-m");

		$sql = "SELECT * FROM mail_traffic WHERE month = '$tstamp' AND mailuser_id = ".$rec["mailuser_id"];
		$tr = $app->dbmaster->queryOneRecord($sql);

		$mail_traffic += $tr["traffic"];
		if($tr["traffic_id"] > 0) {
			$sql = "UPDATE mail_traffic SET traffic = $mail_traffic WHERE traffic_id = ".$tr["traffic_id"];
		} else {
			$sql = "INSERT INTO mail_traffic (month,mailuser_id,traffic) VALUES ('$tstamp',".$rec["mailuser_id"].",$mail_traffic)";
		}
		$app->dbmaster->query($sql);
		echo $sql;

	}

}

#######################################################################################################
// Create webalizer statistics
#######################################################################################################

function setConfigVar( $filename, $varName, $varValue ) {
	if($lines = @file($filename)) {
		$out = '';
		$found = 0;
		foreach($lines as $line) {
			list($key, $value) = preg_split("/[\t= ]+/", $line, 2);
			if($key == $varName) {
				$out .= $varName." ".$varValue."\n";
				$found = 1;
			} else {
				$out .= $line;
			}
		}
		if($found == 0) {
			//* add \n if the last line does not end with \n or \r
			if(substr($out,-1) != "\n" && substr($out,-1) != "\r") $out .= "\n";
			//* add the new line at the end of the file
			if($append == 1) $out .= $varName." ".$varValue."\n";
		}

		file_put_contents($filename,$out);
	}
}


$sql = "SELECT domain_id, domain, document_root FROM web_domain WHERE server_id = ".$conf["server_id"];
$records = $app->db->queryAllRecords($sql);

foreach($records as $rec) {
	$yesterday = date("Ymd",time() - 86400);
	$logfile = escapeshellcmd($rec["document_root"].'/log/'.$yesterday.'-access.log');
	if(!@is_file($logfile)) {
		$logfile = escapeshellcmd($rec["document_root"].'/log/'.$yesterday.'-access.log.gz');
	if(!@is_file($logfile)) {
		continue;
	}
}

$domain = escapeshellcmd($rec["domain"]);
$statsdir = escapeshellcmd($rec["document_root"].'/web/stats');
$webalizer = '/usr/bin/webalizer';
$webalizer_conf_main = '/etc/webalizer/webalizer.conf';
$webalizer_conf = escapeshellcmd($rec["document_root"].'/log/webalizer.conf');

if(!@is_file($webalizer_conf)) {
	exec("cp $webalizer_conf_main $webalizer_conf");
}

if(@is_file($webalizer_conf)) {
	setConfigVar($webalizer_conf, 'Incremental', 'yes');
	setConfigVar($webalizer_conf, 'IncrementalName', $statsdir.'/webalizer.current');
	setConfigVar($webalizer_conf, 'HistoryName', $statsdir.'/webalizer.hist');
}


if(!@is_dir($statsdir)) mkdir($statsdir);
	exec("$webalizer -c $webalizer_conf -n $domain -s $domain -r $domain -q -T -p -o $statsdir $logfile");
}

#######################################################################################################
// Make the web logfiles directories world readable to enable ftp access
#######################################################################################################

exec('chmod +r /var/log/ispconfig/httpd/*');

#######################################################################################################
// Manage and compress web logfiles
#######################################################################################################

$sql = "SELECT domain_id, domain, document_root FROM web_domain WHERE server_id = ".$conf["server_id"];
$records = $app->db->queryAllRecords($sql);
foreach($records as $rec) {
	$yesterday = date("Ymd",time() - 86400);
	$logfile = escapeshellcmd($rec["document_root"].'/log/'.$yesterday.'-access.log');
	if(@is_file($logfile)) {
		// Compress yesterdays logfile
		exec("gzip -c $logfile > $logfile.gz");
		unlink($logfile);
	}

	// delete logfiles after 30 days
	$month_ago = date("Ymd",time() - 86400 * 30);
	$logfile = escapeshellcmd($rec["document_root"].'/log/'.$month_ago.'-access.log.gz');
	if(@is_file($logfile)) {
		unlink($logfile);
	}
}

#######################################################################################################
// Cleanup website tmp directories
#######################################################################################################

/*
$sql = "SELECT domain_id, domain, document_root, system_user FROM web_domain WHERE server_id = ".$conf["server_id"];
$records = $app->db->queryAllRecords($sql);
$app->uses('system');
if(is_array($records)) {
	foreach($records as $rec){
		$tmp_path = realpath(escapeshellcmd($rec["document_root"].'/tmp'));
		if($tmp_path != '' && strlen($tmp_path) > 10 && is_dir($tmp_path) && $app->system->is_user($rec['system_user'])){
			exec("cd ".$tmp_path."; find -ctime +1 -user ".escapeshellcmd($rec['system_user'])." | grep -v -w .no_delete | xargs rm &> /dev/null 2> /dev/null");
			if($app->system->is_user('www-data')) exec("cd ".$tmp_path."; find -ctime +1 -user www-data | grep -v -w .no_delete | xargs rm &> /dev/null 2> /dev/null");
			if($app->system->is_user('wwwrun')) exec("cd ".$tmp_path."; find -ctime +1 -user wwwrun | grep -v -w .no_delete | xargs rm &> /dev/null 2> /dev/null");
		}
	}
}
*/

$sql = "SELECT domain_id, domain, document_root, system_user FROM web_domain WHERE server_id = ".$conf["server_id"];
$records = $app->db->queryAllRecords($sql);
$app->uses('system');
if(is_array($records)) {
	foreach($records as $rec){
		$tmp_path = realpath(escapeshellcmd($rec["document_root"].'/tmp'));
		if($tmp_path != '' && strlen($tmp_path) > 10 && is_dir($tmp_path) && $app->system->is_user($rec['system_user'])){
			exec("cd ".$tmp_path."; find -mtime +2 -name 'sess_*' | grep -v -w .no_delete | xargs rm &> /dev/null 2> /dev/null");
		}
	}
}

#######################################################################################################
// Cleanup logs in master database (only the "master-server")
#######################################################################################################

if ($app->dbmaster == $app->db) {
	/** 7 days */
	$tstamp = time() - (60*60*24*7);

	/*
	 *  Keep 7 days in sys_log
	 * (we can delete the old items, because if they are OK, they don't interrest anymore
	 * if they are NOT ok, the server will try to process them in 1 minute and so the
	 * error appears again after 1 minute. So it is no problem to delete the old one!
	 */
	$sql = "DELETE FROM sys_log WHERE tstamp < " . $tstamp . " AND server_id != 0";
	$app->dbmaster->query($sql);

	/*
	 * Delete all remote-actions "done" and older than 7 days
	 * ATTENTION: We have the same problem as described in cleaning the datalog. We must not
	 * delete the last entry
	 */
	$sql = "SELECT max(action_id) FROM sys_remoteaction";
	$res = $app->dbmaster->queryOneRecord($sql);
	$maxId = $res['max(action_id)'];
	$sql =  "DELETE FROM sys_remoteaction " .
			"WHERE tstamp < " . $tstamp . " " .
			" AND action_status = 'ok' " .
			" AND action_id <" . intval($maxId);
	$app->dbmaster->query($sql);

	/*
	 * The sys_datalog is more difficult.
	 * 1) We have to keet ALL entries with
	 *    server_id=0, because they depend on ALL servers (even if they are not
	 *    actually in the system (and will be insered in 3 days or so).
	 * 2) We have to keey ALL entries which are not actually precessed by the
	 *    server never mind how old they are!
	 * 3) We have to keep the entry with the highest autoinc-id, because mysql calculates the
	 *    autoinc-id as "new value = max(row) +1" and does not store this in a separate table.
	 *    This means, if we delete to entry with the highest autoinc-value then this value is
	 *    reused as autoinc and so there are more than one entries with the same value (over
	 *    for example 4 Weeks). This is confusing for our system.
	 *    ATTENTION 2) and 3) is in some case NOT the same! so we have to check both!
	 */

	/* First we need all servers and the last sys_datalog-id they processed */
	$sql = "SELECT server_id, updated FROM server ORDER BY server_id";
	$records = $app->dbmaster->queryAllRecords($sql);

	/* Then we need the highest value ever */
	$sql = "SELECT max(datalog_id) FROM sys_datalog";
	$res = $app->dbmaster->queryOneRecord($sql);
	$maxId = $res['max(datalog_id)'];

	/* Then delete server by server */
	foreach($records as $server) {
		$tmp_server_id = intval($server['server_id']);
		if($tmp_server_id > 0) {
			$sql = 	"DELETE FROM sys_datalog " .
					"WHERE tstamp < " . $tstamp .
					" AND server_id = " . intval($server['server_id']) .
					" AND datalog_id < " . intval($server['updated']) .
					" AND datalog_id < " . intval($maxId);
		}
//		echo $sql . "\n";
		$app->dbmaster->query($sql);
	}
}

#######################################################################################################
// enforce traffic quota (run only on the "master-server")
#######################################################################################################

if ($app->dbmaster == $app->db) {

	$current_month = date('Y-m');

	//* Check website traffic quota
	$sql = "SELECT sys_groupid,domain_id,domain,traffic_quota,traffic_quota_lock FROM web_domain WHERE traffic_quota > 0 and type = 'vhost'";
	$records = $app->db->queryAllRecords($sql);
	if(is_array($records)) {
		foreach($records as $rec) {

			$web_traffic_quota = $rec['traffic_quota'];
			$domain = $rec['domain'];

			// get the client
			/*
			$client_group_id = $rec["sys_groupid"];
			$client = $app->db->queryOneRecord("SELECT limit_traffic_quota,parent_client_id FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
			$reseller = $app->db->queryOneRecord("SELECT limit_traffic_quota FROM client WHERE client_id = ".intval($client['parent_client_id']));

			$client_traffic_quota = intval($client['limit_traffic_quota']);
			$reseller_traffic_quota = intval($reseller['limit_traffic_quota']);
			*/

			//* get the traffic
			$tmp = $app->db->queryOneRecord("SELECT SUM(traffic_bytes) As total_traffic_bytes FROM web_traffic WHERE traffic_date like '$current_month%' AND hostname = '$domain'");
			$web_traffic = (int)$tmp['total_traffic_bytes']/1024/1024;

			//* Website is over quota, we will disable it
			/*if( ($web_traffic_quota > 0 && $web_traffic > $web_traffic_quota) ||
				($client_traffic_quota > 0 && $web_traffic > $client_traffic_quota) ||
				($reseller_traffic_quota > 0 && $web_traffic > $reseller_traffic_quota)) {*/
			if($web_traffic_quota > 0 && $web_traffic > $web_traffic_quota) {
				$app->dbmaster->datalogUpdate('web_domain', "traffic_quota_lock = 'y',active = 'n'", 'domain_id', $rec['domain_id']);
				$app->log("Traffic quota for ".$rec['domain_id']." Exceeded. Disabling website.",LOGLEVEL_DEBUG);
			} else {
				//* unlock the website, if traffic is lower then quota
				if($rec['traffic_quota_lock'] == 'y') {
					$app->dbmaster->datalogUpdate('web_domain', "traffic_quota_lock = 'n',active = 'y'", 'domain_id', $rec['domain_id']);
					$app->log("Traffic quota for ".$rec['domain_id']." ok again. Enabling website.",LOGLEVEL_DEBUG);
				}
			}
		}
	}


}


die("finished.\n");
?>