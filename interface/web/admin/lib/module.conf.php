<?php

global $conf;

$module['name']  = 'admin';
$module['title']  = 'top_menu_system';
$module['template']  = 'module.tpl.htm';
$module['startpage']  = 'admin/server_list.php';
$module['tab_width']    = '60';
$module['order']    = '90';


$items[] = array(   'title'     => 'CP Users',
	'target'  => 'content',
	'link' => 'admin/users_list.php',
	'html_id'   => 'user_list');

$items[] = array(   'title'  => 'Remote Users',
	'target'  => 'content',
	'link' => 'admin/remote_user_list.php',
	'html_id'   => 'remote_user_list');

$module['nav'][] = array(   'title' => 'User Management',
	'open'  => 1,
	'items' => $items);

// cleanup
unset($items);

$items[] = array(   'title'  => 'Server Services',
	'target'  => 'content',
	'link' => 'admin/server_list.php',
	'html_id'   => 'server_list');

$items[] = array(   'title'  => 'Server Config',
	'target'  => 'content',
	'link' => 'admin/server_config_list.php',
	'html_id'   => 'server_config_list');

$items[] = array(   'title'  => 'Server IP addresses',
	'target'  => 'content',
	'link' => 'admin/server_ip_list.php',
	'html_id'   => 'server_ip_list');

$items[] = array(   'title'  => 'Server IPv4 mapping',
	'target'  => 'content',
	'link' => 'admin/server_ip_map_list.php',
	'html_id'   => 'server_ip_map_list');

$items[] = array(   'title'  => 'Additional PHP Versions',
	'target'  => 'content',
	'link' => 'admin/server_php_list.php',
	'html_id'   => 'server_php_list');

$items[] = array(   'title'  => 'Directive Snippets',
	'target'  => 'content',
	'link' => 'admin/directive_snippets_list.php',
	'html_id'   => 'directive_snippets_list');

$items[] = array(   'title'  => 'Firewall',
	'target'  => 'content',
	'link' => 'admin/firewall_list.php',
	'html_id'   => 'firewall_list');

/*
$items[] = array( 'title' 	=> 'Firewall IPTables',
				  'target' 	=> 'content',
				  'link'	=> 'admin/iptables_list.php');

$items[] = array( 'title' 	=> 'Packet Filter',
				  'target' 	=> 'content',
				  'link'	=> 'admin/firewall_filter_list.php');

$items[] = array( 'title' 	=> 'Port Forward',
				  'target' 	=> 'content',
				  'link'	=> 'admin/firewall_forward_list.php');
*/

$module['nav'][] = array(   'title'     => 'System',
	'open'  => 1,
	'items' => $items);
// cleanup
unset($items);

$items[] = array(   'title'  => 'Interface Config',
	'target'  => 'content',
	'link' => 'admin/system_config_edit.php?id=1',
	'html_id'   => 'interface_config');

$module['nav'][] = array(   'title'     => 'Interface',
	'open'      => "1",
	'items'     => $items);


// cleanup
unset($items);


$items[] = array(   'title'  => 'Repositories',
	'target'  => 'content',
	'link' => 'admin/software_repo_list.php',
	'html_id'   => 'software_repo_list');

$items[] = array(   'title'  => 'Packages',
	'target'  => 'content',
	'link' => 'admin/software_package_list.php',
	'html_id'   => 'software_package_list');

$items[] = array(   'title'  => 'Updates',
	'target'  => 'content',
	'link' => 'admin/software_update_list.php',
	'html_id'   => 'software_update_list');

$module['nav'][] = array(   'title'     => 'Software',
	'open'  => 1,
	'items' => $items);


// cleanup
unset($items);

$items[] = array(   'title'  => 'Languages',
	'target'  => 'content',
	'link' => 'admin/language_list.php',
	'html_id'   => 'language_list');

$items[] = array(   'title'  => 'New Language',
	'target'  => 'content',
	'link' => 'admin/language_add.php',
	'html_id'   => 'language_add');

$items[] = array(   'title'  => 'Merge',
	'target'  => 'content',
	'link' => 'admin/language_complete.php',
	'html_id'   => 'language_complete');

$items[] = array(   'title'  => 'Export',
	'target'  => 'content',
	'link' => 'admin/language_export.php',
	'html_id'   => 'language_export');

$items[] = array(   'title'  => 'Import',
	'target'  => 'content',
	'link' => 'admin/language_import.php',
	'html_id'   => 'language_import');

$module['nav'][] = array(   'title'     => 'Language Editor',
	'open'      => 1,
	'items'     => $items);


// cleanup
unset($items);


$items[] = array(   'title'  => 'Do OS-Update',
	'target'  => 'content',
	'link' => 'admin/remote_action_osupdate.php',
	'html_id'   => 'osupdate');

// ISPConfig interface update has been removed. Please use ispconfig_update.sh on the shell instead.
$items[] = array(   'title'  => 'Do ISPConfig-Update',
	'target'  => 'content',
	'link' => 'admin/remote_action_ispcupdate.php',
	'html_id'   => 'ispcupdate');

$module['nav'][] = array(   'title' => 'Remote Actions',
	'open'  => 1,
	'items' => $items);


// Getting the admin options from other modules
$modules = explode(',', $_SESSION['s']['user']['modules']);
if(is_array($modules)) {
	foreach($modules as $mt) {
		if(is_file($mt.'/lib/admin.conf.php')) {
			$options = array();
			include_once ISPC_WEB_PATH."/$mt/lib/admin.conf.php";
			if(is_array($options)) {
				foreach($options as $opt) {
					$module['nav'][] = $opt;
				}
			}
		}
	}
}

?>
