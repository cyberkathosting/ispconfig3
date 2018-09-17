<?php

/*
 * Author Gody - Orm 2016
 * You need to configure daily log rotation for pureftp (/etc/logorate.d/pure-ftpd-comon)
 * TODO: replace logrotate to ISPConfig log rotation
 */

class cronjob_ftplogfiles extends cronjob {

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

		//######################################################################################################
		// Make the ftp logfiles directories world readable to enable ftp access
		//######################################################################################################

		if(is_dir('/var/log/pure-ftpd/')) exec('chmod +r /var/log/pure-ftpd/*');

		//######################################################################################################
		// Manage and compress ftp logfiles and create traffic statistics
		//######################################################################################################
		$sql = "SELECT domain_id, domain, type, document_root, web_folder, parent_domain_id FROM web_domain WHERE (type = 'vhost' or type = 'vhostsubdomain' or type = 'vhostalias') AND server_id = ?";
		$records = $app->db->queryAllRecords($sql, $conf['server_id']);
		
		function parse_ftp_log($line){		
			if(preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3} - (.+) \[(\d+\/\w+\/\d+):.+\] "(PUT|GET) .+" \d+ (\d+)$/', $line, $matches) == false) return false;

			$date = date('Y-m-d', strtotime(str_replace('/', '-', $matches[2])));  // Correction date
		
			switch($matches[3])
			{
				case 'PUT':
					$direction = 'in';
					break;
				case 'GET':
					$direction = 'out';
					break;
			}		
			// Returned array
			return array('username' => $matches[1],	'date' => $date,'direction' => $direction,	'size' => $matches[4]);	
		}
		
		function add_ftp_traffic(&$traffic_array, $parsed_line)
		{		
			if(is_array($traffic_array[$parsed_line['date']]) && array_key_exists($parsed_line['domain'], $traffic_array[$parsed_line['date']]))
			{
				$traffic_array[$parsed_line['date']][$parsed_line['domain']][$parsed_line['direction']] += $parsed_line['size'];
			}
			else
			{
				$traffic_array[$parsed_line['date']][$parsed_line['domain']] = array('in' => 0, 'out' => 0 );
				$traffic_array[$parsed_line['date']][$parsed_line['domain']][$parsed_line['direction']] = $parsed_line['size'];
			}
		}
		
		$fp = @fopen('/var/log/pure-ftpd/transfer.log.1', 'r');
		$ftp_traffic = array();

		if ($fp) {
			// cumule des stats journalière dans un tableau
			while($line = fgets($fp))
			{
				$parsed_line = parse_ftp_log($line);

				$sql = "SELECT wd.domain FROM ftp_user AS fu INNER JOIN web_domain AS wd ON fu.parent_domain_id = wd.domain_id WHERE fu.username = ? ";		
				$temp = $app->db->queryOneRecord($sql, $parsed_line['username'] );

				$parsed_line['domain'] = $temp['domain'];

				add_ftp_traffic($ftp_traffic, $parsed_line);
			}

			fclose($fp);
		}

		// Save du tableau en BD
		foreach($ftp_traffic as $traffic_date => $all_traffic)
		{
			foreach ( $all_traffic as $hostname =>$traffic)
			{
				$sql1 = "SELECT hostname FROM ftp_traffic WHERE hostname = ? AND traffic_date = ?";
				$tmp = $app->dbmaster->queryOneRecord($sql1, $hostname , $traffic_date);		
				
				if(is_array($tmp) && count($tmp) > 0) {
					$sql = "UPDATE ftp_traffic SET in_bytes=in_bytes+ ?, out_bytes=out_bytes+ ? WHERE hostname = ? AND traffic_date = ? ";
				}
				else
				{
					$sql = "INSERT INTO ftp_traffic (in_bytes, out_bytes, hostname, traffic_date ) VALUES ( ?, ?, ?, ? )";
				}
				
				$resultat = $app->dbmaster->query($sql, $traffic['in'], $traffic['out'], $hostname, $traffic_date );
				
				//if($resultat == 1){
					//echo 'finished.'.PHP_EOL; // maybe you have a better solution ? 	}						
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
