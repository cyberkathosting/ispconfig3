<?php

class dashlet_mailquota {

	function show() {
		global $app, $conf;

		//* Loading Template
		$app->uses('tpl');

		$tpl = new tpl;
		$tpl->newTemplate("dashlets/templates/mailquota.htm");

		$wb = array();
		$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dashlet_mailquota.lng';
		if(is_file($lng_file)) include $lng_file;
		$tpl->setVar($wb);

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
		if($_SESSION["s"]["user"]["typ"] != 'admin'){
			$sql_where = " AND sys_groupid = ".$_SESSION['s']['user']['default_group'];
		}

		$has_mailquota = false;
		// select email accounts belonging to client
		$emails = $app->db->queryAllRecords("SELECT * FROM mail_user WHERE 1".$sql_where);
		//print_r($emails);
		if(is_array($emails) && !empty($emails)){
			for($i=0;$i<sizeof($emails);$i++){
				$email = $emails[$i]['email'];

				$emails[$i]['used'] = isset($monitor_data[$email]['used']) ? $monitor_data[$email]['used'] : array(1 => 0);

				if (!is_numeric($emails[$i]['used'])) $emails[$i]['used']=$emails[$i]['used'][1];

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
			$has_mailquota = true;
			$tpl->setloop('mailquota', $emails);
		}
		//print_r($sites);

		$tpl->setVar('has_mailquota', $has_mailquota);

		return $tpl->grab();


	}

}








?>
