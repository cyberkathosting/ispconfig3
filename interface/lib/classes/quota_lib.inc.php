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
		if($clientid != null){
			$sites = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE active = 'y' AND type = 'vhost' AND sys_groupid = (SELECT default_group FROM sys_user WHERE client_id=?)", $app->functions->intval($client_id));
		}
		else {
			$sites = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE active = 'y' AND type = 'vhost'");
		}
		
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
						$sites[$i]['used'] = round($sites[$i]['used'] / 1024, 2).' MB';
					} else {
						if ($sites[$i]['used'] != '') $sites[$i]['used'] .= ' KB';
					}
			
					if($sites[$i]['soft'] > 1024) {
						$sites[$i]['soft'] = round($sites[$i]['soft'] / 1024, 2).' MB';
					} else {
						$sites[$i]['soft'] .= ' KB';
					}
			
					if($sites[$i]['hard'] > 1024) {
						$sites[$i]['hard'] = round($sites[$i]['hard'] / 1024, 2).' MB';
					} else {
						$sites[$i]['hard'] .= ' KB';
					}
			
					if($sites[$i]['soft'] == " KB") $sites[$i]['soft'] = $app->lng('unlimited');
					if($sites[$i]['hard'] == " KB") $sites[$i]['hard'] = $app->lng('unlimited');
					
					if($sites[$i]['soft'] == '0 B' || $sites[$i]['soft'] == '0 KB' || $sites[$i]['soft'] == '0') $sites[$i]['soft'] = $app->lng('unlimited');
					if($sites[$i]['hard'] == '0 B' || $sites[$i]['hard'] == '0 KB' || $sites[$i]['hard'] == '0') $sites[$i]['hard'] = $app->lng('unlimited');
					
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
		if($clientid != null){
			$emails = $app->db->queryAllRecords("SELECT * FROM mail_user WHERE sys_groupid = (SELECT default_group FROM sys_user WHERE client_id=?)", $app->functions->intval($client_id));
		}
		else {
			$emails = $app->db->queryAllRecords("SELECT * FROM mail_user");
		}
		
		//print_r($emails);
		if(is_array($emails) && !empty($emails)){
			for($i=0;$i<sizeof($emails);$i++){
				$email = $emails[$i]['email'];
		
				$emails[$i]['used'] = isset($monitor_data[$email]['used']) ? $monitor_data[$email]['used'] : array(1 => 0);
		
				if (!is_numeric($emails[$i]['used'])) $emails[$i]['used']=$emails[$i]['used'][1];
				
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
						$emails[$i]['quota'] = $app->lng('unlimited');
					} else {
						$emails[$i]['quota'] = round($emails[$i]['quota'] / 1048576, 4).' MB';
					}
			
			
					if($emails[$i]['used'] < 1544000) {
						$emails[$i]['used'] = round($emails[$i]['used'] / 1024, 4).' KB';
					} else {
						$emails[$i]['used'] = round($emails[$i]['used'] / 1048576, 4).' MB';
					}
				}
			}
		}
		
		return $emails;
	}
}