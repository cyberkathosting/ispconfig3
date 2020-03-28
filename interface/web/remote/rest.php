<?php

define('REMOTE_API_CALL', true);

require_once '../../lib/config.inc.php';
$conf['start_session'] = false;
require_once '../../lib/app.inc.php';

$app->load('remoting_handler_base,rest_handler,getconf');

$security_config = $app->getconf->get_security_config('permissions');
if($security_config['remote_api_allowed'] != 'yes') die('Remote API is disabled in security settings.');

$rest_handler = new ISPConfigRESTHandler();
$rest_handler->run();

?>
