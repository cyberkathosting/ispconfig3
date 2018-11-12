<?php

define('REMOTE_API_CALL', true);

require_once '../../lib/config.inc.php';
$conf['start_session'] = false;
require_once '../../lib/app.inc.php';

$app->load('rest_handler');
$rest_handler = new ISPConfigRESTHandler();
$rest_handler->run();

?>
