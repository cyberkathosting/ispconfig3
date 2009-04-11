<?php

$module["name"] 		= "domain";
$module["title"] 		= "Domain";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "domain/domain_list.php";
$module["tab_width"]    = '';

/*
	Websites menu
*/

$items[] = array( 'title' 	=> "Domain",
				  'target' 	=> 'content',
				  'link'	=> 'domain/domain_list.php');


$items[] = array( 'title' 	=> "TLD",
				  'target' 	=> 'content',
				  'link'	=> 'domain/tld_list.php');


$items[] = array( 'title' 	=> "Handle",
				  'target' 	=> 'content',
				  'link'	=> 'domain/handle_list.php');

$items[] = array( 'title' 	=> "Domain-Provider",
				  'target' 	=> 'content',
				  'link'	=> 'domain/provider_list.php');

$module["nav"][] = array(	'title'	=> 'Domain',
							'open' 	=> 1,
							'items'	=> $items);

// clean up
unset($items);

?>