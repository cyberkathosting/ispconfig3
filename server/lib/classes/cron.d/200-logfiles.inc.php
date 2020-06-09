<?php

/*
Copyright (c) 2013, Marius Cramer, pixcept KG
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

class cronjob_logfiles extends cronjob {

	// job schedule
	protected $_schedule = '0 0 * * *';

	/* this function is optional if it contains no custom code */
	public function onPrepare() {
		global $app;

		parent::onPrepare();
	}

	/* this function is optional if it contains no custom code */
	public function onBeforeRun() {
		global $app;

		return parent::onBeforeRun();
	}

	public function onRunJob() {
		global $app, $conf;
		
		$app->uses('getconf');
		$server_config = $app->getconf->get_server_config($conf['server_id'], 'server');
		
		if($server_config['log_retention'] > 0) {
			$max_syslog = $app->functions->intval($server_config['log_retention']);
		} else {
			$max_syslog = 10;
		}

		//######################################################################################################
		// Make the web logfiles directories world readable to enable ftp access
		//######################################################################################################

		if(is_dir('/var/log/ispconfig/httpd')) exec('chmod +r /var/log/ispconfig/httpd/*');

		//######################################################################################################
		// Manage and compress web logfiles and create traffic statistics
		//######################################################################################################

		$sql = "SELECT domain_id, domain, type, document_root, web_folder, parent_domain_id, log_retention FROM web_domain WHERE (type = 'vhost' or type = 'vhostsubdomain' or type = 'vhostalias') AND server_id = ?";
		$records = $app->db->queryAllRecords($sql, $conf['server_id']);
		foreach($records as $rec) {

			//* create traffic statistics based on yesterdays access log file
			$yesterday = date('Ymd', time() - 86400);

			$log_folder = 'log';
			if($rec['type'] == 'vhostsubdomain' || $rec['type'] == 'vhostalias') {
				$tmp = $app->db->queryOneRecord('SELECT `domain` FROM web_domain WHERE domain_id = ?', $rec['parent_domain_id']);
				$subdomain_host = preg_replace('/^(.*)\.' . preg_quote($tmp['domain'], '/') . '$/', '$1', $rec['domain']);
				if($subdomain_host == '') $subdomain_host = 'web'.$rec['domain_id'];
				$log_folder .= '/' . $subdomain_host;
				unset($tmp);
			}

			$log_retention = $rec['log_retention'];

			$logfile = $rec['document_root'].'/' . $log_folder . '/'.$yesterday.'-access.log';
			$total_bytes = 0;

			$handle = @fopen($logfile, "r");
			if ($handle) {
				while (($line = fgets($handle, 4096)) !== false) {
					if (preg_match('/^\S+ \S+ \S+ \[.*?\] "\S+.*?" \d+ (\d+) ".*?" ".*?"/', $line, $m)) {
						$total_bytes += intval($m[1]);
					}
				}

				//* Insert / update traffic in master database
				$traffic_date = date('Y-m-d', time() - 86400);
				$tmp = $app->dbmaster->queryOneRecord("select hostname from web_traffic where hostname=? and traffic_date=?", $rec['domain'], $traffic_date);
				if(is_array($tmp) && count($tmp) > 0) {
					$sql = "UPDATE web_traffic SET traffic_bytes=traffic_bytes + ? WHERE hostname = ? AND traffic_date = ?";
					$app->dbmaster->query($sql, $total_bytes, $rec['domain'], $traffic_date);
				} else {
					$sql = "INSERT INTO web_traffic (hostname, traffic_date, traffic_bytes) VALUES (?, ?, ?)";
					$app->dbmaster->query($sql, $rec['domain'], $traffic_date, $total_bytes);
				}

				fclose($handle);
			}

			$yesterday2 = date('Ymd', time() - 86400*2);
			$logfile = $rec['document_root'].'/' . $log_folder . '/'.$yesterday2.'-access.log';

			//* Compress logfile
			if(@is_file($logfile)) {
				// Compress yesterdays logfile
				$app->system->exec_safe("gzip -c ? > ?", $logfile, $logfile . '.gz');
				unlink($logfile);
			}
			
			$cron_logfiles = array('cron.log', 'cron_error.log', 'cron_wget.log');
			foreach($cron_logfiles as $cron_logfile) {
				$cron_logfile = $rec['document_root'].'/' . $log_folder . '/' . $cron_logfile;
				
				// rename older files (move up by one)
				$num = $log_retention;
				while($num >= 1) {
					if(is_file($cron_logfile . '.' . $num . '.gz')) rename($cron_logfile . '.' . $num . '.gz', $cron_logfile . '.' . ($num + 1) . '.gz');
					$num--;
				}
				
				// compress current logfile
				if(is_file($cron_logfile)) {
					$app->system->exec_safe("gzip -c ? > ?", $cron_logfile, $cron_logfile . '.1.gz');
					$app->system->exec_safe("cat /dev/null > ?", $cron_logfile);
				}
				// remove older logs
				$num = $log_retention;
				while(is_file($cron_logfile . '.' . $num . '.gz')) {
					@unlink($cron_logfile . '.' . $num . '.gz');
					$num++;
				}
			}

			// rotate and compress the error.log 
			$error_logfile = $rec['document_root'].'/' . $log_folder . '/error.log';
			// rename older files (move up by one)
			$num = $log_retention;
			while($num >= 1) {
				if(is_file($error_logfile . '.' . $num . '.gz')) rename($error_logfile . '.' . $num . '.gz', $error_logfile . '.' . ($num + 1) . '.gz');
				$num--;
			}
			// compress current logfile
			if(is_file($error_logfile)) {
				$app->system->exec_safe("gzip -c ? > ?", $error_logfile, $error_logfile . '.1.gz');
				$app->system->exec_safe("cat /dev/null > ?", $error_logfile);
			}

			// delete logfiles after x days (default 10)
			if($log_retention > 0) {
				foreach (glob($rec['document_root'].'/' . $log_folder . '/'."*.log*") as $logfile) {
					$now = time();
					if (is_file($logfile))
						if ($now - filemtime($logfile) >= 60 * 60 * 24 * $log_retention)
							unlink($logfile);
				}
			}

		}

		//* Delete old logfiles in /var/log/ispconfig/httpd/ that were created by vlogger for the hostname of the server
		exec('hostname -f', $tmp_hostname);
		if($tmp_hostname[0] != '' && is_dir('/var/log/ispconfig/httpd/'.$tmp_hostname[0])) {
			$app->system->exec_safe("cd ?; find . -mtime +$max_syslog -name '*.log' | xargs rm > /dev/null 2> /dev/null", '/var/log/ispconfig/httpd/'.$tmp_hostname[0]);
		}
		unset($tmp_hostname);

		//######################################################################################################
		// Rotate the ispconfig.log file
		//######################################################################################################


		$ispconfig_logfiles = array('ispconfig.log', 'cron.log', 'auth.log');
		foreach($ispconfig_logfiles as $ispconfig_logfile) {
			$num = $max_syslog;
			$ispconfig_logfile = $conf['ispconfig_log_dir'].'/'.$ispconfig_logfile;
			// rename older files (move up by one)
			while($num >= 1) {
				if(is_file($ispconfig_logfile . '.' . $num . '.gz')) rename($ispconfig_logfile . '.' . $num . '.gz', $ispconfig_logfile . '.' . ($num + 1) . '.gz');
				$num--;
			}
			// compress current logfile
			if(is_file($ispconfig_logfile)) {
				$app->system->exec_safe("gzip -c ? > ?", $ispconfig_logfile, $ispconfig_logfile . '.1.gz');
				$app->system->exec_safe("cat /dev/null > ?", $ispconfig_logfile);
			}
			// remove older logs
			$num = $max_syslog;
			while(is_file($ispconfig_logfile . '.' . $num . '.gz')) {
				@unlink($ispconfig_logfile . '.' . $num . '.gz');
				$num++;
			}
		}

		//######################################################################################################
		// Cleanup website tmp directories
		//######################################################################################################

		$sql = "SELECT domain_id, domain, document_root, system_user FROM web_domain WHERE server_id = ?";
		$records = $app->db->queryAllRecords($sql, $conf['server_id']);
		$app->uses('system');
		if(is_array($records)) {
			foreach($records as $rec){
				$tmp_path = realpath($rec['document_root'].'/tmp');
				if($tmp_path != '' && strlen($tmp_path) > 10 && is_dir($tmp_path) && $app->system->is_user($rec['system_user'])){
					$app->system->exec_safe("cd ?; find . -mtime +1 -name 'sess_*' | grep -v -w .no_delete | xargs rm > /dev/null 2> /dev/null", $tmp_path);
				}
			}
		}

		//######################################################################################################
		// Cleanup logs in master database (only the "master-server")
		//######################################################################################################

		if ($app->dbmaster == $app->db) {
			/** 7 days */


			$tstamp = time() - (60*60*24*7);

			/*
             *  Keep 7 days in sys_log
             * (we can delete the old items, because if they are OK, they don't interrest anymore
             * if they are NOT ok, the server will try to process them in 1 minute and so the
             * error appears again after 1 minute. So it is no problem to delete the old one!
             */
			$sql = "DELETE FROM sys_log WHERE tstamp < ? AND server_id != 0";
			$app->dbmaster->query($sql, $tstamp);
			
			/*
			 * now delete those entries without a linked datalog entry (datalog_id = 0)
			 */
			$sql = "DELETE FROM sys_log WHERE tstamp < ? AND server_id = 0 AND datalog_id = 0";
			$app->dbmaster->query($sql, $tstamp);

			/*
			 * now delete those entries with a linked datalog entry (datalog_id != 0) only if older than 30 days
			 */
			$sql = "DELETE FROM sys_log WHERE tstamp < ? AND server_id = 0 AND datalog_id != 0";
			$app->dbmaster->query($sql, $tstamp - (3600 * 24 * 23));

			/*
             * Delete all remote-actions "done" and older than 7 days
             * ATTENTION: We have the same problem as described in cleaning the datalog. We must not
             * delete the last entry
             */
			$sql = "SELECT max(action_id) FROM sys_remoteaction";
			$res = $app->dbmaster->queryOneRecord($sql);
			$maxId = $res['max(action_id)'];
			$sql =  "DELETE FROM sys_remoteaction WHERE tstamp < ? AND action_state = 'ok' AND action_id < ?";
			$app->dbmaster->query($sql, $tstamp, $maxId);

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
					$sql =  "DELETE FROM sys_datalog WHERE tstamp < ? AND server_id = ? AND datalog_id < ? AND datalog_id < ?";
					//  echo $sql . "\n";
					$app->dbmaster->query($sql, $tstamp, $server['server_id'], $server['updated'], $maxId);
				}
			}
		}


		parent::onRunJob();
	}

	/* this function is optional if it contains no custom code */
	public function onAfterRun() {
		global $app;

		parent::onAfterRun();
	}

}

?>
