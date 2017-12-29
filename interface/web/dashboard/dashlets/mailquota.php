<?php

class dashlet_mailquota {

	function show() {
		global $app;

		//* Loading Template
		$app->uses('tpl,quota_lib');

		$tpl = new tpl;
		$tpl->newTemplate("dashlets/templates/mailquota.htm");

		$wb = array();
		$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dashlet_mailquota.lng';
		if(is_file($lng_file)) include $lng_file;
		$tpl->setVar($wb);

		$emails = $app->quota_lib->get_mailquota_data( ($_SESSION["s"]["user"]["typ"] != 'admin') ? $_SESSION['s']['user']['client_id'] : null);
		//print_r($emails);

		$has_mailquota = false;
		if(is_array($emails) && !empty($emails)){
			// email username is quoted in quota.lib already, so no htmlentities here to prevent double encoding
			//$emails = $app->functions->htmlentities($emails);
			$tpl->setloop('mailquota', $emails);
			$has_mailquota = isset($emails[0]['used']);
		}
		$tpl->setVar('has_mailquota', $has_mailquota);
		
		return $tpl->grab();
	}

}








?>
