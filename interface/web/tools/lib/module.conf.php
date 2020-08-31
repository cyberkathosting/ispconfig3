<?php

global $conf;

$module['name']  = 'tools';
$module['title']  = 'top_menu_tools';
$module['template']  = 'module.tpl.htm';
$module['startpage']  = 'tools/user_settings.php';
$module['tab_width']    = '60';
$module['order']    = '80';


//**** Change User password
$items = array();

$items[] = array(   'title'  => 'User Settings',
	'target'  => 'content',
	'link' => 'tools/user_settings.php',
	'html_id'   => 'user_settings');


$module['nav'][] = array(   'title' => 'User Settings',
	'open'  => 1,
	'items' => $items);

unset($items);
?>
