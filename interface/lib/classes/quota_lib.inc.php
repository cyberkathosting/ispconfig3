<?php

class quota_lib {
	public function get_quota_data($clientid = null, $readable = true) {
		global $app; 
		
		$tmp_rec =  $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'harddisk_quota' ORDER BY created DESC");
		$monitor_data = array();
		if(is_array($tmp_rec)) {
			foreach ($tmp_rec as $tmp_mon) {
				$monitor_data = array_merge_recursive($monitor_data, unserialize($app->db->unquote($tmp_mon['data'])));
			}
		}
		//print_r($monitor_data);
		
		// select all websites or websites belonging to client
		$sites = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE active = 'y' AND type = 'vhost'".(($clientid != null)?" AND sys_groupid = (SELECT default_group FROM sys_user WHERE client_id=?)":'') . " ORDER BY domain", $clientid);
		
		//print_r($sites);
		if(is_array($sites) && !empty($sites)){
			for($i=0;$i<sizeof($sites);$i++){
				$username = $sites[$i]['system_user'];
				$sites[$i]['used'] = $monitor_data['user'][$username]['used'];
				$sites[$i]['soft'] = $monitor_data['user'][$username]['soft'];
				$sites[$i]['hard'] = $monitor_data['user'][$username]['hard'];
				$sites[$i]['files'] = $monitor_data['user'][$username]['files'];
		
				if (!is_numeric($sites[$i]['used'])){
					if ($sites[$i]['used'][0] > $sites[$i]['used'][1]){
						$sites[$i]['used'] = $sites[$i]['used'][0];
					} else {
						$sites[$i]['used'] = $sites[$i]['used'][1];
					}
				}
				if (!is_numeric($sites[$i]['soft'])) $sites[$i]['soft']=$sites[$i]['soft'][1];
				if (!is_numeric($sites[$i]['hard'])) $sites[$i]['hard']=$sites[$i]['hard'][1];
				if (!is_numeric($sites[$i]['files'])) $sites[$i]['files']=$sites[$i]['files'][1];
				
				$sites[$i]['used_raw'] = $sites[$i]['used'];
				$sites[$i]['soft_raw'] = $sites[$i]['soft'];
				$sites[$i]['hard_raw'] = $sites[$i]['hard'];
				$sites[$i]['files_raw'] = $sites[$i]['files'];
				$sites[$i]['used_percentage'] = ($sites[$i]['soft'] > 0 && $sites[$i]['used'] > 0 ? round($sites[$i]['used'] * 100 / $sites[$i]['soft']) : 0);
				
				if ($readable) {
					// colours
					$sites[$i]['display_colour'] = '#000000';
					if($sites[$i]['soft'] > 0){
						$used_ratio = $sites[$i]['used']/$sites[$i]['soft'];
					} else {
						$used_ratio = 0;
					}
					if($used_ratio >= 0.8) $sites[$i]['display_colour'] = '#fd934f';
					if($used_ratio >= 1) $sites[$i]['display_colour'] = '#cc0000';
			
					if($sites[$i]['used'] > 1024) {
						$sites[$i]['used'] = round($sites[$i]['used'] / 1024, 1).' MB';
					} else {
						if ($sites[$i]['used'] != '') $sites[$i]['used'] .= ' KB';
					}
			
					if($sites[$i]['soft'] > 1024) {
						$sites[$i]['soft'] = round($sites[$i]['soft'] / 1024, 1).' MB';
					} else {
						$sites[$i]['soft'] .= ' KB';
					}
			
					if($sites[$i]['hard'] > 1024) {
						$sites[$i]['hard'] = round($sites[$i]['hard'] / 1024, 1).' MB';
					} else {
						$sites[$i]['hard'] .= ' KB';
					}
			
					if($sites[$i]['soft'] == " KB") $sites[$i]['soft'] = $app->lng('unlimited_txt');
					if($sites[$i]['hard'] == " KB") $sites[$i]['hard'] = $app->lng('unlimited_txt');
					
					if($sites[$i]['soft'] == '0 B' || $sites[$i]['soft'] == '0 KB' || $sites[$i]['soft'] == '0') $sites[$i]['soft'] = $app->lng('unlimited_txt');
					if($sites[$i]['hard'] == '0 B' || $sites[$i]['hard'] == '0 KB' || $sites[$i]['hard'] == '0') $sites[$i]['hard'] = $app->lng('unlimited_txt');
					
					/*
					 if(!strstr($sites[$i]['used'],'M') && !strstr($sites[$i]['used'],'K')) $sites[$i]['used'].= ' B';
					if(!strstr($sites[$i]['soft'],'M') && !strstr($sites[$i]['soft'],'K')) $sites[$i]['soft'].= ' B';
					if(!strstr($sites[$i]['hard'],'M') && !strstr($sites[$i]['hard'],'K')) $sites[$i]['hard'].= ' B';
					*/
				}
				else {
					if (empty($sites[$i]['soft'])) $sites[$i]['soft'] = -1;
					if (empty($sites[$i]['hard'])) $sites[$i]['hard'] = -1;
					
					if($sites[$i]['soft'] == '0 B' || $sites[$i]['soft'] == '0 KB' || $sites[$i]['soft'] == '0') $sites[$i]['soft'] = -1;
					if($sites[$i]['hard'] == '0 B' || $sites[$i]['hard'] == '0 KB' || $sites[$i]['hard'] == '0') $sites[$i]['hard'] = -1;
				}
			}
		}
		
		return $sites;
	}
	
