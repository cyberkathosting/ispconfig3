<?php

class dashlet_donate {

	function show() {
		global $app, $conf;
		
		if($app->auth->is_admin()) {
			
			//* Check if dashlet is not hidden
			
			//* Loading Template
			$app->uses('tpl');

			$tpl = new tpl;
			$tpl->newTemplate("dashlets/templates/donate.htm");

			$wb = array();
			$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dashlet_donate.lng';
			if(is_file($lng_file)) include $lng_file;
			$tpl->setVar($wb);

			return $tpl->grab();
			
		} else {
			return '';
		}

	}

}

?>
