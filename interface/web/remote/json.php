<?php

require_once '../../lib/config.inc.php';
$conf['start_session'] = false;
require_once '../../lib/app.inc.php';

$app->load('json_handler');
$json_handler = new ISPConfigJSONHandler();
$json_handler->run();

?>
