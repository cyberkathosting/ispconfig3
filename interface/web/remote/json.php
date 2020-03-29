<?php

define('REMOTE_API_CALL', true);

require_once '../../lib/config.inc.php';
$conf['start_session'] = false;
require_once '../../lib/app.inc.php';

if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

$app->load('remoting_handler_base,json_handler,getconf');

$security_config = $app->getconf->get_security_config('permissions');
if($security_config['remote_api_allowed'] != 'yes') die('Remote API is disabled in security settings.');

$json_handler = new ISPConfigJSONHandler();
$json_handler->run();

?>
