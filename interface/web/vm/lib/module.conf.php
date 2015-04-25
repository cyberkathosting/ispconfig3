<?php

$module['name']   = 'vm';
$module['title']   = 'top_menu_vm';
$module['template']  = 'module.tpl.htm';
$module['startpage']  = 'vm/openvz_vm_list.php';
$module['tab_width']    = '';
$module['order'] = '50';

//**** Templates menu
$items = array();

$items[] = array( 'title'  => 'Virtual Servers',
	'target'  => 'content',
	'link' => 'vm/openvz_vm_list.php',
	'html_id' => 'openvz_vm_list');

if($_SESSION["s"]["user"]["typ"] == 'admin') {
	$items[] = array( 'title'  => 'OS Templates',
		'target'  => 'content',
		'link' => 'vm/openvz_ostemplate_list.php',
		'html_id' => 'openvz_ostemplate_list');

	$items[] = array( 'title'  => 'VM Templates',
		'target'  => 'content',
		'link' => 'vm/openvz_template_list.php',
		'html_id' => 'openvz_template_list');

	$items[] = array( 'title'  => 'IP addresses',
		'target'  => 'content',
		'link' => 'vm/openvz_ip_list.php',
		'html_id' => 'openvz_ip_list');
}
if(count($items))
{
	$module['nav'][] = array( 'title' => 'OpenVZ',
		'open'  => 1,
		'items' => $items);
}


//**** Statistics menu
/*
$items = array();

$items[] = array( 'title' 	=> 'Traffic',
				  'target' 	=> 'content',
				  'link'	=> 'vm/traffic_stats.php',
				  'html_id' => 'vm_traffic_stats');

$items[] = array( 'title' 	=> 'Vserver monitor',
				  'target' 	=> 'content',
				  'link'	=> 'vm/traffic_stats.php',
				  'html_id' => 'vm_traffic_stats');

if(count($items))
{
	$module['nav'][] = array(	'title'	=> 'Statistics',
								'open' 	=> 1,
								'items'	=> $items);
}
*/






?>
