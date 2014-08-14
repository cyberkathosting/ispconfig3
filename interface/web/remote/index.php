<?php

require_once '../../lib/config.inc.php';
$conf['start_session'] = false;
require_once '../../lib/app.inc.php';

if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

$app->load('remoting,getconf');

$security_config = $app->getconf->get_security_config('permissions');
if($security_config['remote_api_allowed'] != 'yes') die('Remote API is disabled in security settings.');


$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
$server->setClass('remoting');
$server->handle();



?>
