<?php

$userid=$app->auth->get_user_id();

$module["name"] 		= "sites";
$module["title"] 		= "top_menu_sites";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "sites/web_domain_list.php";
$module["tab_width"]    = '';

/*
	Websites menu
*/
$items=array();

if($app->auth->get_client_limit($userid,'web_domain') != 0)
{
	$items[] = array( 'title' 	=> "Website",
			'target' 	=> 'content',
			'link'	=> 'sites/web_domain_list.php',
			'html_id' => 'domain_list');
}

if($app->auth->get_client_limit($userid,'web_subdomain') != 0)
{
	$items[] = array( 'title' 	=> "Subdomain",
			'target' 	=> 'content',
			'link'	=> 'sites/web_subdomain_list.php',
			'html_id' => 'subdomain_list');
}

if($app->auth->get_client_limit($userid,'web_aliasdomain') != 0)
{
	$items[] = array( 'title'   => "Aliasdomain",
			'target'  => 'content',
			'link'    => 'sites/web_aliasdomain_list.php',
			'html_id' => 'aliasdomain_list');
}

if(count($items))
{
	$module["nav"][] = array(	'title'	=> 'Websites',
								'open' 	=> 1,
								'items'	=> $items);
}


/*
	FTP User menu
*/
if($app->auth->get_client_limit($userid,'ftp_user') != 0)
{
	$items=array();
	
	$items[] = array( 'title' 	=> "FTP-User",
					  'target' 	=> 'content',
					  'link'	=> 'sites/ftp_user_list.php',
					  'html_id' => 'ftp_user_list');
	
	
	$module["nav"][] = array(	'title'	=> 'FTP',
								'open' 	=> 1,
								'items'	=> $items);
}

/*
	Shell User menu
*/
if($app->auth->get_client_limit($userid,'shell_user') != 0)
{
	$items=array();
	
	$items[] = array( 'title' 	=> "Shell-User",
					  'target' 	=> 'content',
					  'link'	=> 'sites/shell_user_list.php',
					  'html_id' => 'shell_user_list');	
	
	$module["nav"][] = array(	'title'	=> 'Shell',
								'open' 	=> 1,
								'items'	=> $items);
}

/*
	Databases menu
*/
if($app->auth->get_client_limit($userid,'database') != 0)
{
	$items=array();
	
	$items[] = array( 'title' 	=> "Database",
					  'target' 	=> 'content',
					  'link'	=> 'sites/database_list.php',
					  'html_id' => 'database_list'
					  );	
	
	$module["nav"][] = array(	'title'	=> 'Database',
								'open' 	=> 1,
								'items'	=> $items);
}

/*
 *	Webdav User menu
 */
if($app->auth->get_client_limit($userid,'webdav_user') != 0)
{
	$items=array();
	
	$items[] = array( 'title' 	=> "Webdav-User",
			'target' 	=> 'content',
			'link'	=> 'sites/webdav_user_list.php',
			'html_id' => 'webdav_user_list');
	
	
	$module["nav"][] = array(	'title'	=> 'Webdav',
			'open' 	=> 1,
			'items'	=> $items);
}

/*
 *	Web folder menu
 */
	$items=array();
	
	$items[] = array( 'title' 	=> "Folder",
			'target' 	=> 'content',
			'link'	=> 'sites/web_folder_list.php',
			'html_id' => 'web_folder_list');
	
	$items[] = array( 'title' 	=> "Folder users",
			'target' 	=> 'content',
			'link'	=> 'sites/web_folder_user_list.php',
			'html_id' => 'web_folder_user_list');
	
	$module["nav"][] = array(	'title'	=> 'Folder protection',
			'open' 	=> 1,
			'items'	=> $items);


/*
    Cron menu
*/
if($app->auth->get_client_limit($userid,'cron') != 0)
{
	$items = array();	
	
	$items[] = array( 'title'   => "Cron Jobs",
	                  'target'  => 'content',
	                  'link'    => 'sites/cron_list.php',
					  'html_id' => 'cron_list');	
	
	$module["nav"][] = array(   'title' => 'Cron',
	                            'open'  => 1,
	                            'items' => $items);
}

//*** APS menu
$items = array();

$items[] = array('title'   => 'Available packages',
                 'target'  => 'content',
                 'link'    => 'sites/aps_availablepackages_list.php',
                 'html_id' => 'aps_availablepackages_list');

$items[] = array('title'   => 'Installed packages',
                 'target'  => 'content',
                 'link'    => 'sites/aps_installedpackages_list.php',
                 'html_id' => 'aps_installedpackages_list');
                

// Second menu group, available only for admins
if($_SESSION['s']['user']['typ'] == 'admin') 
{
	$items[] = array('title'   => 'Update Packagelist',
                 'target'  => 'content',
                 'link'    => 'sites/aps_cron_apscrawler_if.php',
                 'html_id' => 'aps_packagedetails_show');
}

$module['nav'][] = array('title' => 'APS Installer',
                         'open'  => 1,
                         'items' => $items);


//**** Statistics menu
$items = array();

$items[] = array( 'title'   => 'Web traffic',
		'target'  => 'content',
		'link'    => 'sites/web_sites_stats.php',
		'html_id' => 'websites_stats');

$items[] = array( 'title'   => 'Website quota (Harddisk)',
		'target'  => 'content',
		'link'    => 'sites/user_quota_stats.php',
		'html_id' => 'user_quota_stats');


$module['nav'][] = array(   'title' => 'Statistics',
		'open'  => 1,
		'items' => $items);



// clean up
unset($items);


?>