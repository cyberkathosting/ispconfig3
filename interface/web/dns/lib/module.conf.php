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

unset($items);


$items[] = array( 'title' 	=> "Add DNS Zone",
				  'target' 	=> 'content',
				  'link'	=> 'dns/dns_wizard.php');

if($_SESSION["s"]["user"]["typ"] == 'admin') {
				  
	$items[] = array( 	'title' 	=> "Templates",
				  		'target' 	=> 'content',
				  		'link'		=> 'dns/dns_template_list.php');
}


$module["nav"][] = array(	'title'	=> 'DNS Wizard',
							'open' 	=> 1,
							'items'	=> $items);











?>