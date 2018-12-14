<?php

// for testing, put this file in interface/web/tools

require_once '../../lib/config.inc.php';
require_once '../../lib/app.inc.php';

//* Check permissions for module
$app->auth->check_module_permissions('admin');

$app->uses('getconf,ispcmail');
$mail_config = $app->getconf->get_global_config('mail');
if($mail_config['smtp_enabled'] == 'y') {
	$mail_config['use_smtp'] = true;
	$app->ispcmail->setOptions($mail_config);
}

$to = 't.heller@timmehosting.de';
$subject = 'Test von ISPConfig-Mailqueue';
$text = '123'."\n\n".date(DATE_RFC822)."\n\n".'SMTP: '.$mail_config['use_smtp'];
$from = 'ispconfig@thomas.timmeserver.de';
$filepath = '';
$filetype = 'application/pdf';
$filename = '';
$cc = '';
$bcc = '';
$from_name = 'ISPConfig';

$app->ispcmail->setSender($from, $from_name);
$app->ispcmail->setSubject($subject);
$app->ispcmail->setMailText($text);

if($filepath != '') {
	if(!file_exists($filepath)) $app->error("Mail attachement does not exist ".$filepath);
	$app->ispcmail->readAttachFile($filepath);
}

if($cc != '') $app->ispcmail->setHeader('Cc', $cc);
if($bcc != '') $app->ispcmail->setHeader('Bcc', $bcc);

$app->ispcmail->send($to);
$app->ispcmail->finish();

echo $text;

