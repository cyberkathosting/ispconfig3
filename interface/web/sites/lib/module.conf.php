<?php

$module["name"] 		= "sites";
$module["title"] 		= "top_menu_sites";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "sites/web_domain_list.php";
$module["tab_width"]    = '';

/*
	Websites menu
*/

$items[] = array( 'title' 	=> "Website",
		'target' 	=> 'content',
		'link'	=> 'sites/web_domain_list.php',
		'html_id' => 'domain_list');


$items[] = array( 'title' 	=> "Subdomain",
		'target' 	=> 'content',
		'link'	=> 'sites/web_subdomain_list.php',
		'html_id' => 'subdomain_list');


$items[] = array( 'title'   => "Aliasdomain",
		'target'  => 'content',
		'link'    => 'sites/web_aliasdomain_list.php',
		'html_id' => 'aliasdomain_list');

$module["nav"][] = array(	'title'	=> 'Websites',
		'open' 	=> 1,
		'items'	=> $items);

// clean up
unset($items);

/*
	FTP User menu
*/

$items[] = array( 'title' 	=> "FTP-User",
		'target' 	=> 'content',
		'link'	=> 'sites/ftp_user_list.php',
		'html_id' => 'ftp_user_list');


$module["nav"][] = array(	'title'	=> 'FTP',
		'open' 	=> 1,
		'items'	=> $items);

// clean up
unset($items);

/*
	Shell User menu
*/

$items[] = array( 'title' 	=> "Shell-User",
		'target' 	=> 'content',
		'link'	=> 'sites/shell_user_list.php',
		'html_id' => 'shell_user_list');


$module["nav"][] = array(	'title'	=> 'Shell',
		'open' 	=> 1,
		'items'	=> $items);

// clean up
unset($items);

/*
 *	Webdav User menu
 */
$items[] = array( 'title' 	=> "Webdav-User",
		'target' 	=> 'content',
		'link'	=> 'sites/webdav_user_list.php',
		'html_id' => 'webdav_user_list');


$module["nav"][] = array(	'title'	=> 'Webdav',
		'open' 	=> 1,
		'items'	=> $items);

// clean up
unset($items);


/*
	Databases menu
*/

$items[] = array( 'title' 	=> "Database",
		'target' 	=> 'content',
		'link'	=> 'sites/database_list.php',
		'html_id' => 'database_list');


$module["nav"][] = array(	'title'	=> 'Database',
		'open' 	=> 1,
		'items'	=> $items);


/*
    Cron menu
*/
$items = array();

$items[] = array( 'title'   => "Cron Jobs",
		'target'  => 'content',
		'link'    => 'sites/cron_list.php',
		'html_id' => 'cron_list');


$module["nav"][] = array(   'title' => 'Cron',
		'open'  => 1,
		'items' => $items);


//**** Statistics menu
$items = array();

$items[] = array( 'title'   => 'Web traffic',
		'target'  => 'content',
		'link'    => 'sites/web_sites_stats.php',
		'html_id' => 'websites_stats');


$module['nav'][] = array(   'title' => 'Statistics',
		'open'  => 1,
		'items' => $items);



// clean up
unset($items);


?>