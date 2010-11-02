<?php


// Menu

if($app->auth->is_admin()) {

$items = array();

$items[] = array( 'title' 	=> 'Resync',
				  'target' 	=> 'content',
				  'link'	=> 'tools/dns_resync.php');


$module['nav'][] = array(	'title'	=> 'DNS Tools',
							'open' 	=> 1,
							'items'	=> $items);

unset($items);
}

?>