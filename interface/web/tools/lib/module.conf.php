<?php

global $conf;

$module['name']  = 'tools';
$module['title']  = 'top_menu_tools';
$module['template']  = 'module.tpl.htm';
$module['startpage']  = 'tools/index.php';
$module['tab_width']    = '60';
$module['order']    = '80';


//**** Change User password
$items = array();

$items[] = array(   'title'  => 'Password and Language',
	'target'  => 'content',
	'link' => 'tools/user_settings.php',
	'html_id'   => 'user_settings');


$module['nav'][] = array(   'title' => 'User Settings',
	'open'  => 1,
	'items' => $items);

unset($items);

//**** Change interface settings + load settings page of the activated theme
$items = array();

$items[] = array(   'title'     => 'Interface',
	'target'  => 'content',
	'link' => 'tools/interface_settings.php',
	'html_id'   => 'interface_settings');

if(file_exists(ISPC_WEB_PATH.'/tools/lib/interface.d/tpl_' . $_SESSION['s']['user']['app_theme'] . '.menu.php')) include_once ISPC_WEB_PATH.'/tools/lib/interface.d/tpl_' . $_SESSION['s']['user']['app_theme'] . '.menu.php';

$module['nav'][] = array( 'title' => 'Interface',
	'open'  => 1,
	'items' => $items);

unset($items);



?>
