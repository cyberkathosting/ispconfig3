<?php

$module["name"]  = "client";
$module["title"]  = "top_menu_client";
$module["template"]  = "module.tpl.htm";
$module["startpage"]  = "client/client_list.php";
$module["tab_width"]    = '';
$module['order']    = '20';


$items[] = array(   'title'  => "Edit Client",
	'target'  => 'content',
	'link' => 'client/client_list.php',
	'html_id'   => 'client_list');

$items[] = array(   'title'  => "Add Client",
	'target'  => 'content',
	'link' => 'client/client_edit.php',
	'html_id'   => 'client_add');

$module["nav"][] = array(   'title' => 'Clients',
	'open'  => 1,
	'items' => $items);

unset($items);


if($_SESSION["s"]["user"]["typ"] == 'admin'){

	$items[] = array(   'title'  => "Edit Reseller",
		'target'  => 'content',
		'link' => 'client/reseller_list.php',
		'html_id'   => 'reseller_list');

	$items[] = array(   'title'  => "Add Reseller",
		'target'  => 'content',
		'link' => 'client/reseller_edit.php',
		'html_id'   => 'reseller_add');

	$module["nav"][] = array(   'title' => 'Resellers',
		'open'  => 1,
		'items' => $items);

	unset($items);
}

$items[] = array(   'title'  => "Edit Client Circle",
	'target'  => 'content',
	'link' => 'client/client_circle_list.php',
	'html_id'   => 'client_circle_list');

$items[] = array(   'title'  => "Send email",
	'target'  => 'content',
	'link' => 'client/client_message.php',
	'html_id'   => 'reseller_add');

$module["nav"][] = array(   'title' => 'Messaging',
	'open'  => 1,
	'items' => $items);

unset($items);


$items[] = array(   'title'  => "Limit-Templates",
	'target'  => 'content',
	'link' => 'client/client_template_list.php',
	'html_id'   => 'client_template_list');

$items[] = array(   'title'  => "Email-Templates",
	'target'  => 'content',
	'link' => 'client/message_template_list.php',
	'html_id'   => 'message_template_list');

$module["nav"][] = array(   'title' => 'Templates',
	'open'  => 1,
	'items' => $items);

unset($items);


$app->uses('ini_parser,getconf');
$settings = $app->getconf->get_global_config('domains');

if ($settings['use_domain_module'] == 'y') {
	$items = array();
	$items[] = array( 'title'  => 'Domains',
		'target'  => 'content',
		'link' => 'client/domain_list.php');

	$module['nav'][] = array( 'title' => 'Domains',
		'open'  => 1,
		'items' => $items);

	unset($items);
}

/*
// GDPR functions for admin only, might be extended for resellers later
if($_SESSION["s"]["user"]["typ"] == 'admin'){
	$items = array();
	$items[] = array( 'title'  => 'Send Personal Data',
		'target'  => 'content',
		'link' => 'client/gdpr_send.php');

	$module['nav'][] = array( 'title' => 'GDPR',
		'open'  => 1,
		'items' => $items);

	unset($items);
}
*/

/*
if($_SESSION["s"]["user"]["typ"] == 'admin'){
$items[] = array(   'title' 	=> "Interface Settings",
                    'target' 	=> 'content',
                    'link'	=> 'client/reseller_admin_interface.php',
                    'html_id'   => 'reseller_admin_interface');


$module["nav"][] = array(   'title'	=> 'Reseller Panel',
                            'open' 	=> 1,
                            'items'	=> $items);

unset($items);
}
*/
?>
