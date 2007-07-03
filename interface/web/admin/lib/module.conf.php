<?php

$module["name"] 		= "admin";
$module["title"] 		= "System";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "admin/users_list.php";
$module["tab_width"]    = '60';


$items[] = array( 'title' 	=> "Add user",
				  'target' 	=> 'content',
				  'link'	=> 'admin/users_edit.php');

$items[] = array( 'title' 	=> "Edit user",
				  'target' 	=> 'content',
				  'link'	=> 'admin/users_list.php');

				  
$module["nav"][] = array(	'title'	=> 'CP Users',
							'open' 	=> 1,
							'items'	=> $items);


// aufräumen
unset($items);

$items[] = array( 'title' 	=> "Add group",
				  'target' 	=> 'content',
				  'link'	=> 'admin/groups_edit.php');

$items[] = array( 'title' 	=> "Edit group",
				  'target' 	=> 'content',
				  'link'	=> 'admin/groups_list.php');

				  
$module["nav"][] = array(	'title'	=> 'Groups',
							'open' 	=> 1,
							'items'	=> $items);


// aufräumen
unset($items);
/*
$items[] = array( 'title' 	=> "Add server",
				  'target' 	=> 'content',
				  'link'	=> 'admin/server_edit.php');
*/
$items[] = array( 'title' 	=> "Edit server",
				  'target' 	=> 'content',
				  'link'	=> 'admin/server_list.php');
/*
$items[] = array( 'title' 	=> "Add Server IP",
				  'target' 	=> 'content',
				  'link'	=> 'admin/server_ip_edit.php');
*/
$items[] = array( 'title' 	=> "Edit Server IP",
				  'target' 	=> 'content',
				  'link'	=> 'admin/server_ip_list.php');				  


$module["nav"][] = array(	'title'	=> 'Servers',
							'open' 	=> 1,
							'items'	=> $items);


// aufräumen
unset($items);

$items[] = array( 'title' 	=> "Add user",
				  'target' 	=> 'content',
				  'link'	=> 'admin/dbsync_edit.php');

$items[] = array( 'title' 	=> "Edit user",
				  'target' 	=> 'content',
				  'link'	=> 'admin/dbsync_list.php');

				  
$items[] = array( 'title' 	=> "Sync. Now",
				  'target' 	=> 'content',
				  'link'	=> 'admin/dbsync_cron.php');

$module["nav"][] = array(	'title'	=> 'DB Sync.',
							'open' 	=> 1,
							'items'	=> $items);


// aufräumen
unset($items);

/*
$items[] = array( 'title' 	=> "Add user",
				  'target' 	=> 'content',
				  'link'	=> 'admin/filesync_edit.php');

$items[] = array( 'title' 	=> "Edit user",
				  'target' 	=> 'content',
				  'link'	=> 'admin/filesync_list.php');

				  
$module["nav"][] = array(	'title'	=> 'File Sync.',
							'open' 	=> 1,
							'items'	=> $items);


// aufräumen
unset($items);
*/


// Getting the admin options from other modules
$modules = explode(',',$_SESSION["s"]["user"]["modules"]);
if(is_array($modules)) {
	foreach($modules as $mt) {
		if(is_file($mt."/lib/admin.conf.php")) {
			$options = array();
			include_once($conf["rootpath"]."/web/".$mt."/lib/admin.conf.php");
			if(is_array($options)) {
				foreach($options as $opt) {
					$module["nav"][] = $opt;
				}
			}
		}
	}
}




?>