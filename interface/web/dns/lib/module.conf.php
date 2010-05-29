<?php

$module["name"] 		= "dns";
$module["title"] 		= "top_menu_dns";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "dns/dns_soa_list.php";
$module["tab_width"]    = '';


$items[] = array( 'title' 	=> "Add DNS Zone",
				  'target' 	=> 'content',
				  'link'	=> 'dns/dns_wizard.php',
				  'html_id' => 'dns_wizard');

if($_SESSION["s"]["user"]["typ"] == 'admin') {
  $items[] = array( 	'title' 	=> "Templates",
				  'target' 	=> 'content',
				  'link'		=> 'dns/dns_template_list.php',
				  'html_id' => 'dns_template_list');
}


$module["nav"][] = array(	'title'	=> 'DNS Wizard',
							'open' 	=> 1,
							'items'	=> $items);


unset($items);

/*
	Email accounts menu
*/


$items[] = array( 'title' 	=> "Zones",
				  'target' 	=> 'content',
				  'link'	=> 'dns/dns_soa_list.php',
				  'html_id' => 'dns_soa_list');
/*
$items[] = array( 'title' 	=> "A-Records",
				  'target' 	=> 'content',
				  'link'	=> 'dns/dns_a_list.php',
				  'html_id' => 'dns_a_list');
*/


$module["nav"][] = array(	'title'	=> 'DNS',
							'open' 	=> 1,
							'items'	=> $items);

unset($items);








?>