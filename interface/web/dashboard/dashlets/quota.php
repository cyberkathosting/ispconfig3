<?php

class dashlet_quota {

	function show() {
		global $app;

		//* Loading Template
		$app->uses('tpl','quota_lib');

		$tpl = new tpl;
		$tpl->newTemplate("dashlets/templates/quota.htm");

		$wb = array();
		$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dashlet_quota.lng';
		if(is_file($lng_file)) include $lng_file;
		$tpl->setVar($wb);

		$sites = $app->quota_lib->get_quota_data( ($_SESSION["s"]["user"]["typ"] != 'admin') ? $_SESSION['s']['user']['default_group'] : null);
		//print_r($sites);

		$has_quota = false;
		if(is_array($sites) && !empty($sites)){
			$tpl->setloop('quota', $sites);
			$has_quota = isset($sites[0]['used']);
		}
		$tpl->setVar('has_quota', $has_quota);

		return $tpl->grab();


	}

}








?>
