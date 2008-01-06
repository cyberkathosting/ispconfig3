<?php

$module["name"] 		= "client";
$module["title"] 		= "Client";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "client/client_list.php";
$module["tab_width"]    = '';

/*
	Email accounts menu
*/


$items[] = array( 'title' 	=> "Add Client",
				  'target' 	=> 'content',
				  'link'	=> 'client/client_edit.php');

$items[] = array( 'title' 	=> "Edit Client",
				  'target' 	=> 'content',
				  'link'	=> 'client/client_list.php');


$module["nav"][] = array(	'title'	=> 'Clients',
							'open' 	=> 1,
							'items'	=> $items);
?>