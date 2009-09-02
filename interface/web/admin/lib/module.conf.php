<?php

global $conf;

$module['name'] 		= 'admin';
$module['title'] 		= 'System';
$module['template'] 	= 'module.tpl.htm';
$module['startpage'] 	= 'admin/users_list.php';
$module['tab_width']    = '60';


$items[] = array( 'title' 	=> 'Add user',
				  'target' 	=> 'content',
				  'link'	=> 'admin/users_edit.php');

$items[] = array( 'title' 	=> 'Edit user',
				  'target' 	=> 'content',
				  'link'	=> 'admin/users_list.php');

				  
$module['nav'][] = array(	'title'	=> 'CP Users',
							'open' 	=> 1,
							'items'	=> $items);


// cleanup
unset($items);

$items[] = array( 'title' 	=> 'Add group',
				  'target' 	=> 'content',
				  'link'	=> 'admin/groups_edit.php');

$items[] = array( 'title' 	=> 'Edit group',
				  'target' 	=> 'content',
				  'link'	=> 'admin/groups_list.php');

				  
$module['nav'][] = array(	'title'	=> 'Groups',
							'open' 	=> 1,
							'items'	=> $items);


// cleanup
unset($items);
/*
$items[] = array( 'title' 	=> 'Add server',
				  'target' 	=> 'content',
				  'link'	=> 'admin/server_edit.php');
*/
$items[] = array( 'title' 	=> 'Edit server',
				  'target' 	=> 'content',
				  'link'	=> 'admin/server_list.php');

$items[] = array( 'title' 	=> 'Server Config',
				  'target' 	=> 'content',
				  'link'	=> 'admin/server_config_list.php');

/*
$items[] = array( 'title' 	=> 'Add Server IP',
				  'target' 	=> 'content',
				  'link'	=> 'admin/server_ip_edit.php');
*/
$items[] = array( 'title' 	=> 'Edit Server IP',
				  'target' 	=> 'content',
				  'link'	=> 'admin/server_ip_list.php');				  


$items[] = array( 'title' 	=> 'Interface Config',
				  'target' 	=> 'content',
				  'link'	=> 'admin/system_config_edit.php?id=1');

$module['nav'][] = array(	'title'	=> 'System',
							'open' 	=> 1,
							'items'	=> $items);
// cleanup
unset($items);


$items[] = array( 'title' 	=> 'Firewall',
				  'target' 	=> 'content',
				  'link'	=> 'admin/firewall_list.php');				  


$module['nav'][] = array(	'title'	=> 'Firewall',
							'open' 	=> 1,
							'items'	=> $items);


// cleanup
unset($items);


$items[] = array( 'title' 	=> 'Repositories',
				  'target' 	=> 'content',
				  'link'	=> 'admin/software_repo_list.php');

$items[] = array( 'title' 	=> 'Packages',
				  'target' 	=> 'content',
				  'link'	=> 'admin/software_package_list.php');

$items[] = array( 'title' 	=> 'Updates',
				  'target' 	=> 'content',
				  'link'	=> 'admin/software_update_list.php');

$module['nav'][] = array(	'title'	=> 'Software',
							'open' 	=> 1,
							'items'	=> $items);


// cleanup
unset($items);

$items[] = array( 'title' 	=> 'Languages',
				  'target' 	=> 'content',
				  'link'	=> 'admin/language_list.php');

$items[] = array( 'title' 	=> 'New Language',
				  'target' 	=> 'content',
				  'link'	=> 'admin/language_add.php');
				  
$items[] = array( 'title' 	=> 'Merge',
				  'target' 	=> 'content',
				  'link'	=> 'admin/language_complete.php');

$items[] = array( 'title' 	=> 'Export',
				  'target' 	=> 'content',
				  'link'	=> 'admin/language_export.php');

$items[] = array( 'title' 	=> 'Import',
				  'target' 	=> 'content',
				  'link'	=> 'admin/language_import.php');

$module['nav'][] = array(	'title'	=> 'Language Editor',
							'open' 	=> 1,
							'items'	=> $items);


// cleanup
unset($items);

$items[] = array( 'title' 	=> 'Add user',
				  'target' 	=> 'content',
				  'link'	=> 'admin/remote_user_edit.php');

$items[] = array( 'title' 	=> 'Edit user',
				  'target' 	=> 'content',
				  'link'	=> 'admin/remote_user_list.php');

				  
$module['nav'][] = array(	'title'	=> 'Remote Users',
							'open' 	=> 1,
							'items'	=> $items);

// cleanup
unset($items);

// Getting the admin options from other modules
$modules = explode(',', $_SESSION['s']['user']['modules']);
if(is_array($modules)) {
	foreach($modules as $mt) {
		if(is_file($mt.'/lib/admin.conf.php')) {
			$options = array();
			include_once(ISPC_WEB_PATH."/$mt/lib/admin.conf.php");
			if(is_array($options)) {
				foreach($options as $opt) {
					$module['nav'][] = $opt;
				}
			}
		}
	}
}

?>