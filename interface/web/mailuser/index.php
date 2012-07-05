<?php
require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('mailuser');

$app->uses('tpl');

$app->tpl->newTemplate('form.tpl.htm');
$app->tpl->setInclude('content_tpl', 'templates/index.htm');
$msg = '';
$error = '';

//* load language file
$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_index.lng';
include($lng_file);
$app->tpl->setVar($wb);

$sql = "SELECT * FROM mail_user WHERE mailuser_id = ".$_SESSION['s']['user']['mailuser_id'];
$rec = $app->db->queryOneRecord($sql);

if($rec['quota'] == 0) {
	$rec['quota'] = $wb['unlimited_txt'];
} else {
	$rec['quota'] = ($rec['quota'] / 1024 / 1024).' '.$wb['mb_txt'];
}

if($rec['cc'] == '') $rec['cc'] = $wb['none_txt'];

$app->tpl->setVar($rec);

$sql2 = "SELECT * FROM server WHERE server_id = ".$rec['server_id'];
$rec2 = $app->db->queryOneRecord($sql2);

$app->tpl->setVar($rec2);

$app->tpl->setVar('msg',$msg);
$app->tpl->setVar('error',$error);




$app->tpl_defaults();
$app->tpl->pparse();
?>