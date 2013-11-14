<?php

$userid=$app->auth->get_user_id();

$module['name']   = 'mailuser';
$module['title']   = 'top_menu_mailuser';
$module['template']             = 'module.tpl.htm';
$module['startpage']            = 'mailuser/index.php';
$module['tab_width']            = '';


//**** menu
$items = array();

$items[] = array(  'title'     => 'Overview',
	'target'    => 'content',
	'link'      => 'mailuser/index.php',
	'html_id'   => 'mail_user_index');

$items[] = array(  'title'     => 'Password',
	'target'    => 'content',
	'link'      => 'mailuser/mail_user_password_edit.php',
	'html_id'   => 'mail_user_password');

$items[] = array(  'title'     => 'Autoresponder',
	'target'    => 'content',
	'link'      => 'mailuser/mail_user_autoresponder_edit.php',
	'html_id'   => 'mail_user_autoresponder');

$items[] = array(  'title'     => 'Send copy',
	'target'    => 'content',
	'link'      => 'mailuser/mail_user_cc_edit.php',
	'html_id'   => 'mail_user_cc');

$items[] = array(  'title'     => 'Spamfilter',
	'target'    => 'content',
	'link'      => 'mailuser/mail_user_spamfilter_edit.php',
	'html_id'   => 'mail_user_cc');

$items[] = array(  'title'     => 'Email Filters',
	'target'    => 'content',
	'link'      => 'mailuser/mail_user_filter_list.php',
	'html_id'   => 'mail_user_filter_list');


if(count($items)) {
	$module['nav'][] = array(   'title' => 'Email Account',
		'open'  => 1,
		'items' => $items);
}

?>
