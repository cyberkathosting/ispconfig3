<?php

require_once '../../lib/config.inc.php';
$conf['start_session'] = false;
require_once '../../lib/app.inc.php';

if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

$app->load('soap_handler');

$server = new SoapServer(null, array('uri' => $_SERVER['REQUEST_URI']));
$server->setObject(new ISPConfigSoapHandler());
$server->handle();

?>