	public function get_trafficquota_data($clientid = null, $lastdays = 0) {
		global $app;
	
		$traffic_data = array();
	
		// select vhosts (belonging to client)
		if($clientid != null){
			$sql_where = " AND sys_groupid = (SELECT default_group FROM sys_user WHERE client_id=?)";
		}
		$sites = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE active = 'y' AND (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias')".$sql_where, $clientid);
	
		$hostnames = array();
		$traffic_data = array();
	
		foreach ($sites as $site) {
			$hostnames[] = $site['domain'];
			$traffic_data[$site['domain']]['domain_id'] = $site['domain_id'];
		}
	
		// fetch all traffic-data of selected vhosts
		if (!empty($hostnames)) {
			$tmp_year = date('Y');
			$tmp_month = date('m');
			// This Month
			$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(traffic_bytes) as t FROM web_traffic WHERE YEAR(traffic_date) = ? AND MONTH(traffic_date) = ? AND hostname IN ? GROUP BY hostname", $tmp_year, $tmp_month, $hostnames);
			foreach ($tmp_recs as $tmp_rec) {
				$traffic_data[$tmp_rec['hostname']]['this_month'] = $tmp_rec['t'];
			}
			// This Year
			$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(traffic_bytes) as t FROM web_traffic WHERE YEAR(traffic_date) = ? AND hostname IN ? GROUP BY hostname", $tmp_year, $hostnames);
			foreach ($tmp_recs as $tmp_rec) {
				$traffic_data[$tmp_rec['hostname']]['this_year'] = $tmp_rec['t'];
			}
				
			$tmp_year = date('Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			$tmp_month = date('m', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			// Last Month
			$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(traffic_bytes) as t FROM web_traffic WHERE YEAR(traffic_date) = ? AND MONTH(traffic_date) = ? AND hostname IN ? GROUP BY hostname", $tmp_year, $tmp_month, $hostnames);
			foreach ($tmp_recs as $tmp_rec) {
				$traffic_data[$tmp_rec['hostname']]['last_month'] = $tmp_rec['t'];
			}
				
			$tmp_year = date('Y', mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
			// Last Year
			$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(traffic_bytes) as t FROM web_traffic WHERE YEAR(traffic_date) = ? AND hostname IN ? GROUP BY hostname", $tmp_year, $hostnames);
			foreach ($tmp_recs as $tmp_rec) {
				$traffic_data[$tmp_rec['hostname']]['last_year'] = $tmp_rec['t'];
			}
				
			if (is_int($lastdays)  && ($lastdays > 0)) {
				// Last xx Days
				$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(traffic_bytes) as t FROM web_traffic WHERE (traffic_date >= DATE_SUB(NOW(), INTERVAL ? DAY)) AND hostname IN ? GROUP BY hostname", $lastdays, $hostnames);
				foreach ($tmp_recs as $tmp_rec) {
					$traffic_data[$tmp_rec['hostname']]['lastdays'] = $tmp_rec['t'];
				}
			}
		}
	
		return $traffic_data;
	}

	public function get_ftptrafficquota_data($clientid = null, $lastdays = 0) {
		global $app;
	
		$traffic_data = array();
	
		// select vhosts (belonging to client)
		if($clientid != null){
			$sql_where = " AND sys_groupid = (SELECT default_group FROM sys_user WHERE client_id=?)";
		}
		$sites = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE active = 'y' AND (type = 'vhost' OR type = 'vhostsubdomain' OR type = 'vhostalias')".$sql_where, $clientid);
	
		$hostnames = array();
		$traffic_data = array();
	
		foreach ($sites as $site) {
			$hostnames[] = $site['domain'];
			$traffic_data[$site['domain']]['domain_id'] = $site['domain_id'];
		}
	
		// fetch all traffic-data of selected vhosts
		if (!empty($hostnames)) {
			$tmp_year = date('Y');
			$tmp_month = date('m');
			// This Month
			$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(in_bytes) AS ftp_in, SUM(out_bytes) AS ftp_out FROM ftp_traffic WHERE YEAR(traffic_date) = ? AND MONTH(traffic_date) = ? AND hostname IN ? GROUP BY hostname", $tmp_year, $tmp_month, $hostnames);
			foreach ($tmp_recs as $tmp_rec) {
				$traffic_data[$tmp_rec['hostname']]['this_month'] = $tmp_rec['t'];
			}
			// This Year
			$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(in_bytes) AS ftp_in, SUM(out_bytes) AS ftp_out FROM ftp_traffic WHERE YEAR(traffic_date) = ? AND hostname IN ? GROUP BY hostname", $tmp_year, $hostnames);
			foreach ($tmp_recs as $tmp_rec) {
				$traffic_data[$tmp_rec['hostname']]['this_year'] = $tmp_rec['t'];
			}
				
			$tmp_year = date('Y', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			$tmp_month = date('m', mktime(0, 0, 0, date("m")-1, date("d"), date("Y")));
			// Last Month
			$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(in_bytes) AS ftp_in, SUM(out_bytes) AS ftp_out FROM ftp_traffic WHERE YEAR(traffic_date) = ? AND MONTH(traffic_date) = ? AND hostname IN ? GROUP BY hostname", $tmp_year, $tmp_month, $hostnames);
			foreach ($tmp_recs as $tmp_rec) {
				$traffic_data[$tmp_rec['hostname']]['last_month'] = $tmp_rec['t'];
			}
				
			$tmp_year = date('Y', mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
			// Last Year
			$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(in_bytes) AS ftp_in, SUM(out_bytes) AS ftp_out FROM ftp_traffic WHERE YEAR(traffic_date) = ? AND hostname IN ? GROUP BY hostname", $tmp_year, $hostnames);
			foreach ($tmp_recs as $tmp_rec) {
				$traffic_data[$tmp_rec['hostname']]['last_year'] = $tmp_rec['t'];
			}
				
			if (is_int($lastdays)  && ($lastdays > 0)) {
				// Last xx Days
				$tmp_recs = $app->db->queryAllRecords("SELECT hostname, SUM(in_bytes) AS ftp_in, SUM(out_bytes) AS ftp_out FROM ftp_traffic WHERE (traffic_date >= DATE_SUB(NOW(), INTERVAL ? DAY)) AND hostname IN ? GROUP BY hostname", $lastdays, $hostnames);
				foreach ($tmp_recs as $tmp_rec) {
					$traffic_data[$tmp_rec['hostname']]['lastdays'] = $tmp_rec['t'];
				}
			}
		}
	
		return $traffic_data;
	}
	
	public function get_mailquota_data($clientid = null, $readable = true) {
		global $app;
		
		$tmp_rec =  $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'email_quota' ORDER BY created DESC");
		$monitor_data = array();
		if(is_array($tmp_rec)) {
			foreach ($tmp_rec as $tmp_mon) {
				//$monitor_data = array_merge_recursive($monitor_data,unserialize($app->db->unquote($tmp_mon['data'])));
				$tmp_array = unserialize($app->db->unquote($tmp_mon['data']));
				if(is_array($tmp_array)) {
					foreach($tmp_array as $username => $data) {
						if(!$monitor_data[$username]['used']) $monitor_data[$username]['used'] = $data['used'];
					}
				}
			}
		}
		//print_r($monitor_data);
		
		// select all email accounts or email accounts belonging to client
		$emails = $app->db->queryAllRecords("SELECT * FROM mail_user".(($clientid != null)? " WHERE sys_groupid = (SELECT default_group FROM sys_user WHERE client_id=?)" : '') . " ORDER BY email", $clientid);
		
		//print_r($emails);
		if(is_array($emails) && !empty($emails)){
			for($i=0;$i<sizeof($emails);$i++){
				$email = $emails[$i]['email'];
				
				$emails[$i]['name'] = $app->functions->htmlentities($emails[$i]['name']);
				$emails[$i]['used'] = isset($monitor_data[$email]['used']) ? $monitor_data[$email]['used'] : array(1 => 0);
		
				if (!is_numeric($emails[$i]['used'])) $emails[$i]['used']=$emails[$i]['used'][1];
				
				$emails[$i]['quota_raw'] = $emails[$i]['quota'];
				$emails[$i]['used_raw'] = $emails[$i]['used'];
				$emails[$i]['used_percentage'] = ($emails[$i]['quota'] > 0 && $emails[$i]['used'] > 0 ? round($emails[$i]['used'] * 100 / $emails[$i]['quota']) : 0);

				
				if ($readable) {
					// colours
					$emails[$i]['display_colour'] = '#000000';
					if($emails[$i]['quota'] > 0){
						$used_ratio = $emails[$i]['used']/$emails[$i]['quota'];
					} else {
						$used_ratio = 0;
					}
					if($used_ratio >= 0.8) $emails[$i]['display_colour'] = '#fd934f';
					if($used_ratio >= 1) $emails[$i]['display_colour'] = '#cc0000';
			
					if($emails[$i]['quota'] == 0){
						$emails[$i]['quota'] = $app->lng('unlimited_txt');
					} else {
                                               $emails[$i]['quota'] = round($emails[$i]['quota'] / 1048576, 1).' MB';
					}
			
			
					if($emails[$i]['used'] < 1544000) {
                                               $emails[$i]['used'] = round($emails[$i]['used'] / 1024, 1).' KB';
					} else {
                                               $emails[$i]['used'] = round($emails[$i]['used'] / 1048576, 1).' MB';
					}
				}
			}
		}
		
		return $emails;
	}
	
	public function get_databasequota_data($clientid = null, $readable = true) {
		global $app;
	
		$tmp_rec =  $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'database_size' ORDER BY created DESC");
		$monitor_data = array();
		if(is_array($tmp_rec)) {
			foreach ($tmp_rec as $tmp_mon) {
				$tmp_array = unserialize($app->db->unquote($tmp_mon['data']));
				if(is_array($tmp_array)) {
					foreach($tmp_array as $key => $data) {
						if(!isset($monitor_data[$data['database_name']]['size'])) $monitor_data[$data['database_name']]['size'] = $data['size'];
					}
				}
			}
		}
		//print_r($monitor_data);
	
		// select all databases belonging to client
		$databases = $app->db->queryAllRecords("SELECT * FROM web_database".(($clientid != null)? " WHERE sys_groupid = (SELECT default_group FROM sys_user WHERE client_id=?)" : '') . " ORDER BY database_name", $clientid);
	
		//print_r($databases);
		if(is_array($databases) && !empty($databases)){
			for($i=0;$i<sizeof($databases);$i++){
				$databasename = $databases[$i]['database_name'];
	
				$databases[$i]['used'] = isset($monitor_data[$databasename]['size']) ? $monitor_data[$databasename]['size'] : 0;
	
				$databases[$i]['quota_raw'] = $databases[$i]['database_quota'];
				$databases[$i]['used_raw'] = $databases[$i]['used'] / 1024 / 1024; //* quota is stored as MB - calculated bytes
				$databases[$i]['used_percentage'] = (($databases[$i]['database_quota'] > 0) && ($databases[$i]['used'] > 0)) ? round($databases[$i]['used_raw'] * 100 / $databases[$i]['database_quota']) : 0;
	
				if ($readable) {
					// colours
					$databases[$i]['display_colour'] = '#000000';
					if($databases[$i]['database_quota'] > 0){
						$used_ratio = $databases[$i]['used'] / $databases[$i]['database_quota'];
					} else {
						$used_ratio = 0;
					}
					if($used_ratio >= 0.8) $databases[$i]['display_colour'] = '#fd934f';
					if($used_ratio >= 1) $databases[$i]['display_colour'] = '#cc0000';
						
					if($databases[$i]['database_quota'] == 0){
						$databases[$i]['database_quota'] = $app->lng('unlimited_txt');
					} else {
						$databases[$i]['database_quota'] = $databases[$i]['database_quota'] . ' MB';
					}
						
						
					if($databases[$i]['used'] < 1544000) {
						$databases[$i]['used'] = round($databases[$i]['used'] / 1024, 1).' KB';
					} else {
						$databases[$i]['used'] = round($databases[$i]['used'] / 1048576, 1).' MB';
					}
				}
			}
		}
	
		return $databases;
	}
	
}
