<?php

$module["name"] 		= "sites";
$module["title"] 		= "Sites";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "sites/web_domain_list.php";
$module["tab_width"]    = '';

/*
	Websites menu
*/

$items[] = array( 'title' 	=> "Domain",
				  'target' 	=> 'content',
				  'link'	=> 'sites/web_domain_list.php');


$items[] = array( 'title' 	=> "Subdomain",
				  'target' 	=> 'content',
				  'link'	=> 'sites/web_subdomain_list.php');


$items[] = array( 'title'   => "Aliasdomain",
                  'target'  => 'content',
                  'link'    => 'sites/web_aliasdomain_list.php');

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
				  'link'	=> 'sites/ftp_user_list.php');


$module["nav"][] = array(	'title'	=> 'FTP',
							'open' 	=> 1,
							'items'	=> $items);

// clean up
unset($items);

/*
	FTP User menu
*/

$items[] = array( 'title' 	=> "Shell-User",
				  'target' 	=> 'content',
				  'link'	=> 'sites/shell_user_list.php');


$module["nav"][] = array(	'title'	=> 'Shell',
							'open' 	=> 1,
							'items'	=> $items);

// clean up
unset($items);

/*
	Databases menu
*/

$items[] = array( 'title' 	=> "Database",
				  'target' 	=> 'content',
				  'link'	=> 'sites/database_list.php');


$module["nav"][] = array(	'title'	=> 'Database',
							'open' 	=> 1,
							'items'	=> $items);


//**** Statistics menu
$items = array();

$items[] = array( 'title'   => 'Web traffic',
                  'target'  => 'content',
                  'link'    => 'sites/web_sites_stats.php');


$module['nav'][] = array(   'title' => 'Statistics',
                            'open'  => 1,
                            'items' => $items);
                            
                            

// clean up
unset($items);


?>