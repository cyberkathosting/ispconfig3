<?php

class dashlet_quota {

	function show() {
		global $app, $conf;

		//* Loading Template
		$app->uses('tpl');

		$tpl = new tpl;
		$tpl->newTemplate("dashlets/templates/quota.htm");

		$wb = array();
		$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dashlet_quota.lng';
		if(is_file($lng_file)) include $lng_file;
		$tpl->setVar($wb);

		$tmp_rec =  $app->db->queryAllRecords("SELECT data from monitor_data WHERE type = 'harddisk_quota' ORDER BY created DESC");
		$monitor_data = array();
		if(is_array($tmp_rec)) {
			foreach ($tmp_rec as $tmp_mon) {
				$monitor_data = array_merge_recursive($monitor_data, unserialize($app->db->unquote($tmp_mon['data'])));
			}
		}
		//print_r($monitor_data);
		if($_SESSION["s"]["user"]["typ"] != 'admin'){
			$sql_where = " AND sys_groupid = ".$app->functions->intval($_SESSION['s']['user']['default_group']);
		}

		$has_quota = false;
		// select websites belonging to client
		$sites = $app->db->queryAllRecords("SELECT * FROM web_domain WHERE active = 'y' AND type = 'vhost'".$sql_where);
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


				/*
				if(!strstr($sites[$i]['used'],'M') && !strstr($sites[$i]['used'],'K')) $sites[$i]['used'].= ' B';
				if(!strstr($sites[$i]['soft'],'M') && !strstr($sites[$i]['soft'],'K')) $sites[$i]['soft'].= ' B';
				if(!strstr($sites[$i]['hard'],'M') && !strstr($sites[$i]['hard'],'K')) $sites[$i]['hard'].= ' B';
				*/

				if($sites[$i]['soft'] == '0 B' || $sites[$i]['soft'] == '0 KB' || $sites[$i]['soft'] == '0') $sites[$i]['soft'] = $app->lng('unlimited');
				if($sites[$i]['hard'] == '0 B' || $sites[$i]['hard'] == '0 KB' || $sites[$i]['hard'] == '0') $sites[$i]['hard'] = $app->lng('unlimited');

			}
			$has_quota = true;
			$tpl->setloop('quota', $sites);
		}
		//print_r($sites);

		$tpl->setVar('has_quota', $has_quota);

		return $tpl->grab();


	}

}








?>
