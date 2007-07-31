<?php

$module["name"] 		= "dns";
$module["title"] 		= "DNS";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "dns/dns_soa_list.php";
$module["tab_width"]    = '';

/*
	Email accounts menu
*/


$items[] = array( 'title' 	=> "Zones",
				  'target' 	=> 'content',
				  'link'	=> 'dns/dns_soa_list.php');
/*
$items[] = array( 'title' 	=> "A-Records",
				  'target' 	=> 'content',
				  'link'	=> 'dns/dns_a_list.php');
*/


$module["nav"][] = array(	'title'	=> 'DNS',
							'open' 	=> 1,
							'items'	=> $items);
?>