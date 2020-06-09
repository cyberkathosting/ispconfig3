<?php

class dashlet_modules {

	function show() {
		global $app, $conf;

		//* Loading Template
		$app->uses('tpl');

		$tpl = new tpl;
		$tpl->newTemplate("dashlets/templates/modules.htm");

		$wb = array();
		$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dashlet_modules.lng';
		if(is_file($lng_file)) include $lng_file;
		$tpl->setVar($wb);

		/*
		 * Show all modules, the user is allowed to use
		*/
		$modules = explode(',', $_SESSION['s']['user']['modules']);
		$mod = array();
		if(is_array($modules)) {
			foreach($modules as $mt) {
				if(is_file('../' . $mt . '/lib/module.conf.php')) {
					if(!preg_match("/^[a-z]{2,20}$/i", $mt)) die('module name contains unallowed chars.');
					include_once '../' . $mt.'/lib/module.conf.php';
					/* We don't want to show the dashboard */
					if ($mt != 'dashboard') {
						if($mt == 'dns'){
							$dns_servers = $app->db->queryOneRecord("SELECT COUNT(*) as cnt FROM server WHERE dns_server = 1 AND active = 1");
							if($dns_servers['cnt'] == 0) continue;
						}
						if($mt == 'mail'){
							$mail_servers = $app->db->queryOneRecord("SELECT COUNT(*) as cnt FROM server WHERE mail_server = 1 AND active = 1");
							if($mail_servers['cnt'] == 0) continue;
						}
						if($mt == 'sites'){
							$web_servers = $app->db->queryOneRecord("SELECT COUNT(*) as cnt FROM server WHERE web_server = 1 AND active = 1");
							if($web_servers['cnt'] == 0) continue;
						}
						
						if($mt == 'vm'){
							$vserver_servers = $app->db->queryOneRecord("SELECT COUNT(*) as cnt FROM server WHERE vserver_server = 1 AND active = 1");
							if($vserver_servers['cnt'] == 0) continue;
						}
					
						$module_title = $app->lng($module['title']);
						if(function_exists('mb_strlen')) {
							if(mb_strlen($module_title, "UTF-8") > 8) $module_title = mb_substr($module_title, 0, 7, "UTF-8").'..';
						} else {
							if(strlen($module_title) > 8) $module_title = substr($module_title, 0, 7).'..';
						}
						$mod[$module['order'].'-'.$module['name']] = array( 'modules_title'  => $module_title,
							'modules_startpage' => $module['startpage'],
							'modules_name'   => $module['name']);
					}
				}
			}
			ksort($mod);
			$tpl->setloop('modules', $mod);
		}

		return $tpl->grab();

	}

}








?>
