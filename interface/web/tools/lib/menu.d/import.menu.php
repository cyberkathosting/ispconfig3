<?php

// Menu

if($app->auth->is_admin()) {

	$items = array();

	$items[] = array( 'title'  => 'ISPConfig 3 mail',
		'target'  => 'content',
		'link' => 'tools/import_ispconfig.php');

	$items[] = array( 'title'  => 'PDNS Tupa',
		'target'  => 'content',
		'link' => 'tools/dns_import_tupa.php');

	/* not yet complete
$items[] = array( 'title' 	=> 'Plesk',
				  'target' 	=> 'content',
				  'link'	=> 'tools/import_plesk.php');
*/

	$module['nav'][] = array( 'title' => 'Import',
		'open'  => 1,
		'items' => $items);

	unset($items);
}



?>
